<?php

namespace Uncanny_Automator_Pro;

/**
 * Class WPCODE_RUN_ON_DEMAND_SNIPPET
 * @package Uncanny_Automator_Pro
 */
class WPCODE_RUN_ON_DEMAND_SNIPPET extends \Uncanny_Automator\Recipe\Action {

	/**
	 * @return mixed
	 */
	protected function setup_action() {
		if ( defined( 'WPCODE_VERSION' ) && version_compare( WPCODE_VERSION, '2.1.12', '<' ) ) {
			return;
		}
		$this->set_integration( 'WPCODE_IHAF' );
		$this->set_action_code( 'IHAF_RUN_ON_DEMAND_SNIPPET' );
		$this->set_action_meta( 'IHAP_ON_DEMAND' );
		$this->set_requires_user( false );
		$this->set_is_pro( true );
		$this->set_sentence(
			// translators: %s is a WPCode snippet ID
			sprintf( esc_attr_x( 'Run {{an on-demand code snippet:%1$s}}', 'WPCode', 'uncanny-automator-pro' ), $this->get_action_meta() )
		);
		$this->set_readable_sentence( esc_attr_x( 'Run {{an on-demand code snippet}}', 'WPCode', 'uncanny-automator-pro' ) );
		$this->set_helpers( new Wpcode_Pro_Helpers() );
	}

	/**
	 * options
	 *
	 * Override this method to display a default option group
	 *
	 * @return array
	 */
	public function options() {
		return array(
			array(
				'input_type'      => 'select',
				'option_code'     => $this->get_action_meta(),
				'required'        => true,
				'label'           => esc_attr_x( 'Snippet', 'WPCode', 'uncanny-automator-pro' ),
				'options'         => $this->get_helpers()->get_only_on_demand_snippets(),
				'relevant_tokens' => array(),
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
	 * @return mixed
	 */
	protected function process_action( $user_id, $action_data, $recipe_id, $args, $parsed ) {
		$snippet_id = isset( $parsed[ $this->get_action_meta() ] ) ? sanitize_text_field( $parsed[ $this->get_action_meta() ] ) : '';
		$snippet    = wpcode_get_snippet( absint( $snippet_id ) );

		if ( empty( $snippet->id ) ) {
			// translators: %s is a WPCode snippet ID
			$this->add_log_error( sprintf( esc_attr_x( 'The snippet ID: %s is not valid.', 'WPCode', 'uncanny-automator-pro' ), $snippet_id ) );

			return false;
		}

		wpcode()->execute->doing_activation(); // Mark this to unslash the code.
		$snippet->execute( apply_filters( 'wpcode_on_demand_ignore_conditional_logic', false ) );

		return true;
	}
}
