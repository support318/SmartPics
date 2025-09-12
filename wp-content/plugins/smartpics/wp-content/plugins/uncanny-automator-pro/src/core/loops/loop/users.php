<?php
namespace Uncanny_Automator_Pro\Loops\Loop;

use Uncanny_Automator\Automator_Status;
use Uncanny_Automator_Pro\Loops\Loop\Exception\Loops_Exception;

/**
 * Implements a user type driver.
 *
 * @since 5.3 - Now implements the Entity_Loopable interface.
 *
 * @package Uncanny_Automator_Pro\Loops\Loop
 */
class Users implements Entity_Loopable {

	/**
	 * @var int[]
	 */
	protected $users = array();

	/**
	 * @param int[] $users
	 *
	 * @return void
	 */
	public function __construct( $users = array() ) {

		if ( empty( $users ) ) {
			throw new Loops_Exception( 'No users met the filter criteria.', Automator_Status::COMPLETED_WITH_NOTICE );
		}

		if ( ! is_array( $users ) ) {
			throw new Loops_Exception( 'Invalid users format.', Automator_Status::COMPLETED_WITH_ERRORS );
		}

		if ( false === $this->validate( $users ) ) {
			throw new Loops_Exception( 'A user entries contains an invalid user ID', Automator_Status::COMPLETED_WITH_ERRORS );
		}

		$this->users = $users;

	}

	/**
	 * @return int[]
	 */
	public function get() {
		return array_unique( $this->users );
	}

	/**
	 * Validates the list of users.
	 *
	 * @param int[] $users
	 *
	 * @return bool True if users are valid. Otherwise, false.
	 */
	public function validate( $users = array() ) {

		if ( ! is_array( $users ) || empty( $users ) ) {
			return false;
		}

		$filtered_users = array_filter(
			$users,
			function( $user ) {
				return is_numeric( $user );
			}
		);

		return count( $filtered_users ) === count( $users );
	}

}
