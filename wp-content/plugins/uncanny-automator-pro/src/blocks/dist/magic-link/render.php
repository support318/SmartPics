<?php
/**
 * Render.php
 *
 * @see https://github.com/WordPress/gutenberg/blob/trunk/docs/reference-guides/block-api/block-metadata.md#render
 *
 * @package Uncanny_Automator_Pro
 *
 * @param array $attributes - The block attributes.
 * @param string $content - The block default content.
 * @param WP_Block $block - The block instance.
 *
 * @return string - The block HTML.
 */

namespace Uncanny_Automator_Pro\Blocks\Magic_Button;

// Exit if accessed directly.
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

// Return if attributes are not set.
if ( ! isset( $attributes ) || ! is_array( $attributes ) || empty( $attributes ) ) {
	return;
}

// Return if trigger ID is not set.
if ( empty( $attributes['id'] ) ) {
	return;
}

$allowed_attrs = array(
	'id',
	'is_ajax',
	'text',
	'success_message',
	'submit_message',
	'css_class',
);

// Render the shortcode
$shortcode = '[automator_link';
foreach ( $attributes as $key => $value ) {
	$key = 'label' === $key ? 'text' : $key;
	$key = 'className' === $key ? 'css_class' : $key;
	if ( ! in_array( $key, $allowed_attrs, true ) ) {
		continue;
	}
	$shortcode .= ' ' . $key . '="' . esc_attr( $value ) . '"';
}
$shortcode .= ']';
echo do_shortcode( $shortcode );
