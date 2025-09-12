<?php

namespace Uncanny_Automator_Pro;

use Uncanny_Automator\Flsupport_Helpers;
/**
 * Class Flsupport_Pro_Helpers
 *
 * @package Uncanny_Automator
 */
class Flsupport_Pro_Helpers extends Flsupport_Helpers {

	public function __construct() {
		// Selectively load options
		if ( property_exists( '\Uncanny_Automator\Flsupport_Helpers', 'load_options' ) ) {
			$this->load_options = Automator()->helpers->recipe->maybe_load_trigger_options( __CLASS__ );
		}
	}

	/**
	 * @param string $label
	 * @param string $option_code
	 * @param array $args
	 *
	 * @return mixed
	 */
	public function all_ticket_priorities( $label, $option_code, $args = array() ) {
		if ( ! $this->load_options ) {
			return Automator()->helpers->recipe->build_default_options_array( $label, $option_code );
		}

		if ( ! $label ) {
			$label = esc_attr__( 'Priority', 'uncanny-automator-pro' );
		}

		$args = wp_parse_args(
			$args,
			array(
				'uo_include_any' => false,
				'uo_any_label'   => esc_attr__( 'Any priority', 'uncanny-automator-pro' ),
			)
		);

		$options = array();

		if ( $args['uo_include_any'] ) {
			$options[-1] = $args['uo_any_label'];
		}

		$option = array(
			'option_code'     => $option_code,
			'label'           => $label,
			'input_type'      => 'select',
			'required'        => true,
			'options'         => $this->get_all_ticket_priorities_options( $options ),
			'relevant_tokens' => array(),
		);

		return apply_filters( 'uap_option_all_flsupport_ticket_priorities', $option );
	}

	/**
	 * @param string $label
	 * @param string $option_code
	 * @param array $args
	 *
	 * @return mixed
	 */
	public function all_person_types( $label, $option_code, $args = array() ) {
		if ( ! $this->load_options ) {
			return Automator()->helpers->recipe->build_default_options_array( $label, $option_code );
		}

		if ( ! $label ) {
			$label = esc_attr__( 'Customer or agent', 'uncanny-automator-pro' );
		}

		$options = array(
			'-1'       => esc_attr__( 'anyone', 'uncanny-automator-pro' ),
			'customer' => esc_attr__( 'a customer', 'uncanny-automator-pro' ),
			'agent'    => esc_attr__( 'an agent', 'uncanny-automator-pro' ),
		);

		$option = array(
			'option_code'     => $option_code,
			'label'           => $label,
			'input_type'      => 'select',
			'required'        => true,
			'options'         => $options,
			'relevant_tokens' => array(),
		);

		return apply_filters( 'uap_option_all_flsupport_customer_agent', $option );
	}

	/**
	 * @param string $user_id
	 * @return (string)
	 */
	public function get_user_person_type( $user_id ) {
		$person = \FluentSupport\App\Models\Person::where( 'user_id', (int) $user_id )->first();
		if ( $person ) {
			return $person->person_type;
		}
		return '';
	}

	public function get_matched_person_priority_recipes( $ticket, $person, $user_id, $trigger_code, $priority_key ) {
		$recipes = Automator()->get->recipes_from_trigger_code( $trigger_code );

		if ( empty( $recipes ) ) {
			return;
		}

		$person_types = Automator()->get->meta_from_recipes( $recipes, 'flsupport_person_type' );
		$priorities   = Automator()->get->meta_from_recipes( $recipes, $priority_key );

		$matched_recipe_ids = array();
		if ( empty( $person_types ) || empty( $priorities ) ) {
			return $matched_recipe_ids;
		}

		$user_person_type = (string) Automator()->helpers->recipe->fluent_support->pro->get_user_person_type( $user_id );

		foreach ( $recipes as $recipe_id => $recipe ) {
			foreach ( $recipe['triggers'] as $trigger ) {
				$trigger_id  = $trigger['ID'];
				$person_type = isset( $person_types[ $recipe_id ][ $trigger_id ] ) ? $person_types[ $recipe_id ][ $trigger_id ] : '';
				$priority    = isset( $priorities[ $recipe_id ][ $trigger_id ] ) ? $priorities[ $recipe_id ][ $trigger_id ] : '0';

				if ( ( (string) $person_type === $user_person_type || '-1' === (string) $person_type )
					&&
					( (string) $ticket->client_priority === (string) $priority || '-1' === (string) $priority ) ) {

					$matched_recipe_ids[] = array(
						'recipe_id'  => $recipe_id,
						'trigger_id' => $trigger_id,
					);
				}
			}
		}

		return $matched_recipe_ids;
	}

	public function get_matched_priority_customer_recipes( $ticket, $person, $user_id, $trigger_code, $priority_key = 'flsupport_ticket_priority' ) {
		return $this->get_matched_priority_recipes_by_person( 'customer', $ticket, $person, $user_id, $trigger_code, $priority_key );
	}

	public function get_matched_priority_agent_recipes( $ticket, $person, $user_id, $trigger_code, $priority_key = 'flsupport_ticket_priority' ) {
		return $this->get_matched_priority_recipes_by_person( 'agent', $ticket, $person, $user_id, $trigger_code, $priority_key );
	}

	private function get_matched_priority_recipes_by_person( $person_type, $ticket, $person, $user_id, $trigger_code, $priority_key = 'flsupport_ticket_priority' ) {
		$recipes = Automator()->get->recipes_from_trigger_code( $trigger_code );

		if ( empty( $recipes ) ) {
			return;
		}

		$priorities = Automator()->get->meta_from_recipes( $recipes, $priority_key );

		$matched_recipe_ids = array();
		if ( empty( $priorities ) ) {
			return $matched_recipe_ids;
		}

		$user_person_type = (string) Automator()->helpers->recipe->fluent_support->pro->get_user_person_type( $user_id );

		foreach ( $recipes as $recipe_id => $recipe ) {
			foreach ( $recipe['triggers'] as $trigger ) {
				$trigger_id = $trigger['ID'];
				$priority   = isset( $priorities[ $recipe_id ][ $trigger_id ] ) ? $priorities[ $recipe_id ][ $trigger_id ] : '0';
				if ( ( (string) $person_type === $user_person_type )
					&&
					( (string) $ticket->client_priority === (string) $priority || '-1' === (string) $priority ) ) {
					$matched_recipe_ids[] = array(
						'recipe_id'  => $recipe_id,
						'trigger_id' => $trigger_id,
					);
				}
			}
		}

		return $matched_recipe_ids;
	}

	/**
	 * @param string $label
	 * @param string $option_code
	 * @param array $args
	 *
	 * @return mixed
	 */
	public function all_products( $label, $option_code, $args = array() ) {
		if ( ! $this->load_options ) {
			return Automator()->helpers->recipe->build_default_options_array( $label, $option_code );
		}

		if ( ! $label ) {
			$label = esc_attr__( 'Product', 'uncanny-automator-pro' );
		}

		$args = wp_parse_args(
			$args,
			array(
				'uo_include_any' => false,
				'uo_any_label'   => esc_attr__( 'Any product', 'uncanny-automator-pro' ),
			)
		);

		$options = array();

		if ( $args['uo_include_any'] ) {
			$options[-1] = $args['uo_any_label'];
		}

		$option = array(
			'option_code'     => $option_code,
			'label'           => $label,
			'input_type'      => 'select',
			'required'        => true,
			'options'         => $this->get_all_product_options( $options ),
			'relevant_tokens' => array(),
		);

		return apply_filters( 'uap_option_all_flsupport_ticket_products', $option );
	}

	public function get_matched_person_product_recipes( $ticket, $person, $user_id, $trigger_code, $product_option_key = 'flsupport_ticket_product' ) {

		if ( 0 === (int) $ticket->product_id ) {
			return;
		}

		$recipes = Automator()->get->recipes_from_trigger_code( $trigger_code );

		if ( empty( $recipes ) ) {
			return;
		}

		$person_types = Automator()->get->meta_from_recipes( $recipes, 'flsupport_person_type' );
		$products     = Automator()->get->meta_from_recipes( $recipes, $product_option_key );

		$matched_recipe_ids = array();
		if ( empty( $person_types ) || empty( $products ) ) {
			return $matched_recipe_ids;
		}

		$user_person_type = (string) Automator()->helpers->recipe->fluent_support->pro->get_user_person_type( $user_id );

		foreach ( $recipes as $recipe_id => $recipe ) {
			foreach ( $recipe['triggers'] as $trigger ) {
				$trigger_id  = $trigger['ID'];
				$person_type = isset( $person_types[ $recipe_id ][ $trigger_id ] ) ? $person_types[ $recipe_id ][ $trigger_id ] : '';
				$product_id  = isset( $products[ $recipe_id ][ $trigger_id ] ) ? $products[ $recipe_id ][ $trigger_id ] : -1;

				if ( ( (string) $person_type === $user_person_type || '-1' === (string) $person_type )
					&&
					( (int) $ticket->product_id === (int) $product_id || '-1' === (string) $product_id ) ) {
					$matched_recipe_ids[] = array(
						'recipe_id'  => $recipe_id,
						'trigger_id' => $trigger_id,
					);
				}
			}
		}

		return $matched_recipe_ids;
	}

	public function get_all_products() {
		return json_decode( \FluentSupport\App\Models\Product::select( array( 'id', 'title' ) )->get(), true );
	}

	public function get_all_product_options( $options = null ) {
		$products = $this->get_all_products();

		if ( ! is_array( $options ) ) {
			$options = array();
		}

		foreach ( $products as $product ) {
			$options[ sanitize_key( $product['id'] ) ] = esc_html( $product['title'] );
		}

		return $options;
	}

	public function get_all_ticket_priorities() {
		return \FluentSupport\App\Services\Helper::customerTicketPriorities();
	}

	public function get_all_ticket_priorities_options( $options = null ) {
		$priorities = $this->get_all_ticket_priorities();

		if ( ! is_array( $options ) ) {
			$options = array();
		}

		foreach ( $priorities as $k => $v ) {
			$options[ sanitize_key( $k ) ] = esc_html( $v );
		}

		return $options;
	}

	public function get_matched_product_persontype_recipes( $person_type, $ticket, $person, $user_id, $trigger_code, $product_option_key = 'flsupport_ticket_product' ) {

		if ( 0 === (int) $ticket->product_id ) {
			return;
		}

		$recipes = Automator()->get->recipes_from_trigger_code( $trigger_code );

		if ( empty( $recipes ) ) {
			return;
		}

		$products = Automator()->get->meta_from_recipes( $recipes, $product_option_key );

		$matched_recipe_ids = array();
		if ( empty( $products ) ) {
			return $matched_recipe_ids;
		}

		$user_person_type = (string) Automator()->helpers->recipe->fluent_support->pro->get_user_person_type( $user_id );

		foreach ( $recipes as $recipe_id => $recipe ) {
			foreach ( $recipe['triggers'] as $trigger ) {
				$trigger_id = $trigger['ID'];
				$product_id = isset( $products[ $recipe_id ][ $trigger_id ] ) ? $products[ $recipe_id ][ $trigger_id ] : -1;

				if ( (string) $person_type === $user_person_type
					&&
					( (int) $ticket->product_id === (int) $product_id || '-1' === (string) $product_id ) ) {
					$matched_recipe_ids[] = array(
						'recipe_id'  => $recipe_id,
						'trigger_id' => $trigger_id,
					);
				}
			}
		}

		return $matched_recipe_ids;
	}

	public function maybe_process_matched_recipes( $matched_recipe_ids, $base_args, $insert_trigger_meta_dataset ) {

		if ( empty( $matched_recipe_ids ) || ! is_array( $matched_recipe_ids ) || ! is_array( $base_args ) || ! is_array( $insert_trigger_meta_dataset ) ) {
			return;
		}

		foreach ( $matched_recipe_ids as $matched_recipe_id ) {

			$args                     = $base_args;
			$args['recipe_to_match']  = $matched_recipe_id['recipe_id'];
			$args['trigger_to_match'] = $matched_recipe_id['trigger_id'];

			$result = Automator()->maybe_add_trigger_entry( $args, false );

			if ( empty( $result ) ) {
				continue;
			}

			foreach ( $result as $r ) {

				if ( false === $r['result'] ) {
					continue;
				}

				$trigger_id     = (int) $r['args']['trigger_id'];
				$user_id        = (int) $r['args']['user_id'];
				$trigger_log_id = (int) $r['args']['trigger_log_id'];
				$run_number     = (int) $r['args']['run_number'];

				$trigger_meta = array(
					'user_id'        => $user_id,
					'trigger_id'     => $trigger_id,
					'trigger_log_id' => $trigger_log_id,
					'run_number'     => $run_number,
				);

				foreach ( $insert_trigger_meta_dataset as $insert_trigger_meta_data ) {
					if ( isset( $insert_trigger_meta_data['key'] ) && isset( $insert_trigger_meta_data['value'] ) ) {
						$trigger_meta['meta_key']   = sanitize_text_field( $insert_trigger_meta_data['key'] );
						$trigger_meta['meta_value'] = sanitize_text_field( $insert_trigger_meta_data['value'] );
						Automator()->insert_trigger_meta( $trigger_meta );
					}
				}

				Automator()->maybe_trigger_complete( $r['args'] );
			}
		}
	}
}



