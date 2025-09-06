<?php // phpcs:ignore WordPress.Files.FileName.InvalidClassFileName -- The name of the tile is common among others.
/**
 * AffiliateWP Group
 *
 * @package     AffiliateWP
 * @subpackage  Groups
 * @copyright   Copyright (c) 2020, Sandhills Development, LLC
 * @license     http://opensource.org/licenses/gpl-2.0.php GNU Public License
 * @since       2.12.0
 * @author      Aubrey Portwood <aubrey@awesomeomotive.com>
 */

// phpcs:disable PEAR.Functions.FunctionCallSignature.ContentAfterOpenBracket -- Ignore formatting for a better preference in this file.
// phpcs:disable PEAR.Functions.FunctionCallSignature.CloseBracketLine -- Ignore formatting for a better preference in this file.
// phpcs:disable PEAR.Functions.FunctionCallSignature.EmptyLine -- Empty lines are okay.
// phpcs:disable Generic.WhiteSpace.ScopeIndent.Incorre_ct — Empty lines are okay.

namespace AffiliateWP\Groups;

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

if ( class_exists( '\AffiliateWP\Groups\Group' ) ) {
	return;
}

affwp_require_util_traits( 'data' );

#[\AllowDynamicProperties]

/**
 * AffiliateWP Group
 *
 * @since 2.12.0
 *
 * @see Affiliate_WP_DB
 */
final class Group {

	use \AffiliateWP\Utils\Data;

	/**
	 * The value of `group_id` in the database.
	 *
	 * @since 2.12.0
	 *
	 * @var int
	 */
	public $group_id = 0;

	/**
	 * Construct a group.
	 *
	 * @since 2.12.0
	 *
	 * @param int $group_id The ID of the group in the database.
	 *                      We are trusting you to ensure the group exists in the database.
	 *
	 * @throws \InvalidArgumentException If you do not supply a numeric (and positive) `$group_id`.
	 * @throws \Exception                If you try and create a group for a group that isn't in the database.
	 */
	public function __construct( $group_id ) {

		if ( ! $this->is_numeric_and_gt_zero( $group_id ) ) {
			throw new \InvalidArgumentException( '$group_id must be a postive number (greater than zero).' );
		}

		// Might throw exceptions, and might be a WP_Error.
		$this->group_id = affiliate_wp()->groups->group_exists( $group_id ) ? intval( $group_id ) : false;

		if ( ! $this->is_numeric_and_gt_zero( $this->group_id ) ) {
			throw new \Exception( 'Cannot create object for group that does not exist in the database.' );
		}
	}

	/**
	 * Delete this group.
	 *
	 * Note, this completely removes the group from the database.
	 *
	 * @since  2.12.0
	 *
	 * @return bool `true` if it was deleted, `false` if not.
	 */
	public function delete() {
		return affiliate_wp()->groups->delete_group( $this->group_id );
	}

	/**
	 * Get the id.
	 *
	 * @since  2.12.0
	 *
	 * @return int
	 */
	public function get_id() {
		return $this->group_id;
	}

	/**
	 * Get by instance.
	 *
	 * @since  2.12.0
	 *
	 * @param int $group_id The group id in the database (group_id).
	 *
	 * @return \AffiliateWP\Groups\Group
	 *
	 * @see Affiliate_WP_DB->get_core_object().
	 */
	public static function get_instance( $group_id ) {
		return new \AffiliateWP\Groups\Group( $group_id );
	}

	/**
	 * Get this group's meta.
	 *
	 * @since  2.12.0
	 * @since  2.13.0 Added default incase value isn't present.
	 *
	 * @param string $key     If you want a specific key from the meta array, you can specify that here.
	 * @param mixed  $default Default if meta isn't present.
	 *
	 * @return mixed See \AffiliateWP\Groups\DB::get_group_meta() for successful data.
	 *                   Mixed if you ask for a key value of the meta array and it's there.
	 *                   `null` if you ask for a meta key that isn't in the meta array.
	 */
	public function get_meta( string $key = '', $default = null ) {

		$meta = affiliate_wp()->groups->get_group_meta( $this->group_id );

		if ( ! is_array( $meta ) ) {

			return new \WP_Error(
				'unexpected_value',
				'We expected an array for group meta, but got something else, there may be database damage.',
				array(
					'group_id' => $this->group_id,
					'meta'     => $meta,
					'key'      => $key,
				)
			);
		}

		if ( $this->is_string_and_nonempty( $key ) ) {

			if ( ! isset( $meta[ $key ] ) ) {
				return $default;
			}

			return $meta[ $key ];
		}

		return $meta;
	}

	/**
	 * Get this group's title.
	 *
	 * @since  2.12.0
	 *
	 * @return mixed See `\AffiliateWP\Groups\DB::get_group_title()`.
	 */
	public function get_title() {
		return affiliate_wp()->groups->get_group_title( $this->group_id );
	}

	/**
	 * Get the value for `type` from the database.
	 *
	 * @since  2.12.0
	 *
	 * @return mixed See `\AffiliateWP\Groups\DB::get_group_type()` for successful data
	 *                   `WP_Error` if we get back unexpected data.
	 */
	public function get_type() {
		return affiliate_wp()->groups->get_group_type( $this->group_id );
	}

	/**
	 * Update this group in the database.
	 *
	 * @since  2.12.0
	 *
	 * @param array  $args  Arguments for `$args` in `\AffiliateWP\Groups\DB->update( $group_id, $args )`.
	 * @param array  $raw   If you want to update the DB directly, set the to `true` and see
	 *                      \Affiliate_WP_DB::update for return types.
	 * @param mixed  $where If `$raw` is `true` used for `\Affiliate_WP_DB::update()` method.
	 * @param string $type  Ignored, always set to `group`.
	 *
	 * @return mixed See `\AffiliateWP\Groups\DB->update_group()` for return types.
	 *
	 * @throws \InvalidArgumentException If $args is not an array.
	 */
	public function update( $args, $raw = false, $where = '', $type = null ) {

		if ( ! is_array( $args ) ) {
			throw new \InvalidArgumentException( '$args must be an array' );
		}

		if ( ! is_bool( $raw ) ) {
			throw new \InvalidArgumentException( '$raw must be true or false.' );
		}

		$args = array_merge(
			$args,
			array(
				'type' => $this->get_type(),
			)
		);

		if ( true === $raw ) {

			// Update the groups directly (dangerous).
			return affiliate_wp()->groups->update(
				$this->group_id,
				$args,
				$where,
				'group',
				true // Force the raw update.
			);
		}

		return affiliate_wp()->groups->update_group( $this->group_id, $args );
	}
}
