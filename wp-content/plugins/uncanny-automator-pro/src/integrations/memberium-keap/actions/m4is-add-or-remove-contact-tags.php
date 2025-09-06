<?php

namespace Uncanny_Automator_Pro\Integrations\M4IS;

/**
 * Class M4IS_ADD_OR_REMOVE_CONTACT_TAGS
 *
 * @package Uncanny_Automator_Pro
 */
class M4IS_ADD_OR_REMOVE_CONTACT_TAGS extends \Uncanny_Automator\Recipe\Action {

	public $prefix = 'M4IS_ADD_OR_REMOVE_CONTACT_TAGS';

	/**
	 * Define and register the action by pushing it into the Automator object.
	 *
	 * @return void
	 */
	public function setup_action() {

		$this->helpers = array_shift( $this->dependencies );

		$this->set_integration( 'M4IS' );
		$this->set_action_code( $this->prefix . '_CODE' );
		$this->set_action_meta( $this->prefix . '_META' );
		$this->set_requires_user( false );
		$this->set_is_pro( true );
		$this->set_support_link( Automator()->get_author_support_link( $this->action_code, 'knowledge-base/memberium-keap/' ) );
		$this->set_sentence(
			sprintf(
				/* translators: %1$s Tag, %2$s Contact*/
				esc_attr_x( 'Add or remove {{a contact:%2$s}} {{tag(s):%1$s}} ', 'M4IS - update contact tags action', 'uncanny-automator-pro' ),
				$this->prefix . '_EMAIL:' . $this->get_action_meta(),
				$this->get_action_meta()
			)
		);
		$this->set_readable_sentence( esc_attr_x( 'Add or remove {{a contact}} {{tag(s)}}', 'M4IS - update contact tags action', 'uncanny-automator-pro' ) );
		$this->set_action_tokens( $this->helpers->get_contact_tag_action_tokens_config(), $this->action_code );
	}

	/**
	 * Define options.
	 *
	 * @return array
	 */
	public function options() {
		return $this->helpers->get_tag_contact_action_options( $this->prefix, true );
	}

	/**
	 * Process the action.
	 *
	 * @param int $user_id
	 * @param array $action_data
	 * @param int $recipe_id
	 * @param array $args
	 * @param array $parsed
	 *
	 * @return bool
	 */
	protected function process_action( $user_id, $action_data, $recipe_id, $args, $parsed ) {

		$tags     = $this->helpers->get_tag_ids_from_parsed( $parsed, $this->get_action_meta() );
		$email    = $this->helpers->get_email_from_parsed( $parsed, $this->prefix . '_EMAIL' );
		$response = $this->helpers->update_contact_tags( $email, $tags );

		if ( is_wp_error( $response ) ) {
			throw new \Exception(
				sprintf(
					/* translators: %s - error message */
					esc_attr_x( "Error updating contact's tags. %s", 'M4IS - update contact tags action', 'uncanny-automator-pro' ),
					$response->get_error_message()
				)
			);
		}

		$do_nothing = 0;
		$logs       = array();
		foreach ( $response['results'] as $action => $messages ) {
			if ( ! empty( $messages['do_nothing'] ) ) {
				$do_nothing ++;
			}
			if ( ! empty( $messages['error'] ) ) {
				$logs[] = $messages['error'];
			}
		}

		// Log any errors.
		if ( ! empty( $logs ) ) {
			$log = implode( ', ', $logs );
			$this->add_log_error( $log );
		}

		// Hydrate tokens.
		$this->hydrate_tokens( $this->helpers->hydrate_contact_action_tokens( $response['contact_id'], $tags, true ) );

		// Return null if all actions are do nothing.
		if ( $do_nothing === count( $response['results'] ) ) {
			return null;
		}

		// Return true if no errors.
		return empty( $logs ) ? true : false;
	}

}
