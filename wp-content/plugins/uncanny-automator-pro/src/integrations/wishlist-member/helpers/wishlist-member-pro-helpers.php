<?php

namespace Uncanny_Automator_Pro;

use Uncanny_Automator\Wishlist_Member_Helpers;

/**
 * Class Wishlist_Member_Pro_Helpers
 * @package Uncanny_Automator_Pro
 */
class Wishlist_Member_Pro_Helpers extends Wishlist_Member_Helpers {

	/**
	 * @var bool
	 */
	public $load_options = true;

	/**
	 * Wishlist_Member_Pro_Helpers constructor.
	 */
	public function __construct() {

		add_action( 'wp_ajax_select_form_fields_WLMFORM', array( $this, 'select_form_fields_func' ) );
	}

	/**
	 * @param \Uncanny_Automator_Pro\Wishlist_Member_Pro_Helpers $pro
	 */
	public function setPro( \Uncanny_Automator_Pro\Wishlist_Member_Pro_Helpers $pro ) {
		$this->pro = $pro;
	}

	/**
	 * @param null $label
	 * @param string $option_code
	 * @param array $args
	 *
	 * @return array|mixed|void
	 */
	public function wm_get_all_forms( $label = null, $option_code = 'WMFORMS', $args = array() ) {

		global $uncanny_automator, $wpdb;
		if ( ! $this->load_options ) {

			return Automator()->helpers->recipe->build_default_options_array( $label, $option_code );
		}

		if ( ! $label ) {
			$label = esc_attr__( 'Form', 'uncanny-automator-pro' );
		}

		$token        = key_exists( 'token', $args ) ? $args['token'] : false;
		$is_ajax      = key_exists( 'is_ajax', $args ) ? $args['is_ajax'] : false;
		$target_field = key_exists( 'target_field', $args ) ? $args['target_field'] : '';
		$end_point    = key_exists( 'endpoint', $args ) ? $args['endpoint'] : '';
		$any          = key_exists( 'any', $args ) ? $args['any'] : false;

		$options = array();
		if ( $any ) {
			$options['-1'] = esc_attr__( 'Any form', 'uncanny-automator-pro' );
		}

		$options['default'] = esc_attr__( 'Default registration form', 'uncanny-automator-pro' );
		$forms              = $wpdb->get_results( "SELECT option_name,option_value FROM `{$wpdb->prefix}wlm_options` WHERE `option_name` LIKE 'CUSTOMREGFORM-%' ORDER BY `option_name` ASC", ARRAY_A );

		foreach ( $forms as $k => $form ) {
			$form_value                        = maybe_unserialize( wlm_serialize_corrector( $form['option_value'] ) );
			$all_forms[ $form['option_name'] ] = $form_value['form_name'];
		}

		if ( ! empty( $all_forms ) ) {
			foreach ( $all_forms as $key => $form ) {
				$options[ $key ] = $form;
			}
		}

		$option = array(
			'option_code'     => $option_code,
			'label'           => $label,
			'input_type'      => 'select',
			'required'        => true,
			'supports_tokens' => $token,
			'is_ajax'         => $is_ajax,
			'fill_values_in'  => $target_field,
			'endpoint'        => $end_point,
			'options'         => $options,
			'relevant_tokens' => array(
				$option_code => esc_attr__( 'Form', 'uncanny-automator' ),
			),
		);

		return apply_filters( 'uap_option_wm_get_all_forms', $option );
	}

	/**
	 * Return all the specific fields of a form ID provided in ajax call
	 */
	public function select_form_fields_func() {

		global $uncanny_automator, $wpdb;

		Automator()->utilities->ajax_auth_check( $_POST );

		$fields = array();
		if ( automator_filter_has_var( 'value', INPUT_POST ) ) {

			if ( automator_filter_input( 'value', INPUT_POST ) != 'default' ) {
				$form        = $wpdb->get_var( $wpdb->prepare( "SELECT option_value FROM `{$wpdb->prefix}wlm_options` WHERE `option_name` LIKE %s ORDER BY `option_name` ASC", '%%' . automator_filter_input( 'value', INPUT_POST ) . '%%' ) );
				$form_value  = maybe_unserialize( wlm_serialize_corrector( $form ) );
				$form_fields = $form_value['form_dissected']['fields'];
				if ( is_array( $form_fields ) ) {
					foreach ( $form_fields as $field ) {
						if ( $field['attributes']['type'] != 'password' ) {
							$fields[] = array(
								'value' => $field['attributes']['name'],
								'text'  => str_replace( ':', '', $field['label'] ),
							);
						}
					}
				}
			} elseif ( automator_filter_input( 'value', INPUT_POST ) == 'default' ) {
				$form_fields = $this->get_form_fields();
				if ( is_array( $form_fields ) ) {
					foreach ( $form_fields as $key => $field ) {
						$fields[] = array(
							'value' => $key,
							'text'  => $field,
						);
					}
				}
			}
		}
		echo wp_json_encode( $fields );
		die();
	}

	/**
	 * @return mixed|void
	 */
	public function get_form_fields() {

		$fields = array(
			'firstname' => __( 'First name', 'uncanny-automator' ),
			'lastname'  => __( 'Last name', 'uncanny-automator' ),
			'email'     => __( 'Email', 'uncanny-automator' ),
			'username'  => __( 'Username', 'uncanny-automator' ),
		);

		return apply_filters( 'automator_wm_default_form_field', $fields );
	}

	/**
	 * Get Membership Select Condition field args.
	 *
	 * @param string $option_code - The option code identifier.
	 *
	 * @return array
	 */
	public function get_membership_condition_field_args( $option_code ) {
		return array(
			'option_code'           => $option_code,
			'label'                 => esc_html__( 'Membership', 'uncanny-automator-pro' ),
			'required'              => true,
			'options'               => $this->get_membership_conditions_options(),
			'supports_custom_value' => true,
		);
	}

	/**
	 * Get Membership Select Condition options.
	 *
	 * @return array
	 */
	public function get_membership_conditions_options() {
		if ( ! function_exists( 'wlmapi_get_levels' ) ) {
			return array();
		}
		// Get the cached options.
		static $condition_options = null;
		if ( ! is_null( $condition_options ) ) {
			return $condition_options;
		}

		$levels            = wlmapi_get_levels();
		$levels            = isset( $levels['levels']['level'] ) ? $levels['levels']['level'] : array();
		$condition_options = array();
		if ( ! empty( $levels ) ) {

			usort(
				$levels,
				function ( $a, $b ) {
					return strcmp( $a['name'], $b['name'] );
				}
			);

			// Add any membership option.
			$condition_options[] = array(
				'value' => - 1,
				'text'  => esc_html__( 'Any membership', 'uncanny-automator-pro' ),
			);

			foreach ( $levels as $level ) {
				$condition_options[] = array(
					'value' => $level['id'],
					'text'  => $level['name'],
				);
			}
		}

		return $condition_options;
	}

	/**
	 * Evaluate the condition
	 *
	 * @param $membership_id - WP_Post ID of the membership plan
	 * @param $user_id - WP_User ID
	 *
	 * @return bool
	 */
	public function evaluate_condition_check( $membership_id, $user_id ) {

		$levels = wlmapi_get_member_levels( $user_id );

		// No Memberships.
		if ( empty( $levels ) ) {
			return false;
		}

		// Any Membership.
		if ( $membership_id < 0 ) {
			foreach ( $levels as $level ) {
				if ( isset( $level->Active ) && $level->Active ) {
					return true;
				}
			}

			return false;
		}

		// Specific Membership.
		if ( key_exists( $membership_id, $levels ) ) {
			$level = $levels[ $membership_id ];

			return isset( $level->Active ) && $level->Active;
		}

		return false;
	}

}
