<?php
/**
 * Affiliate Review Functions & Tools (including Review with AI).
 *
 * @package     AffiliateWP
 * @subpackage  Admin/Affiliates
 * @copyright   Copyright (c) 2024, AwesomeMotive Inc
 * @license     http://opensource.org/licenses/gpl-2.0.php GNU Public License
 * @since       2.26.0
 * @author      Aubrey Portwood <aportwood@am.co>
 *
 * @see affwp_process_affiliate_moderation() in wp-content/plugins/affiliate-wp/includes/admin/affiliates/actions.php
 *
 * phpcs:disable WordPress.Arrays.MultipleStatementAlignment.DoubleArrowNotAligned
 * phpcs:disable PEAR.Functions.FunctionCallSignature.EmptyLine
 * phpcs:disable PEAR.Functions.FunctionCallSignature.FirstArgumentPosition
 * phpcs:disable PEAR.Functions.FunctionCallSignature.CloseBracketLine
 */

namespace AffiliateWP\Admin\Affiliates\Review_Affiliate;

use AffiliateWP\Utils\Icons as Icons;

// Hooks.
add_action( 'admin_enqueue_scripts', __NAMESPACE__ . '\enqueue_moderate_affiliate_scripts' );
add_action( 'affwp_moderate_affiliate', __NAMESPACE__ . '\store_review_decisions', -99 );

// AJAX Requests.
add_action( 'wp_ajax_secure_affiliate_review_data', __NAMESPACE__ . '\ajax_secure_affiliate_review_data' );
add_action( 'wp_ajax_log_affiliate_review_ai_usage', __NAMESPACE__ . '\ajax_log_affiliate_review_ai_usage' );

/**
 * Review the Affiliate
 *
 * @param array $data  The POST data.
 *
 * @since 2.26.0 Added so there is a dedicated function for reviewing affiliates without
 *               having to deprecate affwp_process_affiliate_moderation().
 *
 * @return void
 */
function store_review_decisions( $data ) {

	// Store all the decisions (user/ai) for later, so we can see when users disagree with AI.
	affwp_update_affiliate_meta( $data['affiliate_id'] ?? 0, 'ai_decision_status', in_array( ( $data['ai_status'] ?? '' ), [ 'accepted', 'rejected' ], true ) ? $data['ai_status'] : '' );
	affwp_update_affiliate_meta( $data['affiliate_id'] ?? 0, 'ai_decision_reason', is_string( $data['ai_reason'] ?? false ) ? html_entity_decode( $data['ai_reason'] ) : '' );
	affwp_update_affiliate_meta( $data['affiliate_id'] ?? 0, 'user_decision_status', in_array( ( $data['decision'] ?? '' ), [ 'accept', 'reject' ], true ) ? $data['decision'] : '' );

	if ( empty( $data['ai_status'] ) ) {
		return; // AI never made a decision, don't log any agreements/disagreements.
	}

	// If they disagree w/ AI.
	if (

		// AI rejected but user accepted.
		(
			'rejected' === ( $data['ai_status'] ?? '' ) &&
			'accept' === ( $data['decision'] ?? '' )
		)

		// Or, AI accepted but user rejected.
		|| (
			'accepted' === ( $data['ai_status'] ?? '' ) &&
			'reject' === ( $data['decision'] ?? '' )
		)

		// They were undecided (but AI decided), that's a disagreement.
		|| 'undecided' === ( $data['decision'] ?? '' )
	) {

		// ++ Disagreements.
		update_option(
			get_ai_prefixed_usage_option_key( 'disagreements', 'interaction' ),
			intval( get_option( get_ai_prefixed_usage_option_key( 'disagreements', 'interaction' ), 0 ) ) + 1,
			false
		);

		return;
	}

	// ++ Agreements.
	update_option(
		get_ai_prefixed_usage_option_key( 'agreements', 'interaction' ),
		intval( get_option( get_ai_prefixed_usage_option_key( 'agreements', 'interaction' ), 0 ) ) + 1,
		false
	);
}

/**
 * Load Scripts for Moderating Affiliate.
 *
 * @since 2.26.0
 *
 * @return void
 */
function enqueue_moderate_affiliate_scripts() {

	if ( 'affiliate-wp-affiliates' !== affwp_get_current_screen() ) {
		return; // Only load these on the affiliates screens.
	}

	$plugin_url    = AFFILIATEWP_PLUGIN_URL;
	$script_handle = 'affiliatewp-admin-review-affiliate';

	if ( 'review_affiliate' !== filter_input( INPUT_GET, 'action', \FILTER_UNSAFE_RAW ) ) {
		return; // Only load the stuff below when we are actually reviewing an affiliate.
	}

	wp_enqueue_style(
		'affiliatewp-admin-review-affiliate',
		affiliatewp_get_constant( 'SCRIPT_DEBUG', false )
			? "{$plugin_url}/assets/css/admin-review-affiliate.css"
			: "{$plugin_url}/assets/css/admin-review-affiliate.min.css",
		array(),
		AFFILIATEWP_VERSION
	);

	affiliate_wp()->scripts->enqueue(
		$script_handle,
		array(
			'affiliatewp-modal',
			'jquery',
			'jquery-confirm',
		),
		affiliatewp_get_constant( 'SCRIPT_DEBUG', false )
			? "{$plugin_url}/assets/js/admin-review-affiliate.js"
			: "{$plugin_url}/assets/js/admin-review-affiliate.min.js",
	);

	// We need wp_get_available_translations() from this file.
	require_once ABSPATH . 'wp-admin/includes/translation-install.php';

	$licensing = new \AffWP\Core\License\License_Data();

	wp_localize_script(
		$script_handle,
		'affiliateWPAffiliateReview',
		[
			'proxyUrl'        => untrailingslashit(
				defined( 'AFFILIATEWP_AI_PROXY_URL' )
					? AFFILIATEWP_AI_PROXY_URL
					: 'https://affiliatewp.com/'
			),
			'nonce'           => wp_create_nonce( 'affiliate_review_with_ai' ),
			'hash'            => md5( wp_unique_id( $licensing->get_license_key() ) ),
			'ajaxURL'         => untrailingslashit( admin_url( 'admin-ajax.php' ) ),
			'siteURL'         => home_url(),
			'language'        => wp_get_available_translations()[ get_locale() ]['english_name'] ?? 'english',
			'hasValidLicense' => $licensing->is_license_valid(),

			// Caches.
			'useCaches'       => defined( 'AFFILIATEWP_AI_PROXY_DISABLE_CACHES' ) && AFFILIATEWP_AI_PROXY_DISABLE_CACHES
				? false
				: true,

			// Translations.
			'i18n'            => [
				'ajaxError'      => __( 'There was an error, please try again later.', 'affiliate-wp' ),
				'invalidLicense' => __( 'You must have an active AffiliateWP license to use this feature.', 'affiliate-wp' ),

				// Modals.
				'modals'         => [

					// Invalid License Check.
					'invalidLicense' => [
						'title'       => __( "We couldn't verify your license", 'affiliate-wp' ),
						'description' => __( 'Please check that your license key is properly activated in your settings, or consider purchasing AffiliateWP.' ),
						'buttons' => [
							'settings' => [
								'title' => __( 'Settings', 'affiliate-wp' ),
							],
							'purchase' => [
								'title' => __( 'Purchase AffiliateWP' ),
							],
							'help' => [
								'title' => __( 'Contact Support', 'affiliate-wp' ),
							],
						],
					],

					// Monthly Credits Plan.
					'purchaseMonthly' => [

						'title' => ( function() : string {

							ob_start();

							Icons::render( 'sparkles' );

							esc_html_e( 'Review Affiliate Applications with AI', 'affiliate-wp' );

							return ob_get_clean();
						} )(),

						'description' => ( function() : string {

							ob_start();

							?>

							<p><?php esc_html_e( 'Unlock the power of AI to efficiently manage your affiliate applications.', 'affiliate-wp' ); ?></p>

							<ul class="affwp-feature-benefits">
								<li><?php esc_html_e( 'Save time with AI reviewing your affiliates.', 'affiliate-wp' ); ?></li>
								<li><?php esc_html_e( 'Ensure consistent and fair affiliate application reviews.', 'affiliate-wp' ); ?></li>
								<li><?php esc_html_e( 'Quickly identify and approve top affiliates.', 'affiliate-wp' ); ?></li>
							</ul>

							<?php

							return ob_get_clean();
						} )(),

						'buttons'     => [
							'purchase' => [
								'title' => __( 'Get AI Access', 'affiliate-wp' ),
							],
						],
					],

					// Topup Credits.
					'purchaseTopup' => [

						'title'       => ( function() : string {

							ob_start();

							esc_html_e( 'AI Review Limit Reached', 'affiliate-wp' );

							return ob_get_clean();
						} )(),

						'description' => __( 'You have reached the limit of AI affiliate reviews for your current plan.', 'affiliate-wp' ),

						'buttons'     => [
							'purchase' => [
								'title' => __( 'Learn More', 'affiliate-wp' ),
							],
						],
					],

					// More Credit Spending Confirmation.
					'requiredCredits' => [

						'title'       => __( 'Heads Up!', 'affiliate-wp' ),
						'description' => __( 'This review may use up more AI credits than usual, are you sure you want to use ${credits} AI credits for this application?', 'affiliate-wp' ),

						'buttons'     => [
							'yes' => [
								'title' => __( 'Yes, use ${credits} credits', 'affiliate-wp' ),
							],
							'no' => [
								'title' => __( 'Cancel', 'affiliate-wp' ),
							],
						],
					],
				],
			],
		]
	);
}

/**
 * (AJAX) Log Affiliate Review with AI Usage.
 *
 * @since 2.26.0
 *
 * @return void
 */
function ajax_log_affiliate_review_ai_usage() : void {

	if ( ! wp_verify_nonce( $_REQUEST['_ajax_nonce'] ?? '', 'affiliate_review_with_ai' ) ) {

		wp_send_json_error(
			[
				'reason' => 'invalid_nonce',
			]
		);

		exit;
	}

	$data = filter_input( INPUT_POST, 'response', FILTER_DEFAULT, FILTER_REQUIRE_ARRAY );

	if ( ! is_array( $data ) ) {

		wp_send_json_error(
			[
				'reason' => 'bad_array_response',
			]
		);

		exit;
	}

	// First log the usage...
	log_ai_usage( 'requests', 1 );
	log_ai_usage( 'credits_used', $data['usage']['credits']['used'] ?? 0 );
	log_ai_usage( 'tokens_used', $data['usage']['tokens']['total'] ?? 0 );
	log_ai_usage( 'words', $data['usage']['words']['total'] ?? 0 );
	log_ai_usage( 'characters', $data['usage']['words']['total_characters'] ?? 0 );

	// Log the amount of things.
	log_ai_amount( 'available_credits', $data['available_credits'] ?? 0 );
	log_ai_amount( 'available_applications', $data['available_applications'] ?? 0 );

	// Note when these started.
	log_ai_first( 'ai_request', time() );
	log_ai_first( 'application_review', time() );
}

/**
 * Get the first time something was used.
 *
 * @since 2.26.0
 *
 * @param string $option The name of the thing you want, e.g. ai_request, etc.
 *
 * @return int The timestamp (epoch) when it was first used.
 */
function get_ai_first( string $option ) : int {

	$option = get_option( get_ai_prefixed_usage_option_key( $option, 'first' ), false );

	if ( is_numeric( $option ) ) {
		return floatval( $option );
	}

	return 0;
}

/**
 * Log an amount.
 *
 * @since 2.26.0
 *
 * @param string $option The name of the thing you want to log.
 * @param float  $amount The amount of something.
 */
function log_ai_amount( string $option, float $amount ) : void {

	update_option(
		get_ai_prefixed_usage_option_key( $option, 'amount' ),
		number_format( floatval( $amount ), 2 ),
		false
	);
}

/**
 * Get an amount.
 *
 * @since 2.26.0
 *
 * @param string $option The name of the thing you want, e.g. ai_request, etc.
 *
 * @return float The amount.
 */
function get_ai_amount( string $option ) : float {

	$option = get_option( get_ai_prefixed_usage_option_key( $option, 'amount' ), false );

	if ( is_numeric( $option ) ) {
		return floatval( $option );
	}

	return 0;
}

/**
 * Log the first time something happened.
 *
 * @since 2.26.0
 *
 * @param string $option The name of the thing you want to log.
 */
function log_ai_first( string $option ) : void {

	if ( has_ai_first_logged( $option ) ) {
		return; // Already logged.
	}

	update_option(
		get_ai_prefixed_usage_option_key( $option, 'first' ),
		time(), // Store a timestamp.
		false
	);
}

/**
 * Has the time something was done first been logged already?
 *
 * @since 2.26.0
 *
 * @param string $option The name of the thing we are logging.
 *
 * @return bool
 */
function has_ai_first_logged( string $option ) : bool {
	return is_numeric( get_option( get_ai_prefixed_usage_option_key( $option, 'first' ), false ) );
}

/**
 * The Option Key for Usage Loging (Prefix)
 *
 * phpcs:disable PEAR.Functions.FunctionCallSignature.CloseBracketLine
 * phpcs:disable PEAR.Functions.FunctionCallSignature.MultipleArguments
 * phpcs:disable PEAR.Functions.FunctionCallSignature.ContentAfterOpenBracket
 *
 * @since 2.26.0
 */
function get_ai_usage_prefix_key() : string {
	return 'affiliatewp_ai_usage';
}

/**
 * Log AI Usage
 *
 * @since 2.26.0
 *
 * @param string $option The name of the item you want to log.
 * @param float  $value  The value of the item you want to log.
 */
function log_ai_usage( string $option, float $value = 0 ) : void {

	$default_usage = get_ai_usage_defaults();

	$current_usage = get_ai_usage( $option );

	if ( ! is_array( $current_usage ) ) {

		$current_usage = array_merge(
			$default_usage,
			[
				'start_time' => time(), // Reset the start time.
				'corrupted'  => true, // So we can ignore this data if we want.
			]
		);
	}

	$usage = floatval(
		( $current_usage['total_usage'] ?? 0 )
	)
		+ floatval( $value );

	$counter = ( $current_usage['counter'] ?? 0 ) + 1;

	// Update usage for next time...
	set_ai_usage(
		$option,
		array_merge(
			$current_usage, // Keep any extra data (defaults set by set_usage()).

			// Set this data to new values.
			[
				'counter'     => $counter,

				// Make sure these numbers don't get too long by making them 0.00 decimals.
				'total_usage' => number_format( $usage, 2 ),
				'average'     => number_format( $usage / $counter, 2 ),
			]
		)
	);
}

/**
 * Loging Defaults
 *
 * These are the value points we log for each type of thing
 * we log.
 *
 * @since 2.26.0
 *
 * @return array
 */
function get_ai_usage_defaults() : array {

	// Please keep these in sync with log_usage().
	return [
		'counter'     => 0,
		'total_usage' => 0,
		'average'     => 0,
		'start_time'  => time(),
		'corrupted'   => false,
	];
}

/**
 * Get Usage
 *
 * Can be:
 *
 * - requests
 * - credits_used
 * - tokens_used
 * - words
 * - characters
 *
 * Each of these should contain:
 *
 * - counter: A general counter of how many times the item has been used
 * - total_usage: A count of all the usage, e.g. for words it will === the total words ever used
 * - average: Same as total usage, but an average
 * - start_time: The time we started loging the item (epoch timestamp)
 * - corrupted: Set to true if at some point the loging data (serialized) was corrupted so we can maybe ignore it
 *
 * @since 2.26.0
 *
 * @param string $option The item you want usage from from the options table (note already prefixed),
 *                       e.g. `requests` or `words`.
 *
 * @return array Usage data for that item.
 */
function get_ai_usage( string $option ) : array {

	$defaults = get_ai_usage_defaults();

	$option = get_option(
		get_ai_prefixed_usage_option_key( $option, 'proxy' ),
		$defaults
	);

	return is_array( $option )
		? $option
		: $defaults;
}

/**
 * Set usage in the database.
 *
 * @since 2.26.0
 *
 * @param string $option     The item you are loging.
 * @param array  $usage_data The data you are storing for loging.
 */
function set_ai_usage( string $option, array $usage_data ) : void {

	update_option(
		get_ai_prefixed_usage_option_key( $option, 'proxy' ),
		array_merge(
			get_ai_usage_defaults(), // Make sure default data is set.
			$usage_data // Override with data sent.
		),
		false
	);
}

/**
 * Get a prefixed option key for usage.
 *
 * @since 2.26.0
 *
 * @param string $option  The item you are logging (that ends up in the options table).
 * @param string $type    The type of usage.
 *
 * @return string A prefixed option key for you to use.
 */
function get_ai_prefixed_usage_option_key( string $option, string $type ) : string {

	$usage_prefix = get_ai_usage_prefix_key();

	return "{$usage_prefix}_{$type}_{$option}";
}

/**
 * (AJAX) Hash moderation data for AI passage.
 *
 * @since 2.26.0
 */
function ajax_secure_affiliate_review_data() : void {

	if ( ! wp_verify_nonce( $_REQUEST['_ajax_nonce'] ?? '', 'affiliate_review_with_ai' ) ) {

		wp_send_json_error(
			[
				'reason' => 'invalid_nonce',
			]
		);

		exit;
	}

	$affiliate_id = intval( $_REQUEST['affiliate_id'] ?? 0 );

	if ( ! affwp_get_affiliate( $affiliate_id ) ) {

		wp_send_json_error(
			[
				'reason' => 'affiliate_does_not_exist',
			]
		);

		exit;
	}

	$promotional_methods = affwp_get_affiliate_meta( $affiliate_id, 'promotion_method', true );

	// Initial application data.
	$application_data = empty( $promotional_methods ) ? [] : [
		'Promotion Methods they will use' => $promotional_methods, // Yes, this value is hard-coded and not translated.
	];

	// Add any custom fields from registration blocks.
	foreach ( affwp_get_custom_registration_fields( $affiliate_id, true ) as $data ) {

		if ( ! isset( $data['name'], $data['meta_value'] ) ) {
			continue;
		}

		if ( ! is_string( $data['meta_value'] ) ) {
			continue;
		}

		$application_data[ $data['name'] ] = $data['meta_value'];
	}

	// secure the data for transaction with the AI Proxy.
	$secure_json = openssl_encrypt(
		wp_json_encode(
			array_merge(
				[
					'license_key'            => \AffWP\Core\License\License_Data::get_license_key(),
					'instructions'           => affiliate_wp()->settings->get( 'review_with_ai_instructions', '' ),
					'application_data'       => $application_data,
				],

				// Allow defining the prompt manually for development...
				defined( 'AFFILIATEWP_REVIEW_WITH_AI_PROXY_MOCK_PROMPT' )
					? AFFILIATEWP_REVIEW_WITH_AI_PROXY_MOCK_PROMPT
					: []
			)
		),
		'aes-128-cbc',
		$_REQUEST['hash'] ?? '',
		0,
		str_pad( substr( $_REQUEST['hash'] ?? '', 0, 16 ), 16, '0' )
	);

	if ( false === $secure_json ) {

		wp_send_json_error(
			[
				'reason' => 'unable_to_secure_json',
			]
		);

		exit;
	}

	wp_send_json_success(
		[
			// Secured JSON to send to the Proxy AI API.
			'secureJSON' => $secure_json,
		],
	);

	exit;
}

/**
 * Settings for AI Affiliate Moderation
 *
 * @since 2.26.0
 *
 * @return array
 */
function get_ai_settings() : array {

	return [
		'review_with_ai_instructions' => array(
			'name'     => __( 'AI Review Instructions', 'affiliate-wp' ),
			'desc'     => __( 'Additional criteria for reviewing pending affiliate applications with AI.', 'affiliate-wp' ),
			'type'     => 'textarea',
			'disabled' => false,
		),
	];
}

/**
 * Get AI Agreements and Disagreements
 *
 * @since 2.26.0
 *
 * @return array {
 *    @type int $agreements    How many times the user has agreed with AI.
 *    @type int $disagreements How many times the user has disagreed with AI.
 * }
 */
function get_ai_agreements_disagreements() : array {

	return [
		'agreements'    => intval( get_option( get_ai_prefixed_usage_option_key( 'agreements', 'interaction' ), 0 ) ),
		'disagreements' => intval( get_option( get_ai_prefixed_usage_option_key( 'disagreements', 'interaction' ), 0 ) ),
	];
}

/**
 * Get information about users interaction with AI for an Affiliate Review.
 *
 * @param integer $affiliate_id The Affiliate's ID.
 *
 * @since 2.26.0
 *
 * @return array {
 *     @type int    $affiliate_id         The Affiliate's ID.
 *     @type string $ai_decision_status   The status from AI (accepted/rejected).
 *     @type string $ai_decision_reason   The reason for choosing the status.
 *     @type string $user_decision_status The decision the user made.
 *     @type string $_rejection_reason    The rejection message if the user rejected the affiliate.
 *     @type string $application_data     What fields that were sent to AI to make a decision.
 * }
 */
function get_ai_review_data_for_affiliate( int $affiliate_id ) : array {

	return [
		'affiliate_id'         => $affiliate_id,
		'affiliate_name'       => affiliate_wp()->affiliates->get_affiliate_name( $affiliate_id ),
		'ai_decision_status'   => affwp_get_affiliate_meta( $affiliate_id, 'ai_decision_status', true ),
		'ai_decision_reason'   => affwp_get_affiliate_meta( $affiliate_id, 'ai_decision_reason', true ),
		'user_decision_status' => affwp_get_affiliate_meta( $affiliate_id, 'user_decision_status', true ),
		'_rejection_reason'    => affwp_get_affiliate_meta( $affiliate_id, '_rejection_reason', true ),
		'application_data'     => array_merge(
			[
				'affwp_promotion_method' => affwp_get_affiliate_meta( $affiliate_id, 'promotion_method', true ),
			],
			affwp_get_custom_registration_fields( $affiliate_id ),
		),
	];
}
