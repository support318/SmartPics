<?php


namespace Uncanny_Automator_Pro;

use Uncanny_Automator\Integromat_Helpers;
/**
 * Class Integromat_Pro_Helpers
 * @package Uncanny_Automator_Pro
 */
class Integromat_Pro_Helpers extends Integromat_Helpers {

	public $load_options = true;

	/**
	 * Integromat_Pro_Helpers constructor.
	 */
	public function __construct() {
		// Selectively load options
		if ( method_exists( '\Uncanny_Automator\Automator_Helpers_Recipe', 'maybe_load_trigger_options' ) ) {

			$this->load_options = Automator()->helpers->recipe->maybe_load_trigger_options( __CLASS__ );
		} else {

		}

	}

	/**
	 * @param Integromat_Pro_Helpers $pro
	 */
	public function setPro( Integromat_Pro_Helpers $pro ) {
		$this->pro = $pro;
	}
}
