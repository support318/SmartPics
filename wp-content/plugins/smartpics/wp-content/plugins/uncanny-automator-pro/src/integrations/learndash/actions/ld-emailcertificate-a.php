<?php

namespace Uncanny_Automator_Pro;

/**
 * Class LD_EMAILCERTIFICATE_A
 *
 * @package Uncanny_Automator_Pro
 */
class LD_EMAILCERTIFICATE_A {

	/**
	 * Integration code
	 *
	 * @var string
	 */
	public static $integration = 'LD';

	private $action_code;
	private $action_meta;

	/**
	 * Set up Automator action constructor.
	 */
	public function __construct() {
		add_filter(
			'automator_pro_maybe_update_action_fields',
			array( $this, 'automator_pro_maybe_update_action_fields_func' ),
			99,
			1
		);

		$this->action_code = 'EMAILACERTIFICATE';
		$this->action_meta = 'SENDCERTIFICATE';
		$this->define_action();
	}

	/**
	 * Define and register the action by pushing it into the Automator object
	 */
	public function define_action() {

		$action = array(
			'author'             => Automator()->get_author_name( $this->action_code ),
			'support_link'       => Automator()->get_author_support_link( $this->action_code, 'knowledge-base/generate-an-email-a-certificate-to-the-user/' ),
			'is_pro'             => true,
			'integration'        => self::$integration,
			'code'               => $this->action_code,
			/* translators: Action - LearnDash */
			'sentence'           => sprintf( __( 'Send a {{certificate:%1$s}}', 'uncanny-automator-pro' ), $this->action_meta ),
			/* translators: Action - LearnDash */
			'select_option_name' => __( 'Send a {{certificate}}', 'uncanny-automator-pro' ),
			'priority'           => 10,
			'accepted_args'      => 1,
			'execution_function' => array( $this, 'send_certificate' ),
			// very last call in WP, we need to make sure they viewed the page and didn't skip before is was fully viewable
			'options_callback'   => array( $this, 'load_options' ),
		);

		Automator()->register->action( $action );
	}

	/**
	 * Load options method.
	 *
	 * @return array[]
	 */
	public function load_options() {
		return Automator()->utilities->keep_order_of_options(
			array(
				'options_group' => array(
					$this->action_meta => array(
						Automator()->helpers->recipe->learndash->options->pro->all_ld_certificates( null, $this->action_meta ),
						Automator()->helpers->recipe->field->select(
							array(
								'option_code' => $this->action_meta . '_CERT_ORIENTATION',
								'label'       => esc_attr__(
									'Certificate orientation',
									'uncanny-automator-pro'
								),
								'options'     => array(
									'landscape' => __(
										'Landscape',
										'uncanny-automator-pro'
									),
									'portrait'  => __(
										'Portrait',
										'uncanny-automator-pro'
									),
								),
							)
						),
						Automator()->helpers->recipe->field->text_field( 'EMAILFROM', __( 'From', 'uncanny-automator' ), true, 'email', '{{admin_email}}', true, '' ),
						Automator()->helpers->recipe->field->text_field( 'EMAILFROMNAME', __( 'From name', 'uncanny-automator' ), true, 'text', '', true, '' ),
						Automator()->helpers->recipe->field->text_field( 'EMAILTO', __( 'To', 'uncanny-automator' ), true, 'email', '', true, esc_html__( 'Separate multiple email addresses with a comma', 'uncanny-automator-pro' ) ),
						Automator()->helpers->recipe->field->text_field( 'REPLYTO', __( 'Reply to', 'uncanny-automator' ), true, 'email', '', false ),
						Automator()->helpers->recipe->field->text_field( 'EMAILCC', __( 'CC', 'uncanny-automator' ), true, 'email', '', false ),
						Automator()->helpers->recipe->field->text_field( 'EMAILCCGROUPLEADERS', __( "CC the user's Group Leaders", 'uncanny-automator-pro' ), false, 'checkbox', 0, false ),
						Automator()->helpers->recipe->field->text_field( 'EMAILBCC', __( 'BCC', 'uncanny-automator' ), true, 'email', '', false ),
						Automator()->helpers->recipe->field->text_field( 'EMAILSUBJECT', __( 'Subject', 'uncanny-automator' ), true ),

						// Email Content Field.
						Automator()->helpers->recipe->field->text(
							array(
								'option_code' => 'EMAILBODY',
								/* translators: Email field */
								'label'       => esc_attr__( 'Email body', 'uncanny-automator' ),
								'input_type'  => 'textarea',
								'supports_fullpage_editing' => true,
							)
						),

						Automator()->helpers->recipe->field->text_field( 'CERTBODY', __( 'Certificate body', 'uncanny-automator-pro' ), true, 'textarea', '', false, esc_html__( 'Use field above to override content of selected certificate. Leave blank to use original content.' ) ),
						array(
							'option_code' => 'CERTBODYCUSTOMCSSCHECKBOX',
							'label'       => esc_attr__( 'Add custom CSS for certificate', 'uncanny-automator-pro' ),
							'input_type'  => 'checkbox',
							'is_toggle'   => true,
							'required'    => false,
						),

						array(
							'option_code'        => 'CERTBODYCUSTOMCSS',
							'label'              => esc_attr__( 'Certificate CSS', 'uncanny-automator-pro' ),
							'input_type'         => 'textarea',
							'required'           => false,
							'description'        => esc_attr__( 'Enter your CSS code into the field above. Please make sure that your CSS rules are correct and targeted appropriately.', 'uncanny-automator' ),
							'dynamic_visibility' => array(
								// 'default_state' specifies the initial visibility state of the element
								'default_state'    => 'hidden', // Possible values: 'hidden', 'visible'

								// 'visibility_rules' is an array of rules that define conditions for changing the visibility state
								'visibility_rules' => array(
									// Each array within 'visibility_rules' represents a single rule
									array(
										// 'operator' specifies how to evaluate the conditions within the rule.
										// 'AND' means all conditions must be true, 'OR' means any condition can be true
										'operator'        => 'AND', // Possible values: 'AND', 'OR'

										// 'rule_conditions' is an array of condition objects
										'rule_conditions' => array(
											// Each condition object within 'rule_conditions' specifies a single condition to evaluate
											array(
												'option_code' => 'CERTBODYCUSTOMCSSCHECKBOX', // The unique identifier for the option/element being evaluated
												'compare' => '==', // The operator used for comparison (e.g., '==', '!=', etc.)
												'value'   => true,  // The value to compare against
											),
											// Additional conditions can be added here if needed
										),

										// 'resulting_visibility' specifies what the visibility should be if the rule conditions are met
										'resulting_visibility' => 'show', // Possible values: 'show', 'hide'
									),
									// Additional rules can be added here if needed
								),
							),

						),
					),
				),
			)
		);
	}

	/**
	 * Validation function when the trigger action is hit
	 *
	 * @param $user_id
	 * @param $action_data
	 * @param $recipe_id
	 */
	public function send_certificate( $user_id, $action_data, $recipe_id, $args ) {

		$certificate_id   = Automator()->parse->text( $action_data['meta'][ $this->action_meta ], $recipe_id, $user_id, $args );
		$cert_orientation = Automator()->parse->text( $action_data['meta'][ $this->action_meta . '_CERT_ORIENTATION' ], $recipe_id, $user_id, $args );
		$to               = Automator()->parse->text( $action_data['meta']['EMAILTO'], $recipe_id, $user_id, $args );
		$reply_to         = Automator()->parse->text( $action_data['meta']['REPLYTO'], $recipe_id, $user_id, $args );
		$from             = Automator()->parse->text( $action_data['meta']['EMAILFROM'], $recipe_id, $user_id, $args );
		$from_name        = Automator()->parse->text( $action_data['meta']['EMAILFROMNAME'], $recipe_id, $user_id, $args );
		$cc               = Automator()->parse->text( $action_data['meta']['EMAILCC'], $recipe_id, $user_id, $args );
		$cc_group_leaders = Automator()->parse->text( $action_data['meta']['EMAILCCGROUPLEADERS'], $recipe_id, $user_id, $args );
		$cc_group_leaders = (string) $cc_group_leaders === 'true';
		$bcc              = Automator()->parse->text( $action_data['meta']['EMAILBCC'], $recipe_id, $user_id, $args );
		$subject          = Automator()->parse->text( $action_data['meta']['EMAILSUBJECT'], $recipe_id, $user_id, $args );
		$subject          = do_shortcode( $subject );
		$email_body       = Automator()->parse->text( $action_data['meta']['EMAILBODY'], $recipe_id, $user_id, $args );
		$email_body       = do_shortcode( $email_body );
		$cert_body        = Automator()->parse->text( $action_data['meta']['CERTBODY'], $recipe_id, $user_id, $args );

		// Maybe add custom CSS
		$custom_css = '';
		if ( isset( $action_data['meta']['CERTBODYCUSTOMCSSCHECKBOX'] ) && 'true' === $action_data['meta']['CERTBODYCUSTOMCSSCHECKBOX'] ) {
			$custom_css = Automator()->parse->text( $action_data['meta']['CERTBODYCUSTOMCSS'], $recipe_id, $user_id, $args );
		}

		if ( empty( wp_strip_all_tags( $cert_body ) ) ) {
			$cert_post = get_post( $certificate_id );
			if ( $cert_post instanceof \WP_Post ) {
				$cert_body = $cert_post->post_content;
			}
		}
		$pattern = get_shortcode_regex();
		preg_match_all( '/' . $pattern . '/s', $cert_body, $matches );
		if (
			preg_match_all( '/' . $pattern . '/s', $cert_body, $matches ) &&
			array_key_exists( 2, $matches ) &&
			(
				in_array( 'quizinfo', $matches[2] ) || //phpcs:ignore WordPress.PHP.StrictInArray.MissingTrueStrict
				in_array( 'courseinfo', $matches[2] ) //phpcs:ignore WordPress.PHP.StrictInArray.MissingTrueStrict
			)
		) {
			if ( isset( $matches[0] ) ) {
				foreach ( $matches[0] as $__mataches ) {
					$cert_body = str_replace( $__mataches, 'N/A', $cert_body );
				}
			}
		}

		$error_message = '';
		$headers[]     = 'From: ' . $from_name . ' <' . $from . '>';

		// CC Group Leaders.
		if ( $cc_group_leaders ) {
			$group_leaders = $this->get_user_group_leaders_emails( $user_id );
			if ( ! empty( $group_leaders ) ) {
				$group_leaders = implode( ',', $group_leaders );
				$cc            = ! empty( $cc ) ? $cc . ',' . $group_leaders : $group_leaders;
				$cc            = $this->remove_duplicate_cc_emails( $to, $cc );
				// Log CCed Group Leaders.
				$properties = array(
					array(
						'type'       => 'string',
						'label'      => 'CCed Group Leaders',
						'value'      => $group_leaders,
						'attributes' => array(),
					),
				);
				Automator()->helpers->recipe->set_log_properties( $properties );
			}
		}

		if ( ! empty( $cc ) ) {
			$headers[] = 'Cc: ' . $cc;
		}

		if ( ! empty( $bcc ) ) {
			$headers[] = 'Bcc: ' . $bcc;
		}

		if ( ! empty( $reply_to ) ) {
			$headers[] = "Reply-To: $reply_to";
		}

		$headers[] = 'Content-Type: text/html; charset=UTF-8';

		/* Save Path on Server under Upload & allow overwrite */
		$save_path = apply_filters( 'automator_certificate_save_path', WP_CONTENT_DIR . '/uploads/automator-certificates/' );

		if ( ! is_dir( $save_path ) ) {
			mkdir( $save_path, 0755 );
		}

		$user      = get_user_by( 'ID', $user_id );
		$file_name = 'certificate-' . $certificate_id . '-' . time();
		$file_name = apply_filters( 'automator_pro_learndash_certificate_filename', $file_name, $user, $certificate_id, $action_data, $recipe_id );

		$certificate_args = array(
			'certificate_post' => $certificate_id,
			'save_path'        => $save_path, // Add save path.
			'file_name'        => $file_name, // Add filename.
			'user'             => $user,
			'orientation'      => ( isset( $cert_orientation ) ) ? $cert_orientation : 'landscape',
		);

		$attachments = Automator()->helpers->recipe->learndash->pro->generate_pdf( $certificate_args, $cert_body, 'automator', $custom_css );

		// Something went wrong with return format, complete with errors.
		if ( ! is_array( $attachments ) ) {

			$error_message = esc_html__( 'Attachments return an invalid array format.', 'uncanny-automator-pro' );

			$action_data['do-nothing']           = true;
			$action_data['complete_with_errors'] = true;

			Automator()->complete_action( $user_id, $action_data, $recipe_id, $error_message );

			return;
		}

		// Something went wrong with pdf, complete with errors.
		if ( false === $attachments['return'] ) {
			$error_message                       = $attachments['message'];
			$action_data['do-nothing']           = true;
			$action_data['complete_with_errors'] = true;

			Automator()->complete_action( $user_id, $action_data, $recipe_id, $error_message );

			return;
		}

		$attachments = $attachments['message'];

		$mailed = wp_mail( $to, $subject, $email_body, $headers, array( $attachments ) );

		if ( ! $mailed ) {

			$error_message = Automator()->error_message->get( 'email-failed' );

			$action_data['do-nothing']           = true;
			$action_data['complete_with_errors'] = true;

			Automator()->complete_action( $user_id, $action_data, $recipe_id, $error_message );

			if ( $attachments ) {
				unlink( $attachments );
			}

			return;
		}

		if ( $attachments ) {
			unlink( $attachments );
		}

		Automator()->complete_action( $user_id, $action_data, $recipe_id, $error_message );
	}

	/**
	 * @param $actions
	 *
	 * @return mixed
	 */
	public function automator_pro_maybe_update_action_fields_func( $actions ) {
		$actions[ $this->action_code ] = array(
			'EMAILFROM',
			'EMAILFROMNAME',
			'EMAILTO',
			'REPLYTO',
			'EMAILCC',
			'EMAILBCC',
			'EMAILSUBJECT',
			'EMAILBODY',
		);

		return $actions;
	}

	/**
	 * Return user's group leaders' emails.
	 *
	 * @param $user_id
	 *
	 * @return array
	 */
	private function get_user_group_leaders_emails( $user_id ) {

		$group_leaders = array();
		$ld_group      = learndash_get_users_group_ids( $user_id, true );
		if ( empty( $ld_group ) ) {
			return $group_leaders;
		}

		foreach ( $ld_group as $group_id ) {
			$group_leaders = array_merge( $group_leaders, learndash_get_groups_administrators( $group_id, true ) );
		}

		if ( empty( $group_leaders ) ) {
			return $group_leaders;
		}

		$group_leaders = array_map(
			function ( $group_leader ) {
				return $group_leader->user_email;
			},
			$group_leaders
		);

		if ( ! empty( $group_leaders ) ) {
			$sanitized_emails = array();
			$group_leaders    = array_unique( $group_leaders );
			foreach ( $group_leaders as $key => $email ) {
				$email = sanitize_email( $email );
				if ( ! empty( $email ) ) {
					$sanitized_emails[ $key ] = $email;
				}
			}
			if ( ! empty( $sanitized_emails ) ) {
				$group_leaders = array_values( $sanitized_emails );
			}
		}

		return array_unique( $group_leaders );
	}

	/**
	 * Remove duplicate CC emails.
	 *
	 * @param $to
	 * @param $cc
	 *
	 * @return string
	 */
	private function remove_duplicate_cc_emails( $to, $cc ) {
		$to_emails = explode( ',', $to );
		$cc_emails = explode( ',', $cc );
		$cc_emails = array_diff( $cc_emails, $to_emails );
		$cc_emails = array_unique( $cc_emails );
		$cc_emails = implode( ',', $cc_emails );

		return $cc_emails;
	}

}
