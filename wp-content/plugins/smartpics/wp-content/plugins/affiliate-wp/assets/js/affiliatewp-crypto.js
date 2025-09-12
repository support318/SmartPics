/**
 * Provides encryption functionality for handling data security.
 *
 * This module offers methods for encrypting various types of data, including strings, arrays, and objects.
 * It also provides options for different encryption types, such as MD5 hashing and base64 encoding/decoding.
 *
 * @author Darvin da Silveira <ddasilveira@awesomemotive.com>
 * @since 2.17.0
 * @since AFFWPN `windowObjectToDelete` parameter is still false, but won't delete `window.crypto` anymore due
 *               to improvements to `window.affiliatewp.attach()`.
 */

/* eslint-disable padded-blocks */

'use strict';

/* eslint-disable no-console, no-undef, no-shadow */
affiliatewp.attach(
	'crypto',
	/**
	 * Encrypts data, supporting strings, arrays, or objects, and provides methods for different encryption types.
	 *
	 * @since 2.17.0
	 *
	 * @param {string|Array|Object} data Data to be encrypted.
	 *
	 * @throws {Error} If the data type is not supported.
	 *
	 * @return {Object} An object with encryption methods.
	 */
	function( data ) {

		if ( typeof data !== 'string' && ! Array.isArray( data ) && ! ( data instanceof Object ) ) {
			throw new Error( `Expected string, array, or object, got ${ typeof data }` );
		}

		if ( typeof data !== 'string' ) {
			data = JSON.stringify( data );
		}

		return {
			/**
			 * Computes the MD5 hash of the data.
			 *
			 * This method calculates the MD5 hash of the input data and returns the hash value.
			 *
			 * @since 2.17.0
			 *
			 * @throws {Error} If the MD5 function is not available.
			 *
			 * @return {string} The MD5 hash of the data.
			 */

			md5: () => {

				if ( typeof MD5 !== 'function' ) {
					throw new Error( `MD5 function not found.` );
				}

				// eslint-disable-next-line no-undef
				return MD5( data );
			},
			/**
			 * Encodes the data using base64.
			 *
			 * This method encodes the input data using base64 encoding and returns the encoded string.
			 *
			 * @since 2.17.0
			 *
			 * @return {string} The base64-encoded data.
			 */

			base64Encode: () => {
				return btoa( data );
			},
			/**
			 * Decodes base64-encoded data.
			 *
			 * This method decodes the base64-encoded input data and returns the original data.
			 *
			 * @since 2.17.0
			 *
			 * @return {string} The decoded data.
			 */

			base64Decode: () => {
				return atob( data );
			},
		};
	}
);

