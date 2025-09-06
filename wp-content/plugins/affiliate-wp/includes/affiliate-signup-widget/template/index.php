<form action="" method="post">
	<div class="relative bg-brand-500 rounded-xl overflow-hidden mb-8 w-full">
		<?php
		/**
		 * Full-size image.
		 */
		?>
		<?php if ( $image ) : ?>
		<div id="signup-image" aria-hidden="true" class="absolute inset-0 opacity-25">
			<div aria-hidden="true" class="absolute inset-0 overflow-hidden">
				<img src="<?php echo $image; ?>" alt="" class="h-full w-full object-cover object-center">
			</div>
		</div>
		<?php endif; ?>

		<?php
		/**
		 * Widget content
		 */
		?>
		<div id="widget-content" class="relative mx-auto flex max-w-4xl flex-col items-center px-6 py-12 lg:py-32 text-center">
			<?php
			/**
			 * Initial view
			 */
			?>
			<?php if ( '{affiliateLink}' === $affiliate_link ) : ?>
			<div id="initial-view" class="w-full">
				<h1 class="text-4xl font-bold tracking-tight text-brand-100 lg:text-6xl"><?php echo $heading; ?></h1>
				<p class="mt-4 text-xl text-brand-105 break-words"><?php echo $text; ?></p>
				<button type="submit" id="<?php echo $button_id; ?>" class="relative mt-8 inline-flex items-center rounded-xl border transition ease-in-out duration-150 border-transparent bg-brand-110 px-8 py-3 text-base font-medium text-brand-120 hover:bg-brand-115">
					<svg class="animate-spin -ml-1 mr-3 h-5 w-5" hidden xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24">
						<circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle>
						<path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4zm2 5.291A7.962 7.962 0 014 12H0c0 3.042 1.135 5.824 3 7.938l3-2.647z"></path>
					</svg>
					<span id="originalButtonText"><?php echo $button_text; ?></span>
					<span id="processingText" hidden><?php echo $button_text_loading; ?></span>
				</button>

				<input type="hidden" name="post_id" value="<?php echo get_the_ID(); ?>">
				<?php if ( ! empty( $terms_of_use_page_id ) ) : ?>
				<p class="mt-4 text-sm leading-6 text-brand-125">
					<?php
					printf(
						esc_html( __( 'By joining you agree to our %s.', 'affiliate-wp' ) ),
						sprintf(
							'<a href="%s" class="underline-offset-4 hover:underline-offset-2 text-brand-130 underline hover:underline transition-underline ease-in-out duration-150" target="_blank">%s</a>',
							esc_url( get_permalink( $terms_of_use_page_id ) ),
							esc_html( __( 'Affiliate Terms of Use', 'affiliate-wp' ) )
						)
					);
					?>
				</p>
				<?php endif; ?>
			</div>
			<?php endif; ?>
			<?php
			/**
			 * Confirmation view (hidden by default)
			 */
			?>
			<div id="confirmation-view" class="w-full @container"<?php if ( ! $display_confirmation ) { echo 'style="display: none;"'; } ?>>
				<h1 class="text-4xl font-bold tracking-tight text-brand-200 lg:text-6xl"><?php echo $confirmation_heading; ?></h1>
				<p class="mt-4 text-xl text-brand-205 break-words"><?php echo $confirmation_text; ?></p>
				<div class="max-w-2xl mx-auto bg-brand-210 ring ring-brand-216 text-brand-220 mt-4 p-1 text-xl rounded-lg relative flex flex-col @lg:flex-row items-stretch gap-1">
					<input class="outline-brand-225 outline-offset-0 rounded-md py-2 px-4 bg-transparent border-transparent w-full text-ellipsis overflow-hidden" type="text" value="<?php echo $affiliate_link; ?>" id="affiliateLink" readonly>
					<button type="button" id="copyLinkButton" class="w-full @lg:w-auto @lg:inline-flex items-center rounded-md border transition ease-in-out duration-150 border-transparent px-8 py-3 font-medium text-base text-brand-240 bg-brand-230 hover:bg-brand-235 whitespace-nowrap"><?php echo $button_copy_text; ?></button>
				</div>
			</div>
			<?php
			/**
			 * Error view (hidden by default)
			 */
			?>
			<div id="error-view" class="w-full @container" style="display: none;">
				<h1 class="text-4xl font-bold tracking-tight text-brand-300 lg:text-6xl"><?php echo $error_heading; ?></h1>
				<p id="error-message" class="mt-4 text-xl text-brand-305"><?php echo $error_text; ?></p>
			</div>
		</div>
	</div>
</form>
