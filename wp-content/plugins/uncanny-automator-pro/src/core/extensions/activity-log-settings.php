<?php

namespace Uncanny_Automator_Pro;

use DateTime;
use DateTimeZone;
use Uncanny_Automator\Automator_Status;

/**
 * Class Activity_Log_Settings
 *
 * @package Uncanny_Automator_Pro
 */
class Activity_Log_Settings {

	/**
	 * @var string
	 */
	public static $cron_schedule = 'uapro_auto_purge_logs';

	/**
	 * Class constructor
	 */
	public function __construct() {
		// Add field to the settings page
		add_action( 'automator_settings_general_logs_content', array( $this, 'tab_output_auto_purge_fields' ) );

		add_action( self::$cron_schedule, array( $this, 'delete_old_logs' ) );
		add_action( 'automator_pro_force_purge_logs', array( $this, 'delete_records' ), 99, 1 );
		add_action( 'admin_init', array( $this, 'save_prone_logs' ) );
		add_action( 'admin_init', array( $this, 'maybe_schedule_purge_logs' ) );
	}

	/**
	 *
	 */
	public function save_prone_logs() {
		if ( ! automator_filter_has_var( 'post_type' ) ) {
			return;
		}

		if ( ! automator_filter_has_var( 'page' ) ) {
			return;
		}

		if ( 'uo-recipe' !== automator_filter_input( 'post_type' ) ) {
			return;
		}

		if ( 'uncanny-automator-config' !== automator_filter_input( 'page' ) ) {
			return;
		}

		if ( 'logs' !== automator_filter_input( 'general' ) ) {
			return;
		}

		if (
			! automator_filter_has_var( 'uap_automator_purge_days_switch', INPUT_POST ) &&
			! automator_filter_has_var( 'uap_automator_purge_days', INPUT_POST )
		) {
			return;
		}

		// Get data
		$enable_auto_prune = automator_filter_input( 'uap_automator_purge_days_switch', INPUT_POST );
		$auto_prune_days   = automator_filter_input( 'uap_automator_purge_days', INPUT_POST );
		$auto_prune_unit   = automator_filter_input( 'uap_automator_purge_unit', INPUT_POST );

		if ( ! empty( $auto_prune_days ) ) {
			// Save the number of days
			automator_pro_update_option( 'uap_automator_purge_days', $auto_prune_days );
			automator_pro_update_option( 'uap_automator_purge_unit', $auto_prune_unit );
		}

		// Check if we have to unschedule
		if ( empty( $enable_auto_prune ) || $enable_auto_prune == '0' ) {

			// Unschedule actions
			as_unschedule_all_actions( self::$cron_schedule );

			wp_safe_redirect(
				add_query_arg(
					array(
						'unscheduled' => 1,
					),
					$this->get_logs_settings_url()
				)
			);

			exit;

		}

	}

	/**
	 *
	 * Add values to settings tab
	 *
	 * @param $tabs
	 *
	 * @return mixed
	 */
	public function tab_output_auto_purge_fields() {
		// Check if the setting is enabled
		$is_enabled = ! empty( as_next_scheduled_action( self::$cron_schedule ) );

		// Interval
		$interval_number_of_days = automator_pro_get_option( 'uap_automator_purge_days' );
		$interval_unit           = automator_pro_get_option( 'uap_automator_purge_unit', 'days' );

		// Load the view
		include Utilities::get_view( 'admin-settings/tab/general/logs/auto-prune-logs.php' );
	}

	/**
	 * Delete old logs.
	 */
	public function delete_old_logs() {

		$purge_value = apply_filters(
			'automator_pro_auto_purge_logs_days',
			automator_pro_get_option( 'uap_automator_purge_days', '' )
		);

		$unit = automator_pro_get_option( 'uap_automator_purge_unit', 'days' );

		if ( false === apply_filters( 'automator_pro_auto_purge_logs_force_delete', false, $purge_value ) ) {
			if ( empty( $purge_value ) || intval( $purge_value ) < 1 ) {
				return;
			}
		}

		$this->delete_records( $purge_value, $unit );
	}

	/**
	 * @param $purge_value
	 *
	 * @return void
	 */
	public function delete_records( $purge_value = 1, $unit = 'days' ) {

		global $wpdb;

		$finished_statuses = apply_filters( 'automator_pro_auto_purge_logs_statuses_to_remove', Automator_Status::get_finished_statuses() );

		// Optionally not remove "Completed with errors"
		if ( true === apply_filters( 'automator_pro_auto_purge_logs_exclude_completed_with_errors', true ) ) {
			foreach ( $finished_statuses as $k => $v ) {
				if ( 2 === (int) $v ) {
					unset( $finished_statuses[ $k ] );
				}
			}
		}

		$datetime_base = $this->get_datetime_base( $purge_value, $unit );

		$previous_time = apply_filters( 'automator_pro_auto_purge_logs_previous_time', $datetime_base, $purge_value );

		$qry = $wpdb->prepare(
			"SELECT `ID`, `automator_recipe_id`, `run_number`
				FROM {$wpdb->prefix}uap_recipe_log
					WHERE `date_time` < %s
						AND `completed` IN (" . join( ',', $finished_statuses ) . ')',
			$previous_time
		);

		$recipes = $wpdb->get_results( $qry );

		if ( empty( $recipes ) ) {
			return;
		}

		foreach ( $recipes as $recipe ) {

			$recipe_id               = absint( $recipe->automator_recipe_id );
			$automator_recipe_log_id = absint( $recipe->ID );
			$run_number              = absint( $recipe->run_number );

			// Purge recipe logs.
			automator_purge_recipe_logs( $recipe_id, $automator_recipe_log_id );
			// Purge trigger logs.
			automator_purge_trigger_logs( $recipe_id, $automator_recipe_log_id );
			// Purge action logs.
			automator_purge_action_logs( $recipe_id, $automator_recipe_log_id );
			// Purge closure logs.
			automator_purge_closure_logs( $recipe_id, $automator_recipe_log_id );

			do_action( 'automator_recipe_log_deleted', $recipe_id, $automator_recipe_log_id, $run_number );

		}
	}

	/**
	 * Retrieve the base datetime string.
	 *
	 * @param int $purge_value Default 1
	 * @param string $unit Default 'days'
	 *
	 * @return string
	 */
	public static function get_datetime_base( $purge_value = 1, $unit = 'days' ) {

		$dt = new DateTime();
		$dt->setTimestamp( strtotime( '-' . $purge_value . ' ' . $unit ) );
		$dt->setTimezone( new DateTimeZone( Automator()->get_timezone_string() ) );

		return $dt->format( 'Y-m-d H:i:s' );
	}


	/**
	 * @param $tbl
	 * @param $tbl_meta
	 * @param $log_meta_key
	 * @param $recipe_id
	 * @param $automator_recipe_log_id
	 */
	public static function delete_logs( $tbl, $tbl_meta, $log_meta_key, $recipe_id, $automator_recipe_log_id ) {
		global $wpdb;
		$results = $wpdb->get_col( $wpdb->prepare( "SELECT `ID` FROM $tbl WHERE automator_recipe_id=%d AND automator_recipe_log_id=%d", $recipe_id, $automator_recipe_log_id ) ); //phpcs:ignore WordPress.DB.PreparedSQL.InterpolatedNotPrepared
		if ( $results ) {
			foreach ( $results as $automator_log_id ) {
				$wpdb->delete(
					$tbl_meta,
					array( $log_meta_key => $automator_log_id )
				);
			}
		}

		$wpdb->delete(
			$tbl,
			array(
				'automator_recipe_id'     => $recipe_id,
				'automator_recipe_log_id' => $automator_recipe_log_id,
			)
		);
	}

	/**
	 *
	 */
	public static function maybe_schedule_purge_logs() {

		if ( ! automator_filter_has_var( '_wpnonce', INPUT_POST ) || ! automator_filter_has_var( 'uap_automator_purge_days', INPUT_POST ) ) {
			return;
		}

		if ( ! wp_verify_nonce( automator_filter_input( '_wpnonce', INPUT_POST ), 'uncanny_automator' ) ) {
			return;
		}

		as_unschedule_all_actions( self::$cron_schedule );

		// Add Action Scheduler event.
		$interval_unit = automator_pro_get_option( 'uap_automator_purge_unit', 'days' );

		// Hourly.
		if ( 'hours' === $interval_unit ) {
			as_schedule_recurring_action( strtotime( '+1 hour' ), 3600, self::$cron_schedule );
			return;
		}

		// Minutes.
		if ( 'minutes' === $interval_unit ) {
			$minutes_interval = apply_filters( 'uap_purge_logs_minutes_interval', 10 );
			as_schedule_recurring_action( strtotime( '+' . $minutes_interval . ' minutes' ), 60 * $minutes_interval, self::$cron_schedule );
			return;
		}

		// The default midnight daily.
		as_schedule_cron_action( strtotime( 'midnight tonight' ), '@daily', self::$cron_schedule );

	}

	/**
	 * Get the URL with the field to prune the logs
	 *
	 * @return string The URL
	 */
	public function get_logs_settings_url() {
		return add_query_arg(
			array(
				'post_type' => 'uo-recipe',
				'page'      => 'uncanny-automator-config',
				'tab'       => 'general',
				'general'   => 'logs',
			),
			admin_url( 'edit.php' )
		);
	}
}

