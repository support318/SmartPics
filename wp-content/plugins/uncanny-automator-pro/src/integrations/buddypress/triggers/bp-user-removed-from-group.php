<?php

namespace Uncanny_Automator_Pro;

use Uncanny_Automator\Recipe;

/**
 * Class BP_USER_REMOVED_FROM_GROUP
 *
 * @package Uncanny_Automator_Pro
 */
class BP_USER_REMOVED_FROM_GROUP {
	use Recipe\Triggers;

	protected $bp_tokens = null;

	public function __construct() {

		$this->maybe_update_trigger_actions();
		$this->bp_tokens = new Bp_Pro_Tokens( false );
		$this->setup_trigger();

		// Adding a middle hook here to resolve when an admin edits from backend.
		add_action( 'bp_rest_group_members_delete_item', array( $this, 'maybe_trigger_automator_groups_remove_member' ), 10, 5 );
	}

	/**
	 * Define and register the trigger by pushing it into the Automator object
	 */
	public function setup_trigger() {
		$this->set_integration( 'BP' );
		$this->set_trigger_code( 'BP_USER_REMOVED_FROM_GROUP' );
		$this->set_trigger_meta( 'BP_GROUPS' );
		$this->set_is_pro( true );
		$this->set_sentence(
		/* Translators: Trigger sentence - BuddyPress */
			sprintf( esc_attr_x( 'A user is removed from {{a group:%1$s}}', 'BuddyPress', 'uncanny-automator-pro' ), $this->get_trigger_meta() )
		);
		$this->set_readable_sentence( esc_html_x( 'A user is removed from {{a group}}', 'BuddyPress', 'uncanny-automator-pro' ) );
		$this->set_action_hook(
			array(
				'groups_remove_member',
				'automator_groups_remove_member',
			)
		);
		if ( null !== $this->bp_tokens ) {
			$this->set_tokens( ( new Bp_Pro_Tokens( false ) )->user_group_tokens() );
		}
		$this->set_options_callback( array( $this, 'load_options' ) );
		$this->set_action_args_count( 2 );
		$this->register_trigger();
	}

	/**
	 * Load options
	 *
	 * @return array
	 */
	public function load_options() {

		return Automator()->utilities->keep_order_of_options(
			array(
				'options' => array(
					Automator()->helpers->recipe->buddypress->options->all_buddypress_groups(
						null,
						$this->get_trigger_meta(),
						array(
							'uo_include_any'  => true,
							'relevant_tokens' => array(),
							'status'          => array( 'public', 'private', 'hidden' ),
						)
					),
				),
			)
		);
	}

	/**
	 * @param ...$args
	 *
	 * @return bool
	 */
	public function validate_trigger( ...$args ) {

		list( $group_id, $user_id ) = $args[0];

		if ( get_user_by( 'ID', absint( $user_id ) ) ) {
			return true;
		}

		return false;

	}

	/**
	 * @param $data
	 *
	 * @return void
	 */
	public function prepare_to_run( $data ) {

		list( $group_id, $user_id ) = $data;

		// Set the user to complete with the one we are editing instead of current login user.
		if ( get_user_by( 'ID', absint( $user_id ) ) ) {
			$this->set_user_id( absint( $user_id ) );
		}

		$this->set_conditional_trigger( true );

	}

	/**
	 * @param $args
	 *
	 * @return mixed
	 */
	public function validate_conditions( $args ) {

		list( $group_id, $user_id ) = $args;

		return $this->find_all( $this->trigger_recipes() )
					->where( array( $this->get_trigger_meta() ) )
					->match( array( absint( $group_id ) ) )
					->format( array( 'trim' ) )
					->get();

	}

	/**
	 * Method parse_additional_tokens.
	 *
	 * @param $parsed
	 * @param $args
	 * @param $trigger
	 *
	 * @return array
	 */
	public function parse_additional_tokens( $parsed, $args, $trigger ) {
		return $this->bp_tokens->hydrate_user_group_tokens( $parsed, $args, $trigger );
	}

	/**
	 * Maybe update the meta_value for the add_action meta_key
	 *
	 * @return void
	 */
	public function maybe_update_trigger_actions() {

		// Check updated flag.
		$check_updated = automator_pro_get_option( 'automator_bp_user_removed_from_group_updated', false );
		if ( ! $check_updated ) {
			global $wpdb;
			// Collect post IDs for the trigger.
			$post_ids = $wpdb->get_col( "SELECT post_id FROM {$wpdb->postmeta} WHERE meta_key = 'code' AND meta_value = 'BP_USER_REMOVED_FROM_GROUP'" );
			if ( ! empty( $post_ids ) ) {
				$post_ids_string = implode( ',', $post_ids );
				// Update the add_action meta_key for the trigger.
				$wpdb->query(
					$wpdb->prepare(
						"UPDATE {$wpdb->postmeta} SET meta_value = %s WHERE meta_key = 'add_action' AND post_id IN ({$post_ids_string})",
						serialize(
							array(
								'groups_remove_member',
								'automator_groups_remove_member',
							)
						)
					)
				);
			}
			// Update the flag.
			automator_pro_update_option( 'automator_bp_user_removed_from_group_updated', 1 );
		}
	}

	/**
	 * Fires after a group member is deleted via the REST API.
	 *
	 * @since 5.0.0
	 *
	 * @param WP_User          $user     The updated member.
	 * @param BP_Groups_Member $member   The group member object.
	 * @param BP_Groups_Group  $group    The group object.
	 * @param WP_REST_Response $response The response data.
	 * @param WP_REST_Request  $request  The request sent to the API.
	 */
	public function maybe_trigger_automator_groups_remove_member( $user, $member, $group, $response, $request ) {

		// Bail if either action has already been triggered.
		if ( did_action( 'automator_groups_remove_member' ) || did_action( 'groups_remove_member' ) ) {
			return;
		}

		do_action( 'automator_groups_remove_member', $group->id, $user->ID );

	}

}
