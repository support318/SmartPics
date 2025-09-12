<?php

namespace Uncanny_Automator_Pro;

/**
 * Class BDB_GUESTREPLIESTOTOPIC
 *
 * @package Uncanny_Automator
 */
class BDB_GUESTREPLIESTOTOPIC {

	/**
	 * Integration code
	 *
	 * @var string
	 */
	public static $integration = 'BDB';

	private $trigger_code;
	private $trigger_meta;

	/**
	 * Set up Automator trigger constructor.
	 */
	public function __construct() {
		$this->trigger_code = 'BDBGUESTREPLIESTOTOPIC';
		$this->trigger_meta = 'BBTOPIC';
		$this->define_trigger();
	}

	/**
	 * Define and register the trigger by pushing it into the Automator object
	 */
	public function define_trigger() {

		$trigger = array(
			'author'              => Automator()->get_author_name( $this->trigger_code ),
			'support_link'        => Automator()->get_author_support_link( $this->trigger_code, 'integration/bbpress/' ),
			'integration'         => self::$integration,
			'code'                => $this->trigger_code,
			'is_pro'              => true,
			/* translators: Logged-in trigger - bbPress */
			'sentence'            => sprintf( esc_attr__( 'A guest replies to {{a topic:%1$s}}', 'uncanny-automator-pro' ), $this->trigger_meta ),
			/* translators: Logged-in trigger - bbPress */
			'select_option_name'  => esc_attr__( 'A guest replies to {{a topic}}', 'uncanny-automator-pro' ),
			'action'              => 'bbp_new_reply',
			'priority'            => 99,
			'accepted_args'       => 3,
			'type'                => 'anonymous',
			'validation_function' => array(
				$this,
				'bbp_guest_replies_to_topic',
			),
			'options_callback'    => array( $this, 'load_options' ),
		);

		Automator()->register->trigger( $trigger );
	}

	/**
	 * @return array[]
	 */
	public function load_options() {

		$bbpress_helper = null;
		if ( ! is_a( Automator()->helpers->recipe->bbpress->options->pro, 'Bbpress_Pro_Helpers' ) ) {
			$bbpress_helper = new Bbpress_Pro_Helpers();
		} else {
			$bbpress_helper = Automator()->helpers->recipe->bbpress->options->pro;
		}
		return Automator()->utilities->keep_order_of_options(
			array(
				'options' => array(
					$bbpress_helper->list_bbpress_topics( null, $this->trigger_meta, true ),
				),
			)
		);
	}

	/**
	 * Validation function when the trigger action is hit
	 *
	 * @param $topic_id
	 * @param $forum_id
	 * @param $anonymous_data
	 * @param $topic_author
	 */
	public function bbp_guest_replies_to_topic( $reply_id, $topic_id, $forum_id ) {
		if ( 0 === intval( $reply_id ) || empty( $reply_id ) ) {
			return;
		}

		$recipes            = Automator()->get->recipes_from_trigger_code( $this->trigger_code );
		$required_post      = Automator()->get->meta_from_recipes( $recipes, $this->trigger_meta );
		$matched_recipe_ids = array();

		//Add where option is set to Any post / specific post
		foreach ( $recipes as $recipe_id => $recipe ) {
			foreach ( $recipe['triggers'] as $trigger ) {
				$trigger_id = $trigger['ID'];
				if ( intval( '-1' ) === intval( $required_post[ $recipe_id ][ $trigger_id ] ) ||
					 $required_post[ $recipe_id ][ $trigger_id ] == $topic_id ) {
					$matched_recipe_ids[] = array(
						'recipe_id'  => $recipe_id,
						'trigger_id' => $trigger_id,
					);
				}
			}
		}

		//	If recipe matches
		if ( ! empty( $matched_recipe_ids ) ) {
			foreach ( $matched_recipe_ids as $matched_recipe_id ) {
				$user_id = get_current_user_id();

				$pass_args = array(
					'code'             => $this->trigger_code,
					'meta'             => $this->trigger_meta,
					'user_id'          => $user_id,
					'recipe_to_match'  => $matched_recipe_id['recipe_id'],
					'trigger_to_match' => $matched_recipe_id['trigger_id'],
					'post_id'          => $topic_id,
				);
				$result    = Automator()->maybe_add_trigger_entry( $pass_args, false );

				if ( $result ) {
					foreach ( $result as $r ) {
						if ( true === $r['result'] ) {
							if ( isset( $r['args'] ) && isset( $r['args']['trigger_log_id'] ) ) {
								$trigger_meta = array(
									'trigger_id'     => (int) $r['args']['trigger_id'],
									'user_id'        => $user_id,
									'trigger_log_id' => $r['args']['trigger_log_id'],
									'run_number'     => $r['args']['run_number'],
								);

								// Guest email
								Automator()->db->token->save( 'BBTOPIC_GUEST_EMAIL', automator_filter_input( 'bbp_anonymous_email', INPUT_POST ), $trigger_meta );
								// Guest website
								Automator()->db->token->save( 'BBTOPIC_GUEST_WEBSITE', automator_filter_input( 'bbp_anonymous_website', INPUT_POST ), $trigger_meta );
								// Guest name
								Automator()->db->token->save( 'BBTOPIC_GUEST_NAME', automator_filter_input( 'bbp_anonymous_name', INPUT_POST ), $trigger_meta );
								// Reply text
								Automator()->db->token->save( 'REPLY_CONTENT', automator_filter_input( 'bbp_reply_content', INPUT_POST ), $trigger_meta );
								// Reply URL
								Automator()->db->token->save( 'REPLY_URL', maybe_serialize( bbp_get_reply_url( $reply_id ) ), $trigger_meta );
								// Reply ID
								Automator()->db->token->save( 'REPLY_ID', absint( $reply_id ), $trigger_meta );
								// Forum ID
								Automator()->db->token->save( 'BBTOPIC_FORUM_ID', absint( $forum_id ), $trigger_meta );
								// Forum title
								Automator()->db->token->save( 'BBTOPIC_FORUM_TITLE', get_post_field( 'post_title', $forum_id ), $trigger_meta );
								// Forum URL
								Automator()->db->token->save( 'BBTOPIC_FORUM_URL', get_the_permalink( $forum_id ), $trigger_meta );

							}
							Automator()->maybe_trigger_complete( $r['args'] );
						}
					}
				}
			}
		}
	}

}
