<?php

namespace Uncanny_Automator_Pro;

use InvalidArgumentException;
use LogicException;
use Uncanny_Automator_Pro\Integration\Woocommerce\Utilities\Coupon_Generate_Field_Options;
use Uncanny_Automator_Pro\Integration\Woocommerce\Utilities\Woo_Coupon_Generator;

/**
 * Class WC_ADD_A_NOTE_TO_ORDER
 *
 * @package Uncanny_Automator_Pro
 */
class WC_GENERATE_COUPON extends \Uncanny_Automator\Recipe\Action {

	/**
	 * @var Woo_Coupon_Generator
	 */
	protected $coupon_generator = null;

	/**
	 * @return mixed
	 */
	protected function setup_action() {

		$this->set_integration( 'WC' );
		$this->set_action_code( 'WC_GENERATE_COUPON_CODE' );
		$this->set_action_meta( 'WC_GENERATE_COUPON_META' );
		$this->set_is_pro( true );
		$this->set_requires_user( false );
		$this->set_sentence( sprintf( _x( 'Generate {{a coupon code:%1$s}}', 'Woo', 'uncanny-automator-pro' ), $this->get_action_meta() ) );
		$this->set_readable_sentence( _x( 'Generate {{a coupon code}}', 'Woo', 'uncanny-automator-pro' ) );
		$this->set_action_tokens( $this->get_action_tokens_config(), $this->get_action_code() );
		$this->coupon_generator = new Woo_Coupon_Generator();

	}

	/**
	 * @return array
	 */
	public function options() {
		return Coupon_Generate_Field_Options::get_fields( $this->get_action_meta() );
	}

	/**
	 * @return array{COUPON: array{name: string}}
	 */
	private function get_action_tokens_config() {
		return array(
			'COUPON_TITLE' => array(
				'name' => _x( 'Generated coupon', 'Woo', 'uncanny-automator-pro' ),
			),
			'COUPON_ID'    => array(
				'name' => _x( 'Generated coupon ID', 'Woo', 'uncanny-automator-pro' ),
			),
			'COUPON_URL'   => array(
				'name' => _x( 'Generated coupon edit URL', 'Woo', 'uncanny-automator-pro' ),
			),
		);
	}

	/**
	 * @param int   $user_id
	 * @param array $action_data
	 * @param int   $recipe_id
	 * @param array $args
	 * @param       $parsed
	 *
	 * @throws Exception If there are any generic errors.
	 * @throws InvalidArgumentException If arguments are invalid.
	 * @throws LogicException If there are any logic errors.
	 */
	protected function process_action( $user_id, $action_data, $recipe_id, $args, $parsed ) {

		$coupon_code            = $parsed['COUPON_CODE'] ?? null;
		$description            = $parsed[ $this->get_action_meta() ] ?? null;
		$discount_type          = $parsed['DISCOUNT_TYPE'] ?? null;
		$coupon_amount          = $parsed['COUPON_AMOUNT'] ?? null;
		$allow_free_shipping    = $parsed['ALLOW_FREE_SHIPPING'] ?? null;
		$coupon_expiry_date     = $parsed['COUPON_EXPIRY_DATE'] ?? null;
		$minimum_spend          = $parsed['MINIMUM_SPEND'] ?? null;
		$maximum_spend          = $parsed['MAXIMUM_SPEND'] ?? null;
		$is_individual_use      = $parsed['IS_INDIVIDUAL_USE'] ?? null;
		$exclude_sale_items     = $parsed['EXCLUDE_SALE_ITEMS'] ?? null;
		$products               = $parsed['PRODUCTS'] ?? null;
		$exclude_products       = $parsed['EXCLUDE_PRODUCTS'] ?? null;
		$product_categories     = $parsed['PRODUCT_CATEGORIES'] ?? null;
		$exclude_categories     = $parsed['EXCLUDE_CATEGORIES'] ?? null;
		$allowed_emails         = $parsed['ALLOWED_EMAILS'] ?? null;
		$usage_limit_per_coupon = $parsed['USAGE_LIMIT_PER_COUPON'] ?? null;
		$limit_usage_per_item   = $parsed['LIMIT_USAGE_PER_ITEM'] ?? null;
		$limit_usage_per_user   = $parsed['LIMIT_USAGE_PER_USER'] ?? null;

		$args = array(
			'code'                   => $coupon_code, // Code will be generated if empty.
			'description'            => $description,
			'discount_type'          => $discount_type ?? 'fixed_cart',
			'amount'                 => $coupon_amount ?? '0',
			'free_shipping'          => $allow_free_shipping ?? false,
			'expiry_date'            => $coupon_expiry_date,
			'minimum_spend'          => $minimum_spend ?? '',
			'maximum_spend'          => $maximum_spend ?? '',
			'individual_use'         => $is_individual_use ?? false,
			'exclude_sale_items'     => $exclude_sale_items ?? false,
			'product_ids'            => $products ?? array(),
			'exclude_product_ids'    => $exclude_products ?? array(),
			'product_categories'     => $product_categories ?? array(),
			'exclude_categories'     => $exclude_categories ?? array(),
			'email_restrictions'     => $allowed_emails ? explode( ',', $allowed_emails ) : array(),
			'usage_limit'            => $usage_limit_per_coupon ?? '',
			'limit_usage_to_x_items' => $limit_usage_per_item ?? '',
			'usage_limit_per_user'   => $limit_usage_per_user ?? '',
		);

		$id = $this->coupon_generator->generate( $args );

		$this->hydrate_tokens(
			array(
				'COUPON_ID'    => $id,
				'COUPON_TITLE' => get_the_title( $id ),
				'COUPON_URL'   => get_edit_post_link( $id ),
			)
		);

		return true;

	}

}
