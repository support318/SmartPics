<?php
/**
 * WP Fusion - Elementor Forms Integration.
 *
 * @package   WP Fusion
 * @copyright Copyright (c) 2024, Very Good Plugins, https://verygoodplugins.com
 * @license   GPL-3.0+
 * @since     3.21.1
 */

use ElementorPro\Modules\Forms\Classes\Form_Record;

if ( ! defined( 'ABSPATH' ) ) {
	exit; // Exit if accessed directly.
}

/**
 * Handles integration with Elementor Forms.
 *
 * @since 3.21.1
 */
class WPF_Elementor_Forms extends WPF_Integrations_Base {

	/**
	 * The slug for WP Fusion's module tracking.
	 *
	 * @since 3.21.1
	 * @var string $slug
	 */
	public $slug = 'elementor-forms';

	/**
	 * The plugin name for WP Fusion's module tracking.
	 *
	 * @since 3.21.1
	 * @var string $name
	 */
	public $name = 'Elementor Forms';

	/**
	 * The link to the documentation on the WP Fusion website.
	 *
	 * @since 3.21.1
	 * @var string $docs_url
	 */
	public $docs_url = 'https://wpfusion.com/documentation/lead-generation/elementor-forms/';

	/**
	 * Gets things started.
	 *
	 * @since 3.21.1
	 */
	public function init() {

		// Load required classes.
		require_once __DIR__ . '/class-elementor-field-mapping.php';
		require_once __DIR__ . '/class-elementor-forms-integration.php';

		add_action( 'elementor_pro/init', array( $this, 'add_form_actions' ) );
		add_action( 'elementor/controls/register', array( $this, 'register_controls' ) );

		// Batch operations.
		add_filter( 'wpf_export_options', array( $this, 'export_options' ) );
		add_filter( 'wpf_batch_elementor_forms_init', array( $this, 'batch_init' ) );
		add_action( 'wpf_batch_elementor_forms', array( $this, 'batch_step' ) );
	}

	/**
	 * Registers the form actions.
	 *
	 * @since 3.41.24
	 */
	public function add_form_actions() {
		if ( version_compare( ELEMENTOR_PRO_VERSION, '3.5.0', '>=' ) ) {
			\ElementorPro\Plugin::instance()->modules_manager->get_modules( 'forms' )->actions_registrar->register( new WPF_Elementor_Forms_Integration(), 'wpfusion' );
		} else {
			\ElementorPro\Plugin::instance()->modules_manager->get_modules( 'forms' )->add_form_action( 'wpfusion', new WPF_Elementor_Forms_Integration() );
		}
	}

	/**
	 * Register the field mapping control.
	 *
	 * @since 3.41.24
	 *
	 * @param object $controls The controls manager.
	 */
	public function register_controls( $controls ) {
		$controls->register( new WPF_Elementor_Field_Mapping() );
	}

	/**
	 * Get sent data from submissions table.
	 *
	 * @since 3.43.0
	 *
	 * @param integer $submission_id The submission ID.
	 * @return array|void The sent data.
	 */
	private function get_sent_data( $submission_id ) {
		global $wpdb;

		$results = $wpdb->get_results(
			$wpdb->prepare(
				"SELECT `key`,`value` FROM {$wpdb->prefix}e_submissions_values WHERE `submission_id` = %d",
				$submission_id
			),
			ARRAY_A
		);

		if ( empty( $results ) ) {
			return;
		}

		return array_column( $results, 'value', 'key' );
	}

	/**
	 * Adds Elementor forms checkbox to available export options.
	 *
	 * @since 3.43.0
	 *
	 * @param array $options The options.
	 * @return array The options.
	 */
	public function export_options( $options ) {
		$options['elementor_forms'] = array(
			'label'         => 'Elementor Forms submissions',
			'process_again' => true,
			'title'         => 'Entries',
			// translators: %s CRM name.
			'tooltip'       => sprintf( __( 'Finds Elementor Forms entries that have not been successfully processed by WP Fusion and syncs them to %s based on their configured feeds.', 'wp-fusion' ), wp_fusion()->crm->name ),
		);

		return $options;
	}

	/**
	 * Gets total list of entries to be processed.
	 *
	 * @since 3.43.0
	 *
	 * @param array $args The args.
	 * @return array Submission IDs.
	 */
	public function batch_init( $args ) {
		global $wpdb;
		$query = "SELECT DISTINCT `submission_id` FROM {$wpdb->prefix}e_submissions_values";
		if ( ! empty( $args['skip_processed'] ) ) {
			$query .= " WHERE NOT EXISTS (SELECT 1 FROM {$wpdb->prefix}e_submissions_values as t2 WHERE t2.submission_id = {$wpdb->prefix}e_submissions_values.submission_id AND t2.key = 'wpf_complete')";
		}

		$submissions_ids = $wpdb->get_results( $wpdb->prepare( $query ), ARRAY_A );
		if ( ! empty( $submissions_ids ) ) {
			$submissions_ids = wp_list_pluck( $submissions_ids, 'submission_id' );
		}

		return $submissions_ids;
	}

	/**
	 * Processes submission.
	 *
	 * @since 3.43.0
	 *
	 * @param int $submission_id The submission ID.
	 */
	public function batch_step( $submission_id ) {
		global $wpdb;
		$results = $wpdb->get_results(
			$wpdb->prepare(
				"SELECT `post_id`,`element_id` FROM {$wpdb->prefix}e_submissions WHERE `type` = 'submission' AND `id` = %d",
				$submission_id
			),
			ARRAY_A
		);

		if ( empty( $results ) ) {
			return;
		}

		$el_data = json_decode( get_post_meta( $results[0]['post_id'], '_elementor_data', true ), true );
		if ( empty( $el_data ) ) {
			return;
		}

		$form = '';
		foreach ( $el_data as $value ) {
			foreach ( $value['elements'] as $element ) {
				if ( 'form' === $element['widgetType'] && $results[0]['element_id'] === $element['id'] ) {
					$form = $element;
				}
			}
		}

		if ( '' === $form ) {
			return;
		}

		$sent_data          = $this->get_sent_data( $submission_id );
		$record             = new Form_Record( $sent_data, $form );
		$wpf_el_forms_class = new WPF_Elementor_Forms_Integration();
		$wpf_el_forms_class->run( $record );
	}
}

new WPF_Elementor_Forms();
