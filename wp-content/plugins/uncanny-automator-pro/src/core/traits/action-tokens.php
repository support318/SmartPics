<?php
/**
 * For backwards compatibility with Uncanny_Automator 4.6 or below.
 *
 * Provides dummy Action_Tokens traits in case Action_Token traits
 * is not available in the free version of Uncanny Automator.
 *
 * Prevents fatal error.
 *
 * @since 4.6
 */
namespace Uncanny_Automator_Pro\Recipe;

if ( trait_exists( '\Uncanny_Automator\Recipe\Action_Tokens' ) ) {

	trait Action_Tokens {
		use \Uncanny_Automator\Recipe\Action_Tokens;
	}

} else {

	trait Action_Tokens {
		public function set_action_tokens() {
			return false;
		}
		public function hydrate_tokens() {
			return false;
		}
	}

}
