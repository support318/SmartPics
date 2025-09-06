<?php
namespace Uncanny_Automator_Pro\Integrations\Plugin_Actions\Triggers;

use Uncanny_Automator\Actionify_Triggers;
use Uncanny_Automator_Pro\Integration\Plugin_Actions\Actions\Listeners\Queries;
use Uncanny_Automator_Pro\Integrations\Plugin_Actions\Utils\Array_Flattener;
use Uncanny_Automator_Pro\Integrations\Plugin_Actions\Listener\Recipe_Builder_Trigger;

/**
 * Class Run_Do_Action
 *
 * @package Uncanny_Automator_Pro
 */
class Add_Action_Trigger extends \Uncanny_Automator\Recipe\Trigger {

	/**
	 * @var string
	 */
	const CAPTURED_TOKENS_POSTMETA_KEY = 'ADD_ACTION_CAPTURED_TOKENS';

	/**
	 * Setups the trigger.
	 *
	 * @return void
	 */
	protected function setup_trigger() {

		add_action( 'rest_api_init', array( $this, 'register_endpoint' ) );
		add_action( 'automator_actionify_triggers_after', array( $this, 'register_listeners' ) );

		$this->set_trigger_type( 'anonymous' );
		$this->set_integration( 'ADD_ACTION' );
		$this->set_trigger_code( 'ADD_ACTION_TRIGGER_CODE' );
		$this->set_trigger_meta( 'ADD_ACTION_TRIGGER_META' );
		$this->set_is_pro( true );

		/* Translators: Trigger sentence */
		$this->set_sentence(
			sprintf(
				/* Translators: Trigger sentence */
				_x( 'Create a custom trigger for {{a plugin action hook:%1$s}}', 'Plugin Actions', 'uncanny-automator-pro' ),
				$this->get_trigger_meta()
			)
		);

		/* Translators: Trigger sentence */
		$this->set_readable_sentence(
			_x( 'Create a custom trigger for {{a plugin action hook}}', 'Plugin Actions', 'uncanny-automator-pro' )
		);

		$this->set_buttons( Recipe_Builder_Trigger::get_buttons_config() );
		$this->set_inline_css( Recipe_Builder_Trigger::inline_css() );

		$this->add_action( 'add_action_wp_hook', 10, 2 );

	}

	/**
	 * @return void
	 */
	public function register_endpoint() {

		$permission_callback = function() {
			// Allow only admins to listen.
			return current_user_can( 'manage_options' );
		};

		register_rest_route(
			'uap/v2',
			'/run-code-wp-hook',
			array(
				'methods'             => 'POST',
				'permission_callback' => $permission_callback,
				'callback'            => array( Recipe_Builder_Trigger::class, 'samples_callback' ),
			)
		);

		register_rest_route(
			'uap/v2',
			'/run-code-wp-hook-save-trigger-tokens',
			array(
				'methods'             => 'POST',
				'permission_callback' => $permission_callback,
				'callback'            => array( Recipe_Builder_Trigger::class, 'samples_save_trigger_tokens_callback' ),
			)
		);
	}

	/**
	 * Register the event listeners.
	 *
	 * @return void
	 */
	public function register_listeners() {

		$triggers = ( new Queries() )->find_triggers();

		foreach ( $triggers as $trigger_id ) {

			$meta = get_post_meta( $trigger_id );
			$hook = $meta[ $this->trigger_meta ][0] ?? '';
			$prio = $meta[ $this->action_priority ][0] ?? 10;
			$num  = $meta[ $this->action_args_count ][0] ?? 1;

			if ( empty( $hook ) ) {
				continue;
			}

			add_action( $hook, array( self::class, 'register_listener' ), $prio, $num );

		}

		// Fetch the current run code > wp_hook triggers that are currently 'listening'.
		self::fetch_triggers_listening();

	}

	/**
	 * Fetch all run code wp hook triggers that are listening.
	 *
	 * This will only fetch if there are triggers that are waiting for a hook.
	 * The option 'automator_run_hook_listening' is autoloaded so no additional query is needed.
	 *
	 * @since 6.0
	 *
	 * @return void
	 */
	public static function fetch_triggers_listening() {

		$is_listening = automator_get_option( 'automator_run_hook_listening', 'no' );

		if ( 'yes' !== $is_listening ) {
			return;
		}

		$triggers_listening = ( new Queries() )->find_triggers_listening();

		foreach ( $triggers_listening as $trigger_id ) {

			$postmeta = get_post_meta( $trigger_id );
			$tag      = $postmeta['ADD_ACTION_TRIGGER_META'][0] ?? '';
			$priority = $postmeta['ADD_ACTION_ARGS_COUNT'][0] ?? 10;
			$num_args = $postmeta['ADD_ACTION_HOOK_PRIORITY'][0] ?? 1;

			if ( empty( $tag ) ) {
				continue; // Skip empty tags.
			}

			// Listen for the action hook that was selected.
			add_action(
				$tag,
				// The callback.
				function( ...$args ) use ( $trigger_id, $tag ) {
					// Defer on shutdown hook to make the performance more snappy.
					add_action(
						'shutdown',
						function() use ( $trigger_id, $tag, $args ) {
							// Send response to wp-ajax listener.
							self::send_feedback( $trigger_id, $tag, $args );
						}
					);
				},
				$priority,
				$num_args
			);

		}
	}

	/**
	 * Send the feedback.
	 *
	 * @param int $trigger_id The hook tag name.
	 * @param string $tag The hook tag name.
	 * @param mixed[] $args The arguments sent by the hook.
	 *
	 * @return void
	 */
	public static function send_feedback( $trigger_id, $tag, $args ) {

		$action       = 'automator_run_code_wp_hook_trigger_action';
		$decoded_args = (string) wp_json_encode( $args );
		$url          = get_rest_url( null, 'uap/v2/run-code-wp-hook' );

		$body = array(
			'hook_args'    => $decoded_args,
			'hook_tag'     => $tag,
			'action'       => $action,
			'item_id'      => $trigger_id,
			'request_type' => 'hook_fired',
		);

		$headers = array(
			'X-WP-Nonce'    => wp_create_nonce( 'wp_rest' ),
			'Cache-Control' => 'no-cache',
		);

		// Need session cookies passed to verify nonce.
		$cookies = array();

		foreach ( (array) $_COOKIE as $name => $value ) {
			$cookies[] = new \WP_Http_Cookie(
				array(
					'name'  => $name,
					'value' => $value,
				)
			);
		}

		$args = array(
			'method'    => 'POST',
			'timeout'   => apply_filters( 'http_request_timeout', 60, $url ),
			'blocking'  => false,
			'sslverify' => true, // No need to verify SSL.
			'body'      => $body,
			'headers'   => $headers,
			'cookies'   => $cookies,
		);

		// We dont really care about the response.
		wp_remote_post( $url, $args );

	}

	/**
	 * @param mixed ...$args
	 * @return mixed|void
	 */
	public static function register_listener( ...$args ) {

		do_action( 'add_action_wp_hook', current_action(), $args );

		if ( ! empty( $args ) ) {
			return array_shift( $args );
		}

	}

	/**
	 * Setups the options.
	 *
	 * @return array{array:mixed[]}
	 */
	public function options() {

		$wordpress_hook = array(
			'input_type'            => 'text',
			'option_code'           => $this->get_trigger_meta(),
			'label'                 => esc_html__( 'Action hook', 'uncanny-automator-pro' ),
			'required'              => true,
			'supports_custom_value' => false,
			'description'           => esc_html__( "Specify the WordPress hook that will trigger this action, such as 'before_delete_post.", 'uncanny-automator-pro' ),
			'relevant_tokens'       => array(),
		);

		$priority = array(
			'input_type'            => 'int',
			'option_code'           => 'ADD_ACTION_HOOK_PRIORITY',
			'label'                 => esc_html__( 'Priority', 'uncanny-automator-pro' ),
			'required'              => true,
			'supports_custom_value' => false,
			'default'               => 10,
			'description'           => esc_html__( 'Set the priority for this action. Lower numbers execute earlier; the default is 10 if left blank.', 'uncanny-automator-pro' ),
			'relevant_tokens'       => array(),
		);

		$args_count = array(
			'input_type'            => 'int',
			'option_code'           => 'ADD_ACTION_ARGS_COUNT',
			'label'                 => esc_html__( 'Arguments count', 'uncanny-automator-pro' ),
			'required'              => true,
			'supports_custom_value' => false,
			'description'           => esc_html__( "Enter the number of arguments the function accepts. For example, use '1' if it accepts a single argument.", 'uncanny-automator-pro' ),
			'relevant_tokens'       => array(),
		);

		return array(
			$wordpress_hook,
			$priority,
			$args_count,
		);

	}


	/**
	 * Defines the tokens for this trigger.
	 *
	 * @param array{string[]} $tokens
	 * @param mixed[] $trigger
	 *
	 * @return array{string[]}
	 */
	public function define_tokens( $trigger, $tokens ) {

		$stored_tokens = $trigger['meta'][ self::CAPTURED_TOKENS_POSTMETA_KEY ] ?? '';

		// The identified tokens.
		$tokens_meta = (array) maybe_unserialize( $stored_tokens );

		// If tokens are identified, present it.
		if ( ! empty( $stored_tokens ) && is_array( $tokens_meta ) ) {
			foreach ( $tokens_meta as $token ) {
				if ( ! empty( $token['key'] ) ) {
					$tokens[] = array(
						'tokenId'   => $token['key'],
						'tokenName' => $token['key'],
					);
				}
			}
		}

		return $tokens;
	}

	/**
	 * Creates a list of default tokens base on the number of arguments.
	 *
	 * @param int $args_count
	 *
	 * @return array{}|array{string[]}
	 */
	public function make_default_tokens( $args_count ) {

		$tokens = array();

		for ( $i = 0; $i < $args_count; $i++ ) {

			$label    = $i + 1;
			$token_id = 'ARG_' . $i;

			$tokens[] = array(
				'tokenId'   => $token_id,
				'tokenName' => sprintf( __( 'Argument %1$s', 'automator-sample' ), $label ),
			);
		}

		return $tokens;

	}

	/**
	 * Hydrates the tokens dynamically.
	 *
	 * @param mixed[] $trigger
	 * @param mixed[] $hook_args
	 *
	 * @return array{mixed[]}
	 */
	public function hydrate_tokens( $trigger, $hook_args ) {

		// Hydrate identified tokens.
		$tokens_meta = (array) maybe_unserialize( $trigger['meta'][ self::CAPTURED_TOKENS_POSTMETA_KEY ] ?? '' );

		$tokens = array();

		foreach ( $tokens_meta as $token ) {
			if ( ! is_array( $token ) || ! isset( $token['key'] ) ) {
				continue;//skip.
			}
			$tokens[ $token['key'] ] = ''; // Initialize as empty string.
		}

		$hook_args_to_array = (array) json_decode( wp_json_encode( (array) $hook_args[1] ), true );

		$array_flattener = new Array_Flattener();
		$array_flattener->flatten_array( $hook_args_to_array );

		$flattened_array = $array_flattener->get_flattened_array();

		foreach ( $flattened_array as $key => $token_value ) {
			$tokens[ 'argument_' . $key ] = self::format_token_value( $token_value );
		}

		return $tokens;

	}

	/**
	 * Validates the trigger.
	 *
	 * @param mixed[] $trigger
	 * @param mixed[] $hook_args
	 *
	 * @return bool
	 */
	public function validate( $trigger, $hook_args ) {

		return $this->validate_current_run( $trigger, $hook_args );

	}

	/**
	 * @param mixed[] $trigger
	 * @param mixed[] $hook_args
	 *
	 * @return bool
	 */
	public function validate_current_run( $trigger, $hook_args ) {

		$current_hook = $hook_args[0];
		$trigger_hook = $trigger['meta']['ADD_ACTION_TRIGGER_META'] ?? '';

		$is_current_hook = $current_hook === $trigger_hook;

		if ( ! $is_current_hook ) {
			return false;
		}

		// If the trigger hook is used in context of filter.
		if ( isset( $hook_args[1][0] ) ) {
			// Return the first argument.
			return $hook_args[1][0];
		}

		// Otherwise, if there are action hook args, just return boolean.
		return true;

	}

	/**
	 * Returns the number of arguments count from the trigger.
	 *
	 * @param array{mixed[]} $trigger
	 *
	 * @return int
	 */
	public static function get_args_count( $trigger ) {

		return absint( $trigger['meta']['ADD_ACTION_ARGS_COUNT'] ?? 0 );

	}

	/**
	 * Identifies the tokens types.
	 *
	 * @param mixed[] $trigger
	 * @param mixed[] $args
	 *
	 * @deprecated 6.0
	 *
	 * @return void
	 */
	public static function identify_tokens_types( $trigger, $args ) {

		$tokenized_args = array();

		$count = self::get_args_count( $trigger );

		for ( $i = 0; $i < $count; $i++ ) {

			if ( ! isset( $args[ $i ] ) ) {
				continue;
			}

			$label = 'arg_' . ( $i + 1 );

			if ( ! is_scalar( $args[ $i ] ) ) {
				$tokenized_args[ $label ] = (array) json_decode( wp_json_encode( $args[ $i ] ), true );
			} else {
				$tokenized_args[ $label ] = $args[ $i ];
			}
		}

		$array_flattener = new Array_Flattener();
		$array_flattener->flatten_array( $tokenized_args );

	}

	/**
	 * Formats the token value.
	 *
	 * @param mixed $mixed
	 *
	 * @return mixed - Returns the value if its scalar. Otherwise, JSON string.
	 */
	public static function format_token_value( $mixed ) {

		if ( is_scalar( $mixed ) ) {

			if ( is_bool( $mixed ) ) {
				return true === $mixed ? 'true' : 'false';
			}

			return $mixed;

		}

		return wp_json_encode( $mixed );

	}

}
