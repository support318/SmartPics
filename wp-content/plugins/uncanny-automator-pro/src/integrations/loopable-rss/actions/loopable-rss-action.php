<?php
//phpcs:disable PHPCompatibility.Operators.NewOperators.t_coalesceFound
/**
 * @package Uncanny_Automator\Integrations\Loopable_Rss\Actions
 *
 * @since 6.0
 */
namespace Uncanny_Automator_Pro\Integrations\Loopable_Rss\Actions;

use Exception;
use Uncanny_Automator\Services\Loopable\Action_Loopable_Token\Store;
use Uncanny_Automator\Services\Loopable\Data_Integrations\Json_To_Array_Converter;
use Uncanny_Automator\Services\Loopable\Data_Integrations\Traits\Array_Loopable;
use Uncanny_Automator\Services\Loopable\Data_Integrations\Xml_To_Json_Converter;
use Uncanny_Automator\Services\Loopable\Loopable_Token_Collection;
use Uncanny_Automator\Utilities;
use Uncanny_Automator_Pro\Integration\Loopable_Rss\Utilities\RSS_Item_Filter;
use Uncanny_Automator_Pro\Integration\Loopable_Rss\Utilities\RSS_Items_Table_Handler;
use Uncanny_Automator_Pro\Integration\Loopable_Rss\Utilities\RSS_Items_Unique_Handler;
use Uncanny_Automator_Pro\Integrations\Loopable_Json\Tokens\Loopable\Analyze\Json_Content;
use Uncanny_Automator_Pro\Integrations\Loopable_Rss\Helpers\Loopable_Rss_Helpers;
use Uncanny_Automator_Pro\Integrations\Loopable_Rss\Tokens\Loopable\Action\Rss_Items;
use Uncanny_Automator_Pro\Loops\Recipe\Token_Loop_Auto;

if ( ! trait_exists( '\Uncanny_Automator\Services\Loopable\Data_Integrations\Traits\Array_Loopable' ) ) {
	return;
}

/**
 * Loopable_Rss_Action
 *
 * @package Uncanny_Automator\Integrations\Loopable_Rss\Triggers
 *
 */
class Loopable_Rss_Action extends \Uncanny_Automator\Recipe\Action {

	use Array_Loopable;

	/**
	 * Setups the Action properties.
	 *
	 * @return void
	 */
	protected function setup_action() {

		$this->set_integration( 'LOOPABLE_RSS' );
		$this->set_action_code( 'ACTION_LOOPABLE_RSS_CODE' );
		$this->set_action_meta( 'ACTION_LOOPABLE_RSS_META' );
		$this->set_requires_user( false );
		$this->set_is_pro( true );

		$this->set_readable_sentence(
			/* translators: Action sentence */
			esc_attr_x( 'Process {{an RSS feed}}', 'RSS', 'uncanny-automator-pro' )
		);

		$this->set_sentence(
			sprintf(
				/* translators: Action sentence */
				esc_attr_x( 'Process {{an RSS feed:%1$s}}', 'RSS', 'uncanny-automator-pro' ),
				$this->get_action_meta()
			)
		);

		$this->set_loopable_tokens(
			array(
				'LOOPABLE_RSS_ITEMS' => Rss_Items::class,
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
		$closure = function ( $item, $recipe_id ) {

			$post_meta       = Utilities::flatten_post_meta( get_post_meta( $item->ID ?? null ) );
			$code            = $post_meta['code'] ?? '';
			$requesting_meta = automator_filter_input( 'optionCode', INPUT_POST );

			if ( 'ACTION_LOOPABLE_RSS_META' !== $requesting_meta ) {
				return;
			}

			$loop_been_added = isset( $post_meta['LOOP_ADDED'] ) && 'yes' === $post_meta['LOOP_ADDED'];

			if ( $loop_been_added ) {
				return;
			}

			$config = array(
				'loopable_id' => 'LOOPABLE_RSS_ITEMS',
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

		$general_fields = Loopable_Rss_Helpers::make_fields( $this->get_action_meta() );

		// Create two new fields.
		$unique_title = array(
			'option_code'           => 'IS_TITLE_UNIQUE',
			'input_type'            => 'select',
			'label'                 => _x( 'Unique titles only', 'RSS', 'uncanny-automator-pro' ),
			'supports_custom_value' => false,
			'options_show_id'       => false,
			'options'               => array(
				array(
					'text'  => _x( 'Yes', 'RSS', 'uncanny-automator-pro' ),
					'value' => 'yes',
				),
				array(
					'text'  => _x( 'No', 'RSS', 'uncanny-automator-pro' ),
					'value' => 'no',
				),
			),
			'description'           => _x( 'Whether to allow multiple feed items to have the same title. When checked, if a feed item has the same title as a previously-imported feed item, it will not be imported.', 'RSS', 'uncanny-automator-pro' ),
		);

		$use_guid = array(
			'option_code'   => 'IS_GUID_USED',
			'input_type'    => 'checkbox',
			'label'         => _x( 'Use GUIDs', 'RSS', 'uncanny-automator-pro' ),
			'is_toggle'     => true,
			'default_value' => false,
			'description'   => _x( 'Enable this option to identify duplicate feed items by their GUIDs, rather than by their title.', 'RSS', 'uncanny-automator-pro' ),
		);

		$action_fields = array(
			$unique_title,
			$use_guid,
		);

		return array_merge( $general_fields, $action_fields );
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

		$is_title_unique = $parsed['IS_TITLE_UNIQUE'] ?? 'yes';
		$is_guid_used    = $parsed['IS_GUID_USED'] ?? 'false';

		$content_array = $this->get_xml_content_action_run( (array) $action_data['meta'] ?? array() );

		$root_path = '$.'; // Always root for RSS.
		$limit     = $parsed['LIMIT_ROWS'] ?? null;

		$json_content_array = Utilities::get_array_value( $content_array, $root_path );
		$json_content_array = Utilities::limit_array_elements( $json_content_array, absint( $limit ) );

		// Filter the contents base on the uniqueness of the record.
		$unique_contents = self::filter_items(
			absint( $action_data['ID'] ),
			$json_content_array,
			'yes' === $is_title_unique,
			'true' === $is_guid_used
		);

		$loopable = $this->create_loopable_items( (array) $unique_contents );

		$action_token_store = new Store();

		$action_token_store->hydrate_loopable_tokens(
			array(
				'LOOPABLE_RSS_ITEMS' => $loopable,
			)
		);

		$processed_items_count = count( $json_content_array ) - count( $unique_contents );

		if ( count( $unique_contents ) !== count( $json_content_array ) ) {

			$this->add_log_error( sprintf( '%d items were skipped from the RSS feed because they were not unique.', $processed_items_count ) );
			$this->set_complete_with_notice( true );

			return;
		}

		return true;
	}

	/**
	 * @param int $action_id
	 * @param array $rss_items
	 * @param bool $is_title_unique
	 * @param bool $use_guid
	 *
	 * @return array
	 */
	public static function filter_items(
		int $action_id = 0,
		array $rss_items = array(),
		bool $is_title_unique = true,
		bool $is_guid_used = false
	) {
		global $wpdb;

		// Early bail if both options were disabled.
		if ( false === $is_title_unique && false === $is_guid_used ) {
			return $rss_items;
		}

		$unique_handler  = new RSS_Items_Unique_Handler( $wpdb );
		$rss_item_filter = new RSS_Item_Filter( $unique_handler );

		// Call the filter_items method to get unique items.
		$rss_item_filter->set_action_id( $action_id );
		$unique_rss_items = $rss_item_filter->filter_items( $rss_items, $is_title_unique, $is_guid_used );

		$table_handler = new RSS_Items_Table_Handler( $wpdb );

		foreach ( (array) $unique_rss_items as $item ) {

			$title = $item['title'][0]['_loopable_xml_text'] ?? '';
			$guid  = $item['guid'][0]['_loopable_xml_text'] ?? '';
			$link  = $item['link'][0]['_loopable_xml_text'] ?? ''; // Assuming 'link' is part of the item.

			if ( '' === $title ) {
				throw new Exception( 'The XML file or feed is malformed. The title field cannot be empty.', 1002 );
			}

			if ( '' === $guid ) {
				throw new Exception( 'The XML file or feed is malformed. The guid should not be empty.', 1003 );
			}

			// Insert the item using the table handler.
			$table_handler->add_item( $action_id, $guid, $title, $link );

		}

		return $unique_rss_items;
	}

	/**
	 * @return Loopable_Token_Collection
	 */
	public function create_loopable_items( $loopable_array ) {

		$loopable = self::create_loopables( new Loopable_Token_Collection(), $loopable_array );

		return $loopable;
	}

	/**
	 * Undocumented function
	 *
	 * @param mixed[] $meta
	 *
	 * @return array{}|mixed[]
	 */
	public function get_xml_content_action_run( $meta ) {

		$data_source = $meta['DATA_SOURCE'] ?? '';
		$xpath       = Loopable_Rss_Helpers::get_default_rss_xpath();

		$xml_to_json   = new Xml_To_Json_Converter();
		$json_to_array = new Json_To_Array_Converter();

		if ( 'upload' === $data_source ) {

			$file_contents = $meta['FILE'] ?? '';

			$file_contents_array = (array) json_decode( $file_contents, true );

			if ( json_last_error() !== JSON_ERROR_NONE ) {
				throw new Exception( 'JSON Error: ' . json_last_error_msg() );
			}

			$file_content = Json_Content::extract_content_from_the_file_field( (array) $file_contents_array );

			$xml_to_json->set_xpath( $xpath );
			$xml_to_json->load_from_text( $file_content );

			$content = $xml_to_json->to_json();

			return $json_to_array->convert( $content );

		}

		if ( 'link' === $data_source ) {

			$url = $meta['LINK'] ?? '';

			$xml_to_json->set_xpath( $xpath );
			$xml_to_json->load_from_url( $url );

			$content = $xml_to_json->to_json();

			return $json_to_array->convert( $content );
		}
	}
}
