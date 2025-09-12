<?php

namespace Uncanny_Automator_Pro;

use Uncanny_Automator\Recipe;

class ANON_WC_VARINVENTORYCHANGED {

	use Recipe\Triggers;

	/**
	 * Trigger code.
	 *
	 * @var string
	 */
	const TRIGGER_CODE = 'ANON_WC_VARINVENTORYCHANGED';

	/**
	 * Tokens var
	 *
	 * @var Wc_Pro_Tokens|null
	 */
	protected $wc_tokens = null;

	/**
	 * Trigger meta.
	 *
	 * @var string
	 */
	const TRIGGER_META = 'ANON_WC_VARINVENTORYCHANGED_META';

	/**
	 * Construct method
	 */
	public function __construct() {

		if ( class_exists( '\Uncanny_Automator_Pro\Wc_Pro_Tokens' ) && class_exists( '\Uncanny_Automator_Pro\Woocommerce_Pro_Helpers' ) ) {

			$this->wc_tokens = new \Uncanny_Automator_Pro\Wc_Pro_Tokens( false );

			$this->setup_trigger();

		}

	}

	/**
	 * Define and register the trigger by pushing it into the Automator object
	 */
	public function setup_trigger() {

		$this->set_integration( 'WC' );

		$this->set_trigger_code( self::TRIGGER_CODE );

		$this->set_trigger_meta( self::TRIGGER_META );

		$this->set_is_login_required( false );

		$this->set_trigger_type( 'anonymous' );

		$this->set_is_pro( true );

		/* Translators: Trigger sentence */
		$this->set_sentence( sprintf( esc_html__( "{{A product variation's:%1\$s}} inventory status is set to {{a specific status:%2\$s}}", 'uncanny-automator-pro' ), $this->get_trigger_meta(), $this->get_trigger_meta() . '_STOCKSTATUS' ) );

		$this->set_readable_sentence(
			esc_html__( "{{A product variation's}} inventory status is set to {{a specific status}}", 'uncanny-automator-pro' )
		);

		$this->add_action( 'woocommerce_variation_set_stock_status' );

		$this->set_action_args_count( 3 );

		if ( null !== $this->wc_tokens ) {

			$this->set_tokens( ( new Wc_Pro_Tokens( false ) )->add_variable_product_tokens() );

		}

		$this->set_options_callback( array( $this, 'load_options' ) );

		$this->register_trigger();

	}

	/**
	 *
	 * Load options method
	 *
	 * @return array
	 */
	public function load_options() {

		return Automator()->utilities->keep_order_of_options(
			array(
				'options_group' => array(
					$this->get_trigger_meta() => array(
						Automator()->helpers->recipe->woocommerce->options->pro->all_wc_variable_products(
							__( 'Product', 'uncanny-automator-pro' ),
							$this->get_trigger_meta(),
							array(
								'token'           => true,
								'is_ajax'         => true,
								'target_field'    => 'WC_WOOVARIPRODUCT',
								'endpoint'        => 'select_variations_from_WOOSELECTVARIATION_with_any_option',
								'relevant_tokens' => array(),
							)
						),
						Automator()->helpers->recipe->field->select_field(
							'WC_WOOVARIPRODUCT',
							__( 'Variation', 'uncanny-automator-pro' ),
							array(),
							null,
							false,
							'',
							array(),
							array()
						),
					),
				),
				'options'       => array(
					Automator()->helpers->recipe->woocommerce->options->pro->wc_stock_statuses( esc_attr__( 'Status', 'uncanny-automator-pro' ), $this->get_trigger_meta() . '_STOCKSTATUS', true ),
				),
			)
		);

	}

	/**
	 *
	 * Validate trigger method
	 *
	 * @param ...$args
	 *
	 * @return bool
	 */
	public function validate_trigger( ...$args ) {

		list( $product_var_id, $stock_status, $product ) = $args[0];

		if ( 'variation' === $product->get_type() && ! empty( $product_var_id ) && ! empty( $stock_status ) ) {
			return true;
		}

		return false;

	}

	/**
	 *
	 * Prepare to run method
	 *
	 * @param $data
	 *
	 * @return void
	 */
	public function prepare_to_run( $data ) {

		$this->set_conditional_trigger( true );

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

		return $this->wc_tokens->var_inventory_change_tokens_hydrate_tokens( $parsed, $args, $trigger );

	}

	/**
	 *
	 * Check email subject against the trigger meta
	 *
	 * @param $args
	 */
	public function validate_conditions( ...$args ) {

		list( $product_var_id, $stock_status, $var_product ) = $args[0];

		$this->actual_where_values = array(); // Fix for when not using the latest Trigger_Recipe_Filters version. Newer integration can omit this line.
		$parent_product_id         = $var_product->get_parent_id();

		// Find the matching recipe.
		return $this->find_all( $this->trigger_recipes() )
					->where(
						array(
							$this->get_trigger_meta(),
							'WC_WOOVARIPRODUCT',
							$this->get_trigger_meta() . '_STOCKSTATUS',
						)
					)
					->match( array( $parent_product_id, $product_var_id, $stock_status ) )
					->format( array( 'intval', 'intval', 'trim' ) )
					->get();

	}

	/**
	 *
	 * Continue to execute anonymous method
	 *
	 * @param ...$args
	 *
	 * @return bool
	 */
	public function do_continue_anon_trigger( ...$args ) {
		return true;
	}

}
