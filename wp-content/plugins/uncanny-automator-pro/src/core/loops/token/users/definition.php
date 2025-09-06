<?php
namespace Uncanny_Automator_Pro\Loops\Token\Users;

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

		if ( 'users' !== $loopable_expr['type'] ) {
			return $tokens;
		}

		$tokens[] = array(
			'data_type'  => 'int',
			'id'         => 'TOKEN_EXTENDED:LOOP_TOKEN:' . $id . ':USERS:USER_ID',
			'name'       => _x( 'User ID', 'User loop token', 'uncanny-automator-pro' ),
			'token_type' => 'custom',
		);

		$tokens[] = array(
			'data_type'  => 'text',
			'id'         => 'TOKEN_EXTENDED:LOOP_TOKEN:' . $id . ':USERS:USER_USERNAME',
			'name'       => _x( 'User username', 'User loop token', 'uncanny-automator-pro' ),
			'token_type' => 'custom',
		);

		$tokens[] = array(
			'data_type'  => 'email',
			'id'         => 'TOKEN_EXTENDED:LOOP_TOKEN:' . $id . ':USERS:USER_EMAIL',
			'name'       => _x( 'User email', 'User loop token', 'uncanny-automator-pro' ),
			'token_type' => 'custom',
		);

		$tokens[] = array(
			'data_type'  => 'text',
			'id'         => 'TOKEN_EXTENDED:LOOP_TOKEN:' . $id . ':USERS:USER_DISPLAY_NAME',
			'name'       => _x( 'User display name', 'User loop token', 'uncanny-automator-pro' ),
			'token_type' => 'custom',
		);

		$tokens[] = array(
			'data_type'  => 'text',
			'id'         => 'TOKEN_EXTENDED:LOOP_TOKEN:' . $id . ':USERS:USER_FIRST_NAME',
			'name'       => _x( 'User first name', 'User loop token', 'uncanny-automator-pro' ),
			'token_type' => 'custom',
		);

		$tokens[] = array(
			'data_type'  => 'text',
			'id'         => 'TOKEN_EXTENDED:LOOP_TOKEN:' . $id . ':USERS:USER_LAST_NAME',
			'name'       => _x( 'User last name', 'User loop token', 'uncanny-automator-pro' ),
			'token_type' => 'custom',
		);

		$tokens[] = array(
			'data_type'  => 'text',
			'id'         => 'TOKEN_EXTENDED:LOOP_TOKEN:' . $id . ':USERS:USER_ROLE',
			'name'       => _x( 'User role', 'User loop token', 'uncanny-automator-pro' ),
			'token_type' => 'custom',
		);

		$tokens[] = array(
			'data_type'  => 'url',
			'id'         => 'TOKEN_EXTENDED:LOOP_TOKEN:' . $id . ':USERS:USER_RESET_PASSWORD_URL',
			'name'       => _x( 'User reset password URL', 'User loop token', 'uncanny-automator-pro' ),
			'token_type' => 'custom',
		);

		$tokens[] = array(
			'data_type'  => 'text',
			'id'         => 'TOKEN_EXTENDED:LOOP_TOKEN:' . $id . ':USERS:USER_LOCALE',
			'name'       => _x( 'User locale', 'User loop token', 'uncanny-automator-pro' ),
			'token_type' => 'custom',
		);

		return $tokens;
	}
}
