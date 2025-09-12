<?php
namespace Uncanny_Automator_Pro;

use Exception;

/**
 * Class EDD_USER_HAS_PURCHASED_DOWNLOAD
 *
 * @since 5.2
 *
 * @package Uncanny_Automator_Pro
 */
class EDD_USER_HAS_PURCHASED_DOWNLOAD extends \Uncanny_Automator_Pro\Action_Condition {

	/**
	 * Defines the condition.
	 *
	 * @return void
	 */
	public function define_condition() {

		$this->integration   = 'EDD';
		$this->name          = _x( 'The user {{has/has not}} purchased {{a download}}', 'EDD', 'uncanny-automator-pro' );
		$this->code          = 'EDD_ACTIVE_SUBSCRIPTION';
		$this->requires_user = true;
		$this->dynamic_name  = sprintf(
			/* translators: Condition sentence */
			esc_html_x( 'The user {{has/has not:%1$s}} purchased {{a download:%2$s}}', 'EDD', 'uncanny-automator-pro' ),
			'CONDITION',
			'EDD_DOWNLOAD'
		);

	}

	/**
	 * Defines the fields.
	 *
	 * @return array
	 */
	public function fields() {

		return array(
			$this->get_condition_fields(),
			$this->get_download_fields(),
		);

	}

	/**
	 * Retrieves the conditions options.
	 *
	 * @return mixed
	 */
	private function get_condition_fields() {

		$options = array(
			array(
				'text'  => esc_html_x( 'has', 'EDD', 'uncanny-automator-pro' ),
				'value' => 'has',
			),
			array(
				'text'  => esc_html_x( 'has not', 'EDD', 'uncanny-automator-pro' ),
				'value' => 'has-not',
			),
		);

		return $this->field->select(
			array(
				'option_code'            => 'CONDITION',
				'label'                  => esc_html_x( 'Condition', 'EDD', 'uncanny-automator-pro' ),
				'show_label_in_sentence' => false,
				'required'               => true,
				'options'                => $options,
			)
		);

	}

	/**
	 * Retrieves all EDD Downloads.
	 *
	 * @return mixed
	 */
	private function get_download_fields() {

		$options = Automator()->helpers->recipe->options->edd->all_edd_downloads( '', 'EDD_DOWNLOAD', false, false, false );

		$downloads = array();

		$options_downloads = isset( $options['options'] ) ? (array) $options['options'] : array();

		foreach ( $options_downloads as $id => $label ) {
			$downloads[] = array(
				'text'  => esc_attr( $label ),
				'value' => absint( $id ),
			);
		}

		return $this->field->select(
			array(
				'option_code'            => 'EDD_DOWNLOAD',
				'label'                  => esc_html_x( 'Download', 'EDD', 'uncanny-automator-pro' ),
				'show_label_in_sentence' => false,
				'required'               => true,
				'options'                => $downloads,
			)
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

		$download_id = absint( $this->get_parsed_option( 'EDD_DOWNLOAD' ) );
		$condition   = trim( $this->get_parsed_option( 'CONDITION' ) );
		// @todo: Introduce a getter method in the parent class so the IDE does not complain the that property $user_id is not defined.
		$user_id = absint( $this->user_id );

		if ( ! function_exists( 'edd_has_user_purchased' ) ) {
			$this->condition_failed( 'The function "edd_has_user_purchased" is not defined' );
			return;
		}

		if ( 'has-not' === $condition && true === edd_has_user_purchased( $user_id, $download_id ) ) {
			$this->condition_failed( sprintf( 'The user (ID: %1$d) has purchased the specified download (ID: %2$d)', $user_id, $download_id ) );
			return;
		}

		if ( 'has' === $condition && false === edd_has_user_purchased( $user_id, $download_id ) ) {
			$this->condition_failed( sprintf( 'The user (ID: %1$d) has not purchased the specified download (ID: %2$d)', $user_id, $download_id ) );
			return;
		}

	}

	/**
	 * Check if EDD dependencies are active
	 *
	 * @return bool
	 */
	protected function is_dependency_active() {
		return function_exists( 'EDD' );
	}
}
