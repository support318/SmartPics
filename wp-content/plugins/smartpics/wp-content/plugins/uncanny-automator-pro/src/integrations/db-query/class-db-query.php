<?php
namespace Uncanny_Automator_Pro\DB_Query\Integration;

use Uncanny_Automator_Pro\Db_Query\Action\Run_Query;
use Uncanny_Automator_Pro\Db_Query\Action\Select_Query_Run_Action;

use Uncanny_Automator_Pro\Db_Query_Helpers;

/**
 * Class Db_Query_Integration
 *
 * @package Uncanny_Automator_Pro\DB_Query\Integration
 */
class Db_Query_Integration extends \Uncanny_Automator\Integration {

	/**
	 * Setups the Integration.
	 *
	 * @return void
	 */
	protected function setup() {

		// The unique integration code.
		$this->set_integration( 'DB_QUERY' );
		// The integration name. You can translate if you want to.
		$this->set_name( 'Database Query' );
		// The icon URL. Absolute URL path to the image file.
		$this->set_icon_url( plugin_dir_url( __FILE__ ) . '/img/db-query-icon.svg' );

	}

	/**
	 * Load some hooks required.
	 *
	 * @return void
	 */
	public function load() {

		$helper = new Db_Query_Helpers();

		add_action( 'wp_ajax_automator_db_query_select_run_retrieve_selected_columns_repeater', array( $helper, 'retrieve_selected_columns_repeater' ) );
		add_action( 'wp_ajax_automator_db_query_select_run_retrieve_selected_columns', array( $helper, 'retrieve_selected_columns' ) );
		add_action( 'wp_ajax_automator_db_query_select_run_retrieve_tables', array( $helper, 'retrieve_tables' ) );

		// The action code we're inserting the action tokens.
		$action_code = 'DB_QUERY_SELECT_QUERY_RUN';

		// Insert dynamic columns as action tokens.
		add_filter( "automator_action_{$action_code}_tokens_renderable", array( $helper, 'insert_dynamic_action_tokens' ), 20, 4 );

		new Select_Query_Run_Action();
		new Run_Query();

	}

}
