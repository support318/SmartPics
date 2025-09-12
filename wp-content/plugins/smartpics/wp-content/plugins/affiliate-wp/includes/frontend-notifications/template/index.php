<?php
/**
 * Frontend Notifications Template
 *
 * @since 2.23.0
 *
 * @author Andrew Munro <amunro@awesomemotive.com>
 *
 * @package AffiliateWP
 */

?>
<template id="affwp-notification">
	<style>
		#notification {
			z-index: 9999;
			opacity: 0;
			transform: translateY(1rem);
			transition: transform 0.3s ease-out, opacity 0.3s ease-out;
		}

		@keyframes slideIn {
			from {
				transform: translateY(1rem);
				opacity: 0;
			}
			to {
				transform: translateY(0);
				opacity: 1;
			}
		}

		@keyframes slideOut {
			from {
				transform: translateY(0);
				opacity: 1;
			}
			to {
				transform: translateY(1rem);
				opacity: 0;
			}
		}
	</style>

	<!-- #notification -->
	<div id="notification" aria-live="assertive" class="mt pointer-events-none fixed inset-0 flex items-end px-4 py-6 sm:items-start sm:p-6<?php echo is_admin_bar_showing() ? ' mt-8' : ''; ?>">

		<!-- Container 1 -->
		<div class="flex w-full flex-col items-center space-y-4 sm:items-end">
			<div class="pointer-events-auto w-full max-w-sm overflow-hidden rounded-lg bg-white dark:bg-slate-800 shadow-lg ring-1 ring-black ring-opacity-5"><!-- Container 2 -->

				<!-- Container 3 -->
				<div class="p-4">

					<!-- items-start -->
					<div class="flex items-start">

						<!-- ✔︎ Icon -->
						<div class="flex-shrink-0">
							<svg class="h-6 w-6 text-green-500" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor" aria-hidden="true">
								<path stroke-linecap="round" stroke-linejoin="round" d="M9 12.75L11.25 15 15 9.75M21 12a9 9 0 11-18 0 9 9 0 0118 0z" />
							</svg>
						</div>

						<!-- Text -->
						<div class="ml-3 w-0 flex-1 pt-0.5">
							<p class="text-sm font-medium text-gray-900 dark:text-white"><?php esc_html_e( 'Discount Applied Successfully!', 'affiliate-wp' ); ?></p>
							<p class="mt-1 text-sm text-gray-500 dark:text-slate-400"><?php esc_html_e( 'Your savings have been added to the cart.', 'affiliate-wp' ); ?></p>
						</div>

						<!-- × Icon -->
						<div class="ml-4 flex flex-shrink-0">
							<button id="close-notification" type="button" class="inline-flex rounded-md bg-white dark:bg-slate-800 text-gray-400 dark:text-slate-300 hover:text-gray-500 dark:hover:text-slate-400 focus:outline-none focus:ring-2 focus:ring-indigo-500 focus:ring-offset-2">
								<span class="sr-only"><?php esc_html_e( 'Close', 'affiliate-wp' ); ?></span>
								<svg class="h-5 w-5" viewBox="0 0 20 20" fill="currentColor" aria-hidden="true">
									<path d="M6.28 5.22a.75.75 0 00-1.06 1.06L8.94 10l-3.72 3.72a.75.75 0 101.06 1.06L10 11.06l3.72 3.72a.75.75 0 101.06-1.06L11.06 10l3.72-3.72a.75.75 0 00-1.06-1.06L10 8.94 6.28 5.22z" />
								</svg>
							</button>
						</div>

					</div> <!-- items-start -->
				</div> <!-- Container 3 -->
			</div> <!-- Container 2 -->
		</div> <!-- Container 1 -->
	</div><!-- #notification -->

</template>
