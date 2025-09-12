<?php

namespace Uncanny_Automator_Pro\Integrations\EDD_SL;

use Uncanny_Automator\Integrations\Edd_SL\Edd_Sl_Helpers;

/**
 * Class EDD_SL_CONDITION_ACTIVE_LICENSE_FOR_DOWNLOAD
 *
 * @package Uncanny_Automator_Pro
 */
class EDD_SL_CONDITION_ACTIVE_LICENSE_FOR_DOWNLOAD extends \Uncanny_Automator_Pro\Action_Condition {

	/**
	 * Defines the condition.
	 *
	 * @return void
	 */
	public function define_condition() {

		$this->integration   = 'EDD_SL';
		$this->name          = _x( 'The user {{has/does not have}} an active license for {{a download}}', 'EDD - Software Licensing', 'uncanny-automator-pro' );
		$this->code          = 'EDD_SL_ACTIVE_LICENSE';
		$this->requires_user = true;
		$this->dynamic_name  = sprintf(
		/* translators: Condition sentence */
			esc_html_x( 'The user {{has/does not have:%1$s}} an active license for {{a download:%2$s}}', 'EDD- Software Licensing', 'uncanny-automator-pro' ),
			'CONDITION',
			'EDD_SL_DOWNLOAD'
		);

	}

	/**
	 * Defines the fields.
	 *
	 * @return array
	 */
	public function fields() {
		$helper = new Edd_Sl_Helpers();

		return array(
			$this->field->select(
				array(
					'option_code'            => 'CONDITION',
					'label'                  => esc_html_x( 'Condition', 'EDD - Software Licensing', 'uncanny-automator-pro' ),
					'show_label_in_sentence' => false,
					'required'               => true,
					'options'                => array(
						array(
							'text'  => esc_html_x( 'has', 'EDD - Software Licensing', 'uncanny-automator-pro' ),
							'value' => 'has',
						),
						array(
							'text'  => esc_html_x( 'does not have', 'EDD - Software Licensing', 'uncanny-automator-pro' ),
							'value' => 'does-not-have',
						),
					),
				)
			),
			$this->field->select(
				array(
					'option_code'            => 'EDD_SL_DOWNLOAD',
					'label'                  => esc_html_x( 'Download', 'EDD - Software Licensing', 'uncanny-automator-pro' ),
					'show_label_in_sentence' => false,
					'required'               => true,
					'options'                => $helper->get_all_downloads( false ),
				)
			),
		);

	}

	/**
	 * Evaluates the condition.
	 *
	 * Has to use the $this->condition_failed( $message ); method if the condition is not met.
	 *
	 * @return void
	 */
	public function evaluate_condition() {
		$download_id = absint( $this->get_parsed_option( 'EDD_SL_DOWNLOAD' ) );
		$condition   = trim( $this->get_parsed_option( 'CONDITION' ) );
		$user_id     = absint( $this->user_id );

		if ( ! function_exists( 'edd_has_user_purchased' ) ) {
			$this->condition_failed( 'The function "edd_has_user_purchased" is not defined' );

			return;
		}

		if ( false === edd_has_user_purchased( $user_id, $download_id ) ) {
			$this->condition_failed( sprintf( 'The user (ID: %1$d) does not have purchased the specified download (ID: %2$d)', $user_id, $download_id ) );

			return;
		}

		$user_active_licenses = edd_software_licensing()->licenses_db->get_licenses(
			array(
				'download_id' => $download_id,
				'user_id'     => $user_id,
				'status'      => 'active',
			)
		);

		if ( 'does-not-have' === $condition && ! empty( $user_active_licenses ) ) {
			$this->condition_failed( sprintf( 'The user (ID: %1$d) has an active license for the specified download (ID: %2$d)', $user_id, $download_id ) );

			return;
		}

		if ( 'has' === $condition && empty( $user_active_licenses ) ) {
			$this->condition_failed( sprintf( 'The user (ID: %1$d) does not have an active license for the specified download (ID: %2$d)', $user_id, $download_id ) );

			return;
		}

	}
}
