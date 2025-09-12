<?php
/**
 * Registration: Form Container
 *
 * @package     AffiliateWP
 * @subpackage  Core/Registration
 * @copyright   Copyright (c) 2021, Sandhills Development, LLC
 * @license     http://opensource.org/licenses/gpl-2.0.php GNU Public License
 * @since       2.8
 */

namespace AffWP\Core\Registration;

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

#[\AllowDynamicProperties]

/**
 * Registration form handler.
 *
 * @since 2.8
 */
class Form_Container {

	/**
	 * Form fields.
	 *
	 * @since 2.8
	 *
	 * @var Form_Field_Container[] Array of form fields
	 */
	protected $fields = array();

	/**
	 * Original block attributes for this form.
	 *
	 * @since 2.11.0
	 *
	 * @var array
	 */
	protected $block_attrs = array();

	/**
	 * Block Name.
	 *
	 * @since 2.18.0
	 *
	 * @var string
	 */
	private string $block_name = '';

	/**
	 * Sets up the form container.
	 *
	 * @since 2.8
	 *
	 * @param array $args {
	 *     Array of arguments to construct this form.
	 *
	 *     @type array $fields Form fields.
	 * }
	 */
	public function __construct( $args ) {

		$args = wp_parse_args(
			$args,
			array(
				'fields'      => array(),
				'block_attrs' => array(),
				'block_name'  => '',
			)
		);

		// Set the type of form.
		$this->block_name = is_string( $args['block_name'] )
			? $args['block_name']
			: $this->block_name;

		// Strip invalid form fields.
		foreach ( $args['fields'] as $field ) {
			if ( $field instanceof Form_Field_Container ) {
				$this->fields[] = $field;
			} else {
				affiliate_wp()->utils->log( 'invalid_form_field', 'A registration field was not added because it was invalid' );
			}
		}

		$this->block_attrs = $args['block_attrs'];
	}

	/**
	 * Get the block name associated with the form.
	 *
	 * Most forms are associated with a block, here we can
	 * store what block is associated with the form.
	 *
	 * @since 2.18.0
	 *
	 * @return string The block name assigned to the form.
	 */
	public function get_block_name() : string {
		return $this->block_name;
	}

	/**
	 * Legacy function to retrieve the hash for this form.
	 *
	 * Hash method based on all form fields, left here due compatibility
	 * reasons and must not be as a method for hashing after version 2.10.
	 * This method is unstable because it is not based on the original
	 * field block attributes, which can be affected by translations, leading
	 * to hashes without a compatible form.
	 *
	 * @since 2.8
	 *
	 * @return string the hash
	 */
	public function get_hash() {

		// Convert objects to arrays. This ensures they get normalized.
		$fields = array();

		if ( is_array( $this->fields ) ) {
			foreach ( $this->fields as $field ) {
				$fields[] = (array) $field;
			}
		}

		return affwp_get_hash( $fields );
	}

	/**
	 * Get all form attributes, including fields.
	 *
	 * Retrieves all attributes used to build this form,
	 * based on block attributes.
	 *
	 * @since 2.11.0
	 * @return array All form attributes including fields
	 */
	public function get_form_attrs() {

		return array_merge(
			// Form attributes.
			empty( $this->block_attrs )
				// No form attributes.
				? array()
				// This form's block attributes.
				: array( $this->block_attrs ),
			// Form fields attributes.
			is_array( $this->fields )
				// Block attributes of all fields.
				? wp_list_pluck( $this->fields, 'block_attrs' )
				// No fields.
				: array()
		);
	}

	/**
	 * Function to retrieve the crc for this form.
	 *
	 * Hash method for form integrity check based on the field block attributes.
	 *
	 * @since 2.11.0
	 * @return int the checksum
	 */
	public function get_checksum() {
		return crc32( wp_json_encode( $this->get_form_attrs() ) );
	}

	/**
	 * Get magic method. Makes all private and protected fields accessible without making it possible to modify their
	 * values.
	 *
	 * @param string $key the key to retrieve.
	 *
	 * @return mixed the value
	 */
	public function __get( $key ) {
		return $this->$key;
	}
}
