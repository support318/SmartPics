<?php

namespace Uncanny_Automator_Pro;

use Uncanny_Automator\Recipe;

/**
 * Class GH_ANON_NOTE_ADDED
 *
 * @package Uncanny_Automator_Pro
 */
class GH_ANON_NOTE_ADDED {

	use Recipe\Triggers;

	/**
	 * Set up Automator trigger constructor.
	 */
	public function __construct() {
		$this->setup_trigger();
		$this->set_helper( new Groundhogg_Pro_Helpers() );
	}

	/**
	 * Define and register the trigger by pushing it into the Automator object
	 */
	public function setup_trigger() {
		$this->set_integration( 'GH' );
		$this->set_trigger_code( 'ANON_GH_CONTACT_NOTE' );
		$this->set_trigger_meta( 'GH_CONTACT' );
		$this->set_trigger_type( 'anonymous' );
		$this->set_is_pro( true );
		$this->set_support_link( Automator()->get_author_support_link( $this->get_trigger_code(), 'integration/groundhogg/' ) );
		/* Translators: Trigger sentence - Groundhogg */
		$this->set_sentence( sprintf( esc_attr_x( 'A note is added to {{a contact:%1$s}}', 'Groundhogg', 'uncanny-automator-pro' ), $this->get_trigger_meta() ) );
		// Non-active state sentence to show - Groundhogg
		$this->set_readable_sentence( esc_attr_x( 'A note is added to {{a contact}}', 'Groundhogg', 'uncanny-automator-pro' ) );
		$this->add_action( array( 'groundhogg/contact/note/added', 'groundhogg/api/note/created' ), 9990, 3 );
		$this->set_options_callback( array( $this, 'load_options' ) );
		$this->register_trigger();
	}

	/**
	 * @return array
	 */
	public function load_options() {

		return Automator()->utilities->keep_order_of_options(
			array(
				'options' => array(
					$this->get_helper()->get_all_gh_contacts(
						array(
							'option_code'     => $this->get_trigger_meta(),
							'is_any'          => true,
							'relevant_tokens' => array(
								'GH_CONTACT_ID'    => __( 'Contact ID', 'uncanny-automator-pro' ),
								'GH_CONTACT_FNAME' => __( 'Contact first name', 'uncanny-automator-pro' ),
								'GH_CONTACT_LNAME' => __( 'Contact last name', 'uncanny-automator-pro' ),
								'GH_CONTACT_EMAIL' => __( 'Contact email', 'uncanny-automator-pro' ),
								'GH_CONTACT_NOTE'  => __( 'Note', 'uncanny-automator-pro' ),
							),
						)
					),
				),
			)
		);

	}

	/**
	 * Validate the trigger.
	 *
	 * @param $args
	 *
	 * @return bool
	 */
	protected function validate_trigger( ...$args ) {
		$args = array_shift( $args );

		if ( empty( $args[0] ) ) {
			return false;
		}

		return true;
	}

	/**
	 * Prepare to run the trigger.
	 *
	 * @param $args
	 *
	 * @return void
	 */
	public function prepare_to_run( $args ) {
		$this->set_conditional_trigger( true );
	}


	/**
	 * Validate if trigger matches the condition.
	 *
	 * @param $args
	 *
	 * @return array
	 */
	protected function validate_conditions( $args ) {
		list( $contact_id, $note, $contact_obj ) = $args[0];
		if ( 'groundhogg/api/note/created' === current_action() ) {
			$contact_obj = $args[0];
			$contact_id  = $contact_obj->data['object_id'];
			// Set the User ID of the contact
			$contact = new \Groundhogg\Contact( $contact_id );
			$user_id = $contact->get_user_id();
			$this->set_user_id( $user_id );
		}

		// Find contact ID
		return $this->find_all( $this->trigger_recipes() )
					->where( array( $this->get_trigger_meta() ) )
					->match( array( $contact_id ) )
					->format( array( 'intval' ) )
					->get();
	}

	/**
	 * @param ...$args
	 *
	 * @return bool
	 */
	public function do_continue_anon_trigger( ...$args ) {

		return true;

	}

}
