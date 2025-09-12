<?php
namespace Uncanny_Automator_Pro\Integrations\Fluent_Community;

use Uncanny_Automator\Recipe\Trigger;

class FLUENTCOMMUNITY_USER_POST_COMMENTED extends Trigger {

	protected $prefix = 'FLUENTCOMMUNITY_USER_POST_COMMENTED';

	protected $helpers;
	/**
	 * Setup trigger.
	 */
	protected function setup_trigger() {
		$this->helpers = array_shift( $this->dependencies );

		$this->set_integration( 'FLUENT_COMMUNITY' );
		$this->set_trigger_code( $this->prefix . '_CODE' );
		$this->set_trigger_meta( $this->prefix . '_META' );
		$this->set_is_pro( true );

		$this->add_action( 'fluent_community/comment_added' );
		$this->set_action_args_count( 3 );

		$this->set_sentence(
			sprintf(
				// translators: %1$s: The space title
				esc_html_x( 'A user comments on a post in {{a space:%1$s}}', 'FluentCommunity', 'uncanny-automator' ),
				$this->get_trigger_meta()
			)
		);

		$this->set_readable_sentence(
			esc_html_x( 'A user comments on a post in {{a space}}', 'FluentCommunity', 'uncanny-automator' )
		);
	}
	/**
	 * Options.
	 *
	 * @return mixed
	 */
	public function options() {
		return array(
			array(
				'option_code'           => $this->get_trigger_meta(),
				'label'                 => esc_html_x( 'Space', 'FluentCommunity', 'uncanny-automator' ),
				'input_type'            => 'select',
				'required'              => true,
				'options'               => $this->helpers->all_spaces( true ),
				'supports_custom_value' => false,
				'relevant_tokens'       => array(),
			),
		);
	}
	/**
	 * Validate.
	 *
	 * @param mixed $trigger The trigger.
	 * @param mixed $hook_args The arguments.
	 * @return mixed
	 */
	public function validate( $trigger, $hook_args ) {
		if ( empty( $hook_args ) || ! is_array( $hook_args ) || count( $hook_args ) < 2 ) {
			return false;
		}

		list( $comment, $post ) = $hook_args;

		if ( ! is_object( $comment ) || ! is_object( $post ) ) {
			return false;
		}

		$user_id = isset( $comment->user_id ) ? absint( $comment->user_id ) : 0;
		if ( 0 === $user_id ) {
			return false;
		}

		$space_id = isset( $post->space_id ) ? intval( $post->space_id ) : 0;
		if ( 0 === $space_id ) {
			return false;
		}

		$this->set_user_id( $user_id );

		$selected_space = isset( $trigger['meta'][ $this->get_trigger_meta() ] )
			? intval( $trigger['meta'][ $this->get_trigger_meta() ] )
			: -1;

		if ( -1 === $selected_space || $selected_space === $space_id ) {
			return true;
		}

		return false;
	}


	/**
	 * Hydrate tokens.
	 *
	 * @param mixed $trigger The trigger.
	 * @param mixed $hook_args The arguments.
	 * @return mixed
	 */
	public function hydrate_tokens( $trigger, $hook_args ) {

		$comment = $hook_args[0] ?? null;
		$feed    = $hook_args[1] ?? null;
		//$mentionedUsers = $hook_args[2] ?? array();

		if ( empty( $comment ) || empty( $feed ) ) {
			return array();
		}

		$comment_url = method_exists( $feed, 'getPermalink' )
			? $feed->getPermalink() . '#comment-' . $comment->id
			: home_url( "/community/post/{$feed->id}#comment-{$comment->id}" );

		return array(
			'COMMENT_ID'      => $comment->id ?? '',
			'COMMENT_CONTENT' => $comment->message ?? '',
			'COMMENT_URL'     => $comment_url,
			'POST_ID'         => $feed->id ?? '',
			'POST_TITLE'      => $feed->title ?? '',
			'POST_CONTENT'    => $feed->message ?? '',
			'POST_URL'        => method_exists( $feed, 'getPermalink' ) ? $feed->getPermalink() : '',
			'SPACE_ID'        => $feed->space_id ?? '',
			'SPACE_NAME'      => $feed->space->title ?? '',
		);
	}
	/**
	 * Define tokens.
	 *
	 * @param mixed $trigger The trigger.
	 * @param mixed $tokens The destination.
	 * @return mixed
	 */
	public function define_tokens( $trigger, $tokens ) {
		return array(
			'COMMENT_ID'      => array(
				'name'      => esc_html_x( 'Comment ID', 'Fluent Community', 'uncanny-automator' ),
				'type'      => 'int',
				'tokenId'   => 'COMMENT_ID',
				'tokenName' => esc_html_x( 'Comment ID', 'Fluent Community', 'uncanny-automator' ),
			),
			'COMMENT_CONTENT' => array(
				'name'      => esc_html_x( 'Comment content', 'Fluent Community', 'uncanny-automator' ),
				'type'      => 'text',
				'tokenId'   => 'COMMENT_CONTENT',
				'tokenName' => esc_html_x( 'Comment content', 'Fluent Community', 'uncanny-automator' ),
			),
			'COMMENT_URL'     => array(
				'name'      => esc_html_x( 'Comment URL', 'Fluent Community', 'uncanny-automator' ),
				'type'      => 'url',
				'tokenId'   => 'COMMENT_URL',
				'tokenName' => esc_html_x( 'Comment URL', 'Fluent Community', 'uncanny-automator' ),
			),
			'POST_ID'         => array(
				'name'      => esc_html_x( 'Post ID', 'Fluent Community', 'uncanny-automator' ),
				'type'      => 'int',
				'tokenId'   => 'POST_ID',
				'tokenName' => esc_html_x( 'Post ID', 'Fluent Community', 'uncanny-automator' ),
			),
			'POST_TITLE'      => array(
				'name'      => esc_html_x( 'Post title', 'Fluent Community', 'uncanny-automator' ),
				'type'      => 'text',
				'tokenId'   => 'POST_TITLE',
				'tokenName' => esc_html_x( 'Post title', 'Fluent Community', 'uncanny-automator' ),
			),
			'POST_CONTENT'    => array(
				'name'      => esc_html_x( 'Post content', 'Fluent Community', 'uncanny-automator' ),
				'type'      => 'text',
				'tokenId'   => 'POST_CONTENT',
				'tokenName' => esc_html_x( 'Post content', 'Fluent Community', 'uncanny-automator' ),
			),
			'POST_URL'        => array(
				'name'      => esc_html_x( 'Post URL', 'Fluent Community', 'uncanny-automator' ),
				'type'      => 'url',
				'tokenId'   => 'POST_URL',
				'tokenName' => esc_html_x( 'Post URL', 'Fluent Community', 'uncanny-automator' ),
			),
			'SPACE_ID'        => array(
				'name'      => esc_html_x( 'Space ID', 'FluentCommunity', 'uncanny-automator' ),
				'type'      => 'int',
				'tokenId'   => 'SPACE_ID',
				'tokenName' => esc_html_x( 'Space ID', 'Fluent Community', 'uncanny-automator' ),
			),
			'SPACE_NAME'      => array(
				'name'      => esc_html_x( 'Space name', 'FluentCommunity', 'uncanny-automator' ),
				'type'      => 'text',
				'tokenId'   => 'SPACE_NAME',
				'tokenName' => esc_html_x( 'Space name', 'Fluent Community', 'uncanny-automator' ),
			),
		);
	}
}
