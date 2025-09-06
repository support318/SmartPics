<?php

namespace Uncanny_Automator_Pro;

use Uncanny_Automator\Recipe;

/**
 * Class SURECART_ANON_ORDER_CONFIRMED
 *
 * @package Uncanny_Automator
 */
class SURECART_ANON_ORDER_CONFIRMED {

	use Recipe\Triggers;

	/**
	 * @var SureCart_Pro_Helpers
	 */
	public $helpers;
	/**
	 * @var SureCart_Pro_Tokens
	 */
	public $surecart_tokens;

	/**
	 * Set up Automator trigger constructor.
	 */
	public function __construct() {

		$this->helpers         = new SureCart_Pro_Helpers();
		$this->surecart_tokens = new SureCart_Pro_Tokens();
		$this->setup_trigger();
		//$this->register_trigger();

	}

	/**
	 * Define and register the trigger by pushing it into the Automator object
	 */
	public function setup_trigger() {

		$this->set_integration( 'SURECART' );
		$this->set_trigger_code( 'ANON_ORDER_CONFIRMED' );
		$this->set_support_link( $this->helpers->support_link( $this->trigger_code ) );
		$this->set_trigger_type( 'anonymous' );
		$this->set_is_login_required( false );

		/* Translators: Product name */
		$this->set_sentence( "A guest's order status is changed to confirmed" );

		$this->set_readable_sentence( "A guest's order status is changed to confirmed" );

		$this->add_action( 'surecart/checkout_confirmed' );

		$this->set_action_args_count( 2 );

		if ( method_exists( $this, 'set_tokens' ) ) {
			$this->set_tokens(
				$this->surecart_tokens->common_tokens() +
				$this->surecart_tokens->order_tokens()
			);
		}

	}

	/**
	 *  Validation function when the trigger action is hit
	 *
	 * @param $data
	 */
	public function validate_trigger( ...$args ) {

		list( $checkout ) = $args[0];

		if ( 'paid' === $checkout->status ) {
			return true;
		}

		return false;
	}

	/**
	 * Method prepare_to_run
	 *
	 * @param $data
	 */
	public function prepare_to_run( $data ) {}


	/**
	 * Method parse_additional_tokens.
	 *
	 * @param $parsed
	 * @param $args
	 * @param $trigger
	 *
	 * @return array
	 */
	public function parse_additional_tokens( $parsed, $args, $trigger ) {

		return $this->surecart_tokens->hydrate_order_tokens( $parsed, $args, $trigger );

	}

	/**
	 * Method do_continue_anon_trigger
	 *
	 * @param  mixed $args
	 * @return void
	 */
	public function do_continue_anon_trigger( ...$args ) {

		return true;

	}

}
