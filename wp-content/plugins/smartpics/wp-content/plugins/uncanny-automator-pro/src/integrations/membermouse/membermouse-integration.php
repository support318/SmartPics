<?php

namespace Uncanny_Automator_Pro\Integrations\MemberMouse;

use Uncanny_Automator\Integration;
use Uncanny_Automator\Integrations\MemberMouse\Membermouse_Helpers;

/**
 * Class Membermouse_Integration
 *
 * @pacakge Uncanny_Automator
 */
class Membermouse_Integration extends Integration {

	/**
	 * Must use function in new integration to setup all required values
	 *
	 * @return mixed
	 */
	protected function setup() {

		if ( ! class_exists( 'Uncanny_Automator\Integrations\MemberMouse\Membermouse_Helpers' ) ) {
			return;
		}

		$this->helpers = new Membermouse_Helpers();
		$this->set_integration( 'MEMBER_MOUSE' );
		$this->set_name( 'MemberMouse' );
		$this->set_icon_url( plugin_dir_url( __FILE__ ) . 'img/membermouse-icon.svg' );
	}

	/**
	 * Load Integration Classes.
	 *
	 * @return void
	 */
	public function load() {
		// Load triggers.
		new MM_MEMBERS_MEMBERSHIP_LEVEL_CHANGED( $this->helpers );
		new MM_MEMBERS_ACCOUNT_STATUS_CHANGED( $this->helpers );
		new MM_MEMBER_ACCOUNT_UPDATED( $this->helpers );

		// Load actions.
		new MM_ADD_BUNDLE_TO_MEMBERS_ACCOUNT( $this->helpers );
		new MM_CREATE_UPDATE_MEMBER( $this->helpers );
	}

	/**
	 * Check if Plugin is active.
	 *
	 * @return bool
	 */
	public function plugin_active() {
		return class_exists( 'MemberMouse' );
	}
}
