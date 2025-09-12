<?php

namespace Uncanny_Automator_Pro;

/**
 * Class FI_SUBMITFORM_PAYMENT
 *
 * @package Uncanny_Automator_Pro
 */
class FI_SUBMITFORM_PAYMENT {

	/**
	 * Integration code
	 *
	 * @var string
	 */
	public static $integration = 'FI';

	private $trigger_code;
	private $trigger_meta;

	/**
	 * Set up Automator trigger constructor.
	 */
	public function __construct() {
		if ( class_exists( 'FrmPaymentsController' ) ) {
			$this->trigger_code = 'FISUBMITFORMPAYMENT';
			$this->trigger_meta = 'FIFORM';
			$this->define_trigger();
		}
	}

	/**
	 * Define and register the trigger by pushing it into the Automator object
	 */
	public function define_trigger() {
		$trigger = array(
			'author'              => Automator()->get_author_name(),
			'support_link'        => Automator()->get_author_support_link( $this->trigger_code, 'integration/formidable-forms/' ),
			'is_pro'              => true,
			'integration'         => self::$integration,
			'code'                => $this->trigger_code,
			/* translators: Logged-in trigger - Formidable */
			'sentence'            => sprintf( __( 'A user submits {{a form:%1$s}} with payment', 'uncanny-automator-pro' ), $this->trigger_meta ),
			/* translators: Logged-in trigger - Formidable */
			'select_option_name'  => __( 'A user submits {{a form}} with payment', 'uncanny-automator-pro' ),
			'action'              => 'frm_payment_paypal_ipn',
			'priority'            => 10,
			'accepted_args'       => 1,
			'validation_function' => array( $this, 'fi_submit_payment_form' ),
			'options_callback'    => array( $this, 'load_options' ),
		);

		Automator()->register->trigger( $trigger );
	}

	/**
	 * @return array[]
	 */
	public function load_options() {
		return Automator()->utilities->keep_order_of_options(
			array(
				'options' => array(
					Automator()->helpers->recipe->formidable->options->all_formidable_forms( null, $this->trigger_meta ),
				),
			)
		);
	}

	/**
	 * Validation function when the trigger action is hit
	 *
	 * @param object $params params array.
	 */
	public function fi_submit_payment_form( $params ) {

		if ( isset( $params['pay_vars'] ) && isset( $params['pay_vars']['completed'] ) && 1 == $params['pay_vars']['completed'] ) {

			$entry   = $params['entry'];
			$user_id = $entry->user_id;
			if ( empty( $user_id ) ) {
				return;
			}

			$args = array(
				'code'         => $this->trigger_code,
				'meta'         => $this->trigger_meta,
				'post_id'      => intval( $entry->form_id ),
				'user_id'      => intval( $user_id ),
				'is_signed_in' => true,
			);

			$result = Automator()->maybe_add_trigger_entry( $args, false );

			if ( $result ) {
				foreach ( $result as $r ) {
					if ( true === $r['result'] ) {
						if ( isset( $r['args'] ) && isset( $r['args']['trigger_log_id'] ) ) {
							//Saving form values in trigger log meta for token parsing!
							$entry_id = $entry->entry_id;
							$fi_args  = array(
								'trigger_id'     => (int) $r['args']['trigger_id'],
								'user_id'        => $user_id,
								'trigger_log_id' => $r['args']['trigger_log_id'],
								'run_number'     => $r['args']['run_number'],
							);

							$fi_args['meta_key'] = $this->trigger_meta;
							Automator()->helpers->recipe->formidable->pro->extract_save_fi_fields( $entry->entry_id, $entry->form_id, $fi_args );

							$fi_args['meta_key']   = 'FIENTRYID';
							$fi_args['meta_value'] = $entry_id;
							Automator()->insert_trigger_meta( $fi_args );

							global $wpdb;
							$entries     = $wpdb->get_row( $wpdb->prepare( "SELECT it.*, fr.name as form_name, fr.form_key as form_key FROM {$wpdb->prefix}frm_items it LEFT OUTER JOIN {$wpdb->prefix}frm_forms fr ON it.form_id=fr.id WHERE it.id = %d", $entry_id ) );
							$description = json_decode( $entries->description );

							$fi_args['meta_key']   = 'FIUSERIP';
							$fi_args['meta_value'] = maybe_serialize( $entries->ip );
							Automator()->insert_trigger_meta( $fi_args );

							$date_format           = __( 'M j, Y @ G:i', 'formidable' );
							$fi_args['meta_key']   = 'FIENTRYDATE';
							$fi_args['meta_value'] = maybe_serialize( \FrmAppHelper::get_localized_date( $date_format, $entries->created_at ) );
							Automator()->insert_trigger_meta( $fi_args );

							$fi_args['meta_key']   = 'FIENTRYSOURCEURL';
							$fi_args['meta_value'] = maybe_serialize( $description->referrer );
							Automator()->insert_trigger_meta( $fi_args );
						}
						Automator()->maybe_trigger_complete( $r['args'] );
					}
				}
			}
		}
	}

}
