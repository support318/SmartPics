<?php

namespace Uncanny_Automator_Pro\Integrations\Woocommerce_Subscription\Actions;

use Uncanny_Automator\Recipe;
use Uncanny_Automator\Recipe\Action;

/**
 * Class Shorten_Subscription_X_Days
 *
 * @package Uncanny_Automator_Pro
 */
class Shorten_Subscription_X_Days extends Action {

	use Recipe\Action_Tokens;

	/**
	 * Define and register the action by pushing it into the Automator object
	 */
	protected function setup_action() {

		$this->set_integration( 'WOOCOMMERCE_SUBSCRIPTION' );
		$this->set_action_code( 'WC_SHORTEN_SUBSCRIPTION' );
		$this->set_action_meta( 'WC_SUBSCRIPTIONS' );
		$this->set_requires_user( true );
		$this->set_is_pro( true );

		$this->set_sentence(
			sprintf(
				/* translators: Action - WooCommerce Subscription */
				esc_attr_x(
					"Shorten a user's subscription to {{a specific product:%1\$s}} by {{a number of:%2\$s}} {{days:%3\$s}}",
					'WooCommerce Subscriptions',
					'uncanny-automator-pro'
				),
				$this->get_action_meta(),
				$this->get_action_meta() . '_NO_OF:' . $this->get_action_meta(),
				$this->get_action_meta() . '_DURATION:' . $this->get_action_meta()
			)
		);

		$this->set_readable_sentence(
			/* translators: Action - WooCommerce Subscription */
			esc_attr_x(
				"Shorten a user's subscription to {{a specific product}} by {{a number of}} {{days}}",
				'WooCommerce Subscriptions',
				'uncanny-automator-pro'
			)
		);

		// Set the action tokens.
		$action_tokens = array(
			'PRODUCT_TITLE'     => array(
				'name' => esc_html_x( 'Product title', 'WooCommerce Subscriptions', 'uncanny-automator-pro' ),
			),
			'PRODUCT_ID'        => array(
				'name' => esc_html_x( 'Product ID', 'WooCommerce Subscriptions', 'uncanny-automator-pro' ),
				'type' => 'int',
			),
			'PRODUCT_URL'       => array(
				'name' => esc_html_x( 'Product URL', 'WooCommerce Subscriptions', 'uncanny-automator-pro' ),
				'type' => 'url',
			),
			'PRODUCT_THUMB_URL' => array(
				'name' => esc_html_x( 'Product featured image URL', 'WooCommerce Subscriptions', 'uncanny-automator-pro' ),
				'type' => 'url',
			),
			'PRODUCT_THUMB_ID'  => array(
				'name' => esc_html_x( 'Product featured image ID', 'WooCommerce Subscriptions', 'uncanny-automator-pro' ),
				'type' => 'int',
			),
		);

		$this->set_action_tokens(
			$action_tokens,
			$this->action_code
		);
	}

	/**
	 * load_options
	 *
	 * @return array
	 */
	public function options() {

		$subscriptions = array(
			'option_code' => $this->get_action_meta(),
			'label'       => esc_html_x( 'Subscription product', 'WooCommerce Subscriptions', 'uncanny-automator-pro' ),
			'input_type'  => 'select',
			'options'     => array(),
			'required'    => true,
			'ajax'        => array(
				'endpoint' => 'automator_select_all_wc_subscriptions',
				'event'    => 'on_load',
			),
		);

		$number = array(
			'option_code' => $this->get_action_meta() . '_NO_OF',
			'label'       => esc_html_x( 'Number', 'WooCommerce Subscriptions', 'uncanny-automator-pro' ),
			'input_type'  => 'text',
			'tokens'      => true,
			'required'    => true,
		);

		$duration_options = array(
			array(
				'text'  => esc_html_x( 'Days', 'WooCommerce Subscriptions', 'uncanny-automator-pro' ),
				'value' => 'day',
			),
			array(
				'text'  => esc_html_x( 'Week', 'WooCommerce Subscriptions', 'uncanny-automator-pro' ),
				'value' => 'week',
			),
			array(
				'text'  => esc_html_x( 'Month', 'WooCommerce Subscriptions', 'uncanny-automator-pro' ),
				'value' => 'month',
			),
			array(
				'text'  => esc_html_x( 'Year', 'WooCommerce Subscriptions', 'uncanny-automator-pro' ),
				'value' => 'year',
			),
		);

		$duration = array(
			'option_code'     => $this->get_action_meta() . '_DURATION',
			'label'           => esc_html_x( 'Length', 'WooCommerce Subscriptions', 'uncanny-automator-pro' ),
			'input_type'      => 'select',
			'options'         => $duration_options,
			'options_show_id' => false,
		);

		return array(
			$subscriptions,
			$number,
			$duration,
		);
	}

	/**
	 * Process the action.
	 *
	 * @todo Refactor this method to delegate the logic to a service class.
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

		// Get subscription ID
		$product_id = $parsed[ $this->get_action_meta() ] ?? 0;
		$no_of      = $parsed[ $this->get_action_meta() . '_NO_OF' ] ?? 0;
		$duration   = $parsed[ $this->get_action_meta() . '_DURATION' ] ?? 0;

		$product = wc_get_product( absint( $product_id ) );

		if ( ! $product || ! $product->is_type( 'subscription' ) ) {
			throw new \Exception(
				esc_html_x(
					'The product is not of a subscription type or does not exist.',
					'WooCommerce Subscriptions',
					'uncanny-automator-pro'
				)
			);
		}

		$subscriptions = wcs_get_subscriptions(
			array(
				'subscriptions_per_page' => 9999,
				'orderby'                => 'start_date',
				'order'                  => 'DESC',
				'customer_id'            => $user_id,
				'product_id'             => absint( $product_id ),
				'subscription_status'    => array( 'active' ),
				'meta_query_relation'    => 'AND',
			)
		);

		if ( empty( $subscriptions ) ) {

			throw new \Exception(
				esc_html_x(
					'No active subscriptions were found.',
					'WooCommerce Subscriptions',
					'uncanny-automator-pro'
				)
			);

		}

		$subscription_updated = false;

		foreach ( $subscriptions as $subscription_list ) {

			$subscription = wcs_get_subscription( $subscription_list->get_id() );
			$expiry       = $subscription->get_date( 'end' );

			// The subscription does not expire, no need to extend the date.
			if ( empty( $expiry ) || intval( '0' ) === intval( $expiry ) ) {
				continue;
			}

			$dates_to_update        = array();
			$new_expiry             = strtotime( - $no_of . ' ' . $duration, strtotime( $expiry ) );
			$dates_to_update['end'] = gmdate( 'Y-m-d H:i:s', $new_expiry );

			$order_number = sprintf(
				/* translators: The hash before the order number */
				esc_html_x(
					'#%s',
					'WooCommerce Subscriptions: The hash before the order number',
					'uncanny-automator-pro'
				),
				$subscription->get_order_number()
			);

			$order_link = sprintf(
				'<a href="%1$s">%2$s</a>',
				esc_url( wcs_get_edit_post_link( $subscription->get_id() ) ),
				$order_number
			);

			$subscription->update_dates( $dates_to_update );

			// translators: placeholder contains a link to the order's edit screen.
			$subscription->add_order_note(
				sprintf(
					esc_html_x(
						'Subscription successfully shortened by Automator. Order %s',
						'WooCommerce Subscriptions',
						'uncanny-automator-pro'
					),
					$order_link
				)
			);

			$subscription_updated = true;

		}

		$this->hydrate_tokens(
			array(
				'PRODUCT_TITLE'     => get_the_title( absint( $product_id ) ),
				'PRODUCT_ID'        => absint( $product_id ),
				'PRODUCT_URL'       => get_the_permalink( absint( $product_id ) ),
				'PRODUCT_THUMB_URL' => get_the_post_thumbnail_url( absint( $product_id ) ),
				'PRODUCT_THUMB_ID'  => get_post_thumbnail_id( absint( $product_id ) ),
			)
		);

		if ( false === $subscription_updated ) {

			throw new \Exception(
				esc_html_x(
					'The subscription has no end date.',
					'WooCommerce Subscriptions',
					'uncanny-automator-pro'
				)
			);

		}

		return true;
	}
}
