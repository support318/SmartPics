<?php
/**
 * Integrations: Advanced Coupons.
 *
 * @package    AffiliateWP
 * @subpackage Integrations
 * @copyright  Copyright (c) 2024, Sandhills Development, LLC
 * @license    http://opensource.org/licenses/gpl-2.0.php GNU Public License
 * @since      2.21.0
 */

use ACFWF\Models\Objects\Advanced_Coupon;

if ( class_exists( 'ACFWF', false ) ) :

	#[\AllowDynamicProperties]

	/**
	 * Implements an integration for Advanced Coupons.
	 *
	 * @since 2.21.0
	 */
	class AffiliateWP_Advanced_Coupons {

		/**
		 * Constructor.
		 *
		 * @since  2.21.0
		*/
		public function __construct() {

			// Register scripts.
			add_action( 'wp_enqueue_scripts', array( $this, 'register_scripts' ) );

			// Add the coupon url in the Affiliate Area coupons table.
			add_filter( 'affwp_coupons_dashboard_code_td', array( $this, 'add_coupon_url' ), 10, 3 );
		}

		/**
		 * Adds the coupon url in the Affiliate Area coupons table.
		 *
		 * @since 2.21.0
		 *
		 * @param string $coupon_code The coupon code.
		 * @param array $coupon_details {
		 *     Coupon details.
		 *
		 *     @type int     $id          Coupon ID.
		 *     @type sting   $type        Coupon type (manual or dynamic).
		 *     @type string  $code        Coupon code.
		 *     @type array   $amount      Coupon amount.
		 *     @type string  $integration Integration.
		 * }
		 * @param int $affiliate_id Affiliate ID.
		 */
		public function add_coupon_url( $coupon_code, $coupon_details, $affiliate_id ) {

			// Bail if not a manual WooCommerce coupon.
			if ( 'woocommerce' !== $coupon_details['integration'] || 'manual' !== $coupon_details['type'] ) {
				return $coupon_code;
			}

			// Coupon ID.
			$id = $coupon_details['id'];

			$coupon = new Advanced_Coupon( $id );

			// If the coupon is not valid, return the coupon code.
			if ( ! $coupon->get_id() || ! $coupon->is_coupon_url_valid() ) {
				return $coupon_code;
			}

			// Get the coupon URL.
			$coupon_url = $coupon->get_coupon_url();

			$label_with_tooltip = sprintf( '%1$s <span class="affwp-tooltip-help" data-tippy-content="%2$s">(?)</span>',
				__( 'COUPON URL' ),
				esc_attr( 'Share this link with your audience to automatically apply the coupon to their cart.', 'affiliate-wp' ),
			);

			affwp_enqueue_script( 'affwp-integration-advanced-coupons' );

			ob_start();

			?>

			<?php echo $coupon_code ?>

			<div class="affwp-coupon-url-section">

				<label class="affwp-row-header"><?php echo $label_with_tooltip; ?></label>

				<div id="affwp-coupon-url-<?php echo esc_attr( $id ); ?>" class="affwp-row" data-coupon-url="<?php echo esc_attr( $coupon_url ); ?>" data-coupon-id="<?php echo esc_attr( $id ); ?>">
					<span class="affwp-coupon-url affwp-tooltip-url-copy"><?php echo $coupon_url; ?></span>
					<span class="affwp-row-actions">
						<button class="affwp-tooltip affwp-tooltip-button-copy" data-tippy-content="<?php esc_attr_e( 'Copy Coupon URL', 'affiliate-wp' ); ?>">
							<span class="affwp-copy-coupon-url affwp-row-action affwp-coupon-url">
								<svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="-0.25 -0.25 24.5 24.5" stroke-width="2" height="20" width="20"><path stroke="currentColor" stroke-linecap="round" stroke-linejoin="round" d="M16.75 4.5V1.75C16.75 1.19772 16.3023 0.75 15.75 0.75H1.75C1.19772 0.75 0.75 1.19771 0.75 1.75V15.75C0.75 16.3023 1.19772 16.75 1.75 16.75H4.5"></path><path stroke="currentColor" stroke-linejoin="round" d="M7.25 8.25C7.25 7.69771 7.69772 7.25 8.25 7.25H22.25C22.8023 7.25 23.25 7.69772 23.25 8.25V22.25C23.25 22.8023 22.8023 23.25 22.25 23.25H8.25C7.69771 23.25 7.25 22.8023 7.25 22.25V8.25Z"></path></svg>
							</span>
						</span>
					</span>

				</div>

			</div>

			<?php

			return ob_get_clean();
		}

		/**
		 * Register scripts.
		 *
		 * @since 2.21.0
		 *
		 * @return void
		 */
		public function register_scripts() : void {
			if ( 'coupons' !== affwp_get_active_affiliate_area_tab() || empty( $_GET['tab'] ) && 'coupons' !== sanitize_key( $_REQUEST['tab'] ) ) {
				return;
			}

			wp_register_script(
				'affwp-integration-advanced-coupons',
				sprintf(
					'%1$sassets/js/affiliatewp-integration-advanced-coupons%2$s.js',
					AFFILIATEWP_PLUGIN_URL,
					( defined( 'SCRIPT_DEBUG' ) && SCRIPT_DEBUG ) ? '' : '.min'
				),
				array(
					'jquery',
					'affiliatewp-tooltip',
				),
				AFFILIATEWP_VERSION,
				true
			);

			$json = wp_json_encode(
				array(
					'i18n' => array(
						'copyCouponURL'     => __( 'Copy Coupon URL', 'affiliate-wp' ),
						'copySuccess'       => __( 'Coupon URL copied!', 'affiliate-wp' ),
					),
				)
			);

			wp_add_inline_script( 'affwp-integration-advanced-coupons', "window.affwpAdvancedCouponsVars={$json}", 'before' );
		}
	}
	new AffiliateWP_Advanced_Coupons();
endif;
