<?php
namespace Uncanny_Automator_Pro\Loops\Loop\Model;

use Uncanny_Automator_Pro\Loops\Loop\Model\Loop;
use WP_Post;

/**
 * Class Loop_Query
 *
 * Handles operations like adding, editing, deleting, and getting loop posts.
 *
 * @package Uncanny_Automator_Pro\Loops\Loop\Model
 */
class Loop_Query {

	/**
	 * Add a new Loop post.
	 *
	 * @param Loop $loop The Loop object to be added.
	 * @return int|WP_Error The post ID on success, or WP_Error on failure.
	 */
	public function add( Loop $loop ) {

		// Create slug from title.
		$slug = sanitize_title( $loop->get_title() );

		// Prepare post data.
		$post_data = array(
			'post_title'  => $loop->get_title(),
			'post_status' => $loop->get_status(),
			'post_type'   => 'uo-loop',
			'post_parent' => $loop->get_parent(),
			'post_name'   => $slug,
		);

		// Insert the post.
		$post_id = wp_insert_post( $post_data );

		// Update post meta with code and iterable expression.
		update_post_meta( $post_id, 'code', $loop->get_code() );
		update_post_meta( $post_id, 'iterable_expression', $loop->get_iterable_expression()->to_array() );

		// Set the ID of the Loop object to the newly inserted post ID.
		$loop->set_id( $post_id );

		return $post_id;
	}

	/**
	 * Edit an existing Loop post.
	 *
	 * This method accepts only the Loop object, and the developer must set the ID beforehand.
	 *
	 * @param Loop $loop The Loop object with updated data.
	 * @return Loop|WP_Error The updated Loop object on success, or WP_Error on failure.
	 */
	public function edit( Loop $loop ) {
		if ( empty( $loop->get_id() ) ) {
			return new \WP_Error( 'invalid_id', 'The Loop object must have a valid ID set.' );
		}

		// Prepare updated post data.
		$post_data = array(
			'ID'          => $loop->get_id(),
			'post_title'  => $loop->get_title(),
			'post_status' => $loop->get_status(),
			'post_parent' => $loop->get_parent(),
		);

		// Update the post.
		$updated_post_id = wp_update_post( $post_data );

		if ( is_wp_error( $updated_post_id ) ) {
			return $updated_post_id; // Return error if wp_update_post failed.
		}

		// Update post meta with new code and iterable expression.
		update_post_meta( $updated_post_id, 'code', $loop->get_code() );
		update_post_meta( $updated_post_id, 'iterable_expression', $loop->get_iterable_expression()->to_array() );

		return $loop; // Return the updated Loop object.
	}

	/**
	 * Delete an existing Loop post.
	 *
	 * @param int $post_id The ID of the Loop post to delete.
	 * @return bool True on success, false on failure.
	 */
	public function delete( $post_id ) {
		// Delete the post.
		return wp_delete_post( $post_id, true ) !== false;
	}

	/**
	 * Get a Loop post by its ID and return a Loop object.
	 *
	 * @param int $post_id The ID of the Loop post to retrieve.
	 * @return Loop|null The Loop object on success, or null if not found.
	 */
	public function get( $post_id ) {
		// Retrieve the post by ID.
		$post = get_post( $post_id );

		// Ensure it's a valid Loop post type.
		if ( $post && $post->post_type === 'loop' ) {
			// Create a new Loop object.
			$iterable_expression = (array) get_post_meta( $post_id, 'iterable_expression', true );
			$loop                = new Loop( new Iterable_Expression( $iterable_expression ) );

			// Set the Loop object properties.
			$loop->set_id( $post->ID );
			$loop->set_title( $post->post_title );
			$loop->set_status( $post->post_status );
			$loop->set_code( get_post_meta( $post->ID, 'code', true ) );
			$loop->set_parent( $post->post_parent );

			return $loop;
		}

		return null; // Return null if not a valid Loop post.
	}
}
