<?php

namespace Uncanny_Automator_Pro;

use Uncanny_Automator\Divi_Helpers;

/**
 * Divi submit form specific field trigger
 */
class DIVI_SUBMIT_FORM_FIELD {

	/**
	 * Integration code
	 *
	 * @var string
	 */
	public static $integration = 'DIVI';

	/**
	 * Trigger Code
	 *
	 * @var string
	 */
	private $trigger_code;
	/**
	 * Trigger Meta
	 *
	 * @var string
	 */
	private $trigger_meta;

	/**
	 * Set up Automator trigger constructor.
	 */
	public function __construct() {
		if ( ! method_exists( '\Uncanny_Automator\Divi_Helpers', 'resolve_form_id' ) ) {
			return;
		}

		$this->trigger_code = 'DIVI_SUBMIT_FORM_FIELD';
		$this->trigger_meta = 'DIVIFORM';
		$this->define_trigger();
	}

	/**
	 * Define and register the trigger by pushing it into the Automator object
	 */
	public function define_trigger() {

		$trigger = array(
			'author'              => Automator()->get_author_name(),
			'support_link'        => Automator()->get_author_support_link( $this->trigger_code, 'integration/divi/' ),
			'integration'         => self::$integration,
			'code'                => $this->trigger_code,
			'is_pro'              => true,
			'sentence'            => sprintf(
			/* translators: Logged-in trigger - Divi */
				esc_attr__( 'A user submits {{a form:%1$s}} with {{a value:%2$s}} in {{a field:%3$s}}', 'uncanny-automator-pro' ),
				$this->trigger_meta,
				'SUBVALUE:' . $this->trigger_meta,
				$this->trigger_code . ':' . $this->trigger_meta
			),
			/* translators: Logged-in trigger - Divi */
			'select_option_name'  => esc_attr__( 'A user submits {{a form}} with {{a value}} in {{a field}}', 'uncanny-automator-pro' ),
			'action'              => 'et_pb_contact_form_submit',
			'priority'            => 100,
			'accepted_args'       => 3,
			'validation_function' => array( $this, 'divi_form_submitted' ),
			'options_callback'    => array( $this, 'load_options' ),
		);

		Automator()->register->trigger( $trigger );
	}

	/**
	 * @return array
	 */
	public function load_options() {
		$options = array(
			'options_group' => array(
				$this->trigger_meta => array(
					Automator()->helpers->recipe->divi->options->all_divi_forms(
						null,
						$this->trigger_meta,
						array(
							'token'             => false,
							'is_ajax'           => true,
							'target_field'      => $this->trigger_code,
							'endpoint'          => 'select_form_fields_DIVIFORMS',
							'uo_update_form_id' => true,
							'uo_include_any'    => true,
						)
					),
					Automator()->helpers->recipe->field->select_field( $this->trigger_code, __( 'Field', 'uncanny-automator-pro' ) ),
					Automator()->helpers->recipe->field->text_field( 'SUBVALUE', __( 'Value', 'uncanny-automator-pro' ) ),
				),
			),
		);

		return Automator()->utilities->keep_order_of_options( $options );
	}

	/**
	 * Form submission handler
	 *
	 * @param $fields_values
	 * @param $et_contact_error
	 * @param $contact_form_info
	 */
	public function divi_form_submitted( $fields_values, $et_contact_error, $contact_form_info ) {

		if ( ! is_user_logged_in() ) {
			return;
		}
		if ( true === $et_contact_error ) {
			return;
		}
		// If the form doesn't have the contact_form_unique_id, return
		if ( ! isset( $contact_form_info['contact_form_unique_id'] ) ) {
			return;
		}

		$user_id            = wp_get_current_user()->ID;
		$recipes            = Automator()->get->recipes_from_trigger_code( $this->trigger_code );
		$matched_recipes    = Divi_Helpers::match_condition_v2( $contact_form_info, $recipes, $this->trigger_meta );
		$matched_conditions = Divi_Pro_Helpers::match_pro_condition_v2( $fields_values, $matched_recipes, $recipes, $this->trigger_meta, $this->trigger_code, 'SUBVALUE' );

		if ( empty( $matched_conditions ) ) {
			return;
		}

		foreach ( $matched_conditions['recipe_ids'] as $recipe_id ) {
			$args = array(
				'code'             => $this->trigger_code,
				'meta'             => $this->trigger_meta,
				'recipe_to_match'  => $recipe_id['recipe_id'],
				'trigger_to_match' => $recipe_id['trigger_id'],
				'ignore_post_id'   => true,
				'user_id'          => $user_id,
			);

			$args = Automator()->process->user->maybe_add_trigger_entry( $args, false );

			if ( empty( $args ) ) {
				continue;
			}

			$form_id = $matched_conditions['form_id'];
			if ( intval( '-1' ) !== intval( $form_id ) ) {
				$unique_id = $contact_form_info['contact_form_unique_id'];
				$post_id   = $contact_form_info['post_id'];
				$form_id   = "$post_id-$unique_id";
			}
			foreach ( $args as $result ) {
				if ( isset( $result['args'] ) && ! empty( $result['args'] ) && is_array( $result['args'] ) ) {
					Divi_Helpers::save_tokens( $result, $fields_values, $form_id, $this->trigger_meta, $user_id );
					Automator()->complete->trigger( $result['args'] );
				}
			}
		}
	}
}
