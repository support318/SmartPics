<?php
/**
 * Emails: Action Callbacks
 *
 * @package     AffiliateWP
 * @subpackage  Emails
 * @copyright   Copyright (c) 2015, Sandhills Development, LLC
 * @license     http://opensource.org/licenses/gpl-2.0.php GNU Public License
 * @since       1.6
 */

// phpcs:disable PEAR.Functions.FunctionCallSignature.EmptyLine -- Formatting this was is ok here.
// phpcs:disable PEAR.Functions.FunctionCallSignature.FirstArgumentPosition -- Formatting this was is ok here.

// Exit if accessed directly
if( ! defined( 'ABSPATH' ) ) exit;

/**
 * Sends an admin email on affiliate registration
 *
 * @since 1.6
 * @param int $affiliate_id The ID of the registered affiliate
 * @param string $status
 * @param array $args
 * @return void
 */
function affwp_notify_on_registration( $affiliate_id = 0, $status = '', $args = array() ) {

	if ( ! affwp_email_notification_enabled( 'admin_affiliate_registration_email' ) ) {
		return;
	}

	if( empty( $affiliate_id ) || empty( $status ) ) {
		return;
	}

	$emails           = new Affiliate_WP_Emails;
	$emails->__set( 'affiliate_id', $affiliate_id );

	$admin_email      = affiliate_wp()->settings->get( 'affiliate_manager_email', get_option( 'admin_email' ) );

	/**
	 * Filters the registration admin email address.
	 *
	 * @since 1.6
	 *
	 * @param string $admin_email Admin email address.
	 */
	$email            = apply_filters( 'affwp_registration_admin_email', $admin_email );
	$user_info        = get_userdata( affwp_get_affiliate_user_id( $affiliate_id ) );
	$user_url         = $user_info->user_url;
	$promotion_method = get_user_meta( affwp_get_affiliate_user_id( $affiliate_id ), 'affwp_promotion_method', true );

	$subject          = affiliate_wp()->settings->get( 'registration_subject', __( 'New Affiliate Registration', 'affiliate-wp' ) );
	$message          = affiliate_wp()->settings->get( 'registration_email', '' );

	if( empty( $message ) ) {

		$message  = __( 'A new affiliate has registered on your site, ', 'affiliate-wp' ) . home_url() . "\n\n";
		/* translators: Affiliate display name */
		$message .= sprintf( __( 'Name: %s', 'affiliate-wp' ), $args['display_name'] ) . "\n\n";

		if( $user_url ) {
			/* translators: User URL */
			$message .= sprintf( __( 'Website URL: %s', 'affiliate-wp' ), esc_url( $user_url ) ) . "\n\n";
		}

		if( $promotion_method ) {
			/* translators: Promotion method */
			$message .= sprintf( __( 'Promotion method: %s', 'affiliate-wp' ), esc_attr( $promotion_method ) ) . "\n\n";
		}

		if( affiliate_wp()->settings->get( 'require_approval' ) ) {
			/* translators: Pending affiliates URL */
			$message .= sprintf( __( 'Review pending applications: %s', 'affiliate-wp' ), affwp_admin_url( 'affiliates', array( 'status' => 'pending' ) ) ) . "\n\n";
		}

	}

	// $args is setup for backwards compatibility with < 1.6
	$args    = array( 'affiliate_id' => $affiliate_id, 'name' => $args['display_name'] );

	/**
	 * Filters the registration email subject.
	 *
	 * @since 1.6
	 *
	 * @param string $subject Email subject.
	 * @param array  $args    Arguments for sending the registration email.
	 */
	$subject = apply_filters( 'affwp_registration_subject', $subject, $args );

	/**
	 * Filters the registration email message.
	 *
	 * @since 1.6
	 *
	 * @param string $message Email message.
	 * @param array  $args    Arguments for sending the registration email.
	 */
	$message = apply_filters( 'affwp_registration_email', $message, $args );

	$emails->send( $email, $subject, $message );

}
add_action( 'affwp_register_user', 'affwp_notify_on_registration', 10, 3 );
add_action( 'affwp_auto_register_user', 'affwp_notify_on_registration', 10, 3 );


/**
 * Sends affiliate an email on affiliate approval
 *
 * @since 1.6
 * @param int $affiliate_id The ID of the registered affiliate
 * @param string $status
 * @param string $old_status
 */
function affwp_notify_on_approval( $affiliate_id = 0, $status = '', $old_status = '' ) {

	if ( ! affwp_email_notification_enabled( 'affiliate_application_accepted_email' ) ) {
		return;
	}

	if( empty( $affiliate_id ) || 'active' !== $status ) {
		return;
	}

	/*
	 * Skip sending the acceptance email for a now-'active' affiliate under
	 * certain conditions:
	 *
	 * 1. The affiliate was previously of 'inactive' or 'rejected' status.
	 * 2. The affiliate was previously of 'pending' status, where the status
	 *    transition wasn't triggered by a registration.
	 * 3. The affiliate's 'active' status didn't change, and the status
	 *    "transition" wasn't triggered by a registration, i.e. the affiliate
	 *    was updated in a bulk action and the 'active' status didn't change.
	 */
	if ( ! in_array( $old_status, array( 'active', 'pending' ), true )
		&& ! did_action( 'affwp_affiliate_register' )
	) {
		return;
	}

	if( doing_action( 'affwp_add_affiliate' ) && empty( $_POST['welcome_email'] ) ) {
		return;
	}

	$emails       = new Affiliate_WP_Emails;
	$emails->__set( 'affiliate_id', $affiliate_id );

	$email        = affwp_get_affiliate_email( $affiliate_id );
	$subject      = affiliate_wp()->settings->get( 'accepted_subject', __( 'Affiliate Application Accepted', 'affiliate-wp' ) );
	$message      = affiliate_wp()->settings->get( 'accepted_email', '' );

	if( empty( $message ) ) {
		/* translators: Affiliate name */
		$message  = sprintf( __( 'Congratulations %s!', 'affiliate-wp' ), affiliate_wp()->affiliates->get_affiliate_name( $affiliate_id ) ) . "\n\n";
		/* translators: Home URL */
		$message .= sprintf( __( 'Your affiliate application on %s has been accepted!', 'affiliate-wp' ), home_url() ) . "\n\n";
		/* translators: Login URL */
		$message .= sprintf( __( 'Log into your affiliate area at %s', 'affiliate-wp' ), affiliate_wp()->login->get_login_url() ) . "\n\n";
	}

	// $args is setup for backwards compatibility with < 1.6
	$args        = array( 'affiliate_id' => $affiliate_id );

	/**
	 * Filters the Application Accepted email subject.
	 *
	 * @since 1.6
	 *
	 * @param string $subject Email subject.
	 * @param array  $args    Arguments for sending the email.
	 */
	$subject     = apply_filters( 'affwp_application_accepted_subject', $subject, $args );

	/**
	 * Filters the Application Accepted email message.
	 *
	 * @since 1.6
	 *
	 * @param string $message Email message contents.
	 * @param array  $args    Arguments for sending the email.
	 */
	$message     = apply_filters( 'affwp_application_accepted_email', $message, $args );
	$user_id     = affwp_get_affiliate_user_id( $affiliate_id );

	if ( true === (bool) get_user_meta( $user_id, 'affwp_generated_pass', true ) && doing_action( 'affwp_add_affiliate' ) && ! empty( $_POST['user_email'] ) ) {

		$key        = get_password_reset_key( get_user_by( 'id', $user_id ) );
		$user_login = affwp_get_affiliate_username( $affiliate_id );

		if ( ! is_wp_error( $key ) ) {
			$message .= "\r\n\r\n" . __( 'To set your password, visit the following address:', 'affiliate-wp' ) . "\r\n\r\n";
			$message .= network_site_url( "wp-login.php?action=rp&key=$key&login=" . rawurlencode( $user_login ), 'login' ) . "\r\n";
		}

	}

	if ( true === (bool) get_user_meta( $user_id, 'affwp_generated_pass', true ) && affiliate_wp()->settings->get( 'allow_affiliate_registration' ) && doing_action( 'affwp_affiliate_register' ) ) {

		$key                          = get_password_reset_key( get_user_by( 'id', $user_id ) );
		$user_login                   = affwp_get_affiliate_username( $affiliate_id );
		$required_registration_fields = affiliate_wp()->settings->get( 'required_registration_fields' );

		if ( ! is_wp_error( $key ) && ! isset( $required_registration_fields['password'] ) ) {
			$message .= "\r\n\r\n" . __( 'To set your password, visit the following address:', 'affiliate-wp' ) . "\r\n\r\n";
			$message .= network_site_url( "wp-login.php?action=rp&key=$key&login=" . rawurlencode( $user_login ), 'login' ) . "\r\n";
		}

	}

	/**
	 * Filters whether to notify an affiliate upon approval of their application.
	 *
	 * @since 1.6
	 *
	 * @param bool $notify Whether to notify the affiliate upon approval. Default true.
	 */
	if ( apply_filters( 'affwp_notify_on_approval', true ) && ! get_user_meta( $user_id, 'affwp_disable_affiliate_email', true ) ) {
		$emails->send( $email, $subject, $message );
	}

}
add_action( 'affwp_set_affiliate_status', 'affwp_notify_on_approval', 10, 3 );

/**
 * Sends affiliate an email on pending affiliate registration
 *
 * @since 1.6.1
 * @param int $affiliate_id The ID of the registered affiliate
 * @param string $status
 * @param array $args
 */
function affwp_notify_on_pending_affiliate_registration( $affiliate_id, $status, $args ) {

	if ( ! affwp_email_notification_enabled( 'affiliate_application_pending_email' ) ) {
		return;
	}

	if ( empty( $affiliate_id ) || empty( $status ) ) {
		return;
	}

	if ( 'pending' != $status ) {
		return;
	}

	$emails       = new Affiliate_WP_Emails;
	$emails->__set( 'affiliate_id', $affiliate_id );

	$email        = affwp_get_affiliate_email( $affiliate_id );
	$subject      = affiliate_wp()->settings->get( 'pending_subject', __( 'Your Affiliate Application Is Being Reviewed', 'affiliate-wp' ) );
	$message      = affiliate_wp()->settings->get( 'pending_email', '' );

	if ( empty( $message ) ) {
		/* translators: Affiliate name */
		$message  = sprintf( __( 'Hi %s!', 'affiliate-wp' ), affiliate_wp()->affiliates->get_affiliate_name( $affiliate_id ) ) . "\n\n";
		$message .= __( 'Thanks for your recent affiliate registration on {site_name}.', 'affiliate-wp' ) . "\n\n";
		$message .= __( 'We&#8217;re currently reviewing your affiliate application and will be in touch soon!', 'affiliate-wp' ) . "\n\n";
	}

	$required_registration_fields = affiliate_wp()->settings->get( 'required_registration_fields' );

	$user_id     = affwp_get_affiliate_user_id( $affiliate_id );
	$key         = get_password_reset_key( get_user_by( 'id', $user_id ) );
	$user_login  = affwp_get_affiliate_username( $affiliate_id );

	if ( true === (bool) get_user_meta( $user_id, 'affwp_generated_pass', true ) && ! is_wp_error( $key ) && ! isset( $required_registration_fields['password'] ) ) {
		$message .= "\r\n\r\n" . __( 'To set your password, visit the following address:', 'affiliate-wp' ) . "\r\n\r\n";
		$message .= network_site_url( "wp-login.php?action=rp&key=$key&login=" . rawurlencode( $user_login ), 'login' ) . "\r\n";
	}

	/**
	 * Filters whether to notify the affiliate upon pending registration.
	 *
	 * @since 1.6.1
	 *
	 * @param bool $send Whether to send the pending affiliate registration email.
	 */
	if ( apply_filters( 'affwp_notify_on_pending_affiliate_registration', true ) ) {
		$emails->send( $email, $subject, $message );
	}

}
add_action( 'affwp_register_user', 'affwp_notify_on_pending_affiliate_registration', 10, 3 );
add_action( 'affwp_auto_register_user', 'affwp_notify_on_pending_affiliate_registration', 10, 3 );

/**
 * Sends affiliate an email on rejected affiliate registration
 *
 * @since 1.6.1
 * @param int $affiliate_id The ID of the registered affiliate
 * @param string $status
 * @param string $old_status
 */
function affwp_notify_on_rejected_affiliate_registration( $affiliate_id = 0, $status = '', $old_status = '' ) {

	if ( ! affwp_email_notification_enabled( 'affiliate_application_rejected_email' ) ) {
		return;
	}

	if ( empty( $affiliate_id ) ) {
		return;
	}

	if ( 'rejected' != $status || 'pending' != $old_status ) {
		return;
	}

	$emails       = new Affiliate_WP_Emails;
	$emails->__set( 'affiliate_id', $affiliate_id );

	$email        = affwp_get_affiliate_email( $affiliate_id );
	$subject      = affiliate_wp()->settings->get( 'rejection_subject', __( 'Your Affiliate Application Has Been Rejected', 'affiliate-wp' ) );
	$message      = affiliate_wp()->settings->get( 'rejection_email', '' );

	if ( empty( $message ) ) {
		/* translators: Affiliate name */
		$message  = sprintf( __( 'Hi %s,', 'affiliate-wp' ), affiliate_wp()->affiliates->get_affiliate_name( $affiliate_id ) ) . "\n\n";
		$message .= __( 'We regret to inform you that your recent affiliate registration on {site_name} was rejected.', 'affiliate-wp' ) . "\n\n";
	}

	/**
	 * Filters whether to notify the affiliate upon rejected registration.
	 *
	 * @since 1.6.1
	 *
	 * @param bool $send Whether to send the rejected affiliate registration email.
	 */
	if ( apply_filters( 'affwp_notify_on_rejected_affiliate_registration', true ) ) {
		$emails->send( $email, $subject, $message );
	}

}
add_action( 'affwp_set_affiliate_status', 'affwp_notify_on_rejected_affiliate_registration', 10, 3 );

/**
 * Sends affiliate an email on new referrals
 *
 * @since 1.6
 * @param int $affiliate_id The ID of the registered affiliate
 * @param array $referral
 */
function affwp_notify_on_new_referral( $affiliate_id, $referral ) {

	if( empty( $affiliate_id ) ) {
		return;
	}

	if( empty( $referral ) ) {
		return;
	}

	if ( ! affwp_email_notification_enabled( 'affiliate_new_referral_email', $affiliate_id ) ) {
		return;
	}

	$user_id = affwp_get_affiliate_user_id( $affiliate_id );

	if( ! get_user_meta( $user_id, 'affwp_referral_notifications', true ) ) {
		return;
	}

	$emails  = new Affiliate_WP_Emails;
	$emails->__set( 'affiliate_id', $affiliate_id );
	$emails->__set( 'referral', $referral );

	$email   = affwp_get_affiliate_email( $affiliate_id );
	$subject = affiliate_wp()->settings->get( 'referral_subject', __( 'Referral Awarded!', 'affiliate-wp' ) );
	$message = affiliate_wp()->settings->get( 'referral_email', false );
	$amount  = html_entity_decode( affwp_currency_filter( $referral->amount ), ENT_COMPAT, 'UTF-8' );

	if( ! $message ) {
		/* translators: Affiliate name */
		$message  = sprintf( __( 'Congratulations %s!', 'affiliate-wp' ), affiliate_wp()->affiliates->get_affiliate_name( $affiliate_id ) ) . "\n\n";
		/* translators: 1: Formatted referral amount, 2: Home URL */
		$message .= sprintf( __( 'You have been awarded a new referral of %1$s on %2$s!', 'affiliate-wp' ), $amount, home_url() ) . "\n\n";
		/* translators: Login URL */
		$message .= sprintf( __( 'log into your affiliate area to view your earnings or disable these notifications: %s', 'affiliate-wp' ), affiliate_wp()->login->get_login_url() ) . "\n\n";
	}

	// $args is setup for backwards compatibility with < 1.6
	$args    = array( 'affiliate_id' => $affiliate_id, 'amount' => $referral->amount, 'referral' => $referral );

	/**
	 * Filters the subject for the New Referral email.
	 *
	 * @since 1.6
	 *
	 * @param string $subject Email subject.
	 * @param array  $args    Arguments for sending the email.
	 */
	$subject = apply_filters( 'affwp_new_referral_subject', $subject, $args );

	/**
	 * Filters the message contents for the New Referral email.
	 *
	 * @since 1.6
	 *
	 * @param string $message Email subject.
	 * @param array  $args    Arguments for sending the email.
	 */
	$message = apply_filters( 'affwp_new_referral_email', $message, $args );

	/**
	 * Filters whether to notify the affiliate with the New Referral email.
	 *
	 * @since 1.6
	 *
	 * @param bool            $send     Whether to send the email.
	 * @param \AffWP\Referral $referral Referral object.
	 */
	if ( apply_filters( 'affwp_notify_on_new_referral', true, $referral ) ) {
		$emails->send( $email, $subject, $message );
	}


}
add_action( 'affwp_referral_accepted', 'affwp_notify_on_new_referral', 10, 2 );

/**
 * Sends an email to admins on when a new referral is generated.
 *
 * @since 2.1.7
 *
 * @param int             $affiliate_id The ID of the registered affiliate
 * @param \AffWP\Referral $referral     Referral object.
 */
function affwp_notify_admin_on_new_referral( $affiliate_id, $referral ) {

	if( empty( $affiliate_id ) ) {
		return;
	}

	if( empty( $referral ) ) {
		return;
	}

	$send = affwp_email_notification_enabled( 'admin_new_referral_email', $affiliate_id );

	/**
	 * Filters whether to notify admins when a new referral is generated.
	 *
	 * @since 2.1.7
	 *
	 * @param bool            $send     Whether to send the email. Default false.
	 * @param \AffWP\Referral $referral Referral object.
	 */
	if( true !== apply_filters( 'affwp_notify_admin_on_new_referral', $send, $referral ) ) {
		return;
	}

	$emails  = new Affiliate_WP_Emails;
	$emails->__set( 'affiliate_id', $affiliate_id );
	$emails->__set( 'referral', $referral );

	$subject = affiliate_wp()->settings->get( 'new_admin_referral_subject', __( 'Referral Earned!', 'affiliate-wp' ) );
	$message = affiliate_wp()->settings->get( 'new_admin_referral_email', false );

	if( ! $message ) {
		$message = '{name} has been awarded a new referral of {amount} on {site_name}.';
	}

	/**
	 * Filters the subject field for the email sent to admins when a new referral is generated.
	 *
	 * @since 2.1.7
	 *
	 * @param string          $subject      Email subject.
	 * @param int             $affiliate_id Affiliate ID.
	 * @param \AffWP\Referral $referral     Referral object.
	 */
	$subject = apply_filters( 'affwp_new_admin_referral_subject', $subject, $affiliate_id, $referral );

	/**
	 * Filters the message body for the email sent to admins when a new referral is generated.
	 *
	 * @since 2.1.7
	 *
	 * @param string          $message      Email message body.
	 * @param int             $affiliate_id Affiliate ID.
	 * @param \AffWP\Referral $referral     Referral object.
	 */
	$message = apply_filters( 'affwp_new_admin_referral_email', $message, $affiliate_id, $referral );

	$admin_email = affiliate_wp()->settings->get( 'affiliate_manager_email', get_option( 'admin_email' ) );

	/**
	 * Filters the recipient email address for the email sent to admins when a new referral is generated.
	 *
	 * @since 2.1.7
	 *
	 * @param string          $admin_email  Recipient email. Default is the value of the 'admin_email' option.
	 * @param int             $affiliate_id Affiliate ID.
	 * @param \AffWP\Referral $referral     Referral object.
	 */
	$to_email = apply_filters( 'affwp_new_admin_referral_email_to', $admin_email, $affiliate_id, $referral );

	$emails->send( $to_email, $subject, $message );

}
add_action( 'affwp_referral_accepted', 'affwp_notify_admin_on_new_referral', 10, 2 );

/**
 * Have we checked referrals once for the first Email Summary?
 *
 * @since 2.10.0
 *
 * @return bool
 */
function affwp_monthly_email_summary_referral_checkonce() {

	$option_key = 'affwp_email_summary_referral_checkonce';

	// Note, if you want to filter this, use the option_{$option} filter.
	$check = get_option( $option_key, false );

	if ( false === $check ) {

		// Note we checked it once with a timestamp.
		update_option( $option_key, time(), false );

		return false;
	}

	return true; // Our option was not false, so we must have checked referral count at least once.
}

/**
 * Monthly Email Summary.
 *
 * @since 2.9.6
 *
 * @param bool $preview Set to true to build email instead of send (for previewing).
 *
 * @see affwp_get_monthly_email_summary_content() For Email Content Body.
 *
 * @return void
 */
function affwp_notify_monthly_email_summary( $preview = false ) {

	if ( ! $preview && false !== affiliate_wp()->settings->get( 'disable_monthly_email_summaries', false ) ) {
		return; // You are wanting to email this summary, but a setting has disabled it.
	}

	// Don't schedule until they have at least one referral.
	if (

		// This function will return false only the first time it's ever run, after that we won't care if they have no referrals.
		false === affwp_monthly_email_summary_referral_checkonce() &&

		// We don't have any referrals yet.
		0 === affiliate_wp()->referrals->get_referrals(
			array(
				'number' => 1,
				'fields' => 'ids',
			),
			true
		)
	) {
		return;
	}

	// Send the email...
	affwp_email_summary(

		// Name of summary.
		'monthly_email',

		// To.
		affiliate_wp()->settings->get( 'affiliate_manager_email', get_option( 'admin_email' ) ),

		// Translators: This is the subject of the email, %1$s is url of the website (note to developers, this subject may not work in Mailhog, see: https://github.com/awesomemotive/AffiliateWP/pull/4410#issuecomment-1226079496 ).
		sprintf( __( 'Your Monthly AffiliateWP Summary for %s', 'affiliate-wp' ), str_replace( array( 'https://', 'http://' ), '', get_option( 'home', 'your site' ) ) ),

		// Content.
		affwp_get_monthly_email_summary_content(

			/**
			 * Filter the time frame for the data we are emailing.
			 *
			 * @since 2.9.6
			 *
			 * @param array $timeframe {
			 *    Arguments for start/end timeframe.
			 *
			 *    @type string $start Start in Y-m-d format.
			 *    @type string $end   End in Y-m-d format.
			 * }
			 */
			apply_filters(
				'affwp_notify_monthly_email_summary_timeframe',
				array(

					// Note, with AFFILIATE_WP_DEBUG enabled, you can override the YYYY-MM-DD format here using "?start=&end=", e.g. &start=2021-01-01&end=2022-08-05.
					'start' => ( isset( $_GET['start'] ) && defined( 'AFFILIATE_WP_DEBUG' ) && AFFILIATE_WP_DEBUG ) ? $_GET['start'] : gmdate( 'Y-m-d', strtotime( '-30 days' ) ), // phpcs:ignore WordPress.Security.NonceVerification.Recommended  -- No sanitization necessary here, just for development.
					'end'   => ( isset( $_GET['end'] ) && defined( 'AFFILIATE_WP_DEBUG' ) && AFFILIATE_WP_DEBUG ) ? $_GET['end'] : gmdate( 'Y-m-d', strtotime( 'today' ) ), // phpcs:ignore WordPress.Security.NonceVerification.Recommended  -- No sanitization necessary here, just for development.
				)
			)
		),

		// Data.
		array(

			// Note the DYK blurb we send with this email (a hook later will remember it was sent when email sends).
			'dyk_blurb' => affwp_get_latest_unsent_dyk_blurb_for_my_license(),
		),

		// Preview (false sends the email, true does not).
		$preview
	);
}
add_action(

	// Send the Summary email.
	'affwp_monthly_email_summaries', // See includes/class-affwp-scheduler.php.
	'affwp_notify_monthly_email_summary'
);

/**
 * Preview the Monthly Summary email.
 *
 * @since 2.9.6
 *
 * @return void
 */
function affwp_preview_monthly_email_summary() {

	if ( ! affwp_is_summary_email_preview( 'monthly_email' ) ) {
		return; // We are not requesting a preview of the monthly admin performance email.
	}

	// Preview the email.
	affwp_notify_monthly_email_summary( true );
}
add_action( 'admin_init', 'affwp_preview_monthly_email_summary' );

/**
 * Don't send DYK blurb again.
 *
 * @since 2.9.6
 *
 * @param  bool  $sent If we sent the email (with a blurb).
 * @param  array $data Data (that has the blurb sent in it).
 * @return void
 */
function affwp_remember_sent_dyk_blurb( $sent, $data ) {

	if ( ! $sent || ! isset( $data['dyk_blurb']['id'] ) ) {
		return; // No blurb with this email.
	}

	// If we had a blurb sent along with it, remember that we sent it.
	affwp_add_sent_dyk_blurb( $data['dyk_blurb']['id'] );
}
add_action( 'affwp_notify_monthly_email_summary_email_sent', 'affwp_remember_sent_dyk_blurb', 10, 2 );

/**
 * Preview the Monthly Affiliate Summary email.
 *
 * @since 2.9.7
 *
 * @return void
 */
function affwp_preview_monthly_affiliate_email_summary() {

	if ( ! affwp_is_summary_email_preview( 'monthly_affiliate_email' ) ) {
		return; // We are not requesting a preview of the monthly admin performance email.
	}

	// Preview the email.
	affwp_notify_monthly_affiliate_email_summary( true );
}
add_action( 'admin_init', 'affwp_preview_monthly_affiliate_email_summary' );

/**
 * Monthly Affiliate Email Summary.
 *
 * @since 2.9.7
 *
 * @param bool $preview Set to true to build email instead of send (for previewing).
 *
 * @return void
 */
function affwp_notify_monthly_affiliate_email_summary( $preview = false ) {

	if ( $preview ) {

		// Preview the first affiliate and stop (will die()).
		affwp_email_summary(

			// Name of summary.
			'monthly_affiliate_email',

			// To.
			'nobody@example.com',

			// Subject.
			sprintf(
				// Translators: %1$s is the site.
				__( 'Your Monthly Summary for %1$s', 'affiliate-wp' ),
				// Show the site label for %1$s (the site).
				str_replace(
					array( 'https://', 'http://' ),
					'', // Replace with nothing.
					get_option( 'home' )
				)
			),

			// Content.
			affwp_get_monthly_affiliate_email_summary_content( -1, array() ),

			// Data (none).
			array(),

			// Preview.
			true,

			// Template.
			'affiliate-summary'
		);

		return;
	}

	if ( is_multisite() ) {

		affiliate_wp()->utils->log(
			__( 'Cound not send affiliate email summaries because it is not supported on multisite.', 'affiliate-wp' )
		);

		return;
	}

	if ( true !== affwp_is_affiliate_email_summaries_enabled() ) {

		affiliate_wp()->utils->log(
			__( 'Cound not send affiliate email summaries because it has not been enabled.', 'affiliate-wp' )
		);

		return; // You are wanting to email this summary, but a setting is not enabled.
	}

	if ( false === affwp_is_wp_mail_smtp_configured() ) {

		affiliate_wp()->utils->log(
			__( 'Cound not send affiliate email summaries because WP Mail SMTP is not configured.', 'affiliate-wp' )
		);

		return; // You need to have WP Mail SMTP set-up before we can send this email.
	}

	// Start sending email now, plus an offset to give them a chance to cancel.
	$timing = time() +
		absint(
			/**
			 * Filter the initial offset before sending individual affiliate email summaries.
			 *
			 * @since 2.9.7
			 *
			 * @param int Offset in seconds.
			 */
			apply_filters(
				'monthly_affiliate_email_summary_initial_timing_offset',
				MINUTE_IN_SECONDS * 15
			)
		);

	// Loop over all the affiliates and schedule/send emails (or maybe preview one)...
	foreach (

		// Affiliates...
		affiliate_wp()->affiliates->get_affiliates(
			array(
				'number' => -1,
				'status' => 'active',
				'fields' => 'ids',
			)
		)
	as $affiliate_id ) {

		/**
		 * Filter the interval for sending each affiliate their email summary.
		 *
		 * @since 2.9.7
		 *
		 * @param string $interval Defaults to MINUTE_IN_SECONDS (60 seconds).
		 */
		$timing = $timing + apply_filters( 'monthly_affiliate_email_summary_interval', MINUTE_IN_SECONDS );

		// Get the email (none is okay for previewing).
		$affiliate_email = affwp_get_affiliate_email( $affiliate_id );

		$subject = sprintf(
			// Translators: %1$s is the site.
			__( 'Your Monthly Summary for %1$s', 'affiliate-wp' ),
			// Show the site label for %1$s (the site).
			str_replace(
				array( 'https://', 'http://' ),
				'', // Replace with nothing.
				get_option( 'home' )
			)
		);

		$content = affwp_get_monthly_affiliate_email_summary_content(
			$affiliate_id,

			/**
			 * Filter the time frame for the data we are emailing.
			 *
			 * @since 2.9.7
			 *
			 * @param array $timeframe {
			 *    Arguments for start/end timeframe.
			 *
			 *    @type string $start Start in Y-m-d format.
			 *    @type string $end   End in Y-m-d format.
			 * }
			 */
			apply_filters(
				'affwp_notify_monthly_affiliate_email_summary_timeframe',
				array(

					// Note, with AFFILIATE_WP_DEBUG enabled, you can override the YYYY-MM-DD format here using "?start=&end=", e.g. &start=2021-01-01&end=2022-08-05.
					'start' => ( isset( $_GET['start'] ) && defined( 'AFFILIATE_WP_DEBUG' ) && AFFILIATE_WP_DEBUG ) ? $_GET['start'] : gmdate( 'Y-m-d', strtotime( '-30 days' ) ), // phpcs:ignore WordPress.Security.NonceVerification.Recommended  -- No sanitization necessary here, just for development.
					'end'   => ( isset( $_GET['end'] ) && defined( 'AFFILIATE_WP_DEBUG' ) && AFFILIATE_WP_DEBUG ) ? $_GET['end'] : gmdate( 'Y-m-d', strtotime( 'today' ) ), // phpcs:ignore WordPress.Security.NonceVerification.Recommended  -- No sanitization necessary here, just for development.
				)
			)
		);

		if ( empty( $content ) ) {
			continue; // Need something to send them, we must not have been able to determine an affiliate.
		}

		if ( false === $affiliate_email || ! filter_var( $affiliate_email, FILTER_VALIDATE_EMAIL ) ) {

			affiliate_wp()->utils->log(
				sprintf(

					// Translators: %1$s is the Affiliate's ID and %2$s the email that was not valid.
					__( 'Affiliate monthly summary for affiliate with ID %1$s with email %2$s was not sent because it is not a valid email.', 'affiliate-wp' ),
					$affiliate_id,
					$affiliate_email
				)
			);

			continue; // We couldn't find (or validate) an email for this affiliate, we can't email them.
		}

		$date_registered = affwp_get_affiliate_date_registered( $affiliate_id );

		if ( empty( $date_registered ) ) {

			affiliate_wp()->utils->log(
				sprintf(

					// Translators: %1$s is the Affiliates ID and %2$s is the bad registration date.
					__( 'Affiliate monthly summary for affiliate with ID %1$s with registered_date of %2$s was not sent because it is not a valid registration date.', 'affiliate-wp' ),
					$affiliate_id,
					$date_registered
				)
			);

			continue; // Can't determine when they registered, don't email them.
		}

		// Determine how many days they have been registered until today.
		$date_diff = date_diff(
			date_create( $date_registered ),
			date_create( gmdate( 'Y-m-d', time() ) )
		);

		if ( ! $date_diff ) {

			affiliate_wp()->utils->log(
				sprintf(

					// Translators: %1$s is the Affiliates ID.
					__( 'Affiliate with ID %1$s could not be sent their email because we could not determine how many days they had been registered.', 'affiliate-wp' ),
					$affiliate_id
				)
			);

			continue; // Could not figure out a number of days from the diff.
		}

		if ( $date_diff->days < 30 ) {

			affiliate_wp()->utils->log(
				sprintf(

					// Translators: %1$s is the Affiliates ID and %2$s is their registration date.
					__( 'Affiliate with ID %1$s (registered on %2$s) was not sent their monthly email summary because they were registered within the last 30 days.', 'affiliate-wp' ),
					$affiliate_id,
					$date_registered
				)
			);

			continue; // They have not been around 30 days yet.
		}

		$last_sent = affwp_get_affiliate_meta(
			$affiliate_id,
			'last_email_summary_sent',
			true
		);

		// They were sent an email at some point...
		if ( is_numeric( $last_sent ) ) {

			$sent_date_diff = date_diff(
				date_create( gmdate( 'Y-m-d', $last_sent ) ), // Sent 8-20-2022.
				date_create( gmdate( 'Y-m-d', time() ) ) // Now: 9-20-2022.
			);

			// Has at least 27 days passed since the last email was sent?
			if ( false !== $sent_date_diff && $sent_date_diff->days < 27 ) {

				affiliate_wp()->utils->log(
					sprintf(

						// Translators: %1$s is the Affiliates ID and %2$s is the date they were last sent an email.
						__( 'Affiliate with ID %1$s was not sent their email summary because they were sent one already on %2$s.', 'affiliate-wp' ),
						$affiliate_id,
						gmdate( 'Y-m-d', $last_sent )
					)
				);

				continue; // They already got an email, don't sent another until 30 days have past.
			}
		}

		// Schedule each affiliates email to send...
		affwp_schedule_summary(
			'monthly_affiliate_email',
			$affiliate_email,
			$subject,
			$content,

			// Data.
			array(
				'affiliate_id' => $affiliate_id, // Used to keep track of sent email summaries.
			),

			// Template.
			'affiliate-summary',
			$timing
		);
	}
}
add_action(
	'affwp_monthly_affiliate_email_summaries', // See includes/class-affwp-scheduler.php.
	'affwp_notify_monthly_affiliate_email_summary'
);

/**
 * Send scheduled email summaries.
 *
 * @since 2.9.7
 *
 * @param  string $name       See affwp_email_summary().
 * @param  string $to         See affwp_email_summary().
 * @param  string $subject    See affwp_email_summary().
 * @param  string $email_body See affwp_email_summary().
 * @param  string $data       See affwp_email_summary().
 * @param  string $template   See affwp_email_summary().
 * @return bool               See affwp_email_summary().
 */
function affwp_send_scheduled_summary(
	$name,
	$to,
	$subject,
	$email_body,
	$data,
	$template
) {

	// Preview the first affiliate and stop (will die()).
	return affwp_email_summary(
		$name,
		$to,
		$subject,
		$email_body,
		$data,
		false, // Never previewing here.
		$template
	);
}
add_action(
	'affwp_send_scheduled_summary',
	'affwp_send_scheduled_summary',
	10,
	6
);

/**
 * Log when we sent an email summary to an affiliate.
 *
 * @since 2.9.7
 *
 * @param  bool  $sent Whether the email was successfully sent or noy.
 * @param  array $data Data about the email.
 * @return bool        Whether the email was sent (unchanged).
 */
function affwp_log_affiliate_email_summary_last_sent_time( $sent, $data ) {

	if ( ! $sent ) {
		return $sent; // Don't log anything, it didn't send.
	}

	if ( ! isset( $data['affiliate_id'] ) || ! is_numeric( $data['affiliate_id'] ) ) {
		return $sent; // We need this to track what affiliate we sent this to.
	}

	// Log that we sent the email summary right now.
	affwp_update_affiliate_meta( $data['affiliate_id'], 'last_email_summary_sent', time() );

	return $sent;
}
add_action(
	'affwp_notify_monthly_affiliate_email_summary_email_sent',
	'affwp_log_affiliate_email_summary_last_sent_time',
	10,
	2
);

/**
 * Make sure certain email summaries always use HTML templates when previewing them.
 *
 * @since 2.9.7
 *
 * @param  string $template The selected template from Settings.
 * @return string           Selected template, unless previewing an email summary
 *                          which returns an HTML template.
 */
function affwp_use_html_templates_for_previewing_email_summaries( $template ) {

	foreach ( array(

		// Email summaries...
		'monthly_affiliate_email' => 'affiliate-summary',
		'monthly_email'           => 'summaries',
	) as $summary => $html_template ) {
		if ( affwp_is_summary_email_preview( $summary ) ) {
			return $html_template;
		}
	}

	return $template; // We're not previewing any of these, so just use the selected template.
}
add_action( 'affwp_email_template', 'affwp_use_html_templates_for_previewing_email_summaries' );
