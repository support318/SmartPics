<?php
namespace Uncanny_Automator_Pro;

use FluentCrm\App\Models\Subscriber as FluentCRM_Subscriber;

/**
 * Class FCRM_CONTACT_TO_LIST
 *
 * @package Uncanny_Automator
 */
class FCRM_CONTACT_TO_LIST {

	use \Uncanny_Automator\Recipe\Actions;

	public function __construct() {

		$this->setup_action();

		$this->register_action();

	}

	/**
	 * Setup Action.
	 *
	 * @return void.
	 */
	protected function setup_action() {

		$this->set_integration( 'FCRM' );

		$this->set_action_code( 'FCRM_CONTACT_TO_LIST' );

		$this->set_action_meta( 'FCRM_CONTACT_TO_LIST_META' );

		$this->set_is_pro( true );

		$this->set_support_link( Automator()->get_author_support_link( $this->get_action_code(), 'integration/fluentcrm/' ) );

		$this->set_requires_user( false );

		$this->set_sentence(
			sprintf(
				/* translators: tag name */
				esc_attr__( 'Add {{a contact:%1$s}} to {{a list:%2$s}}', 'uncanny-automator-pro' ),
				'EMAIL:' . $this->get_action_meta(),
				$this->get_action_meta()
			)
		);

		$this->set_readable_sentence( esc_attr__( 'Add {{a contact}} to {{a list}}', 'uncanny-automator-pro' ) );

		$this->set_options_callback( array( $this, 'load_options' ) );

	}

	/**
	 * Method load_options
	 *
	 * @return void
	 */
	public function load_options() {

		$options[] = Automator()->helpers->recipe->fluent_crm->options->fluent_crm_lists(
			esc_attr_x( 'List(s)', 'Fluent Forms', 'uncanny-automator-pro' ),
			$this->get_action_meta(),
			array(
				'supports_multiple_values' => true,
				'is_any'                   => false,
			)
		);

		$options[] = array(
			'option_code' => 'EMAIL',
			'input_type'  => 'text',
			'label'       => esc_attr__( 'Email address', 'uncanny-automator-pro' ),
			'placeholder' => '',
			'description' => '',
			'required'    => true,
			'tokens'      => true,
			'default'     => '',
		);

		return Automator()->utilities->keep_order_of_options(
			array(
				'options_group' => array(
					$this->get_action_meta() => $options,
				),
			)
		);

	}

	/**
	 * Method process_action.
	 *
	 * @param $user_id
	 * @param $action_data
	 * @param $recipe_id
	 * @param $args
	 * @param $parsed
	 *
	 * @return void.
	 */
	public function process_action( $user_id, $action_data, $recipe_id, $args, $parsed ) {

		$lists = array_map( 'intval', json_decode( $action_data['meta'][ $this->action_meta ] ) );

		$email_address = isset( $parsed['EMAIL'] ) ? sanitize_text_field( $parsed['EMAIL'] ) : '';

		try {

			if ( ! class_exists( 'FluentCrm\App\Models\Subscriber' ) ) {
				throw new \Exception( 'FluentCRM is not active.' );
			}

			$subscriber = FluentCRM_Subscriber::where( 'email', $email_address )->first();

			// Contact must exists and must have valid email address.
			$this->validate( $email_address, $subscriber );

			$existing_list_ids = array();

			foreach ( $subscriber->lists as $list ) {
				if ( in_array( $list->id, $lists, true ) ) {
					$existing_list_ids[] = $list->title;
				}
			}

			$subscriber->attachLists( $lists );

			Automator()->complete->action( $user_id, $action_data, $recipe_id );

		} catch ( \Exception $e ) {

			$action_data['complete_with_errors'] = true;

			Automator()->complete->action( $user_id, $action_data, $recipe_id, $e->getMessage() );

		}

	}

	/**
	 * Validate the action before it actually tries to execute.
	 *
	 * @param string $email_address
	 * @param array $subscriber
	 *
	 * @return boolean True if no \Exception occurs.
	 */
	public function validate( $email_address = '', $subscriber = array() ) {

		if ( empty( $email_address ) ) {
			throw new \Exception( 'Cannot assign tag(s) to a contact with empty email address.' );
		}

		if ( ! filter_var( $email_address, FILTER_VALIDATE_EMAIL ) ) {
			throw new \Exception( sprintf( 'The email address (%s) contains invalid format.', $email_address ) );
		}

		if ( empty( $subscriber ) ) {
			throw new \Exception( sprintf( 'The contact (%s) does not exist.', $email_address ) );
		}

		return true;

	}


}
