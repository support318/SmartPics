<?php

namespace Uncanny_Automator_Pro;

/**
 * Class ANON_NEWSLETTER_SUBSCRIBES_LIST
 * @package Uncanny_Automator_Pro
 */
class ANON_NEWSLETTER_SUBSCRIBES_LIST {

	/**
	 * Integration code
	 * @var string
	 */
	public static $integration = 'NEWSLETTER';

	/**
	 * @var string
	 */
	private $trigger_code;
	/**
	 * @var string
	 */
	private $trigger_meta;

	/**
	 * Set up Automator trigger constructor.
	 */
	public function __construct() {
		$this->trigger_code = 'SUBSCRIBESLIST';
		$this->trigger_meta = 'NEWSLETTERLIST';
		$this->define_trigger();
	}

	/**
	 * Define and register the trigger by pushing it into the Automator object
	 */
	public function define_trigger() {

		$trigger = array(
			'author'              => Automator()->get_author_name( $this->trigger_code ),
			'support_link'        => Automator()->get_author_support_link( $this->trigger_code, 'integration/newsletter/' ),
			'integration'         => self::$integration,
			'is_pro'              => true,
			'code'                => $this->trigger_code,
			/* translators: Anonymous trigger - Newsletter */
			'sentence'            => sprintf( esc_attr_x( 'A subscription form is submitted with  {{a specific list:%1$s}}', 'Newsletter', 'uncanny-automator-pro' ), $this->trigger_meta ),
			/* translators: Anonymous trigger - Newsletter */
			'select_option_name'  => esc_attr_x( 'A subscription form is submitted with  {{a specific list}}', 'Newsletter', 'uncanny-automator-pro' ),
			// THIS ONLY FIRES IN THE FRONTEND AND NOT IN WP-ADMIN
			'action'              => 'newsletter_user_post_subscribe',
			'type'                => 'anonymous',
			'priority'            => 20,
			'accepted_args'       => 1,
			'validation_function' => array( $this, 'subscribes_to_list' ),
			'options_callback'    => array( $this, 'load_options' ),
		);

		Automator()->register->trigger( $trigger );
	}

	/**
	 * @return array
	 */
	public function load_options() {
		return Automator()->utilities->keep_order_of_options( array( 'options' => array( $this->options() ) ) );
	}

	/**
	 * @return array
	 */
	private function options() {
		$lists = $this->get_newsletter_list();

		$option = array(
			'option_code'     => $this->trigger_meta,
			'label'           => esc_attr_x( 'Lists', 'Newsletter', 'uncanny-automator-pro' ),
			'input_type'      => 'select',
			'required'        => true,
			'supports_tokens' => true,
			'options'         => $lists,
		);

		return $option;
	}

	/**
	 * Get the newsletter lists.
	 *
	 * @return $lists array The collection of list.
	 */
	private function get_newsletter_list() {
		$lists = array( '-1' => esc_attr_x( 'Any list', 'Newsletter', 'uncanny-automator-pro' ) );

		if ( class_exists( '\Newsletter' ) ) {
			$newsletter_lists = \Newsletter::instance()->get_lists();
			if ( ! empty( $newsletter_lists ) ) {
				foreach ( $newsletter_lists as $list ) {
					if ( $list->is_public() ) {
						$list_id           = sprintf( 'list_%d', $list->id );
						$lists[ $list_id ] = $list->name;
					}
				}
			}
		}

		return $lists;
	}

	/**
	 * @param $user (object)
	 *
	 * @return object
	 */
	public function subscribes_to_list( $user ) {

		// This is a newsletter subscriber user ID and not a wp user ID
		$user_id = $user->id;

		/*
		 * When a subscription fires, two things happen
		 * The user in the `wp_newsletter` table gets updated.
		 * The plugins logs what happened in the `wp_newsletter_user_logs` table.
		 *  -- the log date sets source(subscribe in our case), the lists selected in the form, the user id, and
		 *     the date.
		 *
		 * We have some information to work with.
		 *
		 * The time in the logs and in the user object may be different but will be very close. The DB inserts both use
		 * time() and it is highly unlikely the elapsed time will be more than a second.
		 */

		// Get all the subscribe logs for the user
		global $wpdb;

		$logs = $wpdb->get_row(
			$wpdb->prepare(
				"SELECT MAX(id), data FROM {$wpdb->prefix}newsletter_user_logs WHERE user_id = %d AND source = %s",
				$user_id,
				'subscribe'
			)
		);

		$recipes              = Automator()->get->recipes_from_trigger_code( $this->trigger_code );
		$list_id_from_trigger = Automator()->get->meta_from_recipes( $recipes, $this->trigger_meta );

		if ( null !== $logs ) {
			if ( isset( $logs->data ) && null !== $logs->data ) {

				/*
				 * json_decode($logs->data) will create:
				 *
				 * object(stdClass)[1620]
				 * public 'list_1' => string '1' ... '1' means the list was selected
				 * public 'list_2' => string '0' ... '0' means the list was not selected
				 * public 'status' => string 'C' ... 'C' means the subscription in confirmed(irrelevant for this trigger)
				 */

				$data = json_decode( $logs->data );
			} else {
				// Convert object to array
				$vars = get_object_vars( $user );
				// Filter keys that start with 'list_'
				$list_items = array_filter(
					$vars,
					function ( $key ) {
						return str_starts_with( $key, 'list_' );
					},
					ARRAY_FILTER_USE_KEY
				);
				$data       = (object) $list_items;
			}
		}

		foreach ( $recipes as $recipe_id => $recipe ) {
			foreach ( $recipe['triggers'] as $trigger ) {
				$trigger_id = $trigger['ID'];//return early for all memberships
				$list_id    = $list_id_from_trigger[ $recipe_id ][ $trigger_id ];
				if ( '-1' === $list_id ) {
					$matched_recipe_ids[] = array(
						'recipe_id'  => $recipe_id,
						'trigger_id' => $trigger_id,
						'lists'      => $data,
					);

					break;
				} elseif ( isset( $data->$list_id ) && '1' === $data->$list_id ) {
					// Handle a specific list option
					$matched_recipe_ids[] = array(
						'recipe_id'  => $recipe_id,
						'trigger_id' => $trigger_id,
						'lists'      => $data,
					);
				}
			}
		}

		if ( ! empty( $matched_recipe_ids ) ) {
			foreach ( $matched_recipe_ids as $matched_recipe_id ) {
				$args = array(
					'code'             => $this->trigger_code,
					'meta'             => $this->trigger_meta,
					'user_id'          => $user_id,
					'recipe_to_match'  => $matched_recipe_id['recipe_id'],
					'trigger_to_match' => $matched_recipe_id['trigger_id'],
					'ignore_post_id'   => true,
				);

				$lists = $matched_recipe_id['lists'];

				$result = Automator()->maybe_add_trigger_entry( $args, false );

				if ( $result ) {
					foreach ( $result as $r ) {
						if ( true === $r['result'] ) {
							$this->save_newsletter_meta( $user, $lists, $matched_recipe_id, $r );
							Automator()->maybe_trigger_complete( $r['args'] );
						}
					}
				}
			}
		}

		return $user;
	}

	/**
	 * @param $user
	 * @param $data
	 * @param $args
	 */
	private function save_newsletter_meta( $user, $lists, $matched_recipe_id, $r ) {

		$trigger_id     = (int) $matched_recipe_id['trigger_id'];
		$trigger_log_id = (int) $r['args']['trigger_log_id'];
		$run_number     = (int) $r['args']['run_number'];

		$args = array(
			'user_id'        => 0,
			'trigger_id'     => $trigger_id,
			'meta_key'       => 'USEREMAIL',
			'meta_value'     => $user->email,
			'run_number'     => $run_number, //get run number
			'trigger_log_id' => $trigger_log_id,
		);

		Automator()->insert_trigger_meta( $args );

		$args = array(
			'user_id'        => 0,
			'trigger_id'     => $trigger_id,
			'meta_key'       => 'LISTSDATA',
			'meta_value'     => maybe_serialize( $lists ),
			'run_number'     => $run_number, //get run number
			'trigger_log_id' => $trigger_log_id,
		);

		Automator()->insert_trigger_meta( $args );
	}
}
