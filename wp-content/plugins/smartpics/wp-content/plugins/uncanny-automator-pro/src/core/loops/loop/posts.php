<?php
namespace Uncanny_Automator_Pro\Loops\Loop;

use Uncanny_Automator\Automator_Status;
use Uncanny_Automator_Pro\Loops\Loop\Exception\Loops_Exception;

class Posts implements Entity_Loopable {

	/**
	 * @var int[]
	 */
	protected $posts = array();

	/**
	 * @param int[] $posts
	 *
	 * @return void
	 */
	public function __construct( $posts = array() ) {

		if ( empty( $posts ) ) {
			throw new Loops_Exception( 'No posts met the filter criteria.', Automator_Status::COMPLETED_WITH_NOTICE );
		}

		if ( ! is_array( $posts ) ) {
			throw new Loops_Exception( 'Invalid return type. Post entity must returned an array of integers', Automator_Status::COMPLETED_WITH_ERRORS );
		}

		if ( false === $this->validate( $posts ) ) {
			throw new Loops_Exception( 'Invalid posts entity ID found. Post entities must be an array of integers.', Automator_Status::COMPLETED_WITH_ERRORS );
		}

		$this->posts = $posts;

	}

	/**
	 * @return int[]
	 */
	public function get() {
		return array_unique( $this->posts );
	}

	/**
	 * Validates the list of posts.
	 *
	 * @param int[] $posts
	 *
	 * @return bool True if posts are valid. Otherwise, false.
	 */
	public function validate( $posts = array() ) {

		if ( ! is_array( $posts ) || empty( $posts ) ) {
			return false;
		}

		$filtered_posts = array_filter(
			$posts,
			function( $post ) {
				return is_numeric( $post );
			}
		);

		return count( $filtered_posts ) === count( $posts );
	}

}
