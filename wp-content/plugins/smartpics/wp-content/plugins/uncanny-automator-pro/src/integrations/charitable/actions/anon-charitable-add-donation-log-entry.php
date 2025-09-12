<?php

namespace Uncanny_Automator_Pro\Integrations\Charitable;

/**
 * Class ANON_CHARITABLE_ADD_DONATION_LOG_ENTRY
 */
class ANON_CHARITABLE_ADD_DONATION_LOG_ENTRY extends \Uncanny_Automator\Recipe\Action {

	/**
	 * Charitable_Integration Instance.
	 *
	 * @var object
	 */
	private $charitable;

	/**
	 * Entry Content Meta Key.
	 *
	 * @var string
	 */
	const ENTRY_META = 'CHARITABLE_DONATION_LOG_ENTRY_CONTENT';

	/**
	 * Register action.
	 *
	 * @return void
	 */
	protected function setup_action() {

		$this->charitable = array_shift( $this->dependencies );

		// Define the Actions's info
		$this->set_integration( 'CHARITABLE' );
		$this->set_action_code( 'CHARITABLE_ADD_DONATION_LOG_ENTRY' );
		$this->set_action_meta( 'CHARITABLE_DONATION_ID' );
		$this->set_requires_user( false );
		$this->set_is_pro( true );

		// Active State Sentence.
		$this->set_sentence(
			sprintf(
				esc_attr_x(
					'Add an entry in {{a donation:%1$s}} log',
					'Charitable',
					'uncanny-automator-pro'
				),
				$this->get_action_meta()
			)
		);

		// Non Active State Sentence.
		$this->set_readable_sentence(
			esc_attr_x(
				'Add an entry in {{a donation}} log',
				'Charitable',
				'uncanny-automator-pro'
			)
		);

	}

	/**
	 * Define the Action's options.
	 *
	 * @return array
	 */
	public function options() {
		return array(
			Automator()->helpers->recipe->field->select(
				array(
					'option_code'           => $this->get_action_meta(),
					'label'                 => _x( 'Donation ID', 'Charitable', 'uncanny-automator-pro' ),
					'supports_custom_value' => true,
					'supports_tokens'       => true,
					'required'              => true,
					/*
					'relevant_tokens'       => array(
						'DONATION_ID' => _x( 'Donation ID', 'Charitable', 'uncanny-automator-pro' ),
					),
					*/
					'options'               => $this->charitable->helpers()->get_donation_options(),
				)
			),
			Automator()->helpers->recipe->field->text(
				array(
					'option_code' => self::ENTRY_META,
					'label'       => _x( 'Entry', 'Charitable', 'uncanny-automator-pro' ),
					'placeholder' => _x( 'Entry content', 'Charitable', 'uncanny-automator-pro' ),
					'input_type'  => 'textarea',
				)
			),
		);
	}

	/**
	 * Process the action.
	 *
	 * @param int $user_id
	 * @param array $action_data
	 * @param int $recipe_id
	 * @param array $args
	 * @param mixed $parsed
	 *
	 * @return bool
	 */
	protected function process_action( $user_id, $action_data, $recipe_id, $args, $parsed ) {

		$action_meta = $action_data['meta'];

		// Get the field values
		$donation_id = (int) sanitize_text_field( Automator()->parse->text( $action_meta[ $this->get_action_meta() ], $recipe_id, $user_id, $args ) );
		$entry       = wp_filter_post_kses( stripslashes( ( Automator()->parse->text( $action_meta[ self::ENTRY_META ], $recipe_id, $user_id, $args ) ) ) );

		// Handle errors.
		if ( empty( $donation_id ) ) {
			$this->add_log_error( esc_attr_x( 'Invalid donation ID', 'Charitable', 'uncanny-automator-pro' ) );

			return false;
		}

		if ( empty( $entry ) ) {
			$this->add_log_error( esc_attr_x( 'No entry content provided', 'Charitable', 'uncanny-automator-pro' ) );

			return false;
		}

		// Get the donation
		$donation = $this->charitable->helpers()->get_donation( $donation_id );
		if ( ! $donation ) {
			// Check if it's a recurring donation.
			$donation = $this->charitable->helpers()->get_recurring_donation( $donation_id );
		}

		// Handle errors.
		if ( ! $donation ) {
			$this->add_log_error( esc_attr_x( 'Donation not found', 'Charitable', 'uncanny-automator-pro' ) );

			return false;
		}

		// Populate the custom token value
		$this->hydrate_tokens(
			array(
				self::ENTRY_META => $entry,
			)
		);

		// Add the note
		$result = $donation->update_donation_log( $entry );

		// Handle errors : On Success will return Meta ID if first log or true, false if failed.
		if ( empty( $result ) ) {
			$this->add_log_error( esc_attr_x( 'Failed to add entry', 'Charitable', 'uncanny-automator-pro' ) );

			return false;
		}

		// Everything is OK, return true.
		return true;
	}

}
