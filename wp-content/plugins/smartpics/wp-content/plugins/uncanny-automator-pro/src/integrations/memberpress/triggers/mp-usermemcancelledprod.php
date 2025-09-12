<?php //phpcs:ignore WordPress.Files.FileName.InvalidClassFileName

namespace Uncanny_Automator_Pro;

/**
 *
 */
class MP_USERMEMCANCELLEDPROD {

	/**
	 * Integration code
	 *
	 * @var string
	 */
	public static $integration = 'MP';

	/**
	 * @var string
	 */
	private $trigger_code;
	/**
	 * @var string
	 */
	private $trigger_meta;


	/**
	 * SetAutomatorTriggers constructor.
	 */
	public function __construct() {
		$this->trigger_code = 'USERMECANCELLEDPROD';
		$this->trigger_meta = 'MPPRODUCT';
		$this->define_trigger();

		// Replace old action hook with the new one
		add_action(
			'admin_init',
			array( $this, 'maybe_migrate_action_hook' ),
			99
		);
	}


	/**
	 * Define and register the trigger by pushing it into the Automator object
	 */
	public function define_trigger() {

		$trigger = array(
			'author'              => Automator()->get_author_name(),
			'support_link'        => Automator()->get_author_support_link( $this->trigger_code, 'integration/memberpress/' ),
			'integration'         => self::$integration,
			'code'                => $this->trigger_code,
			'is_pro'              => true,
			/* translators: Logged-in trigger - MemberPress */
			'sentence'            => sprintf( esc_attr__( "A user's membership to {{a specific product:%1\$s}} is cancelled", 'uncanny-automator-pro' ), $this->trigger_meta ),
			/* translators: Logged-in trigger - MemberPress */
			'select_option_name'  => esc_attr__( "A user's membership to {{a specific product}} is cancelled", 'uncanny-automator-pro' ),
			'action'              => 'mepr-event-subscription-stopped',
			'priority'            => 99,
			'accepted_args'       => 1,
			'validation_function' => array( $this, 'mp_product_expired' ),
			'options_callback'    => array( $this, 'load_options' ),
		);

		Automator()->register->trigger( $trigger );
	}

	/**
	 * @return array[]
	 */
	public function load_options() {
		return Automator()->utilities->keep_order_of_options(
			array(
				'options' => array(
					Automator()->helpers->recipe->memberpress->options->pro->all_memberpress_products_recurring( null, $this->trigger_meta, array( 'uo_include_any' => true ) ),
				),
			)
		);
	}

	/**
	 * @param \MeprEvent $event
	 */
	public function mp_product_expired( $event ) {
		$sub     = $event->get_data();
		$product = $sub->product();
		$user    = $sub->user();

		$product_id = absint( $product->ID );
		$user_id    = absint( $user->rec->ID );

		$recipes          = Automator()->get->recipes_from_trigger_code( $this->trigger_code );
		$required_product = Automator()->get->meta_from_recipes( $recipes, $this->trigger_meta );

		$matched_recipe_ids = array();

		//Add where option is set to Any product
		foreach ( $recipes as $recipe_id => $recipe ) {
			foreach ( $recipe['triggers'] as $trigger ) {
				$trigger_id = $trigger['ID'];//return early for all products
				if ( absint( $required_product[ $recipe_id ][ $trigger_id ] ) === $product_id || intval( '-1' ) === intval( $required_product[ $recipe_id ][ $trigger_id ] ) ) {
					$matched_recipe_ids[] = array(
						'recipe_id'  => $recipe_id,
						'trigger_id' => $trigger_id,
					);
				}
			}
		}

		if ( empty( $matched_recipe_ids ) ) {
			return;
		}
		foreach ( $matched_recipe_ids as $matched_recipe_id ) {
			$args = array(
				'code'             => $this->trigger_code,
				'meta'             => $this->trigger_meta,
				'user_id'          => $user_id,
				'recipe_to_match'  => $matched_recipe_id['recipe_id'],
				'trigger_to_match' => $matched_recipe_id['trigger_id'],
				'ignore_post_id'   => true,
				'is_signed_in'     => true,
			);
			$args = Automator()->maybe_add_trigger_entry( $args, false );

			if ( $args ) {
				foreach ( $args as $result ) {
					if ( true === $result['result'] ) {
						$trigger_meta = array(
							'user_id'        => $user_id,
							'trigger_id'     => $result['args']['trigger_id'],
							'trigger_log_id' => $result['args']['trigger_log_id'],
							'run_number'     => $result['args']['run_number'],
						);

						$trigger_meta['meta_key']   = $this->trigger_meta;
						$trigger_meta['meta_value'] = $product_id;
						Automator()->insert_trigger_meta( $trigger_meta );

						Automator()->maybe_trigger_complete( $result['args'] );
					}
				}
			}
		}
	}

	public function maybe_migrate_action_hook() {
		$migration_key = 'automator_pro_mp_' . strtolower( $this->trigger_code );
		if ( 'yes' === automator_pro_get_option( $migration_key, 'no' ) ) {
			return;
		}
		global $wpdb;

		// Select the trigger's post IDs
		$post_ids = $wpdb->get_col(
			$wpdb->prepare(
				"SELECT post_id 
				FROM $wpdb->postmeta 
				WHERE meta_key = %s AND meta_value = %s",
				'code',
				$this->trigger_code
			)
		);

		if ( ! empty( $post_ids ) ) {
			// Prepare the update query
			$placeholders = implode( ',', array_fill( 0, count( $post_ids ), '%d' ) );
			$sql          = $wpdb->prepare(
				"UPDATE $wpdb->postmeta 
				 SET meta_value = %s 
				 WHERE meta_value = %s 
				 AND meta_key = %s 
				 AND post_id IN ($placeholders)",
				array_merge( array( 'mepr-event-subscription-stopped', 'mepr_subscription_transition_status', 'add_action' ), $post_ids )
			);
			$wpdb->query( $sql );
		}

		automator_pro_update_option( $migration_key, 'yes' );
	}

}
