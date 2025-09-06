<?php
//phpcs:disable PHPCompatibility.Operators.NewOperators.t_coalesceFound
/**
 * @package Uncanny_Automator\Integrations\Loopable_Xml\Actions
 *
 * @since 6.0
 */
namespace Uncanny_Automator\Integrations\Loopable_Xml\Actions;

use Exception;
use Uncanny_Automator\Integrations\Loopable_Xml\Helpers\Loopable_Xml_Helpers;
use Uncanny_Automator\Services\Loopable\Action_Loopable_Token\Store;
use Uncanny_Automator\Services\Loopable\Data_Integrations\Json_To_Array_Converter;
use Uncanny_Automator\Services\Loopable\Data_Integrations\Traits\Array_Loopable;
use Uncanny_Automator\Services\Loopable\Data_Integrations\Xml_To_Json_Converter;
use Uncanny_Automator\Services\Loopable\Loopable_Token_Collection;
use Uncanny_Automator\Utilities;
use Uncanny_Automator_Pro\Integrations\Loopable_Json\Tokens\Loopable\Analyze\Json_Content;
use Uncanny_Automator_Pro\Integrations\Loopable_Xml\Tokens\Loopable\Action\Xml_Items;
use Uncanny_Automator_Pro\Loops\Recipe\Token_Loop_Auto;

if ( ! trait_exists( '\Uncanny_Automator\Services\Loopable\Data_Integrations\Traits\Array_Loopable' ) ) {
	return;
}

/**
 * Loopable_Xml_Action
 *
 * @package Uncanny_Automator\Integrations\Loopable_Xml\Triggers
 *
 */
class Loopable_Xml_Action extends \Uncanny_Automator\Recipe\Action {

	use Array_Loopable;

	/**
	 * Setups the Action properties.
	 *
	 * @return void
	 */
	protected function setup_action() {

		$this->set_integration( 'LOOPABLE_XML' );
		$this->set_action_code( 'ACTION_LOOPABLE_XML_CODE' );
		$this->set_action_meta( 'ACTION_LOOPABLE_XML_META' );
		$this->set_requires_user( false );
		$this->set_is_pro( true );

		$this->set_readable_sentence(
			/* translators: Action sentence */
			esc_attr_x( 'Import {{an XML file}}', 'XML', 'uncanny-automator-pro' )
		);

		$this->set_sentence(
			sprintf(
				/* translators: Action sentence */
				esc_attr_x( 'Import {{an XML file:%1$s}}', 'XML', 'uncanny-automator-pro' ),
				$this->get_action_meta()
			)
		);

		$this->set_loopable_tokens(
			array(
				'LOOPABLE_XML_ITEMS' => Xml_Items::class,
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

			if ( 'ACTION_LOOPABLE_XML_META' !== $requesting_meta ) {
				return;
			}

			$loop_been_added = isset( $post_meta['LOOP_ADDED'] ) && 'yes' === $post_meta['LOOP_ADDED'];

			if ( $loop_been_added ) {
				return;
			}

			$config = array(
				'loopable_id' => 'LOOPABLE_XML_ITEMS',
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
		return Loopable_Xml_Helpers::make_fields( $this->get_action_meta() );
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

		$content_array = $this->get_xml_content_action_run( (array) $action_data['meta'] ?? array() );

		$root_path = '$.'; // Always root for XML.
		$limit     = $parsed['LIMIT_ROWS'] ?? null;

		$json_content_array = Utilities::get_array_value( $content_array, $root_path );
		$json_content_array = Utilities::limit_array_elements( $json_content_array, absint( $limit ) );

		$loopable = $this->create_loopable_items( (array) $json_content_array );

		$action_token_store = new Store();

		$action_token_store->hydrate_loopable_tokens(
			array(
				'LOOPABLE_XML_ITEMS' => $loopable,
			)
		);

		return true;

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
		$xpath       = $meta['XPATH'] ?? '';

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
		};

	}

}
