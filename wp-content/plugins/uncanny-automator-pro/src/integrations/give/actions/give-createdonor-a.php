<?php

namespace Uncanny_Automator_Pro;

use Uncanny_Automator\Recipe;

/**
 * Class GIVE_CREATEDONOR_A
 *
 * @package Uncanny_Automator_Pro
 */
class GIVE_CREATEDONOR_A {
	use Recipe\Action_Tokens;

	/**
	 * Integration code
	 *
	 * @var string
	 */
	public static $integration = 'GIVEWP';

	private $action_code;
	private $action_meta;

	/**
	 * Set up Automator action constructor.
	 */
	public function __construct() {
		$this->action_code = 'GIVECREATEDONOR';
		$this->define_action();
	}

	/**
	 * Define and register the action by pushing it into the Automator object
	 */
	public function define_action() {

		$action = array(
			'author'             => Automator()->get_author_name(),
			'support_link'       => Automator()->get_author_support_link( $this->action_code, 'integration/givewp/' ),
			'is_pro'             => true,
			'integration'        => self::$integration,
			'code'               => $this->action_code,
			'requires_user'      => false,
			/* translators: Actions - Give WP */
			'sentence'           => sprintf( __( 'Create {{a donor:%1$s}}', 'uncanny-automator-pro' ), $this->action_code ),
			/* translators: Actions - Give WP */
			'select_option_name' => __( 'Create {{a donor}}', 'uncanny-automator-pro' ),
			'priority'           => 10,
			'accepted_args'      => 1,
			'execution_function' => array( $this, 'give_create_donor' ),
			'options_callback'   => array( $this, 'load_options' ),
		);

		$this->set_action_tokens(
			array(
				'DONOR_ID' => array(
					'name' => __( 'Donor ID', 'uncanny-automator-pro' ),
					'type' => 'int',
				),
			),
			$this->action_code
		);

		Automator()->register->action( $action );
	}

	/**
	 * @return array[]
	 */
	public function load_options() {
		return Automator()->utilities->keep_order_of_options(
			array(
				'options'       => array(),
				'options_group' => array(
					$this->action_code => array(
						Automator()->helpers->recipe->field->text_field( 'FIRSTNAME', __( 'First name', 'uncanny-automator' ), true, 'text', '', false, '' ),
						Automator()->helpers->recipe->field->text_field( 'LASTNAME', __( 'Last name', 'uncanny-automator' ), true, 'text', '', false, '' ),
						Automator()->helpers->recipe->field->text_field( 'EMAIL', __( 'Email', 'uncanny-automator' ), true, 'text', '', true, '' ),
						Automator()->helpers->recipe->field->text_field( 'COMPANYNAME', __( 'Company name', 'uncanny-automator' ), true, 'text', '', false, '' ),
					),
				),
			)
		);
	}

	/**
	 * Validation function when the trigger action is hit
	 *
	 * @param $user_id
	 * @param $action_data
	 * @param $recipe_id
	 */
	public function give_create_donor( $user_id, $action_data, $recipe_id, $args ) {

		// Email is mandatory. Return error its not valid.
		if ( isset( $action_data['meta']['EMAIL'] ) ) {
			$email = Automator()->parse->text( $action_data['meta']['EMAIL'], $recipe_id, $user_id, $args );
			if ( ! is_email( $email ) ) {
				Automator()->complete->action(
					$user_id,
					$action_data,
					$recipe_id,
					sprintf(
					/* translators: Create a {{donor}} - Error while creating a new donor */
						__( 'Invalid email: %1$s', 'uncanny-automator-pro' ),
						$email
					)
				);
			}
		} else {
			Automator()->complete->action( $user_id, $action_data, $recipe_id, __( 'Email was not set', 'uncanny-automator-pro' ) );

			return;
		}

		$donor_data = array(
			'email' => $email,   //(string) The donor email address.
			'name'  => '',
		);

		if ( isset( $action_data['meta']['COMPANYNAME'] ) && ! empty( $action_data['meta']['COMPANYNAME'] ) ) {
			$donor_company = Automator()->parse->text( $action_data['meta']['COMPANYNAME'], $recipe_id, $user_id, $args );
		}

		if ( isset( $action_data['meta']['FIRSTNAME'] ) && ! empty( $action_data['meta']['FIRSTNAME'] ) ) {
			$donor_data['name'] = Automator()->parse->text( $action_data['meta']['FIRSTNAME'], $recipe_id, $user_id, $args );
		}

		if ( isset( $action_data['meta']['LASTNAME'] ) && ! empty( $action_data['meta']['LASTNAME'] ) ) {
			$donor_data['name'] .= ' ' . Automator()->parse->text( $action_data['meta']['LASTNAME'], $recipe_id, $user_id, $args );
		}

		$donor    = new \Give_Donor();
		$donor_id = $donor->create( $donor_data );

		if ( isset( $donor_id ) && isset( $donor_company ) ) {
			Give()->donor_meta->update_meta( $donor_id, '_give_donor_company', $donor_company );
		}

		$this->hydrate_tokens(
			array(
				'DONOR_ID' => $donor_id,
			)
		);

		Automator()->complete->action( $user_id, $action_data, $recipe_id );

	}

}
