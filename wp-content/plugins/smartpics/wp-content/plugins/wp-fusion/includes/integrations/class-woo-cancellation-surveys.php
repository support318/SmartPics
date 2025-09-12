<?php

if ( ! defined( 'ABSPATH' ) ) {
	exit; // Exit if accessed directly
}

/**
 * WooCommerce Cancellation Surveys integration.
 *
 * @link https://wpfusion.com/documentation/ecommerce/cancellation-survey-for-woocommerce-subscriptions/
 *
 * @since 3.44.12
 */
class WPF_Woo_Cancellation_Surveys extends WPF_Integrations_Base {

	/**
	 * The slug for WP Fusion's integration.
	 *
	 * @since 3.44.12
	 * @var string $slug
	 */
	public $slug = 'woo-cancellation-surveys';

	/**
	 * The name for WP Fusion's integration.
	 *
	 * @since 3.44.12
	 * @var string $name
	 */
	public $name = 'WooCommerce Cancellation Surveys';

	/**
	 * The link to the documentation on the WP Fusion website.
	 *
	 * @since 3.44.12
	 * @var string $docs_url
	 */
	public $docs_url = 'https://wpfusion.com/documentation/ecommerce/cancellation-survey-for-woocommerce-subscriptions/';

	/**
	 * Default settings.
	 *
	 * @since 3.44.17
	 * @var array $settings_defaults
	 */
	public $settings_defaults = array(
		'apply_tags'                 => array(),
		'apply_tags_retention_offer' => array(),
	);

	/**
	 * Initialize the integration.
	 *
	 * @since 3.44.12
	 */
	public function init() {
		add_action( 'cancellation_offers/survey_answers/created', array( $this, 'survey_answer_created' ) );
		add_action( 'cancellation_offers/survey_answers/updated', array( $this, 'survey_answer_created' ) );

		add_action( 'woocommerce_subscription_date_updated', array( $this, 'subscription_date_updated' ) );

		add_filter( 'wpf_meta_fields', array( $this, 'meta_fields' ), 60 );
		add_action( 'add_meta_boxes', array( $this, 'add_meta_boxes' ) );
	}

	/**
	 * Handles the creation of a survey answer.
	 *
	 * @since 3.44.12
	 *
	 * @param SurveyAnswer $survey_answer The survey answer.
	 */
	public function survey_answer_created( $survey_answer ) {

		$update_data = array(
			'cancellation_survey_reason'   => $survey_answer->getSurveySelectedAnswer(),
			'cancellation_survey_comments' => $survey_answer->getSurveyTextAnswer(),
		);

		if ( $survey_answer->isDiscountOfferAccepted() ) {
			$update_data['cancellation_survey_retention_offer'] = get_post_meta( $survey_answer->getOfferId(), '_cancellation_offers_discounts_title', true );
		}

		wp_fusion()->user->push_user_meta( $survey_answer->getUserId(), $update_data );

		// Maybe apply tags.

		$settings = wp_parse_args( get_post_meta( $survey_answer->getOfferId(), 'wpf-settings', true ), $this->settings_defaults );

		$apply_tags = $settings['apply_tags'];

		if ( $survey_answer->isDiscountOfferAccepted() && ! empty( $settings['apply_tags_retention_offer'] ) ) {
			$apply_tags = array_merge( $apply_tags, $settings['apply_tags_retention_offer'] );
		}

		if ( ! empty( $apply_tags ) ) {
			wp_fusion()->user->apply_tags( $apply_tags, $survey_answer->getUserId() );
		}
	}

	/**
	 * Handles the subscription date update after skipping a renewal payment.
	 *
	 * @since 3.45.6
	 *
	 * @param WC_Subscription $subscription The subscription.
	 */
	public function subscription_date_updated( $subscription ) {

		if ( ! check_ajax_referer( 'cos_take_discount', 'nonce', false ) ) {
			return;
		}

		wp_fusion()->integrations->{'woo-subscriptions'}->sync_subscription_fields( $subscription );
	}

	/**
	 * Adds the cancellation survey response field to the meta fields array.
	 *
	 * @since 3.44.12
	 *
	 * @param array $meta_fields The meta fields.
	 * @return array The meta fields.
	 */
	public function meta_fields( $meta_fields ) {

		$meta_fields['cancellation_survey_reason'] = array(
			'label' => __( 'Cancellation Survey Reason', 'wp-fusion' ),
			'type'  => 'text',
			'group' => 'woocommerce_subs',
		);

		$meta_fields['cancellation_survey_comments'] = array(
			'label' => __( 'Cancellation Survey Comments', 'wp-fusion' ),
			'type'  => 'text',
			'group' => 'woocommerce_subs',
		);

		$meta_fields['cancellation_survey_retention_offer'] = array(
			'label' => __( 'Retention Offer Title', 'wp-fusion' ),
			'type'  => 'text',
			'group' => 'woocommerce_subs',
		);

		return $meta_fields;
	}

	/**
	 * Adds the meta box to the cancellation offer post type.
	 *
	 * @since 3.44.12
	 */
	public function add_meta_boxes() {
		add_meta_box( 'wpf-meta', __( 'WP Fusion', 'wp-fusion' ), array( $this, 'cancellation_survey_meta_box' ), 'cancellation-offer', 'side' );
	}

	/**
	 * Outputs the content of the meta box.
	 *
	 * @since 3.44.12
	 */
	public function cancellation_survey_meta_box( $post ) {

		$settings = wp_parse_args( get_post_meta( $post->ID, 'wpf-settings', true ), $this->settings_defaults );

		/*
		// Apply tags
		*/

		echo '<label for="wpf-apply-tags">' . esc_html__( 'Apply these tags when a user submits a cancellation survey:', 'wp-fusion' ) . '</label><br />';

		echo '<p>';
		wpf_render_tag_multiselect(
			array(
				'setting'   => $settings['apply_tags'],
				'meta_name' => 'wpf-settings',
				'field_id'  => 'apply_tags',
			)
		);
		echo '</p>';

		echo '<label for="wpf-apply-tags">' . esc_html__( 'Apply these tags when a user accepts a retention offer:', 'wp-fusion' ) . '</label><br />';

		echo '<p>';
		wpf_render_tag_multiselect(
			array(
				'setting'   => $settings['apply_tags_retention_offer'],
				'meta_name' => 'wpf-settings',
				'field_id'  => 'apply_tags_retention_offer',
			)
		);
		echo '</p>';
	}
}

new WPF_Woo_Cancellation_Surveys();
