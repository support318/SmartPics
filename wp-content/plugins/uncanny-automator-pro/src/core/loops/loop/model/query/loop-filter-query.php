<?php
namespace Uncanny_Automator_Pro\Loops\Loop\Model;

use Uncanny_Automator_Pro\Loops\Loop\Model\Loop_Filter\Fields;
use Uncanny_Automator_Pro\Loops\Loop\Model\Loop_Filter\Field;
use Uncanny_Automator_Pro\Loops\Loop\Model\Loop_Filter\Backup;
use Uncanny_Automator_Pro\Loops\Loop\Model\Loop_Filter;

/**
 * Class Loop_Filter_Query
 *
 * Handles adding, editing, deleting, and getting loop filter posts.
 *
 * @package Uncanny_Automator_Pro\Loops\Loop\Model
 */
class Loop_Filter_Query {

	/**
	 * Add a new Loop_Filter post.
	 *
	 * @param Loop_Filter $loop_filter The Loop_Filter object to be added.
	 * @return int|WP_Error The post ID on success, or WP_Error on failure.
	 */
	public function add( Loop_Filter $loop_filter ) {

		// Create slug from title.
		$slug = sanitize_title( $loop_filter->get_title() );

		// Prepare post data.
		$post_data = array(
			'post_title'  => $loop_filter->get_title(),
			'post_status' => $loop_filter->get_status(),
			'post_type'   => 'uo-loop-filter',
			'post_parent' => $loop_filter->get_parent(),
			'post_name'   => $slug,
		);

		// Insert the post.
		$post_id = wp_insert_post( $post_data );

		if ( is_wp_error( $post_id ) ) {
			return $post_id; // Return error if wp_insert_post failed.
		}

		// Update post meta with code, fields, and backup.
		update_post_meta( $post_id, 'code', $loop_filter->get_code() );
		update_post_meta( $post_id, 'fields', wp_json_encode( $loop_filter->get_fields() ) );
		update_post_meta( $post_id, 'backup', wp_json_encode( $loop_filter->get_backup() ) );
		update_post_meta( $post_id, 'integration', $loop_filter->get_integration() );
		update_post_meta( $post_id, 'integration_name', $loop_filter->get_integration_name() );
		update_post_meta( $post_id, 'integration_code', $loop_filter->get_integration() );

		// Set the ID of the Loop_Filter object to the newly inserted post ID.
		$loop_filter->set_id( $post_id );

		return $post_id;
	}

	/**
	 * Edit an existing Loop_Filter post.
	 *
	 * This method accepts only the Loop_Filter object, and the developer must set the ID beforehand.
	 *
	 * @param Loop_Filter $loop_filter The Loop_Filter object with updated data.
	 * @return Loop_Filter|WP_Error The updated Loop_Filter object on success, or WP_Error on failure.
	 */
	public function edit( Loop_Filter $loop_filter ) {
		if ( empty( $loop_filter->get_id() ) ) {
			return new \WP_Error( 'invalid_id', 'The Loop_Filter object must have a valid ID set.' );
		}

		// Prepare updated post data.
		$post_data = array(
			'ID'          => $loop_filter->get_id(),
			'post_title'  => $loop_filter->get_title(),
			'post_status' => $loop_filter->get_status(),
			'post_parent' => $loop_filter->get_parent(),
		);

		// Update the post.
		$updated_post_id = wp_update_post( $post_data );

		if ( is_wp_error( $updated_post_id ) ) {
			return $updated_post_id; // Return error if wp_update_post failed.
		}

		// Update post meta with new code, fields, and backup.
		update_post_meta( $updated_post_id, 'code', $loop_filter->get_code() );
		update_post_meta( $updated_post_id, 'fields', wp_json_encode( $loop_filter->get_fields() ) );
		update_post_meta( $updated_post_id, 'backup', wp_json_encode( $loop_filter->get_backup() ) );
		update_post_meta( $updated_post_id, 'integration', $loop_filter->get_integration() );
		update_post_meta( $updated_post_id, 'integration_name', $loop_filter->get_integration_name() );
		update_post_meta( $updated_post_id, 'integration_code', $loop_filter->get_integration() );

		return $loop_filter;
	}

	/**
	 * Delete an existing Loop_Filter post.
	 *
	 * @param int $post_id The ID of the Loop_Filter post to delete.
	 * @return bool True on success, false on failure.
	 */
	public function delete( $post_id ) {
		// Delete the post.
		return wp_delete_post( $post_id, true ) !== false;
	}

	/**
	 * Get a Loop_Filter post by its ID and return a Loop_Filter object.
	 *
	 * @param int $post_id The ID of the Loop_Filter post to retrieve.
	 * @return Loop_Filter|null The Loop_Filter object on success, or null if not found.
	 */
	public function get( $post_id ) {
		// Retrieve the post by ID.
		$post = get_post( $post_id );

		// Ensure it's a valid Loop_Filter post type.
		if ( $post && $post->post_type === 'loop_filter' ) {
			// Get post meta data.
			$fields_data = json_decode( get_post_meta( $post_id, 'fields', true ), true );
			$backup_data = json_decode( get_post_meta( $post_id, 'backup', true ), true );

			// Create Fields collection.
			$fields = new Fields();
			foreach ( $fields_data as $field_id => $field_info ) {
				$field = new Field();
				$field->set_id( $field_id );
				$field->set_type( $field_info['type'] );
				$field->set_value( $field_info['value'] );
				$field->set_backup( $field_info['backup'] );
				$fields->add_field( $field );
			}

			// Create Backup object.
			$backup = new Backup();
			$backup->set_sentence( $backup_data['sentence'] );
			$backup->set_sentence_html( $backup_data['sentence_html'] );

			// Create a new Loop_Filter object and set its properties.
			$loop_filter = new Loop_Filter( $fields, $backup );
			$loop_filter->set_id( $post->ID );
			$loop_filter->set_title( $post->post_title );
			$loop_filter->set_status( $post->post_status );
			$loop_filter->set_code( get_post_meta( $post->ID, 'code', true ) );
			$loop_filter->set_parent( $post->post_parent );

			return $loop_filter;
		}

		return null; // Return null if not a valid Loop_Filter post.
	}
}
