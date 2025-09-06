<?php

namespace Uncanny_Automator_Pro;

use EasyAffiliate\Models\Transaction;
use Uncanny_Automator\Easy_Affiliate_Helpers;
use Uncanny_Automator\Recipe;

/**
 * Class ESAF_RECORD_SALE
 *
 * @package Uncanny_Automator_Pro
 */
class ESAF_RECORD_SALE {

	use Recipe\Actions;
	use Recipe\Action_Tokens;

	/**
	 * @var Easy_Affiliate_Pro_Helpers
	 */
	public $pro_helpers;

	/**
	 * Set up Automator action constructor.
	 */
	public function __construct() {
		if ( ! class_exists( '\Uncanny_Automator\Easy_Affiliate_Helpers' ) ) {
			return;
		}
		$this->setup_action();
		$this->pro_helpers = new Easy_Affiliate_Pro_Helpers();
		$this->helpers     = new Easy_Affiliate_Helpers();
	}

	/**
	 * Define and register the action by pushing it into the Automator object
	 */
	protected function setup_action() {
		$this->set_integration( 'ESAF' );
		$this->set_action_code( 'RECORD_SALE_CODE' );
		$this->set_action_meta( 'RECORD_SALE_META' );
		$this->set_requires_user( false );
		$this->set_is_pro( true );

		/* translators: Action - Easy Affiliate */
		$this->set_sentence( sprintf( esc_attr__( 'Record a sale for {{an affiliate:%1$s}}', 'uncanny-automator-pro' ), $this->get_action_meta() ) );

		/* translators: Action - Easy Affiliate */
		$this->set_readable_sentence( esc_attr__( 'Record a sale for {{an affiliate}}', 'uncanny-automator-pro' ) );

		$this->set_options_callback( array( $this, 'load_options' ) );

		$this->set_action_tokens(
			array(
				'affiliate_sale_URL' => array(
					'name' => __( 'Affiliate sale URL', 'uncanny-automator-pro' ),
					'type' => 'url',
				),
			),
			$this->get_action_code()
		);

		$this->register_action();
	}

	/**
	 * load_options
	 *
	 * @return array
	 */
	public function load_options() {

		$options = array(
			'options_group' => array(
				$this->get_action_meta() => array(
					$this->helpers->get_all_affiliates( 'AFFILIATE', false ),
					Automator()->helpers->recipe->field->text(
						array(
							'option_code' => '_wafp_transaction_cust_name',
							'label'       => esc_attr__( 'Customer name', 'uncanny-automator-pro' ),
							'placeholder' => esc_attr__( 'Customer name', 'uncanny-automator-pro' ),
							'required'    => false,
						)
					),
					Automator()->helpers->recipe->field->text(
						array(
							'option_code' => '_wafp_transaction_cust_email',
							'input_type'  => 'email',
							'label'       => esc_attr__( 'Customer email', 'uncanny-automator-pro' ),
							'placeholder' => esc_attr__( 'Customer email', 'uncanny-automator-pro' ),
							'required'    => false,
						)
					),
					Automator()->helpers->recipe->field->text(
						array(
							'option_code' => '_wafp_transaction_item_name',
							'label'       => esc_attr__( 'Product name', 'uncanny-automator-pro' ),
							'placeholder' => esc_attr__( 'Product name', 'uncanny-automator-pro' ),
							'required'    => false,
						)
					),
					Automator()->helpers->recipe->field->text(
						array(
							'option_code' => '_wafp_transaction_trans_num',
							'label'       => esc_attr__( 'Order ID', 'uncanny-automator-pro' ),
							'placeholder' => esc_attr__( 'Order ID', 'uncanny-automator-pro' ),
						)
					),
					Automator()->helpers->recipe->field->select(
						array(
							'option_code' => '_wafp_transaction_source',
							'label'       => esc_attr__( 'Transaction source', 'uncanny-automator-pro' ),
							'options'     => $this->pro_helpers->get_transaction_source(),
						)
					),
					Automator()->helpers->recipe->field->float(
						array(
							'option_code' => '_wafp_transaction_sale_amount',
							'label'       => esc_attr__( 'Amount', 'uncanny-automator-pro' ),
							'placeholder' => 0.00,
						)
					),
					Automator()->helpers->recipe->field->float(
						array(
							'option_code' => '_wafp_transaction_refund_amount',
							'label'       => esc_attr__( 'Refund amount', 'uncanny-automator-pro' ),
							'placeholder' => 0.00,
							'default'     => 0.00,
						)
					),
				),
			),
		);

		return Automator()->utilities->keep_order_of_options( $options );
	}

	/**
	 * Process the action.
	 *
	 * @param $user_id
	 * @param $action_data
	 * @param $recipe_id
	 * @param $args
	 * @param $parsed
	 *
	 * @return void.
	 * @throws \Exception
	 */
	protected function process_action( $user_id, $action_data, $recipe_id, $args, $parsed ) {
		$sale                                    = array();
		$errors                                  = array();
		$sale['referrer']                        = isset( $parsed['AFFILIATE'] ) ? sanitize_text_field( $parsed['AFFILIATE'] ) : '';
		$sale['_wafp_transaction_cust_name']     = isset( $parsed['_wafp_transaction_cust_name'] ) ? sanitize_text_field( $parsed['_wafp_transaction_cust_name'] ) : '';
		$sale['_wafp_transaction_cust_email']    = isset( $parsed['_wafp_transaction_cust_email'] ) ? sanitize_text_field( $parsed['_wafp_transaction_cust_email'] ) : '';
		$sale['_wafp_transaction_item_name']     = isset( $parsed['_wafp_transaction_item_name'] ) ? sanitize_text_field( $parsed['_wafp_transaction_item_name'] ) : '';
		$sale['_wafp_transaction_trans_num']     = isset( $parsed['_wafp_transaction_trans_num'] ) ? sanitize_text_field( $parsed['_wafp_transaction_trans_num'] ) : '';
		$sale['_wafp_transaction_source']        = isset( $parsed['_wafp_transaction_source'] ) ? sanitize_text_field( $parsed['_wafp_transaction_source'] ) : 'general';
		$sale['_wafp_transaction_refund_amount'] = isset( $parsed['_wafp_transaction_refund_amount'] ) ? sanitize_text_field( $parsed['_wafp_transaction_refund_amount'] ) : 0.00;
		$sale['_wafp_transaction_sale_amount']   = isset( $parsed['_wafp_transaction_sale_amount'] ) ? sanitize_text_field( $parsed['_wafp_transaction_sale_amount'] ) : 0.00;

		$transaction = new Transaction();
		$transaction->load_from_sanitized_array( $sale );
		$transaction->affiliate_id = $sale['referrer'];
		$transaction->apply_refund( $sale['_wafp_transaction_refund_amount'] );

		$id = $transaction->store();
		if ( is_wp_error( $id ) ) {
			$errors[] = $id->get_error_message();
		}

		if ( ! empty( $errors ) ) {
			$action_data['do-nothing']           = true;
			$action_data['complete_with_errors'] = true;
			$message                             = __( implode( ',', $errors ), 'uncanny-automator-pro' );
			Automator()->complete->action( $user_id, $action_data, $recipe_id, $message );

			return;
		}

		$this->hydrate_tokens(
			array(
				'affiliate_sale_URL' => esc_url( admin_url( "admin.php?page=easy-affiliate-transactions&action=edit&id={$id}" ) ),
			)
		);

		Automator()->complete->action( $user_id, $action_data, $recipe_id );
	}

}
