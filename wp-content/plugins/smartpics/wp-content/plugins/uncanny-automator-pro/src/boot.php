<?php

namespace Uncanny_Automator_Pro;

/**
 * Class Boot
 *
 * @package Uncanny_Automator_Pro
 */
class Boot {


	/**
	 * The instance of the class
	 *
	 * @since  1.0.0
	 * @access private
	 * @var    Boot
	 */
	private static $instance = null;

	/**
	 * @var Automator_Pro_Helpers_Recipe
	 */
	public static $internal_helpers;

	/**
	 * @var
	 */
	public $internal_process;

	/**
	 * @var Internal_Triggers_Actions
	 */
	public $internal_triggers_actions;

	/**
	 * @var Automator_Pro_Cache_Handler
	 */
	public $automator_pro_cache_handler;

	/**
	 * @var
	 */
	public $installer;

	/**
	 * @var array
	 */
	public static $core_class_inits = array();

	/**
	 * class constructor
	 */
	public function __construct() {

		$this->initialize_db();

		add_action( 'plugins_loaded', array( $this, 'maybe_include_action_scheduler' ), - 10 );
		add_action( 'plugins_loaded', array( $this, 'require_traits_files' ), 5 );

		$this->initialize_loops();
		$this->require_class_files();

		add_action( 'plugins_loaded', array( $this, 'boot_child_plugin' ), 9 );
		add_filter( 'upgrader_pre_install', array( $this, 'upgrader_pre_install' ), 99, 2 );

		// Show upgrade notice from readme.txt
		add_action( 'in_plugin_update_message-' . plugin_basename( AUTOMATOR_PRO_FILE ), array( $this, 'in_plugin_update_message' ), 10, 2 );

		if ( is_file( UAPro_ABSPATH . 'vendor/autoload.php' ) ) {
			include_once UAPro_ABSPATH . 'vendor/autoload.php';
		}

		// Load up the setup wizard.
		$this->initialize_setup_wizard();
		add_action( 'upgrader_process_complete', array( $this, 'flag_last_updated' ), 10, 2 );
		add_filter( 'automator_system_report_get', array( $this, 'display_last_updated' ), 10, 1 );
	}

	/**
	 * Displays last updated in the system report.
	 *
	 * @param mixed $system_report
	 *
	 * @return mixed[]
	 */
	public function display_last_updated( $system_report ) {

		$pro_ver = $system_report['environment']['pro_version'] ?? null;

		$last_updated_dt_string = get_option( 'automator_pro_last_updated' );

		// Bail environmental variable 'pro_version' is null or if there are no records in the DB.
		if ( empty( $pro_ver ) || empty( $last_updated_dt_string ) ) {
			return $system_report;
		}

		$system_report['environment']['pro_version'] = sprintf( '%s (Updated: %s)', $pro_ver, $last_updated_dt_string );

		return $system_report;
	}

	/**
	 * Flags the last automator pro update time.
	 *
	 * @var object $upgrader_object
	 * @var string[] $options
	 *
	 * @return void
	 */
	public function flag_last_updated( $upgrader_object, $options ) {
		// Bail if the options are not set.
		if ( ! isset( $options['action'] ) || ! isset( $options['type'] ) ) {
			return;
		}

		// Check if it's a plugin update.
		if ( 'update' !== $options['action'] || 'plugin' !== $options['type'] ) {
			return;
		}

		// The plugins being updated are stored in the 'plugins' key of the options array.
		if ( ! isset( $options['plugins'] ) || ! is_array( $options['plugins'] ) ) {
			return;
		}

		// The path to the specific plugin to check for, relative to the wp-content/plugins directory.
		$specific_plugin_path = plugin_basename( AUTOMATOR_PRO_FILE );

		foreach ( $options['plugins'] as $plugin_path ) {
			if ( $plugin_path !== $specific_plugin_path ) {
				continue;
			}
			// Update an option with the current time for the specific plugin
			update_option( 'automator_pro_last_updated', current_time( 'mysql' ) );
			break; // No need to continue the loop
		}
	}

	/**
	 * Creates required tables
	 *
	 * @return false
	 */
	private function initialize_db() {
		$schema = new Schema();
		$schema->create_tables();
		// Adds new tables in the Automator > Status > Tools > Database.
		$schema->tools_attach_tables();
	}

	/**
	 * Initializes setup wizard in pro.
	 *
	 * @return void
	 */
	public function initialize_setup_wizard() {
		$setup_wizard = new Setup_Wizard\Setup_Wizard();
		$setup_wizard->register_hooks();
	}

	/**
	 * Starts the loop's required hooks, post types, etc.
	 *
	 * @return void
	 */
	public function initialize_loops() {

		// Initializes loop queue health check.
		( new Loops\Loop\Background_Process\Queue_Health_Check() )->init_hook();

		// The process registry.
		( Loops_Process_Registry::get_instance() );

		// The loops entry point.
		( Loops\Entry_Point::get_instance() );

		// Registers the various process callbacks.
		( new Loops\Process_Hooks_Callback() )->register_hooks();

		// Registers the token definitions for all loops type.
		Loops\Token\Common\Definition::register_hooks();
		// Registers the token definitions for posts loop.
		Loops\Token\Posts\Definition::register_hooks();
		// Registers the token definitions for users loop.
		Loops\Token\Users\Definition::register_hooks();

		// Initializes the post types.
		$this->initialize_loops_post_types();

		// Initializes the recipe ui endpoints.
		$this->initialize_loops_recipe_endpoints();
	}

	/**
	 * Initializes recipe endpoints.
	 *
	 * @return void
	 */
	public function initialize_loops_recipe_endpoints() {

		( new Loops\Recipe\Endpoint() )->register_hooks();
	}

	/**
	 * Registers loops post types.
	 *
	 * @return void
	 */
	public function initialize_loops_post_types() {

		$loop = new Loops\Loop_Post_Types();

		// Registers the loop's post type.
		add_action( 'init', array( $loop, 'register_loop_post_type' ) );
		// Registers the loop's filter's post type.
		add_action( 'init', array( $loop, 'register_filter_post_type' ) );
	}

	/**
	 * @return void
	 */
	public function maybe_include_action_scheduler() {
		// Check if already loading by another plugin
		if ( class_exists( 'ActionScheduler', false ) ) {
			return;
		}
		// If file exists, then queue it
		if ( is_file( dirname( AUTOMATOR_PRO_FILE ) . '/vendor/woocommerce/action-scheduler/action-scheduler.php' ) ) {
			include_once dirname( AUTOMATOR_PRO_FILE ) . '/vendor/woocommerce/action-scheduler/action-scheduler.php';
		}
	}

	/**
	 * Creates singleton instance of Boot class and defines which directories
	 * are autoloaded
	 *
	 * @return Boot
	 * @since  1.0.0
	 */
	public static function get_instance() {

		if ( null === self::$instance ) {
			// Lets boot up!
			self::$instance = new self();
		}

		return self::$instance;
	}

	/**
	 * Require the action tokens traits.
	 *
	 * @return void
	 * @since  4.6
	 */
	public function require_traits_files() {

		require_once trailingslashit( __DIR__ ) . 'core/traits/action-tokens.php';
	}

	/**
	 * SPL Auto Loader functions
	 *
	 * @param string $class
	 *
	 * @since 1.0.0
	 */
	private function require_class_files() {
		/**
		 * Webhook related files - Start
		 */
		self::$core_class_inits['Webhook_Rest_Handler']        = __DIR__ . '/core/webhook/webhook-rest-handler.php';
		self::$core_class_inits['Webhook_Rest_Sample_Handler'] = __DIR__ . '/core/webhook/webhook-rest-sample-handler.php';
		self::$core_class_inits['Webhook_Ajax_Handler']        = __DIR__ . '/core/webhook/webhook-ajax-handler.php';
		self::$core_class_inits['Webhook_Static_Content']      = __DIR__ . '/core/webhook/webhook-static-content.php';
		self::$core_class_inits['Webhook_Common_Options']      = __DIR__ . '/core/webhook/webhook-common-options.php';

		/**
		 * Webhook related files - End
		 */
		self::$core_class_inits['Automator_Pro_Handle_Anonymous'] = __DIR__ . '/core/classes/automator-pro-handle-anonymous.php';
		self::$core_class_inits['Magic_Button']                   = __DIR__ . '/core/classes/magic-button.php';
		self::$core_class_inits['Pro_Filters']                    = __DIR__ . '/core/classes/pro-filters.php';
		self::$core_class_inits['Pro_Ui']                         = __DIR__ . '/core/classes/pro-ui.php';
		self::$core_class_inits['Activity_Log_Settings']          = __DIR__ . '/core/extensions/activity-log-settings.php';
		self::$core_class_inits['Async_Actions']                  = __DIR__ . '/core/classes/async-actions.php';
		self::$core_class_inits['Actions_Conditions']             = __DIR__ . '/core/classes/actions-conditions.php';
		self::$core_class_inits['Migrate_Integrations_Items']     = __DIR__ . '/core/classes/class-migrate-integration-items.php';

		// Licensing
		self::$core_class_inits['Licensing'] = __DIR__ . '/core/admin/licensing/licensing.php';

		// Blocks
		self::$core_class_inits['Blocks'] = __DIR__ . '/blocks/blocks.php';
	}

	/**
	 * Looks through all defined directories and modifies file name to create
	 * new class instance.
	 *
	 * @since 1.0.0
	 */
	private function auto_initialize_classes() {
		foreach ( self::$core_class_inits as $class_name => $file ) {
			include_once $file;
			$class = __NAMESPACE__ . '\\' . $class_name;
			Utilities::add_class_instance( $class, new $class() );
		}
	}

	/**
	 * @param $args
	 * @param $response
	 */
	public function in_plugin_update_message( $args, $response ) {
		$upgrade_notice = '';
		if ( isset( $response->upgrade_notice ) && ! empty( $response->upgrade_notice ) ) {
			$upgrade_notice .= '<p class="ua_plugin_upgrade_notice">';
			$upgrade_notice .= sprintf( '<strong>%s</strong> %s', esc_html_x( 'Heads up!', 'Upgrade notice', 'uncanny-automator' ), preg_replace( '~\[([^\]]*)\]\(([^\)]*)\)~', '<a href="${2}">${1}</a>', $response->upgrade_notice ) );
			$upgrade_notice .= '</p>';
		}

		echo apply_filters( 'uap_pro_in_plugin_update_message', wp_kses_post( $upgrade_notice ) ? '</p>' . wp_kses_post( $upgrade_notice ) . '<p class="dummy">' : '' ); // phpcs:ignore WordPress.XSS.EscapeOutput.OutputNotEscaped, WordPress.Security.EscapeOutput.OutputNotEscaped
	}

	/**
	 * Placeholder function for future use
	 *
	 * @param $response
	 * @param $extra
	 *
	 * @return mixed
	 *
	 * @since  2.8
	 * @author Saad S.
	 */
	public function upgrader_pre_install( $response, $extra ) {

		return $response;
	}

	/**
	 * @return void
	 */
	public function boot_child_plugin() {

		if ( ! $this->is_base_plugin_installed() ) {
			add_action( 'admin_init', array( $this, 'require_plugin_installer' ) );

			return;
		}

		if ( defined( 'AUTOMATOR_BASE_FILE' ) ) {

			if ( class_exists( '\Uncanny_Automator\Automator_Recipe_Process' ) ) {
				include_once Utilities::get_include( 'automator-pro-recipe-process.php' );
				$this->internal_process = new Automator_Pro_Recipe_Process();
			}

			if ( class_exists( '\Uncanny_Automator\Automator_Recipe_Process_User' ) ) {
				include_once Utilities::get_include( 'automator-pro-recipe-process-user.php' );
				include_once Utilities::get_include( 'automator-pro-recipe-process-anon.php' );
				Automator_Pro()->process->anon = new Automator_Pro_Recipe_Process_Anon();
			}

			if ( class_exists( '\Uncanny_Automator\Automator_Recipe_Process_Complete' ) ) {
				include_once Utilities::get_include( 'automator-pro-recipe-process-complete.php' );
				Automator_Pro()->complete->anon = new Automator_Pro_Recipe_Process_Complete();
			}

			include_once Utilities::get_include( 'automator-pro-helpers-recipe.php' );
			self::$internal_helpers = new Automator_Pro_Helpers_Recipe();

		} else {

			Automator_Pro()->helpers                  = new \stdClass();
			Automator_Pro()->process                  = new \stdClass();
			Automator_Pro()->helpers->recipe          = new \stdClass();
			Automator_Pro()->helpers->recipe->field   = Automator_Pro()->options;
			Automator_Pro()->helpers->recipe->options = Automator_Pro()->options;

		}

		include_once Utilities::get_include( 'automator-pro-cache-handler.php' );
		$this->automator_pro_cache_handler = new Automator_Pro_Cache_Handler();

		include_once Utilities::get_include( 'internal-triggers-actions.php' );
		$this->internal_triggers_actions = new Internal_Triggers_Actions();

		// Initialize all classes in given directories
		$this->auto_initialize_classes();

		/* Licensing */
		// URL of store powering the plugin
		/**
		 * @deprecated v3.1 use AUTOMATOR_PRO_STORE_URL
		 */
		define( 'AUTOMATOR_AUTOMATOR_PRO_STORE_URL', AUTOMATOR_STORE_URL ); // you should use your own CONSTANT name, and be sure to replace it throughout this file

		// Store download name/title
		/**
		 * @deprecated v3.1 use AUTOMATOR_PRO_ITEM_NAME
		 */
		define( 'AUTOMATOR_AUTOMATOR_PRO_ITEM_NAME', AUTOMATOR_PRO_ITEM_NAME ); // you should use your own CONSTANT name, and be sure to replace it throughout this file
		/**
		 * Make sure to force true in Free IF Pro >= 3.9 And Free is < 3.9
		 */
		add_filter(
			'automator_do_load_options',
			function ( $status, $class_name ) {
				$migrated = apply_filters(
					'automator_pro_load_options_override',
					array(
						'Uncanny_Automator\Woocommerce_Helpers',
						'Uncanny_Automator\Divi_Helpers',
						'Uncanny_Automator\Elementor_Helpers',
						'Uncanny_Automator\Wp_Helpers',
						'Uncanny_Automator\Wpforms_Helpers',
						'Uncanny_Automator\Wp_Fusion_Helpers',
						'Uncanny_Automator\Edd_Helpers',
						'Uncanny_Automator\Learndash_Helpers',
						'Uncanny_Automator\Buddyboss_Helpers',
						'Uncanny_Automator\Buddypress_Helpers',
						'Uncanny_Automator\Gravity_Forms_Helpers',
						'Uncanny_Automator\Bbpress_Helpers',
						'Uncanny_Automator\Badgeos_Helpers',
						'Uncanny_Automator\Gamipress_Helpers',
						'Uncanny_Automator\Event_Tickets_Helpers',
						'Uncanny_Automator\Affwp_Helpers',
						'Uncanny_Automator\Caldera_Helpers',
						'Uncanny_Automator\Contact_Form7_Helpers',
						'Uncanny_Automator\Events_Manager_Helpers',
						'Uncanny_Automator\Fluent_Crm_Helpers',
						'Uncanny_Automator\Formidable_Helpers',
						'Uncanny_Automator\Forminator_Helpers',
						'Uncanny_Automator\Give_Helpers',
						'Uncanny_Automator\H5p_Helpers',
						'Uncanny_Automator\Happyforms_Helpers',
						'Uncanny_Automator\Learnpress_Helpers',
						'Uncanny_Automator\Lifterlms_Helpers',
						'Uncanny_Automator\Mailpoet_Helpers',
						'Uncanny_Automator\Masterstudy_Helpers',
						'Uncanny_Automator\Memberpress_Courses_Helpers',
						'Uncanny_Automator\Memberpress_Helpers',
						'Uncanny_Automator\MEC_HELPERS',
						'Uncanny_Automator\Mycred_Helpers',
						'Uncanny_Automator\Ninja_Forms_Helpers',
						'Uncanny_Automator\Paid_Memberships_Pro_Helpers',
						'Uncanny_Automator\Presto_Helpers',
						'Uncanny_Automator\Restrict_Content_Helpers',
						'Uncanny_Automator\Tutorlms_Helpers',
						'Uncanny_Automator\Ultimate_Member_Helpers',
						'Uncanny_Automator\Uncanny_Ceus_Helpers',
						'Uncanny_Automator\Uncanny_Groups_Helpers',
						'Uncanny_Automator\Uncanny_Codes_Helpers',
						'Uncanny_Automator\Uncanny_Toolkit_Helpers',
						'Uncanny_Automator\Upsell_Plugin_Helpers',
						'Uncanny_Automator\Wc_Memberships_Helpers',
						'Uncanny_Automator\Wishlist_Member_Helpers',
						'Uncanny_Automator\Wp_Courseware_Helpers',
						'Uncanny_Automator\Wp_Fluent_Forms_Helpers',
						'Uncanny_Automator\Wpjm_Helpers',
						'Uncanny_Automator\Wppolls_Helpers',
						'Uncanny_Automator\Wpsp_Helpers',
						'Uncanny_Automator\Wplms_Helpers',
					)
				);
				if ( array_intersect( array( $class_name ), $migrated ) ) {
					return true;
				}

				return $status;
			},
			99,
			2
		);
	}

	/**
	 * @return bool
	 */
	public function is_base_plugin_installed() {

		return defined( 'AUTOMATOR_PLUGIN_VERSION' );
	}

	/**
	 * @return void
	 */
	public function require_plugin_installer() {

		include_once UAPro_ABSPATH . 'src/core/admin/installer/class-plugin-installer.php';

		$this->installer = new Plugin_Installer();

		$this->installer->create_ajax();

		add_action( 'admin_notices', array( $this, 'require_plugin_installer_notice' ) );
	}

	/**
	 * @return void
	 */
	public function require_plugin_installer_notice() {

		ob_start();

		include_once UAPro_ABSPATH . 'src/core/views/notice-automator-not-found.php';

		echo ob_get_clean(); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped
	}

	/**
	 * Run error notice in Uncanny Automator is not installed
	 */
	public static function free_needs_to_be_upgraded() {
		if ( version_compare( AUTOMATOR_PLUGIN_VERSION, '3.2', '>=' ) ) {
			return;
		}
		wp_enqueue_style( 'thickbox' );
		wp_enqueue_script( 'thickbox' );
		$class   = 'notice notice-error';
		$version = '3.2';
		// An old version of Uncanny Automator is running
		$url = admin_url( 'plugin-install.php?tab=plugin-information&plugin=uncanny-automator&section=changelog&TB_iframe=true&width=850&height=946' );

		/* translators: 1. Trademarked term. 2. Trademarked term */
		$message = sprintf( esc_html_x( 'The version of %1$s you have installed is not compatible with this version of %2$s.', 'Upgrade notice', 'uncanny-automator-pro' ), 'Uncanny Automator', 'Uncanny Automator Pro' );
		// translators: 1. Trademarked term. 2. Version number.
		$message_update = sprintf( esc_html_x( 'Please update %1$s to version %2$s or later.', 'Upgrade notice', 'uncanny-automator-pro' ), 'Uncanny Automator', $version );

		printf( '<div class="%1$s"><h3 style="font-weight: bold; color: red"><span class="dashicons dashicons-warning"></span>%2$s <a href="%3$s" class="thickbox open-plugin-details-modal">' . esc_html( $message_update ) . '</a></h3></div>', esc_attr( $class ), esc_html( $message ), esc_url( $url ) );
	}
}
