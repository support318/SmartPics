<?php

namespace Uncanny_Automator_Pro;

use LP_Global;
use LP_Order;
use LP_User_Item_Course;

/**
 * Class LP_MARKCOURSEDONE
 *
 * @package Uncanny_Automator_Pro
 */
class LP_MARKCOURSEDONE {

	/**
	 * Integration code
	 *
	 * @var string
	 */
	public static $integration = 'LP';

	private $action_code;
	private $action_meta;

	/**
	 * Set up Automator action constructor.
	 */
	public function __construct() {
		$this->action_code = 'LPMARKCOURSEDONE-A';
		$this->action_meta = 'LPCOURSE';
		$this->define_action();
	}

	/**
	 * Define and register the action by pushing it into the Automator object
	 */
	public function define_action() {

		$action = array(
			'author'             => Automator()->get_author_name( $this->action_code ),
			'support_link'       => Automator()->get_author_support_link( $this->action_code, 'integration/learnpress/' ),
			'is_pro'             => true,
			'integration'        => self::$integration,
			'code'               => $this->action_code,
			/* translators: Action - LearnPress */
			'sentence'           => sprintf( __( 'Mark {{a course:%1$s}} complete for the user', 'uncanny-automator-pro' ), $this->action_meta ),
			/* translators: Action - LearnPress */
			'select_option_name' => __( 'Mark {{a course}} complete for the user', 'uncanny-automator-pro' ),
			'priority'           => 10,
			'accepted_args'      => 1,
			'execution_function' => array( $this, 'lp_mark_course_done' ),
			'options_callback'   => array( $this, 'load_options' ),
		);

		Automator()->register->action( $action );
	}

	/**
	 * @return array[]
	 */
	public function load_options() {
		$args = array(
			'post_type'      => 'lp_course',
			'posts_per_page' => 999,
			'orderby'        => 'title',
			'order'          => 'ASC',
			'post_status'    => 'publish',
		);

		$options = Automator()->helpers->recipe->options->wp_query( $args, false, __( 'Any course', 'uncanny-automator-pro' ) );

		return Automator()->utilities->keep_order_of_options(
			array(
				'options' => array(
					Automator()->helpers->recipe->learnpress->options->all_lp_courses( null, $this->action_meta, false ),
				),
			)
		);
	}


	/**
	 * Validation function when the action is hit.
	 *
	 * @param string $user_id user id.
	 * @param array $action_data action data.
	 * @param string $recipe_id recipe id.
	 */
	public function lp_mark_course_done( $user_id, $action_data, $recipe_id, $args ) {

		if ( ! function_exists( 'learn_press_get_current_user' ) ) {
			$error_message = 'The function learn_press_get_current_user does not exist';
			Automator()->complete_action( $user_id, $action_data, $recipe_id, $error_message );

			return;
		}
		$user = learn_press_get_user( $user_id );

		$course_id = $action_data['meta'][ $this->action_meta ];
		// Get All sections from course.
		$course = learn_press_get_course( $course_id );

		//Enroll to New Course
		if ( $course && $course->exists() ) {
			// Check if user is already enrolled
			if ( ! $user->has_enrolled_course( $course_id ) ) {
				// Create order for free enrollment
				$order_status = LP_ORDER_PENDING;
				$order        = new LP_Order();
				$order->set_customer_note( esc_html_x( 'Order created by Uncanny Automator', 'LearnPress', 'uncanny-automator-pro' ) );
				$order->set_status( $order_status );
				$order->set_total( 0 );
				$order->set_subtotal( 0 );
				$order->set_user_ip_address( learn_press_get_ip() );
				$order->set_user_agent( learn_press_get_user_agent() );
				$order->set_created_via( 'Uncanny Automator' );
				$order->set_user_id( $user_id );
				$order_id                      = $order->save();
				$order_item                    = array();
				$order_item['order_item_name'] = $course->get_title();
				$order_item['item_id']         = $course_id;
				$order_item['quantity']        = 1;
				$order_item['subtotal']        = 0;
				$order_item['total']           = 0;
				$item_id                       = $order->add_item( $order_item, 1 );
				$order->update_status( LP_ORDER_COMPLETED );
				
				// Create user course enrollment
				$user_item_data               = array(
					'user_id' => $user->get_id(),
					'item_id' => $course->get_id(),
					'ref_id'  => $order_id,
				);
				$user_item_data['status']     = LP_COURSE_ENROLLED;
				$user_item_data['graduation'] = LP_COURSE_GRADUATION_IN_PROGRESS;
				$user_item_data['start_time'] = current_time( 'mysql', true );

				$user_item_new = new \LP_User_Item_Course( $user_item_data );
				$user_item_new->update();
			}
		}

		$sections = $course->get_curriculum_raw();

		if ( ! empty( $sections ) ) {
			foreach ( $sections as $section ) {
				if ( isset( $section['items'] ) && is_array( $section['items'] ) ) {
					$lessons = $section['items'];
					// Mark lessons completed.
					foreach ( $lessons as $lesson ) {
						if ( $lesson['type'] === 'lp_lesson' ) {
							$result = $user->complete_lesson( $lesson['id'], $course_id );
						} elseif ( $lesson['type'] === 'lp_quiz' ) {
							$quiz_id = $lesson['id'];
							if ( ! $user->has_item_status(
								array(
									'started',
									'completed',
								),
								$quiz_id,
								$course_id
							) ) {
								$quiz_data = $user->start_quiz( $quiz_id, $course_id, false );
								$user->finish_quiz( $quiz_id, $course_id );
							}
						}
					}
				}
			}
		}

		$user_course = $user->get_course_data( $course_id );
		$result      = $user->finish_course( $course_id );

		if ( $result ) {
			Automator()->complete_action( $user_id, $action_data, $recipe_id );
		} else {
			$error_message = 'User not enrolled in course.';
			Automator()->complete_action( $user_id, $action_data, $recipe_id, $error_message );

			return;
		}
	}

}
