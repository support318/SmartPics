<?php
namespace Uncanny_Automator_Pro\Integrations\Fluent_Community;

use Uncanny_Automator\Recipe\Action;
use FluentCommunity\App\Models\Feed;
use FluentCommunity\App\Models\Space;
use FluentCommunity\App\Services\FeedsHelper;
use FluentCommunity\App\Models\User;
use FluentCommunity\App\Services\Helper;

class FLUENTCOMMUNITY_ADD_POST_TO_SPACE extends Action {

	protected $prefix = 'FLUENTCOMMUNITY_ADD_POST_TO_SPACE';

	protected $helpers;

	/**
	 * Setup the action
	 */
	protected function setup_action() {
		$this->helpers = array_shift( $this->dependencies );

		$this->set_integration( 'FLUENT_COMMUNITY' );
		$this->set_action_code( $this->prefix . '_CODE' );
		$this->set_action_meta( $this->prefix . '_META' );
		$this->set_is_pro( true );
		$this->set_requires_user( true );

		$this->set_sentence(
			sprintf(
			// translators: %1$s: The post title, %2$s: The space title
				esc_attr_x( 'Add {{a post:%1$s}} to {{a space:%2$s}}', 'FluentCommunity', 'uncanny-automator' ),
				'POST_TITLE:' . $this->get_action_meta(),
				$this->get_action_meta() . ':' . $this->get_action_meta(),
			)
		);
		$this->set_readable_sentence( esc_attr_x( 'Add {{a post}} to {{a space}}', 'FluentCommunity', 'uncanny-automator' ) );
	}
	/**
	 * Options.
	 *
	 * @return mixed
	 */
	public function options() {
		return array(
			array(
				'option_code'           => $this->get_action_meta(),
				'input_type'            => 'select',
				'label'                 => esc_attr_x( 'Space', 'FluentCommunity', 'uncanny-automator' ),
				'required'              => true,
				'options'               => $this->helpers->all_spaces( false ),
				'supports_custom_value' => false,
			),
			array(
				'option_code' => 'POST_TITLE',
				'input_type'  => 'text',
				'label'       => esc_attr_x( 'Post title', 'FluentCommunity', 'uncanny-automator' ),
				'required'    => true,
			),
			array(
				'option_code' => 'POST_CONTENT',
				'input_type'  => 'textarea',
				'label'       => esc_attr_x( 'Post content', 'FluentCommunity', 'uncanny-automator' ),
				'required'    => true,
			),
		);
	}
	/**
	 * Process action.
	 *
	 * @param mixed $user_id The user ID.
	 * @param mixed $action_data The data.
	 * @param mixed $recipe_id The ID.
	 * @param mixed $args The arguments.
	 * @param mixed $parsed The parsed.
	 * @return mixed
	 */
	protected function process_action( $user_id, $action_data, $recipe_id, $args, $parsed ) {
		$space_id     = absint( $parsed[ $this->get_action_meta() ] );
		$post_title   = sanitize_text_field( $parsed['POST_TITLE'] ?? '' );
		$post_content = sanitize_textarea_field( $parsed['POST_CONTENT'] ?? '' );

		if ( ! $space_id || ! $post_title || ! $post_content ) {
			$this->add_log_error( esc_attr_x( 'Missing required data.', 'FluentCommunity', 'uncanny-automator' ) );
			return false;
		}

		$feed = \FluentCommunity\App\Services\FeedsHelper::createFeed(
			array(
				'user_id'  => $user_id,
				'space_id' => $space_id,
				'title'    => $post_title,
				'message'  => $post_content,
			)
		);

		if ( is_wp_error( $feed ) ) {
			$this->add_log_error( $feed->get_error_message() );
			return false;
		}

		$this->hydrate_tokens(
			array(
				'POST_ID'      => $feed->id,
				'POST_TITLE'   => $feed->title,
				'POST_CONTENT' => $feed->message,
				'POST_URL'     => $feed->getPermalink(),
				'SPACE_ID'     => $feed->space_id,
				'SPACE_TITLE'  => $feed->space ? $feed->space->title : '',
			)
		);

		return true;
	}

	/**
	 * Define tokens.
	 *
	 * @return mixed
	 */
	public function define_tokens() {
		return array(
			'POST_ID'      => array(
				'name' => esc_html_x( 'Post ID', 'Fluent Community', 'uncanny-automator' ),
				'type' => 'int',
			),
			'POST_TITLE'   => array(
				'name' => esc_html_x( 'Post title', 'Fluent Community', 'uncanny-automator' ),
				'type' => 'text',
			),
			'POST_CONTENT' => array(
				'name' => esc_html_x( 'Post content', 'Fluent Community', 'uncanny-automator' ),
				'type' => 'text',
			),
			'POST_URL'     => array(
				'name' => esc_html_x( 'Post URL', 'Fluent Community', 'uncanny-automator' ),
				'type' => 'url',
			),
			'SPACE_ID'     => array(
				'name' => esc_html_x( 'Space ID', 'Fluent Community', 'uncanny-automator' ),
				'type' => 'int',
			),
			'SPACE_TITLE'  => array(
				'name' => esc_html_x( 'Space name', 'Fluent Community', 'uncanny-automator' ),
				'type' => 'text',
			),
		);
	}
}
