<?php
namespace Uncanny_Automator_Pro;

use FluentCrm\App\Models\Tag as FluentCrm_Tag;

/**
 * Class FCRM_USER_TAG_FOUND
 *
 * @package Uncanny_Automator_Pro
 */
class FCRM_USER_TAG_FOUND extends Action_Condition {

	/**
	 * Define_condition
	 *
	 * @return void
	 */
	public function define_condition() {

		$this->integration = 'FCRM';

		/*translators: Token */
		$this->name = __( 'A user has {{a tag}}', 'uncanny-automator-pro' );

		$this->code = 'USER_TAG_FOUND';

		// translators: A token matches a value
		$this->dynamic_name = sprintf( esc_html__( 'A user has {{a tag:%1$s}}', 'uncanny-automator-pro' ), 'TAG' );

		$this->is_pro = true;

		$this->requires_user = true;

	}

	/**
	 * Fields
	 *
	 * @return array
	 */
	public function fields() {

		$fcrm_tags_fields_args = array(
			'option_code'           => 'TAG',
			'label'                 => esc_html__( 'Tag', 'uncanny-automator-pro' ),
			'required'              => true,
			'options'               => $this->fcrm_tags_options(),
			'supports_custom_value' => true,
		);

		return array(
			// Course field
			$this->field->select_field_args( $fcrm_tags_fields_args ),
		);
	}

	/**
	 * Load options
	 *
	 * @return array[]
	 */
	public function fcrm_tags_options() {

		$options = array();

		if ( ! class_exists( 'FluentCrm\App\Models\Tag' ) ) {

			return array();

		}

		$tags = FluentCrm_Tag::orderBy( 'title', 'DESC' )->get();

		if ( ! empty( $tags ) ) {

			foreach ( $tags as $tag ) {

				$options[] = array(
					'value' => $tag->id,
					'text'  => $tag->title,
				);

			}
		}

		return $options;

	}

	/**
	 * Method evaluate_condition.
	 *
	 * Sets the condition to fail with error message if the user has already a tag.
	 *
	 * @return void
	 */
	public function evaluate_condition() {

		$tag = $this->get_parsed_option( 'TAG' );

		if ( ! $this->fcrm_user_has_tags( $this->user_id, array( $tag ) ) ) {

			$error_message = sprintf( 'The user with id: [%d] is not tagged with: [%s]', $this->user_id, $this->get_option( 'TAG_readable' ) );

			$this->condition_failed( $error_message ); // Dont translate.

		}

	}

	/**
	 * Method fcrm_get_user
	 *
	 * Get the Fluent CRM contact.
	 *
	 * @param int $user_id The wp user id of the Fluent CRM contact.
	 *
	 * @return \FluentCrmApi The Fluent CRM contact.
	 */
	public function fcrm_get_user( $user_id = 0 ) {

		if ( ! function_exists( 'FluentCrmApi' ) ) {

			return false;

		}

		$fcrm_contact = FluentCrmApi( 'contacts' );

		$current_contact = $fcrm_contact->getInstance()->where( 'user_id', $user_id )->first();

		return $current_contact;

	}

	/**
	 * Method fcrm_user_has_tags
	 *
	 * Check if the user has any tag.
	 *
	 * @param int $user_id The wp user id of the Fluent CRM contact.
	 * @param array $tags The tags ids (e.g. [1,2,5])
	 *
	 * @return boolean True if the user has tag. Otherwise false.
	 */
	public function fcrm_user_has_tags( $user_id = 0, $tags = array() ) {

		$contact = $this->fcrm_get_user( $user_id );

		return ( $contact && $contact->hasAnyTagId( $tags ) );

	}

}
