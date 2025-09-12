<?php

namespace Uncanny_Automator_Pro\Integrations\Code_Snippets;

use Code_Snippets\Snippet;
use Uncanny_Automator\Recipe\Action;
use function Code_Snippets\save_snippet;

/**
 * Class CODE_SNIPPETS_CREATE_SNIPPET
 *
 * @pacakge Uncanny_Automator_Pro
 */
class CODE_SNIPPETS_CREATE_SNIPPET extends Action {

	protected $helpers;

	/**
	 * @return void
	 */
	protected function setup_action() {
		/** @var \Uncanny_Automator\Integrations\Code_Snippets\Code_Snippets_Helpers $helpers */
		$helpers       = array_shift( $this->dependencies );
		$this->helpers = $helpers;
		$this->set_integration( 'CODE_SNIPPETS' );
		$this->set_action_code( 'CS_CREATE_SNIPPET' );
		$this->set_action_meta( 'CS_SNIPPET' );
		$this->set_requires_user( false );
		$this->set_is_pro( true );
		$this->set_sentence( sprintf( esc_attr_x( 'Create {{a snippet:%1$s}}', 'Code Snippets', 'uncanny-automator-pro' ), $this->get_action_meta() ) );
		$this->set_readable_sentence( esc_attr_x( 'Create {{a snippet}}', 'Code Snippets', 'uncanny-automator-pro' ) );
	}

	/**
	 * @return array[]
	 */
	public function options() {
		return array(
			array(
				'input_type'      => 'text',
				'option_code'     => $this->get_action_meta(),
				'label'           => _x( 'Title', 'Code Snippets', 'uncanny-automator-pro' ),
				'required'        => true,
				'relevant_tokens' => array(),
			),
			array(
				'input_type'            => 'select',
				'option_code'           => 'CS_TYPE',
				'label'                 => _x( 'Type', 'Code Snippets', 'uncanny-automator-pro' ),
				'required'              => true,
				'options'               => $this->helpers->get_code_types(),
				'relevant_tokens'       => array(),
				'is_ajax'               => true,
				'fill_values_in'        => 'CS_SCOPE',
				'supports_tokens'       => false,
				'endpoint'              => 'get_all_scopes_by_code_types',
				'supports_custom_value' => false,
			),
			array(
				'input_type'            => 'select',
				'option_code'           => 'CS_SCOPE',
				'label'                 => _x( 'Scope', 'Code Snippets', 'uncanny-automator-pro' ),
				'required'              => true,
				'supports_tokens'       => false,
				'options'               => array(),
				'relevant_tokens'       => array(),
				'supports_custom_value' => false,
			),
			array(
				'input_type'      => 'textarea',
				'option_code'     => 'CS_CODE',
				'label'           => _x( 'Code', 'Code Snippets', 'uncanny-automator-pro' ),
				'required'        => true,
				'relevant_tokens' => array(),
			),
			array(
				'input_type'      => 'textarea',
				'option_code'     => 'CS_DESCRIPTION',
				'label'           => _x( 'Description', 'Code Snippets', 'uncanny-automator-pro' ),
				'required'        => false,
				'relevant_tokens' => array(),
			),
			array(
				'input_type'      => 'int',
				'option_code'     => 'CS_PRIORITY',
				'label'           => _x( 'Priority', 'Code Snippets', 'uncanny-automator-pro' ),
				'required'        => true,
				'relevant_tokens' => array(),
				'default_value'   => 10,
			),
			array(
				'input_type'            => 'select',
				'option_code'           => 'CS_STATUS',
				'label'                 => _x( 'Status', 'Code Snippets', 'uncanny-automator-pro' ),
				'required'              => true,
				'options'               => array(
					array(
						'text'  => esc_attr_x( 'Inactive', 'Code Snippets', 'uncanny_automator-pro' ),
						'value' => 0,
					),
					array(
						'text'  => esc_attr_x( 'Active', 'Code Snippets', 'uncanny_automator-pro' ),
						'value' => 1,
					),
				),
				'relevant_tokens'       => array(),
				'supports_custom_value' => false,
			),
			array(
				'input_type'               => 'select',
				'option_code'              => 'CS_TAGS',
				'label'                    => _x( 'Tags', 'Code Snippets', 'uncanny-automator-pro' ),
				'required'                 => false,
				'options'                  => $this->helpers->get_all_code_snippet_tags(),
				'relevant_tokens'          => array(),
				'supports_multiple_values' => true,
				'supports_custom_value'    => false,
			),
		);
	}

	/**
	 * @return array
	 */
	public function define_tokens() {
		return $this->helpers->get_action_common_tokens();

	}

	/**
	 * @param $user_id
	 * @param $action_data
	 * @param $recipe_id
	 * @param $args
	 * @param $parsed
	 *
	 * @return bool
	 */
	protected function process_action( $user_id, $action_data, $recipe_id, $args, $parsed ) {
		if ( ! function_exists( 'Code_Snippets\save_snippet' ) ) {
			$this->add_log_error( esc_attr_x( 'The function "Code_Snippets\save_snippet" dose not exists.', 'Code Snippets', 'uncanny-automator-pro' ) );

			return false;
		}

		$new_snippet           = new Snippet();
		$new_snippet->name     = isset( $parsed[ $this->get_action_meta() ] ) ? sanitize_text_field( $parsed[ $this->get_action_meta() ] ) : '';
		$new_snippet->priority = isset( $parsed['CS_PRIORITY'] ) ? absint( $parsed['CS_PRIORITY'] ) : 0;
		$new_snippet->desc     = isset( $parsed['CS_DESCRIPTION'] ) ? $parsed['CS_DESCRIPTION'] : '';
		$new_snippet->code     = isset( $parsed['CS_CODE'] ) ? $parsed['CS_CODE'] : '';
		$new_snippet->scope    = isset( $parsed['CS_SCOPE'] ) ? sanitize_text_field( $parsed['CS_SCOPE'] ) : '';
		$new_snippet->tags     = isset( $parsed['CS_TAGS'] ) ? json_decode( $parsed['CS_TAGS'] ) : '';
		$new_snippet->active   = isset( $parsed['CS_STATUS'] ) ? intval( $parsed['CS_STATUS'] ) : 0;

		$snippet_created = \Code_Snippets\save_snippet( $new_snippet );

		if ( ! $snippet_created instanceof \Code_Snippets\Snippet ) {
			$this->add_log_error( esc_attr_x( 'Could not create a new snippet.', 'Code Snippets', 'uncanny-automator-pro' ) );

			return false;
		}

		$this->hydrate_tokens( $this->helpers->parse_action_tokens( $snippet_created ) );

		return true;
	}
}
