<?php
namespace Uncanny_Automator_Pro\Integrations\Fluent_Community;

use Uncanny_Automator\Recipe\Action;
use FluentCommunity\App\Models\Space;
use FluentCommunity\App\Models\SpaceUserPivot;
use FluentCommunity\App\Models\User;
use FluentCommunity\App\Services\ProfileHelper;
use FluentCommunity\App\Services\Helper;


/**
 * Class FLUENTCOMMUNITY_LEAVE_USER_SPACE
 */
class FLUENTCOMMUNITY_LEAVE_USER_SPACE extends Action {

	protected $prefix = 'FLUENTCOMMUNITY_LEAVE_USER_SPACE';

	protected $helpers;
	/**
	 * Setup action.
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
				/* translators: %1$s - Course */
				esc_html_x( 'Remove the user from {{a space:%1$s}}', 'FluentCommunity', 'uncanny-automator' ),
				$this->get_action_meta()
			)
		);

		$this->set_readable_sentence(
			esc_html_x( 'Remove the user from {{a space}}', 'FluentCommunity', 'uncanny-automator' )
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
				'option_code'           => $this->get_action_meta(),
				'label'                 => esc_html_x( 'Space', 'FluentCommunity', 'uncanny-automator' ),
				'input_type'            => 'select',
				'required'              => true,
				'options'               => $this->helpers->all_spaces( false ),
				'relevant_tokens'       => array(),
				'supports_custom_value' => false,
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
		$space_id = absint( $parsed[ $this->get_action_meta() ] );

		if ( ! $space_id || ! $user_id ) {
			throw new \Exception( esc_html_x( 'Missing space ID or user ID.', 'FluentCommunity', 'uncanny-automator' ) );
		}

		$space = Space::where( 'status', 'published' )->find( $space_id );

		if ( ! $space ) {
			throw new \Exception( esc_html_x( 'The specified space does not exist.', 'FluentCommunity', 'uncanny-automator' ) );
		}

		$user = User::find( $user_id );
		if ( ! $user ) {
			throw new \Exception( esc_html_x( 'User not found.', 'FluentCommunity', 'uncanny-automator' ) );
		}

		$membership = $space->getMembership( $user_id );

		if ( ! $membership ) {
			throw new \Exception( esc_html_x( 'The user is not a member of this space.', 'FluentCommunity', 'uncanny-automator' ) );
		}

		$removed = Helper::removeFromSpace( $space, $user_id, 'by_automation' );

		if ( is_wp_error( $removed ) ) {
			throw new \Exception( esc_html_x( 'Failed to remove the user from the space.', 'FluentCommunity', 'uncanny-automator' ) );
		}

		return true;
	}
}
