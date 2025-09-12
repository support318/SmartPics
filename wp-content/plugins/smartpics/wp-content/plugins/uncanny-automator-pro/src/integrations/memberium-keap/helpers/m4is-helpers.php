<?php

namespace Uncanny_Automator_Pro\Integrations\M4IS;

/**
 * Class M4IS_HELPERS_PRO
 *
 * @package Uncanny_Automator_Pro
 */
class M4IS_HELPERS_PRO extends \Uncanny_Automator\Integrations\M4IS\M4IS_HELPERS {

	/**
	 * M4IS_HELPERS constructor.
	 */
	public function __construct() {
	}

	/**
	 * Get membership levels for select field.
	 *
	 * @return array
	 */
	public function get_membership_level_options( $any = false ) {

		static $membership_levels = null;

		if ( is_null( $membership_levels ) ) {
			$membership_levels = array();
			$level_map         = memb_getMembershipMap();
			if ( ! empty( $level_map ) && is_array( $level_map ) ) {
				foreach ( $level_map as $key => $level ) {
					$membership_levels[] = array(
						'value' => $level['main_id'],
						'text'  => $level['name'],
					);
				}
			}
		}

		if ( ! empty( $any ) ) {
			$label             = is_string( $any ) ? $any : esc_attr_x( 'Any Membership Level', 'M4IS', 'uncanny-automator-pro' );
			$membership_levels = array_merge(
				array(
					array(
						'value' => '-1',
						'text'  => $label,
					),
				),
				$membership_levels
			);
		}

		return $membership_levels;
	}

	/**
	 * Get Membership action token config.
	 *
	 * @return array
	 */
	public function get_membership_action_token_config() {
		return array(
			'MEMBERSHIP_NAME' => array(
				'name' => esc_attr_x( 'Membership name', 'M4IS - token', 'uncanny-automator-pro' ),
				'type' => 'int',
			),
			'TAG'             => array(
				'name' => esc_attr_x( 'Tag', 'M4IS - token', 'uncanny-automator-pro' ),
				'type' => 'text',
			),
		);
	}

	/**
	 * Get membership level ID from parsed.
	 *
	 * @param  array $parsed
	 * @param  string $meta_key
	 * @return string
	 */
	public function get_membership_level_id_from_parsed( $parsed, $meta_key ) {

		if ( ! isset( $parsed[ $meta_key ] ) ) {
			throw new \Exception( esc_html_x( 'Missing membership level', 'M4IS', 'uncanny-automator-pro' ) );
		}

		$membership_level_id = sanitize_text_field( $parsed[ $meta_key ] );

		if ( ! $membership_level_id ) {
			throw new \Exception( esc_html_x( 'Membership level is required.', 'M4IS', 'uncanny-automator-pro' ) );
		}

		return $membership_level_id;
	}

	/**
	 * Update membership level.
	 *
	 * @param int $user_id
	 * @param int $membership_level_id
	 *
	 * @return mixed \WP_Error || Membership level array
	 */
	public function update_membership_level( $user_id, $membership_level_id ) {

		// Assign Membership level ID to tag and convert to integer.
		$tag = intval( $membership_level_id );
		// Make membership level ID positive integer.
		$membership_level_id = abs( $membership_level_id );

		// Validate fields.
		if ( empty( $user_id ) || empty( $membership_level_id ) ) {
			return new \WP_Error( 'invalid_fields', esc_html_x( 'Invalid fields', 'M4IS', 'uncanny-automator-pro' ) );
		}

		// Check if user has manage options capability.
		if ( user_can( $user_id, 'manage_options' ) ) {
			return new \WP_Error( 'invalid_user', esc_html_x( 'Memberium actions not permitted on users with Admin / manage_options capability', 'M4IS', 'uncanny-automator' ) );
		}

		// Validate Membership Exists.
		$membership = $this->get_membership_by_id( $membership_level_id );
		if ( empty( $membership ) ) {
			return new \WP_Error( 'invalid_membership', esc_html_x( 'Invalid membership level', 'M4IS', 'uncanny-automator-pro' ) );
		}

		// Validate User / Contact Exists.
		$contact_id = memb_getContactIdByUserId( $user_id );
		if ( empty( $contact_id ) ) {
			return new \WP_Error( 'not_a_contact', esc_html_x( 'No contact ID for user', 'M4IS', 'uncanny-automator-pro' ) );
		}

		// Check if user has membership level.
		$has_membership = $this->memberium_contact_has_membership_level_id( $user_id, $contact_id, $membership_level_id );
		// Check if removing membership level.
		if ( $tag < 0 ) {
			// Removing and user doesn't have membership level.
			if ( ! $has_membership ) {
				return new \WP_Error( 'not_a_member', esc_html_x( 'User is not a member', 'M4IS', 'uncanny-automator-pro' ) );
			}
		}

		// Checking if adding membership level.
		if ( $tag > 0 ) {
			// Adding and user already has membership level.
			if ( $has_membership ) {
				return new \WP_Error( 'already_a_member', esc_html_x( 'User is already a member', 'M4IS', 'uncanny-automator-pro' ) );
			}
		}

		// Add or remove membership level tag.
		memb_setTags( $tag, (int) $contact_id, true );
		// Sync contact with Memberium.
		memb_syncContact( $contact_id, true );

		return $membership;
	}

	/**
	 * Get membership by ID.
	 *
	 * @param int $membership_level_id
	 *
	 * @return mixed false || array
	 */
	public function get_membership_by_id( $membership_level_id ) {

		$level_map = memb_getMembershipMap();
		if ( ! empty( $level_map ) && is_array( $level_map ) ) {
			if ( array_key_exists( $membership_level_id, $level_map ) ) {
				return $level_map[ $membership_level_id ];
			}
		}

		return false;
	}

	/**
	 * Get membership level name.
	 *
	 * @param int $membership_level_id
	 *
	 * @return string
	 */
	public function get_membership_level_name( $membership_level_id ) {

		if ( empty( $membership_level_id ) ) {
			return '';
		}

		if ( intval( '-1' ) === intval( $membership_level_id ) ) {
			return esc_attr_x( 'Any', 'M4IS', 'uncanny-automator-pro' );
		}

		$membership = $this->get_membership_by_id( $membership_level_id );
		if ( ! empty( $membership ) ) {
			return $membership['name'];
		}

		return '';
	}

	/**
	 * Check if user has membership level.
	 *
	 * @param string $email
	 * @param int $membership_level_id
	 *
	 * @return mixed true || \WP_Error
	 */
	public function has_membership_level( $email, $membership_level_id ) {

		// Validate fields.
		if ( empty( $email ) || empty( $membership_level_id ) ) {
			return new \WP_Error( 'invalid_fields', esc_html_x( 'Email and Membership ID are required.', 'M4IS', 'uncanny-automator-pro' ) );
		}

		// Validate Contact Exists.
		$contact_id = $this->get_contact_id_by_email( $email );
		if ( empty( $contact_id ) ) {
			return new \WP_Error( 'invalid_contact', esc_html_x( 'No Keap contact ID found for email.', 'M4IS', 'uncanny-automator-pro' ) );
		}

		// Get User ID.
		$user_id = memb_getUserIdByContactId( $contact_id );
		if ( empty( $user_id ) ) {
			return new \WP_Error( 'invalid_user', esc_html_x( 'No WP User found for contact.', 'M4IS', 'uncanny-automator-pro' ) );
		} else {
			// Check if user has manage options capability.
			if ( user_can( $user_id, 'manage_options' ) ) {
				return new \WP_Error( 'invalid_user', esc_html_x( 'Memberium actions not permitted on users with Admin / manage_options capability', 'M4IS', 'uncanny-automator' ) );
			}
		}

		// Check if $membership_level_id is any membership level.
		if ( intval( '-1' ) === intval( $membership_level_id ) ) {
			return $this->memberium_contact_has_any_membership_level( $user_id, $contact_id );
		}

		// Check if contact has membership.
		return $this->memberium_contact_has_membership_level_id( $user_id, $contact_id, $membership_level_id );
	}

	/**
	 * Check if contact has any membership level.
	 *
	 * @param int $user_id
	 * @param int $contact_id
	 *
	 * @return bool
	 */
	private function memberium_contact_has_any_membership_level( $user_id, $contact_id ) {

		if ( empty( absint( $user_id ) ) || empty( absint( $contact_id ) ) ) {
			return false;
		}

		// We will check if Memberium api function `memb_hasAnyMembership` has at least one param for Contact ID
		// Contact ID will be added in a future release of Memberium current Version 2.207.
		if ( $this->function_param_count( 'memb_hasAnyMembership' ) < 1 ) {
			return ! empty( $this->get_memberium_user_membership_levels_from_session( $user_id ) );
		}

		return memb_hasAnyMembership( $contact_id );
	}

	/**
	 * Check if contact has membership level.
	 *
	 * @param int $user_id
	 * @param int $contact_id
	 * @param int $membership_level_id
	 *
	 * @return mixed bool || \WP_Error
	 */
	private function memberium_contact_has_membership_level_id( $user_id, $contact_id, $membership_level_id ) {

		if ( empty( absint( $user_id ) ) || empty( absint( $contact_id ) ) ) {
			return false;
		}

		// Validate Membership Exists.
		$membership = $this->get_membership_by_id( $membership_level_id );
		if ( empty( $membership ) ) {
			return new \WP_Error( 'invalid_membership', esc_html_x( 'Invalid Membership ID.', 'M4IS', 'uncanny-automator-pro' ) );
		}

		// We will check if Memberium api function `memb_hasMembership` has at least two params for membership name and Contact ID
		// Contact ID will be added in a future release of Memberium current Version 2.207
		if ( $this->function_param_count( 'memb_hasMembership' ) < 2 ) {
			$ids = $this->get_memberium_user_membership_levels_from_session( $user_id );
			return is_array( $ids ) && in_array( absint( $membership_level_id ), $ids, true );
		}

		return memb_hasMembership( $membership['name'], $contact_id );
	}

	/**
	 * Get User Membership Levels from session.
	 *
	 * @param int $user_id
	 *
	 * @return mixed array|false
	 */
	private function get_memberium_user_membership_levels_from_session( $user_id ) {

		if ( empty( absint( $user_id ) ) ) {
			return false;
		}

		$user_session = memb_getSession( $user_id );

		if ( ! empty( $user_session ) && is_array( $user_session ) ) {
			if ( isset( $user_session['memb_user'] ) && is_array( $user_session['memb_user'] ) ) {
				if ( isset( $user_session['memb_user']['membership_id'] ) ) {
					$ids = $user_session['memb_user']['membership_id'];
					return empty( $ids ) ? false : array_map( 'intval', explode( ',', $ids ) );
				}
			}
		}

		return false;
	}

	/**
	 * Get tag action options.
	 *
	 * @param string $prefix
	 * @param bool $negatives
	 *
	 * @return array
	 */
	public function get_tag_contact_action_options( $prefix, $negatives = false ) {

		return array(
			array(
				'option_code'              => $prefix . '_META',
				'label'                    => _x( 'Tag(s)', 'M4IS - action token', 'uncanny-automator-pro' ),
				'input_type'               => 'select',
				'required'                 => true,
				'supports_custom_value'    => false,
				'options'                  => $this->get_tag_options( $negatives ),
				'supports_multiple_values' => true,
			),
			array(
				'option_code'           => $prefix . '_EMAIL',
				'label'                 => _x( 'Contact email', 'M4IS - action token', 'uncanny-automator-pro' ),
				'input_type'            => 'email',
				'required'              => true,
				'supports_custom_value' => true,
			),
		);

	}

	/**
	 * Get contact tag action token config.
	 *
	 * @return array
	 */
	public function get_contact_tag_action_tokens_config() {
		return array(
			'CONTACT_ID' => array(
				'name' => esc_attr_x( 'Contact ID', 'M4IS - token', 'uncanny-automator-pro' ),
				'type' => 'int',
			),
			'TAG'        => array(
				'name' => esc_attr_x( 'Tag name(s)', 'M4IS - token', 'uncanny-automator-pro' ),
				'type' => 'text',
			),
		);
	}

	/**
	 * Hydrate contact action tokens.
	 *
	 * @param int $contact_id
	 * @param array $tags
	 * @param bool $negatives
	 *
	 * @return array
	 */
	public function hydrate_contact_action_tokens( $contact_id, $tags, $negatives = false ) {
		return array(
			'CONTACT_ID' => $contact_id,
			'TAG'        => $this->get_tag_names( $tags, $negatives ),
		);
	}

	/**
	 * Get tag names.
	 *
	 * @param array $tags
	 * @param bool $negatives
	 * @param string $negative_sprintf
	 *
	 * @return string
	 */
	public function get_tag_names( $tags, $negatives = false, $negative_sprintf = '' ) {

		if ( empty( $tags ) ) {
			return '-';
		}

		$tags      = is_array( $tags ) ? $tags : array( $tags );
		$tag_names = array();
		$tag_map   = wp_list_pluck( $this->get_tag_options( $negatives, $negative_sprintf ), 'text', 'value' );

		foreach ( $tags as $tag_id ) {
			$tag_id = $negatives ? intval( $tag_id ) : abs( intval( $tag_id ) );
			if ( array_key_exists( $tag_id, $tag_map ) ) {
				$tag_names[] = $tag_map[ $tag_id ];
			}
		}

		return implode( ', ', $tag_names );
	}

	/**
	 * Get tag options for select field.
	 *
	 * @param bool $negatives - include negative options
	 * @param string $negative_sprintf - sprintf string for negative options
	 *
	 * @return array
	 */
	public function get_tag_options( $negatives = false, $negative_sprintf = '' ) {
		$cache_key   = 'uo/tags/' . md5( wp_json_encode( func_get_args() ) );
		static $tags = array();

		if ( isset( $tags[ $cache_key ] ) ) {
			return $tags[ $cache_key ];
		}

		$tags[ $cache_key ] = array();

		$tag_map = memb_getTagMap( true, $negatives );
		if ( $tag_map ) {
			if ( $negatives ) {
				if ( empty( $negative_sprintf ) ) {
					/* translators: %1$s Tag, %2$s Tag ID */
					$negative_sprintf = _x( 'Remove %1$s (- %2$s)', 'M4IS', 'uncanny-automator-pro' );
				}
			}
			$tag_map = ( isset( $tag_map['mc'] ) ) ? $tag_map['mc'] : array();
			foreach ( $tag_map as $id => $tag ) {
				$tags[ $cache_key ][] = array(
					'value' => $id,
					'text'  => $tag . ' (' . $id . ')',
				);
				if ( $negatives ) {
					$tags[ $cache_key ][] = array(
						'value' => '-' . $id,
						'text'  => sprintf( $negative_sprintf, $tag, $id ),
					);
				}
			}
		}

		return $tags[ $cache_key ];
	}

	/**
	 * Get tag IDs from parsed.
	 *
	 * @param array $parsed
	 * @param string $meta_key
	 * @param bool $negatives
	 *
	 * @return array
	 */
	public function get_tag_ids_from_parsed( $parsed, $meta_key, $negatives = false ) {

		if ( ! isset( $parsed[ $meta_key ] ) ) {
			throw new \Exception( esc_html_x( 'Missing tag ids', 'M4IS', 'uncanny-automator-pro' ) );
		}

		$parsed_tags = is_string( $parsed[ $meta_key ] ) ? json_decode( $parsed[ $meta_key ] ) : $parsed[ $meta_key ];
		$tags        = ! empty( $parsed_tags ) ? $this->prepare_tag_ids( $parsed_tags, $negatives ) : array();
		if ( empty( $tags ) ) {
			throw new \Exception( esc_html_x( 'Tag is required.', 'M4IS', 'uncanny-automator-pro' ) );
		}

		return $tags;
	}

	/**
	 * Update contact tags.
	 *
	 * @param string $email
	 * @param array $tags
	 *
	 * @return bool
	 */
	public function update_contact_tags( $email, $tags ) {

		$tags = $this->prepare_tag_ids( $tags );

		// Validate fields.
		if ( empty( $email ) || empty( $tags ) ) {
			return new \WP_Error( 'invalid_fields', esc_html_x( 'Invalid fields', 'M4IS', 'uncanny-automator-pro' ) );
		}

		// Validate Contact Exists.
		$contact_id = $this->get_contact_id_by_email( $email );
		if ( empty( $contact_id ) ) {
			return new \WP_Error( 'invalid_contact', esc_html_x( 'Contact not found by email', 'M4IS', 'uncanny-automator' ) );
		}

		// Check if contact is a WP user.
		$user_id = memb_getUserIdByContactId( $contact_id );

		if ( ! empty( $user_id ) ) {
			// Check if user has manage options capability.
			if ( user_can( $user_id, 'manage_options' ) ) {
				return new \WP_Error( 'invalid_user', esc_html_x( 'Memberium actions not permitted on users with Admin / manage_options capability', 'M4IS', 'uncanny-automator' ) );
			}
		}

		// Ensure we have a valid i2SDK connection.
		if ( ! $this->i2sdk() ) {
			return new \WP_Error( 'invalid_i2sdk', esc_html_x( 'Invalid connection to Keap', 'M4IS', 'uncanny-automator' ) );
		}

		// Prepare results array.
		$results = array(
			'assign' => array(
				'SUCCESS' => array(),
				'FAILURE' => array(),
			),
			'remove' => array(
				'SUCCESS' => array(),
				'FAILURE' => array(),
			),
		);

		// Get contact tags from Keap directly ( Issues with Memberium Sessions ).
		$contact_tags = $this->i2sdk()->isdk->loadCon( (int) $contact_id, array( 'Groups' ) );
		$contact_tags = is_array( $contact_tags ) && isset( $contact_tags['Groups'] ) ? $contact_tags['Groups'] : '';
		$contact_tags = ! empty( $contact_tags ) ? array_map( 'intval', explode( ',', $contact_tags ) ) : array();

		// Compare user's existing tags with new tags.
		$has_tags = ! empty( $contact_tags );
		// Generate $results array.
		foreach ( $tags as $tag_index => $tag ) {
			$action = $tag > 0 ? 'assign' : 'remove';
			$tag_id = abs( $tag );
			// User has tags.
			if ( $has_tags ) {
				if ( 'assign' === $action ) {
					$result_key = in_array( $tag_id, $contact_tags, true ) ? 'FAILURE' : 'SUCCESS';
				}
				if ( 'remove' === $action ) {
					$result_key = in_array( $tag_id, $contact_tags, true ) ? 'SUCCESS' : 'FAILURE';
				}
			}
			// User does not have tags.
			if ( ! $has_tags ) {
				$result_key = 'assign' === $action ? 'SUCCESS' : 'FAILURE';
			}
			// Remove from update
			if ( 'FAILURE' === $result_key ) {
				unset( $tags[ $tag_index ] );
			}
			// Add to $results array.
			$results[ $action ][ $result_key ][] = $tag_id;
		}

		// Check if there are still tags to update.
		if ( ! empty( $tags ) ) {

			// Use Memberium API function if user exists.
			if ( ! empty( $user_id ) ) {
				// Update tags using internal Memberium API function.
				memb_setTags( $tags, (int) $contact_id, true );
			}

			// Update tags using using i2SDK if user doesn't exist.
			if ( empty( $user_id ) ) {
				foreach ( $tags as $tag ) {
					$action = $tag > 0 ? 'assign' : 'remove';
					$tag_id = abs( $tag );
					// Remove Tag.
					if ( 'remove' === $action ) {
						$response = $this->i2sdk()->isdk->grpRemove( (int) $contact_id, (int) $tag_id );
					}
					// Add Tag.
					if ( 'assign' === $action ) {
						$response = $this->i2sdk()->isdk->grpAssign( (int) $contact_id, (int) $tag_id );
					}
					// Add to $results array.
					$result_key                          = $response ? 'SUCCESS' : 'FAILURE';
					$results[ $action ][ $result_key ][] = $tag_id;
					// If we have a failure, remove the tag ID from the success results.
					if ( 'FAILURE' === $result_key ) {
						$index = array_search( $tag_id, $results[ $action ]['SUCCESS'], true );
						if ( false !== $index ) {
							unset( $results[ $action ]['SUCCESS'][ $index ] );
						}
					}
				}
			}
		}

		// Sync contact with Memberium to ensure data is updated for any proceeding functionality.
		foreach ( $results as $action => $result ) {
			if ( ! empty( $result['SUCCESS'] ) ) {
				// Sync contact with Memberium.
				memb_syncContact( $contact_id, true );
				break;
			}
		}

		// Return results.
		return array(
			'contact_id' => $contact_id,
			'tags'       => $tags,
			'results'    => $this->parse_update_contact_tags_results( $results ),
		);
	}

	/**
	 * Prepare tag IDs.
	 *
	 * @param mixed array|int|string  $tags
	 * @param bool $negatives - flag to make all tag ids negative.
	 *
	 * @return array - array of poistive or negative integers.
	 */
	public function prepare_tag_ids( $tags, $negatives = false ) {

		if ( empty( $tags ) ) {
			return array();
		}

		// Convert to array.
		$tags = ! is_array( $tags ) ? array( $tags ) : $tags;

		// Make all tags negatives.
		if ( $negatives ) {
			foreach ( $tags as $key => $tag ) {
				$tags[ $key ] = '-' . $tag;
			}
		}

		// Convert to integers.
		return array_map( 'intval', $tags );
	}

	/**
	 * Parse update contact tags results.
	 *
	 * @param array $results
	 *
	 * @return
	 */
	private function parse_update_contact_tags_results( $results ) {

		$messages = array();

		foreach ( $results as $action => $result ) {

			// Count failed and success.
			$failed  = ! empty( $results[ $action ]['FAILURE'] ) ? count( $results[ $action ]['FAILURE'] ) : 0;
			$success = ! empty( $results[ $action ]['SUCCESS'] ) ? count( $results[ $action ]['SUCCESS'] ) : 0;

			// Skip if no failed or success.
			if ( empty( $failed ) && empty( $success ) ) {
				continue;
			}

			$messages[ $action ] = array(
				'error'      => '',
				'success'    => '',
				'do_nothing' => false,
			);

			// Prepare Failed message.
			if ( $failed > 0 ) {
				$failed_tags = $this->get_tag_names( $results[ $action ]['FAILURE'] );

				if ( 'remove' === $action ) {
					$messages[ $action ]['error'] = sprintf(
						/* translators: %s - tag names */
						esc_attr_x( 'Contact did not have tag(s) to remove: %s', 'M4IS', 'uncanny-automator-pro' ),
						$failed_tags
					);
				}
				if ( 'assign' === $action ) {
					$messages[ $action ]['error'] = sprintf(
						/* translators: %s - tag names */
						esc_attr_x( 'Contact already had tag(s) to assign: %s', 'M4IS', 'uncanny-automator-pro' ),
						$failed_tags
					);
				}
			}

			// Prepare Success message.
			if ( $success > 0 ) {
				$success_tags = $this->get_tag_names( $results[ $action ]['SUCCESS'] );
				if ( 'remove' === $action ) {
					$messages[ $action ]['success'] = sprintf(
						/* translators: %s - tag names */
						esc_attr_x( 'Removed tag(s): %s', 'M4IS', 'uncanny-automator-pro' ),
						$success_tags
					);
				}
				if ( 'assign' === $action ) {
					$messages[ $action ]['success'] = sprintf(
						/* translators: %s - tag names */
						esc_attr_x( 'Assigned tag(s): %s', 'M4IS', 'uncanny-automator-pro' ),
						$success_tags
					);
				}
			}
		}

		foreach ( $messages as $action => $result ) {
			$error   = isset( $result['error'] ) && ! empty( $result['error'] ) ? $result['error'] : false;
			$success = isset( $result['success'] ) && ! empty( $result['success'] ) ? $result['success'] : false;
			if ( $error && ! $success ) {
				$messages[ $action ]['do_nothing'] = true;
			}
		}

		return $messages;
	}

	/**
	 * Check number of parameters for a function.
	 *
	 * @param callable $function
	 *
	 * @return int
	 */
	private function function_param_count( $function ) {
		$reflection = new \ReflectionFunction( $function );
		return $reflection->getNumberOfParameters();
	}

}
