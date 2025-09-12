/**
 * AffiliateWp Modal Class.
 *
 * This is an abstraction layer for the Fancybox library.
 *
 * @since 2.16.0
 */

'use strict';

/* eslint-disable no-console, no-undef */
affiliatewp.attach(
	'modal',
	/**
	 * Modal Component.
	 *
	 * Display a modal window.
	 *
	 * @example
	 *
	 * Bind direct to HTML elements.
	 * affiliatewp.modal.bind( selectorOrElement );
	 *
	 * Call programmatically.
	 * affiliatewp.modal.show( contents );
	 *
	 * See documentation below for detailed usage and parameter information.
	 *
	 * @since 2.16.0
	 */
	{

		/**
		 * Instance of the 3rd-party plugin.
		 *
		 * Private property, please do not use this directly.
		 *
		 * @since 2.16.0
		 *
		 * @type {null}
		 */
		instance: null,

		/**
		 * Array of contents to be displayed.
		 *
		 * @since 2.16.0
		 *
		 * @type {Array}
		 */
		contents: [],

		/**
		 * Supported contents.
		 *
		 * @since 2.16.0
		 *
		 * @type {Array}
		 */
		supportedContents: ['image', 'video', 'html', 'ajax'],

		/**
		 * Plugin default settings.
		 *
		 * All the settings added here will be handled by the updateSettings method.
		 *
		 * @since 2.16.0
		 *
		 * @type {Object}
		 */
		settings: {
			enableHashNavigation: true,
			draggable: true,
			showThumbs: true,
			slug: '',
			startIndex: 0,
			groupAttr: 'data-grouped',
			groupAll: false,
			autoFocus: true,
			dragToClose: true,
			idle: 3500,
			parentEl: null,
			keyboard : {
				Escape: 'close',
				Delete: 'close',
				Backspace: 'close',
				PageUp: 'next',
				PageDown: 'prev',
				ArrowUp: 'prev',
				ArrowDown: 'next',
				ArrowRight: 'next',
				ArrowLeft: 'prev',
			}
		},

		/**
		 * Events in queue to be executed by plugin.
		 *
		 * @since 2.16.0
		 *
		 * @type {Object}
		 */
		eventQueue: {},

		/**
		 * Display the modal programmatically.
		 *
		 * @since 2.16.0
		 *
		 * @param {Array} contents Array of contents.
		 * @param {Object} settings Override modal settings.
		 */
		show( contents = [], settings = {} ) {

			// Prepare settings.
			this.updateSettings( settings );

			// Set all contents.
			this.setContents( contents );

			// Fancybox does not have a show method when instantiating as a class, so we emulate the behavior, recreating everything.
			if ( this.instance && typeof this.instance.hasOwnProperty( 'destroy' ) ) {

				this.instance.destroy();
				this.instance = null;
			}

			// Initialize the modal for the first time and execute events.
			this.initializeModal();
			this.initializeCustomEvents();

			return this;
		},

		/**
		 * Bind the click event to open modals directly to elements.
		 *
		 * When using with a single argument, modal will be bound to the elements from the selector provided.
		 * You can also specify a Element from a `document.getElementById` or `document.querySelector` call as first parameter
		 * and provide a second parameter to match only the elements from the selector provided within the Element.
		 * This can be useful when dealing with contents that are injected to the DOM inside the Element after calling this method.
		 *
		 * If a [data-slug] is found in the Element, the hash will be automatically updated.
		 * You can use [data-src] to specify different types of contents:
		 *     - HTML: If an element id (#my-element) is provided, the content from that element will be exhibited in the modal.
		 *     - IMAGE: If a image url is provided will be displayed as image.
		 *     - VIDEO: If a external video url is provided will display the video. Alternatively, you can supply a self-hosted video url after adding [data-type="html5video"] to the element.
		 *         - You can control the video size adding [data-width] and [data-height] to the element.
		 *     - AJAX: To load contents asynchronously, add [data-type="ajax] to the element and supply an endpoint url to [data-src].
		 *
		 * @param {string|Element} selectorOrElement The element to bind, a string when using a single argument or Element when using two or more arguments.
		 * @param {string|Object} filterSelectorOrSettings Filter the elements found from the first parameter based on a selector. It can also be a settings object, see below.
		 * @param {Object} settings Modal settings. See @property settings for the available settings.
		 */
		bind( selectorOrElement, filterSelectorOrSettings, settings = {} ) {

			const events = { on: this.getEventsObject() };

			// First parameter must be a string if you are calling using one or two arguments and the second is an object.
			if (
				( arguments.length === 1 || ( arguments.length === 2 && typeof filterSelectorOrSettings === 'object' ) ) &&
				typeof selectorOrElement !== 'string'
			) {
				throw new Error( `AffiliateWPModal.bind: Expected string as the first argument, got ${typeof selectorOrElement}` );
			}

			// At this point we expect the first argument to be an Element.
			if (
				arguments.length >= 2 &&
				typeof filterSelectorOrSettings === 'string' &&
				! ( selectorOrElement instanceof Element )
			) {
				throw new Error(`AffiliateWPModal.bind: Expected Element as the first argument, got ${typeof selectorOrElement}`);
			}

			// Last check, the second parameter must be a string at this point.
			if (
				arguments.length >= 2 &&
				selectorOrElement instanceof Element &&
				typeof filterSelectorOrSettings !== 'string'
			) {
				throw new Error( `AffiliateWPModal.bind: Expected string as the second argument, got ${typeof filterSelectorOrSettings}` );
			}

			// The simplest way to use bind, a string as selector. Events will be supplied as settings.
			if ( arguments.length === 1 ) {

				Fancybox.bind( selectorOrElement, events );

				return this;
			}

			// A string selector was supplied and a second argument as a settings object.
			if ( arguments.length === 2 && typeof filterSelectorOrSettings === 'object' ) {

				Fancybox.bind(
					selectorOrElement,
					affiliatewp.parseArgs(
						this.parseSettings( filterSelectorOrSettings ),
						events
					)
				);

				return this;
			}

			// An Element was supplied as first argument and will be filtered by the selector on the second argument.
			if ( arguments.length === 2 && typeof filterSelectorOrSettings === 'string' ) {

				Fancybox.bind(
					selectorOrElement,
					filterSelectorOrSettings,
					events
				);

				return this;
			}

			// All available parameters being used.
			if ( arguments.length >= 3 ) {

				Fancybox.bind(
					selectorOrElement,
					filterSelectorOrSettings,
					affiliatewp.parseArgs(
						this.parseSettings( settings ),
						events
					)
				);

				return this;
			}

			return this;
		},

		/**
		 * Initialize the modal if a hash is found in the URL.
		 * This is only useful when calling the modal programmatically, if you're using the bind method
		 * you don't need to call this directly, as Fancybox automatically does.
		 *
		 * @since 2.16.0
		 */
		maybeStartFromHash() {

			/* eslint-disable no-undef */
			const HashPlugin = Fancybox.Plugins.Hash;

			if ( HashPlugin && ! this.instance ) {

				const { hash, slug, index } = HashPlugin.parseURL();

				// Test for hash in the settings. A sequential index will be used in this case, starting from 0.
				if ( hash && slug === this.settings.slug ) {

					this.settings.startIndex = index - 1;
					this.show();

					return; // Slug found. Just exit.
				}

				// Test for individual hashes.
				const slideIndex = this.contents.findIndex( slide => slide.slug === slug );

				if ( hash && slideIndex > -1 ) {

					this.settings.startIndex = slideIndex;
					this.show();
				}

			}
			/* eslint-enable no-undef */
		},

		/**
		 * Set content to be exhibited.
		 *
		 * @since 2.16.0
		 *
		 * @param {Array} contents - Array of content objects.
		 *
		 * @typedef {Object} ContentObject
		 *    @property {string} src - The source of the content. Can be one either an URL for an image or video, or HTML.
		 * 							   You need to change the type property accordingly to the content source.
		 *    @property {string} type - Type of the content. Accepts one of the values set on the supportedContents object.
		 *    @property {string} slug - Unique slug item.
		 *
		 * @return {Object} Return this object, making it chainable.
		 *
		 * @example
		 * setContents([
		 *   { src: 'path/to/content1', type: 'image', slug: 'url-content' },
		 *   { src: 'https://youtube.com/hashForAVideo', type: 'video', slug: 'video-content' },
		 *   { src: '<p>My text here</p>', type: 'html', slug: 'html-content' }
		 * ]);
		 */
		setContents( contents = [] ) {

			// Check if is an array.
			if ( ! Array.isArray( contents ) ) {
				throw new Error( 'Contents must be an array of objects.' );
			}

			// Display errors if the required properties are not set properly.
			contents = contents.filter((item, index) => {

				if ( ! item.hasOwnProperty( 'src' ) ) {
					console.error( `Modal src not found for item index ${index}. Ignoring item...` );
					return false;
				}

				if ( ! item.hasOwnProperty( 'type' ) || ! this.supportedContents.includes( item.type ) ) {
					console.error( `Modal invalid type for item index ${index}. Ignoring item...` );
					return false;
				}

				return true;
			} );

			this.contents = contents;

			return this;
		},

		/**
		 * Update plugin settings.
		 *
		 * It will always merge with the plugin default settings .
		 *
		 * @param {Object} settings Modal settings.
		 *
		 * @return {Object} Return this object, making it chainable.
		 */
		updateSettings( settings ) {

			if ( typeof settings !== 'object' ) {

				throw new Error( 'Settings must be an object.' );
			}

			Object.entries( this.parseSettings( settings ) ).forEach( ( [key, value] ) => {
				this.settings[key] = value;
			} );

			return this;
		},

		/**
		 * Parse our settings and return the equivalent Fancybox settings.
		 * Any other settings will return as it is.
		 *
		 * @since 2.16.0
		 *
		 * @param {Object} settings See @property settings for available settings.
		 *
		 * @return {Object} Fancybox settings.
		 */
		parseSettings( settings ) {

			// Map of settings, currently support only boolean values.
			const settingsMap = {
				enableHashNavigation: {
					type: 'boolean',
					fancyboxName: 'Hash',
					defaultValueOnTrue: true,
					defaultValueOnFalse: false
				},
				showThumbs: {
					type: 'boolean',
					fancyboxName: 'Thumbs',
					defaultValueOnTrue: true,
					defaultValueOnFalse: false
				},
				draggable: {
					type: 'boolean',
					fancyboxName: 'Carousel',
					defaultValueOnTrue: null,
					defaultValueOnFalse: {
						Panzoom: {
							touch: false,
						}
					}
				}
			};

			return Object.entries( settings ).reduce( ( result, [settingName, settingValue] ) => {

				if ( settingsMap.hasOwnProperty( settingName ) ) {

					const settingOptions = settingsMap[settingName];

					if ( settingOptions.type === 'boolean' && settingValue === true && settingOptions.defaultValueOnTrue !== null ) {
						result[settingOptions.fancyboxName] = settingOptions.defaultValueOnTrue;
					}

					if ( settingOptions.type === 'boolean' && settingValue === false && settingOptions.defaultValueOnFalse !== null ) {
						result[settingOptions.fancyboxName] = settingOptions.defaultValueOnFalse;
					}
				}

				result[settingName] = settingValue;

				return result;
			}, {} );
		},

		/**
		 * Runs once the modal is initialized.
		 *
		 * @since 2.16.0
		 *
		 * @param {Function} callback Method to be executed.
		 *
		 * @return {Object} Return this object, making it chainable.
		 */
		onInit( callback ) {

			this.addToEventQueue( 'init', callback );

			return this;
		},

		/**
		 * Runs once the modal is ready with all content loaded.
		 *
		 * @since 2.16.0
		 *
		 * @param {Function} callback Method to be executed.
		 *
		 * @return {Object} Return this object, making it chainable.
		 */
		onDone( callback ) {

			this.addToEventQueue( 'done', callback );

			return this;
		},

		/**
		 * Runs right before closing.
		 *
		 * @since 2.16.0
		 *
		 * @param {Function} callback Method to be executed.
		 *
		 * @return {Object} Return this object, making it chainable.
		 */
		onLoading( callback ) {

			this.addToEventQueue( 'loading', callback );

			return this;
		},

		/**
		 * Runs after closing the modal.
		 *
		 * @since 2.16.0
		 *
		 * @param {Function} callback Method to be executed.
		 *
		 * @return {Object} Return this object, making it chainable.
		 */
		onClose( callback ) {

			this.addToEventQueue( 'close', callback );

			return this;
		},

		/**
		 * Initialize the modal plugin.
		 *
		 * Loads the 3rd-party modal instance.
		 *
		 * @since 2.16.0
		 */
		initializeModal() {

			if ( ! Array.isArray( this.contents ) ) {
				return; // Must be an array.
			}

			if ( ! this.contents.length ) {
				return; // Empty array. Nothing to display.
			}

			// eslint-disable-next-line no-undef
			this.instance = new Fancybox( this.contents, this.settings );
		},

		/**
		 * Map events to custom event handler methods.
		 *
		 * This method maps modal events to corresponding custom event handler methods.
		 * The mapping is based on Fancybox methods. If the modal library changes,
		 * this mapping may need to be updated accordingly.
		 *
		 * @since 2.16.0
		 *
		 * @return {Object} - An object containing the mapped events and their corresponding custom event handler methods.
		 */
		getCustomEvents() {

			return {
				close: 'onClose',
				init: 'onInit',
				done: 'onDone',
				loading: 'onLoading'
			};
		},

		/**
		 * Initialize custom events for the modal.
		 *
		 * This method sets up custom event handlers for the modal using the `.on` method.
		 * It is currently designed to work with Fancybox, but may need modification if the modal library changes.
		 *
		 * @since 2.16.0
		 */
		initializeCustomEvents() {

			if ( this.instance === null ) {
				return; // No instance found.
			}

			Object.entries( this.getEventsObject() ).forEach( ( [ eventName, callback ] ) => {
				this.instance.on( eventName, callback );
			} );
		},

		/**
		 * Return an object formatted with the required pattern for use with Fancybox.
		 *
		 * @since 2.16.0
		 *
		 * @return {Object} The object of events.
		 */
		getEventsObject() {

			const customEvents = this.getCustomEvents();
			const events = {};

			Object.entries( customEvents ).forEach( ( [ eventName, methodName ] ) => {

				if ( typeof this[ methodName ] === 'function' ) {
					events[ eventName ] = () => {
						this.executeEventQueue( eventName );
					}
				}
			} );

			return events;
		},

		/**
		 * Add events to the queue to be executed when requested.
		 *
		 * @since 2.16.0
		 *
		 * @param {string} eventName - Name of the event to be added to the queue.
		 * @param {Function} callback - Method to be executed for the event.
		 */
		addToEventQueue( eventName, callback ) {

			if ( ! this.eventQueue[ eventName ] ) {
				this.eventQueue[ eventName ] = [];
			}

			this.eventQueue[ eventName ].push( callback );
		},

		/**
		 * Runs an event from the queue.
		 *
		 * @since 2.16.0
		 *
		 * @param {string} eventName - Name of the event to be executed.
		 */
		executeEventQueue( eventName ) {

			const callbacks = this.eventQueue.hasOwnProperty( eventName )
				? this.eventQueue[ eventName ]
				: [];

			if ( Array.isArray( callbacks ) ) {

				callbacks.forEach( callback => {

					if ( typeof callback === 'function' ) {
						callback();
					}
				} );
			}
		}
	}
);
