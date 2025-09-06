<?php

namespace Uncanny_Automator_Pro\Loops\Token\Posts;

use Uncanny_Automator_Pro\Loops\Token\Text_Parseable;
use WP_Post;

/**
 * Posts tokens
 *
 * @since 5.3
 *
 * @package Uncanny_Automator_Pro\Loops\Token
 */
final class Parser extends Text_Parseable {

	/**
	 * The regexp pattern.
	 *
	 * @var string $pattern
	 */
	protected $pattern = '/{{TOKEN_EXTENDED:LOOP_TOKEN:\d+:POSTS:[^}]+}}/';

	/**
	 * @param $entity_id
	 * @param $extracted_token
	 *
	 * @return int|string|null
	 */
	public function parse( $entity_id, $extracted_token, $args ) {

		$wp_post = $this->get_post( $entity_id );

		// Make sure $wp_user is a valid user entity before proceeding.
		if ( ! $wp_post instanceof WP_Post ) {
			return '';
		}

		return $this->get_post_property( $wp_post, $extracted_token );
	}

	/**
	 * Retrieves the post from the run time cache or from the database.
	 *
	 * @param int $post_id
	 *
	 * @return WP_Post|mixed[]|null
	 */
	protected function get_post( $post_id ) {

		$tag   = $this->parser_filter_tag . '_' . $post_id;
		$group = $this->parser_filter_tag . '_group';

		$post_entity_cached = wp_cache_get( $tag, $group, true );

		if ( false !== $post_entity_cached && $post_entity_cached instanceof WP_Post ) {
			return $post_entity_cached;
		}

		$post_entity = get_post( $post_id, OBJECT );

		wp_cache_set( $tag, $post_entity, $group );

		return $post_entity;
	}

	/**
	 * Retrieves the actual token value from the token identifier.
	 *
	 * @param WP_Post $post
	 * @param string $token_id
	 *
	 * @return int|string
	 */
	protected function get_post_property( WP_Post $post, $token_id ) {

		$token_id_lowered = strtolower( $token_id );

		$user_id = absint( $post->post_author );

		switch ( $token_id_lowered ) {
			case 'post_id':
				return $post->ID;
			case 'post_url':
				$permalink = get_permalink( $post->ID );
				return ! empty( $permalink ) ? $permalink : '';
			case 'post_name':
				return $post->post_name;
			case 'post_author_id':
				return $post->post_author;
			case 'post_author_email':
				return get_the_author_meta( 'user_email', $user_id );
			case 'post_author_fname':
				return get_the_author_meta( 'user_firstname', $user_id );
			case 'post_author_lname':
				return get_the_author_meta( 'user_lastname', $user_id );
			case 'post_author_display_name':
				return get_the_author_meta( 'display_name', $user_id );
			case 'post_author_url':
				return get_the_author_meta( 'url', $user_id );
			case 'post_image_id':
				$thumbnail_id = get_post_thumbnail_id( $post->ID );
				return ! empty( $thumbnail_id ) ? $thumbnail_id : '';
			case 'post_image_url':
				$thumbnail_url = (string) get_the_post_thumbnail_url( $post->ID );
				return ! empty( $thumbnail_url ) ? $thumbnail_url : '';
			default:
				return $post->$token_id_lowered;
		}
	}
}
