<?php

namespace Uncanny_Automator_Pro;

use Uncanny_Automator\Recipe;

/**
 * Class AFFWP_LINK_CUSTOMER_FOR_LIFETIME_COMMISSIONS
 *
 * @package Uncanny_Automator_Pro
 */
class AFFWP_LINK_CUSTOMER_FOR_LIFETIME_COMMISSIONS {

	use Recipe\Actions;
	use Recipe\Action_Tokens;

	/**
	 * Set up Automator action constructor.
	 */
	public function __construct() {
		$this->setup_action();
	}

	/**
	 * Define and register the action by pushing it into the Automator object
	 */
	protected function setup_action() {
		$this->set_helpers( new Affwp_Pro_Helpers() );
		$this->set_integration( 'AFFWP' );
		$this->set_action_code( 'AFFWP_LINK_CUSTOMER' );
		$this->set_action_meta( 'ALL_AFFILIATES' );
		$this->set_requires_user( false );
		$this->set_is_pro( true );

		/* translators: Action - Affiliate WP */
		$this->set_sentence( sprintf( esc_attr_x( 'Link {{a customer:%2$s}} to {{an affiliate:%1$s}} for lifetime commissions', 'AffiliateWP', 'uncanny-automator-pro' ), $this->get_action_meta(), 'CUSTOMER_EMAIL' ) );

		/* translators: Action - Affiliate WP */
		$this->set_readable_sentence( esc_attr_x( 'Link {{a customer}} to {{an affiliate}} for lifetime commissions', 'AffiliateWP', 'uncanny-automator-pro' ) );

		$this->set_options_callback( array( $this, 'load_options' ) );

		$this->set_action_tokens(
			array(
				'AFFILIATE_ID'   => array(
					'name' => _x( 'Affiliate ID', 'AffiliateWP', 'uncanny-automator-pro' ),
					'type' => 'int',
				),
				'AFFILIATE_URL'  => array(
					'name' => _x( 'Affiliate URL', 'AffiliateWP', 'uncanny-automator-pro' ),
					'type' => 'url',
				),
				'CUSTOMER_ID'    => array(
					'name' => _x( 'Customer ID', 'AffiliateWP', 'uncanny-automator-pro' ),
					'type' => 'int',
				),
				'CUSTOMER_EMAIL' => array(
					'name' => _x( 'Customer email', 'AffiliateWP', 'uncanny-automator-pro' ),
					'type' => 'email',
				),
			),
			$this->get_action_code()
		);

		$this->register_action();
	}

	/**
	 * load_options
	 *
	 * @return array
	 */
	public function load_options() {

		$options = array(
			'options' => array(
				$this->get_helpers()->get_affiliates( null, $this->get_action_meta() ),
				Automator()->helpers->recipe->field->text(
					array(
						'option_code' => 'CUSTOMER_EMAIL',
						'input_type'  => 'email',
						'label'       => _x( 'Customer email', 'AffiliateWP', 'uncanny-automator-pro' ),
					)
				),
			),
		);

		return Automator()->utilities->keep_order_of_options( $options );
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
	 * @return void.
	 * @throws \Exception
	 */
	protected function process_action( $user_id, $action_data, $recipe_id, $args, $parsed ) {
		$affiliate_id      = isset( $parsed[ $this->get_action_meta() ] ) ? absint( sanitize_text_field( $parsed[ $this->get_action_meta() ] ) ) : '';
		$customer_email    = isset( $parsed['CUSTOMER_EMAIL'] ) ? sanitize_text_field( $parsed['CUSTOMER_EMAIL'] ) : '';
		$affiliate_user_id = affwp_get_affiliate_user_id( $affiliate_id );

		if ( false === $affiliate_user_id && false === affwp_is_affiliate( $affiliate_user_id ) ) {
			$action_data['do-nothing']           = true;
			$action_data['complete_with_errors'] = true;
			Automator()->complete->action( $user_id, $action_data, $recipe_id, _x( 'The affiliate is not found.', 'AffiliateWP', 'uncanny-automator-pro' ) );

			return;
		}

		if ( empty( $customer_email ) || ! is_email( $customer_email ) ) {
			$action_data['do-nothing']           = true;
			$action_data['complete_with_errors'] = true;
			Automator()->complete->action( $user_id, $action_data, $recipe_id, _x( 'Please enter a valid email address.', 'AffiliateWP', 'uncanny-automator-pro' ) );

			return;
		}

		$customer = affiliate_wp()->customers->get_by( 'email', $customer_email );

		if ( $customer ) {
			if ( ! $customer->user_id ) {
				$customer_id = $customer->ID;
				$user        = get_user_by( 'email', $customer->email );
				if ( $user ) {
					$args            = array(
						'user_id'     => $user->ID,
						'customer_id' => $customer->customer_id,
					);
					$update_customer = affwp_update_customer( $args );
					if ( $update_customer ) {
						$linked_affiliate = affiliate_wp_lifetime_commissions()->lifetime_customers->get_by( 'affwp_customer_id', $customer->customer_id );
						if ( ! $linked_affiliate ) {
							$this->link_customer_with_affiliate_for_lifetime_commissions( $customer_id, $affiliate_id );
						}
					}
				}
			}
			$linked_affiliate = affiliate_wp_lifetime_commissions()->lifetime_customers->get_by( 'affwp_customer_id', $customer->customer_id );
			if ( ! $linked_affiliate ) {
				$this->link_customer_with_affiliate_for_lifetime_commissions( $customer_id, $affiliate_id );
			}
		} else {
			$args = array(
				'email'        => $customer_email,
				'affiliate_id' => $affiliate_id,
			);
			$user = get_user_by( 'email', $customer_email );
			if ( $user ) {
				$args['user_id']    = $user->ID;
				$args['first_name'] = $user->first_name;
				$args['last_name']  = $user->last_name;
			}
			$customer_id = affwp_add_customer( $args );
			if ( false !== $customer_id ) {
				$this->link_customer_with_affiliate_for_lifetime_commissions( $customer_id, $affiliate_id );
			}
		}

		$this->hydrate_tokens(
			array(
				'AFFILIATE_ID'   => $affiliate_id,
				'AFFILIATE_URL'  => affwp_get_affiliate_referral_url( array( 'affiliate_id' => $affiliate_id ) ),
				'CUSTOMER_ID'    => $customer_id,
				'CUSTOMER_EMAIL' => $customer_email,
			)
		);

		Automator()->complete->action( $user_id, $action_data, $recipe_id );
	}

	/**
	 * @param $customer_id
	 * @param $affiliate_id
	 *
	 * @return void
	 */
	private function link_customer_with_affiliate_for_lifetime_commissions( $customer_id, $affiliate_id ) {
		$args = array(
			'affwp_customer_id' => $customer_id,
			'affiliate_id'      => $affiliate_id,
		);
		affiliate_wp_lifetime_commissions()->lifetime_customers->add( $args );
	}

}
