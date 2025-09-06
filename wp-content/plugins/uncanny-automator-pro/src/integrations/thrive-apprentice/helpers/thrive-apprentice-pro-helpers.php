<?php
namespace Uncanny_Automator_Pro\Integrations\Thrive_Apprentice;

use Uncanny_Automator\Integrations\Thrive_Apprentice\Thrive_Apprentice_Helpers;
/**
 * Class Thrive_Apprentice_Pro_Helpers
 *
 * Use this class to extend pro integration.
 *
 * @package Uncanny_Automator_Pro
 */
class Thrive_Apprentice_Pro_Helpers extends Thrive_Apprentice_Helpers {

	public function __construct() {
		// Nothing to initialize for now
	}

	/**
	 * Get course progress percentage for a user
	 *
	 * @param int $user_id The user ID
	 * @param int $course_id The course ID
	 * @return string The progress percentage (e.g. "75%")
	 */
	public function get_course_progress_percentage( $user_id, $course_id ) {
		$progress = '0%';

		if ( class_exists( '\TVA_Customer' ) && class_exists( '\TVA_Course_V2' ) && $user_id > 0 && $course_id > 0 ) {
			try {
				$customer   = new \TVA_Customer( $user_id );
				$tva_course = new \TVA_Course_V2( $course_id );

				// Verify course exists
				if ( $tva_course->get_id() ) {
					// Use Thrive's built-in progress calculation
					$progress_data = $customer->calculate_progress( $tva_course );

					if ( isset( $progress_data['progress'] ) && ! empty( $progress_data['progress'] ) ) {
						$progress = $progress_data['progress'];
					}
				}
			} catch ( \Exception $e ) {
				// Log error for debugging
				$progress = '0%';
			}
		}

		return $progress;
	}
}
