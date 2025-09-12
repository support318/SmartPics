<?php

namespace Uncanny_Automator_Pro;

/**
 * Class WPJM_USERUPDATESAJOB
 *
 * @package Uncanny_Automator_Pro
 */
class WPJM_USERUPDATESAJOB {

	/**
	 * Integration code
	 *
	 * @var string
	 */
	public static $integration = 'WPJM';

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
		$this->trigger_code = 'WPJMJOBUPDATED';
		$this->trigger_meta = 'WPJMUSERUPDATESAJOB';
		$this->define_trigger();
	}

	/**
	 * Define and register the trigger by pushing it into the Automator object
	 */
	public function define_trigger() {
		$trigger = array(
			'author'              => Automator()->get_author_name( $this->trigger_code ),
			'support_link'        => Automator()->get_author_support_link( $this->trigger_code, 'integration/wp-job-manager/' ),
			'integration'         => self::$integration,
			'code'                => $this->trigger_code,
			'is_pro'              => true,
			/* translators: Logged-in trigger - WP Job Manager */
			'sentence'            => sprintf( esc_attr__( 'A user updates {{a job:%1$s}}', 'uncanny-automator-pro' ), $this->trigger_meta ),
			/* translators: Logged-in trigger - WP Job Manager */
			'select_option_name'  => esc_attr__( 'A user updates {{a job}}', 'uncanny-automator-pro' ),
			'action'              => 'job_manager_user_edit_job_listing',
			'priority'            => 20,
			'accepted_args'       => 3,
			'validation_function' => array( $this, 'user_updates_job' ),
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
					Automator()->helpers->recipe->wp_job_manager->options->list_wpjm_jobs( null, $this->trigger_meta ),
				),
			)
		);
	}

	/**
	 * @param $job_id
	 * @param $message
	 * @param $data
	 */
	public function user_updates_job( $job_id, $message, $data ) {

		$recipes            = Automator()->get->recipes_from_trigger_code( $this->trigger_code );
		$required_job       = Automator()->get->meta_from_recipes( $recipes, $this->trigger_meta );
		$user_id            = get_current_user_id();
		$matched_recipe_ids = array();

		if ( 0 === absint( $user_id ) ) {
			return;
		}

		foreach ( $recipes as $recipe_id => $recipe ) {
			foreach ( $recipe['triggers'] as $trigger ) {
				$trigger_id = $trigger['ID'];
				if ( absint( $required_job[ $recipe_id ][ $trigger_id ] ) === absint( $job_id ) || intval( '-1' ) === intval( $required_job[ $recipe_id ][ $trigger_id ] ) ) {
					$matched_recipe_ids[] = array(
						'recipe_id'  => $recipe_id,
						'trigger_id' => $trigger_id,
					);
				}
			}
		}

		if ( ! empty( $matched_recipe_ids ) ) {
			foreach ( $matched_recipe_ids as $matched_recipe_id ) {
				$pass_args = array(
					'code'             => $this->trigger_code,
					'meta'             => $this->trigger_meta,
					'user_id'          => $user_id,
					'recipe_to_match'  => $matched_recipe_id['recipe_id'],
					'trigger_to_match' => $matched_recipe_id['trigger_id'],
					'ignore_post_id'   => true,
				);

				$args = Automator()->maybe_add_trigger_entry( $pass_args, false );

				if ( $args ) {
					foreach ( $args as $result ) {
						if ( true === $result['result'] ) {

							$trigger_meta = array(
								'user_id'        => $user_id,
								'trigger_id'     => $result['args']['trigger_id'],
								'trigger_log_id' => $result['args']['trigger_log_id'],
								'run_number'     => $result['args']['run_number'],
							);
							Automator()->insert_trigger_meta( $trigger_meta );

							// Get the job categories.
							$categories = Automator()->helpers->recipe->wp_job_manager->pro->get_job_categories( $job_id );

							$trigger_meta['meta_key']   = 'WPJMJOBID';
							$trigger_meta['meta_value'] = maybe_serialize( $job_id );
							Automator()->insert_trigger_meta( $trigger_meta );

							$trigger_meta['meta_value'] = implode( ', ', $categories );
							Automator()->insert_trigger_meta( $trigger_meta );

							$trigger_meta['meta_key']   = 'WPJMJOBTITLE';
							$trigger_meta['meta_value'] = maybe_serialize( $data['job']['job_title'] );
							Automator()->insert_trigger_meta( $trigger_meta );

							$trigger_meta['meta_key']   = 'WPJMJOBLOCATION';
							$trigger_meta['meta_value'] = maybe_serialize( $data['job']['job_location'] );
							Automator()->insert_trigger_meta( $trigger_meta );

							$trigger_meta['meta_key']   = 'WPJMJOBDESCRIPTION';
							$trigger_meta['meta_value'] = maybe_serialize( $data['job']['job_description'] );
							Automator()->insert_trigger_meta( $trigger_meta );

							$trigger_meta['meta_key']   = 'WPJMJOBAPPURL';
							$trigger_meta['meta_value'] = maybe_serialize( $data['job']['application'] );
							Automator()->insert_trigger_meta( $trigger_meta );

							$trigger_meta['meta_key']   = 'WPJMJOBCOMPANYNAME';
							$trigger_meta['meta_value'] = maybe_serialize( $data['company']['company_name'] );
							Automator()->insert_trigger_meta( $trigger_meta );

							$trigger_meta['meta_key']   = 'WPJMJOBWEBSITE';
							$trigger_meta['meta_value'] = maybe_serialize( $data['company']['company_website'] );
							Automator()->insert_trigger_meta( $trigger_meta );

							$trigger_meta['meta_key']   = 'WPJMJOBTAGLINE';
							$trigger_meta['meta_value'] = maybe_serialize( $data['company']['company_tagline'] );
							Automator()->insert_trigger_meta( $trigger_meta );

							$trigger_meta['meta_key']   = 'WPJMJOBVIDEO';
							$trigger_meta['meta_value'] = maybe_serialize( $data['company']['company_video'] );
							Automator()->insert_trigger_meta( $trigger_meta );

							$trigger_meta['meta_key']   = 'WPJMJOBTWITTER';
							$trigger_meta['meta_value'] = maybe_serialize( $data['company']['company_twitter'] );
							Automator()->insert_trigger_meta( $trigger_meta );

							$trigger_meta['meta_key']   = 'WPJMJOBLOGOURL';
							$trigger_meta['meta_value'] = maybe_serialize( $data['company']['company_logo'] );
							Automator()->insert_trigger_meta( $trigger_meta );

							$trigger_meta['meta_key']   = 'WPJMJOBTYPE';
							$trigger_meta['meta_value'] = maybe_serialize( get_term_field( 'name', $data['job']['job_type'] ) );
							Automator()->insert_trigger_meta( $trigger_meta );

							$author          = get_post_field( 'post_author', $job_id );
							$author_username = get_the_author_meta( 'user_login', $author );
							$author_fname    = get_the_author_meta( 'first_name', $author );
							$author_lname    = get_the_author_meta( 'last_name', $author );
							$author_email    = get_the_author_meta( 'user_email', $author );

							$trigger_meta['meta_key']   = 'WPJMJOBOWNERNAME';
							$trigger_meta['meta_value'] = maybe_serialize( $author_username );
							Automator()->insert_trigger_meta( $trigger_meta );

							$trigger_meta['meta_key']   = 'WPJMJOBOWNEREMAIL';
							$trigger_meta['meta_value'] = maybe_serialize( $author_email );
							Automator()->insert_trigger_meta( $trigger_meta );

							$trigger_meta['meta_key']   = 'WPJMJOBOWNERFIRSTNAME';
							$trigger_meta['meta_value'] = maybe_serialize( $author_fname );
							Automator()->insert_trigger_meta( $trigger_meta );

							$trigger_meta['meta_key']   = 'WPJMJOBOWNERLASTNAME';
							$trigger_meta['meta_value'] = maybe_serialize( $author_lname );
							Automator()->insert_trigger_meta( $trigger_meta );

							Automator()->maybe_trigger_complete( $result['args'] );
							$categories = Automator()->helpers->recipe->wp_job_manager->pro->get_job_categories( $job_id );

							// Insert categories as meta.
							if ( ! empty( $categories ) ) {
								$trigger_meta['meta_key']   = 'WPJMJOBCATEGORIES';
								$trigger_meta['meta_value'] = implode( ', ', $categories );
								Automator()->insert_trigger_meta( $trigger_meta );
							}

							Automator()->maybe_trigger_complete( $result['args'] );
						}
					}
				}
			}
		}
	}

}
