<?php
namespace Uncanny_Automator_Pro\Integrations\Fluent_Community;

use Uncanny_Automator\Recipe\Trigger;

/**
 * Class FLUENTCOMMUNITY_USER_POST_REACTED
 *
 * @package Uncanny_Automator
 */
class FLUENTCOMMUNITY_USER_POST_REACTED extends Trigger {

	protected $prefix = 'FLUENTCOMMUNITY_USER_POST_REACTED';

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

		$this->add_action( 'fluent_community/feed/react_added' );
		$this->set_action_args_count( 2 );

		$this->set_sentence(
			sprintf(
				// translators: %1$s: The space title
				esc_html_x( 'A user reacts to a post in {{a space:%1$s}}', 'FluentCommunity', 'uncanny-automator' ),
				$this->get_trigger_meta()
			)
		);

		$this->set_readable_sentence(
			esc_html_x( 'A user reacts to a post in {{a space}}', 'FluentCommunity', 'uncanny-automator' )
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
				'relevant_tokens'       => array(),
				'supports_custom_value' => false,
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
		list( $reaction, $post ) = $hook_args;
		$user_id                 = isset( $reaction->user_id ) ? absint( $reaction->user_id ) : 0;

		if ( ! $user_id || ! is_object( $post ) || empty( $post->space_id ) ) {
			return false;
		}

		$this->set_user_id( $user_id );

		$selected = isset( $trigger['meta'][ $this->get_trigger_meta() ] ) ? (int) $trigger['meta'][ $this->get_trigger_meta() ] : '-1';

		return ( intval( '-1' ) === intval( $selected ) || absint( $post->space_id ) === $selected );
	}

	/**
	 * Hydrate tokens.
	 *
	 * @param mixed $trigger The trigger.
	 * @param mixed $hook_args The arguments.
	 * @return mixed
	 */
	public function hydrate_tokens( $trigger, $hook_args ) {
		list( $reaction, $feed ) = $hook_args;

		return array(
			'REACTION_TYPE' => $reaction->type ?? '',
			'POST_ID'       => $feed->id,
			'POST_TITLE'    => $feed->title ?? '',
			'POST_CONTENT'  => $feed->message ?? '',
			'POST_URL'      => method_exists( $feed, 'getPermalink' ) ? $feed->getPermalink() : '',
			'SPACE_ID'      => $feed->space_id ?? '',
			'SPACE_NAME'    => $feed->space->title ?? '',
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
			'REACTION_TYPE' => array(
				'name'      => esc_html_x( 'Reaction type', 'FluentCommunity', 'uncanny-automator' ),
				'type'      => 'text',
				'tokenId'   => 'REACTION_TYPE',
				'tokenName' => esc_html_x( 'Reaction type', 'FluentCommunity', 'uncanny-automator' ),
			),
			'POST_ID'       => array(
				'name'      => esc_html_x( 'Post ID', 'FluentCommunity', 'uncanny-automator' ),
				'type'      => 'int',
				'tokenId'   => 'POST_ID',
				'tokenName' => esc_html_x( 'Post ID', 'FluentCommunity', 'uncanny-automator' ),
			),
			'POST_TITLE'    => array(
				'name'      => esc_html_x( 'Post title', 'FluentCommunity', 'uncanny-automator' ),
				'type'      => 'text',
				'tokenId'   => 'POST_TITLE',
				'tokenName' => esc_html_x( 'Post title', 'FluentCommunity', 'uncanny-automator' ),
			),
			'POST_CONTENT'  => array(
				'name'      => esc_html_x( 'Post content', 'FluentCommunity', 'uncanny-automator' ),
				'type'      => 'text',
				'tokenId'   => 'POST_CONTENT',
				'tokenName' => esc_html_x( 'Post content', 'FluentCommunity', 'uncanny-automator' ),
			),
			'POST_URL'      => array(
				'name'      => esc_html_x( 'Post URL', 'FluentCommunity', 'uncanny-automator' ),
				'type'      => 'url',
				'tokenId'   => 'POST_URL',
				'tokenName' => esc_html_x( 'Post URL', 'FluentCommunity', 'uncanny-automator' ),
			),
			'SPACE_ID'      => array(
				'name'      => esc_html_x( 'Space ID', 'FluentCommunity', 'uncanny-automator' ),
				'type'      => 'int',
				'tokenId'   => 'SPACE_ID',
				'tokenName' => esc_html_x( 'Space ID', 'FluentCommunity', 'uncanny-automator' ),
			),
			'SPACE_NAME'    => array(
				'name'      => esc_html_x( 'Space name', 'FluentCommunity', 'uncanny-automator' ),
				'type'      => 'text',
				'tokenId'   => 'SPACE_NAME',
				'tokenName' => esc_html_x( 'Space name', 'FluentCommunity', 'uncanny-automator' ),
			),
		);
	}
}
