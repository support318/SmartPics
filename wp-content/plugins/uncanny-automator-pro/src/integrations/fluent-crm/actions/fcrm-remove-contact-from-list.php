<?php

namespace Uncanny_Automator_Pro;

use Exception;
use FluentCrm\App\Models\Subscriber as FluentCRM_Subscriber;
use Uncanny_Automator\Recipe\Action;

/**
 * Class FCRM_REMOVE_CONTACT_FROM_LIST
 *
 * @pacakge Uncanny_Automator_Pro
 */
class FCRM_REMOVE_CONTACT_FROM_LIST extends Action {

	/**
	 * @return mixed
	 */
	protected function setup_action() {
		$this->set_integration( 'FCRM' );
		$this->set_action_code( 'FCRM_REMOVE_CONTACT_FROM_LIST' );
		$this->set_action_meta( 'FCRM_LISTS' );
		$this->set_is_pro( true );
		$this->set_support_link( Automator()->get_author_support_link( $this->get_action_code(), 'integration/fluentcrm/' ) );
		$this->set_requires_user( false );
		$this->set_sentence(
			sprintf(
			/* translators: tag name */
				esc_attr_x( 'Remove {{a contact:%1$s}} from {{lists:%2$s}}', 'FluentCRM', 'uncanny-automator-pro' ),
				'CONTACT_EMAIL:' . $this->get_action_meta(),
				$this->get_action_meta()
			)
		);
		$this->set_readable_sentence( esc_attr_x( 'Remove {{a contact}} from {{lists}}', 'FluentCRM', 'uncanny-automator-pro' ) );
	}

	/**
	 * @return array[]
	 */
	public function options() {
		$options = Automator()->helpers->recipe->fluent_crm->options->fluent_crm_lists();

		$all_lists = array();
		foreach ( $options['options'] as $key => $option ) {
			$all_lists[] = array(
				'text'  => $option,
				'value' => $key,
			);
		}

		return array(
			Automator()->helpers->recipe->field->text(
				array(
					'option_code'     => 'CONTACT_EMAIL',
					'input_type'      => 'email',
					'label'           => esc_attr_x( 'Contact email', 'FluentCRM', 'uncanny-automator-pro' ),
					'relevant_tokens' => array(),
				),
			),
			array(
				'input_type'               => 'select',
				'option_code'              => $this->get_action_meta(),
				'label'                    => esc_attr_x( 'List(s)', 'FluentCRM', 'uncanny-automator-pro' ),
				'options'                  => $all_lists,
				'relevant_tokens'          => array(),
				'supports_multiple_values' => true,
			),
		);
	}

	/**
	 * @param $user_id
	 * @param $action_data
	 * @param $recipe_id
	 * @param $args
	 * @param $parsed
	 *
	 * @return bool
	 */
	public function process_action( $user_id, $action_data, $recipe_id, $args, $parsed ) {
		$lists_to_be_removed = array_map( 'intval', json_decode( $action_data['meta'][ $this->action_meta ] ) );
		$email_address       = isset( $parsed['CONTACT_EMAIL'] ) ? sanitize_email( $parsed['CONTACT_EMAIL'] ) : '';
		try {
			if ( ! class_exists( 'FluentCrm\App\Models\Subscriber' ) ) {
				throw new Exception( 'FluentCRM is not active.' );
			}
			$subscriber = FluentCRM_Subscriber::where( 'email', $email_address )->first();
			// Contact must exists and must have valid email address.
			$this->validate( $email_address, $subscriber );
			foreach ( $subscriber->lists as $list ) {
				$existing_list_ids[] = (int) $list->id;
			}

			if ( empty( array_intersect( $existing_list_ids, $lists_to_be_removed ) ) ) {
				$this->add_log_error( sprintf( esc_attr_x( 'Contact (%s) is not a member of any selected list(s)', 'FluentCRM', 'uncanny-automator-pro' ), $email_address ) );

				return false;
			}

			$subscriber->detachLists( $lists_to_be_removed );
		} catch ( Exception $e ) {
			$this->add_log_error( $e->getMessage() );

			return false;
		}

		return true;
	}

	/**
	 * Validate the action before it actually tries to execute.
	 *
	 * @param string $email_address
	 * @param array  $subscriber
	 *
	 * @return boolean True if no \Exception occurs.
	 */
	public function validate( $email_address = '', $subscriber = array() ) {

		if ( empty( $email_address ) ) {
			throw new Exception( 'Cannot assign tag(s) to a contact with empty email address.' );
		}

		if ( ! filter_var( $email_address, FILTER_VALIDATE_EMAIL ) ) {
			throw new Exception( sprintf( 'The email address (%s) contains invalid format.', $email_address ) );
		}

		if ( empty( $subscriber ) ) {
			throw new Exception( sprintf( 'The contact (%s) does not exist.', $email_address ) );
		}

		return true;

	}
}
