/**
 * Affiliate Area Creative functions.
 *
 * @since 2.16.0
 */

'use strict';

/* eslint-disable no-console, no-undef, jsdoc/no-undefined-types */
( function() {

	const affiliateWPCreatives = {

		/**
		 * Backend data.
		 *
		 * @since 2.16.0
		 *
		 * @type {Object}
		 */
		data: {},

		/**
		 * Creatives page selectors.
		 *
		 * @since 2.16.0
		 *
		 * @type {Object}
		 */
		selectors: {
			creativesViewId: 'affwp-creatives-view',
			modalPreviewId: 'affwp-creatives-modal',
			modalContainerId: 'affwp-modal-container',
			creativeListItem: '.affwp-creatives-list-item.affwp-creatives-list-body',
			creativeClickableColumns: '.affwp-creatives-item-actions',
			copyActionButton: '.affwp-creatives-list-action[data-action="copy"]',
			viewDetailsActionButton: '.affwp-creatives-list-action[data-action="view-details"]',
			modalItem: '[data-modal]',
			copyTextarea: '.affwp-copy-textarea-content',
			creativeClone: '.affwp-creative-clone',
			copyTooltip: 'button[data-action="copy"]:not(.affwp-tooltip-initialized)',
			qrCodeActive: '.fancybox__slide.is-selected .affwp-qrcode-modal-preview',
			qrCodePreview: '.affwp-qrcode-preview',
			qrCodeModalPreview: '.affwp-qrcode-modal-preview',
			qrCodeModalDownloadButton: '.fancybox__slide.is-selected .affwp-button[data-download]'
		},

		/**
		 * Used to better control the flow of some events.
		 *
		 * Don't use directly, this if for private purposes only.
		 *
		 * @since 2.16.0
		 * @type {null|WeakMap}
		 */
		eventHandlers: null,

		/**
		 * Initiate.
		 *
		 * @since 2.16.0
		 */
		init() {

			// Get the back-end data.
			this.data = window.affiliatewpCreativesData ?? {};

			// Store event handlers.
			this.eventHandlers = new WeakMap();

			// Setup all modals.
			this.setupModals();

			// Setup pagination.
			this.setupInfiniteScroll();

			// Bind other actions.
			this.initActions();

			// Generate QR Codes.
			this.generateQRCodes();
		},

		/**
		 * Get the creative ID from hash, if exists.
		 *
		 * @since 2.16.0
		 * @return {number} The Creative ID.
		 */
		getCreativeIDFromHash() {

			const matches = window.location.hash.match( /^#creative-(\d+)$/ );

			return matches ? parseInt( matches[1] ) : 0;
		},

		/**
		 * Initiate modal events.
		 *
		 * @since 2.16.0
		 */
		setupModals() {

			if ( ! affiliatewp.has( 'modal' ) ) {
				throw new Error( 'Missing modal script. Ensure affiliatewp.modal is loaded correctly.' );
			}

			if ( ! document.getElementById( this.selectors.creativesViewId ) ) {
				return; // Bail if no creatives found.
			}

			// Check if it is attempting to load a creative modal on page load.
			let creativeID = this.getCreativeIDFromHash();

			// If the creative exists, set to null, so we prevent the creation of the temporary creative.
			if ( creativeID && document.querySelector( `[data-slug="creative-${creativeID}"]` ) ) {
				creativeID = null;
			}

			// Add a temporary creative element, so the modal can be loaded even if the creative was not loaded yet.
			if ( creativeID && this.data.hasOwnProperty( 'creativeAjaxUrl' ) ) {

				// We insert a temp element so Fancybox will be able to load it using the hash plugin.
				document.getElementById( this.selectors.creativesViewId ).insertAdjacentHTML(
					'afterbegin',
					`<span
							class="${this.selectors.creativeClone.replace('.', '')}"
							data-grouped
							data-modal
							data-type="ajax"
							data-src="${this.data.creativeAjaxUrl + creativeID}"
							data-slug="creative-${creativeID}"
							style="display: none"
						  ></span>`
				)
			}

			affiliatewp.modal
				.onInit( () => {

					const clone = document.querySelector( this.selectors.creativeClone );

					if ( ! clone ) {
						return;
					}

					// Once the modal open, we get rid of the temporary creative to avoid conflicts.
					document.querySelector( '.affwp-creative-clone' ).remove();
				} )
				.onDone( () => {

					document.querySelectorAll( this.selectors.qrCodeModalPreview ).forEach( ( qrCodeElement ) => {

						if (
							! ( qrCodeElement instanceof Element ) ||
							qrCodeElement.classList.contains( 'affwp-qrcode-initialized' )
						) {
							return; // Bail if it is not an Element or already initialized.
						}

						affiliatewp.qrcode(
							qrCodeElement,
							qrCodeElement.dataset.url,
							affiliatewp.parseArgs(
								this.parseQRCodeSettings( qrCodeElement ),
								{
									format: 'png'
								}
							)
						);
					} );
				} )
				.onLoading( () => {
					this.hideAllTooltips();
				} )
				.bind(
					document.getElementById( this.selectors.creativesViewId ),
					this.selectors.modalItem,
					{
						groupAll: true,
						showThumbs: false,
						dragToClose: false,
						draggable: false,
						autoFocus: false,
						idle: false,
						parentEl: document.getElementById( this.selectors.modalContainerId ),

					}
				);
		},

		/**
		 * Retrieve and parse QR Code settings from an HTML Element.
		 *
		 * @param {Element} qrCodeElement The reference element for analyzing the data.
		 *
		 * @since 2.17.0
		 */
		parseQRCodeSettings( qrCodeElement ) {

			if ( ! ( qrCodeElement instanceof Element ) ) {
				throw new Error( `Expected Element, got ${typeof qrCodeElement}` );
			}

			return qrCodeElement.hasAttribute( 'data-settings' ) && typeof JSON.parse( qrCodeElement.dataset.settings ) === 'object'
				? JSON.parse( qrCodeElement.dataset.settings )
				: {}
		},

		/**
		 * Setup infinite scroll.
		 *
		 * @since 2.16.0
		 */
		setupInfiniteScroll() {

			if ( ! affiliatewp.has( 'infiniteScroll' ) ) {
				throw new Error( 'Missing infiniteScroll script. Ensure affiliatewp.infiniteScroll is loaded correctly.' );
			}

			const elementToObserve = document.getElementById( this.selectors.creativesViewId );

			if ( ! elementToObserve ) {
				return; // Bail if the expected Element is not present in this page.
			}

			affiliatewp.infiniteScroll.setup(
				elementToObserve,
				this.data.hasOwnProperty( 'page' ) ? this.data.page : 1,
				this.data.hasOwnProperty( 'itemsPerPage' ) ? this.data.itemsPerPage : 30,
				{
					maxPages: this.data.hasOwnProperty( 'maxPages' ) ? this.data.maxPages : -1,
					triggerElementHTML: '<div class="affwp-spinner"><svg viewBox="0 0 50 50"><circle cx="25" cy="25" r="20"></circle><circle cx="25" cy="25" r="20"></circle></svg></div>',
					ajax: {
						action: 'affwp_creatives_load_more',
						nonce: this.data.hasOwnProperty( 'nonce' ) ? this.data.nonce : '',
						data: this.data.hasOwnProperty( 'queryArgs' ) ? affiliatewp.parseArgs( this.data.queryArgs ) : {}
					},
					on: {
						loadMore: () => {
							this.generateQRCodes();
							this.makeClickableItems();
						}
					}
				}
			);
		},

		/**
		 * Return if the clipboard allows copying for this site.
		 *
		 * @since 2.16.0
		 *
		 * @return {boolean} Whether is enabled or not.
		 */
		isCopyEnabled() {
			return !! ( navigator && navigator.clipboard );
		},

		/**
		 * Initiate action events.
		 *
		 * @since 2.16.0
		 */
		initActions() {

			// Auto select the textarea content for copy sections when clicking on it.
			document
				.getElementById( this.selectors.modalContainerId )
				.addEventListener( 'click', ( event ) => {

					if ( event.target && ! event.target.matches( this.selectors.copyTextarea ) ) {
						return; // Bail if it is not a copy textarea field.
					}

					event.target.focus();
					event.target.select();
				} );

			// Copy action.
			document.addEventListener( 'submit', ( event ) => {

				if ( event.target && event.target.name !== 'affiliatewp_copy_form' ) {
					return; // Bail if it is not a copy textarea.
				}

				event.preventDefault();

				this.handleCopyContent( event.target );
			} );

			// Download action.
			document.addEventListener( 'click', ( event ) => {

				if ( ! event.target.classList.contains( 'affwp-download-button' ) ) {
					return; // Bail if it is not the download button.
				}

				event.preventDefault();

				if ( event.target.dataset.type === 'qr_code' ) {
					this.handleQRCodeDownload( event.target );
					return;
				}

				this.handleImageDownloadFromButton(
					event.target.dataset.download,
					event.target.dataset.href
				);
			} );

			// Print action.
			document.addEventListener( 'click', ( event ) => {

				if ( ! event.target.classList.contains( 'affwp-print-button' ) ) {
					return; // Bail if it is not the print button.
				}

				// Get the content to be printed.
				const sourceEl = document.querySelector( '.fancybox__slide.is-selected .affwp-qrcode-modal-preview' );

				// Create a new element to be appended
				let targetEl = document.getElementById( 'affwp-printable-area' );

				// Document is already in the DOM.
				if ( targetEl ) {

					// Update the contents.
					targetEl.innerHTML = sourceEl.innerHTML;

					// Call print method.
					window.print();

					return;
				}

				// Create a new element.
				targetEl = document.createElement( 'div' );

				targetEl.id        = 'affwp-printable-area';
				targetEl.innerHTML = sourceEl.innerHTML;

				// Append to the end of the document.
				document.body.appendChild( targetEl );

				// Call print method.
				window.print();
			} );

			// Handle view details click for other columns.
			this.makeClickableItems();
		},

		/**
		 * Handle download button for images QR Codes.
		 *
		 * @since 2.17.2
		 *
		 * @param {string} clickedEl The element clicked.
		 */
		handleQRCodeDownload( clickedEl ) {

			// Get the visible QR Code.
			const qrCodeElement = document.querySelector( this.selectors.qrCodeActive );

			// Generate a new QR Code object.
			const qrCodeDownload = affiliatewp.qrcode();

			// Update the QR Code settings.
			qrCodeDownload.updateSettings(
				qrCodeElement.hasAttribute( 'data-settings' ) && typeof JSON.parse( qrCodeElement.dataset.settings ) === 'object'
					? JSON.parse( qrCodeElement.dataset.settings )
					: {}
			);

			// Generate a new PNG file in background and downloaded it.
			qrCodeDownload.createPNG(
				qrCodeElement.dataset.url,
				( downloadUrl ) => {
					this.handleImageDownloadFromButton( clickedEl.dataset.download, downloadUrl );
				}
			);
		},

		/**
		 * Handle image download for HTML buttons.
		 *
		 * @since 2.17.2
		 *
		 * @param {string} filename The name of the file.
		 * @param {string} downloadUrl The URL to be used as href for the temp anchor element.
		 */
		handleImageDownloadFromButton( filename, downloadUrl ) {

			const downloadLink = document.createElement( 'a' );

			downloadLink.download = filename;
			downloadLink.href = downloadUrl;

			// External images cannot be downloaded, so we make sure they at least open in a new tab.
			downloadLink.target = '_blank';

			downloadLink.click();
			downloadLink.remove();
		},

		/**
		 * Generate QR Codes.
		 *
		 * @since 2.17.0
		 */
		generateQRCodes() {

			if ( ! affiliatewp.has( 'qrcode' ) ) {
				throw new Error( 'Missing QR Code script. Ensure affiliatewp.qrcode is loaded correctly.' );
			}

			document.querySelectorAll( this.selectors.qrCodePreview ).forEach( ( qrCodeElement ) => {

				if (
					qrCodeElement.classList.contains( 'affwp-qrcode-initialized' ) ||
					! qrCodeElement.hasAttribute( 'data-url' )
				) {
					return; // Already generated or don't contain a URL to generate.
				}

				const qrCodeSettings = qrCodeElement.hasAttribute( 'data-settings' ) && typeof JSON.parse( qrCodeElement.dataset.settings ) === 'object'
					? JSON.parse( qrCodeElement.dataset.settings )
					: {};

				affiliatewp.qrcode(
					qrCodeElement,
					qrCodeElement.dataset.url,
					affiliatewp.parseArgs(
						qrCodeSettings,
						{
							format: 'png'
						}
					)
				);
			} );
		},

		/**
		 * Make columns clickable, so users can click on the row to open modals.
		 *
		 * @since 2.16.0
		 */
		makeClickableItems() {

			document.querySelectorAll( this.selectors.creativeClickableColumns ).forEach( ( col ) => {

				if ( this.eventHandlers.has( col ) ) {
					return; // Bail if the event handler was already bound.
				}

				// Bind the event handler and store the reference in the map.
				const boundEventHandler = this.handleItemClick.bind( this, col );

				this.eventHandlers.set( col, boundEventHandler );

				// Bind the event on all visible items.
				col.addEventListener( 'click', boundEventHandler );
			} );
		},

		/**
		 * Trigger the modal when clicking on any item column. Except by the actions column.
		 *
		 * @since 2.16.0
		 *
		 * @param {Element} col The column element.
		 */
		handleItemClick( col ) {
			col.parentNode.querySelector( this.selectors.viewDetailsActionButton ).click();
		},

		/**
		 * Hide all active tooltips.
		 *
		 * @since 2.16.0
		 */
		hideAllTooltips() {
			affiliatewp.tooltip.hideAll();
		},

		/**
		 * Display the copy tooltip message.
		 *
		 * @since 2.16.0
		 *
		 * @param {string} content The content to be displayed.
		 * @param {string} trigger The trigger event. Accepts: mouseenter, manual. Default: manual.
		 * @param {string} placement Tooltip position. Accepts: top, right, bottom, left or auto. Default: auto.
		 * @param {number} hideDelay Time in milliseconds before hiding the tooltip. Default: 5000.
		 */
		showCopyTooltip( content, trigger = 'manual', placement = 'auto', hideDelay = 5000 ) {

			affiliatewp.tooltip.show(
				this.selectors.copyTooltip,
				content,
				{
					trigger,
					placement,
					hideDelay
				}
			);
		},

		/**
		 * Handle the copy action from copy buttons.
		 *
		 * @since 2.16.0
		 *
		 * @param {Element} el The form element.
		 */
		handleCopyContent( el ) {

			const textarea = el.querySelector( this.selectors.copyTextarea );
			textarea.focus();
			textarea.select();

			// Copy could not be enabled if site is running on non-secure connection.
			if ( ! this.isCopyEnabled() ) {
				this.showCopyTooltip( this.data.i18n.copyDisabled );
			}

			this.copyContent(
				textarea.value,
				() => this.showCopyTooltip( this.data.i18n.copySuccess ),
				() => this.showCopyTooltip( this.data.i18n.copyError ),
			);
		},

		/**
		 * Copy contents.
		 *
		 * @since 2.16.0
		 *
		 * @param {string} content The content to copy.
		 * @param {Function} successCallback A success callback function.
		 * @param {Function} errorCallback A error callback function.
		 */
		copyContent( content, successCallback, errorCallback ) {

			if ( ! this.isCopyEnabled() ) {
				console.error( 'Copy is disabled for an unknown reason.' );
				return; // Copy is not enabled in this browser.
			}

			navigator.clipboard.writeText( content )
				.then( () => {
					if ( successCallback && typeof successCallback === 'function' ) {
						successCallback();
					}
				} )
				.catch( () => {
					if ( errorCallback && typeof errorCallback === 'function' ) {
						errorCallback();
					}
				} );
		}
	}

	affiliateWPCreatives.init();
} () );

