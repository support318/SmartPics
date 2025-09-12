<?php
//phpcs:disable PHPCompatibility.Operators.NewOperators.t_coalesceFound

/**
 * @package Uncanny_Automator\Integrations\Loopable_Json\Actions
 *
 * @since 6.0
 */
namespace Uncanny_Automator\Integrations\Loopable_Csv\Actions;

use Exception;
use Uncanny_Automator\Integrations\Loopable_Csv\Helpers\Loopable_Csv_Helpers;
use Uncanny_Automator\Services\Loopable\Action_Loopable_Token\Store;
use Uncanny_Automator\Services\Loopable\Data_Integrations\Csv_To_Json_Converter;
use Uncanny_Automator\Services\Loopable\Data_Integrations\Utils;
use Uncanny_Automator\Services\Loopable\Loopable_Token_Collection;
use Uncanny_Automator\Utilities;
use Uncanny_Automator_Pro\Integrations\Loopable_Json\Tokens\Loopable\Action\Csv_Items;
use Uncanny_Automator_Pro\Integrations\Loopable_Json\Tokens\Loopable\Analyze\Csv_Content;
use Uncanny_Automator_Pro\Loops\Recipe\Token_Loop_Auto;

/**
 * Loopable_Csv_Action
 *
 * @package Uncanny_Automator\Integrations\Loopable_Json\Triggers
 *
 */
class Loopable_Csv_Action extends \Uncanny_Automator\Recipe\Action {

	/**
	 * Setups the Action properties.
	 *
	 * @return void
	 */
	protected function setup_action() {

		$this->set_integration( 'LOOPABLE_CSV' );
		$this->set_action_code( 'ACTION_LOOPABLE_CSV_CODE' );
		$this->set_action_meta( 'ACTION_LOOPABLE_CSV_META' );
		$this->set_requires_user( false );
		$this->set_is_pro( true );

		$this->set_readable_sentence(
			/* translators: Trigger sentence */
			esc_attr_x( 'Import {{a CSV file}}', 'CSV', 'uncanny-automator-pro' )
		);

		$this->set_sentence(
			sprintf(
				/* translators: Trigger sentence */
				esc_attr_x( 'Import {{a CSV file:%1$s}}', 'CSV', 'uncanny-automator-pro' ),
				$this->get_action_meta()
			)
		);

		$this->set_loopable_tokens(
			array(
				'LOOPABLE_CSV_ITEMS' => Csv_Items::class,
			)
		);

		$this->register_hooks();
	}

	/**
	 * Registers necessary hooks.
	 *
	 * Automatically creates a token loop when the action is added.
	 *
	 * @return void
	 */
	public function register_hooks() {

		// Create a new loop for this entity.
		$closure = function( $item, $recipe_id ) {

			$post_meta       = Utilities::flatten_post_meta( get_post_meta( $item->ID ?? null ) );
			$code            = $post_meta['code'] ?? '';
			$requesting_meta = automator_filter_input( 'optionCode', INPUT_POST );

			if ( 'ACTION_LOOPABLE_CSV_META' !== $requesting_meta ) {
				return;
			}

			$loop_been_added = isset( $post_meta['LOOP_ADDED'] ) && 'yes' === $post_meta['LOOP_ADDED'];

			if ( $loop_been_added ) {
				return;
			}

			$config = array(
				'loopable_id' => 'LOOPABLE_CSV_ITEMS',
				'type'        => 'ACTION_TOKEN',
				'entity_id'   => $item->ID ?? null,
				'entity_code' => $code ?? null,
				'meta'        => $this->get_action_meta(),
			);

			Token_Loop_Auto::persist( $item, $recipe_id, $config );

		};

		add_action( 'automator_recipe_option_updated_before_cache_is_cleared', $closure, 10, 2 );

	}

	/**
	 * Returns the options array.
	 *
	 * @return array
	 */
	public function options() {

		return Loopable_Csv_Helpers::make_fields( $this->get_action_meta() );

	}

	/**
	 * Processes the action.
	 *
	 * @param int $user_id
	 * @param mixed[] $action_data
	 * @param int $recipe_id
	 * @param mixed[] $args
	 * @param mixed[] $parsed
	 *
	 * @return bool Returns true if success.
	 *
	 * @throws Exception
	 */
	protected function process_action( $user_id, $action_data, $recipe_id, $args, $parsed ) {

		$content_array = self::get_csv_content_action_run( $action_data['meta'] );

		$root_path = '$.'; // Always root for CSV.
		$limit     = $parsed['LIMIT_ROWS'] ?? null;

		$json_content_array = Utilities::get_array_value( $content_array, $root_path );
		$json_content_array = Utilities::limit_array_elements( $json_content_array, absint( $limit ) );

		$loopable = $this->create_loopable_items( $root_path, $json_content_array );

		$action_token_store = new Store();

		$action_token_store->hydrate_loopable_tokens(
			array(
				'LOOPABLE_CSV_ITEMS' => $loopable,
			)
		);

		return true;

	}

	/**
	 * @return Loopable_Token_Collection
	 */
	public function create_loopable_items( $root_path, $csv_items ) {

		$loopable = new Loopable_Token_Collection();

		foreach ( (array) $csv_items as $item ) {
			$loopable->create_item( $item );
		}

		return $loopable;

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

			$path    = Csv_Content::get_attachment_path_from_url( $file_url );
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
