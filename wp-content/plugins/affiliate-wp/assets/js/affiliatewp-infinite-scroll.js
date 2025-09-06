/**
 * AffiliateWp Infinite Scroll.
 *
 * Turns possible to paginate results based on the user scroll.
 *
 * @since 2.16.0
 */

'use strict';

/* eslint-disable no-console, no-undef */
affiliatewp.attach(
	'infiniteScroll',
	/**
	 * Infinite Scroll Component.
	 *
	 * Automatically load more results when a specific element on the page becomes visible.
	 *
	 * @example
	 * affiliatewp.infiniteScroll.setup( targetElement, currentPage, itemsPerPage, settings );
	 *
	 * See documentation for detailed usage and parameter information.
	 *
	 * @since 2.16.0
	 */
	{

		/**
		 * Current page number.
		 *
		 * @since 2.16.0
		 *
		 * @type {number} Current page number.
		 */
		page: 1,

		/**
		 * Number of items to fetch.
		 *
		 * @since 2.16.0
		 *
		 * @type {number} Items per page.
		 */
		perPage: 30,

		/**
		 * Plugin settings. Currently, supports only ajax requests.
		 *
		 * @since 2.16.0
		 *
		 * @param {Object} settings - Plugin settings.
		 *   @property {string} triggerElementHTML - Additional HTML to be placed with the trigger element.
		 *   @property {string} triggerElementTag - Trigger element tag name.
		 *   @property {string} triggerElementClass - Trigger element CSS class.
		 *   @property {string} triggerElementPosition - Acceptable values: beforeend, afterend, beforebegin, afterbegin.
		 *   @property {string} provider - The data provider to be used (e.g., 'ajax', 'fetch', etc.).
		 *   @property {number} maxPages - The maximum number of pages expected to be loaded. -1 means to search indefinitely.
		 *   @property {Object} ajax - AJAX settings for the data provider (only applicable when provider is 'ajax').
		 *     @property {string} url - The URL to send the AJAX request.
		 *     @property {string} action - The AJAX action to be performed on the server.
		 *     @property {string} nonce - The security nonce for the AJAX request.
		 *     @property {Object} data - The data to be sent along with the AJAX request.
		 *     @property {string} method - The HTTP method to be used for the AJAX request. Default is 'POST'.
		 *     @property {Object} headers - Additional headers to be sent with the AJAX request.
		 *     @property {string} body - The request body for the AJAX request.
		 *   @property {Object} on - Event handlers for specific actions.
		 *     @property {Function} beforeLoad - Function to be executed before loading.
		 *     @property {Function} loadMore - Function to be executed on "load more" action.
		 *     @property {Function} finish - Function to be executed when the process finishes.
		 */
		settings: {
			triggerElementHTML: '',
			triggerElementTag: 'div',
			triggerElementClass: 'affwp-infinite-scroll-trigger',
			triggerElementPosition: 'afterend',
			provider: 'ajax',
			maxPages: -1,
			ajax: {
				url: '/wp-admin/admin-ajax.php',
				action: '',
				nonce: '',
				data: {},
				method: 'POST',
				headers: {},
				body: ''
			},
			on: {
				beforeLoad: () => {},
				loadMore: () => {},
				finish: () => {},
			}
		},

		/**
		 * The target element where more results will be loaded.
		 *
		 * @since 2.16.0
		 *
		 * @type {Element}
		 */
		targetElement: null,

		/**
		 * The element that will trigger the event to load more results.
		 * This is set automatically by the plugin, do not try to use directly.
		 *
		 * @since 2.16.0
		 *
		 * @type {Element}
		 */
		triggerElement: null,

		/**
		 * Plugin state whitelist.
		 *
		 * @since 2.16.0
		 *
		 * @type {string[]}
		 */
		states: ['idle', 'loading', 'finished'],

		/**
		 * Store the current plugin state.
		 *
		 * @type {string} The current state of the plugin. See @param states for available options.
		 */
		currentState: 'idle',

		/**
		 * Obeserver object.
		 *
		 * @type {null|IntersectionObserver} An observer object.
		 */
		observer: null,

		/**
		 * Set up an infinite scroll.
		 *
		 * @since 2.16.0
		 *
		 * @param {Element} targetElement The element to observe.
		 * @param {number} page Initial page to return more results.
		 * @param {number} perPage Optional items per page.
		 * @param {Object} settings Override plugin settings.
		 */
		setup( targetElement, page, perPage, settings = {} ) {

			this.setTargetElement( targetElement );
			this.setCurrentPage( page );
			this.setItemsPerPage( perPage );

			this.settings = affiliatewp.parseArgs( settings, this.settings );

			// Check if an action ajax action was supplied, so it can auto-initialize.
			if ( this.settings.provider === 'ajax' && this.settings.ajax.action !== '' ) {
				this.init();
			}
		},

		/**
		 * Set the main target element.
		 *
		 * @since 2.16.0
		 *
		 * @param {Element} el A HTML element.
		 */
		setTargetElement( el ) {

			if ( ! ( el instanceof Element ) ) {
				throw new Error( `Target element expects to be an "Element" but got "${typeof el}"` );
			}

			this.targetElement = el;
		},

		/**
		 * Return the target element.
		 *
		 * @since 2.16.0
		 *
		 * @return {Element} The trigger HTML element.
		 */
		getTargetElement() {
			return this.targetElement;
		},

		/**
		 * Set the current page to fetch.
		 *
		 * @since 2.16.0
		 *
		 * @param {number} page The current page to fetch.
		 *
		 * @return {Object} Return self, so it can be chainable.
		 */
		setCurrentPage( page ) {

			if ( typeof page === 'number' && page > 0 ) {

				this.page = page;

				return this;
			}

			console.error( 'Can not set "page". It must be a number greater than zero. Assuming default value.' );
		},

		/**
		 * Return the current page number.
		 *
		 * @since 2.16.0
		 *
		 * @return {number} The current page number.
		 */
		getCurrentPage() {
			return this.page;
		},

		/**
		 * Set the number of items to fetch.
		 *
		 * @since 2.16.0
		 *
		 * @param {number} perPage The current page to fetch.
		 *
		 * @return {Object} Return self, so it can be chainable.
		 */
		setItemsPerPage( perPage ) {

			if ( typeof perPage === 'number' && perPage > 0 ) {

				this.perPage = perPage;

				return this;
			}

			console.error( 'Can not set "perPage". It must be a number greater than zero. Assuming default value.' );
		},

		/**
		 * Return the items per page number.
		 *
		 * @since 2.16.0
		 *
		 * @return {number} The items per page number.
		 */
		getItemsPerPage() {
			return this.perPage;
		},

		/**
		 * Set the element that will trigger the load more event.
		 *
		 * @since 2.16.0
		 *
		 * @param {Element} el A HTML element.
		 */
		setTriggerElement( el ) {

			if ( ! ( el instanceof Element ) ) {
				throw new Error( `Trigger element expects to be an "Element" but got "${typeof el}"` );
			}

			this.triggerElement = el;
		},

		/**
		 * Return the trigger element.
		 *
		 * @since 2.16.0
		 *
		 * @return {Element} The trigger HTML element.
		 */
		getTriggerElement() {
			return this.triggerElement;
		},

		/**
		 * Update the plugin state, reflects on DOM elements.
		 *
		 * @since 2.16.0
		 *
		 * @param {string} state The plugin state.
		 */
		setCurrentState( state ) {

			if ( ! this.states.includes( state ) ) {
				throw new Error( `Can not set "state". You must use one of these options: ${this.states.join( ', ' )}` );
			}

			if ( this.triggerElement instanceof Element ) {
				this.triggerElement.dataset.state = state;
			}

			this.currentState = state;
		},

		/**
		 * Return the current plugin state.
		 *
		 * @since 2.16.0
		 *
		 * @return {string} The state string.
		 */
		getCurrentState() {
			return this.currentState;
		},

		/**
		 * Call an event.
		 *
		 * @param {string} eventName The name of event to call.
		 */
		callEvent( eventName ) {

			if ( this.settings.on.hasOwnProperty( eventName ) && typeof this.settings.on[eventName] === 'function' ) {
				this.settings.on[eventName]();
			}
		},

		/**
		 * Set up and initiate infinite scroll.
		 *
		 * @since 2.16.0
		 */
		init() {

			try {

				this.setupObserver(
					this.getTargetElement(),
					() => {

						if (
							typeof this.settings.maxPages === 'number' &&
							Number.isInteger( this.settings.maxPages ) &&
							this.settings.maxPages > -1 &&
							this.getCurrentPage() >= this.settings.maxPages
						) {
							this.setCurrentState( 'finished' );
						}

						if ( this.getCurrentState() === 'finished' ) {
							return; // Bail if all results were already loaded.
						}

						// Currently only ajax is supported as provider.
						if ( this.settings.provider !== 'ajax' ) {
							throw new Error( `Unsupported provider. Expected "ajax" got "${this.settings.provider}"` );
						}

						this.callEvent( 'beforeLoad' );

						// Set current state to loading. Reflects on the DOM.
						this.setCurrentState( 'loading' );

						// Disconnect the observer now, we will decide if we need to reconnect again later.
						this.disconnectObserver();

						// Set to load the next results page.
						this.setCurrentPage( this.getCurrentPage() + 1 );

						this.ajax()
							.then( response => {

								// TODO: Maybe enable JSON support in the future.
								if ( typeof response === 'object' ) {
									throw new Error( 'Ajax response must be a text but got JSON.' );
								}

								this.handleHTMLResponse( response.toString() );

								this.callEvent( 'loadMore' );

							} );
					}
				)
			} catch( error ) {

				// Console log any errors found during the process.
				console.error( `AffiliateWPInfiniteScroll: ${error}` );
			}
		},

		/**
		 * Process HTML responses.
		 *
		 * @since 2.16.0
		 *
		 * @param {string} html HTML or empty string if no results.
		 */
		handleHTMLResponse( html ) {

			if ( ! html ) {
				this.setCurrentState( 'finished' );
				this.callEvent( 'finish' );
				return; // No more results to load.
			}

			// We append the results at the end of the target element.
			this.getTargetElement().insertAdjacentHTML( 'beforeend', html );

			// Set state to idle again.
			this.setCurrentState( 'idle' );

			// Reconnect the observer, so more results can be loaded, even if the user is already watching the observer.
			this.reconnectObserver( 1000 );
		},

		/**
		 * Set the fetch mode to use AJAX as the data provider.
		 *
		 * @since 2.16.0
		 *
		 * @param {Object} settings - AJAX settings.
		 *   @property {string} url - The URL to send the AJAX request.
		 *   @property {string} action - The AJAX action to be performed on the server.
		 *   @property {string} nonce - The security nonce for the AJAX request.
		 *   @property {Object} data - The data to be sent along with the AJAX request.
		 *   @property {string} method - The HTTP method to be used for the AJAX request. Default is 'POST'.
		 *   @property {Object} headers - Additional headers to be sent with the AJAX request.
		 *   @property {string} body - The request body for the AJAX request.
		 */
		useAjax( settings ) {

			// Point the provider to use ajax.
			this.settings.provider = 'ajax';

			// Override ajax settings.
			this.settings.ajax = affiliatewp.parseArgs(
				settings,
				this.settings.ajax
			);
		},

		/**
		 * Make an ajax request.
		 *
		 * @since 2.16.0
		 *
		 * @return {Promise<Response>} The request response.
		 */
		ajax() {

			let body = this.settings.ajax.body;

			if ( body === '' ) {

				body = new FormData();

				body.append('action', this.settings.ajax.action);
				body.append('nonce', this.settings.ajax.nonce );
				body.append('page', this.getCurrentPage().toString());
				body.append('items_per_page', this.getItemsPerPage().toString());
				body.append('data', JSON.stringify( this.settings.ajax.data ) );

			}

			return fetch( this.settings.ajax.url,
				{
					method: this.settings.ajax.method,
					headers: this.settings.ajax.headers,
					body
				}
			)
				.then( response => {

					if ( ! response.ok ) {
						throw new Error( 'Ajax request failed.' );
					}

					const contentType = response.headers.get( 'Content-Type' );

					if ( contentType && contentType.includes( 'application/json' ) ) {
						return response.json();
					}

					return response.text();

				} )
				.catch( error => {
					console.error( error );
				} );
		},

		/**
		 * Disconnect the observer preventing the load more to happen.
		 * Use `reconnectObserver` method if you want to enable it again.
		 *
		 * @since 2.16.0
		 *
		 * @param {number} timeout Optional. Disconnect only after x milliseconds.
		 */
		disconnectObserver( timeout = 0 ) {

			if ( ! this.observer instanceof IntersectionObserver ) {
				return;
			}

			setTimeout( () => this.observer.disconnect(), timeout );
		},

		/**
		 * Reconnect the observer.
		 *
		 * @since 2.16.0
		 *
		 * @param {number} delay Optional. Reconnect only after x milliseconds.
		 */
		reconnectObserver( delay = 0 ) {
			setTimeout( () => this.observer.observe( this.getTriggerElement() ), delay );
		},

		/**
		 * Observe an DOM element and trigger a callback function when the user reaches the element.
		 *
		 * @since 2.16.0
		 *
		 * @param {Element} elementToObserve The DOM element to observe.
		 * @param {Function} callback A function to execute when the user reaches the element.
		 */
		setupObserver( elementToObserve, callback ) {

			if ( ! window.IntersectionObserver ) {
				throw new Error( 'This browser do not support intersection observer.' );
			}

			if ( ! ( elementToObserve instanceof Element ) ) {
				throw new Error( `Target element expected and "Element" but found "${typeof elementToObserve}"` );
			}

			if ( typeof callback !== 'function' ) {
				throw new Error( `Callback expected a "function" was expected but found "${typeof callback} instead"` );
			}

			this.observer = new IntersectionObserver( ( entries ) => {

				if ( ! entries[0].isIntersecting ) {
					return;
				}

				callback( this.observer );
			} );

			try {

				// Set up the observer to a new element that will serve as our trigger.
				this.observer.observe( this.generateTriggerElement( elementToObserve ) );

			} catch ( error ) {
				throw new Error( `Infinite Scroll setup error: ${error}` );
			}
		},

		/**
		 * Creates a new element to serve as the final observer.
		 *
		 * This new element will be inserted at the end of the original observed element.
		 * All the new results will be inserted before this element, this way the trigger
		 * element will be always at the end, making it possible to run the observer again
		 * when the user scrolls down.
		 *
		 * @since 2.16.0
		 *
		 * @param {Element} parentElement The element to append our trigger element.
		 * @return {HTMLDivElement} The trigger element.
		 */
		generateTriggerElement( parentElement ) {

			if ( ! ( parentElement instanceof Element ) ) {
				throw new Error( `Element expected, got "${typeof parentElement}"` );
			}

			const triggerElement = document.createElement(
				this.settings.triggerElementTag === ''
					? 'div'
					: this.settings.triggerElementTag
			);

			triggerElement.classList.add(
				this.settings.triggerElementClass === ''
					? 'affwp-infinite-scroll-trigger'
					: this.settings.triggerElementClass
			);

			triggerElement.dataset.state = this.getCurrentState();

			if ( this.settings.triggerElementHTML !== '' ) {
				triggerElement.insertAdjacentHTML( 'afterbegin', this.settings.triggerElementHTML );
			}

			parentElement.insertAdjacentElement(
				['beforeend', 'afterend', 'beforebegin', 'afterbegin'].includes( this.settings.triggerElementPosition )
					? this.settings.triggerElementPosition
					: 'beforeend',
				triggerElement
			);

			this.setTriggerElement( triggerElement );

			return triggerElement;
		}
	}
);
