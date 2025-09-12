<?php
/**
 * Including the view file and passing variables to avoid undefined variable warning
 * @var array $tab
 * @var array $recipes
 * @var array $triggers
 * @var array $actions
 * @var array $action_statuses
 * @var array $users
 * @var bool $view_exists
 */

$post_type    = automator_filter_has_var( 'post_type' ) ? esc_attr( automator_filter_input( 'post_type' ) ) : 'uo-recipe';
$form_action  = admin_url( 'edit.php' ) . '?post_type=uo-recipe';
$search_query = automator_filter_has_var( 'search_key' ) ? esc_attr( automator_filter_input( 'search_key' ) ) : '';

?>
<div class="uap">

	<div class="uap-report">

		<form class="uap-report-filters" method="GET" action="<?php echo $form_action; ?>">

			<input type="hidden" name="page" value="uncanny-automator-<?php echo esc_attr( $tab ); ?>">

			<input type="hidden" name="post_type" value="uo-recipe">

			<div class="uap-report-filters-content">

				<div class="uap-report-filters-left">
					<?php
					/**
					 * Filter by Recipe Name
					 * This is one is going to be global, we're going to show it in all the logs
					 */
					?>
					<div class="uap-report-filters-filter">

						<select name="recipe_id" id="recipe_id_filter">

							<option value=""><?php esc_html_e( 'All recipes', 'uncanny-automator-pro' ); ?></option>

							<?php

							if ( $recipes ) {

								foreach ( $recipes as $recipe ) {

									$recipe_title = sprintf( __( 'ID: %1$s (no title)', 'uncanny-automator' ), $recipe['id'] );

									if ( ! empty( $recipe['recipe_title'] ) ) {

										$recipe_title = sprintf( '%1$s (%2$s)', $recipe['recipe_title'], ucfirst( $recipe['recipe_status'] ) );

									}
									?>
									<option <?php selected( absint( $recipe['id'] ), absint( automator_filter_input( 'recipe_id' ) ), true ); ?>
										value="<?php echo absint( $recipe['id'] ); ?>">

										<?php echo esc_html( $recipe_title ); ?>

									</option>
									<?php
								}
							}

							?>
						</select>
					</div>

					<?php

					/**
					 * Triggers-only filter
					 * Filter by Trigger Name
					 */
					if ( 'trigger-log' === $tab ) {
						?>

						<div class="uap-report-filters-filter">
							<select name="trigger_id" id="trigger_id_filter">
								<option value=""><?php _e( 'All triggers', 'uncanny-automator-pro' ); ?></option>

								<?php

								if ( $triggers ) {
									foreach ( $triggers as $trigger ) {
										if ( automator_filter_has_var( 'trigger_id' ) && absint( automator_filter_input( 'trigger_id' ) ) === absint( $trigger['id'] ) ) {
											?>

											<option value="<?php echo absint( $trigger['id'] ); ?>" selected="selected">
												<?php echo ! empty( $trigger['trigger_title'] ) ? $trigger['trigger_title'] : sprintf( __( 'Trigger deleted: %1$s', 'uncanny-automator' ), $trigger['id'] ); ?>
											</option>

											<?php
										} else {
											?>

											<option value="<?php echo absint( $trigger['id'] ); ?>">
												<?php echo ! empty( $trigger['trigger_title'] ) ? $trigger['trigger_title'] : sprintf( __( 'Trigger deleted: %1$s', 'uncanny-automator' ), $trigger['id'] ); ?>
											</option>

											<?php
										}
									}
								}
								?>
							</select>
						</div>

						<?php
					}

					/**
					 * Actions-only filter
					 * Filter by Action name
					 */

					if ( 'action-log' === $tab ) {
						?>

						<div class="uap-report-filters-filter">
							<select name="action_id" id="action_id_filter">
								<option value=""><?php _e( 'All actions', 'uncanny-automator-pro' ); ?></option>

								<?php

								if ( $actions ) {
									foreach ( $actions as $action ) {
										if ( automator_filter_has_var( 'action_id' ) && absint( automator_filter_input( 'action_id' ) ) === absint( $action['id'] ) ) {
											?>

											<option value="<?php echo absint( $action['id'] ); ?>" selected="selected">
												<?php echo ! empty( $action['action_title'] ) ? $action['action_title'] : sprintf( __( 'Action deleted: %1$s', 'uncanny-automator' ), $action['id'] ); ?>
											</option>

											<?php
										} else {
											?>

											<option value="<?php echo absint( $action['id'] ); ?>">
												<?php echo ! empty( $action['action_title'] ) ? $action['action_title'] : sprintf( __( 'Action deleted: %1$s', 'uncanny-automator' ), $action['id'] ); ?>
											</option>

											<?php
										}
									}
								}

								?>
							</select>
						</div>

						<?php
					}

					/**
					 * Filter by Recipe Creator
					 * This is one is going to be global, we're going to show it in all the logs
					 */
					?>

					<div class="uap-report-filters-filter">
						<?php $all_user_selected = 0 === strlen( automator_filter_input( 'user_id' ) ); ?>
						<select name="user_id">
							<option <?php echo $all_user_selected ? 'selected' : ''; ?> value="">
								<?php esc_html_e( 'All users', 'uncanny-automator-pro' ); ?>
							</option>
							<?php if ( $users ) { ?>
								<?php foreach ( $users as $user ) { ?>
									<?php $selected = null; ?>
									<?php if ( false === $all_user_selected && automator_filter_has_var( 'user_id' ) ) { ?>
										<?php $selected = selected( absint( $user['id'] ), absint( automator_filter_input( 'user_id' ) ) ); ?>
									<?php } ?>
									<option
										<?php echo esc_attr( $selected ); ?>
										value="<?php echo esc_attr( $user['id'] ); ?>
									">
										<?php if ( ! empty( $user['title'] ) ) { ?>
											<?php echo esc_html( sprintf( '#%1$d: %2$s (%3$s)', $user['id'], $user['title'], $user['user_email'] ) ); ?>
										<?php } else { ?>
											<?php // Show `Anonymous` for zero user ID. ?>
											<?php if ( 0 === intval( $user['id'] ) ) { ?>
												<?php echo esc_html( sprintf( __( 'Anonymous', 'uncanny-automator-pro' ), $user['id'] ) ); ?>
											<?php } else { ?>
												<?php echo esc_html( sprintf( __( 'ID: %1$s', 'uncanny-automator-pro' ), $user['id'] ) ); ?>
											<?php } ?>
										<?php } ?>
									</option>
								<?php } ?>
							<?php } ?>
						</select>
					</div>

					<?php

					/**
					 * Filter by Recipe's completion date
					 * This is one is going to be global, we're going to show it in all the logs
					 */

					?>

					<div class="uap-report-filters-filter">
						<input type="text" name="daterange"
							   placeholder="<?php _e( 'Recipe completion date', 'uncanny-automator-pro' ); ?>"
							   class="daterange"
							   value="<?php echo automator_filter_has_var( 'daterange' ) ? esc_attr( automator_filter_input( 'daterange' ) ) : ''; ?>">
					</div>

					<?php

					/**
					 * Triggers-only filter
					 * Filter by Trigger's completion date
					 */

					if ( 'trigger-log' === $tab ) {
						?>

						<div class="uap-report-filters-filter">
							<input type="text" name="trigger_daterange"
								   placeholder="<?php _e( 'Trigger completion date', 'uncanny-automator-pro' ); ?>"
								   class="daterange"
								   value="<?php echo automator_filter_has_var( 'trigger_daterange' ) ? esc_attr( automator_filter_input( 'trigger_daterange' ) ) : ''; ?>">
						</div>

						<?php
					}

					?>

					<?php

					/**
					 * Actions-only filter
					 * Filter by Action's completion date
					 */

					if ( 'action-log' === $tab ) {
						?>

						<div class="uap-report-filters-filter">
							<input
								type="text"
								name="action_daterange"
								placeholder="<?php _e( 'Action completion date', 'uncanny-automator-pro' ); ?>"
								class="daterange"
								value="<?php echo automator_filter_has_var( 'action_daterange' ) ? esc_attr( automator_filter_input( 'action_daterange' ) ) : ''; ?>">
						</div>

						<?php if ( ! empty( $action_statuses ) ) { ?>
							<div class="uap-report-filters-filter">
								<select name="action_completed">
									<option value="">
										<?php esc_html_e( 'All statuses', 'uncanny_automator' ); ?>
									</option>
									<?php foreach ( $action_statuses as $status ) { ?>
										<?php $action_completed = $status['action_completed']; ?>
										<?php if ( '0' === $action_completed ) { ?>
											<?php // Do make exception for zero type because it evaluate to empty. ?>
											<?php $action_completed = 'not_completed'; ?>
										<?php } ?>
										<option <?php selected( automator_filter_input( 'action_completed' ), $action_completed ); ?>
											value="<?php echo esc_attr( $action_completed ); ?>">
											<?php echo esc_html( \Uncanny_Automator_Pro\Utilities::get_action_completed_label( $status['action_completed'] ) ); // Use the actual status_completed value in array. ?>
										</option>
									<?php } ?>
								</select>
							</div>
						<?php } ?>

						<?php
					}

					if ( 'api-log' === $tab ) {
						?>

						<div class="uap-report-filters-filter">
							<input type="text" name="daterange"
								   placeholder="<?php _e( 'Completion date', 'uncanny-automator-pro' ); ?>"
								   class="daterange"
								   value="<?php echo automator_filter_has_var( 'daterange' ) ? esc_attr( automator_filter_input( 'daterange' ) ) : ''; ?>">
						</div>

						<?php if ( ! empty( $api_statuses ) ) { ?>
							<div class="uap-report-filters-filter">
								<select name="completed">
									<option
										value=""><?php echo esc_html__( 'All statuses', 'uncanny_automator' ); ?></option>
									<?php foreach ( $api_statuses as $status ) { ?>
										<?php $completed = $status['completed']; ?>
										<?php if ( '0' === $completed ) { ?>
											<?php // Do make exception for zero type because it evaluate to empty. ?>
											<?php $completed = 'not_completed'; ?>
										<?php } ?>
										<option <?php selected( automator_filter_input( 'completed' ), $completed ); ?>
											value="<?php echo esc_attr( $completed ); ?>">
											<?php echo esc_html( \Uncanny_Automator\Automator_Status::name( $status['completed'] ) ); // Use the actual status_completed value in array. ?>
										</option>
									<?php } ?>
								</select>
							</div>
						<?php } ?>

						<?php
					}

					?>

					<input type="submit" name="filter_action" class="button"
						   value="<?php _e( 'Filter', 'uncanny-automator-pro' ); ?>">

				</div>

				<div class="uap-report-filters-right">
					<div class="uap-report-filters-search">
						<input
							type="text"
							name="search_key"
							value="<?php echo esc_attr( $search_query ); ?>"
							class="uap-report-filters-search__field"
						/>
						<input
							type="submit"
							name="filter_action"
							value="<?php esc_html_e( 'Search', 'uncanny-automator-pro' ); ?>"
							class="button uap-report-filters-search__submit"
						/>
					</div>
				</div>

			</div>

		</form>

	</div>

</div>
