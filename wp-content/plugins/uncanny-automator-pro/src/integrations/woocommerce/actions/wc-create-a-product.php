<?php

namespace Uncanny_Automator_Pro;

/**
 * Class WC_CREATE_A_PRODUCT
 * @package Uncanny_Automator_Pro
 */
class WC_CREATE_A_PRODUCT extends \Uncanny_Automator\Recipe\Action {

	/**
	 * options
	 *
	 * Override this method to display a default option group
	 *
	 * @return array
	 */
	public function options() {
		$product_categories_options = Automator()->helpers->recipe->woocommerce->pro->all_wc_product_categories();
		$product_categories         = array();
		$product_tags_options       = Automator()->helpers->recipe->woocommerce->pro->all_wc_product_tags();
		$product_tags               = array();
		foreach ( $product_categories_options['options'] as $value => $option ) {
			$product_categories[] = array(
				'value' => $value,
				'text'  => $option,
			);
		}

		foreach ( $product_tags_options['options'] as $value => $option ) {
			$product_tags[] = array(
				'value' => $value,
				'text'  => $option,
			);
		}

		return array(
			Automator()->helpers->recipe->field->text(
				array(
					'option_code'           => 'WC_PRODUCT_TITLE',
					'label'                 => esc_attr_x( 'Product title', 'WooCommerce', 'uncanny-automator-pro' ),
					'required'              => false,
					'exclude_default_token' => true,
				)
			),
			Automator()->helpers->recipe->field->text(
				array(
					'option_code'           => 'WC_PRODUCT_DESCRIPTION',
					'label'                 => esc_attr_x( 'Product description', 'WooCommerce', 'uncanny-automator-pro' ),
					'required'              => false,
					'exclude_default_token' => true,
					'input_type'            => 'textarea',
				)
			),
			Automator()->helpers->recipe->field->text(
				array(
					'option_code'           => 'WC_PRODUCT_SHORT_DESCRIPTION',
					'label'                 => esc_attr_x( 'Product short description', 'WooCommerce', 'uncanny-automator-pro' ),
					'required'              => false,
					'exclude_default_token' => true,
					'input_type'            => 'textarea',
				)
			),
			array(
				'input_type'               => 'select',
				'option_code'              => 'WC_PRODUCT_CATEGORIES',
				'label'                    => esc_attr_x( 'Product categories', 'WooCommerce', 'uncanny-automator-pro' ),
				'required'                 => false,
				'supports_custom_value'    => false,
				'exclude_default_token'    => true,
				'supports_multiple_values' => true,
				'options'                  => $product_categories,
			),
			array(
				'input_type'               => 'select',
				'option_code'              => 'WC_PRODUCT_TAGS',
				'label'                    => esc_attr_x( 'Product tags', 'WooCommerce', 'uncanny-automator-pro' ),
				'supports_custom_value'    => false,
				'required'                 => false,
				'exclude_default_token'    => true,
				'supports_multiple_values' => true,
				'options'                  => $product_tags,
			),
			Automator()->helpers->recipe->field->select(
				array(
					'option_code'           => 'WC_PRODUCT_STATUS',
					'label'                 => esc_attr_x( 'Status', 'WooCommerce', 'uncanny-automator-pro' ),
					'exclude_default_token' => true,
					'options'               => array(
						array(
							'value' => 'draft',
							'text'  => 'Draft',
						),
						array(
							'value' => 'published',
							'text'  => 'Publish',
						),
					),
				)
			),
			Automator()->helpers->recipe->field->text(
				array(
					'option_code'           => 'WC_PRODUCT_IMAGE',
					'exclude_default_token' => true,
					'label'                 => esc_attr__( 'Product image URL', 'uncanny-automator-pro' ),
					'placeholder'           => esc_attr__( 'https://examplewebsite.com/path/to/image.jpg', 'uncanny-automator' ),
					'input_type'            => 'url',
					'required'              => false,
					'description'           => esc_attr__( 'The URL must include a supported image file extension (e.g. .jpg, .png, .svg, etc.). Some sites may block remote image download.', 'uncanny-automator' ),
				)
			),
			array(
				'option_code'           => 'WC_PRODUCT_CATALOG_VISIBILITY',
				'input_type'            => 'select',
				'label'                 => esc_attr_x( 'Catalog visibility', 'WooCommerce', 'uncanny-automator-pro' ),
				'description'           => esc_attr_x( 'This setting determines which shop pages products will be listed on.', 'WooCommerce', 'uncanny-automator-pro' ),
				'options'               => array(
					array(
						'value' => 'visible',
						'text'  => 'Shop and search results',
					),
					array(
						'value' => 'catalog',
						'text'  => 'Shop only',
					),
					array(
						'value' => 'search',
						'text'  => 'Search results only',
					),
					array(
						'value' => 'hidden',
						'text'  => 'Hidden',
					),
				),
				'default_value'         => 'visible',
				'exclude_default_token' => true,
			),
			array(
				'option_code'           => 'IS_FEATURED_PRODUCT',
				'label'                 => esc_attr__( 'This is a featured product', 'uncanny-automator' ),
				'input_type'            => 'checkbox',
				'is_toggle'             => true,
				'required'              => false,
				'default_value'         => false,
				'exclude_default_token' => true,
			),
			Automator()->helpers->recipe->field->float(
				array(
					'option_code'           => 'WC_PRODUCT_PRICE',
					'label'                 => esc_attr_x( 'Price', 'WooCommerce', 'uncanny-automator-pro' ),
					'exclude_default_token' => true,
				)
			),
			Automator()->helpers->recipe->field->float(
				array(
					'option_code'           => 'WC_PRODUCT_SALE_PRICE',
					'label'                 => esc_attr_x( 'Sale price', 'WooCommerce', 'uncanny-automator-pro' ),
					'exclude_default_token' => true,
				)
			),
			Automator()->helpers->recipe->field->text(
				array(
					'option_code'           => 'WC_PRODUCT_SKU',
					'label'                 => esc_attr_x( 'SKU', 'WooCommerce', 'uncanny-automator-pro' ),
					'exclude_default_token' => true,
				)
			),
			array(
				'option_code'           => 'WC_PRODUCT_TYPES',
				'input_type'            => 'select',
				'label'                 => esc_attr_x( 'Product type', 'WooCommerce', 'uncanny-automator-pro' ),
				'options'               => Automator()->helpers->recipe->woocommerce->pro->get_all_product_types(),
				'exclude_default_token' => true,
			),
			array(
				'option_code'           => 'WC_PRODUCT_SHIPPING_CLASSES',
				'input_type'            => 'select',
				'label'                 => esc_attr_x( 'Shipping class', 'WooCommerce', 'uncanny-automator-pro' ),
				'options'               => Automator()->helpers->recipe->woocommerce->pro->get_all_product_tax_class_options(),
				'exclude_default_token' => true,
				'default_value'         => '-1',
			),
			array(
				'option_code' => 'WC_PRODUCT_WEIGHT',
				'input_type'  => 'float',
				'label'       => esc_attr_x( 'Weight (kg)', 'Woocommerce', 'uncanny-automator-pro' ),
			),
			array(
				'option_code' => 'WC_PRODUCT_DIMENSIONS_LENGTH',
				'input_type'  => 'float',
				'label'       => esc_attr_x( 'Dimensions length (cm)', 'Woocommerce', 'uncanny-automator-pro' ),
			),
			array(
				'option_code' => 'WC_PRODUCT_DIMENSIONS_WIDTH',
				'input_type'  => 'float',
				'label'       => esc_attr_x( 'Dimensions width (cm)', 'Woocommerce', 'uncanny-automator-pro' ),
			),
			array(
				'option_code' => 'WC_PRODUCT_DIMENSIONS_HEIGHT',
				'input_type'  => 'float',
				'label'       => esc_attr_x( 'Dimensions height (cm)', 'Woocommerce', 'uncanny-automator-pro' ),
			),
			array(
				'option_code'           => 'WC_MANAGE_STOCK',
				'label'                 => esc_attr__( 'Track stock quantity for this product', 'uncanny-automator' ),
				'input_type'            => 'checkbox',
				'is_toggle'             => true,
				'required'              => false,
				'default_value'         => false,
				'exclude_default_token' => true,
			),
			array(
				'option_code'           => 'WC_PRODUCT_STOCK_STATUS',
				'input_type'            => 'select',
				'label'                 => esc_attr_x( 'Stock status', 'WooCommerce', 'uncanny-automator-pro' ),
				'options'               => Automator()->helpers->recipe->woocommerce->pro->get_all_stock_options(),
				'exclude_default_token' => true,
			),
		);
	}

	/**
	 * @param $user_id
	 * @param $action_data
	 * @param $recipe_id
	 * @param $args
	 * @param $parsed
	 *
	 * @return false
	 */
	public function process_action( $user_id, $action_data, $recipe_id, $args, $parsed ) {
		$product_post                 = array( 'post_type' => 'product' );
		$product_post['post_title']   = sanitize_text_field( $parsed['WC_PRODUCT_TITLE'] );
		$product_post['post_name']    = sanitize_title( sanitize_text_field( $parsed['WC_PRODUCT_TITLE'] ) );
		$product_post['post_status']  = sanitize_text_field( $parsed['WC_PRODUCT_STATUS'] );
		$product_post['post_content'] = sanitize_text_field( $parsed['WC_PRODUCT_DESCRIPTION'] );
		$product_post['post_excerpt'] = sanitize_text_field( $parsed['WC_PRODUCT_SHORT_DESCRIPTION'] );
		$product_id                   = wp_insert_post( $product_post, true, false );

		if ( is_wp_error( $product_id ) ) {
			$this->add_log_error( sprintf( esc_attr_x( '%s', 'WooCommerce', 'uncanny-automator-pro' ), $product_id->get_error_message() ) );

			return false;
		}

		$product_categories = json_decode( $parsed['WC_PRODUCT_CATEGORIES'] );
		$product_categories = ! empty( $product_categories ) ? array_filter( array_map( 'intval', $product_categories ) ) : '';

		$product_tags = json_decode( $parsed['WC_PRODUCT_TAGS'] );
		$product_tags = ! empty( $product_tags ) ? array_filter( array_map( 'intval', $product_tags ) ) : '';
		// Set product type
		wp_set_object_terms( $product_id, $parsed['WC_PRODUCT_TYPES'], 'product_type' );
		// Set categories
		wp_set_object_terms( $product_id, $product_categories, 'product_cat' );
		// Set tags
		wp_set_object_terms( $product_id, $product_tags, 'product_tag' );
		// Set image
		$wc_product_image = sanitize_text_field( $parsed['WC_PRODUCT_IMAGE'] );
		$image_url        = filter_var( $wc_product_image, FILTER_SANITIZE_URL );
		$this->add_product_image( $image_url, $product_id );

		$product_meta_data                   = array();
		$wc_manage_stock                     = sanitize_text_field( $parsed['WC_MANAGE_STOCK'] );
		$wc_is_featured                      = sanitize_text_field( $parsed['IS_FEATURED_PRODUCT'] );
		$product_meta_data['_featured']      = 'yes' === $wc_is_featured;
		$product_meta_data['_visibility']    = sanitize_text_field( $parsed['WC_PRODUCT_CATALOG_VISIBILITY'] );
		$product_meta_data['_sku']           = sanitize_text_field( $parsed['WC_PRODUCT_SKU'] );
		$product_meta_data['_regular_price'] = sanitize_text_field( $parsed['WC_PRODUCT_PRICE'] );
		$product_meta_data['_sale_price']    = sanitize_text_field( $parsed['WC_PRODUCT_SALE_PRICE'] );
		$product_meta_data['_manage_stock']  = 'yes' === $wc_manage_stock;
		$product_meta_data['_stock_status']  = sanitize_text_field( $parsed['WC_PRODUCT_STOCK_STATUS'] );
		$product_meta_data['_weight']        = sanitize_text_field( $parsed['WC_PRODUCT_WEIGHT'] );
		$product_meta_data['_length']        = sanitize_text_field( $parsed['WC_PRODUCT_DIMENSIONS_LENGTH'] );
		$product_meta_data['_width']         = sanitize_text_field( $parsed['WC_PRODUCT_DIMENSIONS_WIDTH'] );
		$product_meta_data['_height']        = sanitize_text_field( $parsed['WC_PRODUCT_DIMENSIONS_HEIGHT'] );
		$product_meta_data['_tax_class']     = sanitize_text_field( $parsed['WC_PRODUCT_SHIPPING_CLASSES'] );

		foreach ( $product_meta_data as $meta_key => $meta_value ) {
			update_post_meta( $product_id, $meta_key, $meta_value );
		}

		update_post_meta( $product_id, '_price', $product_meta_data['_regular_price'] );
		if ( $product_meta_data['_sale_price'] ) {
			update_post_meta( $product_id, '_price', $product_meta_data['_sale_price'] );
		}

		return true;
	}

	/**
	 * Adds a featured image using the image URL and post ID.
	 *
	 * @param $image_url
	 *
	 * @return int|string|\WP_Error
	 */
	public function add_product_image( $image_url, $post_id ) {

		require_once ABSPATH . 'wp-admin/includes/media.php';
		require_once ABSPATH . 'wp-admin/includes/file.php';
		require_once ABSPATH . 'wp-admin/includes/image.php';

		// Prevents double image downloading.
		$existing_media_id = absint( attachment_url_to_postid( $image_url ) );

		// If existing_media_id is not equals 0, it means media already exists.
		if ( 0 !== $existing_media_id ) {
			// Overwrite the image url with the existing media.
			$image_url = $existing_media_id;
		}

		// Supports numeric input.
		if ( is_numeric( $image_url ) ) {
			// The $image_url is numeric.
			return set_post_thumbnail( $post_id, $image_url );
		}

		// Otherwise, download and store the image as attachment.
		$attachment_id = media_sideload_image( $image_url, $post_id, null, 'id' );

		// Assign the downloaded attachment ID to the post.
		set_post_thumbnail( $post_id, $attachment_id );

		if ( is_wp_error( $attachment_id ) ) {
			automator_log( $attachment_id->get_error_message(), self::class . '->add_product_image error', true, 'wp-createpost' );
		}

		return $attachment_id;

	}

	/**
	 * @return mixed
	 */
	protected function setup_action() {
		$this->set_integration( 'WC' );
		$this->set_action_code( 'WC_CREATE_PRODUCT' );
		$this->set_action_meta( 'WC_PRODUCT' );
		$this->set_is_pro( true );
		$this->set_requires_user( false );
		$this->set_sentence( sprintf( esc_attr_x( 'Create {{a product:%1$s}}', 'WooCommerce', 'uncanny-automator-pro' ), $this->get_action_meta() ) );
		$this->set_readable_sentence( esc_attr_x( 'Create {{a product}}', 'WooCommerce', 'uncanny-automator-pro' ) );
	}
}
