<?php

namespace Uncanny_Automator_Pro;

/**
 * Class FLSUPPORT_TICKETCLOSEDPRODUCTPERSON
 *
 * @package Uncanny_Automator_Pro
 */
class FLSUPPORT_TICKETCLOSEDPRODUCTPERSON {

	/**
	 * Integration code
	 *
	 * @var string
	 */
	public static $integration = 'FLSUPPORT';

	/**
	 * @var string
	 */
	private $trigger_code;
	/**
	 * @var string
	 */
	private $trigger_meta;

	/**
	 * Set up Automator trigger constructor.
	 */
	public function __construct() {
		$this->trigger_code = 'FLSTTICKETCLOSEDPRODUCTPERSON';
		$this->trigger_meta = 'FLSTTICKETCLOSEDPRODUCTPERSON_META';
		$this->define_trigger();
	}

	/**
	 * Define and register the trigger by pushing it into the Automator object
	 */
	public function define_trigger() {

		$trigger = array(
			'author'              => Automator()->get_author_name( $this->trigger_code ),
			'support_link'        => Automator()->get_author_support_link( $this->trigger_code, 'integration/fluent-support/' ),
			'integration'         => self::$integration,
			'is_pro'              => true,
			'code'                => $this->trigger_code,
			/* translators: Logged-in trigger - Fluent Support */
			'sentence'            => sprintf( esc_attr__( 'A ticket for {{a product:%1$s}} is closed by {{a customer or an agent:%2$s}}', 'uncanny-automator-pro' ), $this->trigger_meta, 'flsupport_person_type' ),
			/* translators: Logged-in trigger - Fluent Support */
			'select_option_name'  => esc_attr__( 'A ticket for  {{a product}} is closed by {{a customer or an agent}}', 'uncanny-automator-pro' ),
			'action'              => 'fluent_support/ticket_closed',
			'priority'            => 20,
			'accepted_args'       => 2,
			'validation_function' => array( $this, 'fsupport_ticket_product_closed_person' ),
			'options_callback'    => array( $this, 'load_options' ),
		);

		Automator()->register->trigger( $trigger );
	}

	/**
	 * @return array[]
	 */
	public function load_options() {
		return Automator()->utilities->keep_order_of_options(
			array(
				'options' => array(
					Automator()->helpers->recipe->fluent_support->pro->all_products( null, $this->trigger_meta, array( 'uo_include_any' => true ) ),
					Automator()->helpers->recipe->fluent_support->pro->all_person_types( null, 'flsupport_person_type', array() ),
				),
			)
		);
	}

	/**
	 * Validation function when the trigger action is hit
	 *
	 * @param $ticket
	 * @param $person
	 */
	public function fsupport_ticket_product_closed_person( $ticket, $person ) {

		$user_id = get_current_user_id();

		// Logged in users only.
		if ( ! $user_id ) {
			return;
		}

		if ( ! is_object( $person ) || ! is_object( $ticket ) ) {
			return;
		}

		$matched_recipe_ids = Automator()->helpers->recipe->fluent_support->pro->get_matched_person_product_recipes( $ticket, $person, $user_id, $this->trigger_code, $this->trigger_meta );

		if ( empty( $matched_recipe_ids ) ) {
			return;
		}

		$base_args = array(
			'code'           => $this->trigger_code,
			'meta'           => $this->trigger_meta,
			'user_id'        => $user_id,
			'ignore_post_id' => true,
			'is_signed_in'   => true,
		);

		$insert_trigger_meta_dataset   = array();
		$insert_trigger_meta_dataset[] = array(
			'key'   => 'FLSUPPORTTICKETID',
			'value' => $ticket->id,
		);

		$insert_trigger_meta_dataset[] = array(
			'key'   => 'FLSUPPORTPERSONID',
			'value' => $person->id,
		);

		Automator()->helpers->recipe->fluent_support->pro->maybe_process_matched_recipes( $matched_recipe_ids, $base_args, $insert_trigger_meta_dataset );
	}
}
