<?php

namespace Uncanny_Automator_Pro;

/**
 * Class BDB_SENDONSCREENNOTIFICATION
 *
 * @package Uncanny_Automator_Pro
 */
class BDB_SENDONSCREENNOTIFICATION {

	/**
	 * Integration code
	 *
	 * @var string
	 */
	public static $integration = 'BDB';

	/**
	 * Property action_code.
	 *
	 * @var string
	 */
	private $action_code = 'BDBSENDONSCREEN';

	/**
	 * Property action_meta.
	 *
	 * @var string
	 */
	private $action_meta = 'BDBONSCREENNOTIFICATION';

	/**
	 * SetAutomatorTriggers constructor.
	 */
	public function __construct() {

		$this->define_action();

	}

	/**
	 * Define and register the action by pushing it into the Automator object.
	 */
	public function define_action() {

		$action = array(
			'author'             => Automator()->get_author_name(),
			'support_link'       => Automator()->get_author_support_link( $this->action_code, 'integration/buddyboss/' ),
			'is_pro'             => true,
			'integration'        => self::$integration,
			'code'               => $this->action_code,
			/* translators: Action - BuddyBoss */
			'sentence'           => sprintf( esc_attr__( 'Show {{an on-screen notification:%1$s}} to the user', 'uncanny-automator-pro' ), $this->action_meta ),
			/* translators: Action - BuddyBoss */
			'select_option_name' => esc_attr__( 'Show {{an on-screen notification}} to the user', 'uncanny-automator-pro' ),
			'execution_function' => array( $this, 'defer_action_wp_footer' ),
			'options_callback'   => array( $this, 'load_options' ),
		);

		Automator()->register->action( $action );
	}

	/**
	 * @return array
	 */
	public function load_options() {

		return Automator()->utilities->keep_order_of_options(
			array(
				'options_group' => array(
					$this->action_meta => array(
						Automator()->helpers->recipe->field->text_field( 'BDBNOTIFICATIONCONTENT', esc_attr__( 'Notification content', 'uncanny-automator-pro' ), true, 'textarea' ),
						Automator()->helpers->recipe->field->text_field( 'BDBNOTIFICATIONLINK', esc_attr__( 'Notification link', 'uncanny-automator-pro' ), true, 'text', '', false ),
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
	 *
	 * @return void
	 */
	public function defer_action_wp_footer( $user_id, $action_data, $recipe_id, $args ) {

		// Add the notification in the background.
		// @since 4.5
		if ( is_admin() || wp_doing_cron() ) {
			$this->show_notification( $user_id, $action_data, $recipe_id, $args );
			return;
		}
		// Otherwise, defer in wp_footer.
		add_action(
			'wp_footer',
			function() use ( $user_id, $action_data, $recipe_id, $args ) {
				$this->show_notification( $user_id, $action_data, $recipe_id, $args );
				?>
				<script>
				document.addEventListener("DOMContentLoaded", function() {
					if ( typeof wp !== 'undefined' && typeof wp.heartbeat !== 'undefined'  ) {
						wp.heartbeat.connectNow();
					} else {
						console.warn('Automator: WordPress Heartbeat API is not found, loaded, or enabled.');
					}
				});
				</script>
				<?php
			}
		);

	}

	/**
	 * Send notification to user
	 *
	 * @param string $user_id
	 * @param array $action_data
	 * @param string $recipe_id
	 *
	 * @return void
	 *
	 * @since 1.1
	 */
	public function show_notification( $user_id, $action_data, $recipe_id, $args ) {

		$notification_content = Automator()->parse->text( $action_data['meta']['BDBNOTIFICATIONCONTENT'], $recipe_id, $user_id, $args );

		$notification_content = do_shortcode( $notification_content );

		$notification_link = Automator()->parse->text( $action_data['meta']['BDBNOTIFICATIONLINK'], $recipe_id, $user_id, $args );

		$notification_link = do_shortcode( $notification_link );

		// Attempt to send notification.
		if ( ! function_exists( 'bp_notifications_add_notification' ) ) {

			Automator()->complete_action( $user_id, $action_data, $recipe_id, __( 'BuddyBoss message module is not active.', 'uncanny-automator-pro' ) );

			return;

		}

		$notification_id = bp_notifications_add_notification(
			array(
				'user_id'           => $user_id,
				'item_id'           => $action_data['ID'],
				'secondary_item_id' => $user_id,
				'component_name'    => 'uncanny-automator',
				'component_action'  => 'uncannyautomator_bdb_notification',
				'date_notified'     => bp_core_current_time(),
				'is_new'            => 1,
				'allow_duplicate'   => true,
			)
		);

		if ( is_wp_error( $notification_id ) ) {
			$action_data['complete_with_errors'] = true;
			Automator()->complete_action( $user_id, $action_data, $recipe_id, $notification_id->get_error_message() );
			return;
		}

		// Add the link.
		if ( ! empty( $notification_link ) ) {
			$notification_content = '<a href="' . esc_attr( esc_url( $notification_link ) ) . '" title="' . esc_attr( wp_strip_all_tags( $notification_content ) ) . '">' . ( $notification_content ) . '</a>';
		}

		// Adding meta for notification display on front-end.
		bp_notifications_update_meta( $notification_id, 'uo_notification_content', $notification_content );

		bp_notifications_update_meta( $notification_id, 'uo_notification_link', $notification_link );

		Automator()->complete_action( $user_id, $action_data, $recipe_id );

	}

}
