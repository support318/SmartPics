<?php

namespace Uncanny_Automator_Pro;

/**
 * Webhook_Static_Content
 */
class Webhook_Static_Content {

	/**
	 * Anonymous JS function invoked as callback when clicking
	 * the custom button "Send test". The JS function requires
	 * the JS module "modal". Make sure it's included in
	 * the "modules" array
	 *
	 * @return string The JS code, with or without the <script> tags
	 */
	public static function get_samples_js() {
		// Start output
		ob_start();

		// It's optional to add the <script> tags
		// This must have only one anonymous function
		?>

		<script>

			// Do when the user clicks on send test
			function ($button, data, modules) {
				// Create a configuration object
				let config = {
					// In milliseconds, the time between each call
					timeBetweenCalls: 1000,
					// In milliseconds, the time we're going to check for samples
					checkingTime: 60 * 1000,
					// Links
					links: {
						noResultsSupport: "<?php echo esc_url_raw( Utilities::utm_parameters( 'https://automatorplugin.com/knowledge-base/webhook-triggers/', 'no_samples', 'get_help_link' ) ); ?>",
					},
					// i18n
					i18n: {
						checkingHooks: "<?php /* translators: Time in seconds */ printf( esc_attr__( "We're checking for a new hook. We'll keep trying for %1\$s seconds.", 'uncanny-automator-pro' ), '{{time}}' ); ?>",
						noResultsTrouble: "<?php esc_attr_e( 'We had trouble finding a sample.', 'uncanny-automator-pro' ); ?>",
						noResultsSupport: "<?php esc_attr_e( 'See more details or get help', 'uncanny-automator-pro' ); ?>",
						samplesModalTitle: "<?php esc_attr_e( "Here is the data we've collected", 'quickbooks-training' ); ?>",
						samplesModalWarning: "<?php /* translators: Confirmation button */ printf( esc_attr__( 'Clicking on \"%1$s\" will remove your current fields and will use the ones on the table above instead.', 'uncanny-automator-pro' ), '{{confirmButton}}' ); ?>", //phpcs:ignore WordPress.WP.I18n.MissingTranslatorsComment
						samplesTableValueType: "<?php esc_attr_e( 'Value type', 'uncanny-automator-pro' ); ?>",
						samplesTableReceivedData: "<?php esc_attr_e( 'Received data', 'uncanny-automator-pro' ); ?>",
						samplesModalButtonConfirm: "<?php esc_attr_e( 'Use these fields', 'uncanny-automator-pro' ); ?>",
						samplesModalButtonCancel: "<?php esc_attr_e( 'Do nothing', 'uncanny-automator-pro' ); ?>",
						tokens: {
							search: "<?php esc_attr_e( 'Search tokens', 'uncanny-automator-pro' ); ?>",
							noResults: "<?php esc_attr_e( 'No tokens found', 'uncanny-automator-pro' ); ?>",
							noResultsDescriptionWithFilter: "<?php esc_attr_e( 'Try searching again or disabling the token type filter.', 'uncanny-automator-pro' ); ?>",
							noResultsDescription: "<?php esc_attr_e( 'Try searching again.', 'uncanny-automator-pro' ); ?>",
							tokenType: {
								text: "<?php esc_attr_x( 'Text', 'Token', 'uncanny-automator-pro' ); ?>",
								email: "<?php esc_attr_x( 'Email', 'Token', 'uncanny-automator-pro' ); ?>",
								url: "<?php esc_attr_x( 'URL', 'Token', 'uncanny-automator-pro' ); ?>",
								float: "<?php esc_attr_x( 'Float', 'Token', 'uncanny-automator-pro' ); ?>",
								int: "<?php esc_attr_x( 'Integer', 'Token', 'uncanny-automator-pro' ); ?>",
								date: "<?php esc_attr_x( 'Date', 'Token', 'uncanny-automator-pro' ); ?>",
								time: "<?php esc_attr_x( 'Time', 'Token', 'uncanny-automator-pro' ); ?>"
							}
						}
					}
				}

				// Get the date when this function started
				let startDate = new Date();

				// Create array with the data we're going to send
				let dataToBeSent = {
					action: 'get_samples_get_webhook_url',
					nonce: UncannyAutomator._site.rest.nonce,
					recipe_id: UncannyAutomator._recipe.recipe_id,
					item_id: data.item.id,
					webhook_url: data.values.WEBHOOK_URL,
					data_format: data.values.DATA_FORMAT,
					called_from_common: 'yes'
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
						url: ajaxurl,
						data: dataToBeSent,
						// Set the checking time as the timeout
						timeout: config.checkingTime,
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

							if ( ! response.succes && response.error ) {
								// Change the notice type
								$notice.removeClass('item-options__notice--warning').addClass('item-options__notice--error');

								// Add error message.
								$notice.html(response.error + ' ');

								// Add help link
								let $noticeHelpLink = jQuery('<a/>', {
										target: '_blank',
										href: config.links.noResultsSupport
									}).text(config.i18n.noResultsSupport);
									$notice.append($noticeHelpLink);

								$button.removeClass('uap-btn--loading uap-btn--disabled');

								return;
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
									// Invoke this function again
									getSamples();
								}, config.timeBetweenCalls);
							} else {
								// Add loading animation to the button
								$button.removeClass('uap-btn--loading uap-btn--disabled');

								// Check if it has results
								if (foundResults) {
									// Remove notice
									$notice.remove();

									// Iterate samples and create an array with the rows
									let rows = [];
									let keys = {}
									jQuery.each(response.samples, function (index, sample) {
										// Iterate keys
										jQuery.each(sample, function (index, row) {
											// Check if we already added this key
											if (typeof keys[row.key] !== 'undefined') {
											} else {
												// Add row and save the index
												keys[row.key] = rows.push(row);
											}
										});
									});

									// Create table with the sample data
									let $sample = jQuery('<div><table><tbody></tbody></table></div>');


									// Get the body of the $sample table
									let $sampleBody = $sample.find('tbody');

									// Iterate the received sample and add rows
									jQuery.each(rows, function (index, row) {
										// Create row
										let $row = jQuery('<tr><td class="SAMPLE_WEBHOOK-sample-table-td-key">' + row.key + '</td><td>' + config.i18n.tokens.tokenType[row.type] + '</td><td class="SAMPLE_WEBHOOK-sample-table-td-data">' + row.data + '</td></tr>');

										// Append row
										$sampleBody.append($row);
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
											// Get the field with the fields (WEBHOOK_DATA)
											let webhookFields = findWebhookField(data.item.options.WEBHOOK_DATA.fields); // Making it dynamic

											// Remove all the current fields
											webhookFields.fieldRows = [];

											// Add new rows. Iterate rows from the sample
											jQuery.each(rows, function (index, row) {
												// Add row
												webhookFields.addRow({
													KEY: row.key,
													VALUE_TYPE: row.type,
													SAMPLE_VALUE: row.data
												}, false);
											});

											// Render again
											webhookFields.reRender();

											// Destroy modal
											modal.destroy();
										},
									});
								} else {
									// Change the notice type
									$notice.removeClass('item-options__notice--warning').addClass('item-options__notice--error');

									// Create a new notice message
									let noticeMessage = config.i18n.noResultsTrouble;

									// Change the notice message
									$notice.html(noticeMessage + ' ');

									// Add help link
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

						fail: function (response) {
						}
					});
				}

				// Add loading animation to the button
				$button.addClass('uap-btn--loading uap-btn--disabled');

				// Try to get samples
				getSamples();

				function findWebhookField(fields, targetOptionCode = 'WEBHOOK_FIELDS'){
					for (let i = 0; i < fields.length; i++) {
						if (fields[i].attributes.optionCode === targetOptionCode) {
							return fields[i];
						}
					}
					return -1; // Return -1 if no matching optionCode is found
				}
			}

		</script>

		<?php

		// Get output
		// Return output
		return ob_get_clean();
	}

	/**
	 * A piece of CSS that it's added only when this item
	 * is on the recipe
	 *
	 * @return string The CSS, with the CSS tags
	 */
	public static function inline_css() {
		// Start output
		ob_start();

		?>

		<style>

			.SAMPLE_WEBHOOK-sample-table-td-key {
				color: #1b92e5 !important;
				font-weight: 500 !important;
			}

			.SAMPLE_WEBHOOK-sample-table-td-data {
				color: #616161 !important;
				font-style: italic !important;
			}

		</style>

		<?php

		// Get output
		// Return output
		return ob_get_clean();
	}
}
