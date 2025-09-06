<?php
namespace Uncanny_Automator_Pro\Loops\Token\Common;

/**
 * Posts tokens definitions.
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

		$tokens[] = array(
			'data_type'  => 'int',
			'id'         => 'TOKEN_EXTENDED:LOOP_TOKEN:' . $id . ':COMMON:POST_ID',
			'name'       => esc_html_x( 'Loop iteration', 'Loop Common token', 'uncanny-automator-pro' ),
			'token_type' => 'custom',
		);

		return $tokens;
	}
}
