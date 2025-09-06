<?php
namespace Uncanny_Automator_Pro\Loop_Filters;

use Uncanny_Automator_Pro\Loops\Filter\Base\Loop_Filter;

/**
 * Class USER_HAS_ACCESS_TO_PRODUCT
 *
 * @package Uncanny_Automator_Pro
 */
final class USER_HAS_ACCESS_TO_PRODUCT extends Loop_Filter {

	/**
	 *
	 * @return void
	 * @throws \Exception
	 */
	public function setup() {
		$this->register_hooks();
		$this->set_integration( 'THRIVE_APPRENTICE' );
		$this->set_meta( 'USER_HAS_ACCESS_TO_PRODUCT' );
		$this->set_sentence( esc_html_x( 'The user {{has/does not have}} access to {{a Thrive Apprentice product}}', 'Thrive Apprentice', 'uncanny-automator-pro' ) );
		$this->set_sentence_readable(
			sprintf(
				/* translators: %1$s: Criteria, %2$s: Product */
				esc_html_x( 'The user {{has/does not have:%1$s}} access to {{a Thrive Apprentice product:%2$s}}', 'Thrive Apprentice', 'uncanny-automator-pro' ),
				'CRITERIA',
				$this->get_meta()
			)
		);
		$this->set_fields( array( $this, 'load_options' ) );
		$this->set_loop_type( 'users' );
		$this->set_entities( array( $this, 'retrieve_users_with_access' ) );
	}

	/**
	 * @return mixed[]
	 */
	public function load_options() {
		return array(
			$this->get_meta() => array(
				array(
					'option_code'           => 'CRITERIA',
					'type'                  => 'select',
					'label'                 => esc_html_x( 'Criteria', 'Thrive Apprentice', 'uncanny-automator-pro' ),
					'supports_custom_value' => false,
					'options'               => array(
						array(
							'text'  => esc_html_x( 'has', 'Thrive Apprentice', 'uncanny-automator-pro' ),
							'value' => 'has',
						),
						array(
							'text'  => esc_html_x( 'does not have', 'Thrive Apprentice', 'uncanny-automator-pro' ),
							'value' => 'does-not-have',
						),
					),
				),
				array(
					'option_code'           => $this->get_meta(),
					'type'                  => 'select',
					'label'                 => esc_html_x( 'Thrive Apprentice product', 'Thrive Apprentice', 'uncanny-automator-pro' ),
					'options'               => array(),
					'ajax'                  => array(
						'endpoint' => 'retrieve_thrive_apprentice_products',
						'event'    => 'on_load',
					),
					'supports_custom_value' => false,
					'options_show_id'       => false,
				),
			),
		);
	}

	/**
	 * @return void
	 */
	protected function register_hooks() {
		add_action( 'wp_ajax_retrieve_thrive_apprentice_products', array( $this, 'retrieve_thrive_apprentice_products_handler' ) );
	}

	/**
	 * @return bool
	 */
	protected function is_dependency_active() {
		return class_exists( '\TVA_Const', false );
	}

	/**
	 * @param array{USER_HAS_ACCESS_TO_PRODUCT:string,CRITERIA:string} $fields
	 *
	 * @return int[]
	 */
	public function retrieve_users_with_access( $fields ) {
		$criteria   = $fields['CRITERIA'];
		$product_id = intval( $fields[ $this->get_meta() ] );

		if ( empty( $criteria ) || empty( $product_id ) || ! $this->is_dependency_active() ) {
			return array();
		}

		// Get the product object
		$product = new \TVA\Product( $product_id );

		if ( ! $product->get_id() ) {
			return array(); // invalid product ID
		}

		if ( 'has' === $criteria ) {
			// Use Thrive's built-in method to get users with access
			$users_with_access = $product->get_users_with_access();
			return ! empty( $users_with_access ) ? array_map( 'absint', $users_with_access ) : array();
		} else {
			// For "does not have", get all users and exclude those with access
			$all_users         = get_users( array( 'fields' => 'ID' ) );
			$users_with_access = $product->get_users_with_access();

			$users_without_access = array_diff( $all_users, $users_with_access );
			return ! empty( $users_without_access ) ? array_map( 'absint', $users_without_access ) : array();
		}
	}

	/**
	 * Check if a user has access to a specific product
	 *
	 * @param int $user_id The user ID
	 * @param \TVA\Product $product The product object
	 * @return bool Whether the user has access
	 */
	private function user_has_access_to_product( $user_id, $product ) {
		if ( ! function_exists( 'tva_access_manager' ) ) {
			return false;
		}

		$user = get_user_by( 'ID', $user_id );
		if ( ! $user ) {
			return false;
		}

		// Use the access manager to check if the user has access to the product
		$access_manager = tva_access_manager();
		$access_manager->set_user( $user );
		$access_manager->set_product( $product );

		return $access_manager->check_rules();
	}

	/**
	 * AJAX handler to retrieve Thrive Apprentice products
	 *
	 * @return void
	 */
	public function retrieve_thrive_apprentice_products_handler() {
		Automator()->utilities->verify_nonce();

		$options = array();

		if ( ! $this->is_dependency_active() ) {
			wp_send_json(
				array(
					'success' => false,
					'options' => $options,
				)
			);
			return;
		}

		// Use direct taxonomy query like other Thrive methods
		$products = get_terms(
			array(
				'taxonomy'   => 'tva_product',
				'hide_empty' => false,
			)
		);

		if ( ! empty( $products ) && ! is_wp_error( $products ) ) {
			foreach ( $products as $product ) {
				if ( $product instanceof \WP_Term ) {
					$options[] = array(
						'text'  => esc_attr( $product->name ),
						'value' => esc_attr( $product->term_id ),
					);
				}
			}
		}

		wp_send_json(
			array(
				'success' => true,
				'options' => $options,
			)
		);
	}
}
