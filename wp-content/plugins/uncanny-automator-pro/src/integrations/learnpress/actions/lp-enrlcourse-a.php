<?php

namespace Uncanny_Automator_Pro;

use LP_Order;

/**
 * Class LP_ENRLCOURSE_A
 *
 * @package Uncanny_Automator_Pro
 */
class LP_ENRLCOURSE_A {

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
		$this->action_code = 'LPENRLCOURSE-A';
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
			// translators: %1$s: course title
			'sentence'           => sprintf( esc_html_x( 'Enroll the user in {{a course:%1$s}}', 'LearnPress', 'uncanny-automator-pro' ), $this->action_meta ),
			'select_option_name' => esc_html_x( 'Enroll the user in {{a course}}', 'LearnPress', 'uncanny-automator-pro' ),
			'priority'           => 10,
			'accepted_args'      => 3,
			'execution_function' => array( $this, 'lp_enroll_in_course' ),
			'options_callback'   => array( $this, 'load_options' ),
		);

		Automator()->register->action( $action );
	}

	/**
	 * @return array[]
	 */
	public function load_options() {
		return Automator()->utilities->keep_order_of_options(
			array(
				'options' => array(
					Automator()->helpers->recipe->learnpress->options->all_lp_courses( null, 'LPCOURSE', false ),
				),
			)
		);
	}

	/**
	 * Validation function when the action is hit
	 *
	 * @param $user_id
	 * @param $action_data
	 * @param $recipe_id
	 */
	public function lp_enroll_in_course( $user_id, $action_data, $recipe_id, $args ) {

		if ( ! function_exists( 'learn_press_get_user' ) ) {
			$error_message = esc_html_x( 'The function learn_press_get_user does not exist', 'LearnPress', 'uncanny-automator-pro' );
			Automator()->complete_action( $user_id, $action_data, $recipe_id, $error_message );

			return;
		}

		if ( ! class_exists( '\LP_User_Item_Course' ) ) {
			$error_message = esc_html_x( 'The class LP_User_Item_Course does not exist. Please upgrade your LearnPress.', 'LearnPress', 'uncanny-automator-pro' );
			Automator()->complete_action( $user_id, $action_data, $recipe_id, $error_message );

			return;
		}

		//loading LP user by user_id
		$user = learn_press_get_user( $user_id );

		$course_id = $action_data['meta'][ $this->action_meta ];
		// loading LP course by ID
		$course = learn_press_get_course( $course_id );
		if ( $user->has_enrolled_course( $course_id ) ) {
			$args['do-nothing']                  = true;
			$action_data['do-nothing']           = true;
			$action_data['complete_with_errors'] = true;
			Automator()->complete_action( $user_id, $action_data, $recipe_id, esc_html_x( 'User already enrolled in course.', 'LearnPress', 'uncanny-automator-pro' ) );

			return;
		}
		if ( $course && $course->exists() ) {
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
			//}
			//Enroll to New Course
			// Data user_item for save database
			$user_item_data               = array(
				'user_id' => $user->get_id(),
				'item_id' => $course->get_id(),
				'ref_id'  => $order_id,
			);
			$user_item_data['status']     = LP_COURSE_ENROLLED;
			$user_item_data['graduation'] = LP_COURSE_GRADUATION_IN_PROGRESS;
			$user_item_data['start_time'] = current_time( 'mysql', true );

			$user_item_new = new \LP_User_Item_Course( $user_item_data );
			$result        = $user_item_new->update();

			if ( ! $result ) {
				Automator()->complete_action( $user_id, $action_data, $recipe_id, esc_html_x( "Error: Can't Enroll course.", 'LearnPress', 'uncanny-automator-pro' ) );

				return;
			}

			Automator()->complete_action( $user_id, $action_data, $recipe_id );

			do_action( 'learnpress/user/course-enrolled', $order_id, $course->get_id(), $user->get_id() ); // phpcs:ignore WordPress.NamingConventions.ValidHookName.UseUnderscores

		} else {
			$args['do-nothing']                  = true;
			$action_data['do-nothing']           = true;
			$action_data['complete_with_errors'] = true;
			Automator()->complete_action( $user_id, $action_data, $recipe_id, esc_html_x( 'Course not found.', 'LearnPress', 'uncanny-automator-pro' ) );

		}
	}
}
