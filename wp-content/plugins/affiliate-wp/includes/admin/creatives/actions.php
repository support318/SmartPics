<?php
/**
 * Admin: Creatives Action Callbacks
 *
 * @package     AffiliateWP
 * @subpackage  Admin/Creatives
 * @copyright   Copyright (c) 2021, Sandhills Development, LLC
 * @license     http://opensource.org/licenses/gpl-2.0.php GNU Public License
 * @since       1.2
 */

/**
 * Process the add creative request
 *
 * @since 1.2
 * @return void
 */
function affwp_process_add_creative( $data ) {

	if ( ! is_admin() ) {
		return false;
	}

	if ( ! current_user_can( 'manage_creatives' ) ) {
		wp_die( __( 'You do not have permission to manage creatives', 'affiliate-wp' ), __( 'Error', 'affiliate-wp' ), array( 'response' => 403 ) );
	}

	if ( affwp_add_creative( $data ) ) {
		wp_safe_redirect( affwp_admin_url( 'creatives', array( 'affwp_notice' => 'creative_added' ) ) );
		exit;
	} else {
		wp_safe_redirect( affwp_admin_url( 'creatives', array( 'affwp_notice' => 'creative_added_failed' ) ) );
		exit;
	}

}
add_action( 'affwp_add_creative', 'affwp_process_add_creative' );

/**
 * Add creative meta
 *
 * @param int   $creative_id The Creative ID.
 * @param array $args Creative arguments.
 *
 * @since 2.17.0
 */
function affwp_process_add_creative_meta( int $creative_id, array $args ) {

	if ( 'qr_code' !== $args['type'] ) {
		return; // Bail if it is not a QR Code.
	}

	affwp_save_qrcode_colors(
		$creative_id,
		$args['qrcode_code_color'] ?? '',
		$args['qrcode_bg_color'] ?? ''
	);
}
add_action( 'affwp_insert_creative', 'affwp_process_add_creative_meta', 10, 2 );

/**
 * Process creative deletion requests
 *
 * @since 1.2
 * @param $data array
 * @return void
 */
function affwp_process_creative_deletion( $data ) {

	if ( ! is_admin() ) {
		return;
	}

	if ( ! current_user_can( 'manage_creatives' ) ) {
		wp_die( __( 'You do not have permission to delete a creative', 'affiliate-wp' ), __( 'Error', 'affiliate-wp' ), array( 'response' => 403 ) );
	}

	if ( ! wp_verify_nonce( $data['affwp_delete_creatives_nonce'], 'affwp_delete_creatives_nonce' ) ) {
		wp_die( __( 'Security check failed', 'affiliate-wp' ), __( 'Error', 'affiliate-wp' ), array( 'response' => 403 ) );
	}

	if ( empty( $data['affwp_creative_ids'] ) || ! is_array( $data['affwp_creative_ids'] ) ) {
		wp_die( __( 'No creative IDs specified for deletion', 'affiliate-wp' ), __( 'Error', 'affiliate-wp' ), array( 'response' => 400 ) );
	}

	$to_delete = array_map( 'absint', $data['affwp_creative_ids'] );

	foreach ( $to_delete as $creative_id ) {

		// Delete meta data.
		affiliatewp_delete_creative_meta( $creative_id, 'qrcode_code_color' );
		affiliatewp_delete_creative_meta( $creative_id, 'qrcode_bg_color' );

		affwp_delete_creative( $creative_id );
	}

	wp_safe_redirect( affwp_admin_url( 'creatives', array( 'affwp_notice' => 'creative_deleted' ) ) );
	exit;

}
add_action( 'affwp_delete_creatives', 'affwp_process_creative_deletion' );

/**
 * Process the add affiliate request
 *
 * @since 1.2
 * @return void
 */
function affwp_process_update_creative( $data ) {

	if ( ! is_admin() ) {
		return false;
	}

	if ( ! current_user_can( 'manage_creatives' ) ) {
		wp_die( __( 'You do not have permission to manage creatives', 'affiliate-wp' ), __( 'Error', 'affiliate-wp' ), array( 'response' => 403 ) );
	}

	if ( affwp_update_creative( $data ) ) {

		// Update QR Code colors if is QR Code type, otherwise delete meta.
		if ( 'qr_code' === $data['type'] ) {

			if ( isset( $data['qrcode_code_color'], $data['qrcode_bg_color'] ) ) {

				affwp_save_qrcode_colors(
					$data['creative_id'],
					$data['qrcode_code_color'],
					$data['qrcode_bg_color']
				);
			}
		} else {
			affiliatewp_delete_creative_meta( $data['creative_id'], 'qrcode_code_color' );
			affiliatewp_delete_creative_meta( $data['creative_id'], 'qrcode_bg_color' );
		}

		wp_safe_redirect( affwp_admin_url( 'creatives', array( 'action' => 'edit_creative', 'affwp_notice' => 'creative_updated', 'creative_id' => $data['creative_id'] ) ) );
		exit;
	} else {
		wp_safe_redirect( affwp_admin_url( 'creatives', array( 'action' => 'edit_creative', 'affwp_notice' => 'creative_update_failed' ) ) );
		exit;
	}

}
add_action( 'affwp_update_creative', 'affwp_process_update_creative' );

/**
 * Save QR Code color metadata.
 *
 * @since 2.17.0
 *
 * @param int    $creative_id The Creative ID.
 * @param string $code_color The Code Color.
 * @param string $bg_color The BG Color.
 */
function affwp_save_qrcode_colors( int $creative_id, string $code_color = '', string $bg_color = '' ) {

	$colors = affiliatewp_get_qrcode_default_colors();

	affiliatewp_update_creative_meta(
		$creative_id,
		'qrcode_code_color',
		empty( $code_color )
			? $colors['code']
			: sanitize_text_field( $code_color )
	);

	affiliatewp_update_creative_meta(
		$creative_id,
		'qrcode_bg_color',
		empty( $bg_color )
			? $colors['bg']
			: sanitize_text_field( $bg_color )
	);
}
