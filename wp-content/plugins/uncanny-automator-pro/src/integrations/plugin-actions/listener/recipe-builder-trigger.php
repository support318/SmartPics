<?php
namespace Uncanny_Automator_Pro\Integrations\Plugin_Actions\Listener;

use Uncanny_Automator_Pro\Integrations\Plugin_Actions\Utils\Array_Flattener;
use WP_Error;
use WP_REST_Request;
use WP_REST_Response;

/**
 * Recipe_Builder_Trigger
 *
 * @package Uncanny_Automator_Pro\Integrations\Plugin_Actions\Listener
 */
class Recipe_Builder_Trigger {

	/**
	 * @var string
	 */
	const WP_AJAX_ACTION = 'automator_add_action_wp_hook_trigger_action';

	/**
	 * @var string
	 */
	const STATE_OPTION_KEY = 'ADD_ACTION_WP_HOOK_LISTENING';

	/**
	 * Returns the specific body request.
	 *
	 * @param string $tag
	 *
	 * @return string
	 */
	public static function get_body( string $tag ) {
		return automator_filter_input( $tag, INPUT_POST );
	}

	/**
	 * Callback function to /uap/v2/run-code-wp-hook.
	 *
	 * @return \WP_REST_Response
	 */
	public static function samples_callback( WP_REST_Request $request ) {

		$data       = array();
		$is_success = true;

		$post_id      = $request->get_param( 'item_id' );
		$hook_args    = $request->get_param( 'hook_args' );
		$request_type = $request->get_param( 'request_type' );

		if ( 'hook_fired' === $request_type ) {
			update_post_meta( $post_id, 'hook_fired_args', $hook_args );
		}

		$hook_fired_args = get_post_meta( $post_id, 'hook_fired_args', true );

		if ( ! empty( $hook_fired_args ) ) {

			$hook_fired_args_array = (array) json_decode( $hook_fired_args, true );

			$flattener = new Array_Flattener();

			$flattener->flatten_array( $hook_fired_args_array );
			$flattend_array = $flattener->get_flattened_array();

			foreach ( $flattend_array as $key => $item ) {
				$data[] = array(
					'key'  => 'argument_' . $key,
					'type' => self::determine_string_type( $item ),
				);
			}
		}

		update_post_meta( $post_id, self::STATE_OPTION_KEY, 'listening' );

		automator_pro_add_option( 'automator_run_hook_listening', 'yes' );

		$response = array(
			'success' => $is_success,
			'samples' => $data,
		);

		if ( 'hook_fired' !== $request_type ) {
			delete_post_meta( $post_id, 'hook_fired_args' );
		}

		return new WP_REST_Response( $response );
	}

	/**
	 * The saves the trigger tokens and returns a response.
	 *
	 * @return WP_REST_Response|WP_Error
	 */
	public static function samples_save_trigger_tokens_callback( WP_REST_Request $request ) {

		$tokens_stringified = $request->get_param( 'tokens' );
		$tokens_decoded     = json_decode( $tokens_stringified, true );
		$trigger_id         = absint( $request->get_param( 'trigger_id' ) );

		if ( empty( $tokens_stringified ) ) {
			return new \WP_Error( 400, 'Cannot save empty tokens', $request );
		}

		if ( json_last_error() !== JSON_ERROR_NONE ) {
			// There was an error decoding the JSON.
			return new \WP_Error( 400, 'JSON Error: ' . json_last_error_msg(), $request );
		}

		// Make sure the data is array.
		$tokens_array = (array) $tokens_decoded;

		update_post_meta( $trigger_id, 'ADD_ACTION_CAPTURED_TOKENS', $tokens_array );

		$data = array(
			'tokens' => $tokens_array,
		);

		$response = array(
			'success'    => true,
			'samples'    => $data,
			'trigger_id' => $trigger_id,
		);

		return new WP_REST_Response( $response );

	}

	/**
	 * Determines the type of the given parameter.
	 *
	 * @param mixed $string
	 *
	 * @return string
	 */
	public static function determine_string_type( $string ) {

		if ( is_numeric( $string ) ) {
			return 'numeric';
		}

		if ( is_bool( $string ) ) {
			return 'boolean';
		}

		return 'string';

	}

	/**
	 * Returns the button config.
	 *
	 * @return array{show_in: string, text: string, css_classes: string, on_click: string, modules: string[]}[]
	 */
	public static function get_buttons_config() {

		return array(
			array(
				'show_in'     => 'ADD_ACTION_TRIGGER_META',
				/* translators: Button. Non-personal infinitive verb */
				'text'        => __( 'Capture hook data', 'uncanny-automator-pro' ),
				'css_classes' => 'uap-btn uap-btn--red',
				'on_click'    => self::get_samples_js(),
				'modules'     => array( 'modal', 'markdown' ),
			),
		);
	}

	/**
	 * JS function triggered when clicking "Send test". Requires "modal" module. Returns JS code.
	 *
	 * @return string The JS code, with or without the <script> tags
	 */
	public static function get_samples_js() {

		ob_start();

		$asset = trailingslashit( UAPro_ABSPATH ) . 'src/integrations/plugin-actions/listener/assets/samples-js.php';

		if ( file_exists( $asset ) ) {
			include $asset;
		}

		return ob_get_clean();
	}

	/**
	 * A piece of CSS that it's added only when this item is on the recipe.
	 *
	 * @return string The CSS, with the CSS tags
	 */
	public static function inline_css() {

		ob_start();

		$css = trailingslashit( UAPro_ABSPATH ) . 'src/integrations/run-code/listener/assets/inline-css.css';

		if ( file_exists( $css ) ) {
			include $css;
		}

		return ob_get_clean();
	}

}
