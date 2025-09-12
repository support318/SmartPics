<?php

namespace Uncanny_Automator_Pro;

/**
 * Class WPJM_JOBFILLED
 *
 * @package Uncanny_Automator_Pro
 */
class WPJM_JOBFILLED {

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
		$this->trigger_code = 'WPJMJOBFILLED';
		$this->trigger_meta = 'WPJMJOBISFILLED';
		$this->define_trigger();
	}

	/**
	 * Define and register the trigger by pushing it into the Automator object
	 */
	public function define_trigger() {
		$trigger = array(
			'author'              => Automator()->get_author_name( $this->trigger_code ),
			'support_link'        => Automator()->get_author_support_link( $this->trigger_code, 'integration/wp-job-manager/' ),
			'is_pro'              => true,
			'integration'         => self::$integration,
			'code'                => $this->trigger_code,
			/* translators: Logged-in trigger - WP Job Manager */
			'sentence'            => sprintf( esc_attr__( '{{A job:%1$s}} is filled', 'uncanny-automator-pro' ), $this->trigger_meta ),
			/* translators: Logged-in trigger - WP Job Manager */
			'select_option_name'  => esc_attr__( '{{A job}} is filled', 'uncanny-automator-pro' ),
			'action'              => 'job_manager_my_job_do_action',
			'priority'            => 29,
			'accepted_args'       => 2,
			'validation_function' => array( $this, 'job_is_filled' ),
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
	 * @param $action
	 * @param $job_id
	 */
	public function job_is_filled( $action, $job_id ) {

		if ( 'mark_filled' !== (string) $action ) {
			return;
		}

		if ( empty( $job_id ) ) {
			return;
		}
		$job     = get_post( $job_id );
		$user_id = get_current_user_id();

		$recipes            = Automator()->get->recipes_from_trigger_code( $this->trigger_code );
		$required_job       = Automator()->get->meta_from_recipes( $recipes, $this->trigger_meta );
		$matched_recipe_ids = array();

		foreach ( $recipes as $recipe_id => $recipe ) {
			foreach ( $recipe['triggers'] as $trigger ) {
				$trigger_id = $trigger['ID'];
				if ( $required_job[ $recipe_id ][ $trigger_id ] === $job_id || intval( '-1' ) === intval( $required_job[ $recipe_id ][ $trigger_id ] ) ) {
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

							$trigger_meta['meta_key']   = 'WPJMJOBID';
							$trigger_meta['meta_value'] = maybe_serialize( $job_id );
							Automator()->insert_trigger_meta( $trigger_meta );

							$trigger_meta['meta_key']   = 'WPJMJOBTITLE';
							$trigger_meta['meta_value'] = maybe_serialize( wpjm_get_the_job_title( $job ) );
							Automator()->insert_trigger_meta( $trigger_meta );

							$trigger_meta['meta_key']   = 'WPJMSUBMITJOB';
							$trigger_meta['meta_value'] = maybe_serialize( $job_id );
							Automator()->insert_trigger_meta( $trigger_meta );

							$trigger_meta['meta_key']   = 'WPJMJOBLOCATION';
							$trigger_meta['meta_value'] = maybe_serialize( get_the_job_location( $job ) );
							Automator()->insert_trigger_meta( $trigger_meta );

							$trigger_meta['meta_key']   = 'WPJMJOBDESCRIPTION';
							$trigger_meta['meta_value'] = maybe_serialize( wpjm_get_the_job_description( $job ) );
							Automator()->insert_trigger_meta( $trigger_meta );

							// Get the job categories.
							$categories = Automator()->helpers->recipe->wp_job_manager->pro->get_job_categories( $job_id );
							// Insert categories as meta.
							if ( ! empty( $categories ) ) {
								$trigger_meta['meta_key']   = 'WPJMJOBCATEGORIES';
								$trigger_meta['meta_value'] = implode( ', ', $categories );
								Automator()->insert_trigger_meta( $trigger_meta );
							}

							$method = get_the_job_application_method( $job );
							if ( ! empty( $method ) ) {
								if ( 'email' === $method->type ) {
									$method = $method->email;
								} elseif ( 'url' === $method->type ) {
									$method = $method->url;
								}
							}

							$trigger_meta['meta_key']   = 'WPJMJOBAPPURL';
							$trigger_meta['meta_value'] = maybe_serialize( $method );
							Automator()->insert_trigger_meta( $trigger_meta );

							$trigger_meta['meta_key']   = 'WPJMJOBCOMPANYNAME';
							$trigger_meta['meta_value'] = maybe_serialize( get_the_company_name( $job ) );
							Automator()->insert_trigger_meta( $trigger_meta );

							$trigger_meta['meta_key']   = 'WPJMJOBWEBSITE';
							$trigger_meta['meta_value'] = maybe_serialize( get_the_company_website( $job ) );
							Automator()->insert_trigger_meta( $trigger_meta );

							$trigger_meta['meta_key']   = 'WPJMJOBTAGLINE';
							$trigger_meta['meta_value'] = maybe_serialize( get_the_company_tagline( $job ) );
							Automator()->insert_trigger_meta( $trigger_meta );

							$trigger_meta['meta_key']   = 'WPJMJOBVIDEO';
							$trigger_meta['meta_value'] = maybe_serialize( get_the_company_video( $job ) );
							Automator()->insert_trigger_meta( $trigger_meta );

							$trigger_meta['meta_key']   = 'WPJMJOBTWITTER';
							$trigger_meta['meta_value'] = maybe_serialize( get_the_company_twitter( $job ) );
							Automator()->insert_trigger_meta( $trigger_meta );

							$trigger_meta['meta_key']   = 'WPJMJOBLOGOURL';
							$trigger_meta['meta_value'] = maybe_serialize( get_the_company_logo( $job ) );
							Automator()->insert_trigger_meta( $trigger_meta );

							$job_types = wpjm_get_the_job_types( $job );
							if ( ! empty( $job_types ) ) {
								foreach ( $job_types as $type ) {
									$types[] = $type->name;
								}
							}

							$trigger_meta['meta_key']   = 'WPJMJOBTYPE';
							$trigger_meta['meta_value'] = maybe_serialize( implode( ',', $types ) );
							Automator()->insert_trigger_meta( $trigger_meta );

							$author          = get_post_field( 'post_author', $job );
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
						}
					}
				}
			}
		}
	}

}
