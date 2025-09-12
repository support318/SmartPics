<?php
//phpcs:disable PHPCompatibility.Operators.NewOperators.t_coalesceFound

namespace Uncanny_Automator_Pro\Integrations\Loopable_Json\Tokens\Loopable\Action;

use Uncanny_Automator\Services\Loopable\Action_Loopable_Token;
use Uncanny_Automator\Services\Loopable\Data_Integrations\Array_Key_Detector;
use Uncanny_Automator\Services\Loopable\Data_Integrations\Utils;
use Uncanny_Automator\Utilities;

/**
 * Json_Items
 *
 * @package Uncanny_Automator_Pro\Integrations\Loopable_Json\Tokens\Loopable\Action
 */
class Json_Items extends Action_Loopable_Token {

	/**
	 * @var int
	 */
	protected $action_id = 0;

	/**
	 * @param int $action_id
	 *
	 * @return void
	 */
	public function __construct( int $action_id = 0 ) {
		$this->action_id = $action_id;
		parent::__construct();
	}

	/**
	 * Register loopable token.
	 *
	 * @return void
	 */
	public function register_loopable_token() {

		$token_title = get_post_meta( $this->action_id, 'ACTION_LOOPABLE_JSON_META', true );
		$root_path   = get_post_meta( $this->action_id, 'ROOT_PATH', true );

		$json_content_array = Utilities::get_array_value( (array) json_decode( $this->get_json_content(), true ), $root_path );
		$child_tokens       = self::prepare_tokens( $json_content_array, $root_path );

		if ( ! is_string( $token_title ) || ! is_string( $root_path ) ) {
			$token_title = '(Error: Invalid token title)';
			$root_path   = '$.';
		}

		$this->set_id( 'LOOPABLE_JSON_ITEMS' );
		$this->set_name( $token_title ?? _x( '(Empty `describe data` field', 'JSON', 'uncanny-automator-pro' ) );
		$this->set_log_identifier( '#{{id}}{{ID}}{{identifier}}' );
		$this->set_child_tokens( $child_tokens );

	}

	/**
	 * @param mixed[] $json_content_array
	 * @param string $root_path
	 *
	 * @return mixed[]
	 */
	public static function prepare_tokens( $json_content_array, $root_path ) {

		$content_keys = Array_Key_Detector::detect_keys( $json_content_array );

		// Support numeric root element.
		if ( 1 === count( $json_content_array )
				&& isset( $json_content_array[0] )
				&& is_array( $json_content_array )
				&& empty( $content_keys )
			) {
				$json_content_array = array_shift( $json_content_array );
				$content_keys       = array_keys( $json_content_array );
		}

		$child_tokens = array();

		foreach ( $content_keys as $key => $token ) {

			$token_name = $token;

			// Supports numeric indexes.
			if ( is_numeric( $token ) ) {
				$token_name = sprintf(
					/* translators: Token name */
					_x( 'Item %d', 'JSON', 'uncanny-automator-pro' ),
					$token
				);
			}

			if ( '_SCALAR_' === $token ) {
				$token_name = $key;
				$token      = '_automator_token_loop_scalar_value_' . $key;

				if ( is_numeric( $key ) ) {
					$token_name = 'Item ' . $key;
					$token      = '_automator_token_loop_scalar_value_item' . $key;
				}
			}

			if ( false !== strpos( $token, '.element_value' ) ) {
				$token_name = str_replace( '.element_value', '', $token );
			}

			$child_tokens[ $token ] = array(
				'name'       => $token_name,
				'id'         => $token,
				'token_type' => 'string',
			);
		}

		return $child_tokens;
	}

	/**
	 * Returns the json content in assoc array from the selected data.
	 *
	 * @return string
	 */
	public function get_json_content() {

		$data_source = get_post_meta( $this->action_id, 'DATA_SOURCE', true );

		if ( 'upload' === $data_source ) {
			$meta_value = Utils::convert_to_string( get_post_meta( $this->action_id, 'LOOPABLE_JSON_CONTENT', true ), true );
			return $meta_value;
		}

		// paste. link
		if ( 'paste' === $data_source ) {
			return Utils::convert_to_string( get_post_meta( $this->action_id, 'DATA', true ) );
		}

		if ( 'link' === $data_source ) {
			return Utils::convert_to_string( get_post_meta( $this->action_id, 'LOOPABLE_JSON_CONTENT_FROM_LINK', true ), true );
		};

		return '';

	}

}
