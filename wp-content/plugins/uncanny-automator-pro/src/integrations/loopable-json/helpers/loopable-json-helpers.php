<?php
namespace Uncanny_Automator\Integrations\Loopable_Json\Helpers;

/**
 * @package Uncanny_Automator\Integrations\Loopable_Json\Helpers
 */
class Loopable_Json_Helpers {

	/**
	 * Make fields.
	 *
	 * @param mixed $meta The trigger or action meta.
	 *
	 * @return mixed[]
	 */
	public static function make_fields( $meta ) {

		$data_source = array(
			'label'         => _x( 'Data source', 'JSON', 'uncanny-automator-pro' ),
			'input_type'    => 'radio',
			'options'       => array(
				array(
					'text'  => 'Upload file',
					'value' => 'upload',
				),
				array(
					'text'  => 'Paste data',
					'value' => 'paste',
				),
				array(
					'text'  => 'Link to file',
					'value' => 'link',
				),
			),
			'required'      => true,
			'default_value' => 'upload',
			'option_code'   => 'DATA_SOURCE',
		);

		$describe_data = array(
			'label'                  => _x( 'Describe data', 'JSON', 'uncanny-automator-pro' ),
			'description'            => _x( 'Add a short description of the data you’re importing (e.g., "List of users").', 'JSON', 'uncanny-automator-pro' ),
			'input_type'             => 'text',
			'required'               => true,
			'show_label_in_sentence' => true,
			'option_code'            => $meta,
		);

		$file = array(
			'label'              => _x( 'File', 'JSON', 'uncanny-automator-pro' ),
			'input_type'         => 'file',
			'file_types'         => array( 'application/json' ),
			'required'           => true,
			'option_code'        => 'FILE',
			'dynamic_visibility' => array(
				'default_state'    => 'hidden',
				'visibility_rules' => array(
					array(
						'rule_conditions'      => array(
							array(
								'option_code' => 'DATA_SOURCE',
								'compare'     => '==',
								'value'       => 'upload',
							),
						),
						'resulting_visibility' => 'show',
					),
				),
			),
		);

		$data = array(
			'label'              => _x( 'Data', 'JSON', 'uncanny-automator-pro' ),
			'input_type'         => 'textarea',
			'option_code'        => 'DATA',
			'supports_tokens'    => false,
			'required'           => true,
			'dynamic_visibility' => array(
				'default_state'    => 'hidden',
				'visibility_rules' => array(
					array(
						'rule_conditions'      => array(
							array(
								'option_code' => 'DATA_SOURCE',
								'compare'     => '==',
								'value'       => 'paste',
							),
						),
						'resulting_visibility' => 'show',
					),
				),
			),
		);

		$link = array(
			'label'              => _x( 'Link to file', 'JSON', 'uncanny-automator-pro' ),
			'input_type'         => 'url',
			'option_code'        => 'LINK',
			'supports_tokens'    => false,
			'required'           => true,
			'dynamic_visibility' => array(
				'default_state'    => 'hidden',
				'visibility_rules' => array(
					array(
						'rule_conditions'      => array(
							array(
								'option_code' => 'DATA_SOURCE',
								'compare'     => '==',
								'value'       => 'link',
							),
						),
						'resulting_visibility' => 'show',
					),
				),
			),
		);

		$root_path = array(
			'label'           => _x( 'Root path', 'JSON', 'uncanny-automator-pro' ),
			'description'     => _x( 'Select the path for the list or group of items you want to work with (e.g., $.data.items, where $.data.items points to a specific section of your data).', 'JSON', 'uncanny-automator-pro' ),
			'input_type'      => 'select',
			'ajax'            => array(
				'event'         => 'parent_fields_change',
				'listen_fields' => array( 'FILE', 'DATA', 'LINK', 'DATA_SOURCE' ),
				'endpoint'      => 'automator_loopable_json_determine_root_path',
			),
			'required'        => true,
			'default_value'   => '$.',
			'options_show_id' => false,
			'option_code'     => 'ROOT_PATH',
		);

		$limit_rows = array(
			'label'       => _x( 'Limit rows', 'JSON', 'uncanny-automator-pro' ),
			'description' => _x( 'Maximum number of rows to import. Leave empty for no limit.', 'JSON', 'uncanny-automator-pro' ),
			'input_type'  => 'int',
			'option_code' => 'LIMIT_ROWS',
		);

		return array(
			$data_source,
			$describe_data,
			$file,
			$data,
			$link,
			$root_path,
			$limit_rows,
		);

	}
}
