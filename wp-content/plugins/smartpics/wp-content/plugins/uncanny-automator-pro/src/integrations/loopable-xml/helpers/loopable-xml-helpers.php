<?php
namespace Uncanny_Automator\Integrations\Loopable_Xml\Helpers;

/**
 * @package Uncanny_Automator\Integrations\Loopable_Xml\Helpers
 */
class Loopable_Xml_Helpers {

	/**
	 * Make fields.
	 *
	 * @param mixed $meta The trigger or action meta.
	 *
	 * @return mixed[]
	 */
	public static function make_fields( $meta ) {

		$data_source = array(
			'label'         => _x( 'Data source', 'XML', 'uncanny-automator-pro' ),
			'input_type'    => 'radio',
			'options'       => array(
				array(
					'text'  => 'Upload file',
					'value' => 'upload',
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
			'label'                  => _x( 'Describe data', 'XML', 'uncanny-automator-pro' ),
			'description'            => _x( 'Add a short description of the data you’re importing (e.g., "List of users").', 'XML', 'uncanny-automator-pro' ),
			'input_type'             => 'text',
			'required'               => true,
			'show_label_in_sentence' => true,
			'option_code'            => $meta,
		);

		$file = array(
			'label'              => _x( 'File', 'XML', 'uncanny-automator-pro' ),
			'input_type'         => 'file',
			'file_types'         => array( 'application/xml', 'text/xml' ),
			'option_code'        => 'FILE',
			'required'           => true,
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

		$link = array(
			'label'              => _x( 'Link to file', 'XML', 'uncanny-automator-pro' ),
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
			'label'           => _x( 'XPath to target elements', 'XML', 'uncanny-automator-pro' ),
			'description'     => _x( 'Choose the XPath that corresponds to the elements you want to extract (e.g., /channel/item).', 'XML', 'uncanny-automator-pro' ),
			'input_type'      => 'select',
			'option_code'     => 'XPATH',
			'required'        => true,
			'ajax'            => array(
				'event'         => 'parent_fields_change',
				'listen_fields' => array( 'FILE', 'LINK', 'DATA_SOURCE' ),
				'endpoint'      => 'automator_loopable_xml_determine_xml_root_paths',
			),
			'options_show_id' => false,
		);

		$limit_rows = array(
			'label'       => _x( 'Limit items', 'XML', 'uncanny-automator-pro' ),
			'description' => _x( 'Maximum number of rows to import. Leave empty for no limit.', 'XML', 'uncanny-automator-pro' ),
			'input_type'  => 'int',
			'option_code' => 'LIMIT_ROWS',
		);

		return array(
			$data_source,
			$describe_data,
			$file,
			$link,
			$root_path,
			$limit_rows,
		);

	}
}
