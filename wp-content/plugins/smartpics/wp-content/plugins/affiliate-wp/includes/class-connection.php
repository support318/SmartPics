<?php // phpcs:ignore WordPress.Files.FileName.InvalidClassFileName -- The name of the tile is common among others.
/**
 * Connection
 *
 * @package     AffiliateWP
 * @subpackage  Connections
 * @copyright   Copyright (c) 2020, Sandhills Development, LLC
 * @license     http://opensource.org/licenses/gpl-2.0.php GNU Public License
 * @since       2.12.0
 * @author      Aubrey Portwood <aubrey@awesomeomotive.com>
 */

// phpcs:disable PEAR.Functions.FunctionCallSignature.ContentAfterOpenBracket -- Ignore formatting for a better preference in this file.
// phpcs:disable PEAR.Functions.FunctionCallSignature.CloseBracketLine -- Ignore formatting for a better preference in this file.
// phpcs:disable PEAR.Functions.FunctionCallSignature.EmptyLine -- Empty lines are okay.
// phpcs:disable Generic.WhiteSpace.ScopeIndent.Incorrect — Empty lines are okay.
// phpcs:disable PEAR.Functions.FunctionCallSignature.FirstArgumentPosition — Empty lines are okay.

namespace AffiliateWP\Connections;

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

if ( class_exists( '\AffiliateWP\Connections\Connection' ) ) {
	return;
}

affwp_require_util_traits( 'data' );

#[\AllowDynamicProperties]

/**
 * Connection
 *
 * A connection is a relationship between two things with ID's in the database.
 *
 * @since 2.12.0
 */
final class Connection {

	use \AffiliateWP\Utils\Data;

	/**
	 * The value for `connection_id` in the database.
	 *
	 * @since 2.12.0
	 *
	 * @var int
	 */
	public $connection_id = 0;

	/**
	 * Information about what is connected in the database.
	 *
	 * E.g.
	 *
	 *     array(
	 *         connectable1 => id (int),
	 *         connectable2 => id (int),
	 *     )
	 *
	 * @since 2.12.0
	 *
	 * @var array
	 */
	private $connected = array();

	/**
	 * Construct.
	 *
	 * @since 2.12.0
	 *
	 * @param int $connection_id Positive numeric value of `connection_id` from
	 *                           the database.
	 *
	 * @throws \InvalidArgumentException If you supply a non-positive or non-numeric value for `$connection_id`.
	 * @throws \Exception                If you try to create an object for a connection that doesn't exist in the database.
	 */
	public function __construct( $connection_id ) {

		if ( ! $this->is_numeric_and_gt_zero( $connection_id ) ) {
			throw new \InvalidArgumentException( '$connection_id must be a postive number (greater than zero).' );
		}

		// We are not going to create an object for something that's not in the DB.
		if ( ! affiliate_wp()->connections->connection_exists( $connection_id ) ) {
			throw new \Exception( 'Cannot create object for connection that does not exist in the database.' );
		}

		$this->connection_id = intval( $connection_id );

		// Might be a WP_Error.
		$this->connected = affiliate_wp()->connections->get_connected_ids( $this->connection_id );
	}

	/**
	 * Delete from the database.
	 *
	 * Note, this does not disconnect, use self::update() for that.
	 *
	 * @since  2.12.0
	 *
	 * @return bool `true` if it was deleted, `false` if not.
	 */
	public function delete() {
		return affiliate_wp()->connections->delete_connection( $this->connection_id );
	}

	/**
	 * Does this object have any issues?
	 *
	 * @since 2.12.0
	 *
	 * @return bool
	 */
	public function has_errors() {
		return is_wp_error( $this->connected );
	}

	/**
	 * Get the things that are connected in this connection.
	 *
	 * E.g.
	 *
	 *     array(
	 *         connectable1 => id (int),
	 *         connectable2 => id (int),
	 *     )
	 *
	 * @since  2.12.0
	 *
	 * @return mixed `array` of the connection information.
	 *               `WP_Error` if there is an issue with the connection.
	 */
	public function get_connected() {
		return $this->connected;
	}

	/**
	 * Get the date this connection was created.
	 *
	 * @since  2.12.0
	 *
	 * @return mixed The string-value of the date in the databse, `WP_Error` otherwise.
	 */
	public function get_date() {
		return affiliate_wp()->connections->get_connection_date( $this->connection_id );
	}

	/**
	 * Get by instance.
	 *
	 * @since  2.12.0
	 *
	 * @param int $connection_id The group id in the database (connection_id).
	 *
	 * @return \AffiliateWP\Groups\Group
	 *
	 * @see Affiliate_WP_DB->get_core_object().
	 */
	public static function get_instance( $connection_id ) {
		return new \AffiliateWP\Connections\Connection( $connection_id );
	}

	/**
	 * Get the status of this connection.
	 *
	 * @since  2.12.0
	 *
	 * @return mixed The status from the database, `WP_Error` otherwise.
	 */
	public function get_status() {
		return affiliate_wp()->connections->get_connection_status( $this->connection_id );
	}

	/**
	 * Is this connection a live connection?
	 *
	 * @since  2.12.0
	 *
	 * @return mixed True if it is, false if it's decoupled or disconnected.
	 */
	public function is_live() {
		return affiliate_wp()->connections->connection_is_live( $this->connection_id );
	}

	/**
	 * Update the connection.
	 *
	 * @since  2.12.0
	 *
	 * @param array $args   See `\AffiliateWP\Connections\DB::update_connection()` on what you can pass here.
	 * @param array $raw    If you want to update the DB directly, set the to `true` and see
	 *                      \Affiliate_WP_DB::update for return types.
	 * @param mixed $where  If `$raw` is `true` used for `\Affiliate_WP_DB::update()` method.
	 *
	 * @return mixed `true` if data was updated in the database.
	 *               `false` if nothing was updated in the database.
	 *               `WP_Error` if something went wrong.
	 *
	 * @throws \InvalidArgumentException If you do not supply an array for `$args` or `$raw` is not set to `true` or `false`.
	 */
	public function update( $args, $raw = false, $where = '' ) {

		if ( ! is_array( $args ) ) {
			throw new \InvalidArgumentException( '$args must be an array.' );
		}

		if ( ! is_bool( $raw ) ) {
			throw new \InvalidArgumentException( '$raw must be true or false.' );
		}

		if ( true === $raw ) {

			// Do it the raw way (dangerougs).
			affiliate_wp()->connections->update(
				$this->connection_id,
				$args,
				$where,
				'connection',
				true // Force the update.
			);
		}

		return affiliate_wp()->connections->update_connection( $this->connection_id, $args );
	}
}
