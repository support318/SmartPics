<?php

namespace Uncanny_Automator_Pro;

use Uncanny_Automator\Recipe;

class BDB_USERRECEIVESONSCREENNOTIFICATION {

	use Recipe\Triggers;

	/**
	 * Trigger code.
	 *
	 * @var string
	 */
	const TRIGGER_CODE = 'BDB_USERRECEIVESONSCREENNOTIFICATION';

	protected $helper;

	protected $bdb_tokens = null;

	/**
	 * Trigger meta.
	 *
	 * @var string
	 */
	const TRIGGER_META = 'BDB_USERRECEIVESONSCREENNOTIFICATION_META';

	public function __construct() {

		$is_on_screen_notification_enable = function_exists( 'bp_get_option' ) ? bp_get_option( '_bp_on_screen_notifications_enable', 0 ) : 0;

		if ( empty( $is_on_screen_notification_enable ) ) {
			return;
		}

		if ( class_exists( '\Uncanny_Automator_Pro\Bdb_Pro_Tokens' ) && class_exists( '\Uncanny_Automator_Pro\Buddyboss_Pro_Helpers' ) ) {

			$this->set_helper( new \Uncanny_Automator_Pro\Buddyboss_Pro_Helpers( false ) );

			$this->bdb_tokens = new \Uncanny_Automator_Pro\Bdb_Pro_Tokens( false );

			$this->setup_trigger();

		}

	}

	public function set_helper( $helper ) {

		$this->helper = $helper;

	}

	/**
	 * Define and register the trigger by pushing it into the Automator object
	 */
	public function setup_trigger() {

		$this->set_integration( 'BDB' );

		$this->set_trigger_code( self::TRIGGER_CODE );

		$this->set_trigger_meta( self::TRIGGER_META );

		$this->set_is_pro( true );

		$this->set_sentence(
		/* Translators: Trigger sentence */
			sprintf( esc_html__( 'A user receives a {{type of:%1$s}} on-screen notification', 'uncanny-automator-pro' ), $this->get_trigger_meta() )
		);

		$this->set_readable_sentence(
			esc_html__( 'A user receives a {{type of}} on-screen notification', 'uncanny-automator-pro' )
		);

		$this->add_action( 'bp_notification_after_save' );

		if ( null !== $this->bdb_tokens ) {

			$this->set_tokens( ( new Bdb_Pro_Tokens( false ) )->user_notification_tokens() );

		}

		$this->set_action_args_count( 1 );

		$this->set_options_callback( array( $this, 'load_options' ) );

		$this->register_trigger();

	}

	public function load_options() {

		if ( function_exists( 'bb_register_notification_preferences' ) ) {
			$all_notifications = bb_register_notification_preferences();

			$options = array();
			if ( ! empty( $all_notifications ) ) {
				foreach ( $all_notifications as $notification ) {
					if ( ! empty( $notification['fields'] ) ) {
						foreach ( $notification['fields'] as $field ) {
							if ( ! empty( $field['notifications'][0]['component'] ) ) {
								$options[ $field['notifications'][0]['component_action'] ] = ucwords( $field['notifications'][0]['component'] ) . ' - ' . $field['admin_label'];
							}
						}
					}
				}
			}
		}

		return Automator()->utilities->keep_order_of_options(
			array(
				'options_group' => array(
					$this->get_trigger_meta() => array(
						array(
							'option_code'     => $this->get_trigger_meta(),
							'input_type'      => 'select',
							'label'           => esc_html__( 'Notification type', 'uncanny-automator' ),
							'options'         => $options,
							'required'        => true,
							'relevant_tokens' => array(),
						),
					),
				),
			)
		);

	}

	public function validate_trigger( ...$args ) {

		$default_settings = bp_get_option( 'bb_enabled_notification', array() );
		if ( is_object( $args[0][0] ) ) {
			if ( 'yes' === $default_settings[ $args[0][0]->component_action ]['web'] && 'yes' === $default_settings[ $args[0][0]->component_action ]['main'] ) {
				return true;
			}
		}

		return false;

	}

	public function prepare_to_run( $data ) {

		$this->set_conditional_trigger( true );

	}

	/**
	 * Method validate_contions.
	 *
	 * @param ...$args
	 *
	 * @return array
	 */
	protected function validate_conditions( ...$args ) {

		if ( ! is_object( $args[0][0] ) ) {
			return 0;
		}

		$component_action = $args[0][0]->component_action;

		$this->actual_where_values = array(); // Fix for when not using the latest Trigger_Recipe_Filters version. Newer integration can omit this line.

		return $this->find_all( $this->trigger_recipes() )
					->where( array( $this->get_trigger_meta() ) )
					->match( array( $component_action ) )
					->format( array( 'trim' ) )
					->get();

	}

	/**
	 * Method parse_additional_tokens.
	 *
	 * @param $parsed
	 * @param $args
	 * @param $trigger
	 *
	 * @return array
	 */
	public function parse_additional_tokens( $parsed, $args, $trigger ) {

		return $this->bdb_tokens->hydrate_user_notification_tokens( $parsed, $args, $trigger );

	}

}
