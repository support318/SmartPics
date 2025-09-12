<?php
namespace Uncanny_Automator_Pro;

class JetEngine_Tokens {

	public function common_tokens() {

		return array(
			'FIELD_NAME'  => array(
				'name'         => __( 'JetEngine field name/ID', 'uncanny-automator-pro' ),
				'hydrate_with' => 'trigger_args|2',
			),
			'FIELD_VALUE' => array(
				'name'         => __( 'JetEngine field value', 'uncanny-automator-pro' ),
				'hydrate_with' => array( $this, 'format_token_value' ),
			),
		);

	}

	public function format_token_value( ...$args ) {

		list( $mid, $object_id, $meta_key, $meta_value ) = $args[1]['trigger_args'];

		if ( is_array( $meta_value ) ) {
			return implode( ', ', $meta_value );
		}

		return $meta_value;

	}

	/**
	 * Populate the token with actual values.
	 *
	 * @param $args
	 * @param $trigger
	 *
	 * @return array
	 */
	public function hydrate_tokens( $parsed, $args, $trigger ) {

		$post_id = $args['trigger_args'][1];

		return $parsed + array(
			'POST_TYPE'           => get_the_title( $post_id ),
			'POST_TYPE_ID'        => absint( $post_id ),
			'POST_TYPE_URL'       => get_permalink( $post_id ),
			'POST_TYPE_THUMB_ID'  => get_post_thumbnail_id( $post_id ),
			'POST_TYPE_THUMB_URL' => get_the_post_thumbnail_url( $post_id ),
		);

	}

}
