<?php

namespace Uncanny_Automator_Pro;

/**
 * Class BP_SETUSERPROFILEDATA
 *
 * @package Uncanny_Automator_Pro
 */
class BP_SETUSERPROFILEDATA {

	/**
	 * Integration code
	 *
	 * @var string
	 */
	public static $integration = 'BP';

	private $action_code;
	private $action_meta;

	/**
	 * SetAutomatorTriggers constructor.
	 */
	public function __construct() {
		$this->action_code = 'BPSETUSERPROFILEDATA';
		$this->action_meta = 'BPPROFILE';

		$this->define_action();
	}

	/**
	 * Define and register the action by pushing it into the Automator object.
	 */
	public function define_action() {

		$action = array(
			'author'             => Automator()->get_author_name(),
			'support_link'       => Automator()->get_author_support_link( $this->action_code, 'integration/buddypress/' ),
			'is_pro'             => true,
			'integration'        => self::$integration,
			'code'               => $this->action_code,
			/* translators: Action - BuddyPress */
			'sentence'           => sprintf( __( "Set the user's {{Xprofile data:%1\$s}}", 'uncanny-automator-pro' ), $this->action_meta ),
			/* translators: Action - BuddyPress */
			'select_option_name' => __( "Set the user's {{Xprofile data}}", 'uncanny-automator-pro' ),
			'priority'           => 10,
			'accepted_args'      => 1,
			'execution_function' => array( $this, 'update_user_profile_data' ),
			'options_callback'   => array( $this, 'load_options' ),
		);

		Automator()->register->action( $action );
	}

	/**
	 * @return array[]
	 */
	public function load_options() {
		return Automator()->utilities->keep_order_of_options(
			array(
				'options_group' => array(
					$this->action_meta => array(
						array(
							'input_type'        => 'repeater',
							'relevant_tokens'   => array(),
							'option_code'       => 'BPPROFILEDATA',
							'label'             => '',
							'required'          => true,
							'fields'            => array(
								Automator()->helpers->recipe->buddypress->options->pro->list_all_profile_fields( esc_attr__( 'Field', 'uncanny-automator-pro' ), 'BPUSERFIELD', array( 'is_repeater' => true ) ),
								array(
									'input_type'      => 'text',
									'option_code'     => 'VALUE',
									'label'           => esc_attr__( 'Value', 'uncanny-automator-pro' ),
									'supports_tokens' => true,
									'required'        => false,
								),
							),
							/* translators: Non-personal infinitive verb */
							'add_row_button'    => esc_attr__( 'Add pair', 'uncanny-automator-pro' ),
							/* translators: Non-personal infinitive verb */
							'remove_row_button' => esc_attr__( 'Remove pair', 'uncanny-automator-pro' ),
						),
					),
				),
			)
		);
	}

	/**
	 * Update user profile type
	 *
	 * @param string $user_id
	 * @param array $action_data
	 * @param string $recipe_id
	 *
	 * @since 1.1
	 * @return void
	 */
	public function update_user_profile_data( $user_id, $action_data, $recipe_id, $args ) {

		// Bail required function doesn't exist.
		if ( ! function_exists( 'xprofile_set_field_data' ) ) {
			$action_data['complete_with_errors'] = true;
			Automator()->complete_action( $user_id, $action_data, $recipe_id, __( 'xprofile_set_field_data function does not exist.', 'uncanny-automator-pro' ) );
			return;
		}

		$user_fields_data = $action_data['meta']['BPPROFILEDATA'];

		// Set Variables in case we need to do xprofile_updated_profile action.
		$posted_field_ids = array();
		$old_values       = array();
		$new_values       = array();

		// Adding other users
		if ( ! empty( $user_fields_data ) ) {
			$user_selectors = json_decode( $user_fields_data, true );
			if ( ! empty( $user_selectors ) ) {
				foreach ( $user_selectors as $user_selector ) {
					$field_id = $user_selector['BPUSERFIELD'];
					if ( ! empty( $user_selector['VALUE'] ) ) {
						$value = Automator()->parse->text( $user_selector['VALUE'], $recipe_id, $user_id, $args );

						// Format value based on field type.
						$value = $this->maybe_format_array_values( $value, $field_id );
						$value = apply_filters( 'automator_buddyboss_set_user_profile_data_value', $value, $field_id, $user_id, $recipe_id, $args );

						// Populate variables for do xprofile_updated_profile action.
						$field_array             = array(
							'value'      => xprofile_get_field_data( $field_id, $user_id ),
							'visibility' => xprofile_get_field_visibility_level( $field_id, $user_id ),
						);
						$posted_field_ids[]      = $field_id;
						$old_values[ $field_id ] = $field_array;
						$field_array['value']    = $value;
						$new_values[ $field_id ] = $field_array;

						// Update Field.
						xprofile_set_field_data( $field_id, $user_id, $value );
					}
				}
			}
		}

		// Maybe do xprofile_updated_profile action.
		if ( empty( did_action( 'xprofile_updated_profile' ) ) && ! empty( $posted_field_ids ) ) {
			do_action( 'xprofile_updated_profile', $user_id, $posted_field_ids, false, $old_values, $new_values );
		}

		Automator()->complete_action( $user_id, $action_data, $recipe_id );
	}

	/**
	 * Convert csv string to arrays based on field type.
	 *
	 * @param mixed $value
	 * @param int $field_id
	 *
	 * @return string|array
	 */
	private function maybe_format_array_values( $value, $field_id ) {

		$field = xprofile_get_field( $field_id );
		if ( ! is_a( $field, 'BP_XProfile_Field' ) ) {
			return $value;
		}

		$is_array_type = apply_filters(
			'automator_buddyboss_xprofile_field_is_array',
			array(
				'checkbox',
				'multiselectbox',
				'socialnetworks',
			)
		);

		if ( in_array( $field->type, $is_array_type ) ) {
			$value = ! is_array( $value ) ? explode( ',', $value ) : $value;
		}

		return $value;
	}

}
