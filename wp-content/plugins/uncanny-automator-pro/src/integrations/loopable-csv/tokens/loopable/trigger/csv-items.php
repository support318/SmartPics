<?php
//phpcs:disable PHPCompatibility.Operators.NewOperators.t_coalesceFound

namespace Uncanny_Automator_Pro\Integrations\Loopable_Csv\Tokens\Loopable\Trigger;

use Error;
use Exception;
use Uncanny_Automator\Services\Loopable\Action_Loopable_Token\Store;
use Uncanny_Automator\Services\Loopable\Data_Integrations\Csv_To_Json_Converter;
use Uncanny_Automator\Services\Loopable\Data_Integrations\Utils;
use Uncanny_Automator\Services\Loopable\Loopable_Token_Collection;
use Uncanny_Automator\Services\Loopable\Trigger_Loopable_Token;
use Uncanny_Automator\Utilities;
use Uncanny_Automator_Pro\Integrations\Loopable_Json\Tokens\Loopable\Analyze\Csv_Content;

/**
 * Csv_Items
 *
 * @package Uncanny_Automator_Pro\Integrations\Loopable_Csv\Tokens\Loopable\Trigger
 */
class Csv_Items extends Trigger_Loopable_Token {

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

		$token_title = get_post_meta( $this->action_id, 'TRIGGER_LOOPABLE_CSV_META', true );
		$has_header  = get_post_meta( $this->action_id, 'HAS_HEADER', true );

		if ( ! is_string( $token_title ) ) {
			$token_title = '(Error: Invalid token title)';
		}

		if ( empty( $token_title ) ) {
			$token_title = '(Empty title)';
		}

		$json_content = $this->get_csv_content();

		// Root path for CSV is always root.
		$content_keys = Utils::identify_keys( $json_content, '$.' );

		$child_tokens = array();

		$count = 0;

		foreach ( $content_keys as $token ) {

			$token_name = $token;

			// Supports numeric indexes.
			if ( is_numeric( $token ) ) {
				$token_name = sprintf(
					/* translators: Token name */
					_x( 'Item %d', 'CSV', 'uncanny-automator-pro' ),
					$token
				);
			}

			if ( 'false' === $has_header ) {
				$column_name_alpha = ( new Csv_To_Json_Converter() )->number_to_alpha( $count );
				$token_name        = sprintf(
					/* translators: Token name */
					_x( 'Column %s', 'CSV', 'uncanny-automator-pro' ),
					$column_name_alpha
				);
				$token = sprintf( 'loopable_item_index_%d', $count );
			}

			$child_tokens[ $token ] = array(
				'name'       => $token_name,
				'id'         => $token,
				'token_type' => 'string',
			);

			$count++;
		}

		$this->set_id( 'TRIGGER_LOOPABLE_CSV_ITEMS' );
		$this->set_name( $token_title ?? _x( '(Empty `describe data` field', 'CSV', 'uncanny-automator-pro' ) );
		$this->set_log_identifier( '#{{id}}{{ID}}{{identifier}}' );
		$this->set_child_tokens( $child_tokens );

	}

	/**
	 * @param mixed $trigger_args
	 *
	 * @return Loopable_Token_Collection
	 */
	public function hydrate_token_loopable( $trigger_args ) {

		$loopable = new Loopable_Token_Collection();

		try {

			$trigger_id    = $trigger_args['_result_args']['trigger_id'] ?? null;
			$content_array = self::get_csv_content_action_run( Utilities::flatten_post_meta( get_post_meta( $trigger_id ) ) );

			$root_path = '$.'; // Always root for CSV.
			$limit     = $parsed['LIMIT_ROWS'] ?? null;

			$json_content_array = Utilities::get_array_value( $content_array, $root_path );
			$json_content_array = Utilities::limit_array_elements( $json_content_array, absint( $limit ) );

			$loopable = $this->create_loopable_items( $json_content_array );

			$action_token_store = new Store();

			$action_token_store->hydrate_loopable_tokens(
				array(
					'LOOPABLE_CSV_ITEMS' => $loopable,
				)
			);

		} catch ( Error $e ) {
			automator_log( 'Error: ' . $e->getMessage(), self::class . '@hydrate_token_loopable', true, 'csv-item' );
			return $loopable;
		} catch ( Exception $e ) {
			automator_log( 'Exception: ' . $e->getMessage(), self::class . '@hydrate_token_loopable', true, 'csv-item' );
			return $loopable;
		}

		return $loopable;
	}

	/**
	 * @return Loopable_Token_Collection
	 */
	public function create_loopable_items( $csv_items ) {

		$loopable = new Loopable_Token_Collection();

		foreach ( (array) $csv_items as $item ) {
			$loopable->create_item( $item );
		}

		return $loopable;

	}

	/**
	 * Returns the csv content in assoc array from the selected data.
	 *
	 * @return string
	 */
	public function get_csv_content() {

		$data_source = get_post_meta( $this->action_id, 'DATA_SOURCE', true );

		if ( 'upload' === $data_source ) {
			$meta_value = Utils::convert_to_string( get_post_meta( $this->action_id, 'LOOPABLE_CSV_CONTENT', true ), true );
			return $meta_value;
		}

		// Raw string.
		if ( 'paste' === $data_source ) {
			return Utils::convert_to_string( get_post_meta( $this->action_id, 'LOOPABLE_CSV_DATA', true ), true );
		}

		if ( 'link' === $data_source ) {
			return Utils::convert_to_string( get_post_meta( $this->action_id, 'LOOPABLE_CSV_CONTENT_FROM_LINK', true ), true );
		};

		return '';

	}

	/**
	 * Undocumented function
	 *
	 * @param int $action_id
	 * @param mixed[] $meta
	 * @param mixed[] $parsed
	 *
	 * @return array{}|mixed[]
	 */
	public static function get_csv_content_action_run( $meta ) {

		$data_source = $meta['DATA_SOURCE'] ?? '';
		$link        = $meta['LINK'] ?? '';
		$upload      = $meta['FILE'] ?? '';
		$paste       = $meta['DATA'] ?? '';
		$has_header  = $meta['HAS_HEADER'] ?? '';
		$delimiter   = $meta['DELIMITER'] ?? '';

		$csv_to_json = new Csv_To_Json_Converter();

		if ( array_key_exists( $delimiter, $csv_to_json::DELIMETERS ) ) {
			$csv_to_json->set_delimiter( $csv_to_json::DELIMETERS[ $delimiter ] );
		}

		if ( 'auto' === $delimiter ) {
			$csv_to_json->set_auto_detect_delimiter( true );
		}

		if ( 'paste' === $data_source ) {
			$content = $csv_to_json->load_from_text( $paste );
		}

		if ( 'upload' === $data_source ) {

			$file_url = (array) json_decode( $upload, true );
			$file_url = $file_url[0]['url'] ?? '';

			// Encode the url (supports non-ASCII characters).
			$file_url_encoded = Utils::encode_url( $file_url );

			if ( false === $file_url_encoded ) {
				throw new Exception( 'Invalid URL found: ' . $file_url_encoded );
			}

			$path = Csv_Content::get_attachment_path_from_url( $file_url );

			$content = $csv_to_json->load_from_file_path( $path );

		}

		if ( 'link' === $data_source ) {
			$content = $csv_to_json->load_from_url( $link );
		};

		return self::process_content( $has_header, $content );

	}

	/**
	 * @param string $has_header "true"|"false"
	 * @param Csv_To_Json_Converter $content
	 *
	 * @return Csv_To_Json_Converter
	 */
	public static function process_content( $has_header, Csv_To_Json_Converter $content ) {

		if ( 'false' === $has_header ) {
			$content->set_start_row( 0 );
			return (array) json_decode( $content->to_json_numeric(), true );
		}

		return (array) json_decode( $content->to_json(), true );

	}

}
