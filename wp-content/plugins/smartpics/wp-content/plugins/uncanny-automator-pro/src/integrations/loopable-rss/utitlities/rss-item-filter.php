<?php
namespace Uncanny_Automator_Pro\Integration\Loopable_Rss\Utilities;

/**
 * Class RSS_Item_Filter
 *
 * Provides functionality to filter RSS items based on title or GUID uniqueness.
 *
 * @package Uncanny_Automator_Pro\Integration\Loopable_Rss\Utilities
 */
class RSS_Item_Filter {

	/**
	 * @var int
	 */
	protected $action_id = 0;

	/**
	 * @var RSS_Items_Unique_Handler
	 */
	private $rss_items_unique_handler;

	/**
	 * Constructor to initialize the unique handler.
	 *
	 * @param RSS_Items_Unique_Handler $rss_items_unique_handler Instance of RSS_Items_Unique_Handler.
	 */
	public function __construct( $rss_items_unique_handler ) {
		$this->rss_items_unique_handler = $rss_items_unique_handler;
	}

	/**
	 * Sets the action ID.
	 *
	 * @param int $action_id The action ID to set.
	 */
	public function set_action_id( $action_id ) {
		$this->action_id = $action_id;
	}

	/**
	 * Gets the action ID.
	 *
	 * @return int The current action ID.
	 */
	public function get_action_id() {
		return $this->action_id;
	}

	/**
	 * Filters RSS items to return only unique entries based on title or GUID.
	 *
	 * @param array $items           List of RSS items to filter.
	 * @param bool  $is_title_unique Whether to check for uniqueness based on the title. Default true.
	 * @param bool  $is_guid_used    Whether to check for uniqueness based on the GUID. Default false.
	 *
	 * @return array Filtered list of unique RSS items.
	 */
	public function filter_items( array $items = array(), bool $is_title_unique = true, bool $is_guid_used = false ) {
		$unique_items = array();

		foreach ( $items as $item ) {
			if ( ! $this->is_item_unique( $item, $is_title_unique, $is_guid_used ) ) {
				continue;
			}

			$unique_items[] = $item;
		}

		return $unique_items;
	}

	/**
	 * Checks if an RSS item is unique based on title and GUID.
	 *
	 * @param array $item             The RSS item to check.
	 * @param bool  $is_title_unique  Whether to check title uniqueness.
	 * @param bool  $is_guid_used     Whether to check GUID uniqueness.
	 *
	 * @return bool True if the item is unique, false otherwise.
	 */
	private function is_item_unique( array $item, bool $is_title_unique, bool $is_guid_used ) {

		// If GUID is used, skip the title check and only validate GUID uniqueness.
		if ( $is_guid_used ) {
			return $this->is_guid_unique( $item );
		}

		// Otherwise, check title uniqueness if required.
		if ( $is_title_unique && ! $this->is_title_unique( $item ) ) {
			return false;
		}
	}

	/**
	 * Checks if an RSS item's title is unique.
	 *
	 * @param array $item The RSS item to check.
	 *
	 * @return bool True if the title is unique, false otherwise.
	 */
	private function is_title_unique( array $item ) {

		$title = $item['title'][0]['_loopable_xml_text'] ?? '';

		if ( '' === $title ) {
			throw new \Exception( 'Unable to determine the title\'s uniqueness because the title is empty.', 1004 );
		}

		$title_rendered       = json_decode( $title, true );
		$title_rendered_value = $title_rendered['rendered'] ?? '';

		return $this->rss_items_unique_handler->is_unique( $title_rendered_value, $this->get_action_id(), 'title' );
	}

	/**
	 * Checks if an RSS item's GUID is unique.
	 *
	 * @param array $item The RSS item to check.
	 *
	 * @return bool True if the GUID is unique, false otherwise.
	 */
	private function is_guid_unique( array $item ) {

		$guid = $item['guid'][0]['_loopable_xml_text'] ?? '';

		if ( '' === $guid ) {
			throw new \Exception( 'Unable to determine the guid\'s uniqueness because the guid is empty.', 1005 );
		}

		return $this->rss_items_unique_handler->is_unique( $guid, $this->get_action_id(), 'guid' );
	}
}
