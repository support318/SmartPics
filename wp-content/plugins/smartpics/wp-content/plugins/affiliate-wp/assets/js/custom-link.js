window.affWPCustomLink = window.affWPCustomLink || ( function( $ ) {

	/**
	 * Back-end vars.
	 *
	 * @since 2.14.0
	 *
	 * @type {object}
	 */
	const vars = affWpCustomLinksVars;

	/**
	 * jQuery elements.
	 *
	 * @since 2.14.0
	 *
	 * @type {object}
	 */
	let el = {}

	/**
	 * Public functions and properties.
	 *
	 * @since 2.14.0
	 *
	 * @type {object}
	 */
	const app = {

		/**
		 * The current custom link.
		 *
		 * @since 2.14.0
		 *
		 * @type {object}
		 */
		customLink: {},

		/**
		 * Notices object.
		 *
		 * @since 2.14.0
		 *
		 * @type {object}
		 */
		notices: null,

		/**
		 * Notices message timeout.
		 *
		 * @since 2.14.0
		 *
		 * @type int
		 */
		noticesTimeout: 3000,

		/**
		 * Initiate.
		 *
		 * @since 2.14.0
		 */
		init: function() {
			$( app.ready );
		},

		/**
		 * Set elements and events.
		 *
		 * @since 2.14.0
		 */
		ready: function() {
			el = {
				$url: $( '#affwp-url' ),
				$campaign: $( '#affwp-campaign' ),
				$campaignBtn: $( '#affwp-generator-toggle-campaign' ),
				$form: $( '#affwp-custom-link-generator' ),
				$table: $( '#affwp-custom-links-table' ),
				$notices: $( '#affwp-generator-submit-notices' ),
				$submitBtn: $( '#affwp-generator-submit-btn' ),
			}

			app.events();
		},

		/**
		 * Set jQuery events.
		 *
		 * @since 2.14.0
		 */
		events: function() {

			// Bind events.
			el.$form.on( 'submit', app.onSave );
			el.$table.on( 'click', '.affwp-custom-link', app.onCopy );
			el.$table.on( 'click', '.affwp-edit-custom-link', app.onEdit );
			el.$campaign.on( 'focusout', app.cleanCampaignWhitespaces );
			el.$campaignBtn.on( 'click', app.onClickCampaignBtn );

			// Initialize all tooltips.
			app.initializeSubmissionNotices();
			app.updateTooltips();
		},

		/**
		 * Initializes the tooltip notices object and make it available through the app.
		 *
		 * @since 2.14.0
		 */
		initializeSubmissionNotices: function() {
			app.notices = tippy( el.$notices.get( 0 ), {
				content: '',
				trigger: 'manual',
				placement: 'top',
				animation: 'fade',
				hideOnClick: false,
			});
		},

		/**
		 * Initialize action tooltips not initialized yet.
		 *
		 * @since 2.14.0
		 */
		updateTooltips: function() {

			// Initialize URL copy tooltip.
			app.initializeButtons(
				'.affwp-tooltip-url-copy:not(.affwp-tooltip-initialized)',
				'click',
				vars.i18n.copied,
				1000
			);

			// Initialize copy button tooltip.
			app.initializeButtons(
				'.affwp-tooltip-button-copy:not(.affwp-tooltip-initialized)',
				'mouseenter focus',
				vars.i18n.copied,
				1000,
				vars.i18n.copy_affiliate_link,
				true
			);

			// Initialize edit button tooltip.
			app.initializeButtons(
				'.affwp-tooltip-edit:not(.affwp-tooltip-initialized)',
				'mouseenter focus',
				vars.i18n.edit_affiliate_link,
				0
			);

		},

		/**
		 * Initialize tooltip buttons.
		 *
		 * @since 2.14.0
		 *
		 * @param className
		 * @param trigger
		 * @param content
		 * @param hideDelay
		 * @param resetContent
		 * @param manualTrigger
		 */
		initializeButtons: function(
			className,
			trigger,
			content,
			hideDelay,
			resetContent = null,
			manualTrigger = false
		) {
			const buttons = document.querySelectorAll( className );
			buttons.forEach(( button ) => {
				const instance = tippy( button, {
					trigger: trigger,
					duration: [300, 250],
					hideOnClick: false,
					onCreate: ( instance ) => {
						instance.reference.classList.add( 'affwp-tooltip-initialized' );
					}
				} );

				button.addEventListener(
					'click',
					() => app.handleButtonClick( instance, content, hideDelay, resetContent, manualTrigger )
				);
			} );
		},

		/**
		 * Handle tooltip click buttons.
		 *
		 * @since 2.14.0
		 *
		 * @param instance
		 * @param content
		 * @param hideDelay
		 * @param resetContent
		 * @param manualTrigger
		 */
		handleButtonClick: function(
			instance,
			content,
			hideDelay,
			resetContent = null,
			manualTrigger = false
		) {
			instance.setContent( content );
			instance.show();

			if ( manualTrigger ) {
				instance.setProps( { trigger: 'manual' } );
			}

			setTimeout(
				() => {
					instance.hide();
					if ( manualTrigger ) {
						instance.setProps( { trigger: 'mouseenter focus' } );
					}
				},
				hideDelay
			);

			if ( resetContent ) {
				instance.setProps( {
					onHidden: () => {
						instance.setContent( resetContent );
						instance.setProps( { onHidden: null } );
					},
				} );
			}
		},

		/**
		 * Remove whitespaces from campaign name.
		 *
		 * @since 2.14.0
		 */
		cleanCampaignWhitespaces: function() {
			el.$campaign.val( el.$campaign.val().replace( /\s/g, '' ) );
		},

		/**
		 * Runs every time the copy link is clicked.
		 *
		 * @since 2.14.0
		 */
		onCopy: function( event ) {
			/**
			 * Prevent default behavior of hyperlinks.
			 *
			 * @since 2.14.0
			 */
			event.preventDefault();

			app.copyToClipboard( $( this ).closest( 'tr' ).data( 'custom-link-id' ) );
		},

		/**
		 * Runs every time the edit link is clicked.
		 *
		 * @since 2.14.0
		 */
		onEdit: function( event ) {
			/**
			 * Prevent default behavior of hyperlinks.
			 *
			 * @since 2.14.0
			 */
			event.preventDefault();

			// We use data- attributes on row to store the custom link data to be edited.
			const $row = $( this ).closest( 'tr' );

			// Parse custom link.
			const url       = new URL( $row.data( 'custom-link' ) );
			const urlParams = new URLSearchParams( url.search );
			const campaign  = urlParams.get( 'campaign' );

			// Set the link into the url field.
			el.$url.val( $row.data( 'destination-link' ) ).focus();

			// Set the custom link ID which will be submitted on save too.
			el.$form.find( '.affwp-custom-link-id' ).val( $row.data( 'custom-link-id' ) );

			// Updates the button label.
			el.$submitBtn.val( vars.i18n.custom_link_btn_update );

			// Set the campaign field to it default state.
			app.hideCampaign( '' );

			if ( ! campaign ) {
				return;
			}

			app.showCampaign( campaign );
		},

		/**
		 * Runs every time a link is submitted.
		 *
		 * @since 2.14.0
		 */
		onSave: function( event ) {
			/**
			 * Prevent form of default submission behavior.
			 *
			 * @since 2.14.0
			 */
			event.preventDefault();

			// Query form data, so it can be accessible through app.customLink object.
			app.queryCustomLink();

			// Get or URL object.
			const url = app.parseUrl( app.customLink.URL );

			// Test if is a valid URL.
			if ( url === null ) {
				app.showNotice( vars.i18n.invalid_url );
				return;
			}

			// Perform the AJAX call trying to save the custom link.
			app.save(
				url.toString(),
				app.customLink.campaign,
				app.customLink.ID
			);
		},

		/**
		 * Return the row HTML with populated data.
		 *
		 * @since 2.14.0
		 *
		 * @param fields Array of key|values to replace within the HTML.
		 * @returns {string} The table row HTML with populated data.
		 */
		getTableRowHtml: function( fields ) {
			// Copy the row HTML template.
			let tableRowHtml = vars.template;

			// Replace all the {{var}} found in the HTML template.
			Object.entries( fields ).forEach( ( [key, value] ) => {
				tableRowHtml = tableRowHtml.replace( new RegExp(`{{\\b${key}\\b}}`, 'g' ), value )
			} );

			return tableRowHtml;
		},

		/**
		 * Send the AJAX request to save (add|update) the custom link.
		 *
		 * @since 2.14.0
		 *
		 * @param url URL string.
		 * @param campaign Campaign name.
		 * @param customLinkId Optional Custom Link ID. Default 0 (create a new custom link)
		 */
		save: function( url, campaign = '', customLinkId = 0 ) {

			// We expect a valid url string.
			if ( typeof url !== 'string' || ! app.isValidUrlString( url ) ) {
				console.error( 'Can not save. You must supply a valid url string.' );
				return;
			}

			// Perform the AJAX request.
			$.ajax( {
				type: 'POST',
				url: vars.ajax_url,
				data: {
					action: 'affwp_save_custom_link',
					nonce: vars.nonce,
					url: url,
					campaign: campaign,
					custom_link_id: customLinkId
				},
				dataType: 'json',
				beforeSend: function() {
					// Prevent form editions during the request process.
					app.lockForm();

					// Display the saving notice.
					app.showNotice( vars.i18n.saving, 'success', 0 );
				},
				success: function( response ){
					// Failed.
					if ( ! response.success ) {
						app.showNotice( response.data.message );
						return; // Bail early on failures.
					}

					// Update the customLink object with the new ID so it can be accessible through app object.
					app.customLink.ID = response.data.fields.ID;

					// Ensure the table is visible. It can be hidden if no custom links were added yet.
					el.$table.removeClass( 'affwp-hidden' );

					// Create the table row jQuery object.
					const $tableRow = $( app.getTableRowHtml( response.data.fields ) );

					// Data was saved successfully.
					if ( response.data.updated ) {
						// Update the edited row with the new data.
						el.$table.find( `tbody tr[data-custom-link-id="${app.customLink.ID}"]` )
							.replaceWith( $tableRow );

						// We do not want the button enabled again before the tooltip goes away.
						setTimeout( function(){
							app.resetForm();
						}, app.noticesTimeout );
					} else {
						// Insert the new row as first row.
						el.$table.find( 'tbody' ).prepend( $tableRow );

						// Reset form to the initial state.
						app.resetForm();
					}

					// Initialize new/updated row tooltips.
					app.updateTooltips();

					// Try to copy the url to the clipboard and display the success message.
					if ( navigator && navigator.clipboard ) {
						navigator.clipboard.writeText( $tableRow.data( 'custom-link' ) )
							.then( () => {
								// Display alternative success message with the copied text.
								app.showNotice(
									response.data.updated
										? vars.i18n.successfully_updated_and_copied
										: vars.i18n.successfully_created_and_copied,
									'success'
								);
							} )
							.catch( () => {
								// Copy failed, display only the success message.
								app.showNotice( response.data.message, 'success' );
							} );
						return;
					}

					// Display the success message in other cases.
					app.showNotice( response.data.message, 'success' );

				},
				complete: function( response ) {

					if (
						response.responseJSON &&
						response.responseJSON.data &&
						response.responseJSON.data.updated
					) {
						return; // Updated items have a "cool down" period before enabling the button again.
					}

					// Unlock form enabling new requests.
					app.unlockForm();
				}
			} ).fail( function( response ) {
				app.showNotice( vars.i18n.invalid_request );
			} );
		},

		/**
		 * Lock all form fields preventing edition.
		 *
		 * @since 2.14.0
		 */
		lockForm: function() {
			el.$campaign.attr( 'disabled', true );
			el.$url.attr( 'disabled', true );
			el.$submitBtn.attr( 'disabled', true );
		},

		/**
		 * Unlock form enabling edition again.
		 *
		 * @since 2.14.0
		 */
		unlockForm: function() {
			el.$campaign.attr( 'disabled', false );
			el.$url.attr( 'disabled', false );
			el.$submitBtn.attr( 'disabled', false );
		},

		/**
		 * Reset form to the initial state.
		 *
		 * @since 2.14.0
		 */
		resetForm: function() {
			el.$form.find( '.affwp-custom-link-id' ).val( 0 );

			el.$submitBtn.val( vars.i18n.custom_link_btn_create );

			el.$url.val( '' );

			app.unlockForm();

			app.hideCampaign( '' );
		},

		/**
		 * Query custom link data from form and set to the global custom link object.
		 *
		 * @since 2.14.0
		 */
		queryCustomLink: function() {
			app.customLink = {
				ID: parseInt( el.$form.find( 'input[type="hidden"].affwp-custom-link-id' ).val() ),
				affiliateID: parseInt( el.$form.find( 'input[type="hidden"].affwp-affiliate-id' ).val() ),
				URL: el.$url.val().toString(),
				campaign: el.$campaign.val().toString(),
			};
		},

		/**
		 * Copy a url to the clipboard and optionally display a small tooltip to the user.
		 *
		 * @since 2.14.0
		 * @param customLinkID The custom link ID to be copied.
		 */
		copyToClipboard: function( customLinkID ) {
			// The row to be copied.
			const $row = el.$table.find( `tbody tr[data-custom-link-id="${customLinkID}"]` );

			if ( ! $row.length ) {
				return; // Do nothing, invalid row.
			}

			// Copy button jQuery object.
			const $copyBtn = $row.find( '.affwp-copy-custom-link' );

			if ( $copyBtn.hasClass( 'copied' ) ) {
				return; // Copy animation in progress, prevent multiple clicks.
			}

			if ( navigator && navigator.clipboard ) {
				navigator.clipboard.writeText( $row.data( 'custom-link' ) );
			}
		},

		/**
		 * Display notices to the user.
		 *
		 * @since 2.14.0
		 *
		 * @param message The message to display.
		 * @param type The message type: `success` or `error`
		 * @param timeout Will be removed after x milliseconds. Use 0 to never remove.
		 */
		showNotice: function( message, type = 'error', timeout = null ) {

			timeout = typeof timeout === 'number'
				? parseInt( timeout )
				: parseInt( app.noticesTimeout );

			app.notices.setContent( message );
			app.notices.show();

			// Only hide if a timeout is greater than 0.
			if ( timeout > 0 ) {
				setTimeout( function() {
					app.notices.hide();
				}, timeout );
			}
		},

		/**
		 * Show campaign field.
		 *
		 * @since 2.14.0
		 * @param content Optional content to be filled in the field. Default to null, so does nothing.
		 */
		showCampaign: function( content = null ) {

			if ( typeof content === 'string' ) {
				el.$campaign.val( content );
			}

			// Hides the Add campaign text link.
			el.$form.find( '.affwp-generator-campaign-text-link-wrap' ).addClass( 'affwp-hidden' );

			// Shows the campaign input field.
			el.$form.find( '.affwp-campaign-wrap' ).removeClass( 'affwp-hidden' );

		},

		/**
		 * Hide campaign field.
		 *
		 * @since 2.14.0
		 * @param content Optional content to be filled in the field. Default to null, so does nothing.
		 */
		hideCampaign: function( content = null ) {

			if ( typeof content === 'string' ) {
				el.$campaign.val( content );
			}

			// Hides the campaign input field.
			el.$form.find( '.affwp-campaign-wrap' ).addClass( 'affwp-hidden' );

			// Shows the Add campaign text link.
			el.$form.find( '.affwp-generator-campaign-text-link-wrap' ).removeClass( 'affwp-hidden' );

		},

		/**
		 * Show campaign field on button click.
		 *
		 * @since 2.14.0
		 */
		onClickCampaignBtn: function( event ) {
			/**
			 * Prevent default behavior of hyperlinks.
			 *
			 * @since 2.14.0
			 */
			event.preventDefault();

			// Clear the campaign field and show to the user.
			app.showCampaign( '' );
		},

		/**
		 * Check given URL string against a URL regex.
		 *
		 * @since 2.14.0
		 *
		 * @param url URL string.
		 * @returns {boolean}
		 */
		isValidUrlString: function( url ) {
			return /^(https?|s?ftp):\/\/(((([a-z]|\d|-|\.|_|~|[\u00A0-\uD7FF\uF900-\uFDCF\uFDF0-\uFFEF])|(%[\da-f]{2})|[!\$&'\(\)\*\+,;=]|:)*@)?(((\d|[1-9]\d|1\d\d|2[0-4]\d|25[0-5])\.(\d|[1-9]\d|1\d\d|2[0-4]\d|25[0-5])\.(\d|[1-9]\d|1\d\d|2[0-4]\d|25[0-5])\.(\d|[1-9]\d|1\d\d|2[0-4]\d|25[0-5]))|((([a-z]|\d|[\u00A0-\uD7FF\uF900-\uFDCF\uFDF0-\uFFEF])|(([a-z]|\d|[\u00A0-\uD7FF\uF900-\uFDCF\uFDF0-\uFFEF])([a-z]|\d|-|\.|_|~|[\u00A0-\uD7FF\uF900-\uFDCF\uFDF0-\uFFEF])*([a-z]|\d|[\u00A0-\uD7FF\uF900-\uFDCF\uFDF0-\uFFEF])))\.)+(([a-z]|[\u00A0-\uD7FF\uF900-\uFDCF\uFDF0-\uFFEF])|(([a-z]|[\u00A0-\uD7FF\uF900-\uFDCF\uFDF0-\uFFEF])([a-z]|\d|-|\.|_|~|[\u00A0-\uD7FF\uF900-\uFDCF\uFDF0-\uFFEF])*([a-z]|[\u00A0-\uD7FF\uF900-\uFDCF\uFDF0-\uFFEF])))\.?)(:\d*)?)(\/((([a-z]|\d|-|\.|_|~|[\u00A0-\uD7FF\uF900-\uFDCF\uFDF0-\uFFEF])|(%[\da-f]{2})|[!\$&'\(\)\*\+,;=]|:|@)+(\/(([a-z]|\d|-|\.|_|~|[\u00A0-\uD7FF\uF900-\uFDCF\uFDF0-\uFFEF])|(%[\da-f]{2})|[!\$&'\(\)\*\+,;=]|:|@)*)*)?)?(\?((([a-z]|\d|-|\.|_|~|[\u00A0-\uD7FF\uF900-\uFDCF\uFDF0-\uFFEF])|(%[\da-f]{2})|[!\$&'\(\)\*\+,;=]|:|@)|[\uE000-\uF8FF]|\/|\?)*)?(#((([a-z]|\d|-|\.|_|~|[\u00A0-\uD7FF\uF900-\uFDCF\uFDF0-\uFFEF])|(%[\da-f]{2})|[!\$&'\(\)\*\+,;=]|:|@)|\/|\?)*)?$/i.test(url);
		},

		/**
		 * Parse URL, return URL object or null on failure.
		 *
		 * @since 2.14.0
		 *
		 * @param url The URL to parse.
		 * @returns {URL|null}
		 */
		parseUrl: function( url ) {

			// We expect a string.
			if ( typeof url !== 'string' ) {
				console.error( 'Url must be a string.' );
				return null;
			}

			url = url
				.trim() // Strip any whitespace from the beginning or end of the URL.
				.replace(/([^:])(\/\/+)/g, '$1/') // Remove any instances of multiple slashes.
				.replace(/ /g, '%20'); // Encode any spaces in the URL.

			// Check if is a valid URL, returns a new URL object on success or null if fails.
			return app.isValidUrlString( url ) ? new URL( url ) : null;
		}

	}

	return app;

} )( jQuery );

// Initialize.
affWPCustomLink.init();
