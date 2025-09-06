<?php

namespace Uncanny_Automator_Pro;

use Uncanny_Automator\Bp_Tokens;

/**
 * Class Bp_Tokens
 *
 * @package Uncanny_Automator_Pro
 */
class Bp_Pro_Tokens extends Bp_Tokens {


	/**
	 * Integration code
	 *
	 * @var string
	 */
	public static $integration = 'BP';

	public function __construct( $load_action_hook = true ) {
		if ( true === $load_action_hook ) {
			add_filter( 'automator_maybe_trigger_bp_tokens', array( $this, 'bp_possible_tokens_pro' ), 20, 2 );
			add_filter( 'automator_maybe_parse_token', array( $this, 'parse_bp_pro_token' ), 20, 6 );
		}

	}

	/**
	 * Only load this integration and its triggers and actions if the related
	 * plugin is active
	 *
	 * @param $status
	 * @param $code
	 *
	 * @return bool
	 */
	public function plugin_active( $status, $code ) {
		if ( self::$integration === $code ) {
			if ( class_exists( 'BuddyPress' ) ) {
				$status = true;
			} else {
				$status = false;
			}
		}

		return $status;
	}

	/**
	 * @param $value
	 * @param $pieces
	 * @param $recipe_id
	 * @param $trigger_data
	 *
	 * @return mixed
	 */
	public function parse_bp_pro_token( $value, $pieces, $recipe_id, $trigger_data, $user_id, $replace_args ) {
		if ( $pieces ) {
			if ( in_array( 'BPGROUPS', $pieces ) ) {
				// Get Group id from meta log
				$group_id = $this->get_meta_data_from_trigger_meta( $user_id, 'BPGROUPS', $replace_args['trigger_id'], $replace_args['trigger_log_id'] );
				if ( $group_id ) {
					$group = groups_get_group( $group_id );
					if ( isset( $group->name ) ) {
						$value = $group->name;
					}
				}
			} elseif ( in_array( 'BPGROUPS_ID', $pieces ) ) {
				// Get Group id from meta log
				$group_id = $this->get_meta_data_from_trigger_meta( $user_id, 'BPGROUPS', $replace_args['trigger_id'], $replace_args['trigger_log_id'] );
				if ( $group_id ) {
					$value = $group_id;
				}
			}
			if ( in_array( 'USER_PROFILE_URL', $pieces ) || in_array( 'MANAGE_GROUP_REQUESTS_URL', $pieces ) ) {
				// Get Group id from meta log
				$value = $this->get_meta_data_from_trigger_meta( $user_id, $pieces[2], $replace_args['trigger_id'], $replace_args['trigger_log_id'] );
				if ( $value ) {
					$value = maybe_unserialize( $value );
				}
			}
			if ( in_array( 'BPUSERREGISTERWITHFIELD', $pieces ) && ( in_array( 'BPFIELD', $pieces ) || in_array( 'SUBVALUE', $pieces ) ) ) {
				if ( $trigger_data ) {
					foreach ( $trigger_data as $trigger ) {
						if ( $pieces[2] === 'SUBVALUE' && array_key_exists( $pieces[2], $trigger['meta'] ) ) {
							$value = $trigger['meta'][ $pieces[2] ];
						}
						if ( $pieces[2] === 'BPFIELD' && array_key_exists( $pieces[2], $trigger['meta'] ) ) {
							$value = $trigger['meta']['BPFIELD_readable'];
						}
					}
				}
			}
		}

		return $value;
	}

	/**
	 * @param $user_id
	 * @param $meta_key
	 * @param $trigger_id
	 * @param $trigger_log_id
	 *
	 * @return mixed|string
	 */
	public function get_meta_data_from_trigger_meta( $user_id, $meta_key, $trigger_id, $trigger_log_id ) {
		global $wpdb;
		if ( empty( $meta_key ) || empty( $trigger_id ) || empty( $trigger_log_id ) ) {
			return '';
		}

		$meta_value = $wpdb->get_var( $wpdb->prepare( "SELECT meta_value FROM {$wpdb->prefix}uap_trigger_log_meta WHERE user_id = %d AND meta_key = %s AND automator_trigger_id = %d AND automator_trigger_log_id = %d ORDER BY ID DESC LIMIT 0,1", $user_id, $meta_key, $trigger_id, $trigger_log_id ) );
		if ( ! empty( $meta_value ) ) {
			return maybe_unserialize( $meta_value );
		}

		return '';
	}

	/**
	 * @param array $tokens
	 * @param array $args
	 *
	 * @return array
	 */
	public function bp_possible_tokens_pro( $tokens = array(), $args = array() ) {
		$trigger_integration = $args['integration'];
		$trigger_meta        = $args['meta'];
		$fields              = array();
		if ( isset( $args['triggers_meta']['code'] ) && 'BPPOSTGROUPACTIVITY' === $args['triggers_meta']['code'] ) {

			$fields[] = array(
				'tokenId'         => 'BPGROUPS_ID',
				'tokenName'       => __( 'Group ID', 'uncanny-automator' ),
				'tokenType'       => 'text',
				'tokenIdentifier' => 'BPUSERACTIVITY',
			);
			$fields[] = array(
				'tokenId'         => 'ACTIVITY_ID',
				'tokenName'       => __( 'Activity ID', 'uncanny-automator' ),
				'tokenType'       => 'text',
				'tokenIdentifier' => 'BPUSERACTIVITY',
			);
			$fields[] = array(
				'tokenId'         => 'ACTIVITY_CONTENT',
				'tokenName'       => __( 'Activity content', 'uncanny-automator' ),
				'tokenType'       => 'text',
				'tokenIdentifier' => 'BPUSERACTIVITY',
			);
			$fields[] = array(
				'tokenId'         => 'ACTIVITY_URL',
				'tokenName'       => __( 'Activity URL', 'uncanny-automator' ),
				'tokenType'       => 'text',
				'tokenIdentifier' => 'BPUSERACTIVITY',
			);
			$fields[] = array(
				'tokenId'         => 'ACTIVITY_STREAM_URL',
				'tokenName'       => __( 'Activity stream URL', 'uncanny-automator' ),
				'tokenType'       => 'text',
				'tokenIdentifier' => 'BPUSERACTIVITY',
			);
		}
		$tokens = array_merge( $tokens, $fields );

		return $tokens;
	}

	/**
	 * @return array[]
	 */
	public function user_activity_tokens() {

		return array(
			'AVATAR_URL'          => array(
				'name' => __( 'Avatar URL', 'uncanny-automator-pro' ),
				'type' => 'url',
			),
			'NAME'                => array(
				'name' => __( 'Display name', 'uncanny-automator-pro' ),
				'type' => 'text',
			),
			'FIRST_NAME'          => array(
				'name' => __( 'First name', 'uncanny-automator-pro' ),
				'type' => 'text',
			),
			'LAST_NAME'           => array(
				'name' => __( 'Last name', 'uncanny-automator-pro' ),
				'type' => 'text',
			),
			'NICKNAME'            => array(
				'name' => __( 'Nickname', 'uncanny-automator-pro' ),
				'type' => 'text',
			),
			'ACTIVITY_ID'         => array(
				'name' => __( 'Activity ID', 'uncanny-automator-pro' ),
				'type' => 'int',
			),
			'ACTIVITY_URL'        => array(
				'name' => __( 'Activity URL', 'uncanny-automator-pro' ),
				'type' => 'url',
			),
			'ACTIVITY_STREAM_URL' => array(
				'name' => __( 'Activity stream URL', 'uncanny-automator-pro' ),
				'type' => 'url',
			),
			'ACTIVITY_CONTENT'    => array(
				'name' => __( 'Activity content', 'uncanny-automator-pro' ),
				'type' => 'text',
			),
		);
	}

	/**
	 * @return array[]
	 */
	public function user_friendship_tokens() {

		return array(
			'AVATAR_URL'         => array(
				'name' => __( 'Avatar URL', 'uncanny-automator-pro' ),
				'type' => 'url',
			),
			'NAME'               => array(
				'name' => __( 'First name', 'uncanny-automator-pro' ),
				'type' => 'text',
			),
			'LAST_NAME'          => array(
				'name' => __( 'Last name', 'uncanny-automator-pro' ),
				'type' => 'text',
			),
			'NICKNAME'           => array(
				'name' => __( 'Nickname', 'uncanny-automator-pro' ),
				'type' => 'text',
			),
//			'USER_ID'            => array(
//				'name' => __( 'User ID', 'uncanny-automator-pro' ),
//				'type' => 'int',
//			),
			'FRIENDS_AVATAR_URL' => array(
				'name' => __( "Friend's avatar URL", 'uncanny-automator-pro' ),
				'type' => 'url',
			),
			'FRIENDS_NAME'       => array(
				'name' => __( "Friend's display name", 'uncanny-automator-pro' ),
				'type' => 'text',
			),
			'FRIENDS_FIRST_NAME' => array(
				'name' => __( "Friend's first name", 'uncanny-automator-pro' ),
				'type' => 'text',
			),
			'FRIENDS_LAST_NAME'  => array(
				'name' => __( "Friend's last name", 'uncanny-automator-pro' ),
				'type' => 'text',
			),
			'FRIENDS_NICKNAME'   => array(
				'name' => __( "Friend's nickname", 'uncanny-automator-pro' ),
				'type' => 'text',
			),
			'FRIENDS_USER_ID'    => array(
				'name' => __( "Friend's user ID", 'uncanny-automator-pro' ),
				'type' => 'int',
			),
		);
	}

	/**
	 * @param $code
	 *
	 * @return array[]
	 */
	public function user_group_tokens( $code = null ) {
		$group_token_code  = 'GROUP_NAME';
		$group_token_label = 'Group name';
		if ( 'BP_REQUEST_TO_JOIN_PRIVATE_GROUP' === $code ) {
			$group_token_code = 'PRIVATE_GROUP_NAME';
		}
		if ( 'BP_USER_CREATES_GROUP' === $code ) {
			$group_token_code = 'PUBLIC_GROUP';
		}

		return array(
			$group_token_code => array(
				'name' => __( 'Group name', 'uncanny-automator-pro' ),
				'type' => 'text',
			),
			'GROUP_TYPE'      => array(
				'name' => __( 'Group type title', 'uncanny-automator-pro' ),
				'type' => 'text',
			),
			'AVATAR_URL'      => array(
				'name' => __( 'Avatar URL', 'uncanny-automator-pro' ),
				'type' => 'text',
			),
			'NAME'            => array(
				'name' => __( 'Display name', 'uncanny-automator-pro' ),
				'type' => 'text',
			),
			'FIRST_NAME'      => array(
				'name' => __( 'First name', 'uncanny-automator-pro' ),
				'type' => 'text',
			),
			'LAST_NAME'       => array(
				'name' => __( 'Last name', 'uncanny-automator-pro' ),
				'type' => 'text',
			),
			'NICKNAME'        => array(
				'name' => __( 'Nickname', 'uncanny-automator-pro' ),
				'type' => 'text',
			),

		);
	}

	/**
	 * Populate the token with actual values.
	 *
	 * @param $args
	 * @param $trigger
	 *
	 * @return array
	 */
	public function hydrate_user_friendship_tokens( $parsed, $args, $trigger ) {

		list( $id, $friendship_object ) = $args['trigger_args'];

		$init_user_id   = ( isset( $friendship_object->initiator_user_id ) ) ? absint( $friendship_object->initiator_user_id ) : 0;
		$friend_user_id = ( isset( $friendship_object->friend_user_id ) ) ? absint( $friendship_object->friend_user_id ) : 0;

		if ( 0 === $init_user_id || 0 === $friend_user_id ) {
			return $parsed;
		}
		$user   = get_user_by( 'ID', $init_user_id );
		$friend = get_user_by( 'ID', $friend_user_id );

		return $parsed + array(
			'AVATAR_URL'         => ( function_exists( 'get_avatar_url' ) ) ? get_avatar_url( $init_user_id ) : '',
			'NAME'               => $user->display_name,
			'FIRST_NAME'         => bp_get_user_meta( absint( $init_user_id ), 'first_name', true ),
			'LAST_NAME'          => bp_get_user_meta( absint( $init_user_id ), 'last_name', true ),
			'NICKNAME'           => $user->nickname,
			'USER_ID'            => $init_user_id,
			'FRIENDS_AVATAR_URL' => ( function_exists( 'get_avatar_url' ) ) ? get_avatar_url( $friend_user_id ) : '',
			'FRIENDS_NAME'       => $friend->display_name,
			'FRIENDS_FIRST_NAME' => bp_get_user_meta( absint( $friend_user_id ), 'first_name', true ),
			'FRIENDS_LAST_NAME'  => bp_get_user_meta( absint( $friend_user_id ), 'last_name', true ),
			'FRIENDS_NICKNAME'   => $friend->nickname,
			'FRIENDS_USER_ID'    => $friend_user_id,
		);
	}

	/**
	 * Populate the token with actual values.
	 *
	 * @param $args
	 * @param $trigger
	 *
	 * @return array
	 */
	public function hydrate_user_group_tokens( $parsed, $args, $trigger ) {
		list( $group_id, $user_id ) = $args['trigger_args'];
		$trigger_code               = $args['trigger_entry']['code'];
		$token_code                 = 'GROUP_NAME';

		if ( 'BP_USER_CREATES_GROUP' === $trigger_code ) {
			$token_code                        = 'PUBLIC_GROUP';
			list( $group_id, $member, $group ) = $args['trigger_args'];
			$user_id                           = absint( $group->creator_id );
		}

		if ( 'BP_REQUEST_TO_JOIN_PRIVATE_GROUP' === $trigger_code ) {
			$token_code                                   = 'PRIVATE_GROUP_NAME';
			list( $user_id, $admins, $group_id, $req_id ) = $args['trigger_args'];
		}

		if ( empty( $group_id ) && ! isset( $user_id ) ) {
			return $parsed;
		}

		$user  = get_user_by( 'ID', absint( $user_id ) );
		$group = groups_get_group( absint( $group_id ) );

		automator_log( array( $trigger_code, $user_id, $user, $group_id ), 'checking', true, 'bp-tokens' );

		return $parsed + array(
			$token_code  => $group->name,
			'GROUP_TYPE' => ( function_exists( 'bp_groups_get_group_type' ) ) ? ucfirst( bp_groups_get_group_type( absint( $group_id ), true ) ) : '',
			'AVATAR_URL' => ( function_exists( 'get_avatar_url' ) ) ? get_avatar_url( absint( $user_id ) ) : '',
			'NAME'       => $user->display_name,
			'FIRST_NAME' => bp_get_user_meta( absint( $user_id ), 'first_name', true ),
			'LAST_NAME'  => bp_get_user_meta( absint( $user_id ), 'last_name', true ),
			'NICKNAME'   => $user->nickname,
		);
	}

	/**
	 * Populate the token with actual values.
	 *
	 * @param $args
	 * @param $trigger
	 *
	 * @return array
	 */
	public function hydrate_user_activity_tokens( $parsed, $args, $trigger ) {

		list( $comment_id, $activity ) = $args['trigger_args'];

		if ( empty( $activity ) && ! isset( $activity['user_id'] ) ) {
			return $parsed;
		}

		if ( 0 === $activity['user_id'] ) {
			return $parsed;
		}
		$user = get_user_by( 'ID', absint( $activity['user_id'] ) );

		return $parsed + array(
			'BP_ACTIVITY_STREAM'  => absint( $activity['user_id'] ),
			'AVATAR_URL'          => ( function_exists( 'get_avatar_url' ) ) ? get_avatar_url( absint( $activity['user_id'] ) ) : '',
			'NAME'                => $user->display_name,
			'FIRST_NAME'          => bp_get_user_meta( absint( $activity['user_id'] ), 'first_name', true ),
			'LAST_NAME'           => bp_get_user_meta( absint( $activity['user_id'] ), 'last_name', true ),
			'NICKNAME'            => $user->nickname,
			'ACTIVITY_ID'         => $activity['activity_id'],
			'ACTIVITY_URL'        => bp_activity_get_permalink( absint( $activity['activity_id'] ) ),
			'ACTIVITY_STREAM_URL' => bp_core_get_user_domain( absint( $activity['user_id'] ) ) . 'activity/' . absint( $activity['activity_id'] ),
			'ACTIVITY_CONTENT'    => $activity['content'],
		);
	}

	/**
	 * @return array[]
	 */
	public function user_forum_topic_tokens() {

		return array(
			'TOPIC_TITLE' => array(
				'name' => __( 'Topic title', 'uncanny-automator-pro' ),
				'type' => 'text',
			),
			'TOPIC_URL'   => array(
				'name' => __( 'Topic URL', 'uncanny-automator-pro' ),
				'type' => 'url',
			),
			'REPLY_URL'   => array(
				'name' => __( 'Reply URL', 'uncanny-automator-pro' ),
				'type' => 'url',
			),
			'AVATAR_URL'  => array(
				'name' => __( 'Avatar URL', 'uncanny-automator-pro' ),
				'type' => 'url',
			),
			'NAME'        => array(
				'name' => __( 'Display name', 'uncanny-automator-pro' ),
				'type' => 'text',
			),
			'FIRST_NAME'  => array(
				'name' => __( 'First name', 'uncanny-automator-pro' ),
				'type' => 'text',
			),
			'LAST_NAME'   => array(
				'name' => __( 'Last name', 'uncanny-automator-pro' ),
				'type' => 'text',
			),
			'NICKNAME'    => array(
				'name' => __( 'Nickname', 'uncanny-automator-pro' ),
				'type' => 'text',
			),

		);
	}

	/**
	 * Populate the token with actual values.
	 *
	 * @param $args
	 * @param $trigger
	 *
	 * @return array
	 */
	public function hydrate_user_forum_topic_tokens( $parsed, $args, $trigger ) {

		list( $reply_id, $topic_id, $forum_id, $anonymous_data, $reply_author_id ) = $args['trigger_args'];

		if ( empty( $reply_id ) && ! isset( $reply_id ) ) {
			return $parsed;
		}

		$user = get_user_by( 'ID', absint( $reply_author_id ) );

		return $parsed + array(
			'BP_FORUMS'     => bbp_get_forum_title( $forum_id ),
			'BP_FORUMS_ID'  => $forum_id,
			'BP_FORUMS_URL' => bbp_get_forum_permalink( $forum_id ),
			'TOPIC_TITLE'   => bbp_get_topic_title( $topic_id ),
			'TOPIC_URL'     => bbp_get_topic_permalink( $topic_id ),
			'REPLY_URL'     => bbp_get_reply_url( $topic_id ),
			'AVATAR_URL'    => ( function_exists( 'get_avatar_url' ) ) ? get_avatar_url( absint( $reply_author_id ) ) : '',
			'NAME'          => $user->display_name,
			'FIRST_NAME'    => bp_get_user_meta( absint( $reply_author_id ), 'first_name', true ),
			'LAST_NAME'     => bp_get_user_meta( absint( $reply_author_id ), 'last_name', true ),
			'NICKNAME'      => $user->nickname,
		);
	}
}
