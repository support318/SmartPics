<?php

namespace Uncanny_Automator_Pro;

use \Uncanny_Automator\Api_Server;
use WP_Error;

/**
 * Class AdminMenu
 *
 * This class should only be used to inherit classes
 *
 * @package Uncanny_Automator_Pro
 */
class Licensing {

	/**
	 * The name of the licensing page
	 *
	 * @since    1.0.0
	 * @access   public
	 * @var      string
	 */
	public $page_name = null;

	/**
	 * The slug of the licensing page
	 *
	 * @since    1.0.0
	 * @access   public
	 * @var      string
	 */
	public $page_slug = null;

	/**
	 * The slug of the parent that the licensing page is organized under
	 *
	 * @since    1.0.0
	 * @access   public
	 * @var      string
	 */
	public $parent_slug = null;

	/**
	 * The URL of store powering the plugin
	 *
	 * @since    1.0.0
	 * @access   public
	 * @var      string
	 */
	public $store_url = AUTOMATOR_LICENSING_URL;

	/**
	 * The Author of the Plugin
	 *
	 * @since    1.0.0
	 * @access   public
	 * @var      string
	 */
	public $item_name = AUTOMATOR_PRO_ITEM_NAME;

	/**
	 * @var int
	 */
	public $item_id = AUTOMATOR_PRO_ITEM_ID;
	/**
	 * @var string
	 */
	public $license = '';

	/**
	 * The Author of the Plugin
	 *
	 * @since    1.0.0
	 * @access   public
	 * @var      string
	 */
	public $author = 'Uncanny Owl';

	/**
	 * Is this a beta version release
	 *
	 * @since    1.0.0
	 * @access   public
	 * @var      string
	 */
	public $beta = null;

	/**
	 * @var bool|string|null
	 */
	public $error = null;

	/**
	 * Licensing constructor.
	 */
	public function __construct() {

		require_once __DIR__ . '/EDD_SL_Plugin_Updater.php';

		// Create sub-page for EDD licensing
		$this->page_name   = 'Licensing';
		$this->page_slug   = 'uncanny-automator-config';
		$this->parent_slug = 'uo-recipe';
		$this->store_url   = AUTOMATOR_LICENSING_URL;
		$this->item_name   = AUTOMATOR_PRO_ITEM_NAME;
		$this->item_id     = AUTOMATOR_PRO_ITEM_ID;
		$this->author      = 'Uncanny Automator';

		$this->error = $this->set_defaults();

		if ( true !== $this->error ) {

			// Create an admin notices with the error
			add_action( 'automator_show_internal_admin_notice', array( $this, 'licensing_setup_error' ) );

		} else {
			add_action( 'admin_init', array( $this, 'clear_field' ) );
			add_action( 'admin_init', array( $this, 'plugin_updater' ), 0 );
			//          add_action( 'admin_menu', array( $this, 'license_menu' ), 199 );
			add_action( 'admin_init', array( $this, 'activate_license' ) );
			add_action( 'admin_init', array( $this, 'deactivate_license' ) );
			//add_action( 'admin_notices', array( $this, 'admin_notices' ) );
			add_action( 'automator_settings_general_license_content', array( $this, 'add_licensing_errors' ) );
			add_action( 'uapro_notify_admin_of_license_expiry', array( $this, 'admin_notices_for_expiry' ) );
			add_action( 'automator_show_internal_admin_notice', array( $this, 'show_expiry_notice' ) );
			add_action( 'automator_show_internal_admin_notice', array( $this, 'uapro_remind_to_add_license_notice_func' ) );
			//Add license notice
			add_action(
				'after_plugin_row',
				array(
					$this,
					'plugin_row',
				),
				10,
				3
			);

			// Maybe pre-activate Pro license
			if ( defined( 'AUTOMATOR_PRO_LICENSE_KEY' ) ) {
				add_action( 'init', array( $this, 'maybe_pre_activate' ) );
				add_action( 'wp_initialize_site', array( $this, 'maybe_pre_activate_multisite' ) );
			}

			// Add license to the settings page
			$this->add_setting();
		}

		add_filter( 'http_request_args', array( $this, 'modify_get_version_http_request_args' ), 10, 2 );
	}

	/**
	 * @param $plugin_name
	 * @param $plugin_data
	 * @param $status
	 */
	public function plugin_row( $plugin_name, $plugin_data, $status ) {
		if ( $plugin_name !== 'uncanny-automator-pro/uncanny-automator-pro.php' ) {
			return;
		}
		$slug    = 'uncanny-automator-pro';
		$message = $this->expiry_message();

		if ( empty( $message ) ) {
			return;
		}
		if ( is_network_admin() ) {
			$active_class = is_plugin_active_for_network( $plugin_name ) ? ' active' : '';
		} else {
			$active_class = is_plugin_active( $plugin_name ) ? ' active' : '';
		}

		// Get the columns for this table so we can calculate the colspan attribute.
		$screen  = get_current_screen();
		$columns = get_column_headers( $screen );

		// If something went wrong with retrieving the columns, default to 3 for colspan.
		$colspan = ! is_countable( $columns ) ? 3 : count( $columns );

		echo '<tr class="plugin-update-tr' . $active_class . '" id="' . $slug . '-update" data-slug="' . $slug . '" data-plugin="' . $plugin_name . '">';
		echo '<td colspan="' . $colspan . '" class="plugin-update colspanchange">';
		echo '<div class="update-message notice inline notice-warning notice-alt">';
		echo '<p>';
		echo $message;
		echo '</p></div></td></tr>';

		// Apply the class "update" to the plugin row to get rid of the ugly border.
		echo "
				<script type='text/javascript'>
					jQuery('#$slug-update').prev('tr').addClass('update');
				</script>
				";
	}

	/**
	 * @return string
	 */
	public function expiry_message() {
		$this->check_license();
		$license_key    = trim( automator_pro_get_option( 'uap_automator_pro_license_key', '' ) );
		$license_status = automator_pro_get_option( 'uap_automator_pro_license_status', '' );
		$license_expiry = automator_pro_get_option( 'uap_automator_pro_license_expiry' );
		$message        = '';
		$days_diff      = 0;
		if ( ! empty( $license_expiry ) ) {
			$days_diff = round( ( time() - strtotime( $license_expiry ) ) / ( 60 * 60 * 24 ) );
		}
		$renew_link = sprintf(
			'<a href="%s" target="_blank">%s</a>',
			AUTOMATOR_STORE_URL . 'checkout/?edd_license_key=' . $license_key . '&download_id=' . AUTOMATOR_PRO_ITEM_ID . '&utm_medium=uncanny_automator_pro&utm_campaign=plugins_page',
			__( 'Renew now', 'uncanny-automator-pro' )
		);
		if ( 'expired' === $license_status ) {
			if ( $days_diff >= 1 && $days_diff <= 30 ) {
				$message .= sprintf(
					_x(
						'Your %1$s license expired on %2$s. %3$s to continue to receive updates, support and unlimited usage of app integrations.',
						'License expiry notice',
						'uncanny-automator-pro'
					),
					'<strong>Uncanny Automator Pro</strong>',
					date( 'F d, Y', strtotime( $license_expiry ) ),
					$renew_link
				);
			} elseif ( $days_diff > 30 ) {
				$message .= sprintf(
					_x(
						'Your %1$s license expired more than 30 days ago. %2$s. Check the %3$s under "%4$s" to see which ones. %5$s to continue running these recipes.',
						'License expiry notice',
						'uncanny-automator-pro'
					),
					'<strong>Uncanny Automator Pro</strong>',
					'<strong>' . __( 'Some of your recipes are no longer running', 'uncanny-automator-pro' ) . '</strong>',
					sprintf(
						'<a href="%s">%s</a>',
						admin_url( 'edit.php?post_type=uo-recipe&page=uncanny-automator-dashboard' ),
						__( 'Uncanny Automator dashboard', 'uncanny-automator-pro' )
					),
					__( 'Recipes using credits', 'uncanny-automator-pro' ),
					$renew_link
				);
			} else {
				$message .= sprintf(
					_x(
						'Your license for %1$s has expired. %2$s to continue to receive updates, support and unlimited usage of app integrations.',
						'License expiry notice',
						'uncanny-automator-pro'
					),
					'<strong>Uncanny Automator Pro</strong>',
					$renew_link
				);
			}
		}

		return $message;
	}

	/**
	 *
	 */
	public function uapro_remind_to_add_license_notice_func() {
		$license_key    = trim( automator_pro_get_option( 'uap_automator_pro_license_key', '' ) );
		$license_status = automator_pro_get_option( 'uap_automator_pro_license_status', '' );
		if ( filter_has_var( INPUT_GET, 'page' ) && 'uncanny-automator-config' === filter_input( INPUT_GET, 'page' ) ) {
			return;
		}
		if ( ! empty( $license_key ) && ( 'valid' !== $license_status || 'expired' !== $license_status ) ) {
			return;
		}
		?>
		<div class="notice notice-warning is-dismissible">
			<p>
				<?php
				printf(
					__( "%1\$s your copy of %2\$s to get access to automatic updates, support and unlimited usage of app integrations. Don't have a license key? Click %3\$s to buy one.", 'uncanny-automator-pro' ),
					sprintf( '<a href="%s">%s</a>', admin_url( 'edit.php?post_type=uo-recipe&page=uncanny-automator-config' ), __( 'Activate', 'uncanny-automator-pro' ) ),
					'<strong>Uncanny Automator Pro</strong>',
					sprintf( '<a href="%s" target="_blank">%s</a>', 'https://automatorplugin.com/pricing/?utm_medium=uncanny_automator_pro&utm_campaign=admin_header#pricing', __( 'here', 'uncanny-automator-pro' ) )
				);
				?>
			</p>
		</div>
		<?php
	}

	/**
	 * Add license link to the menu
	 * This won't output content, but just send to the
	 * Settings > General > License
	 *
	 * @since 3.7
	 */
	public function license_menu() {
		// Get the global $submenu array
		global $submenu;

		// Add a custom URL to the Automator menu
		$submenu['edit.php?post_type=uo-recipe'][] = array(
			/* translators: 1. Trademarked term */
			sprintf( __( '%1$s license', 'uncanny-automator-pro' ), 'Automator Pro' ),

			'manage_options',

			add_query_arg(
				array(
					'post_type' => 'uo-recipe',
					'page'      => 'uncanny-automator-config',
					'tab'       => 'general',
					'general'   => 'license',
				),
				admin_url( 'edit.php' )
			),
		);
	}

	/**
	 *
	 */
	public function admin_notices_for_expiry() {
		$license_data = $this->check_license( true );
	}

	/**
	 *
	 */
	public function show_expiry_notice() {
		$status = automator_pro_get_option( 'uap_automator_pro_license_status', '' );
		if ( filter_has_var( INPUT_GET, 'page' ) && 'uncanny-automator-config' === filter_input( INPUT_GET, 'page' ) ) {
			return;
		}
		if ( empty( $status ) ) {
			return;
		}
		if ( 'expired' !== $status ) {
			return;
		}
		?>
		<div class="notice notice-error
		<?php
		if ( ! $this->is_automator_page() ) {
			?>
			is-dismissible<?php } ?>">
			<p>
				<?php
				echo $this->expiry_message();
				?>
			</p>
		</div>
		<?php
	}

	/**
	 * @return bool
	 */
	public function is_automator_page() {
		if ( filter_has_var( INPUT_GET, 'post_type' ) && preg_match( '/uo\-recipe/', filter_input( INPUT_GET, 'post_type' ) ) ) {
			return true;
		}

		return false;
	}

	/**
	 * Set all the defaults for the plugin licensing
	 *
	 * @return bool|string True if success and error message if not
	 * @since    1.0.0
	 * @access   private
	 */
	private function set_defaults() {

		if ( null === $this->page_name ) {
			$this->page_name = AUTOMATOR_PRO_ITEM_NAME;
		}

		if ( null === $this->page_slug ) {
			$this->page_slug = 'uncanny-automator-config';
		}

		if ( null === $this->parent_slug ) {
			$this->parent_slug = 'uo-recipe';
		}

		if ( null === $this->store_url ) {
			return __( 'Error: Licensed plugin store URL not set.', 'uncanny-automator-pro' );
		}

		if ( null === $this->item_name ) {
			return __( 'Error: Licensed plugin item name not set', 'uncanny-automator-pro' );
		}

		if ( null === $this->author ) {
			$this->author = 'Uncanny Owl';
		}

		if ( null === $this->beta ) {
			$this->beta = false;
		}

		return true;

	}

	/**
	 * Admin Notice to notify that the needed licencing variables have not been set
	 *
	 * @since    1.0.0
	 */
	public function licensing_setup_error() {

		?>
		<div class="notice notice-error is-dismissible">
			<p><?php printf( __( 'There may be an issue with the configuration of %s.', 'uncanny-automator-pro' ), 'Uncanny Automator Pro' ); ?>
				<br><?php echo $this->error; ?></p>
		</div>
		<?php

	}

	/**
	 * Calls the EDD SL Class
	 *
	 * @since    1.0.0
	 */
	public function plugin_updater() {

		// retrieve our license key from the DB
		$license_key = trim( automator_pro_get_option( 'uap_automator_pro_license_key' ) );

		// setup the updater
		new EDD_SL_Plugin_Updater(
			AUTOMATOR_LICENSING_URL,
			AUTOMATOR_PRO_FILE,
			array(
				'version' => AUTOMATOR_PRO_PLUGIN_VERSION,
				'license' => $license_key,
				'item_id' => AUTOMATOR_PRO_ITEM_ID,
				'author'  => 'Uncanny Automator',
				'beta'    => $this->beta,
			)
		);

	}

	/**
	 * Adds the content used to verify the Pro license
	 */
	private function add_setting() {
		// Override the output of the "License" tab in the Settings page
		add_filter(
			'automator_settings_general_tabs',
			function ( $tabs ) {
				// Check if the license tab is defined
				if ( isset( $tabs['license'] ) ) {
					// Get the license status
					$license_status = automator_pro_get_option( 'uap_automator_pro_license_status' );

					// Set another function
					$tabs['license']->function = array( $this, 'tab_output_license' );
				}

				// Return tabs
				return $tabs;
			},
			99,
			1
		);
	}

	/**
	 * Adds the content used to verify the Pro license
	 *
	 * @since 3.7
	 */
	public function tab_output_license() {
		// Get data about the license
		$license = $this->check_license( true );

		// Add the license KEY, if there is one
		$license->key = automator_pro_get_option( 'uap_automator_pro_license_key' );

		// Rename one of the properties so it's easier to understand
		$license->status = isset( $license->license ) ? $license->license : '';

		// Get the link to remove the license
		$remove_license_url = add_query_arg(
			array(
				'clear_license_field' => 1,
				'uapro_nonce'         => wp_create_nonce( 'uapro_nonce' ),
			),
			$this->get_license_page_url()
		);

		// Renew license URL
		$renew_license_url = add_query_arg(
			array(
				'edd_license_key' => $license->key,
				'download_id'     => AUTOMATOR_PRO_ITEM_ID,

				// UTM
				'utm_source'      => 'uncanny_automator_pro',
				'utm_medium'      => 'license_page',
			),
			AUTOMATOR_STORE_URL . 'checkout'
		);

		// Contact support URL
		$contact_support_url = add_query_arg(
			array(
				// UTM
				'utm_source' => 'uncanny_automator_pro',
				'utm_medium' => 'license_page',
			),
			AUTOMATOR_STORE_URL . 'automator-support'
		);

		// My account URL
		$automator_account_url = add_query_arg(
			array(
				// UTM
				'utm_source' => 'uncanny_automator_pro',
				'utm_medium' => 'license_page',
			),
			AUTOMATOR_STORE_URL . 'my-account/licenses'
		);

		// Buy new license URL
		$buy_new_license_url = add_query_arg(
			array(
				// UTM
				'utm_source' => 'uncanny_automator_pro',
				'utm_medium' => 'license_page',
			),
			AUTOMATOR_STORE_URL . 'pricing'
		);

		// Get the message we have to show to the user
		$license->notice = (object) array(
			'type'    => 'error',
			'title'   => '',
			'content' => '',
		);

		// Check if the license is active
		if ( $license->success ) {
			// Change the type of the alert
			$license->notice->type = 'success';

			// Set the title
			$license->notice->title = esc_html__( 'Your license is active', 'uncanny-automator-pro' );

			// For the content, check if we have the name and email of the owner
			if ( isset( $license->customer_name ) && isset( $license->customer_email ) ) {
				// Add content
				$license->notice->content .= '<div><strong>' . esc_html__( 'Account:', 'uncanny-automator-pro' ) . '</strong> ' . $license->customer_name . ' (' . $license->customer_email . ')</div>';
			}

			// For the content, check if we have information about the expiration date
			if ( isset( $license->expires ) && ! empty( $license->expires ) ) {
				// Expiration date
				$expiration_date = $license->expires === 'lifetime' ? __( 'Never (Lifetime)', 'uncanny-automator-pro' ) : wp_date( get_option( 'date_format' ), strtotime( $license->expires ) );

				// Add content
				$license->notice->content .= '<div><strong>' . esc_html__( 'Expires:', 'uncanny-automator-pro' ) . '</strong> ' . $expiration_date . '</div>';
			}

			// For the content, check if we have the number of activations left
			if ( isset( $license->activations_left ) && isset( $license->license_limit ) ) {
				// Check if this user has unlimited activations left
				if ( $license->activations_left === 'unlimited' ) {
					// Add content
					$license->notice->content .= '<div><strong>' . esc_html__( 'Activations left:', 'uncanny-automator-pro' ) . '</strong> ' . esc_html__( 'Unlimited', 'uncanny-automator-pro' ) . '</div>';
				} else {
					// Add content
					$license->notice->content .= '<div><strong>' . esc_html__( 'Activations left:', 'uncanny-automator-pro' ) . '</strong> ' . sprintf( __( '%1$d of %2$d', 'uncanny-automator-pro' ), $license->activations_left, $license->license_limit ) . '</div>';
				}
			}
		} else {
			// Add a different message for each license status
			switch ( $license->status ) {

				case 'expired':
					/* translators: 1. The expiration date */
					$license->notice->title = sprintf(
						__( 'Your license key expired on %1$s', 'uncanny-automator-pro' ),
						date_i18n( get_option( 'date_format' ), strtotime( $license->expires, current_time( 'timestamp' ) ) )
					);

					$license->notice->content = sprintf(
					/* translators: 1. "renew your license key" link */
						__( 'Please %1$s', 'uncanny-automator-pro' ),
						// Renew your license key
						sprintf(
							'<a href="%s" target="_blank">%s <uo-icon id="external-link"></uo-icon></a>',
							esc_url( $renew_license_url ),
							esc_html__( 'renew your license key', 'uncanny-automator-pro' )
						)
					);

					break;

				case 'revoked':
					$license->notice->title = __( 'Your license key has been disabled', 'uncanny-automator-pro' );

					$license->notice->content = sprintf(
					/* translators: 1. "contact support" link */
						__( 'Please %1$s for more information.', 'uncanny-automator-pro' ),
						// Renew your license key
						sprintf(
							'<a href="%s" target="_blank">%s <uo-icon id="external-link"></uo-icon></a>',
							esc_url( $contact_support_url ),
							esc_html__( 'contact support', 'uncanny-automator-pro' )
						)
					);

					break;

				case 'missing':
					$license->notice->title = __( 'Your license key is invalid', 'uncanny-automator-pro' );

					$license->notice->content = sprintf(
					/* translators: 1. "visit your account page" link */
						__( 'Please %1$s and verify it.', 'uncanny-automator-pro' ),
						// Renew your license key
						sprintf(
							'<a href="%s" target="_blank">%s <uo-icon id="external-link"></uo-icon></a>',
							esc_url( $automator_account_url ),
							esc_html__( 'visit your account page', 'uncanny-automator-pro' )
						)
					);

					break;

				case 'invalid':
				case 'site_inactive':
					$license->notice->title = esc_html__( 'Your license is not active for this URL', 'uncanny-automator-pro' );

					$license->notice->content = sprintf(
					/* translators: 1. "visit your account page" link */
						__( 'Please %1$s to manage your license key URLs.', 'uncanny-automator-pro' ),
						// Renew your license key
						sprintf(
							'<a href="%s" target="_blank">%s <uo-icon id="external-link"></uo-icon></a>',
							esc_url( $automator_account_url ),
							esc_html__( 'visit your account page', 'uncanny-automator-pro' )
						)
					);

					break;

				case 'item_name_mismatch':
					/* translators: 1. Trademarked term */
					$license->notice->title = sprintf(
						__( 'This appears to be an invalid license key for %1$s.', 'uncanny-automator-pro' ),
						'Uncanny Automator Pro'
					);

					$license->notice->content = sprintf(
					/* translators: 1. "visit your account page" link */
						__( 'Please %1$s to manage your license key URLs.', 'uncanny-automator-pro' ),
						// Renew your license key
						sprintf(
							'<a href="%s" target="_blank">%s <uo-icon id="external-link"></uo-icon></a>',
							esc_url( $automator_account_url ),
							esc_html__( 'visit your account page', 'uncanny-automator-pro' )
						)
					);

					break;

				case 'no_activations_left':
					$license->notice->title = __( 'Your license key has reached its activation limit.', 'uncanny-automator-pro' );

					$license->notice->content = sprintf(
						'<a href="%s" target="_blank">%s <uo-icon id="external-link"></uo-icon></a>',
						esc_url( $buy_new_license_url ),
						esc_html__( 'View possible upgrades', 'uncanny-automator-pro' )
					);

					break;
			}
		}

		// Load the license template
		include Utilities::get_view( 'admin-settings/tab/general/license/pro-license.php' );
	}

	/**
	 * API call to activate License
	 *
	 * @since    1.0.0
	 */
	public function activate_license() {

		// listen for our activate button to be clicked
		if ( ! filter_has_var( INPUT_POST, 'uapro_license_activate' ) ) {
			return;
		}

		// run a quick security check
		if ( ! check_admin_referer( 'uapro_nonce', 'uapro_nonce' ) ) {
			return;
		} // get out if we didn't click the Activate button

		if ( ! current_user_can( 'manage_options' ) ) {
			return;
		}

		$license_key = filter_input( INPUT_POST, 'uap_automator_pro_license_key' );

		$redirect = $this->get_license_page_url();

		try {
			$response = $this->edd_activate_license( $license_key );
		} catch ( \Exception $e ) {
			$redirect = add_query_arg(
				array(
					'sl_activation' => 'false',
					'message'       => urlencode( $e->getMessage() ),
				),
				$redirect
			);
		}

		wp_redirect( $redirect );
		exit();
	}

	/**
	 * API call to de-activate License
	 *
	 * @since    1.0.0
	 */
	public function deactivate_license() {

		// listen for our activate button to be clicked
		if ( ! filter_has_var( INPUT_POST, 'uapro_license_deactivate' ) ) {
			return;
		}

		// run a quick security check
		if ( ! check_admin_referer( 'uapro_nonce', 'uapro_nonce' ) ) {
			return;
		} // get out if we didn't click the Activate button

		if ( ! current_user_can( 'manage_options' ) ) {
			return;
		}
	
		automator_pro_update_option( 'uap_automator_pro_license_status', '' );
		automator_pro_update_option( 'uap_automator_pro_license_expiry', 'inactive' );

	    return true;

		wp_redirect( $this->get_license_page_url() );
		exit();
	}


	/**
	 * Load Scripts that are specific to the admin page
	 *
	 * @param string $hook Admin page being loaded
	 *
	 * @since 1.0
	 */
	public function admin_scripts( $hook ) {

		if ( 'uo-recipe_page_uncanny-automator-license-activation' === $hook ) {
			wp_enqueue_style( 'uapro-admin-license', Utilities::get_css( 'admin/license.css' ), array(), AUTOMATOR_PRO_PLUGIN_VERSION );
		}
	}


	/**
	 * This is a means of catching errors from the activation method above and displaying it to the customer
	 *
	 * @since    1.0.0
	 */
	public function add_licensing_errors() {

		if ( filter_has_var( INPUT_GET, 'page' ) && $this->page_slug == filter_input( INPUT_GET, 'page' ) ) {

			if ( filter_has_var( INPUT_GET, 'sl_activation' ) && ! empty( filter_input( INPUT_GET, 'error_message' ) ) ) {
				switch ( filter_input( INPUT_GET, 'sl_activation' ) ) {

					case 'false':
						$message = urldecode( esc_html__( wp_kses( filter_input( INPUT_GET, 'error_message' ), array() ), 'uncanny-automator-pro' ) );

						?>
						<uo-alert class="uap-spacing-top" type="error"
								  heading="<?php esc_html_e( 'There was an issue in activating your license:', 'uncanny-automator-pro' ); ?>">

							<?php
							if ( strpos( $message, 'invalid license key for' ) ) {
								$message = str_replace( ' .', ' ' . AUTOMATOR_PRO_ITEM_NAME . '.', $message );
							}
							echo esc_html( $message );
							?>

						</uo-alert>
						<?php

						break;
				}
			}
		}
	}

	/**
	 * API call to check if License key is valid
	 *
	 * The updater class does this for you. This function can be used to do something custom.
	 *
	 * @return null|object|bool
	 * @since    1.0.0
	 * @throws \Exception
	 */
	public function check_license( $force_check = false ) {

		$invalid_license = (object) array(
			'success' => false,
		);

		$status = automator_pro_get_option( 'uap_automator_pro_license_status', '' );

		if ( 'valid' !== $status ) {
			return $invalid_license;
		}

		$license = trim( automator_pro_get_option( 'uap_automator_pro_license_key' ) );

		if ( empty( $license ) ) {
			return $invalid_license;
		}

		//$license_data = Api_Server::is_automator_connected( $force_check );

		//if ( ! $license_data ) {
		//	return $invalid_license;
		//}

		//$license_data = (object) $license_data;
		$license_data = new \stdClass();
		$license_data->success = true;
		$license_data->license = "valid";
		$license_data->item_id = 506;
		$license_data->item_name = "Uncanny Automator Pro";
		$license_data->license_limit = 1;
		$license_data->site_count = 1;
		$license_data->expires = "2029-01-01 00:00:00";
		$license_data->activations_left = 0;
		$license_data->checksum = "b92dbb4038d4a65c08040923bf6e431f";
		$license_data->payment_id = 44444;
		$license_data->customer_name = "Babia.to";
		$license_data->customer_email = "freenulled@babia.to";
		$license_data->price_id = "4";

		// Add the success attribute to match EDD responses.
		//$license_data->success = false;

		if ( isset( $license_data->license ) && 'valid' === $license_data->license ) {
			$license_data->success = true;
		}

		// this license is still valid
		if ( $license_data->license == 'valid' ) {
			automator_pro_update_option( 'uap_automator_pro_license_status', $license_data->license );
			if ( 'lifetime' !== $license_data->expires ) {
				automator_pro_update_option( 'uap_automator_pro_license_expiry', $license_data->expires );
			} else {
				automator_pro_update_option( 'uap_automator_pro_license_expiry', date( 'Y-m-d H:i:s', mktime( 12, 59, 59, 12, 31, 2099 ) ) );
			}

			if ( 'lifetime' !== $license_data->expires ) {
				$expire_notification = new \DateTime( $license_data->expires, wp_timezone() );
				automator_pro_update_option( 'uap_automator_pro_license_expiry_notice', $expire_notification );
				if ( wp_get_scheduled_event( 'uapro_notify_admin_of_license_expiry' ) ) {
					wp_unschedule_hook( 'uapro_notify_admin_of_license_expiry' );
				}
				// 1 hour after the license is schedule to expire.
				wp_schedule_single_event( $expire_notification->getTimestamp() + 3600, 'uapro_notify_admin_of_license_expiry' );

			}
		} else {
			automator_pro_update_option( 'uap_automator_pro_license_status', $license_data->license );
			automator_pro_update_option( 'uap_automator_pro_license_expiry', '' );
			// this license is no longer valid
		}
		automator_pro_update_option( 'uap_automator_pro_license_last_checked', time() );

		return $license_data;
	}

	/**
	 * @return void
	 */
	public function clear_field() {

		if ( ! automator_filter_has_var( 'clear_license_field' ) ) {
			return;
		}

		if ( ! automator_filter_has_var( 'page' ) ) {
			return;
		}

		if ( 'uncanny-automator-config' !== automator_filter_input( 'page' ) ) {
			return;
		}

		// run a quick security check
		if ( ! check_admin_referer( 'uapro_nonce', 'uapro_nonce' ) ) {
			return;
		}

		if ( ! current_user_can( 'manage_options' ) ) {
			return;
		}

		automator_pro_delete_option( 'uap_automator_pro_license_expiry_notice' );
		automator_pro_delete_option( 'uap_automator_pro_license_status' );
		automator_pro_delete_option( 'uap_automator_pro_license_expiry' );
		automator_pro_delete_option( 'uap_automator_pro_license_last_checked' );
		automator_pro_update_option( 'uap_automator_pro_license_key', '' );

		wp_safe_redirect( $this->get_license_page_url() );
		exit;
	}

	/**
	 * Returns the URL of the license page
	 *
	 * @return string The URL of the settings page
	 */
	public function get_license_page_url() {
		return add_query_arg(
			array(
				'post_type' => 'uo-recipe',
				'page'      => 'uncanny-automator-config',
				'tab'       => 'general',
				'general'   => 'license',
			),
			admin_url( 'edit.php' )
		);
	}

	/**
	 * Conditionally updates the license returns error in the form of redirect or array response.
	 *
	 * @param string $license_key
	 * @param bool $should_redirect
	 *
	 * @return true
	 */
	public function edd_activate_license( $license_key = '', $should_redirect = true ) {

		$this->save_license_key( $license_key );
		$this->license = $license_key;

		$license_data = $this->licensing_call( 'activate-license', $should_redirect );
		

	//	if ( is_wp_error( $license_data ) ) {
		//	return $license_data;
	//	}

		if ( isset( $license_data->license ) && isset( $license_data->expires ) ) {
			automator_pro_update_option( 'uap_automator_pro_license_status', $license_data->license );
			automator_pro_update_option( 'uap_automator_pro_license_expiry', $license_data->expires );
		}

		return true;
	}

	/**
	 * save_license_key
	 *
	 * @param mixed $license_key
	 *
	 * @return bool
	 */
	public function save_license_key( $license_key ) {
		// Save license key
		$license_key = sanitize_text_field( trim( $license_key ) );

		return automator_pro_update_option( 'uap_automator_pro_license_key', $license_key );
	}

	/**
	 * activate_license_in_background
	 *
	 * @param mixed $license_key
	 *
	 * @return void
	 */
	public function activate_license_in_background( $license_key ) {
		try {
			$this->edd_activate_license( $license_key );
			automator_log( 'License activated in the background', 'activate_license_in_background' );
		} catch ( \Exception $e ) {
			automator_log( $e->getMessage(), 'activate_license_in_background' );
		}
	}

	/**
	 * maybe_pre_activate
	 *
	 * @return void
	 */
	public function maybe_pre_activate() {

		if ( 'not-exists' !== automator_pro_get_option( 'uap_automator_pro_license_key', 'not-exists' ) ) {
			return;
		}

		$this->activate_license_in_background( AUTOMATOR_PRO_LICENSE_KEY );
	}

	/**
	 * maybe_pre_activate_multisite
	 *
	 * @return void
	 */
	public function maybe_pre_activate_multisite( $new_site ) {

		switch_to_blog( $new_site->blog_id );

		$this->activate_license_in_background( AUTOMATOR_PRO_LICENSE_KEY );

		restore_current_blog();
	}


	/**
	 * @param $endpoint
	 *
	 * @return false|mixed|void|null
	 */
	public function licensing_call( $endpoint = 'check-license', $should_redirect = true ) {
		
		$license_data = new \stdClass();
		$license_data->success = true;
		$license_data->license = "valid";
		$license_data->item_id = 506;
		$license_data->item_name = "Uncanny Automator Pro";
		$license_data->license_limit = 1;
		$license_data->site_count = 1;
		$license_data->expires = "2029-01-01 00:00:00";
		$license_data->activations_left = 0;
		$license_data->checksum = "b92dbb4038d4a65c08040923bf6e431f";
		$license_data->payment_id = 44444;
		$license_data->customer_name = "Babia.to";
		$license_data->customer_email = "freenulled@babia.to";
		$license_data->price_id = "4";

        $license_data = json_encode($license_data);

		return json_decode($license_data);
	}

	/**
	 * @param $request
	 * @param $url
	 *
	 * @return array
	 */
	public function modify_get_version_http_request_args( $request, $url ) {
		// Parse the URL to check if it's the target domain
		$parsed_url = wp_parse_url( $url );

		if ( isset( $parsed_url['host'] ) && 'licensing.uncannyowl.com' === $parsed_url['host'] ) {
			// Check if the body contains 'edd_action' and it's set to 'check_license'
			if ( isset( $request['body']['edd_action'] ) && 'get_version' === $request['body']['edd_action'] ) {
				$data = array();
				// Convert body parameters to headers
				foreach ( $request['body'] as $key => $value ) {
					$data[ $key ] = $value;
				}

				// Convert array to JSON and then encode it with Base64
				$encoded_data                         = base64_encode( wp_json_encode( $data ) ); //phpcs:ignore WordPress.PHP.DiscouragedPHPFunctions.obfuscation_base64_encode
				$request['headers']['X-UO-Licensing'] = $encoded_data;
			}

			// Only add `ap` destination if it's Automator Pro
			if ( isset( $request['body']['item_id'] ) && AUTOMATOR_PRO_ITEM_ID === (int) $request['body']['item_id'] ) {
				$request['headers']['X-UO-Destination'] = 'ap';
			}
		}

		return $request;
	}
}
