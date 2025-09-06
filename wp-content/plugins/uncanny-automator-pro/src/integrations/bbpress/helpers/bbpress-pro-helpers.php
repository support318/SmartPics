<?php


namespace Uncanny_Automator_Pro;

use Uncanny_Automator\Bbpress_Helpers;

/**
 * Class Buddypress_Pro_Helpers
 *
 * @package Uncanny_Automator_Pro
 */
class Bbpress_Pro_Helpers extends Bbpress_Helpers {

	/**
	 * Bbpress_Pro_Helpers constructor.
	 */
	public function __construct() {
		// Selectively load options

		add_action(
			'wp_ajax_select_topic_from_forum_TOPICREPLY',
			array(
				$this,
				'select_topic_fields_func',
			)
		);
		add_action(
			'wp_ajax_select_topic_from_forum_BBTOPICREPLY_NOANY',
			array(
				$this,
				'select_topic_fields_func_noany',
			)
		);
	}

	/**
	 * @param Bbpress_Pro_Helpers $pro
	 */
	public function setPro( Bbpress_Pro_Helpers $pro ) {
		parent::setPro( $pro );
	}

	/**
	 * Return all the specific topics of a forum in ajax call
	 */
	public function select_topic_fields_func() {

		Automator()->utilities->ajax_auth_check( $_POST );

		$fields = array();
		if ( isset( $_POST ) ) {
			$fields[] = array(
				'value' => - 1,
				'text'  => __( 'Any topic', 'uncanny-automator' ),
			);
			$forum_id = (int) automator_filter_input( 'value', INPUT_POST );

			if ( $forum_id > 0 ) {
				$args = array(
					'post_type'      => bbp_get_topic_post_type(),
					'post_parent'    => $forum_id,
					'post_status'    => array_keys( get_post_stati() ),
					'posts_per_page' => 9999,
				);

				$topics = get_posts( $args );

				if ( ! empty( $topics ) ) {
					foreach ( $topics as $topic ) {
						$fields[] = array(
							'value' => $topic->ID,
							'text'  => $topic->post_title,
						);
					}
				}
			}
		}
		echo wp_json_encode( $fields );
		die();
	}

	/**
	 * Return all the specific topics of a forum in ajax call
	 */
	public function select_topic_fields_func_noany() {

		Automator()->utilities->ajax_auth_check( $_POST );

		$fields = array();
		if ( isset( $_POST ) ) {

			$forum_id = (int) automator_filter_input( 'value', INPUT_POST );

			if ( $forum_id > 0 ) {
				$args = array(
					'post_type'      => bbp_get_topic_post_type(),
					'post_parent'    => $forum_id,
					'post_status'    => array_keys( get_post_stati() ),
					'posts_per_page' => 9999,
				);

				$topics = get_posts( $args );

				if ( ! empty( $topics ) ) {
					foreach ( $topics as $topic ) {
						$fields[] = array(
							'value' => $topic->ID,
							'text'  => $topic->post_title,
						);
					}
				}
			}
		}
		echo wp_json_encode( $fields );
		die();
	}

	/**
	 * @param string $label
	 * @param string $option_code
	 *
	 * @return mixed
	 */
	public function list_bbpress_forums( $label = null, $option_code = 'BBFORUMS', $any_option = false, $multi_select = false ) {
		if ( ! $this->load_options ) {

			return Automator()->helpers->recipe->build_default_options_array( $label, $option_code );
		}

		if ( ! function_exists( 'bbp_get_forum_post_type' ) ) {

			return Automator()->helpers->recipe->build_default_options_array( $label, $option_code );
		}

		if ( ! $label ) {
			$label = esc_attr__( 'Forum', 'uncanny-automator' );
		}

		$any_label = null;

		if ( $any_option ) {
			$any_label = esc_attr__( 'Any forum', 'uncanny-automator' );
		}

		$args = array(
			'post_type'      => bbp_get_forum_post_type(),
			'posts_per_page' => 999,
			'orderby'        => 'title',
			'order'          => 'ASC',
			'post_status'    => array( 'publish', 'private' ),
		);

		$options = Automator()->helpers->recipe->options->wp_query( $args, $any_option, $any_label );

		$option = array(
			'option_code'              => $option_code,
			'label'                    => $label,
			'input_type'               => 'select',
			'supports_multiple_values' => $multi_select,
			'required'                 => true,
			'options'                  => $options,
			'relevant_tokens'          => array(
				$option_code          => esc_attr__( 'Forum title', 'uncanny-automator' ),
				$option_code . '_ID'  => esc_attr__( 'Forum ID', 'uncanny-automator' ),
				$option_code . '_URL' => esc_attr__( 'Forum URL', 'uncanny-automator' ),
			),
		);

		return apply_filters( 'uap_option_list_bbpress_forums', $option );
	}

	/**
	 * @param string $label
	 * @param string $option_code
	 *
	 * @return mixed
	 */
	public function list_bbpress_topics( $label = null, $option_code = 'BBTOPICS', $any_option = false, $multi_select = false ) {
		if ( ! $this->load_options ) {

			return Automator()->helpers->recipe->build_default_options_array( $label, $option_code );
		}

		if ( ! function_exists( 'bbp_get_topic_post_type' ) ) {

			return Automator()->helpers->recipe->build_default_options_array( $label, $option_code );
		}

		if ( ! $label ) {
			$label = esc_attr__( 'Topic', 'uncanny-automator-pro' );
		}

		$any_label = null;

		if ( $any_option ) {
			$any_label = esc_attr__( 'Any topic', 'uncanny-automator-pro' );
		}

		$args = array(
			'post_type'      => bbp_get_topic_post_type(),
			'posts_per_page' => 999,
			'orderby'        => 'title',
			'order'          => 'DESC',
			'post_status'    => array( 'publish', 'private' ),
		);

		$options = Automator()->helpers->recipe->options->wp_query( $args, $any_option, $any_label );

		$option = array(
			'option_code'              => $option_code,
			'label'                    => $label,
			'input_type'               => 'select',
			'supports_multiple_values' => $multi_select,
			'required'                 => true,
			'options'                  => $options,
			'relevant_tokens'          => array(
				$option_code                    => esc_attr__( 'Topic title', 'uncanny-automator-pro' ),
				$option_code . '_ID'            => esc_attr__( 'Topic ID', 'uncanny-automator-pro' ),
				$option_code . '_URL'           => esc_attr__( 'Topic URL', 'uncanny-automator-pro' ),
				$option_code . '_GUEST_EMAIL'   => esc_attr__( 'Guest email', 'uncanny-automator-pro' ),
				$option_code . '_GUEST_NAME'    => esc_attr__( 'Guest name', 'uncanny-automator-pro' ),
				$option_code . '_GUEST_WEBSITE' => esc_attr__( 'Guest website', 'uncanny-automator-pro' ),
				$option_code . '_FORUM_ID'      => esc_attr__( 'Forum ID', 'uncanny-automator-pro' ),
				$option_code . '_FORUM_TITLE'   => esc_attr__( 'Forum title', 'uncanny-automator-pro' ),
				$option_code . '_FORUM_URL'     => esc_attr__( 'Forum URL', 'uncanny-automator-pro' ),
			),
		);

		return apply_filters( 'uap_option_list_bbpress_topics', $option );
	}

}
