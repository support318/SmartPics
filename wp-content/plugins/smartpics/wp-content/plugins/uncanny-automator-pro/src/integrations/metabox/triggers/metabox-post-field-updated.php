<?php // phpcs:ignore WordPress.Files.FileName.InvalidClassFileName
namespace Uncanny_Automator_Pro;

use Uncanny_Automator\Recipe;

/**
 * Class METABOX_POST_FIELD_UPDATED
 *
 * @package  Uncanny_Automator_Pro
 * @uses \Uncanny_Automator\Recipe\Triggers Trait.
 */
class METABOX_POST_FIELD_UPDATED {

	use Recipe\Triggers;

	/**
	 * Trigger code.
	 *
	 * @var string
	 */
	const TRIGGER_CODE = 'METABOX_POST_FIELD_UPDATED';

	/**
	 * Trigger meta.
	 *
	 * @var string
	 */
	const TRIGGER_META = 'METABOX_POST_FIELD_UPDATED_META';

	/**
	 * Prop:metabox.
	 *
	 * @property Metabox_Helpers_Pro $metabox
	 */
	public $metabox;

	/**
	 * Prop:metabox_tokens.
	 *
	 * @property Metabox_Helpers_Pro $metabox_tokens
	 */
	public $metabox_tokens;

	public function __construct() {

		$this->metabox        = new Metabox_Helpers_Pro( false );
		$this->metabox_tokens = new Metabox_Tokens_Pro();

		$this->setup_trigger();

	}

	/**
	 * Method setup_trigger.
	 *
	 * @return void
	 */
	public function setup_trigger() {

		$this->set_integration( 'METABOX' );

		$this->set_trigger_code( self::TRIGGER_CODE );

		$this->set_trigger_meta( self::TRIGGER_META );

		$this->set_trigger_type( 'anonymous' );

		$this->set_is_pro( true );

		$this->set_is_login_required( false );

		$this->set_sentence(
			sprintf(
			/* Translators: Trigger sentence */
				esc_html__( '{{A field:%1$s}} is updated on {{a post:%2$s}}', 'uncanny-automator-pro' ),
				$this->get_trigger_meta(),
				'POST_ID:' . $this->get_trigger_meta()
			)
		);

		$this->set_readable_sentence(
			/* Translators: Trigger sentence */
			esc_html__( '{{A field}} is updated on {{a post}}', 'uncanny-automator-pro' )
		);

		$this->add_action( array( 'added_post_meta', 'updated_post_meta' ) );

		//@see https://developer.wordpress.org/reference/hooks/added_post_meta/
		//@see https://developer.wordpress.org/reference/hooks/updated_meta_type_meta/
		$this->set_action_args_count( 4 );

		$this->set_options_callback( array( $this, 'load_options' ) );

		if ( method_exists( $this, 'set_tokens' ) ) {
			$this->set_tokens( $this->metabox_tokens->common_tokens() );
		}

		$this->register_trigger();

	}

	/**
	 * Method load_options.
	 *
	 * @return array
	 */
	public function load_options() {

		return $this->metabox->get_field_options( $this->get_trigger_meta(), true );

	}

	/**
	 * Method prepare_to_run.
	 *
	 * @param $data
	 *
	 * @return void
	 */
	public function prepare_to_run( $data ) {

		$this->set_conditional_trigger( true );

	}

	/**
	 * Method validate_trigger.
	 *
	 * @param ...$args
	 *
	 * @return bool
	 */
	public function validate_trigger( ...$args ) {

		return $this->metabox->validate_trigger( $args[0] );

	}

	/**
	 * Method validate_contions.
	 *
	 * @param ...$args
	 *
	 * @return array
	 */
	protected function validate_conditions( ...$args ) {

		list( $meta_id, $object_id, $meta_key, $_meta_value ) = $args[0];

		$this->actual_where_values = array(); // Fix for when not using the latest Trigger_Recipe_Filters version. Newer integration can omit this line.

		return $this->find_all( $this->trigger_recipes() )
				->where( array( $this->get_trigger_meta(), 'POST_ID', 'POST_TYPE' ) )
				->match( array( $meta_key, $object_id, get_post_type( $object_id ) ) )
				->format( array( 'trim', 'intval' ) )
				->get();

	}

	/**
	 * Method do_continue_anon_trigger.
	 *
	 * @param ...$args
	 *
	 * @return bool
	 */
	public function do_continue_anon_trigger( ...$args ) {

		return true;

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

		return $this->metabox_tokens->hydrate_tokens( $parsed, $args, $trigger );

	}
}
