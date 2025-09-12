<?php
namespace Uncanny_Automator_Pro\Integrations\Thrive_Apprentice;

use Uncanny_Automator\Recipe\Trigger;

/**
 * Class THRIVE_APPRENTICE_USER_PURCHASED
 *
 * @package Uncanny_Automator
 */
class Thrive_Apprentice_User_Purchased extends Trigger {

	protected $helper;

	/**
	 * Constant TRIGGER_CODE.
	 *
	 * @var string
	 */
	const TRIGGER_CODE = 'THRIVE_APPRENTICE_USER_PURCHASED';

	/**
	 * Constant TRIGGER_META.
	 *
	 * @var string
	 */
	const TRIGGER_META = 'THRIVE_APPRENTICE_USER_PURCHASED_META';

	/**
	 * Define and register the trigger by pushing it into the Automator object.
	 *
	 * @return void.
	 */
	protected function setup_trigger() {

		$this->helper = array_shift( $this->dependencies );

		$this->set_integration( 'THRIVE_APPRENTICE' );

		$this->set_trigger_code( self::TRIGGER_CODE );

		$this->set_trigger_meta( self::TRIGGER_META );

		$this->set_is_pro( true );

		// The action hook to attach this trigger into.
		$this->add_action( 'tva_purchase' );

		// The number of arguments that the action hook accepts.
		$this->set_action_args_count( 2 );

		$this->set_sentence(
			sprintf(
				/* Translators: Trigger sentence */
				esc_attr_x( 'A user makes a purchase', 'Thrive Apprentice', 'uncanny-automator-pro' )
			)
		);

		$this->set_readable_sentence(
			/* Translators: Trigger sentence */
			esc_attr_x( 'A user makes a purchase', 'Thrive Apprentice', 'uncanny-automator-pro' )
		);
	}

	/**
	 * Validate.
	 *
	 * @param mixed $trigger The trigger.
	 * @param mixed $hook_args The arguments.
	 * @return mixed
	 */
	public function validate( $trigger, $hook_args ) {
		list( $user, $product ) = $hook_args;

		if ( empty( $user ) || empty( $product ) ) {
			return false;
		}

		$this->set_user_id( absint( $user['user_id'] ) );

		// Allow to fire for any combinations of parameters.
		return true;
	}

	/**
	 * Define tokens.
	 *
	 * @param mixed $trigger The trigger.
	 * @param mixed $tokens The destination.
	 * @return mixed
	 */
	public function define_tokens( $trigger, $tokens ) {
		return array(
			'PRODUCT_ID'   => array(
				'name'      => esc_html_x( 'Product ID', 'Thrive Apprentice', 'uncanny-automator' ),
				'type'      => 'int',
				'tokenId'   => 'PRODUCT_ID',
				'tokenName' => esc_html_x( 'Product ID', 'Thrive Apprentice', 'uncanny-automator' ),
			),
			'PRODUCT_NAME' => array(
				'name'      => esc_html_x( 'Product name', 'Thrive Apprentice', 'uncanny-automator' ),
				'type'      => 'text',
				'tokenId'   => 'PRODUCT_NAME',
				'tokenName' => esc_html_x( 'Product name', 'Thrive Apprentice', 'uncanny-automator' ),
			),
		);
	}

	/**
	 * Hydrate tokens.
	 *
	 * @param mixed $trigger The trigger.
	 * @param mixed $hook_args The arguments.
	 * @return mixed
	 */
	public function hydrate_tokens( $trigger, $hook_args ) {
		list( $user, $product ) = $hook_args;

		if ( ! is_object( $product ) || empty( $product->id ) ) {
			return array();
		}

		return array(
			'PRODUCT_ID'   => $product->id,
			'PRODUCT_NAME' => isset( $product->title ) ? $product->title : '',
		);
	}
}
