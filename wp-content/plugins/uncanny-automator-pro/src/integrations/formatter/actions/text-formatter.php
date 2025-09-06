<?php

namespace Uncanny_Automator_Pro\Integrations\Formatter;

class Text_Formatter extends \Uncanny_Automator\Recipe\Action {

	/**
	 * setup_action
	 *
	 * @return void
	 */
	protected function setup_action() {

		// Define the Actions's info
		$this->set_integration( 'FORMATTER' );
		$this->set_action_code( 'TEXT' );
		$this->set_action_meta( 'INPUT' );
		$this->set_requires_user( false );
		$this->set_is_pro( true );

		// Define the Action's sentence
		// translators: input text, output format
		$this->set_sentence( sprintf( esc_attr__( 'Convert {{text:%1$s}} into {{format:%2$s}}', 'uncanny-automator-pro' ), $this->get_action_meta(), 'TO_FORMAT:' . $this->get_action_meta() ) );
		$this->set_readable_sentence( esc_attr__( 'Convert {{text}} into {{format}}', 'uncanny-automator-pro' ) );

	}

	/**
	 * options
	 *
	 * @return array
	 */
	public function options() {

		return array(
			Automator()->helpers->recipe->field->text(
				array(
					'option_code' => $this->get_action_meta(),
					'label'       => _x( 'Input', 'Text formatter', 'uncanny-automator-pro' ),
					'placeholder' => _x( 'Enter text', 'Text formatter', 'uncanny-automator-pro' ),
					'input_type'  => 'text',
				)
			),
			Automator()->helpers->recipe->field->select(
				array(
					'option_code'           => 'TO_FORMAT',
					'label'                 => _x( 'Output format', 'Text formatter', 'uncanny-automator-pro' ),
					'supports_custom_value' => false,
					'options'               => $this->text_format_options(),
					'options_show_id'       => false,
				)
			),
		);
	}

	/**
	 * define_tokens
	 *
	 * @return array
	 */
	public function define_tokens() {
		return array(
			'OUTPUT' => array(
				'name' => _x( 'Output', 'Text formatter', 'uncanny-automator-pro' ),
				'type' => 'text',
			),
		);
	}

	/**
	 * process_action
	 *
	 * @param  mixed $user_id
	 * @param  mixed $action_data
	 * @param  mixed $recipe_id
	 * @param  mixed $args
	 * @param  mixed $parsed
	 * @return bool
	 */
	public function process_action( $user_id, $action_data, $recipe_id, $args, $parsed ) {

		$input         = $this->get_parsed_meta_value( 'INPUT' );
		$output_format = $this->get_parsed_meta_value( 'TO_FORMAT' );

		$this->hydrate_tokens(
			array(
				'OUTPUT' => $this->format( $input, $output_format ),
			)
		);

		return true;
	}

	/**
	 * format
	 *
	 * @param  mixed $input
	 * @param  mixed $format
	 * @return string
	 */
	public function format( $input, $format ) {

		$formats = $this->get_formats();

		$output = call_user_func( $formats[ $format ]['callback'], $input );

		return $output;
	}

	/**
	 * text_format_options
	 *
	 * @return array
	 */
	public function text_format_options() {

		$formats = $this->get_formats();

		$options = array();

		foreach ( $formats as $id => $format ) {
			$options[] = array(
				'value' => $id,
				'text'  => $format['name'],
			);
		}

		return $options;
	}

	/**
	 * get_formats
	 *
	 * @return array
	 */
	public function get_formats() {

		$formats = array();

		$formats['uppercase'] = array(
			'name' => _x( 'Uppercase', 'Text formatter', 'uncanny-automator-pro' ),
		);

		$formats['lowercase'] = array(
			'name' => _x( 'Lowercase', 'Text formatter', 'uncanny-automator-pro' ),
		);

		$formats['title'] = array(
			'name' => _x( 'Title case', 'Text formatter', 'uncanny-automator-pro' ),
		);

		$formats['capitalize'] = array(
			'name' => _x( 'Capitalize first letters in words', 'Text formatter', 'uncanny-automator-pro' ),
		);

		$formats['escape_html'] = array(
			'name' => _x( 'Escape HTML', 'Text formatter', 'uncanny-automator-pro' ),
		);

		$formats['remove_html'] = array(
			'name' => _x( 'Remove HTML', 'Text formatter', 'uncanny-automator-pro' ),
		);

		$formats['trim'] = array(
			'name' => _x( 'Trim spaces around the string', 'Text formatter', 'uncanny-automator-pro' ),
		);

		$formats['shuffle'] = array(
			'name' => _x( 'Shuffle', 'Text formatter', 'uncanny-automator-pro' ),
		);

		$formats['reverse'] = array(
			'name' => _x( 'Reverse', 'Text formatter', 'uncanny-automator-pro' ),
		);

		$formats['slug'] = array(
			'name' => _x( 'Slugify', 'Text formatter', 'uncanny-automator-pro' ),
		);

		$formats['char_count'] = array(
			'name' => _x( 'Character count', 'Text formatter', 'uncanny-automator-pro' ),
		);

		$formats['word_count'] = array(
			'name' => _x( 'Word count', 'Text formatter', 'uncanny-automator-pro' ),
		);

		$formats['md5'] = array(
			'name' => _x( 'md5 hash', 'Text formatter', 'uncanny-automator-pro' ),
		);

		$formats['sha256'] = array(
			'name' => _x( 'sha256 hash', 'Text formatter', 'uncanny-automator-pro' ),
		);

		$formats['create_nonce'] = array(
			'name' => _x( 'Nonce create', 'Text formatter', 'uncanny-automator-pro' ),
		);

		$formats['url_encode'] = array(
			'name' => _x( 'URL encode', 'Text formatter', 'uncanny-automator-pro' ),
		);

		$formats['url_decode'] = array(
			'name' => _x( 'URL decode', 'Text formatter', 'uncanny-automator-pro' ),
		);

		$formats['extract_email'] = array(
			'name' => _x( 'Extract Email address', 'Text formatter', 'uncanny-automator-pro' ),
		);

		$formats['extract_url'] = array(
			'name' => _x( 'Extract URL', 'Text formatter', 'uncanny-automator-pro' ),
		);

		$formats['remove_spaces'] = array(
			'name' => _x( 'Remove spaces', 'Text formatter', 'uncanny-automator-pro' ),
		);

		foreach ( $formats as $id => &$format ) {
			$format['callback'] = array( $this, 'format_' . $id );
		}

		return apply_filters( 'automator_pro_formatter_text_formats', $formats );
	}

	public function format_uppercase( $input ) {
		return strtoupper( $input );
	}

	public function format_lowercase( $input ) {
		return strtolower( $input );
	}

	/**
	 * format_title
	 *
	 * @param  mixed $input
	 * @return string
	 */
	public function format_title( $input ) {

		$ignore = apply_filters(
			'automator_pro_formatter_title_case_ignore',
			array(
				'of',
				'a',
				'the',
				'and',
				'an',
				'or',
				'nor',
				'but',
				'is',
				'if',
				'then',
				'else',
				'when',
				'at',
				'from',
				'by',
				'on',
				'off',
				'for',
				'in',
				'out',
				'over',
				'to',
				'into',
				'with',
			)
		);

		$words = explode( ' ', $input );

		foreach ( $words as &$word ) {
			if ( ! in_array( strtolower( $word ), $ignore, true ) ) {
				$word = ucwords( $word );
			} else {
				$word = strtolower( $word );
			}
		}

		return implode( ' ', $words );
	}

	/**
	 * format_capitalize
	 */
	public function format_capitalize( $input ) {
		return ucwords( $input );
	}

	/**
	 * format_escape_html
	 */
	public function format_escape_html( $input ) {
		return htmlspecialchars( $input );
	}

	/**
	 * format_remove_html
	 */
	public function format_remove_html( $input ) {
		return wp_strip_all_tags( $input );
	}

	/**
	 * format_trim
	 */
	public function format_trim( $input ) {
		return trim( $input );
	}

	/**
	 * format_shuffle
	 */
	public function format_shuffle( $input ) {
		return str_shuffle( $input );
	}

	/**
	 * format_reverse
	 */
	public function format_reverse( $input ) {
		return strrev( $input );
	}

	/**
	 * format_slug
	 */
	public function format_slug( $input ) {
		return sanitize_title( $input );
	}

	/**
	 * format_char_count
	 */
	public function format_char_count( $input ) {
		return strlen( $input );
	}

	/**
	 * format_word_count
	 */
	public function format_word_count( $input ) {
		return str_word_count( $input );
	}

	/**
	 * format_md5
	 */
	public function format_md5( $input ) {
		return md5( $input );
	}

	/**
	 * format_sha256
	 */
	public function format_sha256( $input ) {
		return hash( 'sha256', $input );
	}

	/**
	 * format_create_nonce
	 */
	public function format_create_nonce( $input ) {
		return wp_create_nonce( $input );
	}

	/**
	 * format_url_encode
	 */
	public function format_url_encode( $input ) {
		return rawurlencode( $input );
	}

	/**
	 * format_url_decode
	 */
	public function format_url_decode( $input ) {
		return rawurldecode( $input );
	}

	/**
	 * format_extract_email
	 */
	public function format_extract_email( $input ) {

		$regexp = apply_filters( 'automator_pro_formatter_extract_email_pattern', '/([a-z0-9_\.\-])+\@(([a-z0-9\-])+\.)+([a-z0-9]{2,4})+/i' );

		preg_match( $regexp, $input, $matches );

		if ( isset( $matches[0] ) ) {
			return $matches[0];
		}

		return '';
	}

	/**
	 * format_extract_url
	 */
	public function format_extract_url( $input ) {

		$regexp = apply_filters( 'automator_pro_formatter_extract_url_pattern', '/\b(?:https?|ftp):\/\/\S+/i' );

		preg_match( $regexp, $input, $matches );

		if ( isset( $matches[0] ) ) {
			return $matches[0];
		}

		return '';
	}

	/**
	 * format_remove_spaces
	 */
	public function format_remove_spaces( $input ) {
		return str_replace( ' ', '', $input );
	}
}
