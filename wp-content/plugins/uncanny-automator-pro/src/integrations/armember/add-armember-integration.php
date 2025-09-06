<?php

namespace Uncanny_Automator_Pro;

use Uncanny_Automator\Recipe;

/**
 * Class Add_Armember_Integration
 *
 * @package Uncanny_Automator
 */
class Add_Armember_Integration {

	use Recipe\Integrations;

	/**
	 * Add_Affwp_Integration constructor.
	 */
	public function __construct() {
		$this->setup();
	}

	/**
	 *
	 */
	protected function setup() {
		$this->set_integration( 'ARMEMBER' );
		$this->set_name( 'ARMember Membership' );
		$this->set_icon( '/img/armember-icon.svg' );
		$this->set_icon_path( __DIR__ . '/img/' );
		$this->set_plugin_file_path( 'armember-membership/armember-membership.php' );
	}

	/**
	 * @return bool
	 */
	public function plugin_active() {
		return DEFINED( 'MEMBERSHIPLITE_DIR_NAME' ) || defined( 'MEMBERSHIP_DIR_NAME' );
	}

	/**
	 * Method get_icon_url.
	 *
	 * @return string
	 */
	protected function get_icon_url() {

		return plugins_url( $this->get_icon(), $this->get_icon_path() );

	}
}
