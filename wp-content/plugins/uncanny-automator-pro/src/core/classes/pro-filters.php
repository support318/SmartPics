<?php

namespace Uncanny_Automator_Pro;

use Uncanny_Automator\Automator_DB;

/**
 * Class Pro_Filters
 *
 * @package Uncanny_Automator_Pro
 */
class Pro_Filters {

	/**
	 * Constructor.
	 */

	public function __construct() {
		add_action( 'admin_enqueue_scripts', array( $this, 'scripts' ), 999 );
	}

	/**
	 * Enqueue scripts only on custom post type edit pages
	 *
	 * @param $hook
	 */
	public function scripts( $hook ) {

		if ( false === strpos( $hook, 'uncanny-automator-admin-logs' ) ) {
			return;
		}

		// De-enqueue BadgeOS select2 assets
		wp_dequeue_script( 'badgeos-select2' );
		wp_dequeue_style( 'badgeos-select2-css' );

		// Select2
		wp_enqueue_script(
			'uap-logs-pro-select2',
			Utilities::get_vendor_asset( 'select2/js/select2.min.js' ),
			array( 'jquery' ),
			false,
			true
		);
		wp_enqueue_style( 'uap-logs-pro-select2', Utilities::get_vendor_asset( 'select2/css/select2.min.css' ), array(), AUTOMATOR_PRO_PLUGIN_VERSION );

		// DateRangePicker
		wp_enqueue_script(
			'uap-logs-pro-moment',
			Utilities::get_vendor_asset( 'daterangepicker/js/moment.min.js' ),
			array( 'jquery' ),
			AUTOMATOR_PRO_PLUGIN_VERSION
		);
		wp_enqueue_script(
			'uap-logs-pro-daterangepicker',
			Utilities::get_vendor_asset( 'daterangepicker/js/daterangepicker.js' ),
			array(
				'jquery',
				'uap-logs-pro-moment',
			),
			AUTOMATOR_PRO_PLUGIN_VERSION
		);
		wp_enqueue_style( 'uap-logs-pro-daterangepicker', Utilities::get_vendor_asset( 'daterangepicker/css/daterangepicker.css' ), array(), AUTOMATOR_PRO_PLUGIN_VERSION );

		// Load main JS
		wp_enqueue_script(
			'uap-logs-pro',
			Utilities::get_js( 'admin/logs.js' ),
			array(
				'jquery',
				'uap-logs-pro-select2',
				'uap-logs-pro-moment',
				'uap-logs-pro-daterangepicker',
			),
			AUTOMATOR_PRO_PLUGIN_VERSION
		);

		$i18n = new \Uncanny_Automator\Automator_Translations();

		// API data
		$api_setup = array(
			'ajax_url'   => admin_url( 'admin-ajax.php' ),
			'ajax_nonce' => wp_create_nonce( 'load-recipes-ref' ),
			'i18n'       => $i18n->get_all(),
		);
		wp_localize_script( 'uap-logs-pro', 'uapActivityLogApiSetup', $api_setup );

		wp_enqueue_style( 'uap-logs-pro', Utilities::get_css( 'admin/logs.css' ), array(), AUTOMATOR_PRO_PLUGIN_VERSION );

	}

	/**
	 * Creates the filters HTML
	 *
	 * @param String $tab The identificator of the log. ( "recipe", "trigger" or "action" )
	 *
	 * @return String      The HTML
	 */

	public static function activities_filters_html( $tab ) {

		$view_exists = true;

		if ( function_exists( 'automator_db_view_exists' ) ) {
			$view_exists = automator_db_view_exists();
		}

		// Get Recipes Name
		$recipes = self::get_filter_data_of_recipe_dropdown( $view_exists );

		// Get Triggers Name
		$triggers = self::get_filter_data_of_trigger_dropdown( $tab );

		// Get Actions Name
		$actions = self::get_filter_data_of_action_dropdown( $tab );

		// Get Action Statuses
		$action_statuses = self::get_filter_data_of_action_status_dropdown( $tab );

		// Get Users
		$users = self::get_filter_data_of_users_dropdown( $view_exists );

		include Utilities::get_view( 'pro-filters-view.php' );

		return ob_get_clean();
	}

	/**
	 * Prepare query for recipe
	 *
	 * @return string query
	 */
	public static function get_recipe_query() {
		global $wpdb;
		$view_exists = true;
		if ( function_exists( 'automator_db_view_exists' ) ) {
			$view_exists = automator_db_view_exists();
		}
		if ( $view_exists ) {
			$search_conditions = ' 1=1 AND recipe_completed != -1 ';
		} else {
			$search_conditions = ' 1=1 AND r.completed != -1 ';
		}
		if ( automator_filter_has_var( 'search_key' ) && automator_filter_input( 'search_key' ) != '' ) {
			$search_key = esc_attr( automator_filter_input( 'search_key' ) );
			if ( $view_exists ) {
				$search_conditions .= " AND ( (recipe_title LIKE '%$search_key%') OR (display_name  LIKE '%$search_key%' ) OR (user_email  LIKE '%$search_key%' ) ) ";
			} else {
				$search_conditions .= " AND ( (p.post_title LIKE '%$search_key%') OR (u.display_name  LIKE '%$search_key%' ) OR (u.user_email  LIKE '%$search_key%' ) ) ";
			}
		}
		if ( automator_filter_has_var( 'recipe_id' ) && automator_filter_input( 'recipe_id' ) != '' ) {
			if ( $view_exists ) {
				$search_conditions .= " AND automator_recipe_id = '" . absint( automator_filter_input( 'recipe_id' ) ) . "' ";
			} else {
				$search_conditions .= " AND r.automator_recipe_id = '" . absint( automator_filter_input( 'recipe_id' ) ) . "' ";
			}
		}
		if ( automator_filter_has_var( 'user_id' ) && automator_filter_input( 'user_id' ) != '' ) {
			if ( $view_exists ) {
				$search_conditions .= " AND user_id = '" . absint( automator_filter_input( 'user_id' ) ) . "' ";
			} else {
				$search_conditions .= " AND r.user_id = '" . absint( automator_filter_input( 'user_id' ) ) . "' ";
			}
		}
		if ( automator_filter_has_var( 'recipe_log_id' ) && automator_filter_input( 'recipe_log_id' ) != '' ) {
			if ( $view_exists ) {
				$search_conditions .= " AND recipe_log_id = '" . absint( automator_filter_input( 'recipe_log_id' ) ) . "' ";
			} else {
				$search_conditions .= " AND r.ID = '" . absint( automator_filter_input( 'recipe_log_id' ) ) . "' ";
			}
		}
		if ( automator_filter_has_var( 'daterange' ) && automator_filter_input( 'daterange' ) != '' ) {
			$date_range = explode( ' - ', automator_filter_input( 'daterange' ), 2 );
			if ( isset( $date_range[0] ) && isset( $date_range[1] ) ) {
				$date_range[0] = date( 'Y-m-d 00:00:00', strtotime( esc_attr( $date_range[0] ) ) );
				$date_range[1] = date( 'Y-m-d 23:59:59', strtotime( esc_attr( $date_range[1] ) ) );
				if ( $view_exists ) {
					$search_conditions .= " AND (recipe_date_time BETWEEN '{$date_range[0]}' AND '{$date_range[1]}' )";
				} else {
					$search_conditions .= " AND (r.date_time BETWEEN '{$date_range[0]}' AND '{$date_range[1]}' )";
				}
			}
		}

		if ( $view_exists ) {
			$query = "SELECT * FROM {$wpdb->prefix}uap_recipe_logs_view WHERE $search_conditions";
		} else {
			$query = Automator_DB::recipe_log_view_query() . " WHERE $search_conditions";
		}

		return $query;
	}

	/**
	 * Prepare query for trigger
	 *
	 * @return string query
	 */
	public static function get_trigger_query() {
		global $wpdb;
		$view_exists = true;
		if ( function_exists( 'automator_db_view_exists' ) ) {
			$view_exists = automator_db_view_exists( 'trigger' );
		}
		$search_conditions = ' 1=1 ';
		if ( automator_filter_has_var( 'search_key' ) && automator_filter_input( 'search_key' ) != '' ) {
			$search_key = esc_attr( automator_filter_input( 'search_key' ) );
			if ( $view_exists ) {
				$search_conditions .= " AND ( (recipe_title LIKE '%$search_key%') OR (trigger_title LIKE '%$search_key%') OR (display_name  LIKE '%$search_key%' ) OR (user_email LIKE '%$search_key%' ) ) ";
			} else {
				$search_conditions .= " AND ( (p.post_title LIKE '%$search_key%') OR (pt.post_title LIKE '%$search_key%') OR (u.display_name  LIKE '%$search_key%' ) OR (u.user_email LIKE '%$search_key%' ) ) ";
			}
		}
		if ( automator_filter_has_var( 'recipe_id' ) && automator_filter_input( 'recipe_id' ) != '' ) {
			if ( $view_exists ) {
				$search_conditions .= " AND automator_recipe_id = '" . absint( automator_filter_input( 'recipe_id' ) ) . "' ";
			} else {
				$search_conditions .= " AND  t.automator_recipe_id = '" . absint( automator_filter_input( 'recipe_id' ) ) . "' ";
			}
		}
		if ( automator_filter_has_var( 'trigger_id' ) && automator_filter_input( 'trigger_id' ) != '' ) {
			if ( $view_exists ) {
				$search_conditions .= " AND automator_trigger_id = '" . absint( automator_filter_input( 'trigger_id' ) ) . "' ";
			} else {
				$search_conditions .= " AND t.automator_trigger_id = '" . absint( automator_filter_input( 'trigger_id' ) ) . "' ";
			}
		}

		if ( automator_filter_has_var( 'run_number' ) && automator_filter_input( 'run_number' ) != '' ) {
			if ( $view_exists ) {
				$search_conditions .= " AND recipe_run_number = '" . absint( automator_filter_input( 'run_number' ) ) . "' ";
			} else {
				$search_conditions .= " AND r.run_number = '" . absint( automator_filter_input( 'run_number' ) ) . "' ";
			}
		}
		if ( automator_filter_has_var( 'recipe_log_id' ) && automator_filter_input( 'recipe_log_id' ) != '' ) {
			if ( $view_exists ) {
				$search_conditions .= " AND recipe_log_id = '" . absint( automator_filter_input( 'recipe_log_id' ) ) . "' ";
			} else {
				$search_conditions .= " AND t.automator_recipe_log_id = '" . absint( automator_filter_input( 'recipe_log_id' ) ) . "' ";
			}
		}

		if ( automator_filter_has_var( 'user_id' ) && automator_filter_input( 'user_id' ) != '' ) {
			if ( $view_exists ) {
				$search_conditions .= " AND user_id = '" . absint( automator_filter_input( 'user_id' ) ) . "' ";
			} else {
				$search_conditions .= " AND u.ID = '" . absint( automator_filter_input( 'user_id' ) ) . "' ";
			}
		}
		if ( automator_filter_has_var( 'daterange' ) && automator_filter_input( 'daterange' ) != '' ) {
			$date_range = explode( ' - ', automator_filter_input( 'daterange' ), 2 );
			if ( isset( $date_range[0] ) && isset( $date_range[1] ) ) {
				$date_range[0] = date( 'Y-m-d 00:00:00', strtotime( esc_attr( $date_range[0] ) ) );
				$date_range[1] = date( 'Y-m-d 23:59:59', strtotime( esc_attr( $date_range[1] ) ) );
				if ( $view_exists ) {
					$search_conditions .= " AND (recipe_date_time BETWEEN '{$date_range[0]}' AND '{$date_range[1]}' )";
				} else {
					$search_conditions .= " AND (r.date_time BETWEEN '{$date_range[0]}' AND '{$date_range[1]}' )";
				}
			}
		}
		if ( automator_filter_has_var( 'trigger_daterange' ) && automator_filter_input( 'trigger_daterange' ) != '' ) {
			$date_range = explode( ' - ', automator_filter_input( 'trigger_daterange' ), 2 );
			if ( isset( $date_range[0] ) && isset( $date_range[1] ) ) {
				$date_range[0] = date( 'Y-m-d 00:00:00', strtotime( esc_attr( $date_range[0] ) ) );
				$date_range[1] = date( 'Y-m-d 23:59:59', strtotime( esc_attr( $date_range[1] ) ) );
				if ( $view_exists ) {
					$search_conditions .= " AND (trigger_date BETWEEN '{$date_range[0]}' AND '{$date_range[1]}' )";
				} else {
					$search_conditions .= " AND (t.date_time BETWEEN '{$date_range[0]}' AND '{$date_range[1]}' )";
				}
			}
		}
		if ( $view_exists ) {
			$query = "SELECT * FROM {$wpdb->prefix}uap_trigger_logs_view WHERE ($search_conditions) ";
		} else {
			$query = Automator_DB::trigger_log_view_query() . " WHERE ($search_conditions) ";
		}

		return $query;
	}

	/**
	 * Prepare query for action
	 *
	 * @return string query
	 */
	public static function get_action_query() {
		global $wpdb;
		$view_exists = true;
		if ( function_exists( 'automator_db_view_exists' ) ) {
			$view_exists = automator_db_view_exists( 'action' );
		}
		$search_conditions = ' 1=1 ';
		if ( automator_filter_has_var( 'search_key' ) && automator_filter_input( 'search_key' ) != '' ) {
			$search_key = esc_attr( automator_filter_input( 'search_key' ) );
			if ( $view_exists ) {
				$search_conditions .= " AND ( (recipe_title LIKE '%$search_key%') OR (action_title LIKE '%$search_key%') OR (display_name LIKE '%$search_key%' ) OR (user_email LIKE '%$search_key%' ) OR (error_message LIKE '%$search_key%' ) ) ";
			} else {
				$search_conditions .= " AND ( (p.post_title LIKE '%$search_key%') OR (pa.post_title LIKE '%$search_key%') OR (u.display_name LIKE '%$search_key%' ) OR (u.user_email LIKE '%$search_key%' ) OR (error_message LIKE '%$search_key%' ) ) ";
			}
		}
		if ( automator_filter_has_var( 'recipe_id' ) && automator_filter_input( 'recipe_id' ) != '' ) {
			if ( $view_exists ) {
				$search_conditions .= " AND automator_recipe_id = '" . absint( automator_filter_input( 'recipe_id' ) ) . "' ";
			} else {
				$search_conditions .= " AND a.automator_recipe_id = '" . absint( automator_filter_input( 'recipe_id' ) ) . "' ";
			}
		}
		if ( automator_filter_has_var( 'action_id' ) && automator_filter_input( 'action_id' ) != '' ) {
			if ( $view_exists ) {
				$search_conditions .= " AND automator_action_id = '" . absint( automator_filter_input( 'action_id' ) ) . "' ";
			} else {
				$search_conditions .= " AND a.automator_action_id = '" . absint( automator_filter_input( 'action_id' ) ) . "' ";
			}
		}
		if ( automator_filter_has_var( 'user_id' ) && automator_filter_input( 'user_id' ) != '' ) {
			if ( $view_exists ) {
				$search_conditions .= " AND user_id = '" . absint( automator_filter_input( 'user_id' ) ) . "' ";
			} else {
				$search_conditions .= " AND u.ID = '" . absint( automator_filter_input( 'user_id' ) ) . "' ";
			}
		}
		if ( automator_filter_has_var( 'run_number' ) && automator_filter_input( 'run_number' ) != '' ) {
			if ( $view_exists ) {
				$search_conditions .= " AND recipe_run_number = '" . absint( automator_filter_input( 'run_number' ) ) . "' ";
			} else {
				$search_conditions .= " AND r.run_number = '" . absint( automator_filter_input( 'run_number' ) ) . "' ";
			}
		}

		if ( ! empty( automator_filter_input( 'action_completed' ) ) ) {

			$action_completed = esc_attr( automator_filter_input( 'action_completed' ) );
			// Do make exception for not_completed status because '0' evaluates to false.
			if ( 'not_completed' === $action_completed ) {
				$action_completed = '0';
			}
			if ( $view_exists ) {
				$search_conditions .= " AND action_completed ='" . absint( $action_completed ) . "' ";
			} else {
				$search_conditions .= " AND a.action_completed = '" . absint( $action_completed ) . "' ";
			}
		}

		if ( automator_filter_has_var( 'recipe_log_id' ) && automator_filter_input( 'recipe_log_id' ) != '' ) {
			if ( $view_exists ) {
				$search_conditions .= " AND recipe_log_id = '" . absint( automator_filter_input( 'recipe_log_id' ) ) . "' ";
			} else {
				$search_conditions .= " AND a.automator_recipe_log_id = '" . absint( automator_filter_input( 'recipe_log_id' ) ) . "' ";
			}
		}
		if ( automator_filter_has_var( 'daterange' ) && automator_filter_input( 'daterange' ) != '' ) {
			$date_range = explode( ' - ', automator_filter_input( 'daterange' ), 2 );
			if ( isset( $date_range[0] ) && isset( $date_range[1] ) ) {
				$date_range[0] = date( 'Y-m-d 00:00:00', strtotime( esc_attr( $date_range[0] ) ) );
				$date_range[1] = date( 'Y-m-d 23:59:59', strtotime( esc_attr( $date_range[1] ) ) );
				if ( $view_exists ) {
					$search_conditions .= " AND (recipe_date_time BETWEEN '{$date_range[0]}' AND '{$date_range[1]}' )";
				} else {
					$search_conditions .= " AND (r.date_time BETWEEN '{$date_range[0]}' AND '{$date_range[1]}' )";
				}
			}
		}
		if ( automator_filter_has_var( 'action_daterange' ) && automator_filter_input( 'action_daterange' ) != '' ) {
			$date_range = explode( ' - ', automator_filter_input( 'action_daterange' ), 2 );
			if ( isset( $date_range[0] ) && isset( $date_range[1] ) ) {
				$date_range[0] = date( 'Y-m-d 00:00:00', strtotime( esc_attr( $date_range[0] ) ) );
				$date_range[1] = date( 'Y-m-d 23:59:59', strtotime( esc_attr( $date_range[1] ) ) );
				if ( $view_exists ) {
					$search_conditions .= " AND (action_date BETWEEN '{$date_range[0]}' AND '{$date_range[1]}' )";
				} else {
					$search_conditions .= " AND (a.date_time BETWEEN '{$date_range[0]}' AND '{$date_range[1]}' )";
				}
			}
		}

		if ( $view_exists ) {
			$query = "SELECT * FROM {$wpdb->prefix}uap_action_logs_view WHERE ($search_conditions)";
		} else {
			$sql = Automator_DB::action_log_view_query( false );

			$query = "$sql WHERE ($search_conditions) GROUP BY a.ID";
		}

		return $query;
	}

	/**
	 * @param $view_exists
	 *
	 * @return array
	 */
	public static function get_filter_data_of_recipe_dropdown( $view_exists ) {
		global $wpdb;

		// If view exists, then we need to get the recipes from the view
		if ( $view_exists ) {
			return $wpdb->get_results(
				"SELECT DISTINCT(automator_recipe_id) AS id, recipe_title, post_status AS recipe_status
				FROM {$wpdb->prefix}uap_recipe_logs_view as recipe_log_view
				INNER JOIN {$wpdb->posts} as post
				ON post.ID = recipe_log_view.automator_recipe_id
				ORDER BY recipe_title ASC",
				ARRAY_A
			);
		}

		// If view does not exist, then we need to get the recipes from the table
		return $wpdb->get_results(
			$wpdb->prepare(
				"SELECT DISTINCT(rl.automator_recipe_id) AS `id`, p.post_title AS `recipe_title`, p.post_status AS `recipe_status`
FROM {$wpdb->prefix}uap_recipe_log rl
JOIN $wpdb->posts p
ON rl.automator_recipe_id = p.ID
WHERE post_type = %s
ORDER BY post_title ASC",
				'uo-recipe'
			),
			ARRAY_A
		);
	}

	/**
	 * @param $tab
	 *
	 * @return array
	 */
	public static function get_filter_data_of_trigger_dropdown( $tab ) {
		if ( 'trigger-log' !== $tab ) {
			return array();
		}

		global $wpdb;
		$trigger_view_exists = true;

		if ( function_exists( 'automator_db_view_exists' ) ) {
			$trigger_view_exists = automator_db_view_exists( 'trigger' );
		}

		// If view exists, then we need to get the triggers from the view
		if ( $trigger_view_exists ) {
			return $wpdb->get_results(
				"SELECT DISTINCT(automator_trigger_id) AS id, trigger_title
					FROM {$wpdb->prefix}uap_trigger_logs_view
					ORDER BY trigger_title ASC",
				ARRAY_A
			);
		}

		// If view does not exist, then we need to get the triggers from the table
		return $wpdb->get_results(
			"SELECT DISTINCT(tl.automator_trigger_id) AS `id`, p.post_title AS `trigger_title`
FROM {$wpdb->prefix}uap_trigger_log tl
JOIN $wpdb->posts p
ON tl.automator_trigger_id = p.ID
ORDER BY p.post_title ASC",
			ARRAY_A
		);
	}

	/**
	 * @param $tab
	 *
	 * @return array
	 */
	public static function get_filter_data_of_action_dropdown( $tab ) {
		if ( 'action-log' !== $tab ) {
			return array();
		}

		global $wpdb;

		$action_view_exists = true;

		if ( function_exists( 'automator_db_view_exists' ) ) {
			$action_view_exists = automator_db_view_exists( 'action' );
		}

		// If view exists, then we need to get the actions from the view
		if ( $action_view_exists ) {
			return $wpdb->get_results(
				"SELECT DISTINCT (automator_action_id) AS id, action_title
					FROM {$wpdb->prefix}uap_action_logs_view
					ORDER BY action_title ASC",
				ARRAY_A
			);
		}

		// If view does not exist, then we need to get the actions from the table
		return $wpdb->get_results(
			"SELECT DISTINCT(al.automator_action_id) AS `id`, p.post_title AS `action_title`
FROM {$wpdb->prefix}uap_action_log al
JOIN $wpdb->posts p
ON al.automator_action_id = p.ID
ORDER BY p.post_title ASC",
			ARRAY_A
		);
	}

	/**
	 * @param $tab
	 *
	 * @return array
	 */
	public static function get_filter_data_of_action_status_dropdown( $tab ) {

		// If tab is not action-log, then we don't need to get the action statuses
		if ( 'action-log' !== $tab ) {
			return array();
		}

		global $wpdb;

		$action_view_exists = true;

		if ( function_exists( 'automator_db_view_exists' ) ) {
			$action_view_exists = automator_db_view_exists( 'action' );
		}

		// If view exists, then we need to get the action statuses from the view
		if ( $action_view_exists ) {
			return $wpdb->get_results(
				"SELECT DISTINCT action_completed AS action_completed
					FROM {$wpdb->prefix}uap_action_logs_view
					ORDER BY action_completed ASC",
				ARRAY_A
			);
		}

		// If view does not exist, then we need to get the action statuses from the table
		return $wpdb->get_results(
			"SELECT DISTINCT(completed) AS `action_completed`
FROM {$wpdb->prefix}uap_action_log
ORDER BY completed ASC",
			ARRAY_A
		);
	}

	/**
	 * @param $recipe_view_exists
	 *
	 * @return array
	 */
	public static function get_filter_data_of_users_dropdown( $recipe_view_exists ) {
		global $wpdb;

		// If view exists, then we need to get the users from the view
		if ( $recipe_view_exists ) {
			return $wpdb->get_results(
				"SELECT DISTINCT (user_id) as id, display_name AS title, user_email
					FROM {$wpdb->prefix}uap_recipe_logs_view
					ORDER BY title ASC",
				ARRAY_A
			);
		}

		// If view does not exist, then we need to get the users from the table
		return $wpdb->get_results(
			"SELECT DISTINCT(rl.user_id) AS `id`, u.display_name AS `title`, u.user_email
FROM {$wpdb->prefix}uap_recipe_log rl
JOIN $wpdb->users u
ON rl.user_id = u.ID
ORDER BY u.display_name ASC",
			ARRAY_A
		);
	}
}
