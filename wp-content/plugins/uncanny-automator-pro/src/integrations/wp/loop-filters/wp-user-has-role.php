<?php
namespace Uncanny_Automator_Pro\Loop_Filters;

use Uncanny_Automator_Pro\Loops\Filter\Base\Loop_Filter;

/**
 * Loop filter User has {{a specific role}}
 *
 * @since 5.0
 */
class WP_USER_HAS_ROLE extends Loop_Filter {

	public function setup() {

		$this->set_integration( 'WP' );

		$this->set_meta( 'WP_USER_HAS_ROLE' );

		$this->set_sentence( esc_html_x( 'User {{has}} {{a specific role}}', 'Filter sentence', 'uncanny-automator-pro' ) );

		$this->set_sentence_readable(
			sprintf(
				/* translators: Filter sentence */
				esc_html_x( 'User {{has:%1$s}} {{a specific role:%2$s}}', 'Filter sentence', 'uncanny-automator-pro' ),
				'CRITERIA',
				$this->get_meta()
			)
		);

		$this->set_fields( array( $this, 'load_options' ) );

		$this->set_entities( array( $this, 'retrieve_users_with_role' ) );

	}

	/**
	 * @return mixed[]
	 */
	public function load_options() {

		return array(
			$this->get_meta() => array(
				array(
					'option_code' => 'CRITERIA',
					'type'        => 'select',
					'label'       => esc_html_x( 'Criteria', 'WordPress', 'uncanny-automator' ),
					'options'     => array(
						array(
							'text'  => esc_html_x( 'has', 'WordPress', 'uncanny-automator' ),
							'value' => esc_html_x( 'has', 'WordPress', 'uncanny-automator' ),
						),
						array(
							'text'  => esc_html_x( 'does not have', 'WordPress', 'uncanny-automator' ),
							'value' => esc_html_x( 'does-not-have', 'WordPress', 'uncanny-automator' ),
						),
					),
				),
				array(
					'option_code' => $this->get_meta(),
					'type'        => 'select',
					'label'       => esc_html_x( 'Role', 'WordPress', 'uncanny-automator' ),
					'options'     => $this->get_roles(),
				),
			),
		);

	}

	/**
	 * @return mixed[]
	 */
	public static function get_wp_registered_roles() {

		global $wp_roles;

		if ( ! $wp_roles instanceof \WP_Roles ) {
			return array();
		}

		return (array) $wp_roles->get_names();

	}

	/**
	 * @return array{array{text:string,value:string}}|array{}
	 */
	protected function get_roles() {

		$options = array();

		$roles = self::get_wp_registered_roles();

		foreach ( $roles as $id => $name ) {
			if ( ! is_string( $name ) || empty( $name ) ) {
				continue; // Skip non-string and falsy values.
			}
			$options[] = array(
				'text'  => esc_attr( $name ),
				'value' => esc_attr( $id ),
			);
		}

		return $options;

	}

	/**
	 * @param array{WP_USER_HAS_ROLE:string,CRITERIA:string} $fields
	 *
	 * @return int[]
	 */
	public function retrieve_users_with_role( $fields ) {

		$role = $this->get_role_logic_value( $fields );

		// We need to be careful here. If the token returns empty, it will fetch all users.
		// Because the class \WP_User_Query fetches all users if 'role' argument is empty.
		if ( empty( $role ) ) {
			return array();
		}

		/**
		 * Construct the arguments array for WP_User_Query.
		 *
		 * @since 5.8.3 - The `cache_results` and `fields` parameters were added by Saad to enhance efficiency.
		 */
		$args = array(
			$role['logic']  => $role['role_id'], // Determine the logic and role for the query.
			'cache_results' => false,            // Suppress caching of query results for efficiency.
			'fields'        => 'ID',             // Retrieve only user IDs to minimize data processing.
		);

		$user_q = new \WP_User_Query( $args );

		$results = $user_q->get_results();

		// Do $user_q->get_results(); returns null? Better make sure its array.
		if ( empty( $results ) ) {
			return array();
		}

		return $results;

	}

	/**
	 * @param array{WP_USER_HAS_ROLE:string,CRITERIA:string} $fields
	 *
	 * @return array{logic:'role__in'|'role__not_in',role_id:array{string}}|array{} The list of roles. Returns empty array if criteria or role is missing.
	 */
	protected function get_role_logic_value( $fields ) {

		$criteria = $fields['CRITERIA'];
		$role     = $fields['WP_USER_HAS_ROLE'];

		if ( empty( $criteria ) || empty( $role ) ) {
			return array();
		}

		$logic = 'role__in';

		if ( 'does-not-have' === $criteria ) {
			$logic = 'role__not_in';
		}

		return array(
			'logic'   => $logic,
			'role_id' => array( $role ),
		);

	}

}
