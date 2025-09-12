/**
 * An abstraction layer for the node-qrcode library.
 *
 * @author Darvin da Silveira <ddasilveira@awesomemotive.com>
 * @since 2.17.0
 */

'use strict';

/* eslint-disable no-console, no-undef, no-shadow */
affiliatewp.attach(
	'qrcode',
	/**
	 * QR Code Component.
	 *
	 * Generate QR Code injecting direct into the DOM.
	 *
	 * @since 2.17.0
	 *
	 * @example
	 * affiliatewp.qrcode( element, content, settings );
	 *
	 * @param {Element} element A HTML element.
	 * @param {string} text The content to display.
	 * @param {Object} settings Additional settings. See @param settings for all available settings.
	 *
	 * return {Object} The QR Code generator instance.
	 */
	function ( element, text, settings = {} ) {

		const qrCodeGenerator = {

			/**
			 * The HTML element to render the QR Code.
			 *
			 * @since 2.17.0
			 *
			 * @type {Element|null} An HTML element.
			 */
			element: null,

			/**
			 * The text used to generate the QR Code.
			 *
			 * @since 2.17.0
			 *
			 * @type {string} The QR Code text.
			 */
			text: '',

			/**
			 * The QR Code generated.
			 *
			 * @since 2.17.0
			 *
			 * @type {string} The result code.
			 */
			code: '',

			/**
			 * Default settings.
			 *
			 * @since 2.17.0
			 *
			 * @param {Object} settings - Plugin settings.
			 *   @property {number} width The QR Code width.
			 *   @property {number} height The QR Code height.
			 *   @property {number} quality Image quality, only applied to PNG format.
			 *   @property {number} format Output format, possible values: svg and png. Default: svg.
			 *   @property {number} margin The quiet zone margin. Default: 2
			 *   @property {Object} color Customize colors.
			 *    @property {string} dark - Dark color.
			 *    @property {string} light - Light color.
			 *   @property {number} errorCorrectionLevel - Correct level. Possible values: L, M, Q, H. Default: M.
			 *   	Higher levels offer a better error resistance but reduce the symbol's capacity.
			 *   	L is the lower level and H the higher level.
			 *   @property {boolean} useSVG - Whether to use SVG or not.
			 *   @property {Object} on - Event handlers for specific actions.
			 *     @property {Function} load - Function to be executed right after the image is fully loaded.
			 *     @property {Function} error - Function to be executed on failed operations.
			 */
			settings: {
				width: 256,
				height: 256,
				quality: 0.92,
				format: 'svg',
				margin: 2,
				color: {
					dark: '#000000',
					light: '#FFFFFF'
				},
				errorCorrectionLevel: 'M',
				on: {
					load: () => {},
					error: () => {}
				}
			},

			/**
			 * Cache parameters.
			 *
			 * @since 2.17.0
			 *
			 * @param {Object} cacheSettings - Cache parameters.
			 *   @property {string} key Cache key.
			 *   @property {number} sizeLimit Size limit in bytes.
			 */
			cacheSettings: {
				enable: true,
				key: 'affwp_qrcodes',
				sizeLimit: 5 * 1024 * 1024 // 5MB
			},

			/**
			 * Set new cache settings.
			 *
			 * @since 2.17.0
			 *
			 * @param {Object} settings New cache settings.
			 */
			setCacheSettings( settings = {} ) {

				this.cacheSettings = affiliatewp.parseArgs(
					settings,
					this.cacheSettings
				);
			},

			/**
			 * Get the cache from the local storage.
			 *
			 * @since 2.17.0
			 *
			 * @return {string|*[]} Cache items.
			 */
			getCache() {
				return JSON.parse( localStorage.getItem( this.cacheSettings.key ) ) ?? [];
			},

			/**
			 * Remove the cache.
			 *
			 * @since 2.17.0
			 */
			clearCache() {
				localStorage.removeItem( this.cacheSettings.key );
			},

			/**
			 * Cache using the local storage.
			 *
			 * @since 2.17.0
			 *
			 * @param {Object|[]} cache Items to cache.
			 */
			saveCache( cache ) {
				localStorage.setItem( this.cacheSettings.key, JSON.stringify( cache ) );
			},

			/**
			 * Retrieve the cache size in bytes.
			 *
			 * If the browser supports, we use TextEncoder to check the size more precisely, otherwise we assume
			 * an average size for each character in the string to rough estimate the size.
			 *
			 * @since 2.17.0
			 *
			 * @param {Object|[]} cache Items to cache.
			 *
			 * @return {number} The cache size in bytes.
			 */
			getCacheSize( cache ) {

				if ( typeof TextEncoder === 'function' ) {

					const encoder = new TextEncoder();

					// Get the number of bytes.
					return encoder.encode( JSON.stringify( cache ) ).length;
				}

				/*
				 UTF-8 encoding uses 1 byte for ASCII characters (0 to 127) and up to 4 bytes for other characters;
				 let's assume an average of 2 bytes per character as a rough estimate.
				 */
				return JSON.stringify( cache ).length * 2;
			},

			/**
			 * Ensure our cache operates always within the limits.
			 *
			 * Browsers can hold values between 5MB to 10MB in local storage,
			 * we operate within the lower limit by default.
			 *
			 * @since 2.17.0
			 *
			 * @param {Object|[]} cache Items to cache.
			 */
			enforceCacheSizeLimit( cache ) {

				let cacheSize = this.getCacheSize( cache );

				while ( cacheSize > this.cacheSettings.sizeLimit ) {

					if ( cache.length === 0 ) break;

					// Remove the oldest items from the cache.
					cache.shift();

					// Get the new cache size.
					cacheSize = this.getCacheSize( cache );
				}
			},

			/**
			 * Add a new item to the cache.
			 *
			 * @since 2.17.0
			 *
			 * @param {string} key Cache key.
			 * @param {string} value Cache value.
			 */
			setCacheItem( key, value ) {

				if ( this.getCacheItemByKey( key ) ) {
					return; // Bail if item already exists.
				}

				const cache = this.getCache();

				cache.push( { key, value } );

				// Ensure we are operating within the local storage size limits.
				this.enforceCacheSizeLimit( cache );

				this.saveCache( cache );
			},

			/**
			 * Find an item by key and remove it from the cache.
			 *
			 * @since 2.17.0
			 *
			 * @param {string} key The key to search for.
			 */
			removeCacheItemByKey( key ) {

				const cache = this.getCache();
				const index = cache.findIndex( item => item.key === key );

				if ( index === -1 ) {
					return;
				}

				// Remove the item found in cache.
				cache.splice( index, 1 );

				this.saveCache( cache );
			},

			/**
			 * Retrieve an item from cache by text.
			 *
			 * @since 2.17.0
			 *
			 * @param {string} key The text to find.
			 *
			 * @return {Object|null} The item found or null if item don't exists.
			 */
			getCacheItemByKey( key ) {

				const cache = this.getCache();

				return cache.find( item => item.key === key ) || null;
			},

			/**
			 * Parse the provided settings and ensures it has the expected types/values for determinant settings.
			 *
			 * @since 2.17.0
			 *
			 * @param {Object} settings The final settings.
			 */
			updateSettings( settings ) {

				if ( settings.hasOwnProperty( 'width' ) && ! Number.isInteger( settings.width ) ) {
					throw new Error( `Width must be an integer, got ${typeof settings.width}` );
				}

				if ( settings.hasOwnProperty( 'height' ) &&  ! Number.isInteger( settings.height ) ) {
					throw new Error( `Height must be an integer, got ${typeof settings.height}` );
				}

				if ( settings.hasOwnProperty( 'format' ) && ! ['svg', 'png'].includes( settings.format ) ) {
					throw new Error( `Expected svg or png formats, got ${settings.format}` );
				}

				this.settings = affiliatewp.parseArgs(
					settings,
					this.settings
				);
			},

			/**
			 * Retrieve the key to be used to query and save to the cache.
			 *
			 * Notice that cache key use some data from our settings to generate the key properly,
			 * so ensure you change the settings accordingly before calling this method.
			 *
			 * @since 2.17.0
			 *
			 * @param {string} text The text used to create the key.
			 *
			 * @return {string} The cache item key.
			 */
			getCacheItemKey( text ) {

				// We use the current settings to help generating a unique cache key.
				const data = { ...this.settings };

				// We don't need the events.
				delete data.on;

				// Append text as well.
				data.text = text;

				// Return a md5 encoded key.
				return affiliatewp.crypto( data ).md5();
			},

			/**
			 * Create a QR Code for a given text.
			 *
			 * The results can be used using the callback argument, allowing you to
			 * inject into the DOM, saving to the cache, etc.
			 *
			 * @since 2.17.0
			 *
			 * @param {string} text The text to create the QR Code.
			 * @param {string} type The format type: svg or png.
			 * @param {Function} callback A callback function when the QR Code is ready.
			 */
			createQRCode( text, type, callback ) {

				if ( typeof text !== 'string' ) {
					throw new Error( `Expected text, got ${text}.` );
				}

				// Check if we have an item in the cache if caching is enabled.
				const cacheItem = this.cacheSettings.enable
					? this.getCacheItemByKey( this.getCacheItemKey( text ) )
					: null;

				// Item in cache, just execute the callback.
				if ( cacheItem && typeof callback === 'function' ) {

					callback( cacheItem.value );

					return; // Execute the callback with the cached value and return.
				}

				// Make the settings object to be used with the 3rd party lib.
				const qrCodeLibSettings = { ...this.settings };

				// These settings don't belong to the external library.
				delete qrCodeLibSettings.format;
				delete qrCodeLibSettings.on;

				const options = {
					...qrCodeLibSettings,
					...{ type }
				};

				const qrCodeFunction = type === 'svg' ? QRCode.toString : QRCode.toDataURL;

				qrCodeFunction( text, options, ( err, result ) => {

					if ( err ) throw err;

					if ( typeof callback === 'function' ) {
						callback( result );
					}
				} );
			},

			/**
			 * Retrieve the SVG markup from a given text.
			 *
			 * The results can be used using the callback argument, allowing you to
			 * inject into the DOM, saving to the cache, etc.
			 *
			 * @since 2.17.0
			 *
			 * @param {string} text The text to create the QR Code.
			 * @param {Function} callback A callback function when the SVG is ready.
			 */
			createSVG( text, callback ) {
				this.createQRCode.call( this, text, 'svg', callback );
			},

			/**
			 * Retrieve the PNG text to be used with an image tag.
			 *
			 * @since 2.17.0
			 *
			 * @param {string} text The text to create the QR Code.
			 * @param {Function} callback A callback function when the SVG is ready.
			 */
			createPNG( text, callback ) {
				this.createQRCode.call(this, text, 'image/png', callback);
			},

			/**
			 * Generate a new QR Code.
			 *
			 * @since 2.17.0
			 *
			 * @param {Element} element A HTML element.
			 * @param {string} text The content to display.
			 * @param {Object} settings Additional settings. See @param settings for all available settings.
			 */
			generate( element, text, settings = {} ) {

				if ( ! ( element instanceof Element ) ) {
					throw new Error( `Expected Element, got ${typeof element}` );
				}

				// Set properties, so it can be accessible within this object.
				this.element = element;
				this.text = text;

				// Overwrite settings with the provided values, ensuring all expected types and values are set.
				this.updateSettings( settings );

				// Common routines to execute independently on the render method: SVG or PNG.
				const onComplete = ( text, output ) => {

					// Save the output, so it can be accessed through the object.
					this.code = output;

					// Cache the new item.
					this.setCacheItem( this.getCacheItemKey( text ), output );

					// Execute the on.load callback.
					if ( typeof this.settings.on.load === 'function' ) {
						this.settings.on.load( this );
					}

					// Add the initialized class to allow an easy way to identify rendered elements.
					this.element.classList.add( 'affwp-qrcode-initialized' );
				};

				try {

					// Resolve SVG format.
					if ( this.settings.format === 'svg' ) {

						this.createSVG(
							text,
							( output ) => {
								this.element.insertAdjacentHTML( 'afterbegin', output );
								onComplete( text, output );
							}
						);

						return;
					}

					// Resolve PNG format.
					this.createPNG(
						text,
						( output ) => {

							const img = document.createElement( 'img' );

							img.width = this.settings.width;
							img.height = this.settings.height;
							img.alt = 'QR Code';
							img.src = output;

							this.element.insertAdjacentElement( 'afterbegin', img );

							onComplete( text, output );
						}
					)

				} catch( err ) {

					// Output any errors to the console.
					console.error( err );

					// Execute error handlers.
					this.settings.on.error( err );
				}
			}
		}

		// Auto-initialize if an element and a text are provided.
		if ( element && text ) {
			qrCodeGenerator.generate( element, text, settings ? settings : {} );
		}

		return qrCodeGenerator;
	}
);

