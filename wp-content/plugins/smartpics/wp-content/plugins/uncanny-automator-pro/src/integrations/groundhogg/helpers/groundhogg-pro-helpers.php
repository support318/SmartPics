<?php

namespace Uncanny_Automator_Pro;

use Groundhogg\Contact;
use Groundhogg\DB\Contacts;

/**
 * Class Groundhogg_Pro_Helpers
 *
 * @package Uncanny_Automator_Pro
 */
class Groundhogg_Pro_Helpers {

	/**
	 * @param $args
	 *
	 * @return array|mixed|void
	 */
	public function get_all_gh_contacts( $args = array() ) {

		$defaults = array(
			'option_code'           => 'GH_CONTACT',
			'label'                 => esc_attr__( 'Contact', 'uncanny-automator' ),
			'is_any'                => false,
			'is_all'                => false,
			'supports_custom_value' => false,
			'relevant_tokens'       => array(),
		);

		$args         = wp_parse_args( $args, $defaults );
		$contacts     = new Contacts();
		$all_contacts = array();

		foreach ( $contacts->get_contacts() as $contact ) {
			$all_contacts[ $contact->ID ] = $contact->email;
		}

		if ( true === $args['is_any'] ) {
			$all_contacts = array( '-1' => _x( 'Any contact', 'Groundhogg', 'uncanny-automator' ) ) + $all_contacts;
		}

		if ( true === $args['is_all'] ) {
			$all_contacts = array( '-1' => _x( 'All contacts', 'Groundhogg', 'uncanny-automator' ) ) + $all_contacts;
		}

		$option = array(
			'option_code'           => $args['option_code'],
			'label'                 => $args['label'],
			'input_type'            => 'select',
			'required'              => true,
			'options_show_id'       => false,
			'relevant_tokens'       => $args['relevant_tokens'],
			'options'               => $all_contacts,
			'supports_custom_value' => $args['supports_custom_value'],
		);

		return apply_filters( 'uap_option_get_all_gh_contacts', $option );

	}

	/**
	 * Get all tags.
	 *
	 * @return array|mixed|void
	 */
	public static function get_tag_options() {

		$tags    = array();
		$options = array();

		try {
			$tags = \Groundhogg\get_db( 'tags' )->query( array() );
		} catch ( \Error $e ) {
			automator_log( $e->getMessage(), $tags, AUTOMATOR_DEBUG_MODE, 'groundhogg' );
		} catch ( \Exception $e ) {
			automator_log( $e->getMessage(), $tags, AUTOMATOR_DEBUG_MODE, 'groundhogg' );
		}

		if ( empty( $tags ) ) {
			return $options;
		}

		foreach ( $tags as $tag ) {
			$options[] = array(
				'value' => $tag->tag_id,
				'text'  => $tag->tag_name,
			);
		}

		return $options;
	}

}
