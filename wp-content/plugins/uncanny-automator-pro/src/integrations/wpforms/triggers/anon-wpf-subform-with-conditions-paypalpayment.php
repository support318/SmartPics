<?php

namespace Uncanny_Automator_Pro;

/**
 * Class ANON_WPF_SUBFORM_WITH_CONDITIONS_PAYPALPAYMENT
 *
 * @package Uncanny_Automator_Pro
 */
class ANON_WPF_SUBFORM_WITH_CONDITIONS_PAYPALPAYMENT {

	/**
	 * Integration code
	 *
	 * @var string
	 */
	public static $integration = 'WPF';

	/**
	 * @var string
	 */
	private $trigger_code;
	/**
	 * @var string
	 */
	private $trigger_meta;

	/**
	 * Set up Automator trigger constructor.
	 */
	public function __construct() {
		$this->trigger_code = 'ANONWPFSUBFORMWITHPAYPALPAYMENTS';
		$this->trigger_meta = 'ANONWPFFORMS';
		$this->define_trigger();

	}

	/**
	 * Define and register the trigger by pushing it into the Automator object
	 */
	public function define_trigger() {
		$trigger = array(
			'author'              => Automator()->get_author_name(),
			'support_link'        => Automator()->get_author_support_link( $this->trigger_code, 'integration/wp-forms/' ),
			'is_pro'              => true,
			'integration'         => self::$integration,
			'code'                => $this->trigger_code,
			'meta'                => $this->trigger_meta,
			'sentence'            => sprintf(
			/* translators: Anonymous trigger - WP Forms */
				esc_attr__( '{{A form:%1$s}} is submitted with {{a specific value:%2$s}} in {{a specific field:%3$s}} with PayPal payment', 'uncanny-automator-pro' ),
				$this->trigger_meta,
				'SUBVALUE:' . $this->trigger_meta,
				$this->trigger_code . ':' . $this->trigger_meta
			),
			/* translators: Anonymous trigger - WP Forms */
			'select_option_name'  => esc_attr__( '{{A form}} is submitted with {{a specific value}} in {{a specific field}} with PayPal payment', 'uncanny-automator-pro' ),
			'action'              => array(
				'wpforms_paypal_standard_process_complete',
				'wpforms_paypal_commerce_process_update_entry_meta',
			),
			'type'                => 'anonymous',
			'priority'            => 10,
			'accepted_args'       => 4,
			'validation_function' => array( $this, 'wpform_submit_with_paypal' ),
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
					Automator()->helpers->recipe->wpforms->options->list_wp_forms(
						null,
						$this->trigger_meta,
						array(
							'token'        => false,
							'is_ajax'      => true,
							'target_field' => $this->trigger_code,
							'endpoint'     => 'select_form_fields_ANONWPFFORMS',
						)
					),
					Automator()->helpers->recipe->field->select_field( $this->trigger_code, __( 'Field', 'uncanny-automator-pro' ) ),
					Automator()->helpers->recipe->field->text_field( 'SUBVALUE', __( 'Value', 'uncanny-automator-pro' ) ),
				),
			),
		);

		$options = Automator()->utilities->keep_order_of_options( $options );

		return $options;
	}

	/**
	 * Validation function when the trigger action is hit
	 *
	 * @param array $fields fields array.
	 * @param array $entry errors array.
	 * @param array $form_data form object.
	 * @param string $entry_id other settings.
	 */
	public function wpform_submit_with_paypal( $fields, $form_data, $payment_id, $data ) {

		$recipes = Automator()->get->recipes_from_trigger_code( $this->trigger_code );

		$get_entry = wpforms()->get( 'entry' )->get( $payment_id );
		$fields    = wpforms_decode( $get_entry->fields );

		$entry['form_id'] = $get_entry->form_id;
		$entry['fields']  = $fields;

		$conditions = Automator()->helpers->recipe->wpforms->pro->match_condition( $entry, $recipes, $this->trigger_meta, $this->trigger_code, 'SUBVALUE' );

		if ( ! $conditions ) {
			return;
		}

		$user_id  = get_current_user_id();
		$triggers = array();
		if ( ! empty( $conditions ) ) {
			foreach ( $conditions['recipe_ids'] as $trigger_id => $recipe_id ) {
				if ( ! Automator()->is_recipe_completed( $recipe_id, $user_id ) ) {
					$args = array(
						'code'             => $this->trigger_code,
						'meta'             => $this->trigger_meta,
						'recipe_to_match'  => $recipe_id,
						'trigger_to_match' => $trigger_id,
						'ignore_post_id'   => true,
						'user_id'          => $user_id,
					);

					$triggers[] = Automator()->maybe_add_trigger_entry( $args, false );
				}
			}
		}

		if ( ! empty( $triggers ) ) {
			foreach ( $triggers as $args ) {
				if ( $args ) {
					do_action( 'automator_save_anon_wp_form', $fields, $form_data, $recipes, $args );
					foreach ( $args as $r ) {
						if ( true === $r['result'] ) {
							if ( isset( $r['args'] ) && isset( $r['args']['trigger_log_id'] ) ) {
								$user_ip    = Automator()->helpers->recipe->wpforms->options->pro->get_entry_user_ip_address( $entry_id );
								$entry_date = Automator()->helpers->recipe->wpforms->options->pro->get_entry_entry_date( $entry_id );
								$entry_id   = Automator()->helpers->recipe->wpforms->options->pro->get_entry_entry_id( $entry_id );
								//Saving form values in trigger log meta for token parsing!
								$wpf_args               = array(
									'trigger_id'     => (int) $r['args']['trigger_id'],
									'user_id'        => $user_id,
									'trigger_log_id' => $r['args']['trigger_log_id'],
									'run_number'     => $r['args']['run_number'],
								);
								$wpf_args['meta_key']   = 'WPFENTRYID';
								$wpf_args['meta_value'] = $entry_id;
								Automator()->insert_trigger_meta( $wpf_args );

								$wpf_args['meta_key']   = 'WPFENTRYIP';
								$wpf_args['meta_value'] = $user_ip;
								Automator()->insert_trigger_meta( $wpf_args );

								$wpf_args['meta_key']   = 'WPFENTRYDATE';
								$wpf_args['meta_value'] = maybe_serialize( Automator()->helpers->recipe->wpforms->options->get_entry_date( $entry_date ) );
								Automator()->insert_trigger_meta( $wpf_args );
							}
							Automator()->maybe_trigger_complete( $r['args'] );
						}
					}
				}
			}
		}

	}
}
