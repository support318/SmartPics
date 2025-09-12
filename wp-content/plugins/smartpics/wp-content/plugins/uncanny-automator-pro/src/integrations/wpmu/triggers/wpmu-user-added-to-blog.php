<?php

namespace Uncanny_Automator_Pro;

use Uncanny_Automator\Recipe;

/**
 * Class WPMU_USER_ADDED_TO_BLOG
 *
 * @package Uncanny_Automator_Pro
 */
class WPMU_USER_ADDED_TO_BLOG {

	use Recipe\Triggers;

	/**
	 * @var Wpmu_Pro_Tokens
	 */
	public $token_class;

	/**
	 * Set up Automator trigger constructor.
	 */
	public function __construct() {
		$this->token_class = new Wpmu_Pro_Tokens();
		// Only available on multisite installs
		$this->setup_trigger();
	}

	/**
	 * Define and register the trigger by pushing it into the Automator object
	 */
	public function setup_trigger() {
		$this->set_integration( 'WPMU' );
		$this->set_trigger_code( 'WPMUADDEDTOBLOG' );
		$this->set_trigger_meta( 'WPMUUSERS' );
		$this->set_is_login_required( true );
		$this->set_is_pro( true );
		/* Translators: Trigger sentence */
		$this->set_sentence( esc_html__( 'A user is added to a subsite', 'uncanny-automator-pro' ) );
		/* Translators: Trigger sentence */
		$this->set_readable_sentence( esc_html__( 'A user is added to a subsite', 'uncanny-automator-pro' ) ); // Non-active state sentence to show
		$this->set_action_hook( 'add_user_to_blog' );
		$this->set_action_args_count( 3 );
		$this->set_tokens( $this->token_class->add_to_blog_tokens() );
		$this->register_trigger();
	}

	/**
	 * @param ...$args
	 *
	 * @return bool
	 */
	public function validate_trigger( ...$args ) {
		$is_valid                         = false;
		list( $user_id, $role, $blog_id ) = array_shift( $args );

		if ( isset( $user_id ) && is_numeric( $user_id ) ) {
			$is_valid = true;
		}
		$this->set_user_id( $user_id );
		$this->set_is_signed_in( true );

		return $is_valid;

	}

	/**
	 * @param $args
	 *
	 * @return void
	 */
	protected function prepare_to_run( $args ) {
	}

}
