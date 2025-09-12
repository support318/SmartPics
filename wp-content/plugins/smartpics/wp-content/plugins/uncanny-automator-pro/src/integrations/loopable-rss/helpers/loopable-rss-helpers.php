<?php
namespace Uncanny_Automator_Pro\Integrations\Loopable_Rss\Helpers;

/**
 * @package Uncanny_Automator\Integrations\Loopable_Rss\Helpers
 */
class Loopable_Rss_Helpers {

	/**
	 * @var string
	 */
	protected static $default_xpath = '//channel/item';

	/**
	 * @return string
	 */
	public static function get_default_rss_xpath() {

		$xpath = apply_filters( 'automator_loopable_rss_default_xpath', self::$default_xpath );

		if ( ! is_scalar( $xpath ) ) {
			return self::$default_xpath;
		}

		return (string) self::$default_xpath;
	}

	/**
	 * Make fields.
	 *
	 * @param mixed $meta The trigger or action meta.
	 *
	 * @return mixed[]
	 */
	public static function make_fields( $meta ) {

		$data_source = array(
			'label'         => _x( 'Data source', 'RSS', 'uncanny-automator-pro' ),
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
			'label'                  => _x( 'Describe data', 'RSS', 'uncanny-automator-pro' ),
			'description'            => _x( 'Add a short description of the data you’re importing (e.g., "List of users").', 'RSS', 'uncanny-automator-pro' ),
			'input_type'             => 'text',
			'required'               => true,
			'show_label_in_sentence' => true,
			'option_code'            => $meta,
		);

		$file = array(
			'label'              => _x( 'File', 'RSS', 'uncanny-automator-pro' ),
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
			'label'              => _x( 'Link to file', 'RSS', 'uncanny-automator-pro' ),
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

		$limit_rows = array(
			'label'       => _x( 'Limit items', 'RSS', 'uncanny-automator-pro' ),
			'description' => _x( 'Maximum number of rows to import. Leave empty for no limit.', 'RSS', 'uncanny-automator-pro' ),
			'input_type'  => 'int',
			'option_code' => 'LIMIT_ROWS',
		);

		return array(
			$data_source,
			$describe_data,
			$file,
			$link,
			$limit_rows,
		);
	}
}
