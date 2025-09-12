<?php

namespace Uncanny_Automator_Pro;

use Uncanny_Automator\Learndash_Helpers;
use Uncanny_Automator\Recipe\Actions;
use Uncanny_Automator\Services\Email\Attachment\Handler;

/**
 * Class LD_EMAILGROUPSLEADERS_A
 *
 * @package Uncanny_Automator_Pro
 */
class LD_EMAILGROUPSLEADERS_A {

	use Actions;

	/**
	 * Reset key holder
	 *
	 * @var null
	 */
	private $key;

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

		$this->key = null;
		$this->setup_action();
	}

	/**
	 * Setting up the action
	 *
	 * @return void
	 */
	protected function setup_action() {

		$this->set_integration( 'LD' );
		$this->set_action_code( 'EMAILGROUPSLEADERS' );
		$this->set_action_meta( 'EMAILTOLEADERS' );
		$this->set_requires_user( true );
		$this->set_is_pro( true );

		/* translators: Action - WordPress */
		$this->set_sentence( sprintf( esc_attr__( "Send an {{email:%1\$s}} to the user's group leader(s)", 'uncanny-automator' ), $this->get_action_meta() ) );

		/* translators: Action - WordPress */
		$this->set_readable_sentence( esc_attr__( "Send an {{email}} to the user's group leader(s)", 'uncanny-automator' ) );

		$this->set_options_callback( array( $this, 'load_options' ) );

		$this->register_action();

	}

	/**
	 * @return array[]
	 */
	public function load_options() {

		// File attachment description.
		$attachment_description = Learndash_Pro_Helpers::get_file_attachment_field_description();

		$attachment_field = array(
			'option_code'              => 'FILE_ATTACHMENT_URL', // Unique identifier for the file field option.
			'input_type'               => 'file', // Specifies that this field is for file input.
			'label'                    => __( 'File attachment', 'uncanny-automator' ), // Label for the file field, displayed in the UI.
			'description'              => $attachment_description, // A brief description of the file field.
			'required'                 => false, // Indicates that this file field is mandatory.
			'supports_multiple_values' => false, // Allows multiple files to be uploaded.
		);

		$options_group = array(
			$this->get_action_meta() => array(
				// Email From Field.
				Automator()->helpers->recipe->field->text(
					array(
						'option_code' => 'EMAILFROM',
						/* translators: Email field */
						'label'       => esc_attr__( 'From', 'uncanny-automator' ),
						'input_type'  => 'email',
						'default'     => '{{admin_email}}',
					)
				),

				// Email From Field.
				Automator()->helpers->recipe->field->text(
					array(
						'option_code' => 'EMAILFROMNAME',
						/* translators: Email field */
						'label'       => esc_attr__( 'From name', 'uncanny-automator' ),
						'input_type'  => 'text',
						'default'     => '{{site_name}}',
					)
				),

				// Reply To Field.
				Automator()->helpers->recipe->field->text(
					array(
						'option_code' => 'REPLYTO',
						/* translators: Email field */
						'label'       => esc_attr__( 'Reply to', 'uncanny-automator' ),
						'input_type'  => 'email',
						'required'    => false,
					)
				),

				// Email CC field.
				Automator()->helpers->recipe->field->text(
					array(
						'option_code' => 'EMAILCC',
						/* translators: Email field */
						'label'       => esc_attr__( 'CC', 'uncanny-automator' ),
						'input_type'  => 'email',
						'required'    => false,
					)
				),

				// Email BCC field.
				Automator()->helpers->recipe->field->text(
					array(
						'option_code' => 'EMAILBCC',
						/* translators: Email field */
						'label'       => esc_attr__( 'BCC', 'uncanny-automator' ),
						'input_type'  => 'email',
						'required'    => false,
					)
				),

				// Email Subject field.
				Automator()->helpers->recipe->field->text(
					array(
						'option_code' => 'EMAILSUBJECT',
						/* translators: Email field */
						'label'       => esc_attr__( 'Subject', 'uncanny-automator' ),
						'required'    => true,
					)
				),

				// Email Content Field.
				Automator()->helpers->recipe->field->text(
					array(
						'option_code'               => 'EMAILBODY',
						/* translators: Email field */
						'label'                     => esc_attr__( 'Body', 'uncanny-automator' ),
						'input_type'                => 'textarea',
						'supports_fullpage_editing' => true,
					)
				),

				$attachment_field,

			),
		);

		return Automator()->utilities->keep_order_of_options(
			array(
				'options_group' => $options_group,
			)
		);
	}

	/**
	 * Handling action function
	 *
	 * @param $user_id
	 * @param $action_data
	 * @param $recipe_id
	 * @param $args
	 * @param $parsed
	 *
	 * @return void.
	 */
	protected function process_action( $user_id, $action_data, $recipe_id, $args, $parsed ) {

		$attachment_url = $parsed['FILE_ATTACHMENT_URL'] ?? '';

		if ( class_exists( '\Uncanny_Automator\Services\Email\Attachment\Handler' ) ) {
			$attachment_url = Handler::get_url_from_field_value( $attachment_url );
		}

		$body_text = isset( $parsed['EMAILBODY'] ) ? $parsed['EMAILBODY'] : '';

		if ( false !== strpos( $body_text, '{{reset_pass_link}}' ) ) {
			$reset_pass = ! is_null( $this->key ) ? $this->key : Automator()->parse->generate_reset_token( $user_id );
			$body       = str_replace( '{{reset_pass_link}}', $reset_pass, $body_text );
		} else {
			$body = $body_text;
		}
		$ld_group = null;
		// Try to find a single Group ID
		if ( isset( $args['post_id'] ) ) {
			// Post ID found
			$ld_group = absint( $args['post_id'] );
			if ( 'groups' !== get_post_type( $ld_group ) ) {
				//  Nop, not a group post ID
				$ld_group = null;
			}
		}
		// Fallback to grab ALL group IDs
		if ( null === $ld_group ) {
			$ld_group = learndash_get_users_group_ids( $user_id, true );
		}
		// Still empty, no groups found for the user
		if ( empty( $ld_group ) ) {
			$error_message                       = esc_attr__( 'User is not a member of any group.', 'uncanny-automator-pro' );
			$action_data['complete_with_errors'] = true;
			Automator()->complete->action( $user_id, $action_data, $recipe_id, $error_message );

			return;
		}
		$group_leaders = array();
		// If fallback is used to grab all group IDs
		if ( is_array( $ld_group ) ) {
			foreach ( $ld_group as $group_id ) {
				$group_leaders = array_merge( $group_leaders, learndash_get_groups_administrators( $group_id, true ) );
			}
		} else {
			// Single group ID found
			$group_leaders = learndash_get_groups_administrators( $ld_group, true );
		}
		if ( empty( $group_leaders ) ) {
			/* translators: Action - LearnDash Group ID */
			$error_message                       = sprintf( esc_attr__( 'No Group Leader associated with the selected group. ID: %d', 'uncanny-automator-pro' ), $ld_group );
			$action_data['complete_with_errors'] = true;
			Automator()->complete->action( $user_id, $action_data, $recipe_id, $error_message );

			return;
		}

		$failed_emails = array();
		$to_emails     = array();

		foreach ( $group_leaders as $leader ) {
			$to_emails[ $leader->ID ] = $leader->user_email;
		}
		$to_emails = array_unique( $to_emails );

		foreach ( $to_emails as $email ) {
			$data = array(
				'to'         => $email,
				'reply_to'   => isset( $parsed['REPLYTO'] ) ? $parsed['REPLYTO'] : '',
				'from'       => isset( $parsed['EMAILFROM'] ) ? $parsed['EMAILFROM'] : '',
				'from_name'  => isset( $parsed['EMAILFROMNAME'] ) ? $parsed['EMAILFROMNAME'] : '',
				'cc'         => isset( $parsed['EMAILCC'] ) ? $parsed['EMAILCC'] : '',
				'bcc'        => isset( $parsed['EMAILBCC'] ) ? $parsed['EMAILBCC'] : '',
				'subject'    => isset( $parsed['EMAILSUBJECT'] ) ? $parsed['EMAILSUBJECT'] : '',
				'body'       => $body,
				'content'    => $this->get_content_type(),
				'charset'    => $this->get_charset(),
				'attachment' => $attachment_url,
			);

			// Clear any cc and bcc fields after the first email.
			$parsed['EMAILCC']  = '';
			$parsed['EMAILBCC'] = '';

			$this->set_mail_values( $data, $user_id, $recipe_id, $args );

			$mailed = $this->send_email();
			if ( false === $mailed && ! empty( $this->get_error_message() ) ) {
				$failed_emails[ $email ] = $this->get_error_message();
				$this->clear_error_message();
			}
		}

		// If any email failed to send record and complete the action with errors.
		if ( ! empty( $failed_emails ) ) {

			// Add message and failed emails.
			$error_message = esc_attr__( 'Failed to send email to the following group leaders:', 'uncanny-automator-pro' ) . ' ' . implode( ', ', array_keys( $failed_emails ) );

			// Check for unique error messages.
			$errors        = array_unique( array_values( $failed_emails ) );
			$errors        = implode( ', ', $errors );
			$error_message .= esc_attr__( 'Errors :', 'uncanny-automator-pro' ) . ' ' . $errors;

			// Complete the action with errors.
			$action_data['complete_with_errors'] = true;
			Automator()->complete->action( $user_id, $action_data, $recipe_id, $error_message );

			return;
		}

		Automator()->complete->action( $user_id, $action_data, $recipe_id );

	}
	/**
	 * @param $actions
	 *
	 * @return mixed
	 */
	public function automator_pro_maybe_update_action_fields_func( $actions ) {
		$actions[ $this->get_action_code() ] = array(
			'EMAILFROM',
			'EMAILFROMNAME',
			//'EMAILTO',
			'REPLYTO',
			'EMAILCC',
			'EMAILBCC',
			'EMAILSUBJECT',
			'EMAILBODY',
		);
		return $actions;
	}
}
