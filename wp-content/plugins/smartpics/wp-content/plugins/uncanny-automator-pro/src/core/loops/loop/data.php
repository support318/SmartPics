<?php
namespace Uncanny_Automator_Pro\Loops\Loop;

use Uncanny_Automator\Automator_Status;
use Uncanny_Automator_Pro\Loops\Loop\Exception\Loops_Exception;

class Data implements Entity_Loopable {

	/**
	 * @var array{array{mixed}}|null
	 */
	protected $data = null;

	/**
	 * @param array{array{mixed}}|null $data
	 *
	 * @return void
	 */
	public function __construct( $data ) {

		if ( empty( $data ) ) {
			throw new Loops_Exception( 'The provided loopable token did not yield any results.', Automator_Status::COMPLETED_WITH_NOTICE );
		}

		if ( ! is_iterable( $data ) ) {
			throw new Loops_Exception( 'Invalid data. Data must be of loopable token type.', Automator_Status::COMPLETED_WITH_ERRORS );
		}

		$this->data = $data;

	}

	/**
	 * @return int[]|array{array{mixed}}
	 */
	public function get() {
		return (array) $this->data;
	}

}
