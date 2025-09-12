<?php

namespace Uncanny_Automator_Pro;

use Uncanny_Automator\Recipe\Trigger;
use Uncanny_Automator\Wpcode_Helpers;

/**
 * Class WPCODE_SNIPPET_THROWS_ERROR
 *
 * @pacakge Uncanny_Automator_Pro
 */
class WPCODE_SNIPPET_THROWS_ERROR extends Trigger {

	/**
	 * @return mixed|void
	 */
	protected function setup_trigger() {
		$this->set_integration( 'WPCODE_IHAF' );
		$this->set_trigger_code( 'WPCODE_SNIPPET_THROWS_ERROR' );
		$this->set_trigger_meta( 'WPCODE_SNIPPETS' );
		$this->set_is_pro( true );
		$this->set_trigger_type( 'anonymous' );
		$this->set_sentence( sprintf( esc_attr_x( '{{A snippet:%1$s}} throws an error', 'WPCode', 'uncanny-automator-pro' ), $this->get_trigger_meta() ) );
		$this->set_readable_sentence( esc_attr_x( '{{A snippet}} throws an error', 'WPCode', 'uncanny-automator-pro' ) );
		$this->add_action( 'wpcode_snippet_error_tracked', 10, 2 );
		$this->set_helper( new Wpcode_Helpers() );
	}

	/**
	 * @return array
	 */
	public function options() {
		$snippet_options = $this->get_helper()->get_wpcode_snippets( array( 'is_any' => true ) );
		$options         = array();
		foreach ( $snippet_options['options'] as $k => $option ) {
			$options[] = array(
				'text'  => $option,
				'value' => $k,
			);
		}

		return array(
			array(
				'input_type'      => 'select',
				'option_code'     => $this->get_trigger_meta(),
				'label'           => _x( 'Snippet', 'WPCode', 'uncanny-automator-pro' ),
				'required'        => true,
				'options'         => $options,
				'relevant_tokens' => array(),
			),
		);
	}

	/**
	 * @return bool
	 */
	public function validate( $trigger, $hook_args ) {
		list( $error, $snippet_obj ) = $hook_args;

		if ( ! isset( $trigger['meta'][ $this->get_trigger_meta() ] ) ) {
			return false;
		}

		$selected_snippet_id = $trigger['meta'][ $this->get_trigger_meta() ];
		$snippet_id          = $snippet_obj->id;

		return ( intval( '-1' ) === intval( $selected_snippet_id ) || ( absint( $selected_snippet_id ) === absint( $snippet_id ) ) );
	}

	/**
	 * define_tokens
	 *
	 * @param mixed $tokens
	 * @param mixed $trigger - options selected in the current recipe/trigger
	 *
	 * @return array
	 */
	public function define_tokens( $trigger, $tokens ) {
		$trigger_tokens = array(
			array(
				'tokenId'   => 'SNIPPET_ID',
				'tokenName' => __( 'Snippet ID', 'uncanny_automator-pro' ),
				'tokenType' => 'int',
			),
			array(
				'tokenId'   => 'SNIPPET_TITLE',
				'tokenName' => __( 'Snippet title', 'uncanny-automator-pro' ),
				'tokenType' => 'text',
			),
			array(
				'tokenId'   => 'SNIPPET_CODE_TYPE',
				'tokenName' => __( 'Code type', 'uncanny-automator-pro' ),
				'tokenType' => 'text',
			),
			array(
				'tokenId'   => 'SNIPPET_CODE',
				'tokenName' => __( 'Code', 'uncanny-automator-pro' ),
				'tokenType' => 'text',
			),
			array(
				'tokenId'   => 'SNIPPET_INSERT_METHOD',
				'tokenName' => __( 'Insert Method', 'uncanny-automator-pro' ),
				'tokenType' => 'text',
			),
			array(
				'tokenId'   => 'SNIPPET_LOCATION',
				'tokenName' => __( 'Location', 'uncanny-automator-pro' ),
				'tokenType' => 'text',
			),
			array(
				'tokenId'   => 'SNIPPET_TAGS',
				'tokenName' => __( 'Tags', 'uncanny-automator-pro' ),
				'tokenType' => 'text',
			),
			array(
				'tokenId'   => 'SNIPPET_PRIORITY',
				'tokenName' => __( 'Priority', 'uncanny-automator-pro' ),
				'tokenType' => 'int',
			),
			array(
				'tokenId'   => 'SNIPPET_ERROR',
				'tokenName' => __( 'Error message', 'uncanny-automator-pro' ),
				'tokenType' => 'text',
			),
		);

		return array_merge( $tokens, $trigger_tokens );
	}

	/**
	 * hydrate_tokens
	 *
	 * @param $trigger
	 * @param $hook_args
	 *
	 * @return array
	 */
	public function hydrate_tokens( $trigger, $hook_args ) {
		list( $error, $snippet_obj ) = $hook_args;

		return array(
			'SNIPPET_ID'            => $snippet_obj->id,
			'SNIPPET_TITLE'         => $snippet_obj->post_data->post_title,
			'SNIPPET_CODE_TYPE'     => $snippet_obj->get_code_type(),
			'SNIPPET_CODE'          => $snippet_obj->get_code(),
			'SNIPPET_INSERT_METHOD' => ( $snippet_obj->get_auto_insert() ) ? 'Auto Insert' : 'Shortcode',
			'SNIPPET_LOCATION'      => wpcode()->auto_insert->get_location_label( $snippet_obj->get_location() ),
			'SNIPPET_PRIORITY'      => $snippet_obj->get_priority(),
			'SNIPPET_TAGS'          => join( ', ', $snippet_obj->get_tags() ),
			'SNIPPET_ERROR'         => $error['message'],
		);
	}
}
