<?php
/**
 * Admin Functions
 *
 * @since       2.13.0
 *
 * @package     AffiliateWP
 * @subpackage  AffiliateWP\Admin
 * @copyright   Copyright (c) 2014, Sandhills Development, LLC
 * @license     http://opensource.org/licenses/gpl-2.0.php GNU Public License
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

if ( ! is_admin() ) {
	return; // Don't load any of these for e.g. the frontend.
}

/**
 * Renders a custom tooltip icon for use with AffiliateWP.
 *
 * @since 2.23.2
 *
 * @param string $tooltip The tooltip text.
 * @param string $type The type of tooltip. Can be one of: 'normal', 'warning', or 'critical'.
 * @param bool   $echo If true, the tooltip icon will be echoed. If false, it will be returned as a string.
 * @param string $classes Additional CSS classes for the tooltip icon.
 * @return string If $echo is false, returns the HTML of the tooltip icon. Otherwise, returns an empty string.
 *
 * @throws \InvalidArgumentException If the $type is not one of the valid types ('normal', 'warning', or 'critical').
 */
function affiliatewp_tooltip(
	string $tooltip = '',
	string $type = 'normal',
	bool $echo = true,
	string $classes = ''
) {

	// See https://developer.wordpress.org/resource/dashicons/ for more icons.
	$dashicons_map = array(
		'critical' => 'dismiss',
		'normal'   => 'editor-help',
		'warning'  => 'warning',
		'setting'  => 'admin-settings',
		'global'   => 'admin-site',
		'unused'   => 'remove',
		'disabled' => 'remove',
		'hidden'   => 'hidden',
		'mdash'    => 'minus',
		'visible'  => 'visibility',
		'privacy'  => 'privacy',
		'locked'   => 'lock',
		'info'     => 'info',
		'status'   => 'format-status',
		'comment'  => 'admin-comments',
		'text'     => '',
	);

	if ( ! in_array( $type, array_keys( $dashicons_map ), true ) ) {
		throw new \InvalidArgumentException( '$type can only be one of: normal, warning, critical.' );
	}

	$id = wp_unique_id( 'affwp-tooltip-' );

	$classes = sprintf(
		'affwp-tooltip icon %s',
		trim( $classes )
	);

	$kses = affwp_kses();

	affiliate_wp()->scripts->enqueue( 'affiliatewp-tooltip' );

	wp_add_inline_script(
		'affiliatewp-tooltip',
		sprintf(
			"affiliatewp.tooltip.show( '%1\$s', `%2\$s`, %3\$s )",
			"#{$id}",
			wp_kses( $tooltip, $kses ),
			wp_json_encode(
				array(
					'interactive' => true,
					'allowHTML'   => true,
					'trigger'     => 'mouseenter focus',
					'theme'       => 'affwp',
					'delay'       => [0, 500],
					'placement'   => 'top',
				)
			)
		)
	);

	ob_start();

	?>

	<span
		class="<?php echo esc_attr( trim( $classes ) ); ?>"
		aria-describedby="tooltip-<?php echo esc_attr( $id ); ?>">

		<span
			id="<?php echo esc_attr( $id ); ?>"
			class="dashicon dashicons dashicons-<?php echo esc_attr( $dashicons_map[ $type ] ); ?>"
		></span>

	</span>

	<?php

	if ( $echo ) {

		// Echo instead.
		echo ob_get_clean();

		return '';
	}

	return trim( ob_get_clean() );
}

/**
 * Icon Tooltip
 *
 * @since 2.13.0
 *
 * @param string $tooltip The tooltip to show on icon hover.
 * @param string $type    The type of info icon: normal, critical, warning.
 * @param bool   $echo    Whether to output (true) or return (false).
 * @param string $class   Custom color for the icon.
 *
 * @return string
 *
 * @throws \InvalidArgumentException If you do not use one of the qualified types.
 */
function affwp_icon_tooltip(
	string $tooltip = '',
	string $type = 'normal',
	bool $echo = true,
	string $class = ''
) : string {

	// See https://developer.wordpress.org/resource/dashicons/ for more icons.
	$dashicons_map = array(
		'critical' => 'dismiss',
		'normal'   => 'editor-help',
		'warning'  => 'warning',
		'setting'  => 'admin-settings',
		'global'   => 'admin-site',
		'unused'   => 'remove',
		'disabled' => 'remove',
		'hidden'   => 'hidden',
		'mdash'    => 'minus',
		'visible'  => 'visibility',
		'hidden'   => 'hidden',
		'privacy'  => 'privacy',
		'locked'   => 'lock',
	);

	if ( ! in_array( $type, array_keys( $dashicons_map ), true ) ) {
		throw new \InvalidArgumentException( '$type can only be one of: normal, warning, critical.' );
	}

	$id = wp_unique_id( wp_rand() );

	ob_start();

	?>
			<span
				class="affwp-tooltip icon alpine <?php echo esc_attr( $type ); ?> tooltip-<?php echo esc_attr( $id ); ?> <?php echo esc_attr( $class ); ?>"
				aria-describedby="tooltip-<?php echo esc_attr( $id ); ?>"
				x-data='{ "tooltip": [] }'><!--

					--><span
							id="tooltip-<?php echo esc_attr( $id ); ?>"
							class="dashicon dashicons dashicons-<?php echo esc_attr( $dashicons_map[ $type ] ); ?>"
							x-on:mouseover="tooltip.showTooltip<?php echo esc_attr( $id ); ?> = true;"
							x-on:mouseover.away="tooltip.showTooltip<?php echo esc_attr( $id ); ?> = false;"><!--

								--><template x-if="tooltip.showTooltip<?php echo esc_attr( $id ); ?>"><span
										class="affwp-tooltip-content active"
										x-show="tooltip.showTooltip<?php echo esc_attr( $id ); ?>"><?php echo esc_html( $tooltip ); ?></span></template><!--
						--></span><!--
			--></span>

	<?php

	if ( $echo ) {

		// Echo instead.
		echo wp_kses(
			trim( ob_get_clean() ),
			affwp_get_tooltip_allowed_html()
		);

		return '';
	}

	return trim( ob_get_clean() );
}

/**
 * Text Tooltip
 *
 * @since 2.13.0
 *
 * @param string $text    The text to initiate the tooltip.
 * @param string $tooltip The tooltip contents.
 * @param bool   $echo    Set to true to echo.
 *
 * @return [type] [description]
 */
function affwp_text_tooltip( string $text, string $tooltip, bool $echo = true ) {

	$id = wp_unique_id( wp_rand() );

	ob_start();

	?>

	<span
		x-data='{ "tooltip": { "showTooltip<?php echo esc_attr( $id ); ?>": false } }'
		class="trigger affwp-tooltip text alpine"
		x-on:mouseover="tooltip.showTooltip<?php echo esc_attr( $id ); ?> = true;"
		x-on:mouseover.away="tooltip.showTooltip<?php echo esc_attr( $id ); ?> = false;"
		aria-describedby="tooltip-<?php echo esc_attr( $id ); ?>"><!--

			--><?php echo $text; // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped -- We escape later. ?><!--

		--><template x-if="tooltip.showTooltip<?php echo esc_attr( $id ); ?>"><span
				id="tooltip-<?php echo esc_attr( $id ); ?>"
				class="affwp-tooltip-content active text"
				x-show="tooltip.showTooltip<?php echo esc_attr( $id ); ?>"><!--

					--><?php echo $tooltip; // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped -- We escape later. ?><!--

			--></span></template><!--

	--></span>

	<?php

	$return = trim( ob_get_clean() );

	if ( $echo ) {

		echo wp_kses(
			trim( ob_get_clean() ),
			affwp_get_tooltip_allowed_html()
		);

		return $return;
	}

	return $return;
}

/**
 * Tooltip Allowed HTML.
 *
 * @since 2.13.0
 *
 * @return array
 */
function affwp_get_tooltip_allowed_html() : array {

	return array_merge(
		wp_kses_allowed_html( 'post' ),
		wp_kses_allowed_html( 'strip' ),
		wp_kses_allowed_html( 'data' ),
		wp_kses_allowed_html( 'entities' ),
		array(
			'span' => array(
				'x-data'              => true,
				'id'                  => true,
				'class'               => true,
				'x-transition'        => true,
				'x-show'              => true,
				'aria-describedby'    => true,
				'x-on:mouseover'      => true,
				'x-on:mouseover.away' => true,
				'style'               => true,
			),
		)
	);
}

/**
 * Load tooltip CSS/JS on our admin pages.
 *
 * @since 2.13.0
 *
 * @return void
 */
function affwp_alpine_all_admin_pages() : void {

	if ( ! affwp_is_admin_page() ) {
		return;
	}

	wp_enqueue_script( 'alpinejs' );
}
add_action( 'admin_enqueue_scripts', 'affwp_alpine_all_admin_pages' );
