<?php
namespace Uncanny_Automator_Pro\Integrations\Thrive_Apprentice;

use Uncanny_Automator\Recipe\Action;

/**
 * Class Thrive_Apprentice_Product_User_Access_Remove
 *
 * @package Uncanny_Automator
 */
class Thrive_Apprentice_Product_User_Access_Remove extends Action {

	protected $helper;


	/**
	 * Setup Action.
	 *
	 * @return void.
	 */
	protected function setup_action() {
		$this->helper = array_shift( $this->dependencies );

		$this->set_integration( 'THRIVE_APPRENTICE' );

		$this->set_action_code( 'THRIVE_APPRENTICE_PRODUCT_USER_ACCESS_REMOVE' );

		$this->set_action_meta( 'THRIVE_APPRENTICE_PRODUCT_USER_ACCESS_REMOVE_META' );

		$this->set_is_pro( true );

		$this->set_support_link( Automator()->get_author_support_link( $this->get_action_code(), 'knowledge-base/thrive-apprentice/' ) );

		$this->set_requires_user( true );

		$this->set_sentence(
			sprintf(
				/* translators: Action - WordPress */
				esc_attr_x( "Remove the user's access to {{a product:%1\$s}}", 'Thrive Apprentice', 'uncanny-automator-pro' ),
				$this->get_action_meta()
			)
		);

		/* translators: Action - WordPress */
		$this->set_readable_sentence( esc_attr_x( "Remove the user's access to {{a product}}", 'Thrive Apprentice', 'uncanny-automator-pro' ) );
	}

	/**
	 * Define tokens for this action.
	 *
	 * @return array
	 */
	public function define_tokens() {

		return array();
	}

	/**
	 * Loads available options for the Trigger.
	 *
	 * @return array The available trigger options.
	 */
	public function load_options() {

		return Automator()->utilities->keep_order_of_options(
			array(
				'options_group' => array(
					$this->get_action_meta() => array(
						array(
							'option_code'              => $this->get_action_meta(),
							'required'                 => true,
							'label'                    => esc_html_x( 'Product', 'Thrive Apprentice', 'uncanny-automator-pro' ),
							'input_type'               => 'select',
							'options'                  => $this->helper->get_products(),
							'supports_custom_value'    => true,
							'custom_value_description' => esc_html_x( 'Product ID', 'Thrive Apprentice', 'uncanny-automator-pro' ),
							'relevant_tokens'          => array(),
						),
					),
				),
			)
		);
	}

	/**
	 * Process the action.
	 *
	 * @param $user_id
	 * @param $action_data
	 * @param $recipe_id
	 * @param $args
	 * @param $parsed
	 *
	 * @return bool
	 */
	protected function process_action( $user_id, $action_data, $recipe_id, $args, $parsed ) {

		$product_id = isset( $parsed[ $this->get_action_meta() ] )
			? absint( sanitize_text_field( $parsed[ $this->get_action_meta() ] ) )
			: 0;

		if ( $this->is_dependencies_loaded() ) {

			/**
			 * This method from Thrive Apprentice always returns (bool) true.
			 * Assume everything works if we can't determine whether it has failed or not.
			 * Otherwise, log the method below if ever they have updated this method to return the actual result.
			 *
			 * @since 4.9
			 */
			\TVA_Customer::remove_user_from_product(
				$user_id,
				new \TVA\Product( get_term( $product_id, 'tva_product' ) )
			);

			return true;

		}

		$this->add_log_error( esc_html_x( 'Thrive Apprentice plugin must be installed and activated for this action to run.', 'Thrive Apprentice', 'uncanny-automator-pro' ) );

		return false;
	}

	/**
	 * Determines whether all dependencies has been loaded.
	 *
	 * @return bool True if dependencies conditions are satisfied. Returns false, otherwise.
	 */
	private function is_dependencies_loaded() {

		return class_exists( '\TVA\Product' )
			&& class_exists( '\TVA_Customer' )
			&& method_exists( '\TVA_Customer', 'remove_user_from_product' );
	}
}
