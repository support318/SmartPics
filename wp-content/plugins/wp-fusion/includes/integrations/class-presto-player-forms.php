<?php

use PrestoPlayer\Models\Video;

if ( ! defined( 'ABSPATH' ) ) {
	exit; // Exit if accessed directly
}

class WPF_Presto_Player_Forms extends WPF_Integrations_Base {

	/**
	 * The slug for WP Fusion's module tracking.
	 *
	 * @since 3.44.23
	 * @var string $slug
	 */

	public $slug = 'presto-player-forms';

	/**
	 * The plugin name for WP Fusion's module tracking.
	 *
	 * @since 3.44.23
	 * @var string $name
	 */
	public $name = 'Presto Player';

	/**
	 * The link to the documentation on the WP Fusion website.
	 *
	 * @since 3.44.23
	 * @var string $docs_url
	 */
	public $docs_url = 'https://wpfusion.com/documentation/other/presto-player/';

	/**
	 * Gets things started.
	 *
	 * @since 3.44.23
	 */
	public function init() {
		add_filter( 'wpf_configure_settings', array( $this, 'register_settings' ), 15, 2 );
		add_action( 'presto_player/pro/forms/save', array( $this, 'save_form' ), 10, 4 );
	}

	/**
	 * Registers Presto Player settings.
	 *
	 * @since 3.44.23
	 *
	 * @param array $settings The settings.
	 * @param array $options The saved options.
	 *
	 * @return array The settings.
	 */
	public function register_settings( $settings, $options ) {

		$settings['presto_player_header'] = array(
			'title'   => __( 'Presto Player Integration', 'wp-fusion' ),
			'url'     => 'https://wpfusion.com/documentation/other/presto-player/',
			'type'    => 'heading',
			'section' => 'integrations',
		);

		$settings['enable_form_capture'] = array(
			'title'   => __( 'Email Capture', 'wp-fusion' ),
			'desc'    => __( 'Enable email form capture for Presto Player.', 'wp-fusion' ),
			'type'    => 'checkbox',
			'tooltip' => __( 'Please first enable Email Capture in the video preset for this feature to work.', 'wp-fusion' ),
			'section' => 'integrations',
		);

		$settings['presto_player_tags'] = array(
			'title'   => __( 'Apply Tags', 'wp-fusion' ),
			'desc'    => __( 'These tags will be applied to anyone who submits the form.', 'wp-fusion' ),
			'type'    => 'assign_tags',
			'section' => 'integrations',
		);

		return $settings;
	}

	/**
	 * Save form data.
	 *
	 * @since 3.44.23
	 *
	 * @param array                      $data    The form data.
	 * @param PrestoPlayer\Models\Preset $preset  The preset data.
	 * @param WP_Post                    $saved   The saved data.
	 * @param bool                       $created Whether or not the entry was created.
	 */
	public function save_form( $data, $preset, $saved, $created ) {

		if ( empty( $data['email'] ) ) {
			return;
		}

		$video = new Video( $data['video_id'] );

		$email_address = $data['email'];
		$update_data   = array(
			'user_email' => $email_address,
			'first_name' => $data['firstname'] ?? '',
			'last_name'  => $data['lastname'] ?? '',
		);

		$update_data = wp_fusion()->crm->map_meta_fields( $update_data );

		$args = array(
			'email_address'    => $email_address,
			'update_data'      => $update_data,
			'apply_tags'       => wpf_get_option( 'presto_player_tags', array() ),
			'integration_slug' => $this->slug,
			'integration_name' => $this->name,
			'form_id'          => $data['video_id'],
			'entry_id'         => $saved->ID,
			'form_title'       => $video->title,
		);

		WPF_Forms_Helper::process_form_data( $args );
	}
}

new WPF_Presto_Player_Forms();
