<?php
/**
 * Creatives Functions
 *
 * @package     AffiliateWP
 * @subpackage  Core
 * @copyright   Copyright (c) 2014, Sandhills Development, LLC
 * @license     http://opensource.org/licenses/gpl-2.0.php GNU Public License
 * @since       1.1
 */

/**
 * Retrieves the creative object.
 *
 * @since 1.1.4
 * @since 2.17.0 Check if is a PRO license before retrieving QR Code Creatives.
 *
 * @param int|AffWP\Creative $creative Creative ID or object.
 * @return AffWP\Creative|false Creative object, otherwise false.
 */
function affwp_get_creative( $creative = null ) {

	if ( is_object( $creative ) && isset( $creative->creative_id ) ) {
		$creative_id = $creative->creative_id;
	} elseif( is_numeric( $creative ) ) {
		$creative_id = absint( $creative );
	} else {
		return false;
	}

	return affiliate_wp()->creatives->get_object( $creative_id );
}

/**
 * Adds a new creative to the database.
 *
 * @since 1.1.4
 * @since 1.9.6 Modified to return the creative ID on success vs true.
 * @since 2.14.0 Added type field and only save image when creative is image type.
 *
 * @param array $data Creative data.
 * @return int|false ID of the newly-created creative, otherwise false.
 */
function affwp_add_creative( array $data = array() ) {

	$args = array(
		'name'        => ! empty( $data['name'] ) ? sanitize_text_field( $data['name'] ) : __( 'Creative', 'affiliate-wp' ),
		'description' => ! empty( $data['description'] ) ? wp_kses_post( $data['description'] ) : '',
		'url'         => ! empty( $data['url'] ) ? esc_url_raw( $data['url'] ) : get_site_url(),
		'text'        => ! empty( $data['text'] ) ? sanitize_text_field( $data['text'] ) : get_bloginfo( 'name' ),
		'image'       => isset( $data['type'] ) && 'image' === $data['type'] && ! empty( $data['image'] )
			? esc_url( $data['image'] )
			: '',
		'type'        => affwp_parse_creative_type( $data['type'] ),
		'status'      => ! empty( $data['status'] ) ? sanitize_text_field( $data['status'] ) : '',
		'date'        => ! empty( $data['date'] ) ? $data['date'] : '',
		'notes'       => ! empty( $data['notes'] ) ? wp_kses_post( $data['notes'] ) : '',
		'start_date'  => ! empty( $data['start_date'] ) && false === affwp_is_upgrade_required( 'pro' ) ? gmdate( 'Y-m-d H:i:s', strtotime( $data['start_date'] ) ) : '',
		'end_date'    => ! empty( $data['end_date'] ) && false === affwp_is_upgrade_required( 'pro' ) ? gmdate( 'Y-m-d H:i:s', strtotime( $data['end_date'] ) ) : '',
	);

	// Append the QR Code colors data to be saved as metadata after inserting the creative.
	if ( 'qr_code' === $args['type'] ) {

		$args = array_merge(
			$args,
			array(
				'qrcode_code_color' => $data['qrcode_code_color'] ?? '',
				'qrcode_bg_color'   => $data['qrcode_bg_color'] ?? '',
			)
		);
	}

	// Check the start and end dates and maybe change the status.
	$args['status'] = affwp_determine_schedule_status( $args['status'], $args['start_date'], $args['end_date'] );

	if ( $creative_id = affiliate_wp()->creatives->add( $args ) ) {
		return $creative_id;
	}

	return false;
}

/**
 * Correctly parses the Creative's type, returning the sanitized value,
 * at the same time check if the type is allowed for the active customer's license
 * and fallback to text if is not.
 *
 * @since 2.17.0
 *
 * @param string $type The type to analyze.
 *
 * @return string The parsed type.
 */
function affwp_parse_creative_type( string $type ) : string {

	if ( in_array( $type, array_keys( affwp_get_creative_types() ), true ) ) {
		return sanitize_text_field( $type );
	}

	return '';
}

/**
 * Updates a creative.
 *
 * @since 1.1.4
 * @since 2.14.0 Added type field and only save image when creative is image type.
 *
 * @param array $data Creative data.
 * @return bool True if the creative was updated, otherwise false.
 */
function affwp_update_creative( array $data = array() ) : bool {

	if ( empty( $data['creative_id'] ) || ( ! $creative = affwp_get_creative( $data['creative_id'] ) ) ) {
		return false;
	}

	$args = array(
		'name'          => ! empty( $data['name'] ) ? sanitize_text_field( $data['name'] ) : __( 'Creative', 'affiliate-wp' ),
		'description'   => ! empty( $data['description'] ) ? wp_kses_post( $data['description'] ) : '',
		'url'           => ! empty( $data['url'] ) ? esc_url_raw( $data['url'] ) : get_site_url(),
		'text'          => ! empty( $data['text'] ) ? sanitize_text_field( $data['text'] ) : get_bloginfo( 'name' ),
		'image'         => 'image' === $data['type'] && ! empty( $data['image'] )
			? esc_url( $data['image'] )
			: '',
		'attachment_id' => 'image' === $data['type'] && ! empty( $data['image'] )
			? attachment_url_to_postid( esc_url( $data['image'] ) )
			: 0,
		'type'          => affwp_parse_creative_type( $data['type'] ),
		'status'        => ! empty( $data['status'] ) ? sanitize_text_field( $data['status'] ) : '',
		'date_updated'  => gmdate( 'Y-m-d H:i:s' ),
		'notes'         => ! empty( $data['notes'] ) ? wp_kses_post( $data['notes'] ) : '',
		'start_date'    => ! empty( $data['start_date'] ) && false === affwp_is_upgrade_required( 'pro' ) ? gmdate( 'Y-m-d H:i:s', strtotime( $data['start_date'] ) ) : '',
		'end_date'      => ! empty( $data['end_date'] ) && false === affwp_is_upgrade_required( 'pro' ) ? gmdate( 'Y-m-d H:i:s', strtotime( $data['end_date'] ) ) : '',
	);

	// Append the QR Code colors data to be saved as metadata after inserting the creative.
	if ( 'qr_code' === $args['type'] ) {

		$args = array_merge(
			$args,
			array(
				'qrcode_code_color' => $data['qrcode_code_color'] ?? '',
				'qrcode_bg_color'   => $data['qrcode_bg_color'] ?? '',
			)
		);
	}

	// Check the start and end dates and maybe change the status.
	$args['status'] = affwp_determine_schedule_status( $args['status'], $args['start_date'], $args['end_date'] );

	if ( affiliate_wp()->creatives->update( $creative->ID, $args, '', 'creative' ) ) {
		return true;
	}

	return false;

}

/**
 * Deletes a creative record.
 *
 * @since 1.2
 *
 * @param int|\AffWP\Creative $creative Creative to delete.
 * @return bool True if the record was deleted, otherwise false.
 */
function affwp_delete_creative( $creative ) {

	if ( ! $creative = affwp_get_creative( $creative ) ) {
		return false;
	}

	/**
	 * Delete creative.
	 *
	 * @since 2.12.0
	 *
	 * @param int $creative_id Creative ID.
	 */
	do_action( 'affwp_delete_creative', $creative->ID );

	return affiliate_wp()->creatives->delete( $creative->ID, 'creative' );
}

/**
 * Sets the status for a creative.
 *
 * @since 1.0
 *
 * @param int|\AffWP\Creative $creative Creative ID or object.
 * @param string              $status   Optional. Status to give the creative. Default empty.
 * @return bool True if the creative was updated with the new status, otherwise false.
 */
function affwp_set_creative_status( $creative, $status = '' ) {

	if ( ! $creative = affwp_get_creative( $creative ) ) {
		return false;
	}

	$old_status = $creative->status;

	/**
	 * Fires immediately before the creative's status has been updated.
	 *
	 * @since 1.0
	 *
	 * @param int    $creative_id Creative ID.
	 * @param string $status      New creative status.
	 * @param string $old_status  Old creative status.
	 */
	do_action( 'affwp_set_creative_status', $creative->ID, $status, $old_status );

	if ( affiliate_wp()->creatives->update( $creative->ID, array( 'status' => $status ), '', 'creative' ) ) {
		return true;
	}

}

/**
 * Sets the start date for a creative.
 *
 * @since 2.15.0
 *
 * @param int|\AffWP\Creative $creative   Creative ID or object.
 * @param string              $start_date Optional. Start date to give the creative. Default empty.
 * @return bool True if the creative was updated with the new start date, otherwise false.
 */
function affwp_set_creative_start_date( $creative, $start_date = '' ) {
	if ( ! $creative = affwp_get_creative( $creative ) ) {
		return false;
	}

	$old_start_date = $creative->start_date;
	$old_end_date   = $creative->end_date;
	$old_status     = $creative->status;

	// If the end date is not valid with the new start date, clear it.
	$end_date = ! empty( $start_date ) && '0000-00-00 00:00:00' !== $start_date && '0000-00-00 00:00:00' !== $old_end_date && $start_date >= $old_end_date ? '' : $old_end_date;

	// Check the start and end dates and maybe change the status.
	$status = affwp_determine_schedule_status( $old_status, $start_date, $end_date );

	$args = array(
		'start_date' => $start_date,
		'end_date'   => $end_date,
		'status'     => $status
	);

	/**
	 * Fires immediately before the creative's start date has been updated.
	 *
	 * @since 2.15.0
	 *
	 * @param int    $creative_id    Creative ID.
	 * @param array  $args           Array of arguments for the creative.
	 * @param string $old_start_date Old creative start date.
	 * @param string $old_end_date   Old creative end date.
	 * @param string $old_status     Old creative status.
	 */
	do_action( 'affwp_set_creative_start_date', $creative->ID, $args, $old_start_date, $old_end_date, $old_status );

	if ( affiliate_wp()->creatives->update( $creative->ID, $args, '', 'creative' ) ) {

		return true;
	}

	return false;
}

/**
 * Sets the end date for a creative and updates to the appropriate status.
 *
 * @since 2.15.0
 *
 * @param int|\AffWP\Creative $creative Creative ID or object.
 * @param string              $end_date Optional. End date to give the creative. Default empty.
 * @return bool True if the creative was updated with the new end date, otherwise false.
 */
function affwp_set_creative_end_date( $creative, $end_date = '' ) {

	if ( ! $creative = affwp_get_creative( $creative ) ) {
		return false;
	}

	$old_start_date = $creative->start_date;
	$old_end_date   = $creative->end_date;
	$old_status     = $creative->status;

	// If the start date is not valid with the new end date, clear it.
	$start_date = ! empty( $end_date ) && '0000-00-00 00:00:00' !== $end_date && '0000-00-00 00:00:00' !== $old_start_date && $end_date < $old_start_date ? '' : $old_start_date;

	$args = array(
		'start_date' => $start_date,
		'end_date'   => $end_date,
		'status'     => affwp_determine_schedule_status( $old_status, $start_date, $end_date ),
	);

	/**
	 * Fires immediately before the creative's end date has been updated.
	 *
	 * @since 2.15.0
	 *
	 * @param int    $creative_id    Creative ID.
	 * @param array  $args           Array of arguments for the creative.
	 * @param string $old_start_date Old creative start date.
	 * @param string $old_end_date   Old creative end date.
	 * @param string $old_status     Old creative status.
	 */
	do_action( 'affwp_set_creative_end_date', $creative->ID, $args, $old_start_date, $old_end_date, $old_status );

	if ( affiliate_wp()->creatives->update( $creative->ID, $args, '', 'creative' ) ) {

		return true;
	}

	return false;
}

/**
 * Retrieves a creative by a given field and value.
 *
 * @since 2.7
 *
 * @param string $field Creative object field.
 * @param mixed  $value Field value.
 * @return \AffWP\Creative|\WP_Error Creative object if found, otherwise a WP_Error object.
 */
function affwp_get_creative_by( $field, $value ) {
	$result = affiliate_wp()->creatives->get_by( $field, $value );

	if ( is_object( $result ) ) {
		$creative = affwp_get_creative( intval( $result->creative_id ) );
	} else {
		$creative = new \WP_Error(
			'invalid_creative_field',
			sprintf( 'No creative could be retrieved with a(n) \'%1$s\' field value of %2$s.', $field, $value )
		);
	}

	return $creative;
}

/**
 * Retrieves the list of creative types and corresponding labels.
 *
 * @since 2.14.0
 * @since 2.16.0 Added plural argument.
 * @since 2.17.0 Added QR Code type.
 *
 * @return array Key/value pairs of types where key is the type and the value is the label.
 */
function affwp_get_creative_types( bool $use_plurals = false ) : array {

	if ( $use_plurals ) {

		return array(
			'image'     => __( 'Images', 'affiliate-wp' ),
			'qr_code'   => __( 'QR Codes', 'affiliate-wp' ),
			'text_link' => __( 'Text Links', 'affiliate-wp' ),
		);
	}

	return array(
		'image'     => __( 'Image', 'affiliate-wp' ),
		'qr_code'   => __( 'QR Code', 'affiliate-wp' ),
		'text_link' => __( 'Text Link', 'affiliate-wp' ),
	);
}

/**
 * Return the sanitized value from type input.
 *
 * @since 2.16.0
 * @param string $input_type Accepts GET or POST.
 * @return string The creative type.
 */
function affwp_filter_creative_type_input( string $input_type = 'GET' ) : string {

	$input_type = in_array( $input_type, array( 'GET', 'POST' ), true )
		? 'INPUT_' . strtoupper( $input_type )
		: 'INPUT_GET';

	$input_value = filter_input( constant( $input_type ), 'type' );

	if ( $input_value && in_array( $input_value, array_keys( affwp_get_creative_types() ), true ) ) {
		return sanitize_text_field( $input_value );
	}

	return '';
}

/**
 * Return the datetime when the creative name turned public.
 * If none is found in DB, we fallback to the 2.16.0 release date.
 *
 * @since 2.16.0
 * @return string
 */
function affwp_get_creative_name_upgrade_date() : string {

	return affwp_date_i18n(
		strtotime(
			get_option( 'affwp_creative_name_upgrade_date', '2023-08-22 00:00:00' )
		),
		'Y-m-d H:i:s'
	);
}

/**
 * Retrieves the creative's status and returns a translatable string.
 *
 * @since 2.15.0
 *
 * @param int|AffWP\Creative $creative Optional. Creative ID or object. Default current creative.
 * @return string $status_label A translatable, filterable label indicating creative status.
 */
function affwp_get_creative_status_label( $creative_or_status = 0 ) {

	if ( is_string( $creative_or_status ) ) {
		$creative = null;
		$status   = $creative_or_status;
	} else {
		$creative = affwp_get_creative( $creative_or_status );

		if ( isset( $creative->status ) ) {
			$status = $creative->status;
		} else {
			return '';
		}
	}

	$statuses = affwp_get_creative_statuses();
	$label    = array_key_exists( $status, $statuses ) ? $statuses[ $status ] : '';

	/**
	 * Filters the creative status label.
	 *
	 * @since 2.15.0
	 *
	 * @param string              $label    Localized status label string.
	 * @param AffWP\Creative|null $creative Creative object or null.
	 * @param string              $status   Creative status.
	 */
	return apply_filters( 'affwp_get_creative_status_label', $label, $creative, $status );
}

/**
 * Retrieves the list of creative statuses and corresponding labels.
 *
 * @since 2.15.0
 *
 * @return array Key/value pairs of statuses where key is the status and the value is the label.
 */
function affwp_get_creative_statuses() : array {

	return array(
		'active'    => __( 'Active', 'affiliate-wp' ),
		'inactive'  => __( 'Inactive', 'affiliate-wp' ),
		'scheduled' => __( 'Scheduled', 'affiliate-wp' ),
	);
}

/**
 * Is a creative private.
 *
 * @since 2.15.0
 * @since 2.16.0 Removed `LIMIT 1` when pulling connected groups.
 *
 * @param object $creative Creative object.
 *
 * @return bool
 */
function affwp_creative_is_private( $creative ) {

	if ( ! is_a( $creative, 'AffWP\Creative' ) ) {
		return false;
	}

	global $wpdb;

	$connected_affiliates = $wpdb->get_results(
		$wpdb->prepare(

			// phpcs:ignore WordPress.DB.PreparedSQL.NotPrepared -- Table name injected does not need surrounding ''.
			str_replace(
				'{table_name}',
				affiliate_wp()->connections->table_name, // phpcs:ignore WordPress.DB.PreparedSQL.NotPrepared -- Table name injected does not need surrounding ''.
				'SELECT `affiliate` FROM {table_name} WHERE `creative` = %d AND `affiliate` IS NOT NULL LIMIT 1'
			),
			$creative->creative_id
		)
	);

	if ( count( $connected_affiliates ) > 0 ) {
		return true;
	}

	$connected_affiliate_groups = array_filter(
		$wpdb->get_results(
			$wpdb->prepare(

				// phpcs:ignore WordPress.DB.PreparedSQL.NotPrepared -- Table name injected does not need surrounding ''.
				str_replace(
					'{table_name}',
					affiliate_wp()->connections->table_name, // phpcs:ignore WordPress.DB.PreparedSQL.NotPrepared -- Table name injected does not need surrounding ''.
					'SELECT `group` FROM {table_name} WHERE `creative` = %d AND `group` IS NOT NULL'
				),
				$creative->creative_id
			)
		),
		function( $object ) {

			$group_id = $object->group ?? null;

			if ( is_null( $group_id ) ) {
				return false;
			}

			return 'affiliate-group' === affiliate_wp()->groups->get_group_type( intval( $group_id ) );
		}
	);

	return count( $connected_affiliate_groups ) > 0;
}

/**
 * Privacy field for edit/add creative.
 *
 * @since 2.15.0
 *
 * @param object|null $creative Creative object or null for none (add).
 *
 * @return void Displays field only.
 */
function affwp_creative_privacy_toggle( $creative = null ) {

	$private = null === $creative
		? false
		: affwp_creative_is_private( $creative );

	?>

	<tr class="form-row hidden" data-row="privacy">

		<th scope="row">
			<label for="creative-privacy"><?php esc_html_e( 'Privacy', 'affiliate-wp' ); ?></label>
		</th>

		<td>
			<!-- Note, this field isn't actually saved to the DB for this creative, but instead triggers other fields. -->
			<select id="creative-privacy" class="affwp-use-select2">
				<option value="public" <?php selected( true, ! $private ); ?>><?php esc_html_e( 'Public', 'affiliate-wp' ); ?></option>
				<option value="private"<?php selected( true, $private ); ?>><?php esc_html_e( 'Private', 'affiliate-wp' ); ?></option>
			</select>

			<p class="description"><?php esc_html_e( 'Select whether you want this creative to be public or private.', 'affiliate-wp' ); ?></p>
		</td>

	</tr>

	<?php
}

/**
 * Determine the status of a scheduled creative.
 *
 * @since 2.15.0
 *
 * @param string $status     Creative status.
 * @param string $start_date Creative start date.
 * @param string $end_date   Creative end date.
 *
 * @return string Creative status.
 */
function affwp_determine_schedule_status( $status, $start_date = '', $end_date = '' ) {

	// Check for empty/null dates.
	$start_date = empty( $start_date ) || '0000-00-00 00:00:00' === $start_date ? false : gmdate( 'Y-m-d H:i:s', strtotime( $start_date ) );
	$end_date   = empty( $end_date ) || '0000-00-00 00:00:00' === $end_date ? false : gmdate( 'Y-m-d H:i:s', strtotime( $end_date ) );

	// Get current datetime.
	$now = gmdate( 'Y-m-d H:i:s', strtotime( 'now' ) + affiliate_wp()->utils->wp_offset );

	if ( false === $start_date && false === $end_date ) {
		// If the status is scheduled, but there no dates, set the status to active.
		if ( 'scheduled' === $status ) {
			return 'active';
		}
		return $status;
	}

	// Only end date set and not reached.
	if ( false !== $end_date && false === $start_date && $end_date > $now ) {
		return 'active';
	}

	// Only end date set and reached.
	if ( false !== $end_date && false === $start_date && $end_date <= $now ) {
		return 'inactive';
	}

	// Both start and end date set (Neither date has been reached).
	if ( false !== $start_date && false !== $end_date && $start_date > $now && $end_date > $now ) {
		return 'scheduled';
	}

	// Both start and end date set (start date reached and end date not reached).
	if ( false !== $start_date && false !== $end_date && $start_date <= $now && $end_date > $now ) {
		return 'active';
	}

	// Both start and end date set (both reached).
	if ( false !== $start_date && false !== $end_date && $start_date <= $now && $end_date <= $now ) {
		return 'inactive';
	}

	// Only start date set and date not reached.
	if ( false !== $start_date && false === $end_date && $start_date > $now ) {
		return 'scheduled';
	}

	// Only start date set and date reached.
	if ( false !== $start_date && false === $end_date && $start_date <= $now ) {
		return 'active';
	}

	// Just in case.
	return 'active';

}

/**
 * Get the creative's schedule description.
 *
 * @since 2.15.0
 *
 * @param int|AffWP\Creative $creative_or_id Creative ID or object.
 * return string Description of the creative's schedule.
 */
function affwp_get_creative_schedule_desc( $creative_or_id ) : string {

	$creative = is_int( $creative_or_id )
		? affwp_get_creative( $creative_or_id )
		: $creative_or_id;

	// Bail if no creative or no scheduling feature.
	if ( false === $creative || false === affwp_has_scheduling_feature( $creative ) ) {
		return '';
	}

	// Check for empty/null dates.
	$start_date = empty( $creative->start_date ) || '0000-00-00 00:00:00' === $creative->start_date ? false : gmdate( 'Y-m-d H:i:s', strtotime( $creative->start_date ) );
	$end_date   = empty( $creative->end_date ) || '0000-00-00 00:00:00' === $creative->end_date ? false : gmdate( 'Y-m-d H:i:s', strtotime( $creative->end_date ) );

	// Get current datetime.
	$now = gmdate( 'Y-m-d H:i:s', strtotime( 'now' ) + affiliate_wp()->utils->wp_offset );

	// Only end date set and not reached.
	if ( false !== $end_date && false === $start_date && $end_date > $now ) {
		return sprintf( /* translators: Creative is Active until this date. */
			__( 'Creative currently Active until %s.', 'affiliate-wp' ),
			affwp_date_i18n( strtotime( $end_date )	)
		);
	}

	// Only end date set and reached.
	if ( false !== $end_date && false === $start_date && $end_date <= $now ) {
		return sprintf( /* translators: Creative is Inactive since this date. */
			__( 'Creative Inactive since %s.', 'affiliate-wp' ),
			affwp_date_i18n( strtotime( $end_date )	)
		);
	}

	// Both start and end date set (Neither date has been reached).
	if ( false !== $start_date && false !== $end_date && $start_date > $now && $end_date > $now ) {
		return sprintf( /* translators: Creative is scheduled to be Active between these dates. */
			__( 'Creative will become Active on %1$s until %2$s.', 'affiliate-wp' ),
			affwp_date_i18n( strtotime( $start_date ) ),
			affwp_date_i18n( strtotime( $end_date )	)
		);
	}

	// Both start and end date set (start date reached and end date not reached).
	if ( false !== $start_date && false !== $end_date && $start_date <= $now && $end_date > $now ) {
		return sprintf( /* translators: Creative is Active until this date. */
			__( 'Creative currently Active until %s.', 'affiliate-wp' ),
			affwp_date_i18n( strtotime( $end_date )	)
		);
	}

	// Both start and end date set (both reached).
	if ( false !== $start_date && false !== $end_date && $start_date <= $now && $end_date <= $now ) {
		return sprintf( /* translators: Creative is Inactive since this date */
			__( 'Creative Inactive since %s.', 'affiliate-wp' ),
			affwp_date_i18n( strtotime( $end_date )	)
		);
	}

	// Only start date set and date not reached.
	if ( false !== $start_date && false === $end_date && $start_date > $now ) {
		return sprintf( /* translators: Creative is scheduled to be Active on this date. */
			__( 'Creative will become Active on %s.', 'affiliate-wp' ),
			affwp_date_i18n( strtotime( $start_date ) )
		);
	}

	// Only start date set and date reached.
	if ( false !== $start_date && false === $end_date && $start_date <= $now ) {
		return sprintf( /* translators: Creative Active since this date. */
			__( 'Creative Active since %s.', 'affiliate-wp' ),
			affwp_date_i18n( strtotime( $start_date ) )
		);
	}

	return '';
}

/**
 * Check if a creative has a schedule.
 *
 * @since 2.15.0
 *
 * @param int|AffWP\Creative $creative_or_id Creative ID or object.
 * @return bool True if the creative has a schedule, false otherwise.
 */
function affwp_has_scheduling_feature( $creative_or_id ) : bool {
	$creative = is_int( $creative_or_id )
		? affwp_get_creative( $creative_or_id )
		: $creative_or_id;

	if ( false === $creative ) {
		return false;
	}

	// No start or end date set so it's not scheduled.
	if ( '0000-00-00 00:00:00' === $creative->start_date && '0000-00-00 00:00:00' === $creative->end_date ) {
		return false;
	}

	return true;
}
