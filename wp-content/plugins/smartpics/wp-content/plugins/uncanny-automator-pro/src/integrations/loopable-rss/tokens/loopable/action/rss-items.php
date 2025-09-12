<?php
//phpcs:disable PHPCompatibility.Operators.NewOperators.t_coalesceFound

namespace Uncanny_Automator_Pro\Integrations\Loopable_Rss\Tokens\Loopable\Action;

use Uncanny_Automator\Services\Loopable\Action_Loopable_Token;
use Uncanny_Automator\Services\Loopable\Data_Integrations\Utils;
use Uncanny_Automator_Pro\Integrations\Loopable_Json\Tokens\Loopable\Action\Json_Items;

/**
 * Rss_Items
 *
 * @package Uncanny_Automator_Pro\Integrations\Loopable_Rss\Tokens\Loopable\Action
 */
class Rss_Items extends Action_Loopable_Token {

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

		$token_title = get_post_meta( $this->action_id, 'ACTION_LOOPABLE_RSS_META', true );

		if ( ! is_string( $token_title ) ) {
			$token_title = '(Error: Invalid token title)';
		}

		if ( empty( $token_title ) ) {
			$token_title = '(Empty title)';
		}

		$json_content_from_xml = $this->get_xml_content();
		$json_array_from_xml   = (array) json_decode( $json_content_from_xml, true );

		$child_tokens = Json_Items::prepare_tokens( $json_array_from_xml, '$.' );

		$this->set_id( 'LOOPABLE_RSS_ITEMS' );
		$this->set_name( $token_title ?? _x( '(Empty `describe data` field', 'Loopable RSS', 'uncanny-automator-pro' ) );
		$this->set_log_identifier( '#{{id}}{{ID}}{{identifier}}' );
		$this->set_child_tokens( $child_tokens );
	}

	/**
	 * Returns the xml content in assoc array from the selected data.
	 *
	 * @return string
	 */
	public function get_xml_content() {

		$data_source = get_post_meta( $this->action_id, 'DATA_SOURCE', true );

		if ( 'upload' === $data_source ) {
			$meta_value = Utils::convert_to_string( get_post_meta( $this->action_id, 'LOOPABLE_RSS_CONTENT', true ), true );
			return $meta_value;
		}

		if ( 'link' === $data_source ) {
			return Utils::convert_to_string( get_post_meta( $this->action_id, 'LOOPABLE_RSS_CONTENT_FROM_LINK', true ), true );
		}

		return '';
	}
}
