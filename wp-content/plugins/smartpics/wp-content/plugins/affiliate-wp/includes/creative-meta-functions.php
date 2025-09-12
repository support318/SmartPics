<?php
/**
 * Creative Meta Functions
 *
 * @package     AffiliateWP
 * @subpackage  Core
 * @copyright   Copyright (c) 2023, Awesome Motive, Inc
 * @license     http://opensource.org/licenses/gpl-2.0.php GNU Public License
 * @since       2.17.0
 */

/**
 * Retrieves meta from a creative.
 *
 * @since 2.17.0
 *
 * @param int    $creative_id Creative ID.
 * @param string $meta_key    Meta key.
 * @param bool   $single      Whether to return a single value for the given meta key.
 *
 * @return array|mixed An array of values if `$single` is false, otherwise a meta data value.
 */
function affiliatewp_get_creative_meta(int $creative_id, string $meta_key = '', bool $single = false ) {
	return affiliate_wp()->creative_meta->get_meta( $creative_id, $meta_key, $single );
}

/**
 * Adds meta to a creative.
 *
 * @since 2.17.0
 *
 * @param int    $creative_id Creative ID.
 * @param string $meta_key    Meta key.
 * @param mixed  $meta_value  Metadata value.
 * @param bool   $unique      Optional. Whether the same key should not be added. Default false.
 *
 * @return bool True on success, otherwise false.
 */
function affiliatewp_add_creative_meta(int $creative_id, string $meta_key, $meta_value, bool $unique = false ) : bool {
	return affiliate_wp()->creative_meta->add_meta( $creative_id, $meta_key, $meta_value, $unique );
}

/**
 * Updates creative meta.
 *
 * @since 2.17.0
 *
 * @param int    $creative_id Creative ID.
 * @param string $meta_key    Meta key.
 * @param mixed  $meta_value  Metadata value.
 * @param mixed  $prev_value  Optional. Previous value to check before removing. Default empty.
 *
 * @return bool True on success, otherwise false.
 */
function affiliatewp_update_creative_meta(int $creative_id, string $meta_key, $meta_value, $prev_value = '' ) : bool {
	return affiliate_wp()->creative_meta->update_meta( $creative_id, $meta_key, $meta_value, $prev_value );
}

/**
 * Deletes creative meta.
 *
 * @since 2.17.0
 *
 * @param int    $creative_id Creative ID.
 * @param string $meta_key    Meta key.
 * @param mixed  $meta_value  Metadata value.
 *
 * @return bool True if the deletion was successful, otherwise false.
 */
function affiliatewp_delete_creative_meta(int $creative_id, string $meta_key = '', $meta_value = '' ) : bool {
	return affiliate_wp()->creative_meta->delete_meta( $creative_id, $meta_key, $meta_value );
}
