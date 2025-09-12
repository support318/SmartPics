<?php

namespace Uncanny_Automator_Pro;

/**
 * Class FLSUPPORT_TICKETPRODUCTREPLIEDAGENT
 *
 * @package Uncanny_Automator_Pro
 */
class FLSUPPORT_TICKETPRODUCTREPLIEDAGENT {

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
		$this->trigger_code = 'FLSTTICKETREPLIEDPRODUCTAGENT';
		$this->trigger_meta = 'FLSTTICKETREPLIEDPRODUCTAGENT_META';
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
			'sentence'            => sprintf( esc_attr__( 'A ticket for {{a product:%1$s}} is replied to by an agent', 'uncanny-automator-pro' ), $this->trigger_meta ),
			/* translators: Logged-in trigger - Fluent Support */
			'select_option_name'  => esc_attr__( 'A ticket for {{a product}} is replied to by an agent', 'uncanny-automator-pro' ),
			'action'              => 'fluent_support/response_added_by_agent',
			'priority'            => 20,
			'accepted_args'       => 3,
			'validation_function' => array( $this, 'fsupport_ticket_response_agent' ),
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
				),
			)
		);
	}

	/**
	 * Validation function when the trigger action is hit
	 *
	 * @param $response
	 * @param $ticket
	 * @param $person
	 */
	public function fsupport_ticket_response_agent( $response, $ticket, $person ) {

		$user_id = get_current_user_id();

		// Logged in users only.
		if ( ! $user_id ) {
			return;
		}

		if ( ! is_object( $person ) || ! is_object( $ticket ) || ! is_object( $response ) ) {
			return;
		}

		$matched_recipe_ids = Automator()->helpers->recipe->fluent_support->pro->get_matched_product_persontype_recipes( 'agent', $ticket, $person, $user_id, $this->trigger_code, $this->trigger_meta );

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

		$insert_trigger_meta_dataset[] = array(
			'key'   => 'FLSUPPORTRESPONSEID',
			'value' => $response->id,
		);

		Automator()->helpers->recipe->fluent_support->pro->maybe_process_matched_recipes( $matched_recipe_ids, $base_args, $insert_trigger_meta_dataset );
	}
}
