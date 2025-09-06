<?php

namespace Uncanny_Automator_Pro;

use Uncanny_Automator\Easy_Affiliate_Helpers;
use \Uncanny_Automator\Recipe;
use EasyAffiliate\Lib\ModelFactory;

/**
 * Class ESAF_PAYOUT_TO_AFFILIATE
 *
 * @package Uncanny_Automator_Pro
 */
class ESAF_PAYOUT_TO_AFFILIATE {
	use Recipe\Triggers;

	/**
	 * Trigger code.
	 *
	 * @var string
	 */
	const TRIGGER_CODE = 'PAYOUT_MADE_CODE';

	/**
	 * Trigger meta.
	 *
	 * @var string
	 */
	const TRIGGER_META = 'PAYOUT_MADE_META';
	/**
	 * @var Easy_Affiliate_Helpers
	 */
	public $helpers;

	/**
	 * Set up Automator trigger constructor.
	 */
	public function __construct() {
		if ( ! class_exists( '\Uncanny_Automator\Easy_Affiliate_Helpers' ) ) {
			return;
		}
		$this->helpers = new Easy_Affiliate_Helpers();
		$this->setup_trigger();
	}

	/**
	 * Define and register the trigger by pushing it into the Automator object
	 */
	public function setup_trigger() {
		$this->set_integration( 'ESAF' );
		$this->set_trigger_code( self::TRIGGER_CODE );
		$this->set_trigger_meta( self::TRIGGER_META );
		$this->set_trigger_type( 'anonymous' );
		$this->set_is_login_required( false );
		$this->set_is_pro( true );
		$this->set_support_link( Automator()->get_author_support_link( $this->trigger_code, 'integration/easy-affiliate/' ) );
		$this->set_sentence(
			sprintf(
			/* Translators: Trigger sentence */
				esc_attr__( 'A payout is made to {{an affiliate:%1$s}}', 'uncanny-automator' ),
				$this->get_trigger_meta()
			)
		);
		// Non-active state sentence to show
		$this->set_readable_sentence( esc_attr__( 'A payout is made to {{an affiliate}}', 'uncanny-automator' ) );
		// Which do_action() fires this trigger.
		$this->add_action( 'esaf_event_payment-added' );
		$this->set_action_args_count( 1 );
		$this->set_options_callback( array( $this, 'load_options' ) );
		$this->register_trigger();

	}

	/**
	 * Method load_options
	 *
	 * @return void
	 */
	public function load_options() {

		return Automator()->utilities->keep_order_of_options(
			array(
				'options' => array(
					$this->helpers->get_all_affiliates( $this->get_trigger_meta() ),
				),
			)
		);

	}

	/**
	 * Validate the trigger.
	 *
	 * @param $args
	 *
	 * @return bool
	 */
	protected function validate_trigger( ...$args ) {
		$event = array_shift( $args );
		if ( empty( $event[0]->evt_id_type ) && empty( $event[0]->evt_id ) && 'payment' !== $event[0]->evt_id_type ) {
			return false;
		}

		return true;
	}

	/**
	 * Prepare to run the trigger.
	 *
	 * @param $data
	 *
	 * @return void
	 */
	public function prepare_to_run( $data ) {
		$this->set_conditional_trigger( true );
	}

	/**
	 * @param $args
	 *
	 * @return void
	 */
	protected function trigger_conditions( $args ) {
		$event = $args[0];
		$data  = ModelFactory::fetch( $event->evt_id_type, $event->evt_id );
		// Support "Any affiliate" option
		$this->do_find_any( true );
		// Find the affiliate in trigger meta
		$this->do_find_this( $this->get_trigger_meta() );
		$this->do_find_in( array( $data->affiliate_id ) );
	}

	/**
	 * @param ...$args
	 *
	 * @return bool
	 */
	public function do_continue_anon_trigger( ...$args ) {
		return true;
	}
}
