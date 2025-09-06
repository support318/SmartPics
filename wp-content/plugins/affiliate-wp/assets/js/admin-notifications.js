/**
 * Handles the in-plugin notifications inbox.
 *
 * Sets up AlpineJS store and methods for notifications.
 *
 * @since 2.9.5
 */

/**
 * Global variable with data passed from WP when localizing the script.
 *
 * @since 2.9.5
 * @global
 *
 * @type object
 */
var affwp_notifications_vars;

document.addEventListener( 'alpine:init', function() {
	/**
	 * Notifications panel handler.
	 */
	Alpine.store( 'affwpNotifications', {
		/**
		 * Checks if the panel is open.
		 *
		 * @since 2.9.5
		 *
		 * @type bool
		 */
		isPanelOpen: false,

		/**
		 * Checks if notifications are loaded.
		 *
		 * @since 2.9.5
		 *
		 * @type bool
		 */
		notificationsLoaded: false,

		/**
		 * Gets the number of active notifications.
		 *
		 * @since 2.9.5
		 *
		 * @type int
		 */
		numberActiveNotifications: 0,

		/**
		 * Active notification data.
		 *
		 * @since 2.9.5
		 *
		 * @type array
		 */
		activeNotifications: [],

		/**
		 * Inactive notification data.
		 *
		 * @since 2.9.5
		 *
		 * @type array
		 */
		inactiveNotifications: [],

		/**
		 * Init.
		 *
		 * Initializes the AlpineJS instance.
		 *
		 * @since 2.9.5
		 *
		 * @return void
		 */
		init: function() {
			var affwpNotifications = this;

			/*
			 * The bubble starts out hidden until AlpineJS is initialized. Once it is, we remove
			 * the hidden class. This prevents a flash of the bubble's visibility in the event that there
			 * are no notifications.
			 */
			var notificationCountBubble = document.querySelector( '#affwp-notification-button .affwp-number' );
			
			if ( notificationCountBubble ) {
				notificationCountBubble.classList.remove( 'affwp-hidden' );
			}

			document.addEventListener( 'keydown', function( e ) {	
				if ( e.key !== 'Escape' ) {
					return;
				}
				
				affwpNotifications.closePanel();
			} );
		},

		/**
		 * Open panel.
		 *
		 * Opens the panel and gets notification data.
		 *
		 * @since 2.9.5
		 *
		 * @return void
		 */
		openPanel: function() {
			// Set for use in the api request.
			var affwpNotifications = this;
			var panelHeader        = document.getElementById( 'affwp-notifications-header' );

			affwpNotifications.isPanelOpen = true;

			if ( affwpNotifications.notificationsLoaded && panelHeader ) {
				panelHeader.focus();
				return;
			}

			// Request notification data.
			affwpNotifications.apiRequest( '/notifications', 'GET' )
				.then( function ( data ) {
					affwpNotifications.activeNotifications   = data.active;
					affwpNotifications.inactiveNotifications = data.dismissed;
					affwpNotifications.notificationsLoaded   = true;

					if ( panelHeader ) {
						panelHeader.focus();
					}
				} )
				.catch( function( error ) {
					// console.log( 'Notification error', error );
				} );
		},

		/**
		 * Close panel.
		 *
		 * Closes the panel.
		 *
		 * @since 2.9.5
		 *
		 * @return void
		 */
		closePanel: function() {
			var affwpNotifications = this;

			if ( ! affwpNotifications.isPanelOpen ) {
				return;
			}

			affwpNotifications.isPanelOpen = false;

			var notificationButton = document.getElementById( 'affwp-notification-button' );
			if ( ! notificationButton ) {
				return;
			}
			
			notificationButton.focus();
		},

		/**
		 * API Request.
		 *
		 * Gets the data for the notifications.
		 *
		 * @since 2.9.5
		 *
		 * @return void
		 */
		apiRequest: function( endpoint, method ) {
			return fetch( affwp_notifications_vars.restBase + endpoint, {
				method: method,
				credentials: 'same-origin',
				headers: {
					'Content-Type': 'application/json',
					'X-WP-Nonce': affwp_notifications_vars.restNonce
				}
			} ).then( function ( response ) {
				if ( ! response.ok ) {
					return Promise.reject( response );
				}

				/*
				 * Returning response.text() instead of response.json() because dismissing
				 * a notification doesn't return a JSON response, so response.json() will break.
				 */
				return response.text();
			} ).then( function ( data ) {
				return data ? JSON.parse( data ) : null;
			} );
		} ,

		/**
		 * Dismiss.
		 *
		 * Dismisses a notification from the inbox.
		 *
		 * @since 2.9.5
		 *
		 * @return void
		 */
		dismiss: function( event, index ) {
			// Set for use in the api request.
			var affwpNotifications = this;

			if ( 'undefined' === typeof affwpNotifications.activeNotifications[ index ] ) {
				return;
			}

			event.target.disabled = true;

			var notification = affwpNotifications.activeNotifications[ index ];

			// Remove notification from the active list.
			affwpNotifications.apiRequest( '/notifications/' + notification.id, 'DELETE' )
				.then( function ( response ) {
					affwpNotifications.activeNotifications.splice( index, 1 );
					affwpNotifications.numberActiveNotifications = affwpNotifications.activeNotifications.length;
				} )
				.catch( function( error ) {
					// console.log( 'Dismiss error', error );
				} );
		}
	} );
} );
