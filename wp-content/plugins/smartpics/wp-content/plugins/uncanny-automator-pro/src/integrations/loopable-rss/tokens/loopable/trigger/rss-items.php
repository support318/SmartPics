<?php
//phpcs:disable PHPCompatibility.Operators.NewOperators.t_coalesceFound

namespace Uncanny_Automator_Pro\Integrations\Loopable_Rss\Tokens\Loopable\Trigger;

use Exception;
use Uncanny_Automator\Services\Loopable\Data_Integrations\Json_To_Array_Converter;
use Uncanny_Automator\Services\Loopable\Data_Integrations\Traits\Array_Loopable;
use Uncanny_Automator\Services\Loopable\Data_Integrations\Utils;
use Uncanny_Automator\Services\Loopable\Data_Integrations\Xml_To_Json_Converter;
use Uncanny_Automator\Services\Loopable\Loopable_Token_Collection;
use Uncanny_Automator\Services\Loopable\Trigger_Loopable_Token;
use Uncanny_Automator\Utilities;
use Uncanny_Automator_Pro\Integrations\Loopable_Json\Tokens\Loopable\Action\Json_Items;
use Uncanny_Automator_Pro\Integrations\Loopable_Json\Tokens\Loopable\Analyze\Json_Content;
use Uncanny_Automator_Pro\Integrations\Loopable_Rss\Helpers\Loopable_Rss_Helpers;

if ( ! trait_exists( '\Uncanny_Automator\Services\Loopable\Data_Integrations\Traits\Array_Loopable' ) ) {
	return;
}

/**
 * Rss_Items
 *
 * @package Uncanny_Automator_Pro\Integrations\Loopable_Rss\Tokens\Loopable\Action
 */
class Rss_Items extends Trigger_Loopable_Token {

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

		$token_title = get_post_meta( $this->trigger_id, 'TRIGGER_LOOPABLE_RSS_META', true );

		if ( ! is_string( $token_title ) ) {
			$token_title = '(Error: Invalid token title)';
		}

		if ( empty( $token_title ) ) {
			$token_title = '(Empty title)';
		}

		$json_content_from_xml = $this->get_xml_content();
		$json_array_from_xml   = (array) json_decode( $json_content_from_xml, true );

		$child_tokens = Json_Items::prepare_tokens( $json_array_from_xml, '$.' );

		if ( empty( $json_array_from_xml ) ) {
			$child_tokens = array();
		}

		$this->set_id( 'TRIGGER_LOOPABLE_RSS_ITEMS' );
		$this->set_name( $token_title ?? _x( '(Empty `describe data` field', 'Loopable RSS', 'uncanny-automator-pro' ) );
		$this->set_log_identifier( '#{{id}}{{ID}}{{identifier}}' );
		$this->set_child_tokens( $child_tokens );
	}

	/**
	 * hydrate_token_loopable
	 *
	 * @param mixed $trigger_args
	 * @return Loopable_Token_Collection
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

	public function get_json_content_action_run( $meta ) {

		$data_source   = $meta['DATA_SOURCE'] ?? '';
		$file_contents = $meta['FILE'] ?? '';
		$xpath         = Loopable_Rss_Helpers::get_default_rss_xpath();
		$link          = $meta['LINK'] ?? '';

		$content = '';

		$xml_to_json = new Xml_To_Json_Converter();

		if ( 'upload' === $data_source ) {

			$file_contents_array = (array) json_decode( $file_contents, true );

			if ( json_last_error() !== JSON_ERROR_NONE ) {
				throw new Exception( 'JSON Error: ' . json_last_error_msg() );
			}

			$xml_content = Json_Content::extract_content_from_the_file_field( (array) $file_contents_array );

			try {

				$xml_to_json->load_from_text( $xml_content );
				$xml_to_json->set_xpath( $xpath );

				$content = $xml_to_json->to_json();

			} catch ( Exception $e ) {

				automator_log( $e->getMessage(), 'Upload: Error message', true, 'xml-token-loop' );

			}
		}

		if ( 'link' === $data_source ) {

			try {

				$xml_to_json->load_from_url( $link );
				$xml_to_json->set_xpath( $xpath );

				$content = $xml_to_json->to_json();

			} catch ( Exception $e ) {

				automator_log( $e->getMessage(), 'Link: Error message', true, 'xml-token-loop' );

			}
		}

		$json_to_array = new Json_To_Array_Converter();

		return $json_to_array->convert( $content );
	}

	/**
	 * Returns the xml content in assoc array from the selected data.
	 *
	 * @return string
	 */
	public function get_xml_content() {

		$data_source = get_post_meta( $this->trigger_id, 'DATA_SOURCE', true );

		if ( 'upload' === $data_source ) {
			$meta_value = Utils::convert_to_string( get_post_meta( $this->trigger_id, 'LOOPABLE_RSS_CONTENT', true ), true );
			return $meta_value;
		}

		if ( 'link' === $data_source ) {
			return Utils::convert_to_string( get_post_meta( $this->trigger_id, 'LOOPABLE_RSS_CONTENT_FROM_LINK', true ), true );
		}

		return '';
	}
}
