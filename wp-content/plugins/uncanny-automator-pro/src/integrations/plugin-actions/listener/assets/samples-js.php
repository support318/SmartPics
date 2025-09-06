<?php
use Uncanny_Automator_Pro\Utilities;
?>
<script>
// Do when the user clicks on send test.
function ($button, data, modules) {

	// Create a configuration object.
	const config = {
		// In milliseconds, the time between each call.
		timeBetweenCalls: 1000,
		// In milliseconds, the time we're going to check for samples.
		checkingTime: 180 * 1000,
		// Links
		links: {
			noResultsSupport: "<?php echo esc_url_raw( Utilities::utm_parameters( 'https://automatorplugin.com/knowledge-base/plugin-actions/', 'no_samples', 'get_help_link' ) ); ?>",
		},
		// i18n
		i18n: {
			checkingHooks: `
				<?php
					/* translators: Time in seconds */
					printf( esc_attr__( "We're capturing token data. Trigger the hook while logged-in as administrator, and we'll keep trying for %1\$s seconds.", 'uncanny-automator-pro' ), '{{time}}' );
				?>
			`,
			noResultsTrouble: "<?php esc_attr_e( 'We had trouble capturing tokens data.', 'uncanny-automator-pro' ); ?>",
			noResultsSupport: "<?php esc_attr_e( 'See more details or get help', 'uncanny-automator-pro' ); ?>",
			samplesModalTitle: "<?php esc_attr_e( "Here is the tokens data we've captured", 'uncanny-automator-pro' ); ?>",
			samplesModalWarning: `
					<?php
					/* translators: Confirmation button */
					printf(
						esc_attr__(
							"Clicking %1\$s will replace any previously saved tokens from this trigger's sample data. If you've captured tokens data before, review all actions using those tokens to ensure they still work correctly.",
							'uncanny-automator-pro'
						),
						'{{confirmButton}}'
					);
					?>
				`,
			samplesTableValueType: "<?php esc_attr_e( 'Value type', 'uncanny-automator-pro' ); ?>",
			samplesTableReceivedData: "<?php esc_attr_e( 'Captured tokens data', 'uncanny-automator-pro' ); ?>",
			samplesModalButtonConfirm: "<?php esc_attr_e( 'Use these tokens', 'uncanny-automator-pro' ); ?>",
			samplesModalButtonCancel: "<?php esc_attr_e( 'Do nothing', 'uncanny-automator-pro' ); ?>",
		}
	}


	/**
	 * Saves the trigger tokens.
	 *
	 * @return void
	 */
	const triggerSaveTokens = ( tokens ) => {

		console.log( JSON.stringify(tokens) );

		jQuery.ajax({
			method: 'POST',
			dataType: 'json',
			url: UncannyAutomator._site.rest.url + '/run-code-wp-hook-save-trigger-tokens',
			data: {
				'tokens': JSON.stringify(tokens),
				'trigger_id': data.item.id
			},
			// Set the checking time as the timeout
			timeout: config.checkingTime,
			headers: {
				'X-WP-Nonce': UncannyAutomator._site.rest.nonce
			},
			success: function( response ) {
				location.reload();
			},
			error: function( err, msg ) {
				console.log( err );
				console.log( msg );
			}
		});

	};

	// Get the date when this function started.
	let startDate = new Date();

	// Create array with the data we're going to send.
	let dataToBeSent = {
		item_id: data.item.id,
		recipe_id: UncannyAutomator._recipe.recipe_id,
		request_type: 'get-samples'
	};


	// Add notice to the item
	// Create notice
	let $notice = jQuery('<div/>', {
		'class': 'item-options__notice item-options__notice--warning'
	});

	// Add notice message
	$notice.html(config.i18n.checkingHooks.replace('{{time}}', parseInt(config.checkingTime / 1000)));

	// Get the notices container
	let $noticesContainer = jQuery('.item[data-id="' + data.item.id + '"] .item-options__notices');

	// Add notice
	$noticesContainer.html($notice);

	// Create the function we're going to use recursively to
	// do check for the samples
	var getSamples = function () {

		// Do AJAX call
		jQuery.ajax({
			method: 'POST',
			dataType: 'json',
			url: UncannyAutomator._site.rest.url + '/run-code-wp-hook',
			data: dataToBeSent,
			// Set the checking time as the timeout
			timeout: config.checkingTime,
			headers: {
				'X-WP-Nonce': UncannyAutomator._site.rest.nonce
			},
			success: function (response) {

				// Get new date
				let currentDate = new Date();

				// Define the default value of foundResults
				let foundResults = false;

				// Check if the response was successful
				if (response.success) {
					// Check if we got the rows from a sample
					if (response.samples.length > 0) {
						// Update foundResults
						foundResults = true;
					}
				}

				// Check if we have to do another call
				let shouldDoAnotherCall = false;

				// First, check if we don't have results
				if (!foundResults) {
					// Check if we still have time left
					if ((currentDate.getTime() - startDate.getTime()) <= config.checkingTime) {
						// Update result
						shouldDoAnotherCall = true;
					}
				}

				if (shouldDoAnotherCall) {
					// Wait and do another call
					setTimeout(function () {
						// Invoke this function again.
						getSamples();
					}, config.timeBetweenCalls);
				} else {
					// Add loading animation to the button.
					$button.removeClass('uap-btn--loading uap-btn--disabled');

					// Check if it has results.
					if (foundResults) {
						// Remove notice
						$notice.remove();

						// Create table with the sample data
						let $sample = jQuery('<div><table><tbody></tbody></table></div>');

						// Get the body of the $sample table
						let $sampleBody = $sample.find('tbody');

						let rows = [];

						// Iterate the received sample and add rows.
						jQuery.each(response.samples, function (index, item) {
							// Create row
							let $row = jQuery(`
									<tr>
										<td class="SAMPLE_WEBHOOK-sample-table-td-key">${item.key}</td>
										<td>${item.type}</td>
									</tr>`
								);
							// Append row
							$sampleBody.append($row);
							rows.push(item);
						});

						// Create modal box
						let modal = new modules.Modal({
							title: config.i18n.samplesModalTitle,
							content: $sample.html(),
							warning: config.i18n.samplesModalWarning.replace('{{confirmButton}}', '<strong>' + config.i18n.samplesModalButtonConfirm + '</strong>'),
							buttons: {
								cancel: config.i18n.samplesModalButtonCancel,
								confirm: config.i18n.samplesModalButtonConfirm,
							}
						}, {
							size: 'extra-large'
						});

						// Set modal events
						modal.setEvents({
							onConfirm: function () {
								triggerSaveTokens( rows );
								// Destroy modal
								modal.destroy();
							},
						});
					} else {
						// Change the notice type.
						$notice.removeClass('item-options__notice--warning').addClass('item-options__notice--error');

						// Create a new notice message.
						let noticeMessage = config.i18n.noResultsTrouble;

						// Change the notice message.
						$notice.html(noticeMessage + ' ');

						// Add help link.
						let $noticeHelpLink = jQuery('<a/>', {
							target: '_blank',
							href: config.links.noResultsSupport
						}).text(config.i18n.noResultsSupport);

						$notice.append($noticeHelpLink);
					}
				}
			},

			statusCode: {
				403: function () {
					location.reload();
				}
			},
		});
	}

	// Add loading animation to the button
	$button.addClass('uap-btn--loading uap-btn--disabled');

	// Try to get samples
	getSamples();

}

</script>
