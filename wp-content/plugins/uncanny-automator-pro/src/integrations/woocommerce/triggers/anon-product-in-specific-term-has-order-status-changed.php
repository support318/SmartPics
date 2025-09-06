<?php

namespace Uncanny_Automator_Pro;

/**
 * Class ANON_PRODUCT_IN_SPECIFIC_TERM_HAS_ORDER_STATUS_CHANGED
 *
 * @package Uncanny_Automator_Pro
 */
class ANON_PRODUCT_IN_SPECIFIC_TERM_HAS_ORDER_STATUS_CHANGED {

	/**
	 * @var string
	 */
	private $trigger_code;
	/**
	 * @var string
	 */
	private $trigger_meta;

	/**
	 * @var
	 */
	private $terms_triggered;

	/**
	 * Set up Automator trigger constructor.
	 * @throws \Exception
	 */
	public function __construct() {
		$this->trigger_code = 'PROD_ORDER_STATUS_CHANGED_IN_A_TERM';
		$this->trigger_meta = 'WCORDERSTATUS';
		$this->define_trigger();
	}

	/**
	 * Define and register the trigger by pushing it into the Automator object
	 * @throws \Exception
	 */
	public function define_trigger() {

		$trigger = array(
			'author'              => Automator()->get_author_name( $this->trigger_code ),
			'support_link'        => Automator()->get_author_support_link( $this->trigger_code, 'integration/woocommerce/' ),
			'is_pro'              => true,
			'integration'         => 'WC',
			'code'                => $this->trigger_code,
			/* translators: Anonymous trigger - WooCommerce */
			'sentence'            => sprintf(
				esc_attr__( '{{A product:%4$s}} in {{a specific term:%1$s}} in {{a specific taxonomy:%2$s}} has its associated order set to {{a specific status:%3$s}}', 'uncanny-automator-pro' ),
				'WC_TAXONOMY_TERM:' . $this->trigger_meta,
				'WC_TAXONOMIES:' . $this->trigger_meta,
				$this->trigger_meta,
				'WOOPRODUCT:' . $this->trigger_meta
			),
			/* translators: Anonymous trigger - WooCommerce */
			'select_option_name'  => esc_attr__( '{{A product}} in {{a specific term}} in {{a specific taxonomy}} has its associated order set to {{a specific status}}', 'uncanny-automator-pro' ),
			'action'              => 'woocommerce_order_status_changed',
			'priority'            => 999,
			'accepted_args'       => 1,
			'type'                => 'anonymous',
			'validation_function' => array( $this, 'wc_order_status_changed' ),
			'options_callback'    => array( $this, 'load_options' ),
		);

		Automator()->register->trigger( $trigger );
	}

	/**
	 * @return array
	 */
	public function load_options() {
		$products = Automator()->helpers->recipe->woocommerce->pro->all_wc_products( __( 'Product', 'uncanny-automator' ) );
		// Add any product
		$products['options'] = array( '-1' => __( 'Any product', 'uncanny-automator' ) ) + $products['options'];

		$status = Automator()->helpers->recipe->woocommerce->options->wc_order_statuses( null, $this->trigger_meta );
		// Add any status
		$status['options']         = array( '-1' => __( 'Any status', 'uncanny-automator' ) ) + $status['options'];
		$status['relevant_tokens'] = array();

		$taxonomies                    = Automator()->helpers->recipe->woocommerce->options->pro->wc_taxonomies(
			null,
			'WC_TAXONOMIES',
			array(
				'is_ajax'      => true,
				'target_field' => 'WC_TAXONOMY_TERM',
				'endpoint'     => 'select_all_terms_by_SELECTED_TAXONOMY',
			)
		);
		$taxonomies['relevant_tokens'] = array();

		$terms = Automator()->helpers->recipe->field->select_field( 'WC_TAXONOMY_TERM', esc_attr__( 'Term', 'uncanny-automator-pro' ) );

		$terms['relevant_tokens'] = array();

		$options_array = array(
			'options_group' => array(
				$this->trigger_meta => array(
					$products,
					$taxonomies,
					$terms,
					$status,
					array(
						'option_code'     => 'COMBINESAMETERMS',
						'label'           => _x( 'Output products in the same taxonomy in a single row', 'WooCommerce', 'uncanny-automator-pro' ),
						'input_type'      => 'checkbox',
						'is_toggle'       => true,
						'relevant_tokens' => array(),
					),
				),
			),
		);

		return Automator()->utilities->keep_order_of_options( $options_array );
	}

	/**
	 * Validate if trigger matches the condition.
	 *
	 * @param $order_id
	 */
	public function wc_order_status_changed( $order_id ) {

		if ( ! $order_id ) {
			return;
		}

		$order = wc_get_order( $order_id );

		if ( ! $order ) {
			return;
		}
		$to_complete = $this->prepare_for_completion( $order );

		if ( empty( $to_complete ) ) {
			return;
		}

		foreach ( $to_complete as $complete_run ) {
			$pass_args                   = $complete_run;
			$return_args                 = $this->run_trigger( $pass_args );
			$complete_run['return_args'] = $return_args;

			do_action( 'uap_wc_save_order_item_meta_by_term', $complete_run );
			$this->complete_trigger( $return_args );
		}
	}

	/**
	 * @param $order_id
	 *
	 * @return \WC_Order|object
	 */
	public function validate_order( $order_id ) {
		if ( ! $order_id ) {
			return (object) array();
		}

		return wc_get_order( $order_id );
	}

	/**
	 * Run the trigger when all conditions have met
	 *
	 * @param $user_id
	 * @param $recipe_id
	 * @param $trigger_id
	 *
	 * @return array|null
	 */
	public function run_trigger( $pass_args ) {

		return Automator()->process->user->maybe_add_trigger_entry( $pass_args, false );
	}

	/**
	 * Completing the trigger
	 *
	 * @param $args
	 */
	public function complete_trigger( $args ) {
		if ( empty( $args ) ) {
			return;
		}

		foreach ( $args as $result ) {
			if ( true === $result['result'] ) {
				Automator()->process->user->maybe_trigger_complete( $result['args'] );
			}
		}
	}

	/**
	 * @param $items
	 * @param $tax
	 *
	 * @return array
	 */
	public function sort_products_by_cat( $items, $tax ) {
		$prods         = array();
		$terms_product = array();
		/** @var \WC_Order_Item_Product $item */
		foreach ( $items as $item ) {
			/** @var \WC_Product $product */
			$product    = $item->get_product();

			if ( ! is_object( $product ) || ! method_exists( $product, 'get_id' ) ) {
				continue;
			}

			$product_id = (int) $product->get_id();
			$term_ids   = wp_get_object_terms( $product_id, $tax );
			if ( empty( $term_ids ) ) {
				continue;
			}

			foreach ( $term_ids as $term ) {
				$data = array(
					'product_id' => $product_id,
					'item_id'    => $item->get_id(),
					'name'       => $product->get_name(),
					'subtotal'   => $item->get_total(),
					'qty'        => $item->get_quantity(),
					'tax'        => $item->get_subtotal_tax(),
					'term'       => $term,
				);

				$terms_product[ $term->term_id ][ $product_id ] = $data;

				$prods[ $product_id ]['item']      = $data;
				$prods['items'][ $item->get_id() ] = $data;
				$prods[ $product_id ]['product']   = $product;
				$prods[ $product_id ]['term']      = $term;
			}
		}

		return array(
			'terms'    => $terms_product,
			'products' => $prods,
		);
	}

	/**
	 * @param $order
	 *
	 * @return array
	 */
	public function get_selected_recipes_data( $order ) {
		$selected_recipes = array();
		$to_status        = $order->get_status();

		$recipes = Automator()->get->recipes_from_trigger_code( $this->trigger_code );
		if ( empty( $recipes ) ) {
			return $selected_recipes;
		}

		$required_taxonomy = Automator()->get->meta_from_recipes( $recipes, 'WC_TAXONOMIES' );
		if ( empty( $required_taxonomy ) ) {
			return $selected_recipes;
		}

		$required_term = Automator()->get->meta_from_recipes( $recipes, 'WC_TAXONOMY_TERM' );
		if ( empty( $required_term ) ) {
			return $selected_recipes;
		}

		$required_status = Automator()->get->meta_from_recipes( $recipes, $this->trigger_meta );
		if ( empty( $required_status ) ) {
			return $selected_recipes;
		}

		$required_product = Automator()->get->meta_from_recipes( $recipes, 'WOOPRODUCT' );
		if ( empty( $required_product ) ) {
			return $selected_recipes;
		}

		$items = $order->get_items();
		if ( empty( $items ) ) {
			return $selected_recipes;
		}

		$required_combine = Automator()->get->meta_from_recipes( $recipes, 'COMBINESAMETERMS' );
		$order_products   = $this->get_order_products( $order );

		foreach ( $recipes as $recipe_id => $recipe ) {
			foreach ( $recipe['triggers'] as $trigger ) {
				$trigger_id = absint( $trigger['ID'] );
				$recipe_id  = absint( $recipe_id );

				// Check if taxonomy is set
				if ( ! isset( $required_taxonomy[ $recipe_id ] ) || ! isset( $required_taxonomy[ $recipe_id ][ $trigger_id ] ) ) {
					continue;
				}

				// Check if term is set
				if ( ! isset( $required_term[ $recipe_id ] ) || ! isset( $required_term[ $recipe_id ][ $trigger_id ] ) ) {
					continue;
				}

				// Check if product is set
				if ( ! isset( $required_product[ $recipe_id ] ) || ! isset( $required_product[ $recipe_id ][ $trigger_id ] ) ) {
					continue;
				}

				// Check if status is set
				if ( ! isset( $required_status[ $recipe_id ] ) || ! isset( $required_status[ $recipe_id ][ $trigger_id ] ) ) {
					continue;
				}

				// Check selected product exists in the order
				if (
					intval( '-1' ) !== intval( $required_product[ $recipe_id ][ $trigger_id ] ) &&
					in_array( absint( $required_product[ $recipe_id ][ $trigger_id ] ), $order_products, true )
				) {
					continue;
				}

				// Check if the order status matches the recipe requirement
				if (
					intval( '-1' ) !== intval( $required_status[ $recipe_id ][ $trigger_id ] ) &&
					str_replace( 'wc-', '', $required_status[ $recipe_id ][ $trigger_id ] ) !== $to_status
				) {
					continue;
				}

				$combine = 'false' === (string) $required_combine[ $recipe_id ][ $trigger_id ] ? 'no' : 'yes';

				$selected_recipes[ $recipe_id ] = array(
					'recipe_id'  => $recipe_id,
					'trigger_id' => $trigger_id,
					'taxonomy'   => $required_taxonomy[ $recipe_id ][ $trigger_id ],
					'term'       => $required_term[ $recipe_id ][ $trigger_id ],
					'combine'    => $combine,
				);
			}
		}

		return $selected_recipes;
	}

	/**
	 * @param $order
	 *
	 * @return array
	 */
	public function prepare_for_completion( $order ) {
		$recipes = $this->get_selected_recipes_data( $order );
		if ( empty( $recipes ) ) {
			return array();
		}
		$hook_args = array();
		foreach ( $recipes as $recipe_id => $data ) {
			$trigger_id      = $data['trigger_id'];
			$taxonomy        = $data['taxonomy'];
			$term            = $data['term'];
			$combine         = $data['combine'];
			$product_details = $this->sort_products_by_cat( $order->get_items(), $taxonomy );
			foreach ( $order->get_items() as $item ) {
				$product = $item->get_product();
				if ( false === $product || ! $product instanceof \WC_Product ) {
					continue; // skip deleted/invalid products
				}

				$product_id = $product->get_id();

				// Get product terms
				$term_ids = wp_get_post_terms( $product_id, $taxonomy, array( 'fields' => 'ids' ) );

				// Product does not have any terms in the selected tax
				if ( empty( $term_ids ) ) {
					continue;
				}

				$term_ids = array_map( 'absint', $term_ids );
				// Check if the recipe and product has the same terms selected
				if (
					intval( '-1' ) !== intval( $term ) &&
					! in_array( absint( $term ), $term_ids, true )
				) {
					continue;
				}

				$additional_details = array(
					'item_id'         => $item->get_id(),
					'order_id'        => $order->get_id(),
					'term_ids'        => $term_ids,
					'product_details' => $product_details,
					'combine_rows'    => $combine,
					'recipe_details'  => $data,
				);

				if ( 'yes' === $combine ) {
					foreach ( $term_ids as $_term_id ) {
						if ( ! array_key_exists( $_term_id, $hook_args ) ) {
							// Make sure to update the term from -1 to the term ID
							$additional_details['recipe_details']['term'] = $_term_id;
							// Adding values
							$hook_args[ $_term_id ] = array(
								'code'               => $this->trigger_code,
								'meta'               => $this->trigger_meta,
								'user_id'            => $order->get_customer_id(),
								'recipe_to_match'    => $recipe_id,
								'trigger_to_match'   => $trigger_id,
								'ignore_post_id'     => true,
								'is_signed_in'       => 0 !== $order->get_customer_id(),
								'additional_details' => $additional_details,
							);
						}
					}
					continue;
				}
				$hook_args[] = array(
					'code'               => $this->trigger_code,
					'meta'               => $this->trigger_meta,
					'user_id'            => $order->get_customer_id(),
					'recipe_to_match'    => $recipe_id,
					'trigger_to_match'   => $trigger_id,
					'ignore_post_id'     => true,
					'is_signed_in'       => 0 !== $order->get_customer_id(),
					'additional_details' => $additional_details,
				);
			}
		}

		return $hook_args;
	}

	/**
	 * @param \WC_Order $order
	 *
	 * @return array
	 */
	public function get_order_products( \WC_Order $order ) {
		$products = array();
		foreach ( $order->get_items() as $item ) {
			$products[] = (int) $item->get_product_id();
		}

		return $products;
	}

}
