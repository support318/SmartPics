/**
 * AffiliateWP JavaScript Namespace
 *
 * All affiliatewp JS utilities scripts should be attached to our namespace for easy access.
 *
 * @since 2.15.0
 * @since 2.26.0 Updated to follow WordPress Coding Standards.
 * @since 2.26.0 Updated so anytime `affiliatewp` is enqueued
 *               (or added as a requirement), this script is loaded.
 */

/* globals window, console */

/* eslint-disable jsdoc/check-param-names */
/* eslint-disable template-curly-spacing */
/* eslint-disable padded-blocks */
/* eslint-disable no-console, no-unused-vars */
/* eslint-disable jsdoc/check-line-alignment */

const affiliatewp = window.affiliatewp || {

	/**
	 * Check if the resource exists and attach to the affiliatewp object.
	 *
	 * @since 2.15.0
	 * @since 2.26.0 Update to allow a main resource and external resource to be merged together.
	 * @since AFFWPN Updated to work as intended: pass string of window[ name ] object to destroy, e.g.
	 *               `.attach( 'myTool', myObject, {}, 'myObject' )`, which will attach the resource
	 *               to `window.affiliatewp`, but delete `window[ 'myObject' ]` after doing so. This
	 *               allows you to localize your object using `wp_localize_object()` in PHP and pass that
	 *               object as the `resource` to move to `affiliatewp` and delete the global object
	 *               if you want to.
	 *
	 * @param {string} name The resource name to be attached.
	 * @param {Object} resource An object to attach to affiliatewp.
	 * @param {Object} localizedData An external resource to merge with the main resource (so you can migrate e.g. localized data in).
	 * @param {Function} windowObjectToDelete Name of a `window[ String name ]` object to delete after attaching ie you localize `window.myObject`
	 *                                        in PHP and want to attach it to `window.affiliatewp`, you can delete the global object after it's been
	 *                                        attached to `affiliatewp`.
	 *
	 * @throws {Error} If the resource was already specified, this will throw an error.
	 */
	attach(
		name,
		resource = {},
		localizedData = {},
		windowObjectToDelete = false
	) {

		if ( this.hasOwnProperty( name ) ) {
			throw new Error( `Resource '${name}' is already registered in the affiliatewp object.` );
		}

		this[ name ] = this.extend( resource, localizedData );

		if ( 'string' !== typeof windowObjectToDelete ) {
			return;
		}

		if ( ! window.hasOwnProperty( windowObjectToDelete ) ) {
			return;
		}

		if ( ! delete window[ name ] ) {
			window[ name ] = null; // Set to null when we cannot delete it.
		}
	},

	/**
	 * Extend two objects.
	 *
	 * @since 2.26.0
	 *
	 * @param {Object} object1 First Object.
	 * @param {Object} object2 Second Object.
	 *
	 * @return {Object} Extended Object.
	 */
	extend(
		object1 = {},
		object2 = {}
	) {
		return Object.assign( object1, object2 );
	},

	/**
	 * Remove a resource (object, function, property) from affiliatewp object.
	 *
	 * @since 2.15.0
	 *
	 * @param {string} name The resource name to be removed.
	 *
	 * @return {*} Return the resource or null if resource was not found.
	 */
	detach( name ) {

		if ( ! this.hasOwnProperty( name ) ) {
			return null;
		}

		const resource = this[ name ];

		if ( ! delete this[ name ] ) {
			this[ name ] = null; // Set to null when we cannot delete it.
		}

		return resource; // Return the resource, so it still can be assigned.
	},

	/**
	 * Check if a resource exists.
	 *
	 * @since 2.16.0
	 *
	 * @param {string} name The resource name.
	 *
	 * @return {boolean} Whether the resource is enabled or not.
	 */
	has( name ) {
		return this.hasOwnProperty( name );
	},

	/**
	 * Merge two objects. Similar to wp_parse_args() function.
	 *
	 * Note that only properties existing in the second parameter will be considered.
	 * If `args` contains properties that `defaults` doesn't have, those properties will be ignored.
	 *
	 * @since 2.16.0
	 *
	 * @param {Object} args Args to be merged/replace.
	 * @param {Object} defaults Default args.
	 *
	 * @return {Object} The new object.
	 */
	parseArgs( args, defaults = {} ) {

		if ( typeof args !== 'object' || typeof defaults !== 'object' ) {

			// This would not stop execution, but it needs to be logged for debug purposes.
			console.error( 'You must provide two valid objects' );

			return {}; // Not able to parse, return an empty object.
		}

		const mergeObjects = ( arg, def ) => {

			for ( const key in arg ) {

				const hasKey = arg.hasOwnProperty( key );

				// If hasKey doesn't exist, the property will be ignored, otherwise we replace in our object.
				if ( hasKey && typeof arg[ key ] === 'object' && typeof def[ key ] === 'object' ) {
					mergeObjects( arg[ key ], def[ key ] );
				} else if ( hasKey ) {
					def[ key ] = arg[ key ];
				}
			}

			return def;
		};

		return mergeObjects( args, defaults );
	},
};
