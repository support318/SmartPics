<?php

namespace Uncanny_Automator_Pro\Integrations\Schedule;

use Uncanny_Automator_Pro\Integrations\Schedule\Helpers\Schedule_Helpers;

/**
 *
 */
class UO_Recipe_Scheduled_Actions_List_Table extends \WP_List_Table {
	/**
	 * @return void
	 * @throws \Exception
	 */
	public function prepare_items() {
		$columns  = $this->get_columns();
		$hidden   = $this->get_hidden_columns();
		$sortable = $this->get_sortable_columns();

		$this->_column_headers = array( $columns, $hidden, $sortable );
		$this->items           = $this->table_data();
	}

	/**
	 * @return string[]
	 */
	public function get_columns() {
		return array(
			'recipe'        => esc_html_x( 'Recipe', 'Scheduled Recipe', 'uncanny-automator-pro' ),
			'next_schedule' => esc_html_x( 'Next run time', 'Scheduled Recipe', 'uncanny-automator-pro' ),
			'arguments'     => esc_html_x( 'Trigger settings', 'Scheduled Recipe', 'uncanny-automator-pro' ),
			'is_recurring'  => esc_html_x( 'Recurring', 'Scheduled Recipe', 'uncanny-automator-pro' ),
			'repeat_times'  => esc_html_x( 'Repeated', 'Scheduled Recipe', 'uncanny-automator-pro' ),
			'actions'       => esc_html_x( 'Actions', 'Scheduled Recipe', 'uncanny-automator-pro' ),
		);
	}

	/**
	 * @return array
	 */
	public function get_hidden_columns() {
		return array();
	}

	/**
	 * @return array
	 */
	public function get_sortable_columns() {
		return array(
			'next_schedule' => array( 'next_schedule', true ), // true means its default sort order is ASC
		);
	}

	/**
	 * @return array
	 * @throws \Exception
	 */
	private function table_data() {
		$data = array();

		$recipe_filter = automator_filter_has_var( 'recipe_filter' ) ? intval( automator_filter_input( 'recipe_filter' ) ) : 0;
		$sort          = automator_filter_has_var( 'order' ) ? sanitize_text_field( automator_filter_input( 'order' ) ) : 'ASC';
		$scheduled_ids = Schedule_Helpers::get_all_scheduled_actions( $sort );
		//$count         = $this->get_scheduled_times( $scheduled_ids );

		foreach ( $scheduled_ids as $scheduled_id => $scheduled_date ) {
			// Fetch information about each scheduled action based on its return ID

			$schedule = \ActionScheduler::store()->fetch_action( $scheduled_id );
			if ( $schedule ) {
				$hash = $this->get_hash( $schedule->get_hook(), $schedule->get_args() );

				if ( ! isset( $count[ $hash ] ) ) {
					$count[ $hash ] = 1; // Initialize if not exists
				} else {
					$count[ $hash ] ++; // Increase count
				}

				// Get the action arguments
				$args         = $schedule->get_args();
				$display_args = Schedule_Helpers::normalize_args_for_table( $schedule->get_hook(), $args );

				$recipe_id  = $args[1];
				$trigger_id = $args[0];

				if ( 0 !== absint( $recipe_filter ) && absint( $recipe_id ) !== absint( $recipe_filter ) ) {
					continue;
				}

				// Convert to DateTime object
				$date_time = new \DateTime( date( 'Y-m-d H:i:s', $scheduled_date ) );

				// Set DateTime object to WordPress timezone
				$date_time->setTimezone( wp_timezone() );

				$title = get_the_title( $recipe_id );
				$title = ! empty( $title ) ? $title : __( '(no title)', 'uncanny-automator-pro' );
				$title = sprintf( __( '(ID: %1$d) <a href="%2$s" target="_blank">%3$s</a>', 'uncanny-automator-pro' ), $recipe_id, admin_url( 'post.php?post=' . $recipe_id . '&action=edit' ), $title );

				$repeat_until = get_post_meta( $trigger_id, 'REPEAT_TIMES', true );

				// Check for custom value
				if ( 'automator_custom_value' === $repeat_until ) {
					$repeat_until = get_post_meta( $trigger_id, 'REPEAT_TIMES_custom', true );
				}

				// 300 is the default value for Until cancelled
				if ( 300 === (int) $repeat_until ) {
					$repeat_until = '-1';
				}

				$is_recurring = intval( '-1' ) === intval( $repeat_until ) ? esc_html_x( 'Yes', 'Scheduled Recipe', 'uncanny-automator-pro' ) : esc_html_x( 'No', 'Scheduled Recipe', 'uncanny-automator-pro' );

				// Unset Trigger ID
				unset( $args[0] );

				// Unset Recipe ID
				unset( $args[1] );

				foreach ( $args as $key => $value ) {
					if ( intval( '-1' ) === intval( $value ) ) {
						$args[ $key ] = esc_html_x( 'Until cancelled', 'Scheduled Recipe', 'uncanny-automator-pro' );
					}
				}

				if ( 'Yes' === $is_recurring ) {
					$unschedule_msg = esc_html_x( 'Are you sure you want to cancel all future runs?', 'Scheduled Recipe', 'uncanny-automator-pro' );
				} else {
					$unschedule_msg = esc_html_x( 'Are you sure you want to cancel this run?', 'Scheduled Recipe', 'uncanny-automator-pro' );
				}

				$schedule_msg = esc_html_x( 'Are you sure you want to run this recipe now?', 'Scheduled Recipe', 'uncanny-automator-pro' );

				$actions = array(
					'<a href="' . wp_nonce_url( admin_url( 'admin-post.php?action=run_now&schedule_id=' . $scheduled_id ), 'run_now_' . $scheduled_id ) . '" class="button primary" onclick="return confirm(\'' . $schedule_msg . '\');">' . esc_html_x( 'Run now', 'Scheduled Recipe', 'uncanny-automator-pro' ) . '</a>',
					'<a href="' . wp_nonce_url( admin_url( 'admin-post.php?action=cancel_schedule&schedule_id=' . $scheduled_id ), 'cancel_schedule_' . $scheduled_id ) . '" class="button secondary" onclick="return confirm(\'' . $unschedule_msg . '\');">' . esc_html_x( 'Cancel', 'Scheduled Recipe', 'uncanny-automator-pro' ) . '</a>',
				);

				$data[] = array(
					'recipe'        => empty( $title ) ? __( '(no title)', 'uncanny-automator-pro' ) : $title,
					'next_schedule' => sprintf( '%s', $date_time->format( get_option( 'date_format' ) . ' ' . get_option( 'time_format' ) ) ),
					'arguments'     => implode( ' ', $display_args ),
					'is_recurring'  => $is_recurring,
					'repeat_times'  => 'No' === $is_recurring ? $count[ $hash ] : '-',
					'actions'       => join( ' ', $actions ),
				);
			}
		}

		return $data;
	}

	/**
	 * @param $scheduled_ids
	 *
	 * @return array
	 */
	public function get_scheduled_times( $scheduled_ids ) {
		$count = array();
		foreach ( $scheduled_ids as $scheduled_id => $scheduled_date ) {
			$action = \ActionScheduler::store()->fetch_action( $scheduled_id );
			$hash   = $this->get_hash( $action->get_hook(), $action->get_args() );
			// Increase the count for this hash
			if ( ! isset( $count[ $hash ] ) ) {
				$count[ $hash ] = 1; // Initialize if not exists
			} else {
				$count[ $hash ] ++; // Increase count
			}
		}

		return $count;
	}

	/**
	 * @param $hook
	 * @param $args
	 *
	 * @return string
	 */
	public function get_hash( $hook, $args ) {
		$args = implode( ',', $args );

		return md5( $hook . $args );
	}

	/**
	 * @param $item
	 * @param $column_name
	 *
	 * @return bool|mixed|string|void
	 */
	public function column_default( $item, $column_name ) {
		switch ( $column_name ) {
			case 'recipe':
			case 'next_schedule':
			case 'arguments':
			case 'is_recurring':
			case 'repeat_times':
			case 'actions':
				return $item[ $column_name ];
			default:
				return print_r( $item, true );
		}
	}

	/**
	 * @return void
	 */
	public function recipes_dropdown() {
		Admin_Helper::uo_recipe_dropdown();
	}

	/**
	 * @param $which
	 *
	 * @return void
	 */
	public function extra_tablenav( $which ) {
		if ( 'top' === $which ) {
			?>
			<form method="get">
				<input type="hidden" name="page" value="<?php echo esc_attr( automator_filter_input( 'page' ) ); ?>"/>
				<input type="hidden" name="post_type"
					   value="<?php echo esc_attr( automator_filter_input( 'post_type' ) ); ?>"/>
				<?php
				$this->recipes_dropdown();
				submit_button( __( 'Filter' ), 'secondary', 'filter_action', false, array( 'id' => 'post-query-submit' ) );
				?>
			</form>
			<?php
		}
	}
}

$uo_recipe_table = new UO_Recipe_Scheduled_Actions_List_Table();
$uo_recipe_table->prepare_items();
?>
	<div class="wrap">
		<h2><?php esc_html_e( 'Pending Scheduled Recipes', 'uncanny-automator-pro' ); ?></h2>
		<?php $uo_recipe_table->display(); ?>
	</div>
<?php
