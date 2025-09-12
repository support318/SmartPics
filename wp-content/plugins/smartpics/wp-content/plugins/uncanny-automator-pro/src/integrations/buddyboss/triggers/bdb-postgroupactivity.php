<?php

namespace Uncanny_Automator_Pro;

use BP_Media;

/**
 * Class BDB_POSTGROUPACTIVITY
 *
 * @package Uncanny_Automator_Pro
 */
class BDB_POSTGROUPACTIVITY {

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
		$this->trigger_code = 'BDBPOSTGROUPACTIVITY';
		$this->trigger_meta = 'BDBGROUPS';
		$this->define_trigger();
	}

	/**
	 * Define and register the trigger by pushing it into the Automator object
	 */
	public function define_trigger() {

		$trigger = array(
			'author'              => Automator()->get_author_name(),
			'support_link'        => Automator()->get_author_support_link( $this->trigger_code, 'integration/buddyboss/' ),
			'is_pro'              => true,
			'integration'         => self::$integration,
			'code'                => $this->trigger_code,
			/* translators: Logged-in trigger - BuddyBoss */
			'sentence'            => sprintf( esc_attr__( 'A user makes a post to the activity stream of {{a group:%1$s}}', 'uncanny-automator-pro' ), $this->trigger_meta ),
			/* translators: Logged-in trigger - BuddyBoss */
			'select_option_name'  => esc_attr__( 'A user makes a post to the activity stream of {{a group}}', 'uncanny-automator-pro' ),
			'action'              => 'bp_groups_posted_update',
			'priority'            => 10,
			'accepted_args'       => 4,
			'validation_function' => array(
				$this,
				'bp_activity_posted_update',
			),
			'options_callback'    => array( $this, 'load_options' ),
		);

		Automator()->register->trigger( $trigger );
	}

	/**
	 * @return array[]
	 */
	public function load_options() {

		$bp_group_args = array(
			'uo_include_any' => true,
			'uo_any_label'   => __( 'Any group', 'uncanny-automator-pro' ),
			'status'         => array( 'public', 'private', 'hidden' ),
		);

		return Automator()->utilities->keep_order_of_options(
			array(
				'options' => array(
					Automator()->helpers->recipe->buddyboss->options->all_buddyboss_groups(
						__( 'Group', 'uncanny-automator-pro' ),
						'BDBGROUPS',
						$bp_group_args
					),
				),
			)
		);
	}

	/**
	 * Validation function when the trigger action is hit
	 *
	 * @param $content
	 * @param $user_id
	 * @param $activity_id
	 */

	public function bp_activity_posted_update( $content, $user_id, $group_id, $activity_id ) {

		$recipes            = Automator()->get->recipes_from_trigger_code( $this->trigger_code );
		$required_users     = Automator()->get->meta_from_recipes( $recipes, $this->trigger_meta );
		$matched_recipe_ids = array();

		foreach ( $recipes as $recipe_id => $recipe ) {
			foreach ( $recipe['triggers'] as $trigger ) {
				$trigger_id = $trigger['ID'];
				if ( intval( '-1' ) === intval( $required_users[ $recipe_id ][ $trigger_id ] ) || intval( $group_id ) === intval( $required_users[ $recipe_id ][ $trigger_id ] ) ) {
					$matched_recipe_ids[] = array(
						'recipe_id'  => $recipe_id,
						'trigger_id' => $trigger_id,
					);
				}
			}
		}

		if ( ! empty( $matched_recipe_ids ) ) {
			foreach ( $matched_recipe_ids as $matched_recipe_id ) {
				$args = array(
					'code'             => $this->trigger_code,
					'meta'             => $this->trigger_meta,
					'user_id'          => $user_id,
					'recipe_to_match'  => $matched_recipe_id['recipe_id'],
					'trigger_to_match' => $matched_recipe_id['trigger_id'],
					'ignore_post_id'   => true,
				);

				$returns = Automator()->maybe_add_trigger_entry( $args, false );

				if ( $returns ) {
					foreach ( $returns as $result ) {
						if ( true === $result['result'] ) {

							$trigger_meta = array(
								'user_id'        => $user_id,
								'trigger_id'     => $result['args']['trigger_id'],
								'trigger_log_id' => $result['args']['trigger_log_id'],
								'run_number'     => $result['args']['run_number'],
							);

							// ACTIVITY_ID Token
							$trigger_meta['meta_key']   = 'ACTIVITY_ID';
							$trigger_meta['meta_value'] = $activity_id;
							Automator()->insert_trigger_meta( $trigger_meta );

							$group = groups_get_group( $group_id );
							// ACTIVITY_URL Token
							$trigger_meta['meta_key']   = 'ACTIVITY_URL';
							$trigger_meta['meta_value'] = bp_get_group_permalink( $group ) . 'activity';
							Automator()->insert_trigger_meta( $trigger_meta );

							// ACTIVITY_STREAM_URL Token
							$trigger_meta['meta_key']   = 'ACTIVITY_STREAM_URL';
							$trigger_meta['meta_value'] = bp_core_get_user_domain( $user_id ) . 'activity/' . $activity_id;
							Automator()->insert_trigger_meta( $trigger_meta );

							$medias = '';
							$medias = bp_activity_get_meta( $activity_id, 'bp_media_id', true );
							if ( ! isset( $medias ) || null === $medias || empty( $medias ) ) {
								$medias = bp_activity_get_meta( $activity_id, 'bp_media_ids', true );
							}

							if ( ! empty( $medias ) ) {
								global $wpdb;
								$bp         = buddypress();
								$select_att = $wpdb->get_results( "SELECT `attachment_id` from {$bp->media->table_name} WHERE id in (" . $medias . ')' );  // phpcs:ignore WordPress.DB.PreparedSQL.NotPrepared

								$m_index = 1;
								foreach ( $select_att as $media_item ) :
									// ACTIVITY_MEDIA_URL Token
									$trigger_meta['meta_key']   = 'ACTIVITY_MEDIA_URL_' . $m_index;
									$trigger_meta['meta_value'] = wp_get_attachment_url( $media_item->attachment_id );
									Automator()->insert_trigger_meta( $trigger_meta );
									$m_index ++;
								endforeach;
							}

							$videos = '';
							$videos = bp_activity_get_meta( $activity_id, 'bp_video_ids', true );

							if ( ! empty( $videos ) ) {
								global $wpdb;
								$bp         = buddypress();
								$select_att = $wpdb->get_results( "SELECT `attachment_id` from {$bp->media->table_name} WHERE id in (" . $videos . ')' );  // phpcs:ignore WordPress.DB.PreparedSQL.NotPrepared

								$m_index = 1;
								foreach ( $select_att as $video_item ) :
									// ACTIVITY_MEDIA_URL Token
									$trigger_meta['meta_key']   = 'ACTIVITY_VIDEO_URL_' . $m_index;
									$trigger_meta['meta_value'] = wp_get_attachment_url( $video_item->attachment_id );
									Automator()->insert_trigger_meta( $trigger_meta );
									$m_index ++;
								endforeach;
							}

							$documents = '';
							$documents = bp_activity_get_meta( $activity_id, 'bp_document_ids', true );

							if ( ! empty( $documents ) ) {
								global $wpdb;
								$bp         = buddypress();
								$select_att = $wpdb->get_results( "SELECT `attachment_id` from {$bp->document->table_name} WHERE id in (" . $documents . ')' );  // phpcs:ignore WordPress.DB.PreparedSQL.NotPrepared

								$m_index = 1;
								foreach ( $select_att as $document_item ) :
									// ACTIVITY_MEDIA_URL Token
									$trigger_meta['meta_key']   = 'ACTIVITY_DOCUMENT_URL_' . $m_index;
									$trigger_meta['meta_value'] = wp_get_attachment_url( $document_item->attachment_id );
									Automator()->insert_trigger_meta( $trigger_meta );
									$m_index ++;
								endforeach;
							}

							// ACTIVITY_CONTENT Token
							$trigger_meta['meta_key']   = 'ACTIVITY_CONTENT';
							$trigger_meta['meta_value'] = $content;
							Automator()->insert_trigger_meta( $trigger_meta );

							// GROUP ID Token
							$trigger_meta['meta_key']   = 'BDBGROUPS';
							$trigger_meta['meta_value'] = $group_id;
							Automator()->insert_trigger_meta( $trigger_meta );

							Automator()->maybe_trigger_complete( $result['args'] );
						}
					}
				}
			}
		}
	}

}
