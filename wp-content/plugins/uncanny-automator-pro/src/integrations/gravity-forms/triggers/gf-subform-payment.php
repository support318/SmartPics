<?php

namespace Uncanny_Automator_Pro;

/**
 * Class GF_SUBFORM_PAYMENT
 *
 * @package Uncanny_Automator_Pro
 */
class GF_SUBFORM_PAYMENT {

	/**
	 * Integration code
	 *
	 * @var string
	 */
	public static $integration = 'GF';

	private $trigger_code;
	private $trigger_meta;

	/**
	 * Set up Automator trigger constructor.
	 */
	public function __construct() {
		$this->trigger_code = 'GFSUBFORMPAYMENT';
		$this->trigger_meta = 'GFFORMS';
		$this->define_trigger();
	}

	/**
	 * Define and register the trigger by pushing it into the Automator object
	 */
	public function define_trigger() {
		$trigger = array(
			'author'              => Automator()->get_author_name( $this->trigger_code ),
			'support_link'        => Automator()->get_author_support_link( $this->trigger_code, 'integration/gravity-forms/' ),
			'is_pro'              => true,
			'integration'         => self::$integration,
			'code'                => $this->trigger_code,
			/* translators: Logged-in trigger - Gravity Forms */
			'sentence'            => sprintf( esc_attr__( 'A user submits {{a form:%1$s}} with payment', 'uncanny-automator-pro' ), $this->trigger_meta ),
			/* translators: Logged-in trigger - Gravity Forms */
			'select_option_name'  => esc_attr__( 'A user submits {{a form}} with payment', 'uncanny-automator-pro' ),
			'action'              => 'gform_post_payment_transaction',
			'priority'            => 20,
			'accepted_args'       => 6,
			'validation_function' => array( $this, 'gform_submit' ),
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
					Automator()->helpers->recipe->gravity_forms->options->list_gravity_forms(),
				),
			)
		);
	}

	/**
	 * Validation function when the trigger action is hit
	 *
	 * @param $txn_id
	 * @param $entry_id
	 * @param $transaction_type
	 * @param $transaction_id
	 * @param $amount
	 * @param $is_recurring
	 */
	public function gform_submit( $txn_id, $entry_id, $transaction_type, $transaction_id, $amount, $is_recurring ) {

		if ( empty( $entry_id ) ) {
			return;
		}

		$entry = \GFAPI::get_entry( $entry_id );

		$user_id = isset( $entry['created_by'] ) && 0 !== absint( $entry['created_by'] ) ? absint( $entry['created_by'] ) : wp_get_current_user()->ID;
		$user_id = apply_filters( 'automator_pro_gravity_forms_user_id', $user_id, $entry, wp_get_current_user() );

		if ( empty( $user_id ) ) {
			$user_id = $entry['created_by'];
		}

		$form_id = isset( $entry->form_id ) ? $entry->form_id : $entry['form_id'];

		$pass_args = array(
			'code'         => $this->trigger_code,
			'meta'         => $this->trigger_meta,
			'post_id'      => $form_id,
			'user_id'      => $user_id,
			'is_signed_in' => true,
		);

		$args = Automator()->maybe_add_trigger_entry( $pass_args, false );

		foreach ( $args as $result ) {
			if ( true === $result['result'] ) {
				$trigger_meta = array(
					'user_id'        => $user_id,
					'trigger_id'     => $result['args']['trigger_id'],
					'trigger_log_id' => $result['args']['trigger_log_id'],
					'run_number'     => $result['args']['run_number'],
				);

				Automator()->db->token->save( 'GFENTRYID', $entry_id, $trigger_meta );
				Automator()->db->token->save( 'TRANS_ID', $transaction_id, $trigger_meta );
				Automator()->db->token->save( 'GFUSERIP', $entry['ip'], $trigger_meta );
				Automator()->db->token->save( 'GFENTRYDATE', \GFCommon::format_date( $entry['date_created'], false, 'Y/m/d' ), $trigger_meta );
				Automator()->db->token->save( 'GFENTRYSOURCEURL', $entry['source_url'], $trigger_meta );
				Automator()->db->token->save( 'TRANSACTIONTYPE', $transaction_type, $trigger_meta );

				Automator()->maybe_trigger_complete( $result['args'] );
			}
		}
	}
}
