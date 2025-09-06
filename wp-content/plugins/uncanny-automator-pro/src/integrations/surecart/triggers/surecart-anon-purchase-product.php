<?php

namespace Uncanny_Automator_Pro;

use Uncanny_Automator\Recipe;

/**
 * Class SURECART_ANON_PURCHASE_PRODUCT
 *
 * @package Uncanny_Automator
 */
class SURECART_ANON_PURCHASE_PRODUCT {

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
		$this->register_trigger();

	}

	/**
	 * Define and register the trigger by pushing it into the Automator object
	 */
	public function setup_trigger() {

		$this->set_integration( 'SURECART' );
		$this->set_trigger_code( 'ANON_PURCHASE_PRODUCT' );
		$this->set_trigger_meta( 'PRODUCT' );
		$this->set_support_link( $this->helpers->support_link( $this->trigger_code ) );
		$this->set_trigger_type( 'anonymous' );
		$this->set_is_login_required( false );
		$this->set_is_pro( true );

		/* Translators: Product name */
		$this->set_sentence( sprintf( 'A guest purchases {{a product:%1$s}}', $this->get_trigger_meta() ) );

		$this->set_readable_sentence( 'A guest purchases {{a product}}' );

		$this->add_action( 'surecart/purchase_created' );

		$this->set_action_args_count( 2 );

		$this->set_options_callback( array( $this, 'load_options' ) );

		if ( method_exists( $this, 'set_tokens' ) ) {
			$this->set_tokens(
				$this->surecart_tokens->common_tokens() +
				$this->surecart_tokens->product_tokens() +
				$this->surecart_tokens->shipping_tokens() +
				$this->surecart_tokens->billing_tokens()
			);
		}

	}

	/**
	 * Method load_options
	 *
	 * @return void
	 */
	public function load_options() {
		$options[] = $this->helpers->get_products_dropdown();

		return array( 'options' => $options );

	}

	/**
	 *  Validation function when the trigger action is hit
	 *
	 * @param $data
	 */
	public function validate_trigger( ...$args ) {

		return true;
	}

	/**
	 * Method prepare_to_run
	 *
	 * @param $data
	 */
	public function prepare_to_run( $data ) {

		$this->set_conditional_trigger( true );

	}

	/**
	 * Check product ID against the trigger meta
	 *
	 * @param $args
	 */
	public function validate_conditions( ...$args ) {

		list( $purchase ) = $args[0];

		$recipes = $this->find_all( $this->trigger_recipes() )
						->where( array( $this->get_trigger_meta() ) )
						->match( array( $purchase->product ) )
						->format( array( 'trim' ) )
						->get();

		return $recipes;
	}

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

		return $this->surecart_tokens->hydrate_product_tokens( $parsed, $args, $trigger );

	}

	/**
	 * Method do_continue_anon_trigger
	 *
	 * @param mixed $args
	 *
	 * @return void
	 */
	public function do_continue_anon_trigger( ...$args ) {

		return true;

	}
}
