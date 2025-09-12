<?php

namespace Uncanny_Automator_Pro;

/**
 * Class BDB_SENDEMAILTOALLMEMBERSOFGROUP
 *
 * @package Uncanny_Automator
 */
class BDB_SENDEMAILTOALLMEMBERSOFGROUP {

	use \Uncanny_Automator\Recipe\Actions;
	use Recipe\Action_Tokens;

	/**
	 * The helper class.
	 *
	 * @var Uncanny_Automator_Pro\Buddyboss_Pro_Helpers $helper
	 */
	protected $helper;

	/**
	 * Construct method
	 */
	public function __construct() {

		// Register the AS callback.
		$this->helper = new Buddyboss_Pro_Helpers( false );

		$this->setup_action();

	}

	/**
	 * Method setup_action.
	 *
	 * @return void
	 */
	protected function setup_action() {

		$this->set_integration( 'BDB' );

		$this->set_action_code( 'BDB_SENDEMAILTOALLMEMBERSOFGROUP' );

		$this->set_action_meta( 'BDB_SENDEMAILTOALLMEMBERSOFGROUP_META' );

		$this->set_requires_user( false );

		$this->set_is_pro( true );

		/* translators: Action - WordPress */
		$this->set_sentence( sprintf( esc_attr__( 'Send an email to all members of {{a group:%1$s}}', 'uncanny-automator-pro' ), $this->get_action_meta() ) );

		/* translators: Action - WordPress */
		$this->set_readable_sentence( esc_attr__( 'Send an email to all members of {{a group}}', 'uncanny-automator-pro' ) );

		$this->set_options_callback( array( $this, 'load_options' ) );

		// Enable background processing.
		if ( method_exists( $this, 'set_background_processing' ) ) {

			$this->set_background_processing( true );

		}

		$this->set_action_tokens(
			array(
				'GROUP_ID'    => array(
					'name' => __( 'Group ID', 'uncanny-automator-pro' ),
				),
				'GROUP_TITLE' => array(
					'name' => __( 'Group title', 'uncanny-automator-pro' ),
				),
			),
			$this->action_code
		);

		$this->register_action();

	}

	/**
	 * Method load_options
	 *
	 * @return array The options field.
	 */
	public function load_options() {

		$options_group = array(

			$this->get_action_meta() => array(
				Automator()->helpers->recipe->buddyboss->options->all_buddyboss_groups(
					esc_attr__( 'Group', 'uncanny-automator-pro' ),
					'BDBGROUPS',
					array(
						'status' => array(
							'public',
							'private',
							'hidden',
						),
					)
				),
				// Email From Field.
				Automator()->helpers->recipe->field->text(
					array(
						'option_code' => 'EMAILFROM',
						/* translators: Email field */
						'label'       => esc_attr__( 'From', 'uncanny-automator-pro' ),
						'input_type'  => 'email',
						'default'     => '{{admin_email}}',
					)
				),

				// Email From Field.
				Automator()->helpers->recipe->field->text(
					array(
						'option_code' => 'EMAILFROMNAME',
						/* translators: Email field */
						'label'       => esc_attr__( 'From name', 'uncanny-automator-pro' ),
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
						/* translators: Email CC field */
						'label'       => esc_attr__( 'CC', 'uncanny-automator-pro' ),
						'required'    => false,
						'input_type'  => 'email',
					)
				),

				// Email BCC field.
				Automator()->helpers->recipe->field->text(
					array(
						'option_code' => 'EMAILBCC',
						/* translators: Email BCC field */
						'label'       => esc_attr__( 'BCC', 'uncanny-automator-pro' ),
						'required'    => false,
						'input_type'  => 'email',
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
			),
		);

		return Automator()->utilities->keep_order_of_options(
			array(
				'options_group' => $options_group,
			)
		);

	}


	/**
	 * Method process_action.
	 *
	 * @return void.
	 */
	protected function process_action( $user_id, $action_data, $recipe_id, $args, $parsed ) {

		$group_id = $parsed['BDBGROUPS'] ?? '';
		$from     = $parsed['EMAILFROM'] ?? '';
		$fromname = $parsed['EMAILFROMNAME'] ?? '';
		$cc       = $parsed['EMAILCC'] ?? '';
		$bcc      = $parsed['EMAILBCC'] ?? '';
		$subject  = $parsed['EMAILSUBJECT'] ?? '';
		$body     = $parsed['EMAILBODY'] ?? '';
		$reply_to = $parsed['REPLYTO'] ?? '';

		$members = array();

		if ( function_exists( 'groups_get_group_members' ) ) {

			$members = groups_get_group_members(
				array(
					'group_id' => absint( $group_id ),
					'per_page' => 9999,
				)
			);

			if ( ! empty( $members ) ) {

				foreach ( $members['members'] as $member ) {

					$headers[] = 'From: ' . $fromname . ' <' . $from . '>';

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

					$headers = apply_filters( 'automator_pro_bdb_send_email_to_all_group_members', $headers, $this );

					// Send the email.
					wp_mail( $member->user_email, $subject, $this->replace_newlines_in_body( $body ), $headers );
				}
			}
		} else {

			$action_data['complete_with_errors'] = true;

			Automator()->complete->action( $user_id, $action_data, $recipe_id, esc_attr__( 'Buddyboss module is not active or not installed.', 'uncanny-automator-pro' ) );

			return;
		}

		$this->hydrate_tokens(
			array(
				'GROUP_ID'    => absint( $group_id ),
				'GROUP_TITLE' => groups_get_group( absint( $group_id ) )->name,
			)
		);

		Automator()->complete->action( $user_id, $action_data, $recipe_id );
	}

	/**
	 * Replaces newlines with BR.
	 *
	 * @param mixed $html_content
	 * @return string|string[]|null
	 */
	function replace_newlines_in_body( $html_content ) {

		return preg_replace_callback(
			'/<body[^>]*>((?!<\/?body>).)*<\/body>/ms',
			function( $match ) {
				return preg_replace( '/(?<!<body>)(\r\n|\r|\n)/', '<br/>', $match[0] );
			},
			$html_content
		);

	}

}
