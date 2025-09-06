<?php
//phpcs:disable PHPCompatibility.Operators.NewOperators.t_coalesceFound

namespace Uncanny_Automator_Pro\Integrations\Loopable_Rss;

use Uncanny_Automator\Automator_Status;
use Uncanny_Automator_Pro\Integrations\Loopable_Rss\Actions\Loopable_Rss_Action;
use Uncanny_Automator_Pro\Integrations\Loopable_Rss\Triggers\Loopable_Rss_Trigger;
use Uncanny_Automator_Pro\Integrations\Loopable_Rss\Tokens\Loopable\Analyze\Rss_Content;

/**
 * Loopable_Rss_Integration
 *
 * @package Uncanny_Automator_Pro\Integrations\Loopable_Rss
 */
class Loopable_Rss_Integration extends \Uncanny_Automator\Integration {

	/**
	 * Setups the Integration.
	 *
	 * @return void
	 */
	protected function setup() {

		$this->load_hooks( $this->helpers );

		$this->set_integration( 'LOOPABLE_RSS' );
		$this->set_name( 'RSS' );
		$this->set_icon_url( plugin_dir_url( __FILE__ ) . 'img/rss-feed-icon.svg' );
	}

	/**
	 * @param Loopable_Helpers $helper
	 *
	 * @return void
	 */
	public function load_hooks( $helper ) {

		add_filter( 'upload_mimes', array( $this, 'allow_xml_mime_type' ) );
		add_action( 'automator_recipe_before_update', array( Rss_Content::class, 'on_update_save_content' ), 10, 2 );
		add_action( 'wp_ajax_automator_loopable_xml_determine_xml_root_paths', array( Rss_Content::class, 'determine_xml_root_paths' ), 10, 2 );
		add_filter( 'automator_recipe_main_object\structure\miscellaneous', array( $this, 'misc_add_properties' ), 10, 2 );
	}

	/**
	 * Sets several new properties.
	 *
	 * The property "has_run_now" determines whether it has a run_now recipe or not.
	 *
	 * @param \Uncanny_Automator\Services\Recipe\Structure\Miscellaneous $miscellaneous
	 * @param \Uncanny_Automator\Services\Recipe\Structure $recipe_object
	 *
	 * @return mixed
	 */
	public function misc_add_properties( $miscellaneous, $recipe_object ) {

		$recipe_id = $recipe_object->get_recipe_id();

		$status = self::fetch_recipe_status( $recipe_id );

		if ( method_exists( '\Uncanny_Automator\Services\Recipe\Structure\Miscellaneous', 'set' ) ) {

			$has_run_now = $this->recipe_has_run_now_trigger( $recipe_id );

			if ( false === $has_run_now ) {
				return $miscellaneous;
			}

			$miscellaneous->set( 'has_run_now', $has_run_now );
			$miscellaneous->set( 'recipe_is_running', Automator_Status::IN_PROGRESS === $status );

			$can_edit = true;

			// Make sure to only apply if has run now trigger.
			if ( true === $has_run_now && true === $miscellaneous->get( 'recipe_is_running' ) ) {
				$can_edit = false;
			}

			$miscellaneous->set( 'can_edit', $can_edit );
			$miscellaneous->set( 'latest_recipe_log_status', Automator_Status::get_class_name( $status ) );

		}

		return $miscellaneous;
	}

	/**
	 * Determines whether the recipe has run now trigger.
	 *
	 * @param int $recipe_id
	 *
	 * @return bool True if the recipe has run now Trigger. Returns false, otherwise.
	 */
	protected function recipe_has_run_now_trigger( $recipe_id ) {

		return ! empty( $this->fetch_recipe_run_now_trigger( $recipe_id ) );
	}

	/**
	 * Fetches all Triggers having "RECIPE_MANUAL_TRIGGER_ANON" as trigger code in a specific recipe.
	 *
	 * @param int $recipe_id
	 *
	 * @return string[]
	 */
	protected function fetch_recipe_run_now_trigger( $recipe_id ) {

		global $wpdb;

		$results = (array) $wpdb->get_results(
			$wpdb->prepare(
				"SELECT ID FROM {$wpdb->posts} AS post 
					INNER JOIN {$wpdb->postmeta} AS meta 
						ON meta.post_id = post.ID
					WHERE meta.meta_key = %s AND meta.meta_value = %s
						AND post.post_parent = %d",
				'code',
				'TRIGGER_LOOPABLE_RSS_CODE',
				$recipe_id
			),
			ARRAY_A
		);

		return $results;
	}

	/**
	 * Fetches the recipe status from the database using the recipe ID.
	 *
	 * The status will be based on the most recent log.
	 *
	 * @param int $recipe_id
	 *
	 * @return int The recipe status.
	 */
	public static function fetch_recipe_status( $recipe_id ) {

		global $wpdb;

		$results = (array) $wpdb->get_results(
			$wpdb->prepare(
				"SELECT ID, completed from {$wpdb->prefix}uap_recipe_log WHERE automator_recipe_id = %d ORDER by ID DESC",
				$recipe_id
			),
			ARRAY_A
		);

		// If there are no results, maybe recipe is still running?
		// Return not completed.
		if ( empty( $results ) || ! isset( $results[0]['completed'] ) ) {
			return Automator_Status::NOT_COMPLETED;
		}

		return absint( $results[0]['completed'] );
	}

	/**
	 * Allows 'text/xml' mime type to be uploaded.
	 *
	 * @param string[] $mimes
	 *
	 * @return mixed[]
	 */
	public function allow_xml_mime_type( $mimes ) {

		// Allow XML files.
		$mimes['xml'] = 'text/xml';

		return $mimes;
	}

	/**
	 * Loads actions and settings.
	 *
	 * @return void
	 */
	public function load() {

		new Loopable_Rss_Trigger();

		new Loopable_Rss_Action();
	}
}
