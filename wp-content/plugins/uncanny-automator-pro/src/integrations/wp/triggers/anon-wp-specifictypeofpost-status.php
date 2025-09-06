<?php

namespace Uncanny_Automator_Pro;

use Uncanny_Automator\Recipe;
use Uncanny_Automator\Wp_Helpers;

/**
 * Class ANON_WP_SPECIFICTYPEOFPOST_STATUS
 *
 * @package Uncanny_Automator_Pro
 */
class ANON_WP_SPECIFICTYPEOFPOST_STATUS {

	use Recipe\Triggers;

	/**
	 * Set up Automator trigger constructor.
	 */
	public function __construct() {
		if ( Automator()->helpers->recipe->is_edit_page() ) {
			add_action(
				'wp_loaded',
				function () {
					$this->setup_trigger();
				},
				99
			);

			return;
		}
		$this->setup_trigger();

	}

	/**
	 * Define and register the trigger by pushing it into the Automator object
	 */
	public function setup_trigger() {
		$this->set_integration( 'WP' );
		$this->set_trigger_code( 'ANON_WPPOSTSTATUS' );
		$this->set_trigger_meta( 'SPECIFICPOSTTYPESTATUSUPDATED' );
		$this->set_is_login_required( false );
		$this->set_trigger_type( 'anonymous' );
		$this->set_is_pro( true );
		$this->set_action_args_count( 3 );

		/* Translators: Trigger sentence */
		$this->set_sentence( sprintf( esc_html__( '{{A specific type of post:%1$s}} is set to {{a status:%2$s}}', 'uncanny-automator-pro' ), 'WPPOSTTYPES', $this->trigger_meta ) );

		/* Translators: Trigger sentence */
		$this->set_readable_sentence( esc_html__( '{{A specific type of post}} is set to {{a status}}', 'uncanny-automator-pro' ) ); // Non-active state sentence to show

		$this->set_action_hook( 'transition_post_status' );
		$this->set_options_callback( array( $this, 'load_options' ) );

		if ( method_exists( '\Uncanny_Automator\Wp_Helpers', 'common_trigger_loopable_tokens' ) ) {
			$this->set_loopable_tokens( Wp_Helpers::common_trigger_loopable_tokens() );
		}

		$this->register_trigger();
	}

	/**
	 * @return array
	 */
	public function load_options() {

		return Automator()->utilities->keep_order_of_options(
			array(
				'options' => array(
					Automator()->helpers->recipe->wp->options->pro->all_wp_post_types(
						__( 'Post type', 'uncanny-automator-pro' ),
						'WPPOSTTYPES',
						array(
							'relevant_tokens' => array(),
						)
					),
					Automator()->helpers->recipe->wp->options->pro->wp_post_statuses(
						__( 'Status', 'uncanny-automator-pro' ),
						$this->trigger_meta,
						array(
							'is_any' => true,
						)
					),
				),
			)
		);

	}

	/**
	 * @param ...$args
	 *
	 * @return bool
	 */
	public function validate_trigger( ...$args ) {
		$is_valid                               = false;
		list( $new_status, $old_status, $post ) = $args[0];

		if ( isset( $new_status ) && isset( $old_status ) && isset( $post ) ) {
			if ( $new_status !== $old_status ) {
				$is_valid = true;
			}
		}

		return $is_valid;

	}

	/**
	 * @param $data
	 *
	 * @return void
	 */
	public function prepare_to_run( $data ) {
		$this->set_conditional_trigger( true );
	}

	/**
	 * Check email subject against the trigger meta
	 *
	 * @param $args
	 */
	public function validate_conditions( ...$args ) {
		list( $new_status, $old_status, $post ) = $args[0];

		$this->actual_where_values = array(); // Fix for when not using the latest Trigger_Recipe_Filters version. Newer integration can omit this line.

		return $this->find_all( $this->trigger_recipes() )
					   ->where( array( 'WPPOSTTYPES', $this->trigger_meta ) )
					   ->match( array( $post->post_type, $new_status ) )
					   ->format( array( 'trim', 'trim' ) )
					   ->get();
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
