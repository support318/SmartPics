<?php // phpcs:ignore WordPress.Files.FileName.InvalidClassFileName
namespace Uncanny_Automator_Pro;

use Uncanny_Automator\Recipe;

/**
 * Class USER_JETENGINE_POST_FIELD_UPDATED
 *
 * @package Uncanny_Automator
 */
class USER_JETENGINE_POST_FIELD_UPDATED {

	use Recipe\Triggers;

	/**
	 * Trigger code.
	 *
	 * @var string
	 */
	const TRIGGER_CODE = 'USER_JETENGINE_POST_FIELD_UPDATED';

	/**
	 * Trigger meta.
	 *
	 * @var string
	 */
	const TRIGGER_META = 'USER_JETENGINE_POST_FIELD_UPDATED_META';

	/**
	 * The helper object.
	 *
	 * @var JetEngine_Helpers $helper The helper object.
	 */
	protected $helper;

	/**
	 * The JetEngine tokens.
	 *
	 * @var JetEngine_Tokens
	 */
	public $jetengine_tokens = null;

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
					'A user updates {{a specific JetEngine field:%1$s}} on {{a specific post type:%2$s}}',
					'uncanny-automator-pro'
				),
				$this->get_trigger_meta(),
				'POST_TYPE:' . $this->get_trigger_meta()
			)
		);

		$this->set_readable_sentence(
			/* Translators: Trigger sentence */
			esc_html__(
				'A user updates {{a specific JetEngine field}} on {{a specific post type}}',
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
							'label'           => esc_html__( 'Field', 'uncanny-automator' ),
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

		// Bail out if value didn't change or if meta value is empty.
		if ( 'added_post_meta' === current_action() ) {

			if ( empty( $meta_value ) ) {
				return false;
			}

			$field = $this->helper->get_post_field( $meta_key );

			$field = array_shift( $field );

			$field_default_value = isset( $field['default_val'] ) ? $field['default_val'] : '';

			// Bail if no changes detected for normal fields.
			if ( $meta_value === $field_default_value ) {
				return false;
			}

			$options_checked = array();
			// Handle array values.
			if ( ! empty( $field['options'] ) ) {
				foreach ( $field['options'] as $option ) {
					if ( true === $option['is_checked'] ) {
						$options_checked[] = $option['key'];
					}
				}
			}

			// Only use loose comparison because input can have different order.
			if ( $options_checked == $meta_value ) { // phpcs:ignore WordPress.PHP.StrictComparisons.LooseComparison
				return false;
			}
		}

		// Supports both Metabox field and CPT fields.
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

		$result = $this->find_all( $this->trigger_recipes() )
			->where( array( $this->get_trigger_meta(), 'POST_TYPE' ) )
			->match( array( $meta_key, get_post_type( $object_id ) ) )
			->format( array( 'trim', 'trim' ) )
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
