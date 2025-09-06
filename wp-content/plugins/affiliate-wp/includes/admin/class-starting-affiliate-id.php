<?php
/**
 * Implements admin settings fields and relevant actions.
 *
 * @since 1.0.0
 * @since 2.18.0 Moved class from Starting Affiliate ID addon to core.
 *
 * @package    AffiliateWP
 * @subpackage AffiliateWP\Admin
 * @copyright  Copyright (c) 2023, Awesome Motive, Inc
 */

namespace AffiliateWP\Admin;

#[\AllowDynamicProperties]

/**
 * This class provides methods for managing the starting affiliate ID setting in AffiliateWP.
 * It allows synchronization of the starting affiliate ID value with the auto-increment value
 * in the affiliate table and adds the starting affiliate ID setting to the AffiliateWP settings page.
 *
 * @since 1.0.0
 * @since 2.18.0 Moved class from Starting Affiliate ID addon to core.
 */
class Starting_Affiliate_ID {

	/**
	 * Synchronizes the starting affiliate id value with the auto increment value in the affiliate table.
	 *
	 * Perform checks to confirm that the new value is valid, and updates the auto increment if so.
	 * Otherwise, this option will set the starting affiliate id to the minimum possible auto_increment value.
	 *
	 * @since 1.0.0
	 * @since 2.18.0 Changed to static method.
	 *
	 * @param mixed $new_settings array of new settings passed by update_option.
	 * @param mixed $old_settings array of previous settings passed by update_option.
	 *
	 * @return array of filtered $new_settings value.
	 */
	public static function sync_affiliate_id( $new_settings, $old_settings ) : array {

		$new_auto_increment = $new_settings['starting_affiliate_id'] ?? 0;
		$old_auto_increment = $old_settings['starting_affiliate_id'] ?? 0;

		if ( $new_auto_increment === $old_auto_increment ) {
			return $new_settings; // Nothing changed, just return.
		}

		$newest_affiliate = self::get_newest_affiliate_id();
		$auto_increment   = $newest_affiliate > $new_auto_increment ? $newest_affiliate + 1 : $new_auto_increment;

		$updated = self::update_affiliate_id_auto_increment( $auto_increment );

		// Reset the option to the minimum auto increment value if something went wrong.
		if ( ! $updated || $newest_affiliate > $new_auto_increment ) {
			$new_settings['starting_affiliate_id'] = $newest_affiliate + 1;
		}

		return $new_settings;
	}


	/**
	 * Fetches the newest affiliate from the database.
	 *
	 * @since 1.0.0
	 * @since 2.18.0 Changed to static method.
	 *
	 * @return int|mixed int affiliate object or field(s). Otherwise, returns 0.
	 */
	public static function get_newest_affiliate_id() {

		$affiliates = affiliate_wp()->affiliates->get_affiliates(
			array(
				'fields' => 'ids',
				'number' => 1,
				'order'  => 'DESC',
			)
		);

		return $affiliates[0] ?? 0;
	}

	/**
	 * Ensure the setting value will always be greater than the last Affiliate ID.
	 *
	 * @since 2.18.0
	 *
	 * @param mixed $value The final value.
	 *
	 * @return mixed
	 */
	public static function setting_value( $value ) {
		return max( $value, self::get_newest_affiliate_id() + 1 );
	}

	/**
	 * Updates the affiliate ID auto increment to the specified value.
	 *
	 * @since 1.0.0
	 * @since 2.18.0 Changed to static method and SQL security improvements.
	 *
	 * @param int $auto_increment The auto increment value to set.
	 *
	 * @return bool True if update was successful, otherwise false.
	 */
	public static function update_affiliate_id_auto_increment( int $auto_increment ) : bool {

		global $wpdb;

		return false !== $wpdb->query(
			esc_sql(
				sprintf(
					'ALTER TABLE %s MODIFY `affiliate_id` bigint(20) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=%d;',
					affiliate_wp()->affiliates->table_name,
					absint( $auto_increment )
				)
			)
		);
	}

	/**
	 * Adds the starting affiliate ID setting to the AffiliateWP settings page.
	 *
	 * @since 1.0.0
	 * @since 2.18.0 Changed to static method.
	 *
	 * @param array $settings Array of settings provided by AffiliateWP.
	 *
	 * @return array of filtered settings.
	 */
	public static function add_starting_affiliate_id_setting( array $settings ) : array {

		$minimum = self::get_newest_affiliate_id() + 1;

		$settings['starting_affiliate_id'] = array(
			'name' => __( 'Starting Affiliate ID', 'affiliate-wp' ),
			'desc' =>  esc_html__( 'The initial ID for new affiliate registrations. This ID will always be higher than the most recent affiliate ID, whether it\'s the first registration or subsequent ones.', 'affiliate-wp' ),
			'type' => 'number',
			'max'  => 1000000,
			'min'  => $minimum,
			'step' => 1,
			'size' => 'medium',
			'std'  => $minimum,
		);

		return $settings;
	}
}
