<?php

namespace Uncanny_Automator_Pro\Integrations\Schedule;

use Uncanny_Automator_Pro\Integrations\Schedule\Helpers\Schedule_Helpers;

/**
 *
 */
class Admin_Helper {

	/**
	 *
	 */
	public function __construct() {
		add_action( 'admin_menu', array( $this, 'uo_recipe_admin_menu' ) );

		add_action( 'admin_post_cancel_schedule', array( $this, 'cancel_schedule' ) );
		add_action( 'admin_post_run_now', array( $this, 'run_now' ) );

		add_action( 'automator_show_internal_admin_notice', array( $this, 'display_cancel_schedule_success_message' ) );
	}

	/**
	 * @return void
	 */
	public function uo_recipe_admin_menu() {
		global $wpdb;

		add_submenu_page(
			'edit.php?post_type=uo-recipe', // Parent slug
			esc_html_x( 'Scheduled recipes', 'Scheduled recipes', 'uncanny-automator-pro' ), // Page title
			esc_html_x( 'Scheduled recipes', 'Scheduled recipes', 'uncanny-automator-pro' ), // Menu title
			'manage_options', // Capability
			'uo-recipe-scheduled-actions', // Menu slug
			array( $this, 'uo_recipe_scheduled_actions_page_callback' ), // Function callback
			5
		);
	}

	/**
	 * @return void
	 */
	public function cancel_schedule() {
		$schedule_id = automator_filter_input( 'schedule_id' );
		$nonce       = automator_filter_input( '_wpnonce' );

		if ( $schedule_id && wp_verify_nonce( $nonce, 'cancel_schedule_' . $schedule_id ) ) {
			\ActionScheduler::store()->cancel_action( $schedule_id );
			// Set a success message in a transient
			set_transient( 'uo_recipe_schedule_cancelled', 'Scheduled recipe cancelled successfully.', 60 );
			// Redirect to the custom admin page with a query arg to check for the transient
			wp_safe_redirect( admin_url( 'edit.php?post_type=uo-recipe&page=uo-recipe-scheduled-actions&cancelled=1' ) );
			exit;
		}
	}

	/**
	 * @return void
	 */
	public function run_now() {
		$schedule_id = automator_filter_input( 'schedule_id' );
		$nonce       = automator_filter_input( '_wpnonce' );

		if ( $schedule_id && wp_verify_nonce( $nonce, 'run_now_' . $schedule_id ) ) {

			$store  = \ActionScheduler::store();
			$action = $store->fetch_action( $schedule_id );

			as_schedule_single_action( time() + 5, $action->get_hook(), $action->get_args(), 'Uncanny Automator' );

			// Set a success message in a transient
			set_transient( 'uo_recipe_schedule_run_now', 'The recipe is queued to run shortly.', 60 );
			// Redirect to the custom admin page with a query arg to check for the transient
			wp_safe_redirect( admin_url( 'edit.php?post_type=uo-recipe&page=uo-recipe-scheduled-actions&run_completed=1' ) );
			exit;
		}
	}

	/**
	 * @return void
	 */
	public function uo_recipe_scheduled_actions_page_callback() {

		if ( ! class_exists( '\WP_List_Table' ) ) {
			require_once ABSPATH . 'wp-admin/includes/class-wp-list-table.php';
		}

		new UO_Recipe_Scheduled_Actions_List_Table();
	}

	/**
	 * @return void
	 */
	public function display_cancel_schedule_success_message() {
		// Check if coming from a successful cancellation.
		if ( automator_filter_has_var( 'cancelled' ) && get_transient( 'uo_recipe_schedule_cancelled' ) ) {
			$message = get_transient( 'uo_recipe_schedule_cancelled' );
			// Display the success message.
			echo '<div class="notice notice-success is-dismissible"><p>' . esc_html( $message ) . '</p></div>';
			// Delete the transient so the message doesn't persist on refresh.
			delete_transient( 'uo_recipe_schedule_cancelled' );
		}
		if ( automator_filter_has_var( 'run_completed' ) && get_transient( 'uo_recipe_schedule_run_now' ) ) {
			$message = get_transient( 'uo_recipe_schedule_run_now' );
			// Display the success message.
			echo '<div class="notice notice-success is-dismissible"><p>' . esc_html( $message ) . '</p></div>';
			// Delete the transient so the message doesn't persist on refresh.
			delete_transient( 'uo_recipe_schedule_run_now' );
		}
	}

	/**
	 * @return void
	 */
	public static function uo_recipe_dropdown() {
		$valid_hooks = array(
			'RECURRING_WEEKDAY_TRIGGER_CODE',
			'RECURRING_TRIGGER_CODE',
			'TRIGGER_ON_SPECIFIC_DATE',
		);

		global $wpdb;
		$recipe_posts = $wpdb->get_col( "SELECT post_id FROM $wpdb->postmeta WHERE meta_value IN ('" . implode( "','", $valid_hooks ) . "') AND meta_key = 'code'" ); //phpcs:ignore
		echo '<select name="recipe_filter" id="recipe_filter">'; // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped
		echo '<option value="">' . esc_html__( 'All Recipes', 'uncanny-automator-pro' ) . '</option>';
		foreach ( $recipe_posts as $posts ) {
			$post = get_post( $posts );
			if ( empty( $wpdb->get_col( "SELECT action_id FROM $wpdb->actionscheduler_actions WHERE args LIKE '%" . $post->post_parent . "%' AND status = 'pending'" ) ) ) { //phpcs:ignore
				continue;
			}
			$selected = automator_filter_has_var( 'recipe_filter' ) && absint( automator_filter_input( 'recipe_filter' ) ) === absint( $post->post_parent ) ? ' selected="selected"' : '';
			$title    = get_the_title( $post->post_parent );
			echo '<option value="' . esc_attr( $post->post_parent ) . '"' . $selected . '>(ID: ' . esc_html( $post->ID ) . ') ' . esc_html( $title ) . '</option>';// phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped
		}

		echo '</select>';
	}
}
