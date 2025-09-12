<?php

namespace Uncanny_Automator_Pro;

use Uncanny_Automator\Recipe;

/**
 * Class WP_SETPOSTTHUMBNAIL
 *
 * @package Uncanny_Automator
 */
class WP_SETPOSTTHUMBNAIL {

	use Recipe\Actions;

	public function __construct() {

		// Filter to remove URL protocol from Media IDs.
		add_filter( 'automator_field_values_before_save', array( $this, 'handle_mixed_media_sanitation' ), 10, 2 );

		$this->setup_action();

	}

	/**
	 * Setup Action.
	 *
	 * @return void.
	 */
	protected function setup_action() {

		$this->set_integration( 'WP' );

		$this->set_action_code( 'WP_SETPOSTTHUMBNAIL' );

		$this->set_action_meta( 'WP_SETPOSTTHUMBNAIL_META' );

		$this->set_requires_user( false );

		$this->set_is_pro( true );

		/* translators: Action - WordPress */
		$this->set_sentence( sprintf( esc_attr__( 'Set the featured image of {{a post:%1$s}}', 'uncanny-automator' ), $this->get_action_meta() ) );

		/* translators: Action - WordPress */
		$this->set_readable_sentence( esc_attr__( 'Set the featured image of {{a post}}', 'uncanny-automator' ) );

		$this->set_options_callback( array( $this, 'load_options' ) );

		$this->register_action();

	}

	/**
	 * Load options.
	 *
	 * @return void
	 */
	public function load_options() {

		$options = array(

			'options_group' => array(

				$this->get_action_meta() => array(

					Automator()->helpers->recipe->wp->options->pro->all_wp_post_types(
						__( 'Post type', 'uncanny-automator-pro' ),
						'WPSPOSTTYPES',
						array(
							'token'        => false,
							'is_ajax'      => true,
							'target_field' => $this->get_action_meta(),
							'is_any'       => false,
							'endpoint'     => 'select_all_post_of_selected_post_type_no_all',
						)
					),

					Automator()->helpers->recipe->field->select_field( $this->get_action_meta(), __( 'Post', 'uncanny-automator-pro' ) ),
					array(
						'input_type'     => 'url',
						'option_code'    => 'MEDIA_ID',
						'required'       => true,
						'supports_token' => true,
						'label'          => esc_html__( 'Media Library ID or external URL', 'uncanny-automator' ),
						'description'    => esc_html__( "Enter the ID or URL of an image in the Media Library. If the URL isn't in the Media Library, the image at the URL will be added to the Media Library.", 'uncanny-automator' ),
					),

					Automator()->helpers->recipe->field->text(
						array(
							'option_code' => 'IMAGE_TEXT',
							/* translators: Email field */
							'required'    => false,
							'label'       => esc_attr__( 'Alternative text (external images only)', 'uncanny-automator-pro' ),
							'input_type'  => 'text',
						)
					),

					Automator()->helpers->recipe->field->text(
						array(
							'option_code' => 'IMAGE_TITLE',
							/* translators: Email field */
							'required'    => false,
							'label'       => esc_attr__( 'Title (external images only)', 'uncanny-automator-pro' ),
							'input_type'  => 'text',
						)
					),

					Automator()->helpers->recipe->field->text(
						array(
							'option_code' => 'IMAGE_CAPTION',
							/* translators: Email field */
							'required'    => false,
							'label'       => esc_attr__( 'Caption (external images only)', 'uncanny-automator-pro' ),
							'input_type'  => 'text',
						)
					),

					Automator()->helpers->recipe->field->text(
						array(
							'option_code'      => 'IMAGE_DESCRIPTION',
							/* translators: Email field */
							'required'         => false,
							'label'            => esc_attr__( 'Description (external images only)', 'uncanny-automator-pro' ),
							'input_type'       => 'textarea',
							'supports_tinymce' => false,
						)
					),
				),

			),

		);

		return Automator()->utilities->keep_order_of_options( $options );

	}


	/**
	 * Process the action.
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

		$media   = isset( $parsed['MEDIA_ID'] ) ? $parsed['MEDIA_ID'] : '';
		$post_id = isset( $parsed[ $this->get_action_meta() ] ) ? sanitize_text_field( $parsed[ $this->get_action_meta() ] ) : 0;

		// Optional external image fields.
		$description = Automator()->parse->text( $action_data['meta']['IMAGE_DESCRIPTION'], $recipe_id, $user_id, $args );
		$title       = Automator()->parse->text( $action_data['meta']['IMAGE_TITLE'], $recipe_id, $user_id, $args );
		$caption     = Automator()->parse->text( $action_data['meta']['IMAGE_CAPTION'], $recipe_id, $user_id, $args );
		$alt_text    = Automator()->parse->text( $action_data['meta']['IMAGE_TEXT'], $recipe_id, $user_id, $args );

		try {

			$this->set_media( $media, $post_id, $description, $title, $caption, $alt_text );

			Automator()->complete->action( $user_id, $action_data, $recipe_id );

			return;

		} catch ( \Exception $e ) {

			$action_data['complete_with_errors'] = true;

			Automator()->complete->action( $user_id, $action_data, $recipe_id, $e->getMessage() );

		}

	}

	/**
	 * Set the Post Thumbnail using Media URL or Media ID.
	 *
	 * @param mixed $media The Media ID or the Media URL.
	 * @param int $post_id
	 * @param string $description
	 * @param string $title
	 * @param string $caption
	 * @param string $alt_text
	 *
	 * @return Boolean True on success. Otherwise, throws Exception.
	 *
	 * @throws Exception
	 */
	protected function set_media( $media = '', $post_id = 0, $description = null, $title = null, $caption = null, $alt_text = null ) {

		// Handle URLs.
		if ( ! is_numeric( $media ) ) {

			$image_url = filter_var( $media, FILTER_SANITIZE_URL );

			// Check if the URL is an existing Media URL.
			$media = absint( attachment_url_to_postid( $image_url, $post_id ) );

			// If the URL is not an existing Media URL, try to add the image to the Media Library.
			if ( empty( $media ) ) {
				$media = $this->add_image_to_media_library( $image_url, $post_id, $description, $title, $caption, $alt_text );
			}
		}

		// Handle IDs.
		$media = absint( sanitize_text_field( $media ) );

		return $this->set_media_using_id( $media, $post_id );
	}

	/**
	 * Add the image to the Media Library.
	 *
	 * @param string $image_url
	 * @param int $post_id
	 * @param string $description
	 * @param string $title
	 * @param string $caption
	 * @param string $alt_text
	 *
	 * @return int The Media ID of the image.
	 */
	private function add_image_to_media_library( $image_url, $post_id = 0, $description = null, $title = null, $caption = null, $alt_text = null ) {

		// Include supporting files.
		require_once ABSPATH . 'wp-admin/includes/media.php';
		require_once ABSPATH . 'wp-admin/includes/file.php';
		require_once ABSPATH . 'wp-admin/includes/image.php';

		// Download and store the image as attachment.
		$attachment_id = media_sideload_image( $image_url, $post_id, null, 'id' );

		// Check for WP_Error.
		if ( is_wp_error( $attachment_id ) ) {

			// Complete with error.
			throw new \Exception(
				sprintf(
					/* translators: Error message - WordPress */
					_x( 'Error: unable to add image to the media library: %s', 'WordPress - Set post thumbnail', 'uncanny-automator-pro' ),
					$attachment_id->get_error_message()
				),
				400
			);
		}

		// If no valid attachment ID, throw an error to complete the action with error.
		if ( empty( absint( $attachment_id ) ) ) {
			throw new \Exception( _x( 'Error: unable to add image to the media library', 'WordPress - Set post thumbnail', 'uncanny-automator-pro' ), 400 );
		}

		// Sanitize the description, title, caption, and alt text.
		$description = ! empty( $description ) ? sanitize_text_field( $description ) : null;
		$title       = ! empty( $title ) ? sanitize_text_field( $title ) : null;
		$caption     = ! empty( $caption ) ? sanitize_text_field( $caption ) : null;
		$alt_text    = ! empty( $alt_text ) ? sanitize_text_field( $alt_text ) : null;

		// Update the image details.
		if ( ! empty( $description ) || ! empty( $title ) || ! empty( $caption ) ) {

			$image_uploaded = wp_get_attachment_url( $attachment_id );
			$filetype       = wp_check_filetype( basename( $image_uploaded ) );

			$image_details = array(
				'post_title'     => $title,
				'post_excerpt'   => $caption,
				'post_content'   => $description,
				'ID'             => $attachment_id,
				'file'           => $image_uploaded,
				'post_mime_type' => $filetype['type'],
			);

			wp_insert_attachment( $image_details );
		}

		// Update the alt text.
		if ( ! empty( $alt_text ) ) {
			update_post_meta( $attachment_id, '_wp_attachment_image_alt', $alt_text );
		}

		return absint( $attachment_id );
	}


	/**
	 * Set the Post Thumbnail using Media ID.
	 *
	 * @param int $media_id
	 * @param int $post_id
	 *
	 * @return Boolean True on success. Otherwise, throws Exception.
	 *
	 * @throws Exception
	 */
	protected function set_media_using_id( $media_id = 0, $post_id = 0 ) {

		// Complete with error if media is not found.
		if ( empty( wp_get_attachment_image_src( $media_id ) ) ) {

			throw new \Exception(
				sprintf(
					/* translators: Media ID - WordPress */
					_x( 'Error: Cannot find media object using the Media ID: %s', 'WordPress - Set post thumbnail', 'uncanny-automator-pro' ),
					$media_id
				),
				400
			);

		}

		// Try setting the post thumbnail.
		$post_thumbnail = set_post_thumbnail( $post_id, $media_id );

		// Complete with error if there are any issues.
		if ( false === $post_thumbnail ) {

			throw new \Exception( _x( 'The function `set_post_thumbnail` has returned false. The media is already assigned to the post or there was an unexpected error.', 'WordPress - Set post thumbnail', 'uncanny-automator-pro' ), 400 );

		}

		return true;

	}

	/**
	 * Remove protocol from Media IDs before save.
	 *
	 * Resolves issues when setting the MEDIA_ID `text` field using a URL with encoded spaces.
	 * Example: https://example.com/wp-content/uploads/2020/01/My%20Image.jpg
	 * When setting field type to `url` and providing a Media Library ID it gets prefixed with the protocol.
	 *
	 * @param mixed $meta_value
	 * @param mixed $item
	 *
	 * @return mixed
	 */
	public function handle_mixed_media_sanitation( $meta_value, $item ) {

		if ( ! isset( $meta_value['MEDIA_ID'] ) || empty( $meta_value['MEDIA_ID'] ) ) {
			return $meta_value;
		}

		$media = $meta_value['MEDIA_ID'];

		// Check if $media is protocol and number like http://123
		if ( preg_match( '/^https?:\/\/\d+$/', $media ) === 1 ) {
			$media = str_replace( array( 'http://', 'https://' ), '', $media );
		}

		// Return original value if $media is not a number
		if ( ! is_numeric( $media ) ) {
			return $meta_value;
		}

		// Update the $meta_value with the sanitized value
		$meta_value['MEDIA_ID'] = (int) $media;

		return $meta_value;
	}

}
