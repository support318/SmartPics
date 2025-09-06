<?php

namespace Uncanny_Automator_Pro;

/**
 * Class LF_ENRLMEMBERSHIP_A
 *
 * @package Uncanny_Automator_Pro
 */
class LF_ENRLMEMBERSHIP_A {

	/**
	 * Integration code
	 *
	 * @var string
	 */
	public static $integration = 'LF';

	private $action_code;
	private $action_meta;

	/**
	 * Set up Automator action constructor.
	 */
	public function __construct() {
		$this->action_code = 'LFENRLMEMBERSHIP-A';
		$this->action_meta = 'LFMEMBERSHIP';
		$this->define_action();
	}

	/**
	 * Define and register the action by pushing it into the Automator object
	 */
	public function define_action() {

		$action = array(
			'author'             => Automator()->get_author_name( $this->action_code ),
			'support_link'       => Automator()->get_author_support_link( $this->action_code, 'integration/lifterlms/' ),
			'is_pro'             => true,
			'integration'        => self::$integration,
			'code'               => $this->action_code,
			/* translators: Action - LifterLMS */
			'sentence'           => sprintf( __( 'Enroll the user in {{a membership:%1$s}}', 'uncanny-automator-pro' ), $this->action_meta ),
			/* translators: Action - LifterLMS */
			'select_option_name' => __( 'Enroll the user in {{a membership}}', 'uncanny-automator-pro' ),
			'priority'           => 10,
			'accepted_args'      => 1,
			'execution_function' => array( $this, 'lf_enroll_in_membership' ),
			'options'            => array(
				Automator()->helpers->recipe->lifterlms->options->all_lf_memberships( __( 'Membership', 'uncanny-automator' ), $this->action_meta, false ),
			),
		);

		Automator()->register->action( $action );
	}


	/**
	 * Validation function when the action is hit
	 *
	 * @param $user_id
	 * @param $action_data
	 * @param $recipe_id
	 */
	public function lf_enroll_in_membership( $user_id, $action_data, $recipe_id, $args ) {

		if ( ! function_exists( 'llms_enroll_student' ) ) {
			$error_message = 'The function llms_enroll_student does not exist';
			Automator()->complete_action( $user_id, $action_data, $recipe_id, $error_message );

			return;
		}

		$membership_id = $action_data['meta'][ $this->action_meta ];

		// Enroll to New Membership with a specific trigger message.
		$recipe_title = get_the_title( $recipe_id );
		if ( empty( $recipe_title ) ) {
				$recipe_title = _x( '(no title)', 'Recipe no title', 'uncanny-automator-pro' );
		}
		llms_enroll_student( $user_id, $membership_id, "Automator - {$recipe_title}" );

		Automator()->complete_action( $user_id, $action_data, $recipe_id );
	}

}
