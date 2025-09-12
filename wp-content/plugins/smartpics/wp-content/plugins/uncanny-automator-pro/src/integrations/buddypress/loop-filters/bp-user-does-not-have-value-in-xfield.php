<?php

namespace Uncanny_Automator_Pro\Loop_Filters;

use Uncanny_Automator_Pro\Loops\Filter\Base\Loop_Filter;

/**
 * Loop filter - A user has {a value} in {an Xprofile field}
 * Class BP_USER_DOES_NOT_HAVE_VALUE_IN_XFIELD
 *
 * @package Uncanny_Automator_Pro
 */
class BP_USER_DOES_NOT_HAVE_VALUE_IN_XFIELD extends Loop_Filter {

	/**
	 * @return void
	 * @throws \Exception
	 */
	public function setup() {
		$this->set_integration( 'BP' );
		$this->set_meta( 'BP_USER_DOES_NOT_HAVE_VALUE_IN_XFIELD' );
		$this->set_sentence( esc_html_x( 'A user does not have {{a value}} in {{an Xprofile field}}', 'Filter sentence', 'uncanny-automator-pro' ) );
		$this->set_sentence_readable(
			sprintf(
			/* translators: Filter sentence */
				esc_html_x( 'A user does not have {{a value:%1$s}} in {{an Xprofile field:%2$s}}', 'Filter sentence', 'uncanny-automator-pro' ),
				'FIELD_VALUE',
				$this->get_meta()
			)
		);
		$this->set_fields( array( $this, 'load_options' ) );
		$this->set_entities( array( $this, 'retrieve_users_field_value' ) );
	}

	/**
	 * @return mixed[]
	 */
	public function load_options() {

		$fields_option = Automator()->helpers->recipe->buddypress->options->pro->list_all_profile_fields();
		$options       = array();

		foreach ( $fields_option['options'] as $id => $value ) {
			$options[] = array(
				'text'  => esc_attr( $value ),
				'value' => esc_attr( $id ),
			);
		}

		return array(
			$this->get_meta() => array(
				array(
					'option_code' => 'FIELD_VALUE',
					'type'        => 'text',
					'label'       => esc_html_x( 'Value', 'BuddyPress', 'uncanny-automator-pro' ),
				),
				array(
					'option_code'           => $this->get_meta(),
					'type'                  => 'select',
					'supports_custom_value' => false,
					'label'                 => esc_html_x( 'Field', 'BuddyPress', 'uncanny-automator-pro' ),
					'options'               => $options,
				),
			),
		);

	}

	/**
	 * @param array{BP_USER_DOES_NOT_HAVE_VALUE_IN_XFIELD:string,FIELD_VALUE:string} $fields
	 *
	 * @return int[]
	 */
	public function retrieve_users_field_value( $fields ) {

		$field_value = $fields['FIELD_VALUE'];
		$field       = $fields['BP_USER_DOES_NOT_HAVE_VALUE_IN_XFIELD'];

		if ( empty( $field_value ) || empty( $field ) ) {
			return array();
		}

		/**
		 * @since 5.8.0.3 - Added cache_results and specified the fields return.
		 */
		$all_users = new \WP_User_Query(
			array(
				'cache_results' => false,
				'fields'        => 'ID',
			)
		);

		$all_user_ids = $all_users->get_results();
		$users        = array();

		foreach ( $all_user_ids as $user_id ) {
			$user_xprofile_field_value = xprofile_get_field_data( $field, $user_id );
			if ( false === Automator()->helpers->recipe->buddypress->options->pro->check_field_value( $user_xprofile_field_value, $field_value ) ) {
				$users[] = $user_id;
			}
		}

		return $users;

	}
}
