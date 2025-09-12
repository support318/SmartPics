<?php
//phpcs:disable PHPCompatibility.Operators.NewOperators.t_coalesceFound

namespace Uncanny_Automator_Pro\Integrations\Loopable_Json\Tokens\Loopable\Action;

use Uncanny_Automator\Services\Loopable\Action_Loopable_Token;
use Uncanny_Automator\Services\Loopable\Data_Integrations\Csv_To_Json_Converter;
use Uncanny_Automator\Services\Loopable\Data_Integrations\Utils;

/**
 * Csv_Items
 *
 * @package Uncanny_Automator_Pro\Integrations\Loopable_Json\Tokens\Loopable\Action
 */
class Csv_Items extends Action_Loopable_Token {

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

		$token_title = get_post_meta( $this->action_id, 'ACTION_LOOPABLE_CSV_META', true );
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

		$this->set_id( 'LOOPABLE_CSV_ITEMS' );
		$this->set_name( $token_title ?? _x( '(Empty `describe data` field', 'CSV', 'uncanny-automator-pro' ) );
		$this->set_log_identifier( '#{{id}}{{ID}}{{identifier}}' );
		$this->set_child_tokens( $child_tokens );

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

}
