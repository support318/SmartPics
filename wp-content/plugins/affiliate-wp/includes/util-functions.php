<?php
/**
 * Utility Functions
 *
 * @package     AffiliateWP
 * @subpackage  Functions/Utils
 * @copyright   Copyright (c) 2017, Sandhills Development, LLC
 * @license     http://opensource.org/licenses/gpl-2.0.php GNU Public License
 * @since       2.14.0
 */

/**
 * Get the includes/util directory.
 *
 * @since 2.14.0
 *
 * @param string $rel_path Included directory path to add.
 *
 * @return string Path to the includes/utils directory.
 */
function affwp_get_util_dir( string $rel_path ) : string {

	$plugin_dir = untrailingslashit( AFFILIATEWP_PLUGIN_DIR );

	return untrailingslashit( "{$plugin_dir}/includes/utils/{$rel_path}" );
}

/**
 * Require a utility trait (or traits).
 *
 * @since 2.14.0
 *
 * @return void Requires the trait(s).
 *
 * @throws \InvalidArgumentException If `$traits` is not a  `string` or `array`.
 */
function affwp_require_util_traits() : void {

	foreach ( func_get_args() as $trait ) {
		require_once affwp_get_util_dir( "traits/trait-{$trait}.php" );
	}
}
