<?php

namespace Uncanny_Automator_Pro;

/**
 * Class Wplms_Pro_Helpers
 *
 * @package Uncanny_Automator_Pro
 */
class Wplms_Pro_Helpers extends \Uncanny_Automator\Wplms_Helpers {

	/**
	 * @var bool
	 */
	public $load_options = true;

	/**
	 * Wplms_Helpers constructor.
	 */
	public function __construct() {

		$this->load_options = Automator()->helpers->recipe->maybe_load_trigger_options( __CLASS__ );
	}

	/**
	 * @param Wplms_Pro_Helpers $pro
	 */
	public function setPro( Wplms_Pro_Helpers $pro ) {
		$this->pro = $pro;
	}
}
