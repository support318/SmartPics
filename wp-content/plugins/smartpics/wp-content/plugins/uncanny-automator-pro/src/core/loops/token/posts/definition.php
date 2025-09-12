<?php
namespace Uncanny_Automator_Pro\Loops\Token\Posts;

/**
 * Posts tokens definitions.
 *
 * @since 5.3
 *
 * @package Uncanny_Automator_Pro\Loops\Token
 */
final class Definition {

	/**
	 * Registers the tokens.
	 *
	 * @return void
	 */
	public static function register_hooks() {
		add_filter( 'automator_recipe_main_object_loop_tokens_items', array( new self(), 'list_tokens' ), 10, 2 );
	}

	/**
	 * List all tokens.
	 *
	 * @param string[] $tokens
	 * @param \Uncanny_Automator\Services\Recipe\Structure\Actions\Item\Loop $loop
	 *
	 * @return mixed[]
	 */
	public function list_tokens( $tokens, $loop ) {

		$id = $loop->get( 'id' );

		$loopable_expr = $loop->get( 'iterable_expression' );

		if ( ! isset( $loopable_expr['type'] ) ) {
			return $tokens;
		}

		if ( 'posts' !== $loopable_expr['type'] ) {
			return $tokens;
		}

		$tokens[] = array(
			'data_type'  => 'int',
			'id'         => 'TOKEN_EXTENDED:LOOP_TOKEN:' . $id . ':POSTS:POST_ID',
			'name'       => _x( 'Post ID', 'Post loop token', 'uncanny-automator-pro' ),
			'token_type' => 'custom',
		);

		$tokens[] = array(
			'data_type'  => 'text',
			'id'         => 'TOKEN_EXTENDED:LOOP_TOKEN:' . $id . ':POSTS:POST_TITLE',
			'name'       => _x( 'Post title', 'Post loop token', 'uncanny-automator-pro' ),
			'token_type' => 'custom',
		);

		$tokens[] = array(
			'data_type'  => 'text',
			'id'         => 'TOKEN_EXTENDED:LOOP_TOKEN:' . $id . ':POSTS:POST_DATE',
			'name'       => _x( 'Post date', 'Post loop token', 'uncanny-automator-pro' ),
			'token_type' => 'custom',
		);

		$tokens[] = array(
			'data_type'  => 'text',
			'id'         => 'TOKEN_EXTENDED:LOOP_TOKEN:' . $id . ':POSTS:POST_MODIFIED',
			'name'       => _x( 'Post modified date', 'Post loop token', 'uncanny-automator-pro' ),
			'token_type' => 'custom',
		);

		$tokens[] = array(
			'data_type'  => 'text',
			'id'         => 'TOKEN_EXTENDED:LOOP_TOKEN:' . $id . ':POSTS:POST_CONTENT',
			'name'       => _x( 'Post content', 'Post loop token', 'uncanny-automator-pro' ),
			'token_type' => 'custom',
		);

		$tokens[] = array(
			'data_type'  => 'text',
			'id'         => 'TOKEN_EXTENDED:LOOP_TOKEN:' . $id . ':POSTS:POST_EXCERPT',
			'name'       => _x( 'Post excerpt', 'Post loop token', 'uncanny-automator-pro' ),
			'token_type' => 'custom',
		);

		$tokens[] = array(
			'data_type'  => 'text',
			'id'         => 'TOKEN_EXTENDED:LOOP_TOKEN:' . $id . ':POSTS:POST_STATUS',
			'name'       => _x( 'Post status', 'Post loop token', 'uncanny-automator-pro' ),
			'token_type' => 'custom',
		);

		$tokens[] = array(
			'data_type'  => 'text',
			'id'         => 'TOKEN_EXTENDED:LOOP_TOKEN:' . $id . ':POSTS:POST_TYPE',
			'name'       => _x( 'Post type', 'Post loop token', 'uncanny-automator-pro' ),
			'token_type' => 'custom',
		);

		$tokens[] = array(
			'data_type'  => 'url',
			'id'         => 'TOKEN_EXTENDED:LOOP_TOKEN:' . $id . ':POSTS:POST_URL',
			'name'       => _x( 'Post URL', 'Post loop token', 'uncanny-automator-pro' ),
			'token_type' => 'custom',
		);
		$tokens[] = array(
			'data_type'  => 'text',
			'id'         => 'TOKEN_EXTENDED:LOOP_TOKEN:' . $id . ':POSTS:POST_NAME',
			'name'       => _x( 'Post slug', 'Post loop token', 'uncanny-automator-pro' ),
			'token_type' => 'custom',
		);

		$tokens[] = array(
			'data_type'  => 'int',
			'id'         => 'TOKEN_EXTENDED:LOOP_TOKEN:' . $id . ':POSTS:POST_AUTHOR_ID',
			'name'       => _x( 'Post author ID', 'Post loop token', 'uncanny-automator-pro' ),
			'token_type' => 'custom',
		);

		$tokens[] = array(
			'data_type'  => 'text',
			'id'         => 'TOKEN_EXTENDED:LOOP_TOKEN:' . $id . ':POSTS:POST_AUTHOR_FNAME',
			'name'       => _x( 'Post author first name', 'Post loop token', 'uncanny-automator-pro' ),
			'token_type' => 'custom',
		);

		$tokens[] = array(
			'data_type'  => 'text',
			'id'         => 'TOKEN_EXTENDED:LOOP_TOKEN:' . $id . ':POSTS:POST_AUTHOR_LNAME',
			'name'       => _x( 'Post author last name', 'Post loop token', 'uncanny-automator-pro' ),
			'token_type' => 'custom',
		);

		$tokens[] = array(
			'data_type'  => 'text',
			'id'         => 'TOKEN_EXTENDED:LOOP_TOKEN:' . $id . ':POSTS:POST_AUTHOR_DISPLAY_NAME',
			'name'       => _x( 'Post author display name', 'Post loop token', 'uncanny-automator-pro' ),
			'token_type' => 'custom',
		);

		$tokens[] = array(
			'data_type'  => 'email',
			'id'         => 'TOKEN_EXTENDED:LOOP_TOKEN:' . $id . ':POSTS:POST_AUTHOR_EMAIL',
			'name'       => _x( 'Post author email', 'Post loop token', 'uncanny-automator-pro' ),
			'token_type' => 'custom',
		);

		$tokens[] = array(
			'data_type'  => 'url',
			'id'         => 'TOKEN_EXTENDED:LOOP_TOKEN:' . $id . ':POSTS:POST_AUTHOR_URL',
			'name'       => _x( 'Post author URL', 'Post loop token', 'uncanny-automator-pro' ),
			'token_type' => 'custom',
		);

		$tokens[] = array(
			'data_type'  => 'int',
			'id'         => 'TOKEN_EXTENDED:LOOP_TOKEN:' . $id . ':POSTS:POST_IMAGE_ID',
			'name'       => _x( 'Post featured image ID', 'Post loop token', 'uncanny-automator-pro' ),
			'token_type' => 'custom',
		);

		$tokens[] = array(
			'data_type'  => 'url',
			'id'         => 'TOKEN_EXTENDED:LOOP_TOKEN:' . $id . ':POSTS:POST_IMAGE_URL',
			'name'       => _x( 'Post featured image URL', 'Post loop token', 'uncanny-automator-pro' ),
			'token_type' => 'custom',
		);

		return $tokens;
	}
}
