<?php

namespace Uncanny_Automator_Pro;

use Uncanny_Automator\Set_Up_Automator;

/**
 * Class Internal_Triggers_Actions
 *
 * @package Uncanny_Automator_Pro
 */
class Internal_Triggers_Actions {
	/**
	 * The directories that are autoloaded and initialized
	 *
	 * @since    1.0.0
	 * @access   private
	 * @var      array
	 */
	private $auto_loaded_directories = null;

	/**
	 * @var array|string[]
	 */
	public $default_directories = array();

	/**
	 * @var
	 */
	public $active_directories;

	/**
	 * @var
	 */
	public $directories_to_include = array();

	/**
	 * @var array
	 */
	public $all_integrations = array();

	/**
	 * @var string
	 */
	public $integrations_directory_path;

	/**
	 * @throws \Uncanny_Automator\Automator_Exception
	 */
	public function __construct() {
		// Path of the Pro integration directory
		$this->integrations_directory_path = dirname( AUTOMATOR_PRO_FILE ) . '/src/integrations';
		// Check if the cache exists
		$integrations = Automator()->cache->get( 'automator_pro_get_all_integrations' );

		if ( empty( $integrations ) ) {
			// Read directory again to grab all integration folders
			$legacy_integrations = new Pro_Legacy_Integrations();
			$integrations        = $legacy_integrations->generate_integrations_file_map();
			Automator()->cache->set( 'automator_pro_get_all_integrations', $integrations, 'automator', Automator()->cache->long_expires );
		}

		$this->all_integrations = apply_filters( 'automator_pro_integrations_setup', $integrations );

		// check if files with in directory exists
		$this->auto_loaded_directories = Automator()->cache->get( 'automator_pro_integration_directories_loaded' );
		if ( empty( $this->auto_loaded_directories ) ) {
			$this->auto_loaded_directories = Set_Up_Automator::extract_integration_folders( $integrations, $this->integrations_directory_path );
			Automator()->cache->set( 'automator_pro_integration_directories_loaded', $this->auto_loaded_directories, 'automator', Automator()->cache->long_expires );
		}
		// Add Pro integrations
		add_action( 'automator_add_integration', array( $this, 'init' ), 11 );
		// Add Pro integration helpers
		add_action( 'automator_add_integration_helpers', array( $this, 'add_integration_helpers' ), 13 );
		// Add Pro integration triggers/actions/etc
		add_action(
			'automator_add_integration_recipe_parts',
			array(
				$this,
				'boot_triggers_actions_closures',
			),
			15
		);
	}

	/**
	 *
	 * @throws \Exception
	 */
	public function init() {

		$this->initialize_add_integrations();

		// Only load when the Integration Framework is avaialble
		if ( class_exists( 'Uncanny_Automator\Integration' ) ) {
			$this->load_framework_integrations();
		}

		$this->auto_loaded_directories = apply_filters_deprecated(
			'uncanny_automator_pro_integration_directory',
			array( $this->auto_loaded_directories ),
			'3.9',
			'automator_pro_integration_directory'
		);
		$this->auto_loaded_directories = apply_filters( 'automator_pro_integration_directory', $this->auto_loaded_directories );

		do_action_deprecated( 'uncanny_automator_pro_loaded', array(), '3.9', 'automator_pro_loaded' );
		do_action( 'automator_pro_loaded' );
	}

	/**
	 *
	 * @throws \Exception
	 */
	public function initialize_add_integrations() {
		if ( empty( $this->auto_loaded_directories ) ) {
			return;
		}
		// Check each directory
		foreach ( $this->auto_loaded_directories as $directory ) {
			$files    = array();
			$dir_name = basename( $directory );
			if ( ! isset( $this->all_integrations[ $dir_name ] ) ) {
				continue;
			}

			if ( ! isset( $this->all_integrations[ $dir_name ]['main'] ) || empty( $this->all_integrations[ $dir_name ]['main'] ) ) {
				continue;
			}

			$files[] = $this->all_integrations[ $dir_name ]['main'];
			if ( empty( $files ) ) {
				continue;
			}
			foreach ( $files as $file ) {
				// bail early if the $file is not a string
				if ( is_array( $file ) ) {
					continue;
				}

				// Add file existence check to prevent fatal errors
				if ( ! file_exists( $file ) ) {
					continue;
				}

				$class = apply_filters( 'automator_pro_integrations_class_name', $this->get_class_name( $file ), $file );
				if ( class_exists( $class, false ) ) {
					continue;
				}

				require_once $file;
				$i                = new $class();
				$integration_code = method_exists( $i, 'get_integration' ) ? $i->get_integration() : $class::$integration;
				$active           = method_exists( $i, 'get_integration' ) ? $i->plugin_active() : $i->plugin_active( 0, $integration_code );
				$active           = apply_filters( 'automator_pro_maybe_integration_active', $active, $integration_code );
				if ( method_exists( '\Uncanny_Automator\Automator_Functions', 'set_all_integrations' ) ) {
					/**
					 * Store all the integrations, regardless of the status,
					 * to get integration name and the icon
					 * @since v4.6
					 */
					$integration_name      = method_exists( $i, 'get_name' ) ? $i->get_name() : '';
					$integration_icon      = method_exists( $i, 'get_icon' ) ? $i->get_icon() : '';
					$integration_icon_path = method_exists( $i, 'get_icon_path' ) ? $i->get_icon_path() : '';
					$integration_icon_url  = method_exists( $i, 'get_integration_icon' ) ? $i->get_integration_icon() : '';
					// Fix path if /img/ is not found
					if ( ! empty( $integration_icon_path ) && empty( preg_match_all( '/img\//', $integration_icon ) ) ) {
						$integration_icon     = "/img/$integration_icon";
						$integration_icon_url = plugins_url( $integration_icon, $integration_icon_path );
					} elseif ( empty( $integration_icon ) && ! empty( $integration_icon_path ) ) {
						$integration_icon     = "/img/$integration_icon";
						$integration_icon_url = plugins_url( $integration_icon, $integration_icon_path );
					}
					if ( ! empty( $integration_icon_url ) && ! empty( $integration_name ) ) {
						Automator()->set_all_integrations(
							$integration_code,
							array(
								'name'     => $integration_name,
								'icon_svg' => $integration_icon_url,
							)
						);
					}
				}
				if ( true !== $active ) {
					unset( $i );
					continue;
				}

				// Include only active integrations
				if ( method_exists( $i, 'add_integration_func' ) ) {
					$i->add_integration_func();
				}

				if ( ! in_array( $integration_code, Set_Up_Automator::$active_integrations_code, true ) ) {
					Set_Up_Automator::$active_integrations_code[] = $integration_code;
				}

				$this->active_directories[ $dir_name ] = $i;
				$this->active_directories              = apply_filters( 'automator_pro_active_integration_directories', $this->active_directories );
				if ( method_exists( $i, 'add_integration_directory_func' ) ) {
					$directories_to_include = $i->add_integration_directory_func( array(), $file );
					if ( $directories_to_include ) {
						foreach ( $directories_to_include as $dir ) {
							$this->directories_to_include[ $dir_name ][] = basename( $dir );
						}
					}
				}

				//Now everything is checked, add integration to the system.
				if ( method_exists( $i, 'add_integration' ) ) {
					$i->add_integration( $i->get_integration(), array( $i->get_name(), $i->get_icon() ) );
				}

				Utilities::add_class_instance( $class, $i );
			}
		}
	}

	/**
	 *
	 */
	public function add_integration_helpers() {
		if ( empty( $this->active_directories ) ) {
			return;
		}
		foreach ( $this->active_directories as $dir_name => $object ) {

			$files = isset( $this->all_integrations[ $dir_name ]['helpers'] ) && in_array( 'helpers', $this->directories_to_include[ $dir_name ], true ) ? $this->all_integrations[ $dir_name ]['helpers'] : array();

			if ( empty( $files ) ) {
				continue;
			}
			// Loop through all files in directory to create class names from file name
			foreach ( $files as $file ) {
				// bail early if the $file is not a string
				if ( ! is_file( $file ) ) {
					continue;
				}
				if ( is_array( $file ) ) {
					continue;
				}
				// Remove file extension my-class-name.php to my-class-name
				$class = apply_filters( 'automator_pro_helpers_class_name', $this->get_class_name( $file ), $file );
				if ( ! class_exists( $class, false ) ) {
					require_once $file;
					$mod = str_replace( '-', '_', $dir_name );
					Utilities::add_helper_instances( $mod, new $class() );
				}
			}
		}

		Automator_Pro_Helpers_Recipe::load_pro_recipe_helpers();
	}

	/**
	 *
	 */
	public function boot_triggers_actions_closures() {
		if ( empty( $this->active_directories ) ) {
			return;
		}

		foreach ( $this->active_directories as $dir_name => $object ) {
			$mod = $dir_name;
			if ( ! isset( $this->all_integrations[ $mod ] ) ) {
				continue;
			}

			$tokens     = isset( $this->all_integrations[ $mod ]['tokens'] ) && in_array( 'tokens', $this->directories_to_include[ $mod ], true ) ? $this->all_integrations[ $mod ]['tokens'] : array();
			$triggers   = isset( $this->all_integrations[ $mod ]['triggers'] ) && in_array( 'triggers', $this->directories_to_include[ $mod ], true ) ? $this->all_integrations[ $mod ]['triggers'] : array();
			$actions    = isset( $this->all_integrations[ $mod ]['actions'] ) && in_array( 'actions', $this->directories_to_include[ $mod ], true ) ? $this->all_integrations[ $mod ]['actions'] : array();
			$closures   = isset( $this->all_integrations[ $mod ]['closures'] ) && in_array( 'closures', $this->directories_to_include[ $mod ], true ) ? $this->all_integrations[ $mod ]['closures'] : array();
			$conditions = isset( $this->all_integrations[ $mod ]['conditions'] ) && in_array( 'conditions', $this->directories_to_include[ $mod ], true ) ? $this->all_integrations[ $mod ]['conditions'] : array();
			$filters    = isset( $this->all_integrations[ $mod ]['loop-filters'] ) && in_array( 'loop-filters', $this->directories_to_include[ $mod ], true ) ? $this->all_integrations[ $mod ]['loop-filters'] : array();
			$vendor     = array();
			$files      = array_merge( $tokens, $triggers, $actions, $closures, $conditions, $filters, $vendor );

			if ( empty( $files ) ) {
				continue;
			}

			// Loop through all files in directory to create class names from file name
			foreach ( $files as $file ) {
				if ( ! is_file( $file ) ) {
					continue;
				}

				// bail early if the $file is not a string
				if ( is_array( $file ) ) {
					continue;
				}

				$class = apply_filters( 'automator_pro_recipe_parts_class_name', $this->get_class_name( $file, true ), $file );

				// Add unique namespace to filters to avoid collision with conditions, triggers, and actions.
				if ( strpos( $file, 'loop-filter' ) ) {
					$class = $this->prepend_loop_filter_namespace( $class );
				}

				if ( ! class_exists( $class, false ) ) {
					require_once $file;
					Utilities::add_class_instance( $class, new $class() );
				}
			}
		}
	}

	/**
	 * Prepends loop filters namespace into a class name.
	 *
	 * @param string $class The fully qualified class name of the filter.
	 */
	public function prepend_loop_filter_namespace( $class = '' ) {
		$class = str_replace( 'Uncanny_Automator_Pro\\', '', $class );

		return 'Uncanny_Automator_Pro\\Loop_Filters\\' . $class;
	}

	/**
	 * Get a class name based on file name
	 *
	 * @param $file
	 * @param bool $uppercase
	 *
	 * @return mixed|void
	 */
	public function get_class_name( $file, $uppercase = false ) {
		// Remove file extension my-class-name.php to my-class-name
		$file_name = basename( $file, '.php' );
		// Implode array into class name - eg. array( 'My', 'Class', 'Name') to My_Class_Name
		$class_name = Set_Up_Automator::file_name_to_class( $file_name );
		if ( $uppercase ) {
			$class_name = strtoupper( $class_name );
		}
		$class = __NAMESPACE__ . '\\' . $class_name;

		return apply_filters( 'automator_pro_recipes_class_name', $class, $file, $file_name );
	}

	/**
	 * load_framework_integrations
	 *
	 * Will scan the integrations folder and if one has the load.php file, it will include it.
	 *
	 * @return void
	 */
	public function load_framework_integrations() {

		$dirs = scandir( $this->integrations_directory_path );

		foreach ( $dirs as $integration ) {

			if ( '.' === $integration || '..' === $integration ) {
				continue;
			}

			$load_file_path = $this->integrations_directory_path . DIRECTORY_SEPARATOR . $integration . DIRECTORY_SEPARATOR . 'load.php';

			if ( is_file( $load_file_path ) ) {
				include_once $load_file_path;
			}
		}
	}
}
