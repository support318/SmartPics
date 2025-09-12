<?php // phpcs:ignore WordPress.Files.FileName.InvalidClassFileName
namespace Uncanny_Automator_Pro;

/**
 * Class MAILERLITE_GROUP_REMOVE
 *
 * @package Uncanny_Automator
 */
class MAILERLITE_GROUP_REMOVE {

	use \Uncanny_Automator\Recipe\Actions;

	public function __construct() {

		$this->set_helpers( new Mailerlite_Helpers( false ) );

		$this->setup_action();

	}

	/**
	 * Set-up the action.
	 *
	 * @return void
	 */
	protected function setup_action() {

		$this->set_integration( 'MAILERLITE' );

		$this->set_action_code( 'MAILERLITE_GROUP_REMOVE_CODE' );

		$this->set_action_meta( 'MAILERLITE_GROUP_REMOVE' );

		$this->set_is_pro( true );

		$this->set_support_link( Automator()->get_author_support_link( $this->get_action_code(), 'knowledge-base/mailerlite/' ) );

		$this->set_requires_user( false );

		/* translators: Action - WordPress */
		$this->set_sentence( sprintf( esc_attr__( 'Remove {{a specific group:%1$s}}', 'uncanny-automator-pro' ), $this->get_action_meta() ) );

		/* translators: Action - WordPress */
		$this->set_readable_sentence( esc_attr__( 'Remove {{a specific group}}', 'uncanny-automator-pro' ) );

		$this->set_options_callback( array( $this, 'load_options' ) );

		$this->set_background_processing( true );

		$this->register_action();

	}

	public function load_options() {

		return Automator()->utilities->keep_order_of_options(
			array(
				'options_group' => array(
					$this->get_action_meta() => array(
						array(
							'option_code' => $this->get_action_meta(),
							'label'       => esc_attr__( 'Group', 'uncanny-automator-pro' ),
							'input_type'  => 'select',
							'options'     => $this->get_helpers()->fetch_groups(),
							'required'    => true,
							'description' => esc_attr__( "This action is irreversible. Please make sure you've selected the right Group", 'uncanny-automator-pro' ),
						),
					),
				),
			)
		);

	}

	/**
	 * Run during action processing.
	 *
	 * @param int $user_id
	 * @param array $action_data
	 * @param int $recipe_id
	 * @param array $args
	 * @param array $parsed
	 *
	 * @return void
	 */
	protected function process_action( $user_id, $action_data, $recipe_id, $args, $parsed ) {

		$group_id = isset( $parsed[ $this->get_action_meta() ] ) ?
			str_replace( '_', '', sanitize_text_field( $parsed[ $this->get_action_meta() ] ) )
			: '';

		try {

			if ( empty( $group_id ) ) {

				// Unprocessable entity.
				throw new \Exception( 'Group ID is required.', 422 );

			}

			$this->get_helpers()->http( 'DELETE', 'application/json' )->request(
				'groups/' . $group_id,
				array()
			);

			Automator()->complete->action( $user_id, $action_data, $recipe_id );

		} catch ( \Exception $e ) {

			$action_data['complete_with_errors'] = true;

			Automator()->complete->action( $user_id, $action_data, $recipe_id, $e->getCode() . ':' . $e->getMessage() );

		}

	}

}
