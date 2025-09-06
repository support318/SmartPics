<?php // phpcs:ignore WordPress.Files.FileName.InvalidClassFileName
namespace Uncanny_Automator_Pro;

use Uncanny_Automator\Recipe;

/**
 * Class USER_JETENGINE_POST_FIELD_UPDATED_TO
 *
 * @package Uncanny_Automator
 */
class USER_JETENGINE_POST_FIELD_UPDATED_TO {

	use Recipe\Triggers;

	/**
	 * Trigger code.
	 *
	 * @var string
	 */
	const TRIGGER_CODE = 'USER_JETENGINE_POST_FIELD_UPDATED_TO';

	/**
	 * Trigger meta.
	 *
	 * @var string
	 */
	const TRIGGER_META = 'USER_JETENGINE_POST_FIELD_UPDATED_TO_META';

	/**
	 * The JetEngine tokens.
	 *
	 * @var JetEngine_Tokens
	 */
	protected $jetengine_tokens = null;

	/**
	 * The helper object.
	 *
	 * @var \JetEngine_Helpers $helper The helper object.
	 */
	protected $helper;

	public function __construct() {

		$this->helper = new JetEngine_Helpers( false );

		$this->jetengine_tokens = new JetEngine_Tokens();

		$this->setup_trigger();

	}

	/**
	 * Define and register the trigger by pushing it into the Automator object
	 */
	public function setup_trigger() {

		$this->set_integration( 'JETENGINE' );

		$this->set_trigger_code( self::TRIGGER_CODE );

		$this->set_trigger_meta( self::TRIGGER_META );

		$this->set_is_pro( true );

		// The action hook to attach this trigger into.
		$this->add_action( array( 'added_post_meta', 'updated_post_meta' ), 10, 4 );

		$this->set_sentence(
			sprintf(
				/* Translators: Trigger sentence */
				esc_html__(
					'A user updates {{a specific JetEngine field:%1$s}} on {{a specific post type:%2$s}} to {{a specific value:%3$s}}',
					'uncanny-automator-pro'
				),
				$this->get_trigger_meta(),
				'POST_TYPE:' . $this->get_trigger_meta(),
				'VALUE:' . $this->get_trigger_meta()
			)
		);

		$this->set_readable_sentence(
			/* Translators: Trigger sentence */
			esc_html__(
				'A user updates {{a specific JetEngine field}} on {{a specific post type}} to {{a specific value}}',
				'uncanny-automator-pro'
			)
		);

		$this->set_options_callback( array( $this, 'load_options' ) );

		$this->set_tokens( $this->jetengine_tokens->common_tokens() );

		$this->register_trigger();

	}

	public function load_options() {

		return Automator()->utilities->keep_order_of_options(
			array(
				'options_group' => array(
					$this->get_trigger_meta() => array(
						Automator()->helpers->recipe->wp->options->all_wp_post_types(
							__( 'Post type', 'uncanny-automator-pro' ),
							'POST_TYPE',
							array(
								'token'        => false,
								'is_any'       => false,
								'is_ajax'      => true,
								'target_field' => $this->get_trigger_meta(),
								'endpoint'     => 'automator_jetengine_get_post_field_by_post_type',
							)
						),
						array(
							'option_code'     => $this->get_trigger_meta(),
							'input_type'      => 'select',
							'label'           => esc_html__( 'Field', 'uncanny-automator-pro' ),
							'required'        => true,
							'relevant_tokens' => array(),
						),
						array(
							'option_code'     => 'VALUE',
							'input_type'      => 'text',
							'label'           => esc_html__( 'Value', 'uncanny-automator-pro' ),
							'required'        => true,
							'relevant_tokens' => array(),
						),
					),
				),
			)
		);

	}

	/**
	 * Validate the trigger.
	 *
	 * Return false if returned booking data is empty.
	 */
	public function validate_trigger( ...$args ) {

		list ( $meta_id, $object_id, $meta_key, $meta_value ) = $args[0];

		// The method get_post_field will return a non-empty value if any field's name is equals to provided $meta_key.
		// Supports JetEngine's CPT Fields.
		return ! empty( $this->helper->get_post_field( $meta_key ) ) || $this->helper->is_cpt_field( $object_id, $meta_key );

	}

	/**
	 * Prepare to run.
	 *
	 * Sets the conditional trigger to true.
	 *
	 * @return void.
	 */
	public function prepare_to_run( $data ) {

		$this->set_conditional_trigger( true );

	}

	public function validate_conditions( $args ) {

		list( $meta_id, $object_id, $meta_key, $meta_value ) = $args;

		if ( is_array( $meta_value ) ) {
			$meta_value = maybe_serialize( $meta_value );
		}

		$result = $this->find_all( $this->trigger_recipes() )
			->where( array( $this->get_trigger_meta(), 'POST_TYPE', 'VALUE' ) )
			->match( array( $meta_key, get_post_type( $object_id ), $meta_value ) )
			->format( array( 'trim', 'trim', 'trim' ) )
			->get();

		return $result;

	}

	/**
	 * Method parse_additional_tokens.
	 *
	 * @param $parsed
	 * @param $args
	 * @param $trigger
	 *
	 * @return array
	 */
	public function parse_additional_tokens( $parsed, $args, $trigger ) {

		return $this->jetengine_tokens->hydrate_tokens( $parsed, $args, $trigger );

	}

}
