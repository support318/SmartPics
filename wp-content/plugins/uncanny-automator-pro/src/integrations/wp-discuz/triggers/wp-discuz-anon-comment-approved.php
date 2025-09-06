<?php

namespace Uncanny_Automator_Pro\Integrations\Wp_Discuz;

/**
 * Class WP_DISCUZ_ANON_COMMENT_APPROVED
 *
 * @package Uncanny_Automator_Pro
 */
class WP_DISCUZ_ANON_COMMENT_APPROVED extends \Uncanny_Automator\Recipe\Trigger {

	protected $helpers;

	/**
	 * @return mixed|void
	 */
	protected function setup_trigger() {
		$this->helpers = array_shift( $this->dependencies );
		$this->set_integration( 'WPDISCUZ' );
		$this->set_trigger_code( 'WPD_ANON_COMMENT_APPROVED' );
		$this->set_trigger_meta( 'WPD_POST' );
		$this->set_is_pro( true );
		$this->set_trigger_type( 'anonymous' );
		$this->set_sentence( sprintf( esc_attr_x( "A guest comment on a user's {{post:%1\$s}} is approved", 'wpDiscuz', 'uncanny-automator' ), $this->get_trigger_meta() ) );
		$this->set_readable_sentence( esc_attr_x( "A guest comment on a user's {{post}} is approved", 'wpDiscuz', 'uncanny-automator' ) );
		$this->add_action( 'transition_comment_status', 10, 3 );
	}

	/**
	 * @return array
	 */
	public function options() {
		return array(
			array(
				'input_type'      => 'select',
				'option_code'     => $this->get_trigger_meta(),
				'label'           => _x( 'Post', 'wpDiscuz', 'uncanny-automator' ),
				'required'        => true,
				'options'         => $this->helpers->get_all_posts_options(),
				'relevant_tokens' => array(),
			),
		);
	}

	/**
	 * @return bool
	 */
	public function validate( $trigger, $hook_args ) {
		list( $new_status, $old_status, $comment ) = $hook_args;

		if ( $old_status === $new_status || $new_status !== 'approved' ) {
			return false;
		}

		if ( ! isset( $trigger['meta'][ $this->get_trigger_meta() ] ) ) {
			return false;
		}

		$selected_post_id = $trigger['meta'][ $this->get_trigger_meta() ];
		$this->set_user_id( get_post_field( 'post_author', (int) $comment->comment_post_ID ) );

		return ( intval( '-1' ) === intval( $selected_post_id ) ) || ( absint( $selected_post_id ) === absint( $comment->comment_post_ID ) );
	}

	/**
	 * define_tokens
	 *
	 * @param mixed $tokens
	 * @param mixed $trigger - options selected in the current recipe/trigger
	 *
	 * @return array
	 */
	public function define_tokens( $trigger, $tokens ) {
		$common_tokens = $this->helpers->wpDiscuz_common_tokens();

		return array_merge( $tokens, $common_tokens );
	}

	/**
	 * hydrate_tokens
	 *
	 * @param $trigger
	 * @param $hook_args
	 *
	 * @return array
	 */
	public function hydrate_tokens( $trigger, $hook_args ) {
		list( $new_status, $old_status, $comment ) = $hook_args;
		$author_id                                 = get_post_field( 'post_author', $comment->comment_post_ID );

		return $this->helpers->parse_common_token_values( $comment->comment_post_ID, $comment->comment_ID, $author_id );

	}
}
