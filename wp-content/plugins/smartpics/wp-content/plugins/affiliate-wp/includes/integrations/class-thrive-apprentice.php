<?php
/**
 * Integrations: Thrive Apprentice
 *
 * @package     AffiliateWP
 * @subpackage  Integrations
 * @copyright   Copyright (c) 2024, Sandhills Development, LLC
 * @license     http://opensource.org/licenses/gpl-2.0.php GNU Public License
 * @since       2.23.0
 * @author      Aubrey Portwood <aportwood@am.co>
 */

namespace AffiliateWP\Integrations;

// phpcs:disable PEAR.Functions.FunctionCallSignature.FirstArgumentPosition,PEAR.Functions.FunctionCallSignature.EmptyLine -- Allow space above comments.
// phpcs:disable WordPress.Arrays.ArrayIndentation.CloseBraceNotAligned -- Alignment not appropriate here.
// phpcs:disable WordPress.Arrays.MultipleStatementAlignment.DoubleArrowNotAligned -- Alignments are making code too hard to maintain.
// phpcs:disable WordPress.DB.PreparedSQL.InterpolatedNotPrepared,phpcs:disable WordPress.DB.PreparedSQL.NotPrepared,WordPress.DB.PreparedSQL.NotPrepared -- We are being safe about escaping into the DB.
// phpcs:disable Squiz.Commenting.FunctionCommentThrowTag.WrongNumber -- PHPCS gets this wrong.
// phpcs:disable Squiz.PHP.CommentedOutCode.Found -- WPCS also gets this wrong.

#[\AllowDynamicProperties]

/**
 * Thrive Apprentice Integration
 *
 * @since 2.23.0
 *
 * @see Affiliate_WP_Base
 */
class Thrive_Apprentice extends \Affiliate_WP_Base {

	/**
	 * The (named) context for referrals.
	 *
	 * This refers to the integration that is being used.
	 *
	 * @access  public
	 * @since   2.23.0
	 *
	 * @var string
	 */
	public $context = 'thrive-apprentice';

	/**
	 * The required version of Thrive Apprentice for the Integration to Operate.
	 *
	 * Note: 5.14 is when they released the filter required for the integration to work.
	 *
	 * @since 2.23.0
	 *
	 * @var string
	 */
	private static string $required_plugin_version = '5.14';

	/**
	 * Initialization
	 *
	 * @access  public
	 * @since   2.23.0
	 */
	public function init() {

		if ( ! $this->plugin_is_active() ) {
			return;
		}

		add_action( 'template_redirect', array( $this, 'add_frontend_hooks' ) );
		add_action( 'admin_init', array( $this, 'add_admin_hooks' ) );

		$this->add_stripe_hooks(); // Add these to catch Webhooks anytime/anywhere.
	}

	/**
	 * Runs the check necessary to confirm this plugin is active.
	 *
	 * @since 2.23.0
	 *
	 * @return bool True if the plugin is active, false otherwise.
	 */
	public function plugin_is_active() {

		return function_exists( 'thrive_load' ) &&
			self::is_plugin_required_version();
	}

	/**
	 * Is Thrive Apprentice the Correct Version?
	 *
	 * @since 2.23.0
	 *
	 * @return bool
	 */
	public static function is_plugin_required_version() : bool {

		if ( ! class_exists( '\TVA_Const' ) ) {
			return false;
		}

		return version_compare( \TVA_Const::PLUGIN_VERSION, self::$required_plugin_version, '>=' );
	}

	/**
	 * Add hooks.
	 *
	 * @since 2.23.0
	 */
	public function add_frontend_hooks() {
		$this->add_tva_stripe_buynow_button_hooks();
	}

	/**
	 * Hooks to modify the Stripe Buy Now button.
	 *
	 * Thrive Apprentice adds a Buy Now button that links to Stripe to
	 * complete the checkout/purchase process.
	 *
	 * @since 2.23.0
	 */
	private function add_tva_stripe_buynow_button_hooks() : void {
		add_filter( 'tva_buy_now_url_stripe_checkout_data', array( $this, 'track_stripe_buy_now_referral' ), 10, 2 );
	}

	/**
	 * Add referral/affiliate metadata to the Buy Now button URL.
	 *
	 * If the user clicks the button, we will pass along information that will help
	 * us convert a referral when:
	 *
	 * * The user fails to complete a purchase (e.g. bad CC #, insufficient funds, etc) on Stripe
	 * * The user completes the purchase (charge complete) on Stripe
	 *
	 * @since 2.23.0
	 *
	 * @param array $checkout_data The checkout data used to generate the Buy Now button URL.
	 * @param array $buynow_data   Data from the \TVA\Buy_Now object.
	 */
	public function track_stripe_buy_now_referral( array $checkout_data, array $buynow_data ) {

		// Get the affiliate_id using various methods whether the cookies have been set or not.
		$affiliate_id = $this->get_affiliate_id();

		// Note: We cannot trust self::was_referred() because the cookies might not have been generated yet.

		if ( ! is_numeric( $affiliate_id ) || intval( $affiliate_id ) === 0 ) {
			return $checkout_data; // No referring affiliate e.g. ?ref=X or /ref/X.
		}

		$reference = $this->create_reference();

		return array_merge(
			$checkout_data,
			array(
				'payment_intent_data' => array(
					'metadata' => array_merge(

						// Keep current metadata...
						$checkout_data['metadata'] ?? array(),

						// Add our metadata....
						array(

							// Encapsulate our data, yes we have to encode it into a string, stripe does not like multi-dimensional arrays.
							'affiliatewp' => wp_json_encode(
								array(

									// Pass along the Affiliate who referred the course...
									'affiliate_id' => $affiliate_id, // This may be from cookies or from the URL.

									// A reference for the referral once Stripe talks back to us.
									'reference' => $reference,

									// Send along the product data...
									'price_id'   => $buynow_data['price_id'] ?? '',
									'product_id' => $buynow_data['product_id'] ?? '',

									// Pass on the URI (we store in referral meta later)...
									'request_uri' => $_SERVER['REQUEST_URI'] ?? '',

									// Used to link the referral to the visit_id...
									'tracking_ip' => affiliate_wp()->tracking->get_ip(),
									'tracking_url' => affwp_sanitize_visit_url( affiliate_wp()->tracking->get_current_page_url() ),

									// Pass along a nonce, for extra security...
									'wp_nonce' => wp_create_nonce( $reference ),
								),
							),
						)
					),
				),
			)
		);
	}

	/**
	 * Get the Affiliate ID (Modified).
	 *
	 * This trusts the cookie first and foremost. But, in the case
	 * where the cookie is not found (because it has not been set yet)
	 * we get the Affiliate ID from other sources and eventually the URL
	 * itself.
	 *
	 * @since 2.23.0
	 *
	 * @param int $reference The reference (unused).
	 *
	 * @return int The Affiliate ID
	 */
	public function get_affiliate_id( $reference = 0 ) {

		// Get the affiliate ID from tracking first (the cookies might not be set yet).
		$affiliate_id = affiliate_wp()->tracking->get_affiliate_id();

		if ( is_numeric( $affiliate_id ) && ! $this->credit_last_referrer_set() ) {
			return (int) $affiliate_id; // The cookie has it (but don't trust when credit last referral is enabled).
		}

		// Try using other tracking methods to get the affiliate_id...
		$affiliate_id = affiliate_wp()->tracking->referral // Can be null.
			?? affiliate_wp()->tracking->get_fallback_affiliate_id();

		if ( is_numeric( $affiliate_id ) ) {
			return (int) $affiliate_id; // Tracking still got us the affiliate_id.
		}

		// Try one more fallback...
		$affiliate_id = affiliate_wp()->tracking->get_affiliate_id_from_login( $affiliate_id );

		if ( is_numeric( $affiliate_id ) ) {
			return (int) $affiliate_id; // Tracking got it from login...
		}

		// Get the affiliate ID from the URL (extreme fallback)...
		$affiliate_id = $this->get_affiliate_id_from_rewrite();

		if ( ! is_numeric( $affiliate_id ) || intval( $affiliate_id ) === 0 ) {
			return 0; // No ID from rewrite...
		}

		return (int) $affiliate_id; // The URL supplied us with the ID...
	}

	/**
	 * Is Credit Last Referrer Setting Set?
	 *
	 * If Credit Last Referrer is on, we can't really trust cookies
	 * because they haven't been switched in time...
	 *
	 * @since 2.23.0
	 *
	 * @return bool
	 */
	private function credit_last_referrer_set() : bool {

		$setting = affiliate_wp()->settings->get( 'referral_credit_last', false );

		return false === $setting
			? false
			: 1 === intval( $setting );
	}

	/**
	 * Get the Affiliate ID from the URL.
	 *
	 * If we fail to get the Affiliate ID from:
	 *
	 * 1. The cookies (because they haven't been set yet)
	 * 2. The tracking class
	 *
	 * ...revert to parsing the URL.
	 *
	 * Note this is very unlikely to happen, but it here as a last-resort
	 * fallback.
	 *
	 * @since 2.23.0
	 *
	 * @return int The Affiliate ID, 0 if none.
	 */
	private function get_affiliate_id_from_rewrite() : int {

		// phpcs:ignore WordPress.Security.NonceVerification.Recommended
		$ref = $_GET['ref'] ?? '';

		if ( '' !== $ref && false !== affwp_get_affiliate( $ref ) ) {

			/* This filter is documented in includes/class-tracking.php */
			return apply_filters( 'affwp_tracking_get_affiliate_id', $ref ); // ?ref=<id> had it, use this ID...
		}

		// Get /ref/<username|affiliate_id>...

		$url = wp_parse_url( home_url( $_SERVER['REQUEST_URI'] ?? '' ) );

		$ref_var = affiliate_wp()->tracking->get_referral_var();

		$ref = preg_match( "/\/{$ref_var}\/(.*)\/?/", $url['path'] ?? '', $matches );

		if ( ! isset( $matches[1] ) ) {
			return 0; // No username or # cound after /ref/...
		}

		$ref = trim( $matches[1] ?? '', '/' );

		if ( empty( $ref ) ) {
			return 0;
		}

		$affiliate = affwp_get_affiliate( $ref ); // Try by /ref/ID...

		if ( is_a( $affiliate, '\AffWP\Affiliate' ) ) {

			/* This filter is documented in includes/class-tracking.php */
			return apply_filters( 'affwp_tracking_get_affiliate_id', $affiliate->affiliate_id ); // /ref/# had it...
		}

		// Get by /ref/username...
		$user = \WP_User::get_data_by( 'login', $ref );

		if ( is_object( $user ) ) {
			return 0; // No user by that username, bail...
		}

		$affiliate = affwp_get_affiliate_by( 'affiliate_id', $user->ID );

		if ( is_a( $affiliate, '\AffWP\Affiliate' ) ) {

			/* This filter is documented in includes/class-tracking.php */
			return apply_filters( 'affwp_tracking_get_affiliate_id', $affiliate->affiliate_id ); // We found an affiliate by that username/ID.
		}

		return 0; // No affiliate found in the URL...
	}

	/**
	 * Create a Reference to use for the Referral.
	 *
	 * This uses a counter to generate an ID to use for Reference in the DB.
	 *
	 * It includes a tva- prefix because if any
	 * function uses `affwp_get_referral_by( 'reference', ... )`
	 * we can have collisions with other referrals with ID's that are plain-integers.
	 *
	 * @since 2.23.0
	 *
	 * @return string Returns a reference based on a counter we will
	 *                maintain in the database.
	 */
	private function create_reference() : string {

		// Where we store the reference counter...
		$option_key = 'affwp_tva_ref_counter';

		// Get the last ID we generated (default counting at 1)...
		$reference = get_option( $option_key, 1 );

		// Iterate the counter for next time and write that to the DB.
		update_option( $option_key, $reference + 1, false );

		return $reference;
	}

	/**
	 * Add hooks for responding to Stripe Webhooks.
	 *
	 * @since 2.23.0
	 */
	private function add_stripe_hooks() : void {

		add_action( 'tva_stripe_webhook_charge.succeeded', array( $this, 'process_successful_payment' ) );
		add_action( 'tva_stripe_webhook_payment_intent.payment_failed', array( $this, 'process_unsuccessful_payment' ) );
	}

	/**
	 * Process a (Successful) Stripe Charge.
	 *
	 * Again, this is responding to the page that the Buy Now button sent us to.
	 *
	 * @since 2.23.0
	 *
	 * @param \Stripe\Event $event The Stripe Event.
	 */
	public function process_successful_payment( \Stripe\Event $event ) : void {

		$stripe_data = $this->extract_stripe_event_data( $event );

		if ( empty( $stripe_data ) ) {
			return;
		}

		$affiliatewp_meta = $this->extract_affiliatewp_meta( $stripe_data );

		if ( empty( $affiliatewp_meta ) ) {
			return;
		}

		if ( ! wp_verify_nonce( $affiliatewp_meta['wp_nonce'], $affiliatewp_meta['reference'] ) ) {
			return; // The nonce did not check out, it did not come from us.
		}

		// Create a draft referral (there might be various reasons to mark this as failed)...
		$referral = $this->setup_referral(
			$affiliatewp_meta['affiliate_id'],
			array(
				'reference' => $affiliatewp_meta['reference'],
				'description' => $this->get_product_description( $affiliatewp_meta['price_id'] ?? '' ),
			)
		);

		// This will mark the referral failed if anything is doesn't check out...
		if ( ! $this->ready_to_hydrate_and_complete_referral( $referral, $affiliatewp_meta, $stripe_data ) ) {
			return; // ready_to_hydrate_and_complete_referral() should have thrown logs for you (and marks the draft as failed).
		}

		$product_total = $this->convert_stripe_total( $stripe_data['data']['object']['amount'] );

		// Update the referral with the information Stripe sent us.
		$this->hydrate_referral(
			$referral->referral_id,
			array(

				// The referral amount...
				'amount'      => $this->calculate_referral_amount(
					$product_total,
					$referral->reference,
					0, // No Product Rates.
					$affiliatewp_meta['affiliate_id'],
					0 // No Product Category ID.
				),

				// Data...
				'order_total' => $product_total,
				'customer_id' => $this->add_customer( $affiliatewp_meta, $stripe_data ),
				'products'    => isset( $affiliatewp_meta['price_id'], $affiliatewp_meta['product_id'] )

					// Store an array of "products".
					? array(

						// This is just one Buy Now button.
						$affiliatewp_meta['product_id'] => $affiliatewp_meta['price_id'],
					)

					// No products (unlikely to happen).
					: array(),
			)
		);

		$this->try_and_store_stripe_referral_information( $referral, $affiliatewp_meta, $stripe_data );

		$this->complete_referral( $referral ); // Convert to a full-fledged referral (unpaid).

		$this->try_and_update_visit_retroactively(
			$affiliatewp_meta['tracking_ip'],
			$affiliatewp_meta['tracking_url'],
			$referral->referral_id,
			$affiliatewp_meta['affiliate_id']
		);
	}

	/**
	 * Process a (Unsuccessful) Referral Charge.
	 *
	 * Again, this responds to the page the Buy Now button sent us to on Stripe.
	 *
	 * @since 2.23.0
	 *
	 * @param \Stripe\Event $event Stripe Event (Webhooks).
	 */
	public function process_unsuccessful_payment( \Stripe\Event $event ) : void {

		$stripe_data = $this->extract_stripe_event_data( $event );

		if ( empty( $stripe_data ) ) {
			return;
		}

		if ( 'succeeded' === $stripe_data['data']['object']['status'] ?? '' ) {

			// Very unlikely Stripe would ever do this, but if its succeeded, something is very wrong.
			$this->log( 'Referral processed as failed, but found `succeeded` in Stripe data.' );
				return;
		}

		$affiliatewp_meta = $this->extract_affiliatewp_meta( $stripe_data );

		if ( empty( $affiliatewp_meta ) ) {

			$this->log( 'Unable to extract AffiliateWP meta from stripe data on failed charge.' );
				return; // Can't mark a referral as incomplete due to not being able to find it.
		}

		if ( ! wp_verify_nonce( $affiliatewp_meta['wp_nonce'], $affiliatewp_meta['reference'] ) ) {
			return; // The nonce did not check out, it did not come from us.
		}

		// Create a draft referral (or get the one we already created for this by reference).
		$referral = $this->setup_referral(
			$affiliatewp_meta['affiliate_id'],
			array(
				'reference' => $affiliatewp_meta['reference'],
			)
		);

		if ( ! is_a( $referral, '\AffWP\Referral' ) ) {

			$this->log( 'Could not obtain draft referral from the database.' ); // Unlikely to happen.
				return; // Not a valid referral (unlikely to happen).
		}

		// Note, during a failure, we do not get customer information to log.

		$this->try_and_store_stripe_referral_information( $referral, $affiliatewp_meta, $stripe_data );

		$this->mark_referral_failed(
			array(
				'referral_id' => $referral->referral_id,
				'reason'      => 'Unsuccessful charge.',
			)
		);

		$this->try_and_update_visit_retroactively(
			$affiliatewp_meta['tracking_ip'],
			$affiliatewp_meta['tracking_url'],
			$referral->referral_id,
			$affiliatewp_meta['affiliate_id']
		);
	}

	/**
	 * Update a Visit (Retroactively).
	 *
	 * @since 2.23.0
	 *
	 * @param string $tracking_ip  The Tracking IP we sent with the Stripe metadata (used to find the visit).
	 * @param string $tracking_url The Tracking URL we sent with the Stripe metadata (used to find the visit).
	 * @param int    $referral_id  The Referral ID (link the referral to the visit).
	 * @param int    $affiliate_id The Affiliate ID (link the affiliate to the visit).
	 */
	private function try_and_update_visit_retroactively(
		string $tracking_ip,
		string $tracking_url,
		int $referral_id,
		int $affiliate_id
	) : void {

		global $wpdb;

		// Get the (last) visit by the IP and URL (for the Affiliate) we passed via the AffiliateWP Stripe metadata...
		$visit_id = $wpdb->get_var(
			str_replace(
				'{table_name}',
				affiliate_wp()->visits->table_name,
				$wpdb->prepare(
					'
						SELECT
							`visit_id`
						FROM
							{table_name}
						WHERE
							`url` = %s
								AND `ip` = %s
								AND `affiliate_id` = %d
						ORDER BY
							`date` DESC
					',
					$tracking_url,
					$tracking_ip,
					$affiliate_id
				)
			)
		);

		if ( ! is_numeric( $visit_id ) ) {

			$this->log( "Unable to update visit retroactively for Referral {$referral_id}." );
				return;
		}

		affiliate_wp()->visits->update(
			$visit_id,
			array(
				'affiliate_id' => $affiliate_id,
				'referral_id'  => $referral_id,
				'context'      => $this->context,
			)
		);
	}

	/**
	 * Get (or Create) a Referral.
	 *
	 * This takes into account that there may already be a referral (e.g. failed)
	 * for the process (by reference).
	 *
	 * @since 2.23.0
	 *
	 * @param int   $affiliate_id The Affiliate ID.
	 * @param array $args         The arguments (reference is required).
	 *
	 * @return mixed An `\AffWP\Referral` if we created/obtained a draft referral or `false` if we could not.
	 */
	private function setup_referral( int $affiliate_id, array $args ) {

		if ( ! isset( $args['reference'] ) ) {
			return false; // We need at least the affiliate_id and reference to get the right draft referral.
		}

		// This may create a new one or get an existing one by reference...
		$referral_id = $this->get_referral( $affiliate_id, $args );

		if ( ! is_numeric( $affiliate_id ) || intval( $referral_id ) === 0 ) {

			$this->log( "Could not create/get draft referral for Affiliate with ID {$affiliate_id}." );
				return false;
		}

		$referral = affwp_get_referral( $referral_id );

		if ( ! is_a( $referral, '\AffWP\Referral' ) ) {

			$this->log( 'Could not obtain draft referral from the database.' ); // Unlikely to happen.
				return false; // Not a valid referral (unlikely to happen).
		}

		return $referral;
	}

	/**
	 * Get Referral
	 *
	 * This takes into account that there may already be a referral created
	 * with the same reference (even one that isn't a draft, and previously was
	 * failed).
	 *
	 * @since 2.23.0
	 *
	 * @param int   $affiliate_id Affiliate ID.
	 * @param array $data         Data (arguments).
	 *
	 * @return int
	 */
	public function get_referral( int $affiliate_id, array $data = array() ) {

		$referral = affwp_get_referral_by( 'reference', $data['reference'] ?? '', $this->context );

		if (
			is_a( $referral, '\AffWP\Referral' ) &&
			intval( $affiliate_id ) === $referral->affiliate_id
		) {
			return $referral->referral_id; // Use the referral we already created...
		}

		// Create a new draft referral for this.
		return parent::insert_draft_referral( $affiliate_id, $data );
	}

	/**
	 * Convert a Stripe Total.
	 *
	 * @since 2.23.0
	 *
	 * @param int $total The Stripe Total (e.g. for $10 it's 1000).
	 *
	 * @return float Converts e.g. $10 (1000) to 10.00.
	 */
	private function convert_stripe_total( int $total ) : float {
		return $total / 100;
	}

	/**
	 * Mark Referral Failed (Modified).
	 *
	 * This adds a way to log the reason why it failed that ends up
	 * logged to the logs and in the referral's meta.
	 *
	 * @since 2.23.0
	 *
	 * @param array $data {
	 *     Information about the failed referral.
	 *     @type int    $referral_id The Referral ID to mark as failed.
	 *     @type string $reason      The reason it was failed.
	 * }
	 *
	 * @throws \InvalidArgumentException If $data is not an array (developer error).
	 * @throws \InvalidArgumentException If $data[referral_id] is not numeric (developer error).
	 * @throws \InvalidArgumentException If $data[reason] or $data[referral_id] is not set (developer error).
	 * @throws \InvalidArgumentException If $data[reason] is not a string (developer error).
	 */
	public function mark_referral_failed( $data ) : void {

		// Mark as failed like normal.
		parent::mark_referral_failed( $data['referral_id'] ?? 0 );

		if ( ! is_array( $data ) ) {
			throw new \InvalidArgumentException( '$data must be an array' );
		}

		if ( ! isset( $data['referral_id'], $data['reason'] ) ) {
			throw new \InvalidArgumentException( '$data[referral_id] and $data[reason] must be set.' );
		}

		if ( ! is_numeric( $data['referral_id'] ) ) {
			throw new \InvalidArgumentException( '$data[referral_id] must be numeric.' );
		}

		if ( ! is_string( $data['reason'] ) ) {
			throw new \InvalidArgumentException( '$data[reason] must be a string.' );
		}

		// Log more information....
		$this->log( "Referral w/ ID #{$data['referral_id']} failed because: {$data['reason']}" );

		// Store why it failed.
		affwp_update_referral_meta( $data['referral_id'], 'tva_failed', $data['reason'] );
	}

	/**
	 * Complete a Referral (Modified).
	 *
	 * This also makes sure we delete any reasons it might have
	 * failed in the past (in referral meta).
	 *
	 * @since 2.23.0
	 *
	 * @param \AffWP\Referral $referral The Referral to complete (object).
	 *
	 * @return bool
	 *
	 * @throws \InvalidArgumentException If you do not pass a Referral object (developer error).
	 */
	public function complete_referral( $referral = 0 ) {

		if ( ! is_a( $referral, '\AffWP\Referral' ) ) {
			throw new \InvalidArgumentException( '$referral should be an \AffWP\Referral object.' );
		}

		$current_status = affwp_get_referral_status( $referral->referral_id );

		// We have to set the status to unpaid for parent::complete_referral() to work if it failed previously.
		affwp_set_referral_status( $referral->referral_id, 'pending' );

		if ( ! parent::complete_referral( $referral ) ) {

			// Completion didn't work, convert back to this' old status (e.g. failed).
			affwp_set_referral_status( $referral->referral_id, $current_status );

			return false;
		}

		// In case it failed previously, let's delete this information.
		affwp_delete_referral_meta( $referral->referral_id, 'tva_failed', '' );

		return true;
	}

	/**
	 * Extract and check data from a Stripe Event.
	 *
	 * @since 2.23.0
	 *
	 * @param \Stripe\Event $event The Stripe Event.
	 *
	 * @return array Array of Stripe Data, or empty array when there are issues.
	 */
	private function extract_stripe_event_data( \Stripe\Event $event ) : array {

		$stripe_data = $event->toArray() ?? array();

		if ( empty( $stripe_data ) || ! is_array( $stripe_data ) ) {
			return array(); // Why toArray() would not result in an array would be odd, but just in case.
		}

		return $stripe_data;
	}

	/**
	 * Extract sent AffiliateWP meta from Stripe Data.
	 *
	 * This make sure, for a Stripe Event (data), that there was all the
	 * metadata we need to process a referral.
	 *
	 * Logs information when there are issues too.
	 *
	 * @since 2.23.0
	 *
	 * @param array $stripe_data Stripe data from extract_stripe_event_data().
	 *
	 * @return array AffiliateWP Meta, or empty array when there are issues.
	 */
	private function extract_affiliatewp_meta( array $stripe_data ) : array {

		$affiliatewp_meta = $stripe_data['data']['object']['metadata']['affiliatewp'] ?? false;

		if ( false === $affiliatewp_meta || ! is_string( $affiliatewp_meta ) || empty( $affiliatewp_meta ) ) {
			return array(); // No valid meta found, not Stripe data associated with us.
		}

		$affiliatewp_meta = json_decode( $affiliatewp_meta, true );

		if (
			! is_array( $affiliatewp_meta ) ||
			! isset(

				$affiliatewp_meta['affiliate_id'], // Used to stop self-referrals.
				$affiliatewp_meta['reference'], // Used as the reference.

				// Used to get the vistit_id retroactively...
				$affiliatewp_meta['tracking_ip'],
				$affiliatewp_meta['tracking_url'],

				// Used when hydrating and remembering products.
				$affiliatewp_meta['price_id'], // Used to get a description for the referral.
				$affiliatewp_meta['product_id'], // Stored in the products column of the referral.

				// Security...
				$affiliatewp_meta['wp_nonce'] // We generated a nonce for this, for extra security...
			)
		) {

			$this->log( 'All required data not present in Stripe data.' );
				return array(); // This should have converted to an array, with our expected data in there.
		}

		return $affiliatewp_meta;
	}

	/**
	 * Check Stripe Data, Affiliate Meta, and Referral if it can be hydrated/completed.
	 *
	 * Logs information when there are issues too.
	 *
	 * @since 2.23.0
	 *
	 * @param mixed $referral         The referral object.
	 * @param array $affiliatewp_meta The AffiliateWP Meta from extract_and_check_affiliatewp_meta().
	 * @param array $stripe_data      The Stripe Data from extract_stripe_event_data().
	 *
	 * @return bool True if all the data checks out, false if something doesn't check out to continue to the hydrate step.
	 */
	private function ready_to_hydrate_and_complete_referral(
		$referral,
		array $affiliatewp_meta,
		array $stripe_data
	) : bool {

		if ( ! is_a( $referral, '\AffWP\Referral' ) ) {
			return false; // Can't hydrate referral that isn't a referral (unlikely).
		}

		// A valid referral needs an amount.
		if ( ! isset( $stripe_data['data']['object']['amount'] ) || ! is_numeric( $stripe_data['data']['object']['amount'] ) ) {

			$this->mark_referral_failed(
				array(
					'referral_id' => $referral->referral_id,
					'reason'      => 'No valid amount (from Stripe).',
				)
			);

			return false;
		}

		// Successful status' only...
		if ( 'succeeded' !== $stripe_data['data']['object']['status'] ?? '' ) {

			$this->mark_referral_failed(
				array(
					'referral_id' => $referral->referral_id,
					'reason'      => "Expecting data->object->status to be `succeeded`, but isn't.",
				)
			);

			return false;
		}

		// Stop self-referrals.
		if (
			isset( $stripe_data['data']['object']['billing_details']['email'] ) &&
			$this->is_affiliate_email( $stripe_data['data']['object']['billing_details']['email'], $affiliatewp_meta['affiliate_id'] )
		) {

			$this->mark_referral_failed(
				array(
					'referral_id' => $referral->referral_id,
					'reason'      => "Referral not created because affiliate's own account was used.",
				)
			);

			return false;
		}

		return true; // Go ahead and hydrate and/or complete the referral.
	}

	/**
	 * Pluck Data (Stripe) and Store it in Referral Meta.
	 *
	 * Takes the data from the AffiliateWP Meta and Stripe Data and
	 * stores information for the referral that might be useful later.
	 *
	 * The `stripe_receipt_url` is especially important for linking
	 * to the receipt in the admin.
	 *
	 * The rest of the data isn't functional yet, but I want to make sure
	 * we log information if we want to extend this integration more.
	 *
	 * @since 2.23.0
	 *
	 * @param \AffWP\Referral $referral         The Referral.
	 * @param array           $affiliatewp_meta The AffiliateWP Meta.
	 * @param array           $stripe_data      The Stripe Data.
	 */
	private function try_and_store_stripe_referral_information(
		\AffWP\Referral $referral,
		array $affiliatewp_meta,
		array $stripe_data
	) : void {

		foreach (

			// Save some information in referral meta...
			array(

				// Thrive Apprentice information...
				'tva_price_id'          => $affiliatewp_meta['price_id'] ?? '',
				'tva_product_id'        => $affiliatewp_meta['product_id'] ?? '',
				'tva_course_uri'        => strval( $affiliatewp_meta['tracking_url'] ?? '' ),

				// Information from Stripe...
				'stripe_id'             => strval( $stripe_data['id'] ?? '' ),
				'stripe_created'        => strval( $stripe_data['created'] ?? '' ),
				'stripe_currency'       => strval( $stripe_data['data']['object']['currency'] ?? '' ),
				'stripe_payment_intent' => strval( $stripe_data['data']['object']['payment_intent'] ?? '' ),
				'stripe_receipt_url'    => strval( $stripe_data['data']['object']['receipt_url'] ?? '' ), // We link this in the reference column.

				// Errors (if any)...
				'stripe_last_payment_error_code'         => strval( $stripe_data['data']['object']['last_payment_error']['code'] ?? 'none' ),
				'stripe_last_payment_error_decline_code' => strval( $stripe_data['data']['object']['last_payment_error']['decline_code'] ?? 'none' ),
				'stripe_last_payment_error_type'         => strval( $stripe_data['data']['object']['last_payment_error']['type'] ?? 'none' ),

				// Testing/Live mode...
				'stripe_livemode'       => (
					isset( $stripe_data['livemode'] ) &&
					1 === intval( $stripe_data['livemode'] )
				)
					? 'yes'
					: 'no',
			) as $key => $value
		) {

			if ( empty( $value ) ) {
				continue;
			}

			affwp_update_referral_meta(
				$referral->referral_id,
				$key,
				$value
			);
		}
	}

	/**
	 * Add a Customer to the Customer DB.
	 *
	 * * If the Stripe Data doesn't have an email (at least), we don't add the customer at all.
	 * * We will store the `affiliate_id` only if the AffiliateWP Meta has it (and it should).
	 *
	 * All the data is optional, if it's not passed to us (for whatever reason) we don't log it.
	 * Stripe might have settings for privacy that might prevent some of this data from making
	 * it down.
	 *
	 * @since 2.23.0
	 *
	 * @param array $affiliatewp_meta The AffiliateWP Meta.
	 * @param array $stripe_data      The Stripe Data.
	 *
	 * @return int The Customer ID, or 0 if we don't add one.
	 */
	private function add_customer(
		array $affiliatewp_meta,
		array $stripe_data
	) : int {

		if ( ! isset( $stripe_data['data']['object']['billing_details']['email'] ) ) {
			return 0; // We need at least the email to add a customer.
		}

		// Add a customer for this referral (even if the referral fails, they at least referred a person)...
		$customer_id = affwp_add_customer(
			array(
				'email'      => $stripe_data['data']['object']['billing_details']['email'],

				// Try and Grab the first name from the name value...
				'first_name' => ( isset( $stripe_data['data']['object']['billing_details']['name'] ) && is_string( $stripe_data['data']['object']['billing_details']['name'] ) )
					? current( explode( ' ', $stripe_data['data']['object']['billing_details']['name'] ) )
					: '',

				// Try and grab the last name from the name value...
				'last_name'  => ( isset( $stripe_data['data']['object']['billing_details']['name'] ) && is_string( $stripe_data['data']['object']['billing_details']['name'] ) )
					? end( explode( ' ', $stripe_data['data']['object']['billing_details']['name'] ) )
					: '',

				// The affiliate who created the customer (if we have one).
				'affiliate_id' => $affiliatewp_meta['affiliate_id'] ?? 0,
			),
		);

		if (

			// Update other customer information...
			is_numeric( $customer_id ) &&
			isset( $stripe_data['data']['object']['billing_details']['address'] ) &&
			is_array( $stripe_data['data']['object']['billing_details']['address'] )
		) {

			// Store the address information for the customer.
			foreach ( $stripe_data['data']['object']['billing_details']['address'] as $key => $value ) {

				if ( ! is_string( $value ) ) {
					continue; // Just store what they give us...
				}

				affwp_update_customer_meta(
					$customer_id,
					"address_{$key}",
					$value
				);
			}
		}

		return is_numeric( $customer_id )
			? $customer_id
			: 0;
	}

	/**
	 * Get the description of a product by price_id.
	 *
	 * We can use `price_id` to retroactively get the product_name from the order
	 * that was added to the DB.
	 *
	 * @since 2.23.0
	 *
	 * @param string $price_id The price_id.
	 *
	 * @return string Unknown if we didn't find it.
	 */
	private function get_product_description( string $price_id ) : string {

		global $wpdb, $table_prefix;

		$course = $wpdb->get_var(
			str_replace(
				array(
					'{tva_table_name}',
				),
				array(
					"{$table_prefix}tva_order_items",
				),
				$wpdb->prepare( 'SELECT `product_name` FROM {tva_table_name} WHERE `product_id` = %s', $price_id )
			)
		);

		return is_string( $course ) && ! empty( $course )
			? $course
			: __( 'Unknown', 'affiliate-wp' );
	}

	/**
	 * Hooks to Configure the Admin.
	 *
	 * @since 2.23.0
	 */
	public function add_admin_hooks() : void {
		add_filter( 'affwp_referral_reference_column', array( $this, 'link_stripe_receipt_reference' ), 10, 2 );
	}

	/**
	 * Link the Reference Column to the Stripe Receipt.
	 *
	 * @since 2.23.0
	 *
	 * @param string          $reference The reference of the Referral.
	 * @param \AffWP\Referral $referral  The referral.
	 *
	 * @return string The reference linked to the Stripe Receipt URL,
	 *                or just the reference if no URL available (unlikely).
	 */
	public function link_stripe_receipt_reference( $reference, \AffWP\Referral $referral ) {

		if ( empty( $referral->context ) || $this->context !== $referral->context ) {
			return $reference;
		}

		$receipt_url = $this->get_referral_stripe_receipt_url( $referral->referral_id );

		if ( empty( $receipt_url ) || ! filter_var( $receipt_url, FILTER_VALIDATE_URL ) ) {
			return $reference; // Nothing to link to, use current reference.
		}

		return sprintf(
			'<a href="%1$s" target="_blank" title="%2$s">%3$s</a>',
			$receipt_url,
			__( 'View Stripe Receipt', 'affiliate-wp' ),
			$referral->reference
		);
	}

	/**
	 * Get the Stripe Receipt URL we store when we validate the Referral.
	 *
	 * @since 2.23.0
	 *
	 * @param int $referral_id The Referral ID.
	 *
	 * @return string Empty if none.
	 */
	private function get_referral_stripe_receipt_url( int $referral_id ) : string {

		$receipt_url = affwp_get_referral_meta( $referral_id, 'stripe_receipt_url', true );

		if ( ! is_string( $receipt_url ) ) {
			return ''; // No URL found.
		}

		return $receipt_url;
	}
}

// Instantiate the integration...
new Thrive_Apprentice();
