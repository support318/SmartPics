<?php

if ( ! defined( 'ABSPATH' ) ) {
	exit; // Exit if accessed directly
}

class WPF_Woo_Checkout_Field_Editor_Pro extends WPF_Integrations_Base {

	/**
	 * The slug for WP Fusion's module tracking.
	 *
	 * @since 3.45.2.1
	 * @var string $slug
	 */

	public $slug = 'woo-checkout-field-editor-pro';

	/**
	 * The plugin name for WP Fusion's module tracking.
	 *
	 * @since 3.45.2.1
	 * @var string $name
	 */
	public $name = 'Checkout Field Editor for WooCommerce';

	/**
	 * The link to the documentation on the WP Fusion website.
	 *
	 * @since 3.45.2.1
	 * @var string $docs_url
	 */
	public $docs_url = 'https://wpfusion.com/documentation/ecommerce/woocommerce/#syncing-customer-data-and-custom-fields';

	/**
	 * Gets things started
	 *
	 * @since 3.45.2.1
	 */
	public function init() {

		add_filter( 'wpf_meta_fields', array( $this, 'add_meta_fields' ) );
	}

	/**
	 * Add fields from Checkout Field Editor Pro to contact fields list
	 *
	 * @since 3.45.2.1
	 *
	 * @param array $meta_fields The meta fields
	 * @return  array Meta Fields
	 */
	public function add_meta_fields( $meta_fields ) {

		$additional_old_fields = get_option( 'thwcfe_sections', array() );
		$additional_new_fields = get_option( 'thwcfe_block_sections', array() );
		$additional_fields     = array_merge( $additional_old_fields, $additional_new_fields );

		if ( ! empty( $additional_fields ) ) {
			foreach ( $additional_fields as $section ) {
				if ( ! empty( $section->fields ) ) {
					foreach ( $section->fields as $field ) {
						if ( ! isset( $meta_fields[ $field->id ] ) ) {
							$meta_fields[ $field->id ] = array(
								'label' => $field->title,
								'type'  => $field->type,
								'group' => 'woocommerce',
							);
						}
					}
				}
			}
		}

		return $meta_fields;
	}
}

new WPF_Woo_Checkout_Field_Editor_Pro();
