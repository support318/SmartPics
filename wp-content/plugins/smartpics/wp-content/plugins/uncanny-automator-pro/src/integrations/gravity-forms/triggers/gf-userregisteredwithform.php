<?php

namespace Uncanny_Automator_Pro;

/**
 * Class GF_USERREGISTERED
 *
 * @package Uncanny_Automator_Pro
 */
class GF_USERREGISTEREDWITHFORM {

	/**
	 * @var string
	 */
	private $trigger_code;
	/**
	 * @var string
	 */
	private $trigger_meta;

	/**
	 * SetAutomatorTriggers constructor.
	 */
	public function __construct() {
		$this->trigger_code = 'GFUSERCREATEDWITHFORM';
		$this->trigger_meta = 'GFFORMS';
		if ( defined( 'GF_USER_REGISTRATION_VERSION' ) ) {
			$this->define_trigger();
		}
	}

	/**
	 *
	 */
	public function define_trigger() {
		$trigger = array(
			'author'              => Automator()->get_author_name( $this->trigger_code ),
			'support_link'        => Automator()->get_author_support_link( $this->trigger_code, 'integration/gravity-forms/' ),
			'is_pro'              => true,
			'integration'         => 'GF',
			'code'                => $this->trigger_code,
			'meta'                => $this->trigger_meta,
			/* translators: Logged-in trigger - Gravity Forms */
			'sentence'            => sprintf( esc_attr__( 'A user registers with {{a form:%1$s}}', 'uncanny-automator-pro' ), $this->trigger_meta ),
			/* translators: Logged-in trigger - Gravity Forms */
			'select_option_name'  => esc_attr__( 'A user registers with {{a form}}', 'uncanny-automator-pro' ),
			'action'              => 'gform_user_registered',
			'priority'            => 10,
			'accepted_args'       => 4,
			'validation_function' => array( $this, 'save_data' ),
			'options_callback'    => array( $this, 'load_options' ),
		);

		Automator()->register->trigger( $trigger );
	}

	/**
	 * @return array
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
	 * @param $user_id
	 * @param $feed
	 * @param $entry
	 * @param $password
	 */
	public function save_data( $user_id, $feed, $entry, $password ) {

		$feed_addon = isset( $feed['addon_slug'] ) ? $feed['addon_slug'] : '';
		// Bail if not coming from user registration
		if ( 'gravityformsuserregistration' !== $feed_addon ) {
			return;
		}

		$form_id   = $feed['form_id'];
		$pass_args = array(
			'code'         => $this->trigger_code,
			'meta'         => $this->trigger_meta,
			'post_id'      => $form_id,
			'user_id'      => $user_id,
			'is_signed_in' => true,
		);

		$args = Automator()->process->user->maybe_add_trigger_entry( $pass_args, false );

		if ( ! empty( $args ) ) {
			foreach ( $args as $result ) {
				if ( true === $result['result'] ) {
					$trigger_meta = array(
						'user_id'        => $user_id,
						'trigger_id'     => $result['args']['trigger_id'],
						'trigger_log_id' => $result['args']['trigger_log_id'],
						'run_number'     => $result['args']['run_number'],
					);

					$trigger_meta['meta_key']   = 'GFENTRYID';
					$trigger_meta['meta_value'] = $entry['id'];
					Automator()->insert_trigger_meta( $trigger_meta );

					$trigger_meta['meta_key']   = 'GFUSERIP';
					$trigger_meta['meta_value'] = $entry['ip'];
					Automator()->insert_trigger_meta( $trigger_meta );

					$trigger_meta['meta_key']   = 'GFENTRYDATE';
					$trigger_meta['meta_value'] = \GFCommon::format_date( $entry['date_created'], false, get_option( 'date_format' ) );
					Automator()->insert_trigger_meta( $trigger_meta );

					$trigger_meta['meta_key']   = 'GFENTRYSOURCEURL';
					$trigger_meta['meta_value'] = $entry['source_url'];
					Automator()->insert_trigger_meta( $trigger_meta );

					$trigger_meta ['meta_key']  = 'user_id';
					$trigger_meta['meta_value'] = $user_id;
					Automator()->insert_trigger_meta( $trigger_meta );

					Automator()->maybe_trigger_complete( $result['args'] );
				}
			}
		}
	}
}
