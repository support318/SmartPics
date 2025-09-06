<?php

namespace Uncanny_Automator_Pro;

use Uncanny_Automator\Bdb_Tokens;

/**
 * Class Bdb_Tokens
 *
 * @package Uncanny_Automator_Pro
 */
class Bdb_Pro_Tokens extends Bdb_Tokens {

	/**
	 * Integration code
	 *
	 * @var string
	 */
	public static $integration = 'BDB';

	/**
	 * @param $load_action_hook
	 */
	public function __construct( $load_action_hook = true ) {

		if ( true === $load_action_hook ) {
			add_filter(
				'automator_maybe_trigger_bdb_tokens',
				array(
					$this,
					'bdb_possible_tokens_pro',
				),
				20,
				2
			);

			add_filter(
				'automator_maybe_trigger_bdb_tokens',
				array(
					$this,
					'bdb_remove_user_tokens_for_guest',
				),
				40,
				2
			);

			add_filter(
				'automator_maybe_trigger_bdb_bdbaccessprivategroup_tokens',
				array(
					$this,
					'bdb_private_group_tokens_pro',
				),
				20,
				2
			);

			add_filter(
				'automator_maybe_trigger_bdb_bdbjoingroup_tokens',
				array(
					$this,
					'bdb_join_group_tokens_pro',
				),
				20,
				2
			);

			add_filter(
				'automator_maybe_trigger_bdb_bdbjoinhiddengroup_tokens',
				array(
					$this,
					'bdb_join_group_tokens_pro',
				),
				20,
				2
			);

			add_filter(
				'automator_maybe_trigger_bdb_bdbjoinprivategroup_tokens',
				array(
					$this,
					'bdb_join_group_tokens_pro',
				),
				20,
				2
			);

			add_filter(
				'automator_maybe_trigger_bdb_bdbleavegroup_tokens',
				array(
					$this,
					'bdb_join_group_tokens_pro',
				),
				20,
				2
			);

			add_filter(
				'automator_maybe_parse_token',
				array(
					$this,
					'parse_bp_pro_token',
				),
				20,
				6
			);
		}

	}


	/**
	 * @return array[]
	 */
	public function common_tokens() {

		return array(
			'FORUM_ID'          => array(
				'name' => __( 'Forum ID', 'uncanny-automator-pro' ),
			),
			'FORUM_TITLE'       => array(
				'name' => __( 'Forum title', 'uncanny-automator-pro' ),
			),
			'FORUM_DESCRIPTION' => array(
				'name' => __( 'Forum description', 'uncanny-automator-pro' ),
			),
			'FORUM_TYPE'        => array(
				'name' => __( 'Forum type', 'uncanny-automator-pro' ),
			),
			'FORUM_STATUS'      => array(
				'name' => __( 'Forum status', 'uncanny-automator-pro' ),
			),
			'FORUM_VISIBILITY'  => array(
				'name' => __( 'Forum visibility', 'uncanny-automator-pro' ),
			),
			'PARENT_FORUM_ID'   => array(
				'name' => __( 'Parent forum ID', 'uncanny-automator-pro' ),
			),
			'PARENT_FORUM'      => array(
				'name' => __( 'Parent forum', 'uncanny-automator-pro' ),
			),
		);
	}

	/**
	 * @return array[]
	 */
	public function send_msg_to_user_tokens() {

		return array(
			'AVATAR_URL'     => array(
				'name' => __( 'Avatar URL', 'uncanny-automator-pro' ),
			),
			'NAME'           => array(
				'name' => __( 'Name', 'uncanny-automator-pro' ),
			),
			'LAST_NAME'      => array(
				'name' => __( 'Last name', 'uncanny-automator-pro' ),
			),
			'NICKNAME'       => array(
				'name' => __( 'Nickname', 'uncanny-automator-pro' ),
			),
			'RCV_AVATAR_URL' => array(
				'name' => __( "Receiver's avatar URL", 'uncanny-automator-pro' ),
			),
			'RCV_NAME'       => array(
				'name' => __( "Receiver's name", 'uncanny-automator-pro' ),
			),
			'RCV_LAST_NAME'  => array(
				'name' => __( "Receiver's last name", 'uncanny-automator-pro' ),
			),
			'RCV_NICKNAME'   => array(
				'name' => __( "Receiver's nickname", 'uncanny-automator-pro' ),
			),
			'RCV_EMAIL'      => array(
				'name' => __( "Receiver's email", 'uncanny-automator-pro' ),
			),
		);
	}


	/**
	 * @return array[]
	 */
	public function receive_msg_to_user_tokens() {

		return array(
			'AVATAR_URL'      => array(
				'name' => __( 'Avatar URL', 'uncanny-automator-pro' ),
			),
			'NAME'            => array(
				'name' => __( 'Name', 'uncanny-automator-pro' ),
			),
			'LAST_NAME'       => array(
				'name' => __( 'Last name', 'uncanny-automator-pro' ),
			),
			'NICKNAME'        => array(
				'name' => __( 'Nickname', 'uncanny-automator-pro' ),
			),
			'SEND_AVATAR_URL' => array(
				'name' => __( "Sender's avatar URL", 'uncanny-automator-pro' ),
			),
			'SEND_NAME'       => array(
				'name' => __( "Sender's name", 'uncanny-automator-pro' ),
			),
			'SEND_LAST_NAME'  => array(
				'name' => __( "Sender's last name", 'uncanny-automator-pro' ),
			),
			'SEND_NICKNAME'   => array(
				'name' => __( "Sender's nickname", 'uncanny-automator-pro' ),
			),
			'SEND_EMAIL'      => array(
				'name' => __( "Sender's email", 'uncanny-automator-pro' ),
			),
		);
	}

	/**
	 * @return array[]
	 */
	public function banned_user_tokens() {

		return array(
			'GROUP_NAME' => array(
				'name' => __( 'Group name', 'uncanny-automator-pro' ),
			),
			'GROUP_TYPE' => array(
				'name' => __( 'Group type title', 'uncanny-automator-pro' ),
			),
			'AVATAR_URL' => array(
				'name'       => __( 'Avatar URL', 'uncanny-automator-pro' ),
				'token_type' => 'url',
			),
			'FIRST_NAME' => array(
				'name' => __( 'First name', 'uncanny-automator-pro' ),
			),
			'LAST_NAME'  => array(
				'name' => __( 'Last name', 'uncanny-automator-pro' ),
			),
			'NICKNAME'   => array(
				'name' => __( 'Nickname', 'uncanny-automator-pro' ),
			),
		);
	}


	/**
	 * Hydrate tokens method for ban user trigger.
	 *
	 * @param $parsed
	 * @param $args
	 * @param $trigger
	 *
	 * @return array
	 */
	public function banned_user_tokens_hydrate_tokens( $parsed, $args, $trigger ) {

		list( $group_id, $user_id ) = $args['trigger_args'];

		$user = get_user_by( 'ID', absint( $user_id ) );

		return $parsed + array(
			'GROUP_NAME' => Automator()->helpers->recipe->buddyboss->pro->bdb_get_group_title( absint( $group_id ) ),
			'GROUP_TYPE' => ( function_exists( 'bp_groups_get_group_type' ) ) ? bp_groups_get_group_type( absint( $group_id ) ) : '',
			'AVATAR_URL' => ( function_exists( 'get_avatar_url' ) ) ? get_avatar_url( absint( $user_id ) ) : '',
			'FIRST_NAME' => bp_get_user_meta( absint( $user_id ), 'first_name', true ),
			'LAST_NAME'  => bp_get_user_meta( absint( $user_id ), 'last_name', true ),
			'NICKNAME'   => $user->nickname,
		);

	}

	/**
	 * User tokens methods
	 *
	 * @return array[]
	 */
	public function user_tokens() {
		if ( ! is_admin() ) {
			return array();
		}
		$fields = array(
			'BDBMEMBER_ID'  => array(
				'name' => __( 'User ID', 'uncanny-automator-pro' ),
			),
			'BDBUSERAVATAR' => array(
				'name' => __( 'Avatar URL', 'uncanny-automator-pro' ),
			),
		);

		// Get BDB xprofile fields from DB.
		global $wpdb;

		$xprofile_fields = $wpdb->get_results(
			$wpdb->prepare(
				"SELECT * FROM {$wpdb->base_prefix}bp_xprofile_fields WHERE parent_id = %d ORDER BY group_id ASC",
				0
			)
		);

		if ( ! empty( $xprofile_fields ) ) {

			foreach ( $xprofile_fields as $field ) {

				if ( 'socialnetworks' === $field->type ) {
					$child_fields = $wpdb->get_results(
						$wpdb->prepare(
							"SELECT * FROM {$wpdb->base_prefix}bp_xprofile_fields WHERE parent_id = %d ORDER BY group_id ASC",
							$field->id
						)
					);
					if ( ! empty( $child_fields ) ) {
						foreach ( $child_fields as $child_field ) {
							$fields[ 'BDBXPROFILE_' . $child_field->id . '|' . $child_field->name ] = array(
								'name' => $field->name . ' - ' . $child_field->name,
							);
						}
					}
				} elseif ( 'membertypes' === $field->type ) {
					$fields[ 'BDBXPROFILE_' . $field->id . '|membertypes' ] = array(
						'name' => $field->name,
					);
				} else {
					$fields[ 'BDBXPROFILE_' . $field->id ] = array(
						'name' => $field->name,
					);
				}
			}
		}

		return $fields;

	}

	/**
	 * Populate the token with actual values.
	 *
	 * @param $args
	 * @param $trigger
	 *
	 * @return array
	 */
	public function hydrate_user_tokens( $parsed, $args, $trigger ) {

		$member_id = $args['trigger_args'][0];

		// Get Group id from meta log
		$avatar_url = '';
		if ( function_exists( 'get_avatar_url' ) ) {
			$avatar_url = get_avatar_url( $member_id );
		}

		$parse_tokens = array(
			'BDBMEMBER_ID'  => absint( $member_id ),
			'BDBUSERAVATAR' => $avatar_url,
		);

		// Get BDB xprofile fields from DB.
		global $wpdb;

		$xprofile_fields = $wpdb->get_results(
			$wpdb->prepare(
				"SELECT * FROM {$wpdb->base_prefix}bp_xprofile_fields WHERE parent_id = %d ORDER BY group_id ASC",
				0
			)
		);

		if ( ! empty( $xprofile_fields ) ) {

			foreach ( $xprofile_fields as $field ) {

				if ( 'socialnetworks' === $field->type ) {
					$child_fields = $wpdb->get_results(
						$wpdb->prepare(
							"SELECT * FROM {$wpdb->base_prefix}bp_xprofile_fields WHERE parent_id = %d ORDER BY group_id ASC",
							$field->id
						)
					);
					if ( ! empty( $child_fields ) ) {
						foreach ( $child_fields as $child_field ) {
							$parse_tokens[ 'BDBXPROFILE_' . $child_field->id . '|' . $child_field->name ] = $this->get_profile_field_value_pro( $member_id, $field->id, 'social_media', $child_field->name );
						}
					}
				} elseif ( 'membertypes' === $field->type ) {
					$parse_tokens[ 'BDBXPROFILE_' . $field->id . '|membertypes' ] = $this->get_profile_field_value_pro( $member_id, $field->id, 'membertypes' );
				} else {
					$parse_tokens[ 'BDBXPROFILE_' . $field->id ] = $this->get_profile_field_value_pro( $member_id, $field->id, 'simple' );
				}
			}
		}

		return $parsed + $parse_tokens;
	}


	/**
	 * Method get_profile_field_value
	 *
	 * @param integer $user_id The user id.
	 * @param string $piece The token piece.
	 *
	 * @return string The profile field value. Comma separated list of values for multiple selection.
	 */
	public function get_profile_field_value_pro( $user_id = 0, $piece = '', $type = 'simple', $social_handle = '' ) {

		if ( ! function_exists( 'bp_get_profile_field_data' ) ) {
			return '';
		}

		$profile_field_value = bp_get_profile_field_data(
			array(
				'field'   => absint( $piece ),
				'user_id' => $user_id,
			)
		);

		// Checkbox
		if ( 'social_media' == (string) $type ) {
			foreach ( $profile_field_value as $sm_handle ) {
				if ( strpos( strtolower( $sm_handle ), strtolower( $social_handle ) ) ) {
					return ( is_array( $sm_handle ) ) ? implode( ', ', trim( $sm_handle ) ) : $sm_handle;
				}
			}
		} elseif ( is_array( $profile_field_value ) ) {
			return implode( ', ', $profile_field_value );
		}

		return $profile_field_value;

	}

	/**
	 * Populate the token with actual values.
	 *
	 * @param $args
	 * @param $trigger
	 *
	 * @return array
	 */
	public function hydrate_tokens( $parsed, $args, $trigger ) {

		$forum_id = $args['trigger_args'][0]['forum_id'];

		return $parsed + array(
			'FORUM_ID'          => absint( $forum_id ),
			'FORUM_TITLE'       => bbp_get_forum_title( $forum_id ),
			'FORUM_DESCRIPTION' => bbp_get_forum_content( $forum_id ),
			'FORUM_TYPE'        => bbp_get_forum_type( $forum_id ),
			'FORUM_STATUS'      => bbp_get_forum_status( $forum_id ),
			'FORUM_VISIBILITY'  => bbp_get_forum_visibility( $forum_id ),
			'PARENT_FORUM_ID'   => bbp_get_forum_parent_id( $forum_id ),
			'PARENT_FORUM'      => bbp_get_forum_title( bbp_get_forum_parent_id( $forum_id ) ),
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
	public function send_msg_to_usr_hydrate_tokens( $parsed, $args, $trigger ) {

		$sender_id   = $args['trigger_args'][0]->sender_id;
		$rec_user_id = array_values( $args['trigger_args'][0]->recipients )[0]->user_id;

		$user     = get_user_by( 'ID', $sender_id );
		$rec_user = get_user_by( 'ID', $rec_user_id );

		return $parsed + array(
			'AVATAR_URL'     => ( function_exists( 'get_avatar_url' ) ) ? get_avatar_url( $sender_id ) : '',
			'NAME'           => $user->display_name,
			'LAST_NAME'      => bp_get_user_meta( $sender_id, 'last_name', true ),
			'NICKNAME'       => $user->nickname,
			'RCV_AVATAR_URL' => ( function_exists( 'get_avatar_url' ) ) ? get_avatar_url( $rec_user_id ) : '',
			'RCV_NAME'       => $rec_user->display_name,
			'RCV_LAST_NAME'  => bp_get_user_meta( $rec_user_id, 'last_name', true ),
			'RCV_NICKNAME'   => $rec_user->nickname,
			'RCV_EMAIL'      => $rec_user->user_email,
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
	public function receive_msg_to_usr_hydrate_tokens( $parsed, $args, $trigger ) {

		$sender_id   = array_values( $args['trigger_args'][0]->recipients )[0]->user_id;
		$rec_user_id = $args['trigger_args'][0]->sender_id;

		$user     = get_user_by( 'ID', $sender_id );
		$rec_user = get_user_by( 'ID', $rec_user_id );

		return $parsed + array(
			'AVATAR_URL'      => ( function_exists( 'get_avatar_url' ) ) ? get_avatar_url( $sender_id ) : '',
			'NAME'            => $user->display_name,
			'LAST_NAME'       => bp_get_user_meta( $sender_id, 'last_name', true ),
			'NICKNAME'        => $user->nickname,
			'SEND_AVATAR_URL' => ( function_exists( 'get_avatar_url' ) ) ? get_avatar_url( $rec_user_id ) : '',
			'SEND_NAME'       => $rec_user->display_name,
			'SEND_LAST_NAME'  => bp_get_user_meta( $rec_user_id, 'last_name', true ),
			'SEND_NICKNAME'   => $rec_user->nickname,
			'SEND_EMAIL'      => $rec_user->user_email,
		);

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
			if ( in_array( 'BDBGROUPS', $pieces ) ) {
				// Get Group id from meta log
				$group_id = $this->get_meta_data_from_trigger_meta( $user_id, 'BDBGROUPS', $replace_args['trigger_id'], $replace_args['trigger_log_id'] );
				if ( $group_id ) {
					$group = groups_get_group( $group_id );
					if ( isset( $group->name ) ) {
						$value = $group->name;
					}
				}
			} elseif ( in_array( 'BDBGROUPS_ID', $pieces ) ) {
				// Get Group id from meta log
				$group_id = $this->get_meta_data_from_trigger_meta( $user_id, 'BDBGROUPS', $replace_args['trigger_id'], $replace_args['trigger_log_id'] );
				if ( $group_id ) {
					$value = $group_id;
				}
			}
			if ( in_array( 'BDBUSERREGISTERWITHFIELD', $pieces ) && ( in_array( 'BDBFIELD', $pieces ) || in_array( 'SUBVALUE', $pieces ) ) ) {
				if ( $trigger_data ) {
					foreach ( $trigger_data as $trigger ) {
						if ( $pieces[2] === 'SUBVALUE' && array_key_exists( $pieces[2], $trigger['meta'] ) ) {
							return $trigger['meta'][ $pieces[2] ];
						}
						if ( $pieces[2] === 'BDBFIELD' && array_key_exists( $pieces[2], $trigger['meta'] ) ) {
							return $trigger['meta']['BDBFIELD_readable'];
						}
					}
				}
			}
			if ( in_array( 'USER_PROFILE_URL', $pieces ) || in_array( 'MANAGE_GROUP_REQUESTS_URL', $pieces ) ) {
				// Get Group id from meta log
				$value = $this->get_meta_data_from_trigger_meta( $user_id, $pieces[2], $replace_args['trigger_id'], $replace_args['trigger_log_id'] );
				if ( $value ) {
					$value = maybe_unserialize( $value );
				}
			}
			if ( in_array( 'BDBUSERUPDATEPROFILEFIELDS', $pieces ) ) {
				if ( 'BDBUSER' === $pieces[2] ) {
					return $this->get_meta_data_from_trigger_meta( $user_id, $pieces[2], $replace_args['trigger_id'], $replace_args['trigger_log_id'] );
				}
				if ( 'SUBVALUE' === $pieces[2] ) {
					return $this->get_meta_data_from_trigger_meta( $user_id, $pieces[2], $replace_args['trigger_id'], $replace_args['trigger_log_id'] );
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
	public function bdb_possible_tokens_pro( $tokens = array(), $args = array() ) {
		$trigger_integration = $args['integration'];
		$trigger_meta        = $args['meta'];
		$fields              = array();
		if ( isset( $args['triggers_meta']['code'] ) && 'BDBPOSTGROUPACTIVITY' === $args['triggers_meta']['code'] ) {

			$fields[] = array(
				'tokenId'         => 'BDBGROUPS_ID',
				'tokenName'       => __( 'Group ID', 'uncanny-automator' ),
				'tokenType'       => 'text',
				'tokenIdentifier' => 'BDBUSERACTIVITY',
			);
			$fields[] = array(
				'tokenId'         => 'ACTIVITY_ID',
				'tokenName'       => __( 'Activity ID', 'uncanny-automator' ),
				'tokenType'       => 'text',
				'tokenIdentifier' => 'BDBUSERACTIVITY',
			);
			$fields[] = array(
				'tokenId'         => 'ACTIVITY_CONTENT',
				'tokenName'       => __( 'Activity content', 'uncanny-automator' ),
				'tokenType'       => 'text',
				'tokenIdentifier' => 'BDBUSERACTIVITY',
			);
			$fields[] = array(
				'tokenId'         => 'ACTIVITY_URL',
				'tokenName'       => __( 'Activity URL', 'uncanny-automator' ),
				'tokenType'       => 'text',
				'tokenIdentifier' => 'BDBUSERACTIVITY',
			);
			$fields[] = array(
				'tokenId'         => 'ACTIVITY_STREAM_URL',
				'tokenName'       => __( 'Activity stream URL', 'uncanny-automator' ),
				'tokenType'       => 'text',
				'tokenIdentifier' => 'BDBUSERACTIVITY',
			);

			$tokens_length = (int) get_option( 'bp_media_allowed_per_batch', 10 );
			if ( $tokens_length > 20 ) {
				$tokens_length = 20;
			}

			for ( $tok = 1; $tok <= $tokens_length; $tok ++ ) {
				$fields[] = array(
					'tokenId'         => 'ACTIVITY_MEDIA_URL_' . $tok,
					'tokenName'       => __( 'Activity media URL #' . $tok, 'uncanny-automator-pro' ),
					'tokenType'       => 'text',
					'tokenIdentifier' => 'BDBUSERACTIVITY',
				);
			}

			$tokens_length = 0;
			$tokens_length = (int) get_option( 'bp_video_allowed_per_batch', 10 );
			if ( $tokens_length > 20 ) {
				$tokens_length = 20;
			}

			for ( $tok = 1; $tok <= $tokens_length; $tok ++ ) {
				$fields[] = array(
					'tokenId'         => 'ACTIVITY_VIDEO_URL_' . $tok,
					'tokenName'       => __( 'Activity video URL #' . $tok, 'uncanny-automator-pro' ),
					'tokenType'       => 'text',
					'tokenIdentifier' => 'BDBUSERACTIVITY',
				);
			}

			$tokens_length = 0;
			$tokens_length = (int) get_option( 'bp_document_allowed_per_batch', 10 );
			if ( $tokens_length > 20 ) {
				$tokens_length = 20;
			}

			for ( $tok = 1; $tok <= $tokens_length; $tok ++ ) {
				$fields[] = array(
					'tokenId'         => 'ACTIVITY_DOCUMENT_URL_' . $tok,
					'tokenName'       => __( 'Activity document URL #' . $tok, 'uncanny-automator-pro' ),
					'tokenType'       => 'text',
					'tokenIdentifier' => 'BDBUSERACTIVITY',
				);
			}
		}
		$tokens = array_merge( $tokens, $fields );

		return $tokens;
	}

	/**
	 * @param array $tokens
	 * @param array $args
	 *
	 * @return array
	 */
	public function bdb_private_group_tokens_pro( $tokens = array(), $args = array() ) {
		$trigger_meta = $args['meta'];

		$fields = array(
			array(
				'tokenId'         => 'USER_PROFILE_URL',
				'tokenName'       => __( 'User profile URL', 'uncanny-automator-pro' ),
				'tokenType'       => 'text',
				'tokenIdentifier' => $trigger_meta,
			),
			array(
				'tokenId'         => 'MANAGE_GROUP_REQUESTS_URL',
				'tokenName'       => __( 'Manage group requests URL', 'uncanny-automator-pro' ),
				'tokenType'       => 'text',
				'tokenIdentifier' => $trigger_meta,
			),
		);

		$tokens = array_merge( $tokens, $fields );

		return $tokens;
	}

	/**
	 * Remove Default User Tokens when the user is a guest.
	 *
	 * @param array $tokens
	 * @param array $args
	 *
	 * @return array
	 */
	public function bdb_remove_user_tokens_for_guest( $tokens = array(), $args = array() ) {

		$trigger_integration = $args['integration'];
		$trigger_meta        = $args['meta'];

		if ( 'BBTOPIC' === $trigger_meta && 'BDB' === $trigger_integration ) {

			if ( 'BDBGUESTREPLIESTOTOPIC' === $args['triggers_meta']['code'] ) {
				$tokens = array(
					array(
						'tokenId'         => 'REPLY_ID',
						'tokenName'       => __( 'Reply ID', 'uncanny-automator-pro' ),
						'tokenType'       => 'text',
						'tokenIdentifier' => $trigger_meta,
					),
					array(
						'tokenId'         => 'REPLY_URL',
						'tokenName'       => __( 'Reply URL', 'uncanny-automator-pro' ),
						'tokenType'       => 'text',
						'tokenIdentifier' => $trigger_meta,
					),
					array(
						'tokenId'         => 'REPLY_CONTENT',
						'tokenName'       => __( 'Reply content', 'uncanny-automator-pro' ),
						'tokenType'       => 'text',
						'tokenIdentifier' => $trigger_meta,
					),
				);
			}
		}

		return $tokens;
	}


	/**
	 * @param array $tokens
	 * @param array $args
	 *
	 * @return array
	 */
	public function bdb_join_group_tokens_pro( $tokens = array(), $args = array() ) {
		$trigger_meta = $args['meta'];

		$fields = array(
			array(
				'tokenId'         => 'BDBGROUPS_ID',
				'tokenName'       => __( 'Group ID', 'uncanny-automator-pro' ),
				'tokenType'       => 'text',
				'tokenIdentifier' => $trigger_meta,
			),
		);

		return array_merge( $fields, $tokens );
	}

	/**
	 * @return array[]
	 */
	public function user_notification_tokens() {

		return array(
			'NOTIFICATION_TYPE' => array(
				'name' => __( 'Notification type', 'uncanny-automator-pro' ),
				'type' => 'text',
			),
			'USER_ID'           => array(
				'name' => __( 'User ID', 'uncanny-automator-pro' ),
				'type' => 'int',
			),
			'AVATAR_URL'        => array(
				'name' => __( 'Avatar URL', 'uncanny-automator-pro' ),
				'type' => 'url',
			),
			'NAME'              => array(
				'name' => __( 'Name', 'uncanny-automator-pro' ),
				'type' => 'text',
			),
			'LAST_NAME'         => array(
				'name' => __( 'Last name', 'uncanny-automator-pro' ),
				'type' => 'text',
			),
			'NICKNAME'          => array(
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
	public function hydrate_user_notification_tokens( $parsed, $args, $trigger ) {

		$not_type = $args['trigger_args'][0]->component_action;
		$user_id  = $args['trigger_args'][0]->user_id;

		$user = get_user_by( 'ID', $user_id );

		return $parsed + array(
			'NOTIFICATION_TYPE' => Automator()->helpers->recipe->buddyboss->pro->get_notification_type( $not_type ),
			'USER_ID'           => $user_id,
			'AVATAR_URL'        => ( function_exists( 'get_avatar_url' ) ) ? get_avatar_url( $user_id ) : '',
			'NAME'              => $user->display_name,
			'LAST_NAME'         => bp_get_user_meta( $user_id, 'last_name', true ),
			'NICKNAME'          => $user->nickname,
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
				'name' => __( 'Display name', 'uncanny-automator-pro' ),
				'type' => 'text',
			),
			'FIRST_NAME'         => array(
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
			'USER_ID'            => array(
				'name' => __( 'User ID', 'uncanny-automator-pro' ),
				'type' => 'int',
			),
			'FRIENDS_AVATAR_URL' => array(
				'name' => __( "Friend's avatar URL", 'uncanny-automator-pro' ),
				'type' => 'url',
			),
			'FRIENDS_NAME'       => array(
				'name' => __( "Friend's name", 'uncanny-automator-pro' ),
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
	 * Populate the token with actual values.
	 *
	 * @param $parsed
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
	 * @param $parsed
	 * @param $args
	 * @param $trigger
	 *
	 * @return array
	 */
	public function hydrate_users_friendship_tokens( $parsed, $args, $trigger ) {

		list( $id, $init_user_id, $friend_user_id, $friendship ) = $args['trigger_args'];

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
			'BDB_USERREPLIESTOACTIVITYSTREAM_META' => absint( $activity['user_id'] ),
			'AVATAR_URL'                           => ( function_exists( 'get_avatar_url' ) ) ? get_avatar_url( absint( $activity['user_id'] ) ) : '',
			'NAME'                                 => $user->display_name,
			'FIRST_NAME'                           => bp_get_user_meta( absint( $activity['user_id'] ), 'first_name', true ),
			'LAST_NAME'                            => bp_get_user_meta( absint( $activity['user_id'] ), 'last_name', true ),
			'NICKNAME'                             => $user->nickname,
			'ACTIVITY_ID'                          => $activity['activity_id'],
			'ACTIVITY_URL'                         => bp_activity_get_permalink( absint( $activity['activity_id'] ) ),
			'ACTIVITY_STREAM_URL'                  => bp_core_get_user_domain( absint( $activity['user_id'] ) ) . 'activity/' . absint( $activity['activity_id'] ),
			'ACTIVITY_CONTENT'                     => $activity['content'],
		);
	}

	/**
	 * @return array[]
	 */
	public function user_forum_topic_tokens() {

		return array(
			'TOPIC_ID'    => array(
				'name' => __( 'Topic ID', 'uncanny-automator-pro' ),
				'type' => 'int',
			),
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
			'BDB_USERSTOPICRECEIVESREPLY_META'     => bbp_get_forum_title( $forum_id ),
			'BDB_USERSTOPICRECEIVESREPLY_META_ID'  => $forum_id,
			'BDB_USERSTOPICRECEIVESREPLY_META_URL' => bbp_get_forum_permalink( $forum_id ),
			'TOPIC_ID'                             => absint( $topic_id ),
			'TOPIC_TITLE'                          => bbp_get_topic_title( $topic_id ),
			'TOPIC_URL'                            => bbp_get_topic_permalink( $topic_id ),
			'REPLY_URL'                            => bbp_get_reply_url( $topic_id ),
			'AVATAR_URL'                           => ( function_exists( 'get_avatar_url' ) ) ? get_avatar_url( absint( $reply_author_id ) ) : '',
			'NAME'                                 => $user->display_name,
			'FIRST_NAME'                           => bp_get_user_meta( absint( $reply_author_id ), 'first_name', true ),
			'LAST_NAME'                            => bp_get_user_meta( absint( $reply_author_id ), 'last_name', true ),
			'NICKNAME'                             => $user->nickname,
		);
	}

	/**
	 * @return array[]
	 */
	public function user_group_tokens() {

		return array(
			'GROUP_NAME' => array(
				'name' => __( 'Group name', 'uncanny-automator-pro' ),
				'type' => 'text',
			),
			'GROUP_TYPE' => array(
				'name' => __( 'Group type title', 'uncanny-automator-pro' ),
				'type' => 'text',
			),
			'AVATAR_URL' => array(
				'name' => __( 'Avatar URL', 'uncanny-automator-pro' ),
				'type' => 'text',
			),
			'NAME'       => array(
				'name' => __( 'Display name', 'uncanny-automator-pro' ),
				'type' => 'text',
			),
			'FIRST_NAME' => array(
				'name' => __( 'First name', 'uncanny-automator-pro' ),
				'type' => 'text',
			),
			'LAST_NAME'  => array(
				'name' => __( 'Last name', 'uncanny-automator-pro' ),
				'type' => 'text',
			),
			'NICKNAME'   => array(
				'name' => __( 'Nickname', 'uncanny-automator-pro' ),
				'type' => 'text',
			),

		);
	}

	/**
	 * @return array[]
	 */
	public function user_private_group_tokens() {

		return array(
			'PRIVATE_GROUP_NAME' => array(
				'name' => __( 'Private group name', 'uncanny-automator-pro' ),
				'type' => 'text',
			),
			'GROUP_TYPE'         => array(
				'name' => __( 'Group type title', 'uncanny-automator-pro' ),
				'type' => 'text',
			),
			'AVATAR_URL'         => array(
				'name' => __( 'Avatar URL', 'uncanny-automator-pro' ),
				'type' => 'url',
			),
			'NAME'               => array(
				'name' => __( 'Display name', 'uncanny-automator-pro' ),
				'type' => 'text',
			),
			'FIRST_NAME'         => array(
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

		if ( empty( $group_id ) && ! isset( $user_id ) ) {
			return $parsed;
		}

		$user = get_user_by( 'ID', absint( $user_id ) );

		return $parsed + array(
			'GROUP_NAME' => Automator()->helpers->recipe->buddyboss->pro->bdb_get_group_title( absint( $group_id ) ),
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
	public function hydrate_user_private_group_tokens( $parsed, $args, $trigger ) {

		list( $user_id, $admins, $group_id, $req_id ) = $args['trigger_args'];

		if ( empty( $group_id ) && ! isset( $user_id ) ) {
			return $parsed;
		}

		$user = get_user_by( 'ID', absint( $user_id ) );

		return $parsed + array(
			'PRIVATE_GROUP_NAME' => Automator()->helpers->recipe->buddyboss->pro->bdb_get_group_title( absint( $group_id ) ),
			'GROUP_TYPE'         => Automator()->helpers->recipe->buddyboss->pro->uo_bp_groups_get_group_type( absint( $group_id ), true ),
			'AVATAR_URL'         => ( function_exists( 'get_avatar_url' ) ) ? get_avatar_url( absint( $user_id ) ) : '',
			'NAME'               => $user->display_name,
			'FIRST_NAME'         => bp_get_user_meta( absint( $user_id ), 'first_name', true ),
			'LAST_NAME'          => bp_get_user_meta( absint( $user_id ), 'last_name', true ),
			'NICKNAME'           => $user->nickname,
		);
	}

	/**
	 * @return array[]
	 */
	public function user_creates_group_tokens() {

		return array(
			'GROUP_ID'     => array(
				'name' => __( 'Group ID', 'uncanny-automator-pro' ),
				'type' => 'int',
			),
			'PUBLIC_GROUP' => array(
				'name' => __( 'Public group name', 'uncanny-automator-pro' ),
				'type' => 'text',
			),
			'AVATAR_URL'   => array(
				'name' => __( 'Avatar URL', 'uncanny-automator-pro' ),
				'type' => 'url',
			),
			'NAME'         => array(
				'name' => __( 'Display name', 'uncanny-automator-pro' ),
				'type' => 'text',
			),
			'FIRST_NAME'   => array(
				'name' => __( 'First name', 'uncanny-automator-pro' ),
				'type' => 'text',
			),
			'LAST_NAME'    => array(
				'name' => __( 'Last name', 'uncanny-automator-pro' ),
				'type' => 'text',
			),
			'NICKNAME'     => array(
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
	public function hydrate_user_creates_group_tokens( $parsed, $args, $trigger ) {

		list( $group_id, $member, $group ) = $args['trigger_args'];

		if ( ! isset( $group_id ) && ! isset( $group->creator_id ) ) {
			return $parsed;
		}

		$user_id = absint( $group->creator_id );
		$user    = get_user_by( 'ID', $user_id );

		return $parsed + array(
			'PUBLIC_GROUP' => Automator()->helpers->recipe->buddyboss->pro->bdb_get_group_title( absint( $group_id ) ),
			'GROUP_TYPE'   => ( function_exists( 'bp_groups_get_group_type' ) ) ? ucfirst( bp_groups_get_group_type( absint( $group_id ), true ) ) : '',
			'AVATAR_URL'   => ( function_exists( 'get_avatar_url' ) ) ? get_avatar_url( absint( $user_id ) ) : '',
			'NAME'         => $user->display_name,
			'FIRST_NAME'   => bp_get_user_meta( absint( $user_id ), 'first_name', true ),
			'LAST_NAME'    => bp_get_user_meta( absint( $user_id ), 'last_name', true ),
			'NICKNAME'     => $user->nickname,
			'GROUP_ID'     => $group_id,
		);
	}

}
