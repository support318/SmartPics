<?php
//phpcs:disable PHPCompatibility.Operators.NewOperators.t_coalesceFound

namespace Uncanny_Automator_Pro\Integrations\Loopable_Json\Tokens\Loopable\Trigger;

use Exception;
use Uncanny_Automator\Services\Loopable\Data_Integrations\Array_Key_Detector;
use Uncanny_Automator\Services\Loopable\Data_Integrations\Json_To_Array_Converter;
use Uncanny_Automator\Services\Loopable\Data_Integrations\Traits\Array_Loopable;
use Uncanny_Automator\Services\Loopable\Data_Integrations\Utils;
use Uncanny_Automator\Services\Loopable\Loopable_Token_Collection;
use Uncanny_Automator\Services\Loopable\Trigger_Loopable_Token;
use Uncanny_Automator\Utilities;
use Uncanny_Automator_Pro\Integrations\Loopable_Json\Tokens\Loopable\Analyze\Json_Content;

if ( ! trait_exists( '\Uncanny_Automator\Services\Loopable\Data_Integrations\Traits\Array_Loopable' ) ) {
	return;
}

/**
 * Json_Items
 *
 * @package Uncanny_Automator_Pro\Integrations\Loopable_Json\Tokens\Loopable\Action
 */
class Json_Items extends Trigger_Loopable_Token {

	use Array_Loopable;

	/**
	 * @var int
	 */
	protected $trigger_id = 0;

	/**
	 * @param int $trigger_id
	 *
	 * @return void
	 */
	public function __construct( int $trigger_id = 0 ) {
		$this->trigger_id = $trigger_id;
		parent::__construct();
	}

	/**
	 * Register loopable token.
	 *
	 * @return void
	 */
	public function register_loopable_token() {

		$token_title = get_post_meta( $this->trigger_id, 'TRIGGER_LOOPABLE_JSON_META', true );
		$root_path   = get_post_meta( $this->trigger_id, 'ROOT_PATH', true );

		$json_content_array = Utilities::get_array_value( (array) json_decode( $this->get_json_content(), true ), $root_path );
		$child_tokens       = self::prepare_tokens( $json_content_array, $root_path );

		if ( empty( $json_content_array ) ) {
			$child_tokens = array();
		}

		if ( ! is_string( $token_title ) || ! is_string( $root_path ) ) {
			$token_title = '(Error: Invalid token title)';
			$root_path   = '$.';
		}

		$this->set_id( 'TRIGGER_LOOPABLE_JSON_ITEMS' );
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

		$child_tokens = array();

		$content_keys = Array_Key_Detector::detect_keys( $json_content_array );

		foreach ( (array) $content_keys as $key => $token ) {

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

		$data_source = get_post_meta( $this->trigger_id, 'DATA_SOURCE', true );

		if ( 'upload' === $data_source ) {
			$meta_value = Utils::convert_to_string( get_post_meta( $this->trigger_id, 'LOOPABLE_JSON_CONTENT', true ), true );
			return $meta_value;
		}

		// paste. link
		if ( 'paste' === $data_source ) {
			return Utils::convert_to_string( get_post_meta( $this->trigger_id, 'DATA', true ) );
		}

		if ( 'link' === $data_source ) {
			return Utils::convert_to_string( get_post_meta( $this->trigger_id, 'LOOPABLE_JSON_CONTENT_FROM_LINK', true ), true );
		};

		return '';

	}

	/**
	 * @param mixed $trigger_args
	 *
	 * @return Loopable_Token_Collection
	 *
	 * @throws Exception
	 */
	public function hydrate_token_loopable( $trigger_args ) {

		$loopable   = new Loopable_Token_Collection();
		$trigger_id = $trigger_args['_result_args']['trigger_id'] ?? null;

		$meta           = Utilities::flatten_post_meta( get_post_meta( $trigger_id ) );
		$loopable_array = $this->get_json_content_action_run( $meta );

		$root_path = $meta['ROOT_PATH'] ?? null;
		$limit     = $meta['LIMIT_ROWS'] ?? null;

		$json_content_array = (array) Utilities::get_array_value( $loopable_array, $root_path );
		$json_content_array = (array) Utilities::limit_array_elements( $json_content_array, absint( $limit ) );

		$loopable = self::create_loopables( new Loopable_Token_Collection(), $json_content_array );

		return $loopable;

	}

	/**
	 * @param mixed[] $meta
	 *
	 * @return mixed[]
	 *
	 * @throws Exception
	 */
	public function get_json_content_action_run( $meta ) {

		$data_source = $meta['DATA_SOURCE'] ?? '';

		if ( 'paste' === $data_source ) {
			$content = $meta['DATA'] ?? '';
		}

		if ( 'upload' === $data_source ) {

			$file_contents       = $meta['FILE'] ?? '';
			$file_contents_array = (array) json_decode( $file_contents, true );

			if ( json_last_error() !== JSON_ERROR_NONE ) {
				throw new Exception( 'JSON Error: ' . json_last_error_msg() );
			}

			$content = Json_Content::extract_content_from_the_file_field( (array) $file_contents_array );

		}

		if ( 'link' === $data_source ) {
			$content = $meta['LINK'] ?? '';
		};

		$json_to_array = new Json_To_Array_Converter();

		return $json_to_array->convert( $content );

	}

}
