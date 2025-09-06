<?php

namespace Uncanny_Automator_Pro;

/**
 * Class BP_USERACTIVITYSTREAM
 *
 * @package Uncanny_Automator_Pro
 */
class BP_USERACTIVITYSTREAM {

	/**
	 * Integration code
	 *
	 * @var string
	 */
	public static $integration = 'BP';

	private $action_code;
	private $action_meta;

	/**
	 * Set Triggers constructor.
	 */
	public function __construct() {
		$this->action_code = 'BPUSERACTIVITYSTREAM';
		$this->action_meta = 'BPACTION';

		$this->define_action();
	}

	/**
	 * Define and register the action by pushing it into the Automator object.
	 */
	public function define_action() {

		$action = array(
			'author'             => Automator()->get_author_name(),
			'support_link'       => Automator()->get_author_support_link( $this->action_code, 'integration/buddypress/' ),
			'is_pro'             => true,
			'integration'        => self::$integration,
			'code'               => $this->action_code,
			/* translators: Action - BuddyPress */
			'sentence'           => sprintf( esc_attr__( "Add a post to the user's {{activity:%1\$s}} stream", 'uncanny-automator-pro' ), $this->action_meta ),
			/* translators: Action - BuddyPress */
			'select_option_name' => esc_attr__( "Add a post to the user's {{activity}} stream", 'uncanny-automator-pro' ),
			'priority'           => 10,
			'accepted_args'      => 1,
			'execution_function' => array( $this, 'add_post_stream' ),
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
				'options_group' => array(
					$this->action_meta => array(
						Automator()->helpers->recipe->field->text_field( $this->action_meta, esc_attr__( 'Activity action', 'uncanny-automator-pro' ), true, 'text', '', false ),
						Automator()->helpers->recipe->buddypress->all_buddypress_users(
							__( 'Author', 'uncanny-automator-pro' ),
							'BPAUTHOR',
							array(
								'uo_include_any' => true,
								'uo_any_label'   => esc_attr__( 'User that completes the triggers', 'uncanny-automator-pro' ),
							)
						),
						Automator()->helpers->recipe->field->text_field( 'BPACTIONLINK', esc_attr__( 'Activity action link', 'uncanny-automator-pro' ), true, 'url', '', false ),
						Automator()->helpers->recipe->field->text_field( 'BPCONTENT', esc_attr__( 'Activity content', 'uncanny-automator-pro' ), true, 'textarea' ),
					),
				),
			)
		);
	}

	/**
	 * Remove from BP Groups
	 *
	 * @param string $user_id
	 * @param array $action_data
	 * @param string $recipe_id
	 *
	 * @since 1.1
	 * @return void
	 *
	 */
	public function add_post_stream( $user_id, $action_data, $recipe_id, $args ) {

		$action         = Automator()->parse->text( $action_data['meta']['BPACTION'], $recipe_id, $user_id, $args );
		$action         = do_shortcode( $action );
		$action_link    = Automator()->parse->text( $action_data['meta']['BPACTIONLINK'], $recipe_id, $user_id, $args );
		$action_link    = do_shortcode( $action_link );
		$action_content = $action_data['meta']['BPCONTENT'];
		$action_content = Automator()->parse->text( $action_content, $recipe_id, $user_id, $args );
		$action_content = do_shortcode( $action_content );
		$action_author  = $user_id;

		if ( isset( $action_data['meta']['BPAUTHOR'] ) ) {
			$action_author = Automator()->parse->text( $action_data['meta']['BPAUTHOR'], $recipe_id, $user_id, $args );

			if ( $action_author == '-1' ) {
				$action_author = $user_id;
			}
		}

		$activity = bp_activity_add(
			array(
				'action'        => $action,
				'content'       => $action_content,
				'primary_link'  => $action_link,
				'component'     => 'activity',
				'type'          => 'activity_update',
				'user_id'       => $action_author,
				'hide_sitewide' => true,
				'error_type'    => 'wp_error',
			)
		);

		if ( ! $activity ) {
			$activity = new \WP_Error( 'bp_activity_add', __( 'There was an error posting to stream.', 'uncanny-automator-pro' ) );
		}

		if ( is_wp_error( $activity ) ) {
			$action_data['complete_with_errors'] = true;
			$action_data['do-nothing']           = true;
			Automator()->complete_action( $user_id, $action_data, $recipe_id, __( $activity->get_error_message() ) );
			return;
		}

		Automator()->complete_action( $user_id, $action_data, $recipe_id );
	}

}
