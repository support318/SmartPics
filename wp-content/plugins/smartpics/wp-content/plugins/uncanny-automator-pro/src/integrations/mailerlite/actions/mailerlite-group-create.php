<?php // phpcs:ignore WordPress.Files.FileName.InvalidClassFileName
namespace Uncanny_Automator_Pro;

/**
 * Class MAILERLITE_GROUP_CREATE
 *
 * @package Uncanny_Automator
 */
class MAILERLITE_GROUP_CREATE {

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

		$this->set_action_code( 'MAILERLITE_GROUP_CREATE_CODE' );

		$this->set_action_meta( 'MAILERLITE_GROUP_CREATE_META' );

		$this->set_is_pro( true );

		$this->set_support_link( Automator()->get_author_support_link( $this->get_action_code(), 'knowledge-base/mailerlite/' ) );

		$this->set_requires_user( false );

		/* translators: Action - WordPress */
		$this->set_sentence( sprintf( esc_attr__( 'Create {{a group:%1$s}}', 'uncanny-automator-pro' ), $this->get_action_meta() ) );

		/* translators: Action - WordPress */
		$this->set_readable_sentence( esc_attr__( 'Create {{a group}}', 'uncanny-automator-pro' ) );

		$this->set_options_callback( array( $this, 'load_options' ) );

		$this->set_background_processing( true );

		$this->register_action();

	}

	/**
	 * Load options.
	 *
	 * @return void
	 */
	public function load_options() {
		return Automator()->utilities->keep_order_of_options(
			array(
				'options_group' => array(
					$this->get_action_meta() => array(
						array(
							'option_code' => $this->get_action_meta(),
							'label'       => esc_attr__( 'Group name', 'uncanny-automator-pro' ),
							'input_type'  => 'text',
							'required'    => true,
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

		$name = isset( $parsed[ $this->get_action_meta() ] ) ? sanitize_text_field( $parsed[ $this->get_action_meta() ] ) : '';

		try {

			if ( empty( $name ) ) {

				// Unprocessable entity.
				throw new \Exception( 'Field name is required.', 422 );

			}

			$this->get_helpers()->http( 'POST', 'application/json' )->request(
				'groups',
				array(
					'body' => wp_json_encode(
						array(
							'name' => $name,
						)
					),
				)
			);

			Automator()->complete->action( $user_id, $action_data, $recipe_id );

		} catch ( \Exception $e ) {

			$action_data['complete_with_errors'] = true;

			Automator()->complete->action( $user_id, $action_data, $recipe_id, $e->getCode() . ':' . $e->getMessage() );

		}

	}
}
