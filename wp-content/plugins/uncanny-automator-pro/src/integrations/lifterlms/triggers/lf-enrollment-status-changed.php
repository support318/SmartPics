<?php

namespace Uncanny_Automator_Pro;

/**
 * Class LF_ENROLLMENT_STATUS_CHANGED
 *
 * @package Uncanny_Automator_Pro
 */
class LF_ENROLLMENT_STATUS_CHANGED extends \Uncanny_Automator\Recipe\Trigger {

	/**
	 * Set up the trigger.
	 *
	 * @return mixed|void
	 */
	protected function setup_trigger() {
		$this->set_integration( 'LF' );
		$this->set_trigger_code( 'LF_ENROLLMENT_STATUS_CHANGED' );
		$this->set_trigger_meta( 'LF_PRODUCT_TYPE' );
		$this->set_is_pro( true );
		$this->set_readable_sentence( esc_attr_x( "A user's enrollment of {{a type}} is changed to {{a status}}", 'LifterLMS', 'uncanny-automator-pro' ) );
		$this->set_sentence(
			sprintf(
				esc_attr_x(
					"A user's enrollment of {{a type:%1\$s}} is changed to {{a status:%2\$s}}",
					'LifterLMS',
					'uncanny-automator-pro'
				),
				$this->get_trigger_meta(),
				$this->get_trigger_meta() . '_STATUS'
			)
		);

		$this->add_action( 'automator_pro_llms_user_enrollment_update' );
		$this->set_action_args_count( 4 );

	}

	/**
	 * Load Options for the trigger.
	 *
	 * @return array
	 */
	public function load_options() {

		$types = array(
			'input_type'  => 'select',
			'option_code' => $this->get_trigger_meta(),
			'label'       => esc_attr_x( 'Type', 'LifterLMS', 'uncanny-automator-pro' ),
			'required'    => true,
			'options'     => array(),
			'ajax'        => array(
				'endpoint' => 'lifter_lms_retrieve_product_types',
				'event'    => 'on_load',
			),
		);

		$statuses = array(
			'input_type'  => 'select',
			'option_code' => $this->get_trigger_meta() . '_STATUS',
			'label'       => esc_attr_x( 'Enrollment status', 'LifterLMS', 'uncanny-automator-pro' ),
			'required'    => true,
			'options'     => array(),
			'ajax'        => array(
				'endpoint' => 'lifter_lms_retrieve_enrollment_statuses',
				'event'    => 'on_load',
			),
		);

		return array(
			'options' => array(
				$types,
				$statuses,
			),
		);
	}

	/**
	 * Validate the trigger.
	 *
	 * @param array $trigger
	 * @param array $hook_args
	 *
	 * @return bool
	 */
	public function validate( $trigger, $hook_args ) {

		// List the arguments passed to the hook.
		list( $user_id, $product_id, $post_type, $status ) = $hook_args;

		// If any params are empty bail.
		if ( empty( $user_id ) || empty( $product_id ) || empty( $post_type ) || empty( $status ) ) {
			return false;
		}

		// If the trigger product type and status are not set bail.
		if ( ! isset( $trigger['meta'][ $this->get_trigger_meta() ] ) || ! isset( $trigger['meta'][ $this->get_trigger_meta() . '_STATUS' ] ) ) {
			return false;
		}

		// Validate the post type of the product.
		if ( $post_type !== $trigger['meta'][ $this->get_trigger_meta() ] ) {
			return false;
		}

		// If the status is not set or the status does not match the selected status bail.
		if ( empty( $status ) || $status !== $trigger['meta'][ $this->get_trigger_meta() . '_STATUS' ] ) {
			return false;
		}

		// Validated - set the user ID.
		$this->set_user_id( $user_id );

		return true;
	}

	/**
	 * Define the tokens.
	 *
	 * @param  array $tokens
	 * @param  array $args
	 * @return array
	 */
	public function define_tokens( $trigger, $tokens ) {

		$tokens = ! is_array( $tokens ) ? array() : $tokens;

		$tokens[] = array(
			'tokenId'   => 'LF_ENROLLMENT_PRODUCT_ID',
			'tokenName' => esc_attr_x( 'Product ID', 'LifterLMS', 'uncanny-automator-pro' ),
			'tokenType' => 'int',
		);

		$tokens[] = array(
			'tokenId'   => 'LF_ENROLLMENT_PRODUCT_TITLE',
			'tokenName' => esc_attr_x( 'Product title', 'LifterLMS', 'uncanny-automator-pro' ),
			'tokenType' => '',
		);

		return $tokens;
	}

	/**
	 * Hydrate tokens.
	 *
	 * @param array $trigger
	 * @param array $hook_args
	 *
	 * @return array
	 */
	public function hydrate_tokens( $trigger, $hook_args ) {

		// List the arguments passed to the hook.
		list( $user_id, $product_id, $post_type, $status ) = $hook_args;

		$meta_key     = $this->get_trigger_meta();
		$trigger_meta = $trigger['meta'] ?? array();
		$token_values = array(
			$meta_key                     => $trigger_meta[ $meta_key . '_readable' ] ?? ucfirst( str_replace( 'llms_', '', $post_type ) ),
			$meta_key . '_STATUS'         => $trigger_meta[ $meta_key . '_STATUS_readable' ] ?? ucfirst( $status ),
			'LF_ENROLLMENT_PRODUCT_ID'    => $product_id,
			'LF_ENROLLMENT_PRODUCT_TITLE' => get_the_title( $product_id ),
		);

		return $token_values;
	}
}
