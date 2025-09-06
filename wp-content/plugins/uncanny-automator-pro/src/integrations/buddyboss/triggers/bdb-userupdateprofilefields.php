<?php

namespace Uncanny_Automator_Pro;

/**
 * Class BDB_USERUPDATEPROFILEFIELDS
 *
 * @package Uncanny_Automator_Pro
 */
class BDB_USERUPDATEPROFILEFIELDS {

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
	 *
	 * @return void
	 */
	public function __construct() {
		$this->trigger_code = 'BDBUSERUPDATEPROFILEFIELDS';
		$this->trigger_meta = 'BDBUSER';
		$this->define_trigger();
	}

	/**
	 * Define and register the trigger by pushing it into the Automator object
	 *
	 * @return void
	 */
	public function define_trigger() {

		$trigger = array(
			'author'              => Automator()->get_author_name( $this->trigger_code ),
			'support_link'        => Automator()->get_author_support_link( $this->trigger_code, 'integration/buddyboss/' ),
			'integration'         => self::$integration,
			'is_pro'              => true,
			'code'                => $this->trigger_code,
			'meta'                => $this->trigger_meta,
			/* translators: Logged-in trigger - BuddyBoss */
			'sentence'            => sprintf( __( 'A user updates their profile with {{a specific value:%1$s}} in {{a specific field:%2$s}}', 'uncanny-automator-pro' ), 'SUBVALUE:' . $this->trigger_meta, $this->trigger_meta ),
			/* translators: Logged-in trigger - BuddyBoss */
			'select_option_name'  => __( 'A user updates their profile with {{a specific value}} in {{a specific field}}', 'uncanny-automator-pro' ),
			'action'              => 'xprofile_updated_profile',
			'priority'            => 10,
			'accepted_args'       => 5,
			'validation_function' => array( $this, 'bp_user_updated_profile' ),
			'options_callback'    => array( $this, 'load_options' ),
		);

		Automator()->register->trigger( $trigger );
	}

	/**
	 * Load options for this trigger's select fields
	 *
	 * @return array[]
	 */
	public function load_options() {
		return Automator()->utilities->keep_order_of_options(
			array(
				'options_group' => array(
					$this->trigger_meta => array(
						Automator()->helpers->recipe->buddyboss->options->pro->list_all_profile_fields( esc_attr__( 'Field', 'uncanny-automator-pro' ), $this->trigger_meta ),
						Automator()->helpers->recipe->field->text(
							array(
								'option_code' => 'SUBVALUE',
								'label'       => __( 'Value', 'uncanny-automator-pro' ),
								'description' => esc_attr__( 'Enter * to trigger on all values', 'uncanny-automator-pro' ),
							)
						),
					),
				),
			)
		);
	}

	/**
	 *  Validation function when the trigger action is hit
	 *
	 * @param $user_id
	 * @param $posted_field_ids
	 * @param $errors
	 * @param $old_values
	 * @param $new_values
	 *
	 * @return void
	 */
	public function bp_user_updated_profile( $user_id, $posted_field_ids, $errors, $old_values, $new_values ) {

		if ( $errors ) {
			return;
		}

		// Remove all fields that are not actually updated.
		$values = $this->remove_non_updated_fields( $old_values, $new_values );
		// No actual updates were made.
		if ( ! $values ) {
			return;
		}

		// Set any updated values.
		$old_values = $values['old_values'];
		$new_values = $values['new_values'];

		// Check conditions.
		$recipes    = Automator()->get->recipes_from_trigger_code( $this->trigger_code );
		$conditions = $this->match_condition( $user_id, $recipes, $this->trigger_meta, $this->trigger_code, 'SUBVALUE', $new_values );

		if ( empty( $conditions ) ) {
			return;
		}

		foreach ( $conditions['recipe_ids'] as $trigger_id => $recipe_id ) {

			$args = array(
				'code'             => $this->trigger_code,
				'meta'             => $this->trigger_meta,
				'user_id'          => $user_id,
				'ignore_post_id'   => true,
				'is_signed_in'     => true,
				'recipe_to_match'  => $recipe_id,
				'trigger_to_match' => $trigger_id,
			);

			$user_data = get_userdata( $user_id );
			$args      = Automator()->maybe_add_trigger_entry( $args, false );

			// Save trigger meta
			if ( $args ) {
				foreach ( $args as $result ) {
					if ( true === $result['result'] && $result['args']['trigger_id'] && $result['args']['trigger_log_id'] ) {

						$run_number = Automator()->get->trigger_run_number( $result['args']['trigger_id'], $result['args']['trigger_log_id'], $user_id );
						$save_meta  = array(
							'user_id'        => $user_id,
							'trigger_id'     => $result['args']['trigger_id'],
							'run_number'     => $run_number, //get run number
							'trigger_log_id' => $result['args']['trigger_log_id'],
							'ignore_user_id' => true,
						);

						// Add Default Tokens Profile Field and Value.
						$save_meta['meta_key']   = $this->trigger_meta;
						$save_meta['meta_value'] = $conditions['matched'][ $trigger_id ]['field_readable'];
						Automator()->insert_trigger_meta( $save_meta );

						$save_meta['meta_key']   = 'SUBVALUE';
						$save_meta['meta_value'] = $conditions['matched'][ $trigger_id ]['value'];
						Automator()->insert_trigger_meta( $save_meta );

						$save_meta['meta_key']   = 'first_name';
						$save_meta['meta_value'] = $user_data->first_name;
						Automator()->insert_trigger_meta( $save_meta );

						$save_meta['meta_key']   = 'last_name';
						$save_meta['meta_value'] = $user_data->last_name;
						Automator()->insert_trigger_meta( $save_meta );

						$save_meta['meta_key']   = 'useremail';
						$save_meta['meta_value'] = $user_data->user_email;
						Automator()->insert_trigger_meta( $save_meta );

						$save_meta['meta_key']   = 'username';
						$save_meta['meta_value'] = $user_data->user_login;
						Automator()->insert_trigger_meta( $save_meta );

						$save_meta['meta_key']   = 'user_id';
						$save_meta['meta_value'] = $user_data->ID;
						Automator()->insert_trigger_meta( $save_meta );

						Automator()->maybe_trigger_complete( $result['args'] );
					}
				}
			}
		}
	}

	/**
	 * Match condition against selected recipe's triggers.
	 *
	 * @param $user_id
	 * @param null $recipes
	 * @param null $trigger_meta
	 * @param null $trigger_code
	 * @param null $trigger_second_code
	 * @param array $new_values
	 *
	 * @return array|bool
	 */
	public function match_condition( $user_id, $recipes = null, $trigger_meta = null, $trigger_code = null, $trigger_second_code = null, $new_values = array() ) {

		if ( empty( $recipes ) ) {
			return false;
		}

		if ( empty( $new_values ) ) {
			return false;
		}

		$matches    = array();
		$recipe_ids = array();

		foreach ( $recipes as $recipe ) {
			foreach ( $recipe['triggers'] as $trigger ) {
				if ( key_exists( $trigger_meta, $trigger['meta'] ) ) {
					$matches[ $trigger['ID'] ]    = array(
						'field'          => $trigger['meta'][ $trigger_meta ],
						'value'          => $trigger['meta'][ $trigger_second_code ],
						'field_readable' => $trigger['meta'][ "{$trigger_meta}_readable" ],
					);
					$recipe_ids[ $trigger['ID'] ] = $recipe['ID'];
				}
			}
		}

		if ( empty( $matches ) ) {
			return false;
		}

		foreach ( $matches as $recipe_id => $match ) {
			// Required field does not exist in new values.
			if ( ! isset( $new_values[ $match['field'] ] ) ) {
				unset( $recipe_ids[ $recipe_id ] );
				continue;
			}

			// Check if any value is selected.
			if ( '*' === $match['value'] ) {
				continue;
			}

			$new_value = $new_values[ $match['field'] ]['value'];

			// Check if updated array value matches condition value.
			if ( is_array( $new_value ) ) {
				$trigger_match = explode( ',', $match['value'] );
				if ( ! empty( array_diff( $trigger_match, $new_value ) ) ) {
					unset( $recipe_ids[ $recipe_id ] );
					continue;
				}
			}

			// Check if updated value matches condition value.
			if ( $new_value !== $match['value'] ) {
				unset( $recipe_ids[ $recipe_id ] );
			}
		}

		if ( empty( $recipe_ids ) ) {
			return false;
		}

		return array(
			'recipe_ids' => $recipe_ids,
			'result'     => true,
			'matched'    => $matches,
		);
	}

	/**
	 * Remove non-updated fields from the old and new values.
	 *
	 * @param array $old_values
	 * @param array $new_values
	 *
	 * @return array|bool false if no updates were made, or an array of old and new values.
	 */
	public function remove_non_updated_fields( $old_values, $new_values ) {
		foreach ( $old_values as $key => $value ) {
			if ( is_array( $value ) && isset( $value['value'] ) ) {
				$old_value = $value['value'];
				$new_value = $new_values[ $key ]['value'];

				// Cleanse Strings value differences.
				if ( is_string( $new_value ) && is_string( $old_value ) ) {
					// Check URLS for HTML links in old value.
					$old_value = $this->maybe_extract_url( $old_value );
					// Remove trailing time from dates.
					$new_value = $this->maybe_extract_time( $new_value );
					if ( trim( $old_value ) === trim( $new_value ) ) {
						unset( $old_values[ $key ] );
						unset( $new_values[ $key ] );
					}
				}

				// Cleanse Arrays value differences.
				if ( is_array( $new_value ) ) {

					// Check if the old value is an array.
					if ( ! is_array( $old_value ) ) {
						unset( $old_values[ $key ] );
						unset( $new_values[ $key ] );
						continue;
					}

					if ( empty( array_diff( $new_value, $old_value ) ) && empty( array_diff( $old_value, $new_value ) ) ) {
						unset( $old_values[ $key ] );
						unset( $new_values[ $key ] );
					}
				}
			}
		}

		if ( empty( $new_values ) ) {
			return false;
		}

		return array(
			'old_values' => $old_values,
			'new_values' => $new_values,
		);

	}

	/**
	 * Maybe extract the time from date.
	 *
	 * @param string $string
	 *
	 * @return string
	 */
	public function maybe_extract_time( $string ) {
		// Check if the string ends with a time.
		$time = ' 00:00:00';
		if ( substr( $string, -strlen( $time ) ) !== $time ) {
			return $string;
		}
		// Extract the date only.
		return substr( $string, 0, -strlen( $time ) );
	}

	/**
	 * Maybe extract the URL from an HTML link.
	 *
	 * @param string $string
	 *
	 * @return string
	 */
	public function maybe_extract_url( $string ) {
		// Check if the string starts with a link.
		$link = '<a href="';
		if ( substr( $string, 0, strlen( $link ) ) !== $link ) {
			return $string;
		}
		// Extract the URL only.
		$e = new \SimpleXMLElement( $string );
		return (string) $e->attributes()->{'href'};
	}
}
