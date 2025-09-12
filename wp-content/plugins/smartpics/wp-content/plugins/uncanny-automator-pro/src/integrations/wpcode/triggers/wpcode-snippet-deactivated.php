<?php

namespace Uncanny_Automator_Pro;

use Uncanny_Automator\Recipe\Trigger;
use Uncanny_Automator\Wpcode_Helpers;
use WPCode_Snippet;

/**
 * Class WPCODE_SNIPPET_DEACTIVATED
 *
 * @pacakge Uncanny_Automator_Pro
 */
class WPCODE_SNIPPET_DEACTIVATED extends Trigger {

	/**
	 * @return mixed|void
	 */
	protected function setup_trigger() {
		$this->set_integration( 'WPCODE_IHAF' );
		$this->set_trigger_code( 'WPCODE_SNIPPET_DEACTIVATED' );
		$this->set_trigger_meta( 'WPCODE_SNIPPETS' );
		$this->set_is_pro( true );
		$this->set_trigger_type( 'anonymous' );
		$this->set_sentence( sprintf( esc_attr_x( '{{A snippet:%1$s}} is deactivated', 'WPCode', 'uncanny-automator-pro' ), $this->get_trigger_meta() ) );
		$this->set_readable_sentence( esc_attr_x( '{{A snippet}} is deactivated', 'WPCode', 'uncanny-automator-pro' ) );
		$this->add_action( 'post_updated', 10, 3 );
		$this->set_helper( new Wpcode_Helpers() );
	}

	/**
	 * @return array
	 */
	public function options() {
		$snippet_options = $this->get_helper()->get_wpcode_snippets();
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
		list( $post_id, $post_after, $post_before ) = $hook_args;

		if ( 'wpcode' !== $post_before->post_type ) {
			return false;
		}

		if ( $post_after->post_status === $post_before->post_status ) {
			return false;
		}

		if ( 'draft' !== $post_after->post_status ) {
			return false;
		}

		if ( ! isset( $trigger['meta'][ $this->get_trigger_meta() ] ) ) {
			return false;
		}

		$selected_snippet_id = $trigger['meta'][ $this->get_trigger_meta() ];

		return ( intval( '-1' ) === intval( $selected_snippet_id ) || ( absint( $selected_snippet_id ) === absint( $post_id ) ) );
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
		list( $post_id, $post_after, $post_before ) = $hook_args;

		$snippet = new WPCode_Snippet( absint( $post_id ) );

		return array(
			'SNIPPET_ID'            => $post_after->ID,
			'SNIPPET_TITLE'         => $post_after->post_title,
			'SNIPPET_CODE_TYPE'     => $snippet->get_code_type(),
			'SNIPPET_CODE'          => $snippet->get_code(),
			'SNIPPET_INSERT_METHOD' => ( $snippet->get_auto_insert() ) ? 'Auto Insert' : 'Shortcode',
			'SNIPPET_LOCATION'      => wpcode()->auto_insert->get_location_label( $snippet->get_location() ),
			'SNIPPET_PRIORITY'      => $snippet->get_priority(),
			'SNIPPET_TAGS'          => join( ', ', $snippet->get_tags() ),
		);
	}
}
