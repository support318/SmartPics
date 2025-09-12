<?php // phpcs:ignore WordPress.Files.FileName.InvalidClassFileName -- The name of the tile is common among others.
/**
 * SQL Utilities
 *
 * @package     AffiliateWP
 * @subpackage  Utils
 * @copyright   Copyright (c) 2020, Sandhills Development, LLC
 * @license     http://opensource.org/licenses/gpl-2.0.php GNU Public License
 * @since       2.12.0
 * @author      Aubrey Portwood <aubrey@awesomeomotive.com>
 */

namespace AffiliateWP\Utils\DB;

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

if ( trait_exists( '\AffiliateWP\Utils\DB\SQL' ) ) {
	return;
}

affwp_require_util_traits( 'data' );

/**
 * SQL Utilities
 *
 * This includes helpful methods when formatting SQL for various
 * functions like LIMIT, OFFSET, etc.
 *
 * @since 2.12.0
 *
 * @see Affiliate_WP_DB
 */
trait SQL {

	use \AffiliateWP\Utils\Data;

	/**
	 * The date format we use for date in the database.
	 *
	 * @since 2.12.0
	 *
	 * @var string Used for `gmdate( $date_format, ... )`.
	 */
	protected $date_format = 'Y-m-d';

	/**
	 * Search SQL.
	 *
	 * @since 2.15.0
	 *
	 * @param string $search The search value.
	 *
	 * @return string SQL for search by title.
	 */
	protected function search_sql( string $search = '' ) : string {

		if ( ! $this->is_string_and_nonempty( $search ) ) {
			return ''; // You're asking for something improperly.
		}

		if ( empty( trim( $search ) ) ) {
			return ''; // You can't search for nothing.
		}

		$like = strtolower( trim( $search ) );

		global $wpdb;
		return $wpdb->prepare( 'AND title LIKE %s', "%{$like}%" );
	}

	/**
	 * Initial WHERE clause.
	 *
	 * @since 2.15.0
	 *
	 * @return string
	 */
	protected function where_sql() : string {
		return 'WHERE 1 = 1';
	}

	/**
	 * SQL for date.
	 *
	 * E.g.
	 *
	 * String:
	 *
	 *     '01-01-2023'
	 *
	 * Array:
	 *
	 *     array(
	 *         'start' => '01-01-2023',
	 *         'end'   => '01-15-2023',
	 *     )
	 *
	 * @since  2.12.0
	 *
	 * @param mixed  $date_arg A string with a valid `strtotome()` value
	 *                         or an `array` with a `start` and/or an `end`
	 *                         values set to valid `strtotime()` dates.
	 * @param string $column   The name of the column where you store a date.
	 *
	 * @return string SQL for date argument.
	 */
	protected function date_sql( $date_arg, $column = 'date' ) {

		if ( empty( $date_arg ) ) {
			return ''; // This would work for a string or an array.
		}

		if ( true !== $this->is_valid_date_arg( $date_arg ) ) {
			return ''; // Can't filter by date when date argument is invalid.
		}

		// Start and end must be set, let's use those.
		global $wpdb;

		if ( is_string( $date_arg ) ) {

			// Convert e.g. 8-01-2023 to a timestamp and into the format we use in the database (2023-08-01).
			return str_replace(
				'{column}',
				$wpdb->_real_escape( $column ),
				$wpdb->prepare(
					'WHERE `{column}` = %s',
					gmdate( $this->date_format, strtotime( $date_arg ) )
				)
			);
		}

		// date >= start AND <= end.
		if ( isset( $date_arg['start'] ) && isset( $date_arg['end'] ) ) {

			// E.g. WHERE `date` >= '2023-01-01' AND `date` <= '2023-01-30'.
			return str_replace(
				'{column}',
				$wpdb->_real_escape( $column ),
				$wpdb->prepare(
					'WHERE
						`{column}` >= %s
					AND
						`{column}` <= %s',
					gmdate( $this->date_format, strtotime( $date_arg['start'] ) ),
					gmdate( $this->date_format, strtotime( $date_arg['end'] ) )
				)
			);
		}

		// date >= start.
		if ( isset( $date_arg['start'] ) && ! isset( $date_arg['end'] ) ) {

			// E.g. WHERE `date` >= '2023-01-01'.
			return $wpdb->prepare(
				'
				WHERE
					`date` >= %s
				',
				gmdate( $this->date_format, strtotime( $date_arg['start'] ) )
			);
		}

		// date <= end.
		return $wpdb->prepare(
			'
			WHERE
				`date` <= %s
			',
			gmdate( $this->date_format, strtotime( $date_arg['end'] ) )
		);
	}

	/**
	 * LIMIT SQL.
	 *
	 * @since 2.12.0
	 *
	 * @param mixed $limit Numeric value for `LIMIT`.
	 *
	 * @return string Perpared LIMIT SQL.
	 */
	protected function limit_sql( $limit ) {

		// Must be positive, or -1.
		$limit = $this->get_positive_numeric_or_negative_one( $limit );

		global $wpdb;

		if ( $limit < 1 ) {

			return sprintf(
				'LIMIT %d',
				$wpdb->_real_escape( $this->virtually_unlimited() )
			);
		}

		return $wpdb->prepare( 'LIMIT %d', $limit );
	}

	/**
	 * OFFSET sql.
	 *
	 * @since 2.12.0
	 * @param  mixed $offset Numeric value for OFFSET.
	 * @return string        Perpared OFFSET sql.
	 */
	protected function offset_sql( $offset ) {

		if ( ! $this->is_numeric_and_at_least_zero( $offset ) ) {
			return ''; // Must be 0 or positive.
		}

		global $wpdb;
		return $wpdb->prepare( 'OFFSET %d', $offset );
	}

	/**
	 * ORDER BY SQL.
	 *
	 * @since 2.12.0
	 *
	 * @param  string $orderby `ORDER BY` value (a column in the database table), e.g.
	 *                         `group_id` which is the default.
	 * @param  string $order   The value for `ORDER`, must be `ASC` or `DESC` or will default to `ASC`.
	 *
	 * @return string Prepared ORDER SQL.
	 */
	protected function orderby_sql( $orderby, $order ) {

		$orderby = $this->is_string_and_nonempty( $orderby )
				? $orderby
				: '';

		if ( empty( $orderby ) ) {
			return ''; // ORDER BY and ORDER are unuseful if we're not ordering by anything.
		}

		// ORDER BY can only be set to one of our column values, not going to attempt if it's something else.
		if ( ! in_array( $orderby, array_keys( $this->get_columns() ), true ) ) {
			return '';
		}

		// We have ORDER BY let's set ORDER.
		$order = is_string( $order ) &&

			// Must be ASC or DESC, otherwise we will default to ASC.
			(
				'asc' === trim( strtolower( $order ) ) ||
				'desc' === trim( strtolower( $order ) )
			)
				? strtoupper( trim( $order ) )
				: 'ASC';

		global $wpdb;

		return sprintf(
			'ORDER BY %s %s',
			$wpdb->_real_escape( $orderby ),
			$wpdb->_real_escape( $order )
		);
	}
}
