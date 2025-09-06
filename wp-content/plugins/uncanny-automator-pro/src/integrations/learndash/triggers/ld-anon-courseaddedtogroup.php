<?php
namespace Uncanny_Automator_Pro;

use Uncanny_Automator\Recipe;

/**
 * Class LD_ANON_COURSEADDEDTOGROUP
 *
 * @package Uncanny_Automator_Pro
 */
class LD_ANON_COURSEADDEDTOGROUP {

	use Recipe\Triggers;

	const TRIGGER_CODE = 'LD_ANON_COURSEADDEDTOGROUP';

	const TRIGGER_META = 'LD_ANON_COURSEADDEDTOGROUP_META';

	protected $helper;

	protected $ld_tokens = null;

	/**
	 * Set up Automator trigger constructor.
	 */
	public function __construct() {

		if ( class_exists( '\Uncanny_Automator_Pro\Ld_Pro_Tokens' ) && class_exists( '\Uncanny_Automator\Learndash_Helpers' ) ) {

			$this->set_helper( new \Uncanny_Automator\Learndash_Helpers( false ) );

			$this->ld_tokens = new \Uncanny_Automator_Pro\Ld_Pro_Tokens( false );

			if ( version_compare( AUTOMATOR_PLUGIN_VERSION, '4.8', '<' ) ) {
				return false;
			}
			$this->setup_trigger();

		}

	}

	/**
	 * Define and register the trigger by pushing it into the Automator object
	 */
	public function setup_trigger() {
		$this->set_integration( 'LD' );
		$this->set_trigger_code( self::TRIGGER_CODE );
		$this->set_trigger_meta( self::TRIGGER_META );
		$this->set_is_login_required( false );
		$this->set_trigger_type( 'anonymous' );
		$this->set_is_pro( true );
		$this->set_support_link( Automator()->get_author_support_link( $this->trigger_code, 'integration/learndash/' ) );

		$this->set_sentence(
		/* Translators: Trigger sentence */
			sprintf( esc_html__( '{{A course:%1$s}} is added to {{a group:%2$s}}', 'uncanny-automator-pro' ), $this->get_trigger_meta(), $this->get_trigger_meta() . '_GROUP' )
		);

		// Non-active state sentence to show
		$this->set_readable_sentence( esc_attr__( 'A course is added to a group', 'uncanny-automator-pro' ) );

		$this->set_options_callback( array( $this, 'load_options' ) );

		// Which do_action() fires this trigger.
		$this->set_action_hook( 'ld_added_course_group_access' );

		$this->set_options_callback( array( $this, 'load_options' ) );

		$this->set_action_args_count( 2 );

		if ( null !== $this->ld_tokens ) {

			$this->set_tokens( $this->ld_tokens->group_course_tokens() );

		}

		$this->register_trigger();

	}

	public function load_options() {

		return Automator()->utilities->keep_order_of_options(
			array(
				'options' => array(
					$this->helper->all_ld_courses( null, $this->get_trigger_meta(), true, false ),
					$this->helper->all_ld_groups( null, $this->get_trigger_meta() . '_GROUP', false, true, true, false ),
				),
			)
		);

	}

	/**
	 * Validate the trigger.
	 *
	 * @param $args
	 *
	 * @return bool
	 */
	protected function validate_trigger( ...$args ) {
		list( $course_id, $group_id ) = $args[0];

		if ( get_post_type( $course_id ) !== learndash_get_post_type_slug( 'course' ) && get_post_type( $group_id ) !== learndash_get_post_type_slug( 'group' ) ) {
			return false;
		}

		return true;
	}

	/**
	 * Prepare to run the trigger.
	 *
	 * @param $data
	 *
	 * @return void
	 */
	public function prepare_to_run( $data ) {
		$this->set_conditional_trigger( true );
	}

	/**
	 * Do continue anon trigger.
	 *
	 * @param ...$args
	 *
	 * @return bool
	 */
	public function do_continue_anon_trigger( ...$args ) {
		return true;
	}

	protected function validate_conditions( $args ) {

		list( $course_id, $group_id ) = $args;

		// Find the receiver user id
		return $this->find_all( $this->trigger_recipes() )
					->where( array( $this->get_trigger_meta() ) )
					->match( array( $course_id, $group_id ) )
					->format( array( 'intval', 'intval' ) )
					->get();

	}

	public function parse_additional_tokens( $parsed, $args, $trigger ) {
		return $this->ld_tokens->hydrate_group_course_tokens( $parsed, $args, $trigger );
	}

}
