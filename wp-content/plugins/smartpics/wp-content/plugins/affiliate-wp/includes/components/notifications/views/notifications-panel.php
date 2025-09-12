<?php
/**
 * Displays a list of notifications.
 *
 * @package     AffiliateWP
 * @subpackage  Components/Notifications/Views
 * @copyright   Copyright (c) 2022, Sandhills Development, LLC
 * @license     http://opensource.org/licenses/gpl-2.0.php GNU Public License
 * @since       2.9.5
 */

?>

<div
	id="affwp-notifications"
	class="affwp-hidden"
	x-data
	x-init="function() { $el.classList.remove( 'affwp-hidden' ) }"
>
	<div
		class="affwp-overlay"
		x-show="$store.affwpNotifications.isPanelOpen"
		x-on:click="$store.affwpNotifications.closePanel()"
	></div>

	<div
		id="affwp-notifications-panel"
		x-show="$store.affwpNotifications.isPanelOpen"
		x-transition:enter-start="affwp-slide-in"
		x-transition:leave-end="affwp-slide-in"
	>
		<div id="affwp-notifications-header" tabindex="-1">
			<h3>
				<?php
				echo wp_kses(
					sprintf(
						/* Translators: %s - number of notifications */
						__( '(%s) New Notifications', 'affiliate-wp' ),
						'<span x-text="$store.affwpNotifications.numberActiveNotifications"></span>'
					),
					array(
						'span' => array(
							'x-text' => true,
						),
					)
				);
				?>
			</h3>

			<button
				type="button"
				class="affwp-close"
				x-on:click="$store.affwpNotifications.closePanel()"
			>
				<span class="dashicons dashicons-no-alt"></span>
				<span class="screen-reader-text"><?php esc_html_e( 'Close panel', 'affiliate-wp' ); ?></span>
			</button>
		</div>

		<div id="affwp-notifications-body">
			<template x-if="$store.affwpNotifications.notificationsLoaded && $store.affwpNotifications.activeNotifications.length">
				<template x-for="(notification, index) in $store.affwpNotifications.activeNotifications" :key="notification.id">
					<div class="affwp-notification">
						<div class="affwp-notification--icon" :class="'affwp-notification--icon-' + notification.type">
							<span class="dashicons" :class="'dashicons-' + notification.icon_name"></span>
						</div>

						<div class="affwp-notification--body">
							<div class="affwp-notification--header">
								<h4 class="affwp-notification--title" x-text="notification.title"></h4>

								<div class="affwp-notification--date" x-text="notification.relative_date"></div>
							</div>

							<div class="affwp-notification--content" x-html="notification.content.replaceAll('\n', '<br>')"></div>

							<div class="affwp-notification--actions">
								<template x-for="button in notification.buttons">
									<a
										:href="button.url"
										:class="button.type === 'primary' ? 'button button-primary' : 'button button-secondary'"
										target="_blank"
										x-text="button.text"
									></a>
								</template>

								<button
									type="button"
									class="affwp-notification--dismiss"
									x-on:click="$store.affwpNotifications.dismiss( $event, index )"
								>
									<?php esc_html_e( 'Dismiss', 'affiliate-wp' ); ?>
								</button>
							</div>
						</div>
					</div>
				</template>
			</template>

			<template x-if="$store.affwpNotifications.notificationsLoaded && ! $store.affwpNotifications.activeNotifications.length">
				<div id="affwp-notifications-none">
					<?php esc_html_e( 'You have no new notifications.', 'affiliate-wp' ); ?>
				</div>
			</template>

			<template x-if="! $store.affwpNotifications.notificationsLoaded">
				<div>
					<?php esc_html_e( 'Loading notifications...', 'affiliate-wp' ); ?>
				</div>
			</template>
		</div>
	</div>
</div>
