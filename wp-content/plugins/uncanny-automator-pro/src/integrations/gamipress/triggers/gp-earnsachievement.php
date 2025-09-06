<?php

namespace Uncanny_Automator_Pro;

/**
 * Class GP_EARNSACHIEVEMENT
 *
 * @package Uncanny_Automator_Pro
 */
class GP_EARNSACHIEVEMENT {

	/**
	 * Integration code
	 *
	 * @var string
	 */
	public static $integration = 'GP';

	private $trigger_code;
	private $trigger_meta;

	/**
	 * SetAutomatorTriggers constructor.
	 */
	public function __construct() {
		$this->trigger_code = 'GPEARNSACHIEVEMENT';
		$this->trigger_meta = 'GPACHIEVEMENT';
		$this->define_trigger();

	}

	/**
	 * Define trigger settings
	 */
	public function define_trigger() {

		$trigger = array(
			'author'              => Automator()->get_author_name( $this->trigger_code ),
			'support_link'        => Automator()->get_author_support_link( $this->trigger_code, 'integration/gamipress/' ),
			'is_pro'              => true,
			'integration'         => self::$integration,
			'code'                => $this->trigger_code,
			/* translators: Logged-in trigger - GamiPress */
			'sentence'            => sprintf( __( 'A user earns {{an achievement:%1$s}}', 'uncanny-automator-pro' ), $this->trigger_meta ),
			/* translators: Logged-in trigger - GamiPress */
			'select_option_name'  => __( 'A user earns {{an achievement}}', 'uncanny-automator-pro' ),
			'action'              => 'gamipress_award_achievement',
			'priority'            => 20,
			'accepted_args'       => 5,
			'validation_function' => array( $this, 'earned_achievement' ),
			'options'             => array(),
			'options_callback'    => array( $this, 'load_options' ),
		);

		Automator()->register->trigger( $trigger );
	}

	/**
	 * @return array[]
	 */
	public function load_options() {
		return Automator()->utilities->keep_order_of_options(
			array(
				'options_group' => array(
					$this->trigger_meta => array(
						Automator()->helpers->recipe->gamipress->options->list_gp_award_types(
							__( 'Achievement type', 'uncanny-automator-pro' ),
							'GPAWARDTYPES',
							array(
								'token'        => false,
								'is_ajax'      => true,
								'is_any'       => true,
								'target_field' => $this->trigger_meta,
								'endpoint'     => 'select_achievements_from_types_EARNSACHIEVEMENT',
							)
						),
						Automator()->helpers->recipe->field->select_field( $this->trigger_meta, __( 'Award', 'uncanny-automator' ) ),
					),
				),
			)
		);
	}

	/**
	 * @param $user_id
	 * @param $achievement_id
	 * @param $trigger
	 * @param $site_id
	 * @param $args
	 */
	public function earned_achievement( $user_id, $achievement_id, $trigger, $site_id, $args ) {

		if ( ! $user_id ) {
			$user_id = get_current_user_id();
		}
		if ( empty( $user_id ) ) {
			return;
		}

		$recipes = Automator()->get->recipes_from_trigger_code( $this->trigger_code );

		if ( empty( $recipes ) ) {
			return;
		}

		$achievement_ids = Automator()->get->meta_from_recipes( $recipes, $this->trigger_meta );
		$award_types     = Automator()->get->meta_from_recipes( $recipes, 'GPAWARDTYPES' );
		if ( empty( $achievement_ids ) || empty( $award_types ) ) {
			return;
		}

		$matched_recipe_ids = array();

		$achievement_id = absint( $achievement_id );
		$award_type     = get_post_type( $achievement_id );
		foreach ( $recipes as $recipe_id => $recipe ) {
			foreach ( $recipe['triggers'] as $_trigger ) {
				$trigger_id = $_trigger['ID'];

				$r_achievement_id = (int) $achievement_ids[ $recipe_id ][ $trigger_id ];
				$r_award_type     = (string) $award_types[ $recipe_id ][ $trigger_id ];
				if ( ( intval( '-1' ) === intval( $r_award_type ) || $award_type === $r_award_type )
					 &&
					 ( intval( '-1' ) === intval( $r_achievement_id ) || (int) $achievement_id === (int) $r_achievement_id )
				) {
					$matched_recipe_ids[] = array(
						'recipe_id'      => $recipe_id,
						'trigger_id'     => $trigger_id,
						'achievement_id' => $r_achievement_id,
						'award_type'     => $r_award_type,
					);
				}
			}
		}

		if ( empty( $matched_recipe_ids ) ) {
			return;
		}

		foreach ( $matched_recipe_ids as $matched_recipe_id ) {

			$pass_args = array(
				'code'             => $this->trigger_code,
				'meta'             => $this->trigger_meta,
				'post_id'          => absint( $achievement_id ),
				'user_id'          => $user_id,
				'recipe_to_match'  => $matched_recipe_id['recipe_id'],
				'trigger_to_match' => $matched_recipe_id['trigger_id'],
			);

			$args = Automator()->maybe_add_trigger_entry( $pass_args, false );

			if ( $args ) {

				foreach ( $args as $result ) {
					if ( true === $result['result'] ) {

						$trigger_meta = array(
							'user_id'        => $user_id,
							'trigger_id'     => $result['args']['trigger_id'],
							'trigger_log_id' => $result['args']['trigger_log_id'],
							'run_number'     => $result['args']['run_number'],
						);

						$trigger_meta['meta_key']   = 'GPAWARDTYPES';
						$trigger_meta['meta_value'] = maybe_serialize( ucfirst( get_post_type( $achievement_id ) ) );
						Automator()->insert_trigger_meta( $trigger_meta );

						Automator()->db->token->save( 'GP_ACHIEVEMENT_IMAGE_URL', get_the_post_thumbnail_url( $achievement_id ), $trigger_meta );
						Automator()->db->token->save( 'GP_ACHIEVEMENT_ID', $achievement_id, $trigger_meta );

						Automator()->maybe_trigger_complete( $result['args'] );
					}
				}
			}
		}

	}
}
