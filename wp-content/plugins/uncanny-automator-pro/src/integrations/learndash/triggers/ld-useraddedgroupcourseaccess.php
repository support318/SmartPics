<?php // phpcs:ignore WordPress.Files.FileName.InvalidClassFileName

namespace Uncanny_Automator_Pro;

/**
 * Class LD_USERADDEDGROUPCOURSEACCESS
 *
 * @package Uncanny_Automator_Pro
 */
class LD_USERADDEDGROUPCOURSEACCESS {

	/**
	 * Integration code
	 *
	 * @var string
	 */
	public static $integration = 'LD';

	private $trigger_code;
	private $trigger_meta;

	/**
	 * Set up Automator trigger constructor.
	 */
	public function __construct() {
		$this->trigger_code = 'LD_USERADDEDGROUPCOURSEACCESS';
		$this->trigger_meta = 'LDCOURSE';
		$this->define_trigger();
	}

	/**
	 * Define and register the trigger by pushing it into the Automator object
	 */
	public function define_trigger() {

		$trigger = array(
			'author'              => Automator()->get_author_name( $this->trigger_code ),
			'support_link'        => Automator()->get_author_support_link( $this->trigger_code, 'integration/learndash/' ),
			'is_pro'              => true,
			'integration'         => self::$integration,
			'code'                => $this->trigger_code,
			/* translators: Logged-in trigger - LearnDash */
			'sentence'            => sprintf( __( 'A user is added to a group that has access to {{a course:%1$s}}', 'uncanny-automator-pro' ), $this->trigger_meta ),
			/* translators: Logged-in trigger - LearnDash */
			'select_option_name'  => __( 'A user is added to a group that has access to {{a course}}', 'uncanny-automator-pro' ),
			'action'              => 'ld_added_group_access',
			'priority'            => 10,
			'accepted_args'       => 2,
			'validation_function' => array( $this, 'validate_group_course_access' ),
			'options_callback'    => array( $this, 'load_options' ),
		);

		Automator()->register->trigger( $trigger );
	}

	/**
	 * @return array
	 */
	public function load_options() {
		$relevant_tokens                    = wp_list_pluck( Automator()->helpers->recipe->learndash->options->get_group_relevant_tokens( 'trigger' ), 'name' );
		$relevant_tokens['LDGROUP_LEADERS'] = __( 'Group Leader email', 'uncanny-automator-pro' );

		return Automator()->utilities->keep_order_of_options(
			array(
				'options' => array(
					Automator()->helpers->recipe->learndash->options->all_ld_courses( null, $this->trigger_meta, false, true, $relevant_tokens ),
				),
			)
		);
	}

	/**
	 * Validation function when the trigger action is hit
	 *
	 * @param $user_id
	 * @param $group_id
	 */
	public function validate_group_course_access( $user_id, $group_id ) {
		if ( empty( $group_id ) || empty( $user_id ) ) {
			return;
		}

		$recipes = Automator()->get->recipes_from_trigger_code( $this->trigger_code );
		if ( ! is_array( $recipes ) || empty( $recipes ) ) {
			return; // No recipes? Bailout.
		}

		$courses = Automator()->get->meta_from_recipes( $recipes, $this->trigger_meta );

		$group_course_ids = learndash_group_enrolled_courses( $group_id, true );
		if ( ! is_array( $group_course_ids ) || empty( $group_course_ids ) ) {
			return; // No courses in the group? Bailout.
		}

		$matched_recipe_ids = $this->get_matched_recipe_ids( $recipes, $courses, $group_course_ids );
		if ( empty( $matched_recipe_ids ) ) {
			return; // No matched reciped? Bailout.
		}

		$this->process_matched_recipes( $matched_recipe_ids, $user_id, $group_id );
	}

	private function get_matched_recipe_ids( array $recipes, array $courses, array $group_course_ids ) {
		$matched_recipe_ids = array();
		foreach ( $recipes as $recipe_id => $recipe ) {
			foreach ( $recipe['triggers'] as $trigger ) {
				$trigger_id = $trigger['ID'];
				$course_id  = absint( $courses[ $recipe_id ][ $trigger_id ] );
				if ( in_array( $course_id, $group_course_ids, true ) ) {
					$matched_recipe_ids[] = array(
						'recipe_id'  => $recipe_id,
						'trigger_id' => $trigger_id,
					);
				}
			}
		}
		return $matched_recipe_ids;
	}

	private function process_matched_recipes( $matched_recipe_ids, $user_id, $group_id ) {
		foreach ( $matched_recipe_ids as $matched_recipe_id ) {
			$pass_args = array(
				'code'             => $this->trigger_code,
				'meta'             => $this->trigger_meta,
				'user_id'          => $user_id,
				'recipe_to_match'  => $matched_recipe_id['recipe_id'],
				'trigger_to_match' => $matched_recipe_id['trigger_id'],
				'is_signed_in'     => true,
				'ignore_post_id'   => true,
			);

			$args = Automator()->maybe_add_trigger_entry( $pass_args, false );
			if ( $args ) {
				foreach ( $args as $result ) {
					if ( true === $result['result'] ) {
						$trigger_meta = array(
							'user_id'        => $user_id,
							'trigger_id'     => $result['args']['trigger_id'],
							'trigger_log_id' => $result['args']['get_trigger_id'],
							'run_number'     => $result['args']['run_number'],
						);

						Automator()->db->token->save( 'group_id', $group_id, $trigger_meta );
						Automator()->maybe_trigger_complete( $result['args'] );
					}
				}
			}
		}
	}
}
