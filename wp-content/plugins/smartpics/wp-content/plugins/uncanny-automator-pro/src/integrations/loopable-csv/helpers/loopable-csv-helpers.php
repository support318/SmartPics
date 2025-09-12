<?php
namespace Uncanny_Automator\Integrations\Loopable_Csv\Helpers;

/**
 * @package Uncanny_Automator\Integrations\Loopable_Csv\Helpers
 */
class Loopable_Csv_Helpers {

	/**
	 * Make fields.
	 *
	 * @param mixed $meta The trigger or action meta.
	 *
	 * @return mixed[]
	 */
	public static function make_fields( $meta ) {

		$data_source = array(
			'label'         => _x( 'Data source', 'CSV', 'uncanny-automator-pro' ),
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
			'label'                  => _x( 'Describe data', 'CSV', 'uncanny-automator-pro' ),
			'description'            => _x( 'Add a short description of the data you’re importing (e.g., "List of users").', 'CSV', 'uncanny-automator-pro' ),
			'input_type'             => 'text',
			'required'               => true,
			'show_label_in_sentence' => true,
			'option_code'            => $meta,
		);

		$file = array(
			'label'              => _x( 'File', 'CSV', 'uncanny-automator-pro' ),
			'input_type'         => 'file',
			'file_types'         => array( 'text/csv', 'application/csv' ),
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

		$data = array(
			'label'              => _x( 'Data', 'CSV', 'uncanny-automator-pro' ),
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
			'label'              => _x( 'Link to file', 'CSV', 'uncanny-automator-pro' ),
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

		$delimiter = array(
			'label'           => _x( 'Separator type', 'CSV', 'uncanny-automator-pro' ),
			'description'     => _x( 'Choose the character that separates fields in the CSV file.', 'CSV', 'uncanny-automator-pro' ),
			'required'        => true,
			'option_code'     => 'DELIMITER',
			'input_type'      => 'select',
			'options'         => array(
				array(
					'text'  => _x( 'Detect automatically', 'CSV', 'uncanny-automator-pro' ),
					'value' => 'auto',
				),
				array(
					'text'  => _x( 'Comma', 'CSV', 'uncanny-automator-pro' ),
					'value' => 'comma',
				),
				array(
					'text'  => _x( 'Pipe', 'CSV', 'uncanny-automator-pro' ),
					'value' => 'pipe',
				),
				array(
					'text'  => _x( 'Semicolon', 'CSV', 'uncanny-automator-pro' ),
					'value' => 'semicolon',
				),
				array(
					'text'  => _x( 'Tab', 'CSV', 'uncanny-automator-pro' ),
					'value' => 'tab',
				),
			),
			'options_show_id' => false,
		);

		$skip_rows = array(
			'label'         => _x( 'Skip initial rows', 'CSV', 'uncanny-automator-pro' ),
			'description'   => _x( 'Number of initial rows to skip before reading data.', 'CSV', 'uncanny-automator-pro' ),
			'default_value' => '0',
			'input_type'    => 'int',
			'option_code'   => 'SKIP_ROWS',
		);

		$header_toggle = array(
			'label'         => _x( 'CSV includes headers in the first row', 'CSV', 'uncanny-automator-pro' ),
			'description'   => _x( 'When enabled, the first row of the CSV will be treated as column headers. Disable this if the CSV contains only data rows without headers.', 'CSV', 'uncanny-automator-pro' ),
			'input_type'    => 'checkbox',
			'default_value' => true,
			'is_toggle'     => true,
			'option_code'   => 'HAS_HEADER',
		);

		$limit_rows = array(
			'label'       => _x( 'Limit rows', 'CSV', 'uncanny-automator-pro' ),
			'description' => _x( 'Maximum number of rows to import. Leave empty for no limit.', 'CSV', 'uncanny-automator-pro' ),
			'input_type'  => 'int',
			'option_code' => 'LIMIT_ROWS',
		);

		return array(
			$data_source,
			$describe_data,
			$file,
			$data,
			$link,
			$delimiter,
			$header_toggle,
			$skip_rows,
			$limit_rows,
		);

	}
}
