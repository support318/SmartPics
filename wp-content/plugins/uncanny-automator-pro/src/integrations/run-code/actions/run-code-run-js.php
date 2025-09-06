<?php

namespace Uncanny_Automator_Pro;

/**
 *
 */
class RUN_CODE_RUN_JS {

	use \Uncanny_Automator\Recipe\Actions;

	/**
	 * Constant ACTION_CODE.
	 *
	 * @var string
	 */
	const ACTION_CODE = 'UOA_RUN_JS';

	/**
	 * Constant ACTION_META.
	 *
	 * @var string
	 */
	const ACTION_META = 'UOA_RUN_JS_META';

	/**
	 * Constant PRIORITY.
	 *
	 * @var integer
	 */
	const PRIORITY = 10;

	/**
	 * Constant ACCEPTED_NUM_ARGS.
	 *
	 * @var integer
	 */
	const ACCEPTED_NUM_ARGS = 1;

	/**
	 *
	 */
	public function __construct() {

		$this->setup_action();

		add_action( 'automator_before_process_action', array( $this, 'prepare_filter' ), 10, 4 );

	}

	/**
	 * @param $user_id
	 * @param $action_data
	 * @param $recipe_id
	 * @param $args
	 *
	 * @return void
	 */
	public function prepare_filter( $user_id, $action_data, $recipe_id, $args ) {

		if ( 'UOA_RUN_JS' === $action_data['meta']['code'] ) {
			// Remove the qoutes and double qoutes.
			add_filter(
				'automator_maybe_parse_replaceable',
				function( $replaceable ) {
					return str_replace( array( '”', "'" ), '', $replaceable );
				},
				self::PRIORITY,
				self::ACCEPTED_NUM_ARGS
			);
		}

	}

	/**
	 * @return void
	 */
	protected function setup_action() {

		$this->set_integration( 'RUN_CODE' );

		$this->set_is_pro( true );

		$this->set_wpautop( false );

		$this->set_action_meta( self::ACTION_META );

		$this->set_action_code( self::ACTION_CODE );

		$this->set_requires_user( false );

		$this->set_support_link( 'https://automatorplugin.com/knowledge-base/run-a-javascript-code/' );

		/* translators: Action - WordPress */
		$this->set_sentence( sprintf( esc_attr__( 'Run {{JavaScript code:%1$s}}', 'uncanny-automator-pro' ), $this->get_action_meta() ) );

		/* translators: Action - WordPress */
		$this->set_readable_sentence( esc_attr__( 'Run {{JavaScript code}}', 'uncanny-automator-pro' ) );

		$this->set_options_callback( array( $this, 'load_options' ) );

		$this->register_action();

	}

	/**
	 * @return array
	 */
	public function load_options() {

		return Automator()->utilities->keep_order_of_options(
			array(
				'options_group' => array(
					$this->get_action_meta() => array(
						array(
							'input_type'  => 'select',
							'option_code' => $this->get_action_meta() . '_POSITION',
							'label'       => esc_html__( 'Where should the code be output?', 'uncanny-automator-pro' ),
							'required'    => true,
							'options'     => array(
								'head'   => esc_html__( 'head', 'uncanny-automator-pro' ),
								'body'   => esc_html__( 'body', 'uncanny-automator-pro' ),
								'footer' => esc_html__( 'footer', 'uncanny-automator-pro' ),
							),
						),
						array(
							'input_type'  => 'textarea',
							'option_code' => $this->get_action_meta() . '_CODE',
							'label'       => esc_html__( 'JavaScript code', 'uncanny-automator-pro' ),
							'description' => __( 'Enter your JavaScript code here. Do not include opening and closing script tags.', 'uncanny-automator-pro' ),
							'required'    => true,
						),
					),
				),
			)
		);
	}

	/**
	 * @param $user_id
	 * @param $action_data
	 * @param $recipe_id
	 * @param $args
	 * @param $parsed
	 *
	 * @return void
	 */
	protected function process_action( $user_id, $action_data, $recipe_id, $args, $parsed ) {

		$action_hooks = array(
			'head'   => 'wp_head',
			'body'   => 'wp_body_open',
			'footer' => 'wp_footer',
		);

		$position = $parsed[ $this->get_action_meta() . '_POSITION' ];

		if ( empty( $action_hooks[ $position ] ) ) {

			$action_data['complete_with_errors'] = true;

			Automator()->complete->action( $user_id, $action_data, $recipe_id, esc_html__( 'Invalid code position argument', 'uncanny-automator-pro' ) );

			return;

		}

		add_action(
			$action_hooks[ $position ],
			function() use ( $parsed, $recipe_id ) { ?>
				<script id="automator-action-<?php echo esc_attr( $recipe_id ); ?>">
					// The user can run any JS code.
					<?php echo $parsed[ $this->get_action_meta() . '_CODE' ]; //phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped ?>
				</script>
				<?php
			},
			999
		);

		Automator()->complete->action( $user_id, $action_data, $recipe_id );

	}

}
