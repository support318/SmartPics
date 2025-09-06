<?php

namespace Uncanny_Automator_Pro;

use Uncanny_Automator\Recipe\Actions;
use Uncanny_Automator\Services\Email\Attachment\Handler;

/**
 * Class LD_EMAILGROUPLEADERS
 *
 * @package Uncanny_Automator_Pro
 */
class LD_EMAILGROUPLEADERS {

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

		$this->set_action_code( 'LD_EMAILGROUPLEADERS' );

		$this->set_action_meta( 'LD_EMAILGROUPLEADERS_META' );

		$this->set_requires_user( true );

		$this->set_is_pro( true );

		$this->set_sentence(
			sprintf(
			/* translators: Action - WordPress */
				esc_attr__( 'Send an {{email:%1$s}} to Group Leaders of {{a group:%2$s}}', 'uncanny-automator-pro' ),
				$this->get_action_meta(),
				$this->get_action_meta() . '_GROUP_ID'
			)
		);

		/* translators: Action - WordPress */
		$this->set_readable_sentence( esc_attr__( 'Send an {{email}} to Group Leaders of {{a group}}', 'uncanny-automator' ) );

		$this->set_options_callback( array( $this, 'load_options' ) );

		$this->register_action();

	}

	/**
	 * @return array
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
						'label'       => esc_attr__( 'CC', 'uncanny-automator-pro' ),
						'input_type'  => 'email',
						'required'    => false,
					)
				),

				// Email BCC field.
				Automator()->helpers->recipe->field->text(
					array(
						'option_code' => 'EMAILBCC',
						/* translators: Email field */
						'label'       => esc_attr__( 'BCC', 'uncanny-automator-pro' ),
						'input_type'  => 'email',
						'required'    => false,
					)
				),

				// Email Subject field.
				Automator()->helpers->recipe->field->text(
					array(
						'option_code' => 'EMAILSUBJECT',
						/* translators: Email field */
						'label'       => esc_attr__( 'Subject', 'uncanny-automator-pro' ),
						'required'    => true,
					)
				),

				// Email Content Field.
				Automator()->helpers->recipe->field->text(
					array(
						'option_code'               => 'EMAILBODY',
						/* translators: Email field */
						'label'                     => esc_attr__( 'Body', 'uncanny-automator-pro' ),
						'input_type'                => 'textarea',
						'supports_fullpage_editing' => true,
					)
				),

				// Exclude Leaders of Child Groups.
				Automator()->helpers->recipe->field->text_field(
					'EXCLUDE_CHILD_GROUP_LEADERS',
					esc_attr__( "Don't send emails to Group Leader(s) of child group(s)", 'uncanny-automator-pro' ),
					true,
					'checkbox',
					'',
					false,
					''
				),

				// File attachment URL.
				$attachment_field,

			),
		);

		return Automator()->utilities->keep_order_of_options(
			array(
				'options'       => array(
					array(
						'option_code'              => $this->get_action_meta() . '_GROUP_ID',
						/* translators: Email field */
						'label'                    => esc_attr__( 'Group', 'uncanny-automator-pro' ),
						'input_type'               => 'select',
						'supports_tokens'          => true,
						'supports_custom_value'    => true,
						'custom_value_description' => esc_attr__( 'Group ID', 'uncanny-automator-pro' ),
						'required'                 => true,
						'options'                  => $this->get_learndash_groups(),
					),
				),
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

		$body_text                   = isset( $parsed['EMAILBODY'] ) ? $parsed['EMAILBODY'] : '';
		$group_id                    = isset( $parsed[ $this->get_action_meta() . '_GROUP_ID' ] ) ? absint( $parsed[ $this->get_action_meta() . '_GROUP_ID' ] ) : 0;
		$exclude_child_group_leaders = isset( $parsed['EXCLUDE_CHILD_GROUP_LEADERS'] ) ? $parsed['EXCLUDE_CHILD_GROUP_LEADERS'] : false;

		if ( false !== strpos( $body_text, '{{reset_pass_link}}' ) ) {

			$reset_pass = ! is_null( $this->key ) ? $this->key : Automator()->parse->generate_reset_token( $user_id );

			$body = str_replace( '{{reset_pass_link}}', $reset_pass, $body_text );

		} else {

			$body = $body_text;

		}

		$group_admin_emails = $this->get_group_admin_emails( $group_id, filter_var( $exclude_child_group_leaders, FILTER_VALIDATE_BOOLEAN ) );
		if ( empty( $group_admin_emails ) ) {
			$error_message = __( 'The selected group does not have a Group Leader.', 'uncanny-automator-pro' );

			$action_data['complete_with_errors'] = true;

			Automator()->complete->action( $user_id, $action_data, $recipe_id, $error_message );

			return;
		}

		$failed_emails = array();

		foreach ( $group_admin_emails as $email ) {
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
	 * Get group admin emails.
	 *
	 * @param int $group_id
	 * @param bool $exclude_child_group_leaders
	 *
	 * @return array
	 */
	public function get_group_admin_emails( $group_id = 0, $exclude_child_group_leaders = false ) {

		if ( ! function_exists( 'learndash_get_groups_administrators' ) ) {

			return array();

		}

		$group_admins = array();

		$ld_group_admins = learndash_get_groups_administrators( $group_id, true );

		if ( ! empty( $ld_group_admins ) ) {

			foreach ( $ld_group_admins as $admin ) {

				$group_admins[ $admin->ID ] = $admin->user_email;

			}
		}

		// Include child group leaders.
		if ( empty( $exclude_child_group_leaders ) && ! empty( Learndash_Pro_Helpers::is_group_hierarchy_enabled() ) ) {
			$group_children_ids = Learndash_Pro_Helpers::get_group_children_in_an_action( $group_id );
			if ( ! empty( $group_children_ids ) ) {
				foreach ( $group_children_ids as $child_group_id ) {
					$child_group_admins = learndash_get_groups_administrators( $child_group_id, true );
					if ( ! empty( $child_group_admins ) ) {
						foreach ( $child_group_admins as $admin ) {
							$group_admins[ $admin->ID ] = $admin->user_email;
						}
					}
				}
			}
		}

		// Remove any duplicate emails.
		$group_admins = array_unique( $group_admins );

		return $group_admins;
	}

	/**
	 * @return array
	 */
	public function get_learndash_groups() {

		if ( ! function_exists( 'learndash_get_groups' ) ) {

			return array();

		}

		$groups = array();

		$learndash_groups = learndash_get_groups();

		foreach ( $learndash_groups as $group ) {

			$groups[ $group->ID ] = $group->post_title;

		}

		return $groups;

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
