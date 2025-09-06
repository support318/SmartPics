<?php
/**
 * Emails: Functions
 *
 * @package     AffiliateWP
 * @subpackage  Emails
 * @copyright   Copyright (c) 2015, Sandhills Development, LLC
 * @license     http://opensource.org/licenses/gpl-2.0.php GNU Public License
 * @since       1.6
 */

// phpcs:disable PEAR.Functions.FunctionCallSignature.EmptyLine -- Formatting this was is ok here.
// phpcs:disable PEAR.Functions.FunctionCallSignature.FirstArgumentPosition -- Formatting this was is ok here.
// phpcs:disable WordPress.Arrays.MultipleStatementAlignment.DoubleArrowNotAligned -- WPCS is throwing this warning inaccurately.

// Exit if accessed directly
if( ! defined( 'ABSPATH' ) ) exit;

/**
 * Get a list of available email templates
 *
 * @since 1.6
 * @return array
 */
function affwp_get_email_templates() {
	return affiliate_wp()->emails->get_templates();
}

/**
 * Get a formatted HTML list of all available tags
 *
 * @since 1.6
 * @return string $list HTML formated list
 */
function affwp_get_emails_tags_list() {
	// The list
	$list = '';

	// Get all tags
	$email_tags = affiliate_wp()->emails->get_tags();

	// Check
	if( count( $email_tags ) > 0 ) {
		foreach( $email_tags as $email_tag ) {
			$list .= '{' . $email_tag['tag'] . '} - ' . $email_tag['description'] . '<br />';
		}
	}

	// Return the list
	return $list;
}


/**
 * Email template tag: name
 * The affiliate's name
 *
 * @param int $affiliate_id
 * @return string name
 */
function affwp_email_tag_name( $affiliate_id = 0 ) {
	return affiliate_wp()->affiliates->get_affiliate_name( $affiliate_id );
}


/**
 * Email template tag: username
 * The affiliate's username on the site
 *
 * @param int $affiliate_id
 * @return string username
 */
function affwp_email_tag_user_name( $affiliate_id = 0 ) {
	$user_info = get_userdata( affwp_get_affiliate_user_id( $affiliate_id ) );

	return $user_info->user_login;
}


/**
 * Email template tag: user_email
 * The affiliate's email
 *
 * @param int $affiliate_id
 * @return string email
 */
function affwp_email_tag_user_email( $affiliate_id = 0 ) {
	return affwp_get_affiliate_email( $affiliate_id );
}


/**
 * Email template tag: website
 * The affiliate's website
 *
 * @param int $affiliate_id
 * @return string website
 */
function affwp_email_tag_website( $affiliate_id = 0 ) {
	$user_info = get_userdata( affwp_get_affiliate_user_id( $affiliate_id ) );

	return $user_info->user_url;
}


/**
 * Email template tag: promo_method
 * The affiliate promo method
 *
 * @param int $affiliate_id
 * @return string promo_method
 */
function affwp_email_tag_promo_method( $affiliate_id = 0 ) {
	return get_user_meta( affwp_get_affiliate_user_id( $affiliate_id ), 'affwp_promotion_method', true );
}

/**
 * Email template tag: affwp_email_tag_rejection_reason
 * The affiliate rejection reason
 *
 * @param int $affiliate_id Affiliate ID.
 * @return string rejection_reason
 */
function affwp_email_tag_rejection_reason( $affiliate_id ) {
	$reason = affwp_get_affiliate_meta( $affiliate_id, '_rejection_reason', true );
	if( empty( $reason ) ) {
		$reason = '';
	}
	return $reason;
}


/**
 * Email template tag: login_url
 * The affiliate login URL
 *
 * @return string login_url
 */
function affwp_email_tag_login_url() {
	return esc_url( affiliate_wp()->login->get_login_url() );
}


/**
 * Email template tag: amount
 * The amount of an affiliate transaction
 *
 * @return string amount
 */
function affwp_email_tag_amount( $affiliate_id, $referral ) {
	return html_entity_decode( affwp_currency_filter( $referral->amount ), ENT_COMPAT, 'UTF-8' );
}


/**
 * Email template tag: sitename
 * Your site name
 *
 * @return string sitename
 */
function affwp_email_tag_site_name() {
	return wp_specialchars_decode( get_bloginfo( 'name' ), ENT_QUOTES );
}

/**
 * Email template tag: referral URL
 * Affiliate's referral URL
 *
 * @return string referral_url
 */
function affwp_email_tag_referral_url( $affiliate_id = 0 ) {
	return affwp_get_affiliate_referral_url( array( 'affiliate_id' => $affiliate_id ) );
}

/**
 * Email template tag: affiliate ID
 * Affiliate's ID
 *
 * @return int affiliate ID
 */
function affwp_email_tag_affiliate_id( $affiliate_id = 0 ) {
	return $affiliate_id;
}

/**
 * Email template tag: referral rate
 * The affiliate's referral rate as shown from Affiliate -> Affiliates
 *
 * @since 1.9
 * @return string referral_rate
 */
function affwp_email_tag_referral_rate( $affiliate_id = 0 ) {
	return affwp_get_affiliate_rate( $affiliate_id, true );
}

/**
 * Is Affiliate Email Summaries enabled without WP Mail SMTP configured?
 *
 * @since 2.9.7
 *
 * @return bool
 */
function affwp_affiliate_email_summaries_enabled_without_wp_mail_smtp() {
	return true === affwp_is_affiliate_email_summaries_enabled()
		&& true !== affwp_is_wp_mail_smtp_configured();
}

/**
 * Is the setting for Affiliate Email Summaries enabled (checked)?
 *
 * @since 2.9.7
 *
 * @return bool
 */
function affwp_is_affiliate_email_summaries_enabled() {

	$settings = get_option( 'affwp_settings', false );

	if ( ! is_array( $settings ) ) {
		return false;
	}

	return isset( $settings['affiliate_email_summaries'] ) &&
		true === (bool) $settings['affiliate_email_summaries'];
}

/**
 * Email template tag: review URL
 * Affiliate's review page URL
 *
 * @since 1.9
 * @return string URL to the review page
 */
function affwp_email_tag_review_url( $affiliate_id = 0 ) {
	return affwp_admin_url( 'affiliates', array( 'affiliate_id' => absint( $affiliate_id ), 'action' => 'review_affiliate' ) );
}

/**
 * Email template tag: registration_coupon
 * The affiliate's registration coupon
 *
 * @since 2.6
 *
 * @param int $affiliate_id Affiliate ID.
 * @return string Affiliate registration coupon, or empty string if none.
 */
function affwp_email_tag_registration_coupon( $affiliate_id = 0 ) {
	$coupon_code = '';

	$coupons = affwp_get_dynamic_affiliate_coupons( $affiliate_id, false );

	if ( ! empty( $coupons ) ) {
		$coupon = reset( $coupons );

		$coupon_code = affwp_get_affiliate_coupon_code( $affiliate_id, $coupon->coupon_id );
	}

	return $coupon_code;
}

/**
 * Get the landing page of the referral
 *
 * @since 1.9
 * @return string URL to the landing page
 */
function affwp_email_tag_get_landing_page( $affiliate_id, $referral ) {
    return esc_url( affiliate_wp()->visits->get_column_by( 'url', 'visit_id', $referral->visit_id ) );
}

/**
 * Gets the campaign (if set) of the referral.
 *
 * @since 1.9.4
 *
 * @param int             $affiliate_id Affiliate ID.
 * @param \AffWP\Referral $referral     Referral object.
 * @return string Referral campaign, or (no campaign) if none.
 */
function affwp_email_tag_campaign_name( $affiliate_id, $referral ) {
	return empty( $referral->campaign ) ? __( '(no campaign)', 'affiliate-wp' ) : esc_html( $referral->campaign );
}

/**
 * Determine if New Referral Notifications can be sent to the affiliate
 *
 * @since 2.2
 * @uses affwp_email_notification_enabled()
 * @param int $affiliate_id The affiliate's ID
 *
 * @return boolean True if new referral notifications are enabled, false otherwise.
 */
function affwp_email_referral_notifications( $affiliate_id = 0 ) {

	$enabled = false;

	if ( true === affwp_email_notification_enabled( 'affiliate_new_referral_email', $affiliate_id ) ) {
		$enabled = true;
	}

	return (bool) $enabled;

}

/**
 * Determine if a specific email notification is enabled.
 *
 * @since 2.2
 * @param string $email_notification The email notification to check.
 * @param int $affiliate_id The affiliate's ID
 *
 * @return boolean True if the email notification is enabled, false otherwise.
 */
function affwp_email_notification_enabled( $email_notification = '', $affiliate_id = 0 ) {

	$enabled = false;

	if ( array_key_exists( $email_notification, affwp_get_enabled_email_notifications() ) ) {
		$enabled = true;
	}

	/**
	 * Filters whether the email notification is enabled.
	 *
	 * @since 2.2
	 *
	 * @param bool   $enabled            Whether the email notification is enabled.
	 * @param string $email_notification Email notification slug.
	 * @param int    $affiliate_id       Affiliate ID.
	 */
	return (bool) apply_filters( 'affwp_email_notification_enabled', $enabled, $email_notification, $affiliate_id );
}

/**
 * Get the email notifications settings array.
 *
 * @since 2.2
 *
 * @return array $email_notifications
 */
function affwp_get_enabled_email_notifications() {

	$email_notifications = affiliate_wp()->settings->get( 'email_notifications' );

	if ( is_array( $email_notifications ) ) {
		return $email_notifications;
	}

	// Return empty array.
	return array();

}

/**
 * Get the latest unsent DYK blurb.
 *
 * @since 2.9.6
 *
 * @return array Empty if we could not get them.
 *
 * @TODO Cache this data so we can send unsent blurbs in the case of
 *       it not being available.
 */
function affwp_get_latest_unsent_dyk_blurb_for_my_license() {

	$response = wp_remote_get(
		defined( 'AFFWP_EMAIL_SUMMARIES_JSON' )
			? esc_url( AFFWP_EMAIL_SUMMARIES_JSON )
			: 'https://affiliatewp.com/wp-content/email-summaries.json',
		array(
			'sslverify' => defined( 'AFFILIATE_WP_DEBUG' ) && AFFILIATE_WP_DEBUG
				? false // We won't SSLVERIFY on local dev.
				: true, // But we will, in production.
		)
	);

	if ( is_wp_error( $response ) ) {
		return array();
	}

	if ( 200 !== absint( wp_remote_retrieve_response_code( $response ) ) ) {
		return array();
	};

	$json = wp_remote_retrieve_body( $response );

	if ( ! is_string( $json ) || empty( $json ) ) {
		return array();
	}

	$blurbs = json_decode( $json, true );

	if ( ! is_array( $blurbs ) ) {
		return array();
	}

	// Get ready to sort them by ID...
	$blurbs_by_id = array();

	// We'll need sent blurbs so we don't re-send any...
	$sent_blurbs = affwp_get_sent_dyk_blurbs();

	// We also want to just send blurbs important for that user's license...
	$license = new AffWP\Core\License\License_Data();

	foreach ( $blurbs as $blurb ) {

		if ( ! isset(

			// These are required at a minimum.
			$blurb['id'],
			$blurb['title']
		) ) {
			continue;
		}

		if (

			// You are trying to focus this DYK blurb to a license(s).
			isset( $blurb['type'] ) && is_array( $blurb['type'] ) && ! empty( $blurb['type'] ) &&

			// And your license is not in the array.
			! in_array( strtolower( $license->get_license_type( $license->get_license_id() ) ), $blurb['type'], true )
		) {
			continue; // Not for you.
		}

		if ( in_array( absint( $blurb['id'] ), $sent_blurbs, true ) ) {
			continue; // Already sent this one.
		}

		// Not a sent blurb.
		$blurbs_by_id[ $blurb['id'] ] = $blurb;
	}

	// Sort by ID so we get the latest...
	ksort( $blurbs_by_id );

	// Send back the top-most.
	return current( $blurbs_by_id );
}

/**
 * Get sent DYK Blurbs.
 *
 * @since 2.9.6
 *
 * @return array
 */
function affwp_get_sent_dyk_blurbs() {

	$sent_blurbs = get_option( 'affwp_emailed_dyk_blurbs', array() );

	if ( ! is_array( $sent_blurbs ) ) {
		return array(); // Something broke, reset blurbs.
	}

	// Sanitize blurbs (list of ID's).
	return array_map(
		function( $blurb_id ) {
			return absint( $blurb_id );
		},
		$sent_blurbs
	);
}

/**
 * Remember a DYK blurb that we sent.
 *
 * @since 2.9.6
 *
 * @param  int $blurb_id The Blurb ID from JSON.
 * @return bool          False if you didn't pass a proper ID.
 */
function affwp_add_sent_dyk_blurb( $blurb_id ) {

	$blurb_id = absint( $blurb_id );

	if ( 0 === $blurb_id ) {
		return false; // Can't add a nothing blurb.
	}

	$sent_blurbs = affwp_get_sent_dyk_blurbs();

	$sent_blurbs[] = absint( $blurb_id );

	/*
	 * We should always send a DYK blurb that isn't in this option (array),
	 * so update_option() should always return true (new value added).
	 */
	return update_option(
		'affwp_emailed_dyk_blurbs',
		array_map(
			// Sanitize (out) blurbs (list of ID's).
			function( $blurb_id ) {
				return absint( $blurb_id );
			},
			$sent_blurbs
		),
		false
	);
}

/**
 * Schedule an email summary.
 *
 * @since 2.9.7
 *
 * @param  string $name       See affwp_email_summary().
 * @param  string $to         See affwp_email_summary().
 * @param  string $subject    See affwp_email_summary().
 * @param  string $email_body See affwp_email_summary().
 * @param  string $data       See affwp_email_summary().
 * @param  string $template   See affwp_email_summary().
 * @param  string $timestamp  Timestamp of when to send it with Action Scheduler.
 * @return bool               See affwp_email_summary().
 */
function affwp_schedule_summary(
	$name,
	$to,
	$subject,
	$email_body,
	$data = null,
	$template = 'summaries',
	$timestamp = 0
) {

	if ( ! function_exists( 'as_schedule_single_action' ) ) {

		affiliate_wp()->utils->log(
			sprintf(

				// Translators: %1$s is the name of the summary.
				__( 'Could not schedule %1$s email summary because we could not find the function as_schedule_single_action().' ),
				$name
			)
		);

		return false;
	}

	// Schedule a one-time summary to be send when indicated, and return if it was scheduled or not.
	return 0 === as_schedule_single_action(
		0 === $timestamp
			? time() // Send now.
			: $timestamp, // Send when scheduled.
		'affwp_send_scheduled_summary',
		array(
			$name,
			$to,
			$subject,
			$email_body,
			$data,
			$template,
		),
		'affiliatewp' // Action scheduler group.
	)
		? false // It wasn't scheduled for some reason.
		: true; // It was scheduled.
}

/**
 * Send an email summary.
 *
 * @param  string $name       Name of the email summary.
 * @param  string $to         To.
 * @param  string $subject    Subject.
 * @param  string $email_body Content.
 * @param  mixed  $data       Any data associated with the email.
 * @param  bool   $preview    Set to true to preview email instead.
 * @param  string $template   Template.
 * @return bool               True if it was emailed.
 */
function affwp_email_summary(
	$name,
	$to,
	$subject,
	$email_body,
	$data = null,
	$preview = false,
	$template = 'summaries'
) {

	if ( $preview ) {
		check_admin_referer( 'preview_email_summary', '_wpnonce' );
	}

	$emailer = new Affiliate_WP_Emails();

	$emailer->template = $template;

	// Send context to our template filters.
	set_query_var(
		'context',
		array(
			'name'     => $name, // The most important part.
			'to'       => $to,
			'template' => $template,
			'data'     => $data,
		)
	);

	if ( $preview ) {

		/**
		 * This filter is documented below (same as filtering the email body below).
		 *
		 * @since 2.9.6
		 */
		die( apply_filters( "affwp_notify_{$name}_summary_body", $emailer->build_email( $email_body ) ) ); // phpcs:ignore -- Okay to die here without escaping.
	}

	$sent = $emailer->send(

		/**
		 * Filter who the Monthly Summary Emails are sent to.
		 *
		 * Default to admin email if they haven't set a separate manager email set.
		 *
		 * @since 2.9.6
		 *
		 * @param string $email The email address.
		 */
		apply_filters( "affwp_notify_{$name}_summary_to_email", $to ),

		/**
		 * Filter the subject of the Monthly Summary Report.
		 *
		 * @since 2.9.6
		 *
		 * @param string $subject Subject.
		 */
		apply_filters( "affwp_notify_{$name}_summary_email_subject", $subject ),

		/**
		 * Filter the content of the Monthly Summary.
		 *
		 * @since 2.9.6
		 *
		 * @param string $body Email body.
		 */
		apply_filters( "affwp_notify_{$name}_summary_email_body", $email_body )
	);

	/**
	 * When the Sumamry Email is sent.
	 *
	 * @since 2.9.6
	 *
	 * @param string $sent Sent status.
	 * @param mixed  $data Any data related to this email.
	 */
	do_action( "affwp_notify_{$name}_summary_email_sent", $sent, $data );

	return $sent;
}

/**
 * Are we previewing an email summary?
 *
 * @param  string $name Name of email summary.
 * @return bool
 */
function affwp_is_summary_email_preview( $name ) {

	if ( ! is_string( $name ) ) {
		return false;
	}

	return (
		is_admin() && // Within the admin.
		isset( $_GET['preview'] ) && // phpcs:ignore WordPress.Security.NonceVerification.Recommended -- We're not using the data.
		isset( $_GET[ "affwp_notify_{$name}_summary" ] ) && // phpcs:ignore WordPress.Security.NonceVerification.Recommended -- We're not using the data.
		isset( $_GET['_wpnonce'] ) && // phpcs:ignore WordPress.Security.NonceVerification.Recommended -- We're not using the data.
		current_user_can( 'manage_affiliate_options' ) // Only admins can see the preview.
	);
}

if ( ! function_exists( 'affwp_get_monthly_affiliate_email_summary_content' ) ) :

	/**
	 * Get the Monthly Affiliate Report Markup (Content).
	 *
	 * If you want to custimize anything here, simply create this function elsewhere and
	 * override it with your own content.
	 *
	 * @since 2.9.7
	 *
	 * @param  int   $affiliate_id The affiliate to show data for,
	 *                             -1 means we must be previewing the content.
	 * @param  array $timeframe {
	 *     Timeframe.
	 *     @type string $start Start time in Y-m-d format.
	 *     @type string $end   End time in Y-m-d format.
	 *
	 *     Might look like:
	 *      'start' => string '2022-08-15'
	 *      'end' => string '2022-09-14'
	 * }
	 * @return string HTML Markup for email body.
	 *
	 * @see templates/dashboard-tab-stats.php Will present similar data here.
	 */
	function affwp_get_monthly_affiliate_email_summary_content( $affiliate_id, $timeframe ) {

		if ( ! is_numeric( $affiliate_id ) ) {

			affiliate_wp()->utils->log(
				sprintf(

					// Translators: %1$s is the ID that is bad.
					__( 'Affiliate monthly summary content for affiliate with ID %1$s was not sent because it is not a valid Affiliate ID.' ),
					$affiliate_id
				)
			);

			return ''; // Can't send anything to an affiliate w/out an ID.
		}

		// phpcs:ignore WordPress.Security.NonceVerification.Recommended -- We're just accessing it.
		if ( isset( $_GET['affiliate_id'] ) && is_numeric( $_GET['affiliate_id'] ) ) {

			// phpcs:ignore WordPress.Security.NonceVerification.Recommended -- For debugging only, might remove later.
			$affiliate_id = absint( $_GET['affiliate_id'] ); // Used to preview a specific affiliate's data.
		}

		// Affiliate name for the email content.
		$affiliate_name = -1 === $affiliate_id
			? __( 'John', 'affiliate-wp' ) // For previewing.
			: affwp_get_affiliate_first_name( $affiliate_id );

		// Get the user's top converting URLs.
		$top_converted_visits = affwp_get_affiliate_top_visit_urls(
			$affiliate_id,
			$timeframe,
			'converted',
			10
		);

		$intro = sprintf(

			// Translators: %1$s is the display-name of the affiliate.
			__( 'Hi %1$s,', 'affiliate-wp' ),

			// %$1s
			empty( $affiliate_name )

				// There is a chance the affiliate hasn't set their first name yet, show "Hi there," instead.
				? _x( 'there', 'affiliate-wp' )
				: $affiliate_name
		);

		$intro2 = sprintf(

			// Translators: %1$s is the name of this website.
			__( 'Here\'s how you\'ve performed on %1$s in the last 30 days.', 'affiliate-wp' ),
			sprintf(
				'<strong>%1$s</strong>',
				str_replace(
					array( 'https://', 'http://' ),
					'', // Replace with nothing.
					get_option( 'home' )
				)
			)
		);

		$total_earnings = affwp_currency_filter(
			affwp_format_amount(

				// Unpaid earnings.
				affiliate_wp()->referrals->unpaid_earnings(
					$timeframe,
					$affiliate_id,
					false
				)
				+

				// Plus Paid earnings.
				affiliate_wp()->referrals->paid_earnings(
					$timeframe,
					$affiliate_id,
					false
				)
			)
		);

		$total_referrals = affiliate_wp()->referrals->get_referrals(
			array(
				'number'       => -1,
				'affiliate_id' => $affiliate_id,
				'date'         => $timeframe,
				'fields'       => 'ids',
				'status'       => array(
					'paid',
					'unpaid',
				),
			),
			true // Just the count.
		);

		$total_visits = affwp_count_visits( $affiliate_id, $timeframe );

		$conversion_rate = affwp_get_affiliate_conversion_rate(
			$affiliate_id,
			$timeframe
		);

		if (

			// If they selected none, use plain text...
			'none' === affiliate_wp()->settings->get( 'email_template' ) &&

			// But never use this content when previewing.
			! affwp_is_summary_email_preview( 'monthly_affiliate_email' )
		) {

			/*
			 * Plain Text Version
			 *
			 * Note, spacing is important here.
			 */
			ob_start();

			?>

			<?php echo esc_html( $intro ); ?>


			<?php echo esc_html( $intro2 ); ?>


			Total Earnings: <?php echo esc_html( $total_earnings ); ?>

			Total Referrals: <?php echo esc_html( $total_referrals ); ?>

			Total Visits: <?php echo esc_html( $total_visits ); ?>

			Conversion Rate: <?php echo esc_html( $conversion_rate ); ?>


			<?php if ( ! empty( $top_converted_visits ) || -1 === $affiliate_id ) : ?>

				<?php echo esc_html( __( 'Top 10 Highest Converting URLs:', 'affiliate-wp' ) ); ?>

				<?php foreach ( $top_converted_visits as $url => $row ) : ?>

					<?php echo empty( $url ) ? esc_html__( 'Unknown', 'affiliate-wp' ) : esc_url( $url ); ?>

					Referrals: <?php echo isset( $row['visits'] ) ? absint( $row['visits'] ) : 0; ?>

					Visits: <?php echo absint( affwp_get_affiliate_total_visits_to_url( $url, $affiliate_id, 'today - 30 days' ) ); ?>

				<?php endforeach; ?>
			<?php endif; // Top Visits. ?>
			<?php

			return mb_convert_encoding(
				str_replace( "\t", '', ob_get_clean() ),
				'UTF-8',
				'HTML-ENTITIES'
			);
		}

		/*
		 * HTML Version.
		 */
		ob_start();

		?>

		<div class="content">

			<p style="text-align: left; font-weight: bold;">
				<?php

				echo esc_html(
					$intro
				);

				?>
			</p>

			<p style="text-align: left; margin-bottom: 30px;">
				<?php

				echo wp_kses(
					$intro2,
					array(
						'strong' => true,
					)
				);

				?>
			</p>

			<h2 style="margin-bottom: 25px; text-align: center;">
				<strong>
					<?php esc_html_e( 'Total Earnings', 'affiliate-wp' ); ?>
				</strong>
			</h2>

			<p style="font-size: 24px; margin-top: 10px; text-align: center;">
				<strong>
					<?php if ( -1 === $affiliate_id ) : ?>
						$150.56
					<?php else : ?>
						<?php

						// Unpaid + Paid earnings.
						echo esc_html(
							$total_earnings
						);

						?>
					<?php endif; ?>
				</strong>
			</p>

			<table class="data" style="border-collapse: collapse; margin: 20px 0;table-layout: fixed; width: 100%;">
				<tbody>
					<tr style="text-align: center">

						<td style="padding: 10px; width: 33%;">
							<p style="margin-bottom: 0;">
								<strong>
									<?php esc_html_e( 'Total Referrals', 'affiliate-wp' ); ?>
								</strong>
							</p>

							<p style="font-size: 24px; margin-top: 10px;">
								<strong>
									<?php if ( -1 === $affiliate_id ) : ?>
										12
									<?php else : ?>
										<?php

										// Count of paid and unpaid referrals.
										echo absint(
											$total_referrals
										);

										?>
									<?php endif; ?>
								</strong>
							</p>
						</td>

						<td style="padding: 10px; width: 33%;">
							<p style="margin-bottom: 0;">
								<strong>
									<?php esc_html_e( 'Total Visits', 'affiliate-wp' ); ?>
								</strong>
							</p>

							<p style="font-size: 24px; margin-top: 10px;">
								<strong>
									<?php if ( -1 === $affiliate_id ) : ?>
										18
									<?php else : ?>
										<?php echo absint( $total_visits ); ?>
									<?php endif; ?>
								</strong>
							</p>
						</td>

						<td style="padding: 10px; width: 33%;">
							<p style="margin-bottom: 0;">
								<strong>
									<?php esc_html_e( 'Conversion Rate', 'affiliate-wp' ); ?>
								</strong>
							</p>

							<p style="font-size: 24px; margin-top: 10px;">
								<strong>
									<?php if ( -1 === $affiliate_id ) : ?>
										66% <!-- // Preview data. -->
									<?php else : ?>
										<?php

										echo absint(
											affwp_get_affiliate_conversion_rate(
												$affiliate_id,
												$timeframe
											)
										);

										// Yes we just echo it here, because sprintf seems to break.
										echo '%';

										?>
									<?php endif; ?>
								</strong>
							</p>
						</td>
					</tr>
				</tbody>
			</table>

			<?php if ( ! empty( $top_converted_visits ) || -1 === $affiliate_id ) : ?>

				<h2 style="text-align: center; margin-top: 50px; margin-bottom: 25px;">
					<strong>
						<?php esc_html_e( 'Top 10 Highest Converting URLs', 'affiliate-wp' ); ?>
					</strong>
				</h2>

				<table class="top-urls" style="border-collapse: collapse; border: 1px solid #eee; margin: 20px 0; width: 100%;">
					<tbody>

						<tr style="background-color: #eee; font-weight: bold; border-bottom: 1px solid #eee;">
							<td style="text-align: center;"><?php esc_html_e( 'URL', 'affiliate-wp' ); ?></td>
							<td style="text-align: center;"><?php esc_html_e( 'Referrals', 'affiliate-wp' ); ?></td>
							<td style="text-align: center;"><?php esc_html_e( 'Total Visits', 'affiliate-wp' ); ?></td>
						</tr>

						<?php if ( -1 === $affiliate_id ) : ?>

							<?php for ( $i = 1; $i <= 10; $i++ ) : ?>

								<tr style="border-bottom: 1px solid #eee;">
									<td style="text-align: center; text-align: left; border-right: 1px solid #eee;"><?php echo esc_url( home_url( "/product/{$i}" ) ); ?></td>
									<td style="text-align: center;"><?php echo absint( wp_rand( 0, 30 ) ); ?></td>
									<td style="text-align: center; border-left: 1px solid #eee;"><?php echo absint( wp_rand( 30, 60 ) ); ?></td>
								</tr>

							<?php endfor; ?>


						<?php else : ?>

							<?php foreach ( $top_converted_visits as $url => $row ) : ?>
								<tr style="border-bottom: 1px solid #eee;">

									<td style="text-align: center; text-align: left; border-right: 1px solid #eee;">
										<?php

										echo empty( $url )
											? esc_html__( 'Unknown', 'affiliate-wp' )
											: esc_url( $url );
										?>
									</td>

									<td style="text-align: center;">
										<?php echo isset( $row['visits'] ) ? absint( $row['visits'] ) : 0; ?>
									</td>

									<td style="text-align: center; border-left: 1px solid #eee;">
										<?php echo absint( affwp_get_affiliate_total_visits_to_url( $url, $affiliate_id, 'today - 30 days' ) ); ?>
									</td>

								</tr>
							<?php endforeach; ?>

						<?php endif; ?>
					</tbody>
				</table>

			<?php endif; // Top Visits. ?>

		</div>

		<?php

		return ob_get_clean();
	}
endif;

/**
 * Get the v6 or v5 Mailer.
 *
 * @since 2.9.7
 *
 * @return mixed Mailer.
 */
function affwp_get_phpmailer() {

	if ( version_compare( get_bloginfo( 'version' ), '5.5-alpha', '<' ) ) {
		return affwp_get_phpmailer_v5();
	}

	return affwp_get_phpmailer_v6();
}

/**
 * Get the v5 Mailer.
 *
 * @since 2.9.7
 *
 * @return mixed Mailer.
 */
function affwp_get_phpmailer_v5() {

	global $phpmailer;

	if ( ! ( $phpmailer instanceof \PHPMailer ) ) {

		require_once ABSPATH . WPINC . '/class-phpmailer.php';
		require_once ABSPATH . WPINC . '/class-smtp.php';

		$phpmailer = new \PHPMailer( true ); // phpcs:ignore WordPress.WP.GlobalVariablesOverride.Prohibited
	}

	return $phpmailer;
}

/**
 * Get the v6 Mailer.
 *
 * @since 2.9.7
 *
 * @return mixed Mailer.
 */
function affwp_get_phpmailer_v6() {

	global $phpmailer;

	if ( ! ( $phpmailer instanceof \PHPMailer\PHPMailer\PHPMailer ) ) {

		require_once ABSPATH . WPINC . '/PHPMailer/PHPMailer.php';
		require_once ABSPATH . WPINC . '/PHPMailer/SMTP.php';
		require_once ABSPATH . WPINC . '/PHPMailer/Exception.php';

		$phpmailer = new \PHPMailer\PHPMailer\PHPMailer( true ); // phpcs:ignore WordPress.WP.GlobalVariablesOverride.Prohibited
	}

	return $phpmailer;
}


/**
 * Is WP Mail SMTP Pro (or lite) configured?
 *
 * @since 2.9.7
 *
 * @return bool
 */
function affwp_is_wp_mail_smtp_configured() {

	/**
	 * Filter whehter or not WP Mail SMTP is configured or not.
	 *
	 * @since 2.9.7
	 *
	 * @param null Leave null to let us figure it out,
	 *             otherwise set to true or false to use your determination.
	 */
	$filtered = apply_filters( 'affwp_is_wp_mail_smtp_configured', null );

	if ( is_bool( $filtered ) ) {
		return $filtered;
	}

	require_once ABSPATH . 'wp-admin/includes/plugin.php';

	if (
		! is_plugin_active( 'wp-mail-smtp/wp_mail_smtp.php' ) &&
		! is_plugin_active( 'wp-mail-smtp-pro/wp_mail_smtp.php' )
	) {
		return false; // No lite or pro plugin active.
	}

	if ( ! function_exists( 'wp_mail_smtp' ) ) {
		return false; // This should exist, but it doesn't for some reason.
	}

	$mailer = \WPMailSMTP\Options::init()->get( 'mail', 'mailer' );

	if ( empty( $mailer ) ) {
		return false; // No mailer set.
	}

	if ( 'mail' === $mailer ) {
		return false; // Default PHP mailer set (we don't consider this configured).
	}

	// Is the mailer, then, completely configured?
	return wp_mail_smtp()->get_providers()->get_mailer( $mailer, affwp_get_phpmailer() )->is_mailer_complete();
}

/**
 * Get an affiliate's total visits for a URL.
 *
 * @param  string $url          URL.
 * @param  int    $affiliate_id Affiliate ID.
 * @param  string $date         Since when, defaults to all time (epoch).
 * @return int                  Number of visits to a url.
 */
function affwp_get_affiliate_total_visits_to_url( $url, $affiliate_id, $date = '1970-01-01' ) {

	global $wpdb;

	$count = $wpdb->get_var(
		$wpdb->prepare(
			"SELECT
				COUNT(visit_id)
				FROM {$wpdb->prefix}affiliate_wp_visits WHERE
					url = %s AND
					affiliate_id = %d AND
					date >= %s",
			$url,
			absint( $affiliate_id ),
			gmdate(
				'Y-m-d',
				strtotime( $date )
			)
		)
	);

	return is_numeric( $count )
		? absint( $count )
		: 0;
}

/**
 * Get the top converting visits for an affiliate by timeframe.
 *
 * @since 2.9.7
 *
 * @param int    $affiliate_id Affiliate ID.
 * @param array  $date {
 *     Timeframe.
 *     @type string start Start date.
 *     @type string end   End date.
 * }
 * @param string $referral_status Status of visits, defaults to converted.
 * @param int    $number          How many to retrieve.
 * @param array  $args            Arguments to merge or override.
 * @return array                  Array grouped by URL with visits and referrals.
 */
function affwp_get_affiliate_top_visit_urls(
	$affiliate_id,
	$date,
	$referral_status = 'converted',
	$number = -1,
	$args = array()
) {

	$urls = array();

	foreach ( affiliate_wp()->visits->get_visits(
		array_merge(
			array(
				'number'          => absint( $number ),
				'affiliate_id'    => absint( $affiliate_id ),
				'date'            => is_array( $date ) ? $date : array(),
				'order_by'        => 'date',
				'referral_status' => is_string( $referral_status ) ? $referral_status : 'converted',
				'fields' => array(
					'url',
					'referral_id',
				),
			),
			$args
		)
	) as $visit ) {

		// Group them by URL.
		$urls[ $visit->url ][] = $visit;
	}

	$top_urls = array();

	// Count visits and referrals for URLs.
	foreach ( $urls as $url => $visits ) {

		$top_urls[ $url ] = array(
			'visits'    => count( $visits ),

			// Log all the referrals.
			'referrals' => array_filter(
				$visits,
				function( $visit ) {
					return isset( $visit->referral_id );
				}
			),
		);
	}

	return $top_urls;
}

/**
 * Get the Monthly Email summary Markup (Content).
 *
 * @since 2.9.6
 * @since 2.9.7 Added plain text support.
 *
 * @param  array $timeframe {
 *     Timeframe.
 *     @type string $start Start time in Y-m-d format.
 *     @type string $end   End time in Y-m-d format.
 * }
 * @return string            HTML Markup for email body.
 */
function affwp_get_monthly_email_summary_content( $timeframe ) {

	if ( ! is_array( $timeframe ) ) {
		return __( 'Invalid timeframe.', 'affiliate-wp' );
	}

	// Based on includes/admin/reports/tabs/class-sales-reports-tab.php::active_integration_supports_sales().
	$has_active_integration_supports_sales_repoorting = in_array(
		true,
		array_map(
			function( $supported_integration_id ) {

				$integration_data = affiliate_wp()->integrations->get( $supported_integration_id );

				// If active, will return true and will be at least one integration that is active that supports sales reporting.
				return method_exists( $integration_data, 'is_active' )
					&& $integration_data->is_active();

			},
			affiliate_wp()->integrations->query(
				array(
					'supports' => 'sales_reporting',
					'status'   => 'enabled',
					'fields'   => 'ids',
				)
			)
		),
		true
	);

	$intro  = __( 'Hey there!', 'affiliate-wp' );
	$intro2 = __( "Let's see how your affiliate program has performed over the last 30 days.", 'affiliate-wp' );

	$total_program_revenue = affwp_currency_filter(
		affwp_format_amount(
			affiliate_wp()->referrals->sales->get_revenue_by_referral_status(
				array(
					'paid',
					'unpaid',
				),
				null,
				$timeframe
			)
		)
	);

	$new_approved_affiliates = affiliate_wp()->affiliates->count(
		array(
			'date'   => $timeframe,
			'status' => 'active',
		)
	);

	$unpaid_earnings = affwp_currency_filter(
		affwp_format_amount( affiliate_wp()->referrals->unpaid_earnings( $timeframe, 0, false ) )
	);

	$paid_earnings = affwp_currency_filter(
		affwp_format_amount( affiliate_wp()->referrals->paid_earnings( $timeframe, 0, false ) )
	);

	$top_affiliates = affwp_get_top_earning_affiliates(
		5,
		array(
			'date'   => $timeframe,
			'fields' => 'ids',
		)
	);

	/*
	 * Preview Version
	 */
	if (

		// If they selected none, use plain text...
		'none' === affiliate_wp()->settings->get( 'email_template' ) &&

		// But never use this content when previewing.
		! affwp_is_summary_email_preview( 'monthly_email' )
	) {

		ob_start();

		?>

		<?php echo esc_html( $intro ); ?>


		<?php echo esc_html( $intro2 ); ?>

		<?php if ( $has_active_integration_supports_sales_repoorting ) : // Only if there is an integration active that supports sales. ?>

			<?php esc_html_e( 'Total Program Revenue', 'affiliate-wp' ); ?>: <?php echo esc_html( $total_program_revenue ); ?>
		<?php endif; ?>

		<?php esc_html_e( 'New Approved Affiliates', 'affiliate-wp' ); ?>: <?php echo absint( $new_approved_affiliates ); ?>

		<?php echo esc_html( __( 'Unpaid Earnings', 'affiliate-wp' ) ); ?>: <?php echo esc_html( $unpaid_earnings ); ?>

		<?php echo esc_html( __( 'Paid Earnings', 'affiliate-wp' ) ); ?>: <?php echo esc_html( $paid_earnings ); ?>

		<?php if ( ! empty( $top_affiliates ) ) : ?>

			<?php echo esc_html( sprintf( __( 'Top %1$s Most Valuable Affiliates', 'affiliate-wp' ), count( $top_affiliates ) ) ); ?>:
			<?php foreach ( $top_affiliates as $top_affiliate_id ) : ?>

				<?php echo esc_html( affwp_get_affiliate_full_name_or_display_name( $top_affiliate_id ) ); ?>

				<?php esc_html_e( 'Earnings', 'affiliate-wp' ); ?>: <?php echo esc_html( affwp_currency_filter( affwp_format_amount( affiliate_wp()->referrals->get_earnings_by_status( array( 'paid', 'unpaid' ), $top_affiliate_id, $timeframe ) ) ) ); ?>

				<?php esc_html_e( 'Referrals', 'affiliate-wp' ); ?>: <?php echo esc_html( affiliate_wp()->referrals->count_by_status( array( 'paid', 'unpaid' ), $top_affiliate_id, $timeframe ) ); ?>

				<?php esc_html_e( 'Visits', 'affiliate-wp' ); ?>: <?php echo esc_html( affwp_count_visits( $top_affiliate_id, $timeframe ) ); ?>

			<?php endforeach ?>
		<?php endif; ?>

		<?php

		return mb_convert_encoding(
			str_replace( "\t", '', ob_get_clean() ),
			'UTF-8',
			'HTML-ENTITIES'
		);
	}

	/*
	 * HTML Version.
	 */
	ob_start();

	?>

	<div>

		<h3 style="margin-bottom: 9px; color: #1F2937;">
			<strong><?php echo esc_html( $intro ); ?></strong>
		</h3>

		<p style="margin-top: 0; margin-bottom: 20px; font-style: normal; font-weight: 400; font-size: 13px; line-height: 18px;"><?php echo esc_html( $intro2 ); ?></p>

		<div class="data" style="margin: 30px 0; width: 100%;">
			<div style="text-align: center;">

				<?php if ( $has_active_integration_supports_sales_repoorting ) : // Only if there is an integration active that supports sales. ?>
					<div style="height: 150px; width: 250px; display: inline-table;">

						<p style="text-align: center; margin-bottom: 0;">
							<img style="display: inline-block;" src="<?php echo esc_url( AFFILIATEWP_PLUGIN_URL . 'assets/images/summaries/total-program-revenue-icon.png' ); ?>" height="28" width="28" align="center" alt=" ">
						</p>
						<p style="margin-bottom: 0; margin-top: 5px; font-size: 14px; line-height: 18px;">
							<strong style="font-weight: 500;">
								<?php esc_html_e( 'Total Program Revenue', 'affiliate-wp' ); ?>
							</strong>
						</p>

						<p style="font-size: 32px; color: #000000; line-height: 32px; margin-top: 5px;">
							<strong>
								<?php

								// Data.
								echo esc_html(
									$total_program_revenue
								);

								?>
							</strong>
						</p>
					</div>
				<?php endif; ?>

				<div style="height: 150px; width: <?php echo esc_attr( $has_active_integration_supports_sales_repoorting ? '250px' : '160px' ); ?>; display: inline-table;">

					<p style="text-align: center; margin-bottom: 0;">
						<img style="display: inline-block;" src="<?php echo esc_url( AFFILIATEWP_PLUGIN_URL . 'assets/images/summaries/new-affiliates-icon.png' ); ?>" height="28" width="28" align="center" alt=" ">
					</p>
					<p style="margin-bottom: 0; margin-top: 5px; font-size: 14px; line-height: 18px;">
						<strong style="font-weight: 500;">
							<?php echo wp_kses_post( __( 'New Approved Affiliates', 'affiliate-wp' ) ); ?>
						</strong>
					</p>

					<p style="font-size: 32px; color: #000000; line-height: 32px; margin-top: 10px;">
						<strong>
							<?php

								// Data.
								echo absint(
									$new_approved_affiliates
								);

							?>
						</strong>
					</p>
				</div>

				<div style="height: 150px; width: <?php echo esc_attr( $has_active_integration_supports_sales_repoorting ? '250px' : '160px' ); ?>; display: inline-table;">

					<p style="text-align: center; margin-bottom: 0;">
						<img style="display: inline-block;" src="<?php echo esc_url( AFFILIATEWP_PLUGIN_URL . 'assets/images/summaries/unpaid-earnings-icon.png' ); ?>" height="28" width="28" align="center" alt=" ">
					</p>
					<p style="margin-bottom: 0; margin-top: 5px; font-size: 14px; line-height: 18px;">
						<strong style="font-weight: 500;">
							<?php echo wp_kses_post( __( 'Unpaid Earnings', 'affiliate-wp' ) ); ?>
						</strong>
					</p>

					<p style="font-size: 32px; color: #000000; line-height: 32px; margin-top: 10px;">
						<strong>
							<?php

							// Data.
							echo esc_html(
								$unpaid_earnings
							);

							?>
						</strong>
					</p>
				</div>

				<div style="height: 150px; width: <?php echo esc_attr( $has_active_integration_supports_sales_repoorting ? '250px' : '160px' ); ?>; display: inline-table;">

					<p style="text-align: center; margin-bottom: 0;">
						<img style="display: inline-block;" src="<?php echo esc_url( AFFILIATEWP_PLUGIN_URL . 'assets/images/summaries/paid-earnings-icon.png' ); ?>" height="28" width="28" align="center" alt=" ">
					</p>
					<p style="margin-bottom: 0; margin-top: 5px; font-size: 14px; line-height: 18px;">
						<strong style="font-weight: 500;">
							<?php echo wp_kses_post( __( 'Paid Earnings', 'affiliate-wp' ) ); ?>
						</strong>
					</p>

					<p style="font-size: 32px; color: #000000; line-height: 32px; margin-top: 10px;">
						<strong>
							<?php

							// Data.
							echo esc_html(
								$paid_earnings
							);

							?>
						</strong>
					</p>
				</div>
			</div>
		</div>

	</div>

	<?php if ( ! empty( $top_affiliates ) ) : ?>
		<div style="border-top: 1px solid #eee; padding-top: 20px; margin-bottom: 40px;">
			<div style="text-align: center; margin-bottom: 30px;">
				<p style="text-align: center; margin-bottom: 0;">
					<img style="display: inline-block;" src="<?php echo esc_url( AFFILIATEWP_PLUGIN_URL . 'assets/images/summaries/top-affiliates-icon.png' ); ?>" height="28" width="28" align="center" alt=" ">
				</p>
				<p style="margin-bottom: 0; margin-top: 5px; font-size: 14px; line-height: 18px;">
					<strong style="font-weight: 500;">
						<?php echo esc_html( sprintf( __( 'Top %1$s Most Valuable Affiliates', 'affiliate-wp' ), count( $top_affiliates ) ) ); ?>
					</strong>
				</p>
			</div>
			<table style="width: 100%; border-collapse: collapse; font-size: 12px; line-height: 12px; color: #000000;" cellpadding="10">
				<tbody>
					<tr>
						<td><strong style="font-weight: 500;"><?php esc_html_e( 'Affiliate', 'affiliate-wp' ); ?></strong></td>
						<td><strong style="font-weight: 500;"><?php esc_html_e( 'Earnings', 'affiliate-wp' ); ?></strong></td>
						<td><strong style="font-weight: 500;"><?php esc_html_e( 'Referrals', 'affiliate-wp' ); ?></strong></td>
						<td><strong style="font-weight: 500;"><?php esc_html_e( 'Visits', 'affiliate-wp' ); ?></strong></td>
					</tr>

					<?php foreach ( $top_affiliates as $top_affiliate_id ) : ?>
						<tr style="border-top: 1px solid #eee;">
							<td>
								<?php echo esc_html( affwp_get_affiliate_full_name_or_display_name( $top_affiliate_id ) ); ?>
							</td>

							<td>
								<?php echo esc_html( affwp_currency_filter( affwp_format_amount( affiliate_wp()->referrals->get_earnings_by_status( array( 'paid', 'unpaid' ), $top_affiliate_id, $timeframe ) ) ) ); ?>
							</td>
							<td>
								<?php echo esc_html( affiliate_wp()->referrals->count_by_status( array( 'paid', 'unpaid' ), $top_affiliate_id, $timeframe ) ); ?>
							</td>
							<td>
								<?php echo esc_html( affwp_count_visits( $top_affiliate_id, $timeframe ) ); ?>
							</td>
						</tr>
					<?php endforeach ?>

				</tbody>
			</table>
		</div>
	<?php endif; ?>

	<?php
	/*
	 * Did you Know Blurb...
	 */

	$dyk_blurb = isset( $_GET['no_dyk'] ) // phpcs:ignore WordPress.Security.NonceVerification.Recommended -- We're not using the data.
		? array() // Never show a DYK blurb when previewing.
		: affwp_get_latest_unsent_dyk_blurb_for_my_license();

	// Only if we got a valid DYK blurb from above...
	if ( isset( $dyk_blurb['id'] ) ) {
		?>

		<div style="margin: 30px 0 0; clear: both;">

			<div style="padding: 32px 40px; background: #ECF6F6; border-radius: 10px;">

				<table style="font-style: normal; font-weight: 500; font-size: 17px; line-height: 24px; color: #368286; min-height: 24px;">
					<tr>
						<td width="25" style="vertical-align: top;"><img src="<?php echo esc_url( AFFILIATEWP_PLUGIN_URL . 'assets/images/summaries/megaphone-icon.png' ); ?>" height="24" width="24" alt="📣"></td>
						<td style="vertical-align: top;"><span style="display: inline-block; line-height: 21px; padding-left: 5px;"><?php esc_html_e( 'Pro tip from our expert:', 'affiliate-wp' ); ?></span></td>
					</tr>
				</table>

				<p style="margin-top: 20px; font-size: 18px; color: #17243B;"><strong><?php echo esc_html( $dyk_blurb['title'] ); ?></strong></p>
				<p><?php echo esc_html( wp_strip_all_tags( $dyk_blurb['content'] ) ); ?></p>

				<?php if ( isset( $dyk_blurb['url'] ) ) : ?>
					<p style="margin-bottom: -10px; margin-top: 18px;">
						<a style="display: inline-block; text-align: center; padding: 7.31469px 21.9441px; background: #F63F3A; border-radius: 32.9161px; color: #fff; text-decoration: none;" href="<?php echo esc_url( $dyk_blurb['url'] ); ?>" rel="noopener noreferrer"><?php echo esc_html( ( isset( $dyk_blurb['button'] ) && ! empty( $dyk_blurb['button'] ) ) ? $dyk_blurb['button'] : __( 'Learn More', 'affiliate-wp' ) ); ?></a>
					</p>
				<?php endif; ?>
			</div>
		</div>

		<?php
	}

	/*
	 * Collect the content and preview or email...
	 */

	return ob_get_clean();
}

/**
 * Use the Administrator's desired logo on Affiliate Summaries, if there is one.
 *
 * @since 2.9.7
 *
 * @param  string $header_img The header image used by default: AffiliateWP.
 * @param  mixed  $context    The context from get_query_var( 'context' ) in the template, null if none.
 * @return string             The default one if the Administrator hasn't selected a customized logo,
 *                            or the customized logo from the Emails setting tab.
 *
 * @see affwp_notify_monthly_affiliate_perf_summary() where this is used.
 */
function affwp_notify_monthly_affiliate_summary_header_img( $header_img, $context = null ) {

	$customized_logo = affiliate_wp()->settings->get( 'email_logo', '' );

	if ( ! filter_var( $customized_logo, FILTER_SANITIZE_URL ) ) {
		return $header_img; // Use AffiliateWP by default.
	}

	return $customized_logo; // Use the customers logo.
}
add_filter(
	'affwp_email_template_affiliate_summary_header_img',
	'affwp_notify_monthly_affiliate_summary_header_img',
	10,
	2
);
