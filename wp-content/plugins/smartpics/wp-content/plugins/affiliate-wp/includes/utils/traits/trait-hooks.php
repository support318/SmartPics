<?php // phpcs:ignore WordPress.Files.FileName.InvalidClassFileName -- The name of the tile is common among others.
/**
 * Hook Utilities
 *
 * @package     AffiliateWP
 * @subpackage  Data
 * @copyright   Copyright (c) 2020, Sandhills Development, LLC
 * @license     http://opensource.org/licenses/gpl-2.0.php GNU Public License
 * @since       2.12.0
 * @author      Aubrey Portwood <aubrey@awesomeomotive.com>
 */

namespace AffiliateWP\Utils;

require_once __DIR__ . '/trait-data.php';

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

if ( trait_exists( '\AffiliateWP\Utils\Hooks' ) ) {
	return;
}

/**
 * Hook Utilities
 *
 * @since 2.12.0
 *
 * @see Affiliate_WP_Data
 */
trait Hooks {

	use \AffiliateWP\Utils\Data;

	/**
	 * Filter a priority.
	 *
	 * @since 2.15.0
	 *
	 * @param int $priority The priority.
	 *
	 * @return int
	 */
	protected function filter_priority( int $priority ) : int {

		/**
		 * Filter a priority.
		 *
		 * @since 2.15.0
		 *
		 * @param int    $priority The priority.
		 * @param object $context  `$this`.
		 */
		return apply_filters( 'affwp_filter_priority', $priority, $this );
	}

	/**
	 * Filter a hook name.
	 *
	 * Mainly so we can change them to other names.
	 *
	 * @since 2.12.0
	 * @since 2.15.0 Return typehint for `string` added.
	 *
	 * @param  string $hook_name The filter.
	 *
	 * @return string
	 *
	 * @see AffiliateWP\Admin\Creatives\Categories\Connect for examples of how we use this.
	 *
	 * @throws \InvalidArgumentException If `$hook_name` is not a valid non-empty string.
	 */
	protected function filter_hook_name( $hook_name ) : string {

		if ( ! $this->is_string_and_nonempty( $hook_name ) ) {
			throw new \InvalidArgumentException( '$hook_name must be a non-empty string.' );
		}

		/**
		 * Filter a filter name.
		 *
		 * You can use this filter to change a filter name we use as long as that
		 * filter name is passed through this method. .e.g.
		 *
		 *     apply_filters(
		 *         $this->filter_hooks_name( 'my_great_filter' ),
		 *         $my_variable
		 *     );
		 *
		 * You can change `my_great_filter` to another filter using, e.g.:
		 *
		 *     add_filter(
		 *         'affwp_filter_hook_name_my_great_filter',
		 *         function( $hook_name ) {
		 *             return 'my_better_hook_filter';
		 *         }
		 *     );
		 *
		 * If you need context for conditional changes, will pass the instance to you
		 * for that so you can, e.g.:
		 *
		 *     add_filter(
		 *         'affwp_filter_hook_name_my_great_filter',
		 *         function( $hook_name, $context ) {
		 *
		 *             if ( $context->something ) {
		 *                 return $hook_name;
		 *             }
		 *
		 *             return 'my_better_hook_filter';
		 *         },
		 *         10,
		 *         2
		 *     );
		 *
		 * @since 2.12.0
		 *
		 * @param string $filter  The filter name.
		 * @param object $context `$this`.
		 */
		return apply_filters( "affwp_filter_hook_name_{$hook_name}", $hook_name, $this );
	}
}
