<?php

/**
 * Class WP_SETPOSTCONTENT
 *
 * @package Uncanny_Automator_Pro
 */

namespace Uncanny_Automator_Pro;

class WP_SETPOSTCONTENT {

	use Recipe\Action_Tokens;
	/**
	 * Integration code
	 *
	 * @var string
	 */
	public static $integration = 'WP';

	/**
	 * Action code
	 *
	 * @var string
	 */
	private $action_code;

	/**
	 * Action meta
	 *
	 * @var string
	 */
	private $action_meta;

	/**
	 * Universally accessible action code.
	 *
	 * @var string
	 */
	const ACTION_CODE = 'SETPOSTCONTENT';

	/**
	 * Set up Automator action constructor.
	 */
	public function __construct() {

		$this->action_code = self::ACTION_CODE;

		$this->action_meta = 'WPPOST';

		if ( Automator()->helpers->recipe->is_edit_page() ) {

			add_action(
				'wp_loaded',
				function () {
					$this->define_action();
				},
				99
			);

			return;
		}

		$this->define_action();

	}

	/**
	 * Define and register the action by pushing it into the Automator object
	 */
	public function define_action() {

		// Disables the token's parser shortcode processing using the filter below. Shortcode should render 'as-is'.
		add_filter( 'automator_skip_do_shortcode_parse_in_fields', array( $this, 'disable_input_parser_do_shortcode' ) );

		$action = array(
			'author'             => Automator()->get_author_name( $this->action_code ),
			'support_link'       => Automator()->get_author_support_link( $this->action_code, 'integration/wordpress-core/' ),
			'is_pro'             => true,
			'integration'        => self::$integration,
			'code'               => $this->action_code,
			'requires_user'      => false,
			/* translators: Action - WordPress Core */
			'sentence'           => sprintf( __( 'Update the content of {{a post:%1$s}}', 'uncanny-automator-pro' ), $this->action_meta ),
			/* translators: Action - WordPress Core */
			'select_option_name' => __( 'Update the content of {{a post}}', 'uncanny-automator-pro' ),
			'priority'           => 11,
			'accepted_args'      => 4,
			'execution_function' => array( $this, 'set_post_content' ),
			'options_callback'   => array( $this, 'load_options' ),
		);

		$this->set_action_tokens(
			array(
				'POST_UPDATED_CONTENT' => array(
					'name' => __( 'Updated content', 'uncanny-automator-pro' ),
					'type' => 'url',
				),
			),
			$this->action_code
		);

		Automator()->register->action( $action );

	}

	/**
	 * Callback method to automator_skip_do_shortcode_parse_in_fields filter.
	 *
	 * Adds this action's code to the list of meta's to skip.
	 *
	 * @param string[] $action_codes
	 *
	 * @return string[]
	 */
	public function disable_input_parser_do_shortcode( $action_codes ) {

		$action_codes[] = self::ACTION_CODE;

		return $action_codes;

	}

	/**
	 * load_options
	 *
	 * @return void
	 */
	public function load_options() {

		$options = Automator()->utilities->keep_order_of_options(
			array(
				'options_group' => array(
					$this->action_meta => array(
						Automator()->helpers->recipe->wp->options->pro->all_wp_post_types(
							__( 'Post type', 'uncanny-automator-pro' ),
							'WPPOSTTYPE',
							array(
								'token'        => false,
								'is_ajax'      => true,
								'target_field' => $this->action_meta,
								'is_any'       => false,
								'endpoint'     => 'select_all_post_of_selected_post_type_no_all',
							)
						),
						Automator()->helpers->recipe->field->select_field( $this->action_meta, __( 'Post', 'uncanny-automator-pro' ) ),
						Automator()->helpers->recipe->field->text_field( 'WPPOSTCONTENT', __( 'Content', 'uncanny-automator' ), true, 'textarea', '', true ),
					),

				),
			)
		);

		return $options;
	}

	/**
	 * Set Post Content
	 *
	 * @param int $user_id
	 * @param array $action_data
	 * @param int $recipe_id
	 * @param array $args
	 */
	public function set_post_content( $user_id, $action_data, $recipe_id, $args ) {

		$data = array(
			'ID'           => $action_data['meta'][ $this->action_meta ],
			//'post_type'    => sanitize_text_field( $action_data['meta']['WPPOSTTYPE'] ),
			'post_content' => Automator()->parse->text( $action_data['meta']['WPPOSTCONTENT'], $recipe_id, $user_id, $args ),
		);

		$post_id = wp_update_post( $data, true );

		if ( is_wp_error( $post_id ) ) {

			$errors = $post_id->get_error_messages();

			$message = '';

			foreach ( $errors as $error ) {
				$message .= $error . "\n";
			}

			$action_data['do-nothing']           = true;
			$action_data['complete_with_errors'] = true;

			Automator()->complete_action( $user_id, $action_data, $recipe_id, $message );

			return;
		}

		$this->hydrate_tokens(
			array(
				'POST_UPDATED_CONTENT' => apply_filters( 'the_content', get_post_field( 'post_content', $post_id ) ),
			)
		);

		Automator()->complete_action( $user_id, $action_data, $recipe_id );

	}

}
