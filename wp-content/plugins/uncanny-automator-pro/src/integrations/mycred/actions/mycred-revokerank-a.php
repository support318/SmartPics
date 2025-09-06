<?php

namespace Uncanny_Automator_Pro;

/**
 * Class MYCRED_REVOKERANK_A
 *
 * @package Uncanny_Automator_Pro
 */
class MYCRED_REVOKERANK_A {

	/**
	 * integration code
	 *
	 * @var string
	 */
	public static $integration = 'MYCRED';

	private $action_code;
	private $action_meta;

	/**
	 * Set up Automator action constructor.
	 */
	public function __construct() {
		$this->action_code = 'MYCREDREVOKERANK';
		$this->action_meta = 'MYCREDRANK';
		//$this->define_action();
	}

	/**
	 * Define and register the action by pushing it into the Automator object
	 */
	public function define_action() {

		$action = array(
			'author'             => Automator()->get_author_name(),
			'support_link'       => Automator()->get_author_support_link( $this->action_code, 'integration/mycred/' ),
			'is_pro'             => true,
			'integration'        => self::$integration,
			'code'               => $this->action_code,
			/* translators: Action - myCred */
			'sentence'           => sprintf( __( 'Revoke {{a rank:%1$s}} from the user', 'uncanny-automator-pro' ), $this->action_meta ),
			/* translators: Action - myCred */
			'select_option_name' => __( 'Revoke {{a rank}} from the user', 'uncanny-automator-pro' ),
			'priority'           => 10,
			'accepted_args'      => 1,
			'execution_function' => array( $this, 'revoke_mycred_ranks' ),
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
				'options'       => array(),
				'options_group' => array(
					$this->action_meta => array(
						Automator()->helpers->recipe->mycred->options->list_mycred_rank_types(
							__( 'Rank', 'uncanny-automator-pro' ),
							$this->action_meta,
							array(
								'token'        => false,
								'is_ajax'      => true,
								'target_field' => $this->action_meta,
								'include_all'  => true,
							)
						),
					),
				),
			)
		);
	}

	/**
	 * Validation function when the trigger action is hit
	 *
	 * @param $user_id
	 * @param $action_data
	 * @param $recipe_id
	 */
	public function revoke_mycred_ranks( $user_id, $action_data, $recipe_id, $args ) {

		$rank_id = $action_data['meta'][ $this->action_meta ];

		if ( 'ua-all-mycred-ranks' === $rank_id ) {
			$pointTypes = mycred_get_types();
			if ( is_array( $pointTypes ) && ! empty( $pointTypes ) ) {
				foreach ( $pointTypes as $key => $value ) {
					$user_ranks     = mycred_get_users_rank( absint( $user_id ), $key );
					$user_rank_type = ( $user_ranks->point_type->cred_id !== 'mycred_default' ) ? $user_ranks->point_type->cred_id : '';
					mycred_delete_user_meta( $user_id, MYCRED_RANK_KEY, $user_rank_type );
				}
			}
		} else {
			$rank_detail    = mycred_get_rank( $rank_id );
			$user_rank_type = ( $rank_detail->point_type->cred_id !== 'mycred_default' ) ? $rank_detail->point_type->cred_id : '';
			mycred_delete_user_meta( $user_id, MYCRED_RANK_KEY, $user_rank_type );
		}

		Automator()->complete_action( $user_id, $action_data, $recipe_id );
	}

}
