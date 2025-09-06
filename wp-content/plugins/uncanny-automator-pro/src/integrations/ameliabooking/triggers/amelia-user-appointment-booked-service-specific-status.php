<?php // phpcs:ignore WordPress.Files.FileName.InvalidClassFileName
namespace Uncanny_Automator_Pro;

use Uncanny_Automator\Recipe;

/**
 * Class AMELIA_USER_APPOINTMENT_BOOKED_SERVICE_SPECIFIC_STATUS
 *
 * @package Uncanny_Automator
 */
class AMELIA_USER_APPOINTMENT_BOOKED_SERVICE_SPECIFIC_STATUS {

	use Recipe\Triggers;

	/**
	 * Trigger code.
	 *
	 * @var string
	 */
	const TRIGGER_CODE = 'AMELIA_USER_APPOINTMENT_BOOKED_SERVICE_SPECIFIC_STATUS';

	/**
	 * Trigger meta.
	 *
	 * @var string
	 */
	const TRIGGER_META = 'AMELIA_USER_APPOINTMENT_BOOKED_SERVICE_SPECIFIC_STATUS_META';

	/**
	 * Set up Automator trigger constructor.
	 */
	public function __construct() {

		// Only enable in Amelia Pro.
		if ( ! defined( 'AMELIA_LITE_VERSION' ) ) {

			$this->setup_trigger();

		}
	}

	/**
	 * Define and register the trigger by pushing it into the Automator object
	 */
	public function setup_trigger() {

		// Bailout if helpers from base Automator is not found.
		if ( is_null( Automator()->helpers->recipe->ameliabooking ) ) {
			return;
		}

		$this->set_integration( 'AMELIABOOKING' );
		$this->set_trigger_code( self::TRIGGER_CODE );
		$this->set_trigger_meta( self::TRIGGER_META );
		$this->set_is_pro( true );
		$this->set_is_login_required( true );

		// The hook 'automator_ameliabooking_status_updated' is a fake hook that is used to fire the trigger for each booking.
		// Check the Ameliabooking_Pro_Helpers::ameliabooking_status_updated() method for more details.
		$this->add_action( array( 'automator_ameliabooking_status_updated', 'amelia_booking_status_cancelled' ) );

		// The number of arguments that the action hook accepts.
		$this->set_action_args_count( 3 );

		$this->set_sentence(
			sprintf(
			/* Translators: Trigger sentence */
				esc_html_x( "A user's booking of an appointment for {{a service:%1\$s}} has been changed to {{a specific status:%2\$s}}", 'Ameliabooking', 'uncanny-automator' ),
				$this->get_trigger_meta(),
				$this->get_trigger_meta() . '_STATUS'
			)
		);

		$this->set_readable_sentence(
			/* Translators: Trigger sentence */
			esc_html_x( "A user's booking of an appointment for {{a service}} has been changed to {{a specific status}}", 'Ameliabooking', 'uncanny-automator' )
		);

		// Set the options field group.
		$this->set_options_callback( array( $this, 'load_options' ) );

		// Register the trigger.
		$this->register_trigger();
	}

	/**
	 * @return array
	 */
	public function load_options() {
		return Automator()->utilities->keep_order_of_options(
			array(
				'options_group' => $this->get_trigger_option_fields(),
				'options'       => array(
					array(
						'input_type'               => 'select',
						'option_code'              => $this->get_trigger_meta() . '_STATUS',
						'required'                 => true,
						'label'                    => esc_html_x( 'Status', 'Ameliabooking', 'uncanny-automator' ),
						'options'                  => array(
							'-1'       => esc_html_x( 'Any status', 'Ameliabooking', 'uncanny-automator' ),
							'approved' => esc_html_x( 'Approved', 'Ameliabooking', 'uncanny-automator' ),
							'pending'  => esc_html_x( 'Pending', 'Ameliabooking', 'uncanny-automator' ),
							'rejected' => esc_html_x( 'Rejected', 'Ameliabooking', 'uncanny-automator' ),
							'no-show'  => esc_html_x( 'No show', 'Ameliabooking', 'uncanny-automator' ),
						),
						'supports_custom_value'    => true,
						'custom_value_description' => esc_html_x( 'Status ID', 'Ameliabooking', 'uncanny-automator' ),
					),
				),
			)
		);
	}

	/**
	 * The trigger options fields.
	 *
	 * @return array The field options.
	 */
	public function get_trigger_option_fields() {

		$existing_fields = Automator()->helpers->recipe->ameliabooking->options->get_option_fields(
			$this->get_trigger_code(),
			$this->get_trigger_meta()
		);

		return $existing_fields;
	}

	/**
	 * Validate the trigger.
	 *
	 * Return false if returned booking data is empty.
	 */
	public function validate_trigger( ...$args ) {

		return Automator()->helpers->recipe->ameliabooking->options->validate_trigger( $args );
	}

	/**
	 * Prepare to run.
	 *
	 * Sets the conditional trigger to true.
	 *
	 * @return void.
	 */
	public function prepare_to_run( $data ) {

		list( $reservation, $booking, $container ) = $data;

		// Update the current user ID.
		$user_id = Ameliabooking_Pro_Helpers::get_single_booking_wp_user_id( $booking, $container );

		$this->set_user_id( $user_id );

		$this->set_conditional_trigger( true );
	}

	/**
	 * Validate if trigger matches the condition.
	 *
	 * @param $args
	 *
	 * @return array
	 */
	protected function validate_conditions( $args ) {

		$service_id = isset( $args[0]['serviceId'] ) ? $args[0]['serviceId'] : null;

		$status = isset( $args[0]['status'] ) ? $args[0]['status'] : null;

		$matched_recipe_ids = array();

		if ( empty( $service_id ) || empty( $status ) ) {

			return $matched_recipe_ids;

		}

		$recipes = $this->trigger_recipes();

		if ( empty( $recipes ) ) {

			return $matched_recipe_ids;

		}

		$required_service = Automator()->get->meta_from_recipes( $recipes, $this->get_trigger_meta() );

		$required_status = Automator()->get->meta_from_recipes( $recipes, $this->get_trigger_meta() . '_STATUS' );

		if ( empty( $required_service ) || empty( $required_status ) ) {

			return $matched_recipe_ids;

		}

		foreach ( $recipes as $recipe_id => $recipe ) {

			foreach ( $recipe['triggers'] as $trigger ) {

				$trigger_id = absint( $trigger['ID'] );

				if ( ! isset( $required_service[ $recipe_id ] ) && ! isset( $required_status[ $recipe_id ] ) ) {
					continue;
				}

				if ( ! isset( $required_status[ $recipe_id ][ $trigger_id ] ) && ! isset( $required_status[ $recipe_id ][ $trigger_id ] ) ) {
					continue;
				}

				$is_any_service = intval( '-1' ) === intval( $required_service[ $recipe_id ][ $trigger_id ] );

				$is_any_status = intval( '-1' ) === intval( $required_status[ $recipe_id ][ $trigger_id ] );

				if (
					( $is_any_service || absint( $service_id ) === absint( $required_service[ $recipe_id ][ $trigger_id ] ) ) &&
					( $is_any_status || $status === $required_status[ $recipe_id ][ $trigger_id ] )
				) {
					$matched_recipe_ids[ $recipe_id ] = $trigger_id;
				}
			}
		}

		return $matched_recipe_ids;
	}
}
