<?php

namespace Uncanny_Automator_Pro;

use Uncanny_Automator\Divi_Helpers;

/**
 * Divi Pro Helpers
 */
class Divi_Pro_Helpers extends Divi_Helpers {
	/**
	 * Save options
	 *
	 * @var
	 */
	public $options;

	/**
	 * Divi Pro Helper instance
	 *
	 * @var Divi_Pro_Helpers
	 */
	public $pro;

	/**
	 * Check if load options is set
	 *
	 * @var bool
	 */
	public $load_options = true;

	/**
	 * Divi_Pro_Helpers Constructor
	 */
	public function __construct() {
		// Selectively load options

		add_action( 'wp_ajax_select_form_fields_ANONDIVIFORMS', array( $this, 'select_form_fields_func' ) );
		add_action( 'wp_ajax_select_form_fields_DIVIFORMS', array( $this, 'select_form_fields_func' ) );
	}

	/**
	 * Set Pro Instance
	 *
	 * @param Divi_Pro_Helpers $pro
	 */
	public function setPro( Divi_Pro_Helpers $pro ) { //phpcs:ignore Generic.CodeAnalysis.UselessOverridingMethod.Found
		parent::setPro( $pro );
	}

	/**
	 * Return all the specific fields of a form ID provided in ajax call
	 */
	public function select_form_fields_func() {
		if ( ! class_exists( '\Uncanny_Automator\Divi_Helpers' ) ) {
			echo wp_json_encode( array() );
			die();
		}

		Automator()->utilities->ajax_auth_check();

		$fields       = array();
		$form_id      = sanitize_text_field( automator_filter_input( 'value', INPUT_POST ) );
		$target_field = sanitize_text_field( automator_filter_input( 'target_field', INPUT_POST ) );
		$form_fields  = Divi_Helpers::get_form_by_id( $form_id );
		if ( in_array( $target_field, array( 'ANON_DIVI_SUBMIT_FORM_FIELD', 'DIVI_SUBMIT_FORM_FIELD' ), true ) ) {
			$form_fields = Divi_Helpers::get_form_by_id( $form_id, true );
		}
		if ( empty( $form_fields ) ) {
			echo wp_json_encode( $fields );
			die();
		}
		foreach ( $form_fields as $form_field ) {
			$input_id = $form_field['field_id'];
			$fields[] = array(
				'value' => $input_id,
				'text'  => $form_field['field_title'],
			);
		}

		echo wp_json_encode( $fields );
		die();
	}

	/**
	 * Match Pro conditions
	 *
	 * @param $data
	 * @param $form_id
	 * @param null $recipes
	 * @param null $trigger_meta
	 * @param null $trigger_code
	 * @param null $trigger_second_code
	 *
	 * @return array|false
	 */
	public static function match_pro_condition( $data, $form_id, $recipes = null, $trigger_meta = null, $trigger_code = null, $trigger_second_code = null ) {
		if ( null === $recipes ) {
			return false;
		}

		$matches        = array();
		$recipe_ids     = array();
		$entry_to_match = $form_id;

		foreach ( $recipes as $recipe ) {
			foreach ( $recipe['triggers'] as $trigger ) {
				if ( ! array_key_exists( $trigger_meta, $trigger['meta'] ) ) {
					continue;
				}

				if ( ( (string) $trigger['meta'][ $trigger_meta ] === (string) $entry_to_match ) || ( intval( '-1' ) === intval( $trigger['meta'][ $trigger_meta ] ) ) ) {

					if ( isset( $trigger['meta'][ $trigger_code ] ) && isset( $trigger['meta'][ $trigger_second_code ] ) ) {
						$matches[ $trigger['ID'] ]    = array(
							'field' => $trigger['meta'][ $trigger_code ],
							'value' => $trigger['meta'][ $trigger_second_code ],
						);
						$recipe_ids[ $trigger['ID'] ] = $recipe['ID'];
					}
				}
			}
		}
		if ( empty( $matches ) ) {
			return false;
		}

		//Try to match value with submitted to isolate recipe ids matched
		foreach ( $matches as $recipe_id => $match ) {
			foreach ( $data as $meta_key => $meta ) {

				if ( $match['field'] !== $meta_key ) {
					continue;
				}
				if ( is_array( $meta ) ) {
					$trigger_match = explode( ',', $match['value'] );
					if ( ! empty( array_diff( $trigger_match, $meta ) ) ) {
						unset( $recipe_ids[ $recipe_id ] );
					}
				} else {
					if ( $meta !== $match['value'] ) {
						unset( $recipe_ids[ $recipe_id ] );
					}
				}
			}
		}

		if ( ! empty( $recipe_ids ) ) {
			return array(
				'recipe_ids' => $recipe_ids,
				'result'     => true,
			);
		}

		return false;
	}

	/**
	 * @param $data
	 * @param $matched_recipes
	 * @param $recipes
	 * @param $trigger_meta
	 * @param $trigger_code
	 * @param $trigger_second_code
	 *
	 * @return false|array
	 */
	public static function match_pro_condition_v2( $data, $matched_recipes, $recipes = null, $trigger_meta = null, $trigger_code = null, $trigger_second_code = null ) {
		if ( null === $recipes ) {
			return false;
		}

		foreach ( $matched_recipes['recipe_ids'] as $recipe_id => $matched_recipe ) {
			foreach ( $recipes as $recipe ) {
				if ( $recipe['ID'] === $recipe_id ) {
					foreach ( $recipe['triggers'] as $trigger ) {
						if ( $matched_recipe['trigger_id'] === $trigger['ID'] ) {
							if ( isset( $trigger['meta'][ $trigger_code ] ) && isset( $trigger['meta'][ $trigger_second_code ] ) ) {
								foreach ( $data as $meta_key => $meta ) {
									if ( $trigger['meta'][ $trigger_code ] !== $meta_key ) {
										continue;
									}

									if ( is_array( $meta ) ) {
										$trigger_match = explode( ',', $trigger['meta'][ $trigger_second_code ] );
										if ( ! empty( array_diff( $trigger_match, $meta ) ) ) {
											unset( $matched_recipes['recipe_ids'][ $recipe_id ] );
										}
									} else {
										if ( $meta !== $trigger['meta'][ $trigger_second_code ] ) {
											unset( $matched_recipes['recipe_ids'][ $recipe_id ] );
										}
									}
								}
							}
						}
					}
				}
			}
		}

		if ( empty( $matched_recipes['recipe_ids'] ) ) {
			return false;
		}

		return $matched_recipes;
	}
}
