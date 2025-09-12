<?php // phpcs:ignore WordPress.Files.FileName.InvalidClassFileName

namespace Uncanny_Automator_Pro;

/**
 * Class LD_GROUPLEADERADDEDGROUP
 *
 * @package Uncanny_Automator_Pro
 */
class LD_GROUPLEADERADDEDGROUP {

	/**
	 * Integration code
	 *
	 * @var string
	 */
	public static $integration = 'LD';

	private $trigger_code;
	private $trigger_meta;

	/**
	 * Set up Automator trigger constructor.
	 */
	public function __construct() {
		$this->trigger_code = 'LD_GROUPLEADERADDEDGROUP';
		$this->trigger_meta = 'LDGROUPLEADER';
		$this->define_trigger();
	}

	/**
	 * Define and register the trigger by pushing it into the Automator object
	 */
	public function define_trigger() {

		$trigger = array(
			'author'              => Automator()->get_author_name( $this->trigger_code ),
			'support_link'        => Automator()->get_author_support_link( $this->trigger_code, 'integration/learndash/' ),
			'is_pro'              => true,
			'integration'         => self::$integration,
			'code'                => $this->trigger_code,
			/* translators: Logged-in trigger - LearnDash */
			'sentence'            => sprintf( __( 'A Group Leader is added to {{a group:%1$s}}', 'uncanny-automator-pro' ), $this->trigger_meta ),
			/* translators: Logged-in trigger - LearnDash */
			'select_option_name'  => __( 'A Group Leader is added to {{a group}}', 'uncanny-automator-pro' ),
			'action'              => 'ld_added_leader_group_access',
			'priority'            => 10,
			'accepted_args'       => 2,
			'validation_function' => array(
				$this,
				'group_added_group_leader',
			),
			'options_callback'    => array( $this, 'load_options' ),
		);

		Automator()->register->trigger( $trigger );

	}

	/**
	 * @return array
	 */
	public function load_options() {
		$options_array = array(
			'options' => array(
				Automator()->helpers->recipe->learndash->options->all_ld_groups( null, $this->trigger_meta ),
			),
		);

		return Automator()->utilities->keep_order_of_options( $options_array );
	}

	/**
	 * Validation function when the trigger action is hit
	 *
	 * @param $user_id
	 * @param $group_id
	 */
	public function group_added_group_leader( $user_id, $group_id ) {

		if ( empty( $group_id ) || empty( $user_id ) ) {
			return;
		}

		$pass_args = array(
			'code'         => $this->trigger_code,
			'meta'         => $this->trigger_meta,
			'post_id'      => $group_id,
			'user_id'      => $user_id,
			'is_signed_in' => true,
		);

		$args = Automator()->maybe_add_trigger_entry( $pass_args, false );

		if ( $args ) {
			foreach ( $args as $result ) {
				if ( true === $result['result'] ) {

					$trigger_meta = array(
						'user_id'        => $user_id,
						'trigger_id'     => $result['args']['trigger_id'],
						'trigger_log_id' => $result['args']['trigger_log_id'],
						'run_number'     => $result['args']['run_number'],
					);

					// Group leader ID
					$trigger_meta['meta_key']   = 'GROUP_LEADER_ID';
					$trigger_meta['meta_value'] = $user_id;
					Automator()->insert_trigger_meta( $trigger_meta );

					$user                       = get_user_by( 'id', $user_id );
					$trigger_meta['meta_key']   = 'GROUP_LEADER_NAME';
					$trigger_meta['meta_value'] = sprintf( '%s %s', $user->first_name, $user->last_name );
					Automator()->insert_trigger_meta( $trigger_meta );

					$trigger_meta['meta_key']   = 'GROUP_LEADER_EMAIL';
					$trigger_meta['meta_value'] = $user->user_email;
					Automator()->insert_trigger_meta( $trigger_meta );

					Automator()->maybe_trigger_complete( $result['args'] );
				}
			}
		}
	}
}
