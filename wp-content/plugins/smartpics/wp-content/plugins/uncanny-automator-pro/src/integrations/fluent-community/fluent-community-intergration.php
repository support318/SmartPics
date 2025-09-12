<?php
namespace Uncanny_Automator_Pro\Integrations\Fluent_Community;

/**
 * Class Fluent_Community_Integration
 *
 * @package Uncanny_Automator_Pro
 */
class Fluent_Community_Integration extends \Uncanny_Automator\Integration {

	/**
	 * Integration Set-up.
	 *
	 * @return void
	 */
	protected function setup() {
		// no special helpers for pro
		$this->helpers = new \Uncanny_Automator\Integrations\Fluent_Community\Fluent_Community_Helpers();

		$this->set_integration( 'FLUENT_COMMUNITY' );

		$this->set_name( 'Fluent Community' );

		$this->set_icon_url( plugin_dir_url( __FILE__ ) . 'img/fluent-community-icon.svg' );
	}

	/**
	 * Bootstrap actions, triggers, settings page, etc.
	 *
	 * @return void
	 */
	public function load() {

		//triggers
		new FLUENTCOMMUNITY_USER_UNENROLLED_COURSE( $this->helpers ); //pro
		new FLUENTCOMMUNITY_USER_SPACE_LEAVED( $this->helpers ); //pro
		new FLUENTCOMMUNITY_USER_SPACE_REQUESTED( $this->helpers );//pro
		new FLUENTCOMMUNITY_USER_POST_COMMENTED( $this->helpers );//pro
		new FLUENTCOMMUNITY_USER_POST_REACTED( $this->helpers );//pro
		//actions
		new FLUENTCOMMUNITY_UNENROLL_USER_COURSE( $this->helpers );
		new FLUENTCOMMUNITY_LEAVE_USER_SPACE( $this->helpers );
		new FLUENTCOMMUNITY_ADD_POST_TO_SPACE( $this->helpers );
		new FLUENTCOMMUNITY_MARK_COURSE_COMPLETE( $this->helpers );
		new FLUENTCOMMUNITY_MARK_LESSON_COMPLETE( $this->helpers );
	}

	/**
	 * Check if the plugin is active.
	 *
	 * @return bool Returns true if \Fluent_Community class is active. Returns false, othwerwise.
	 */
	public function plugin_active() {
		return class_exists( '\FluentCommunity\App\Services\Helper' );
	}
}
