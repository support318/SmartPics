<?php

namespace Uncanny_Automator_Pro;

/**
 *
 */
class Migrate_Integrations_Items {

	/**
	 *
	 */
	public function __construct() {

		add_action( 'admin_init', array( $this, 'migrate_magic_button_integration_items' ) );
		add_action( 'admin_init', array( $this, 'migrate_run_code_integration_items' ) );
		add_action( 'admin_init', array( $this, 'migrate_generator_integration_items' ) );

		add_action( 'automator_daily_healthcheck', array( $this, 'perform_run_code_migration' ), 999 );
		add_action( 'automator_daily_healthcheck', array( $this, 'perform_magic_button_migration' ), 999 );
		add_action( 'automator_daily_healthcheck', array( $this, 'perform_generator_migration' ), 999 );
		add_action( 'automator_daily_healthcheck', array( $this, 'update_webhooks_sample_values' ), 999 );
	}

	/**
	 * @return void
	 * @since 5.4
	 */
	public function migrate_magic_button_integration_items() {
		$option_key = 'automator_magic_button_trigger_moved';

		if ( 'yes' === automator_pro_get_option( $option_key, 'no' ) ) {
			return;
		}

		$this->perform_magic_button_migration();

		automator_pro_update_option( $option_key, 'yes' );
	}

	/**
	 * @return void
	 */
	public function migrate_generator_integration_items() {
		$option_key = 'automator_generator_action_moved';

		if ( 'yes' === automator_pro_get_option( $option_key, 'no' ) ) {
			return;
		}

		$this->perform_generator_migration();

		automator_pro_update_option( $option_key, 'yes' );
	}

	/**
	 * @return void
	 * @since 5.4
	 */
	public function perform_magic_button_migration() {

		if ( defined( 'DOING_CRON' ) && 'yes' === automator_pro_get_option( 'automator_health_check_magic_button_moved', 'no' ) ) {
			return;
		}

		global $wpdb;

		$current_actions = $wpdb->get_results(
			$wpdb->prepare(
				"SELECT post_id
FROM $wpdb->postmeta
WHERE meta_value IN ('WPMAGICLINK', 'ANONWPMAGICBUTTON', 'ANONWPMAGICLINK', 'WPMAGICBUTTON')
  AND meta_key = %s",
				'code'
			)
		);

		if ( empty( $current_actions ) ) {

			if ( defined( 'DOING_CRON' ) ) {
				automator_pro_update_option( 'automator_health_check_magic_button_moved', 'yes' );
			}

			return;
		}

		foreach ( $current_actions as $trigger ) {
			$trigger_id = $trigger->post_id;
			update_post_meta( $trigger_id, 'integration', 'MAGIC_BUTTON' );
			update_post_meta( $trigger_id, 'integration_name', 'Magic Button' );
		}

		if ( defined( 'DOING_CRON' ) ) {
			automator_pro_update_option( 'automator_health_check_magic_button_moved', 'yes' );
		}
	}

	/**
	 * @return void
	 */
	public function migrate_run_code_integration_items() {
		$option_key = 'automator_run_code_trigger_moved';

		if ( 'yes' === automator_pro_get_option( $option_key, 'no' ) ) {
			return;
		}

		$this->perform_run_code_migration();

		automator_pro_update_option( $option_key, 'yes' );
	}

	/**
	 * @return void
	 * @since 5.4
	 */
	public function perform_run_code_migration() {

		if ( defined( 'DOING_CRON' ) && 'yes' === automator_pro_get_option( 'automator_health_check_run_code_moved', 'no' ) ) {
			return;
		}

		global $wpdb;

		$current_actions = $wpdb->get_results(
			$wpdb->prepare(
				"SELECT post_id
FROM $wpdb->postmeta
WHERE meta_value IN ('UOA_CALL_FUNC_CODE', 'UOA_CALL_FUNC_EVERYONE_CODE', 'UOADOACTION', 'UOADOACTION_EVERYONE', 'UOA_RUN_JS')
  AND meta_key = %s",
				'code'
			)
		);

		if ( empty( $current_actions ) ) {

			if ( defined( 'DOING_CRON' ) ) {
				automator_pro_update_option( 'automator_health_check_run_code_moved', 'yes' );
			}

			return;
		}

		foreach ( $current_actions as $action ) {
			$action_id = $action->post_id;
			update_post_meta( $action_id, 'integration', 'RUN_CODE' );
			update_post_meta( $action_id, 'integration_name', 'Run Code' );
		}

		if ( defined( 'DOING_CRON' ) ) {
			automator_pro_update_option( 'automator_health_check_run_code_moved', 'yes' );
		}
	}

	/**
	 * @return void
	 */
	public function perform_generator_migration() {

		if ( defined( 'DOING_CRON' ) && 'yes' === automator_pro_get_option( 'automator_health_check_generator_moved', 'no' ) ) {
			return;
		}

		global $wpdb;

		$current_actions = $wpdb->get_results(
			$wpdb->prepare(
				"SELECT post_id
FROM $wpdb->postmeta
WHERE meta_value = %s
  AND meta_key = %s",
				'GENERATE_RAND_STR_META',
				'code'
			)
		);

		if ( empty( $current_actions ) ) {

			if ( defined( 'DOING_CRON' ) ) {
				automator_pro_update_option( 'automator_health_check_generator_moved', 'yes' );
			}

			return;
		}

		foreach ( $current_actions as $action ) {
			$action_id = $action->post_id;
			update_post_meta( $action_id, 'integration', 'AUTOMATOR_GENERATOR' );
			update_post_meta( $action_id, 'integration_name', 'Generator' );
		}

		if ( defined( 'DOING_CRON' ) ) {
			automator_pro_update_option( 'automator_health_check_generator_moved', 'yes' );
		}
	}

	/**
	 * @return void
	 */
	public function update_webhooks_sample_values() {

		if ( 'yes' === automator_pro_get_option( 'automator_health_check_webhook_sample_values_moved', 'no' ) ) {
			return;
		}

		global $wpdb;

		$current_actions = $wpdb->get_results(
			$wpdb->prepare(
				"SELECT post_id, meta_value
         FROM $wpdb->postmeta
         WHERE meta_key = %s",
				'WEBHOOK_SAMPLE'
			)
		);

		if ( empty( $current_actions ) ) {

			automator_pro_update_option( 'automator_health_check_webhook_sample_values_moved', 'yes' );

			return;
		}

		foreach ( $current_actions as $action ) {
			$changed   = false;
			$action_id = $action->post_id;
			// Exiting sample data
			$webhook_sample = maybe_unserialize( $action->meta_value );

			// Check if the sample data is empty
			if ( empty( $webhook_sample ) ) {
				continue;
			}

			// Existing field values
			$webhook_fields = json_decode( get_post_meta( $action_id, 'WEBHOOK_FIELDS', true ), true );

			if ( empty( $webhook_fields ) ) {
				continue;
			}

			// Normalize the sample data
			$params = Webhook_Rest_Handler::handle_non_json_type_format( $webhook_sample, get_post_meta( $action_id, 'DATA_FORMAT', true ) );

			// Convert the sample data to an array
			if ( is_object( $params ) ) {
				// Make sure the $params contains a valid array.
				$params = (array) json_decode( wp_json_encode( $params ), true );
			}

			// Convert multidimensional array to a single dimensional array
			$fields = Webhook_Rest_Handler::handle_params( $params );

			foreach ( $webhook_fields as $key => $value ) {
				if ( isset( $value['SAMPLE_VALUE'] ) ) {
					continue;
				}

				$webhook_fields[ $key ]['SAMPLE_VALUE'] = isset( $fields[ $value['KEY'] ] ) ? $fields[ $value['KEY'] ] : '';

				$changed = true;
			}

			if ( $changed ) {
				$webhook_fields = json_encode( $webhook_fields );
				update_post_meta( $action_id, 'WEBHOOK_FIELDS', $webhook_fields );
			}
		}

		automator_pro_update_option( 'automator_health_check_webhook_sample_values_moved', 'yes' );
	}
}
