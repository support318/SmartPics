/**
 * Frontend Notifications
 *
 * @since  2.23.0
 * @author Andrew Munro <amunro@awesomemotive.com>
 */

/* globals HTMLElement, affiliateWPFENotifications, customElements */

/**
 * Represents a custom notification element for AffiliateWP.
 *
 * @since 2.23.0
 *
 * This class extends HTMLElement to create a custom web component for displaying notifications.
 * It includes functionality to dynamically set the theme based on admin settings,
 * handle animations, and manage the display of notifications based on certain conditions.
 */
class AffiliateWPNotification extends HTMLElement {

	/**
	 * Constructor
	 *
	 * @since 2.23.0
	 *
	 * Constructs the AffiliateWPNotification element, initializing its shadow DOM
	 * and cloning the template content into it.
	 */
	constructor() {

		super();

		this.attachShadow( { mode: 'open' } );

		const template = document.getElementById( 'affwp-notification' );

		if ( ! template ) {

			window.console.error( 'Template not found' );
			return;
		}

		this.shadowRoot.appendChild( template.content.cloneNode( true ) );

		// Find and store references to dynamic parts of the template.
		this.notificationDiv = this.shadowRoot.querySelector( '#notification' );
	}

	/**
	 * Lifecycle hook called when the element is added to the document's DOM.
	 *
	 * @since 2.23.0
	 *
	 * It sets the theme based on admin settings, initializes CSS styling,
	 * and sets up animations and event listeners for the notification element.
	 */
	connectedCallback() {

		// Set the data-mode attribute based on the admin setting.
		const notificationDiv      = this.shadowRoot.querySelector( '#notification' );
		const adminDarkModeSetting = affiliateWPFENotifications.darkMode;

		let useDarkMode = false;

		if ( adminDarkModeSetting === 'dark' ) {
			useDarkMode = true;
		} else if ( adminDarkModeSetting === 'auto' ) {
			useDarkMode = window.matchMedia( '(prefers-color-scheme: dark)' ).matches;
		}

		if ( notificationDiv ) {

			if ( useDarkMode ) {
				notificationDiv.setAttribute( 'data-mode', 'dark' );
			} else {
				notificationDiv.removeAttribute( 'data-mode' );
			}
		}

		// Create a link element for the CSS.
		const cssLink = document.createElement( 'link' );

		cssLink.setAttribute( 'rel', 'stylesheet' );
		cssLink.setAttribute( 'type', 'text/css' );

		if ( affiliateWPFENotifications && affiliateWPFENotifications.cssUrl ) {
			cssLink.setAttribute( 'href', affiliateWPFENotifications.cssUrl );
		}

		this.shadowRoot.appendChild( cssLink );

		// Animation on mount.
		const notification = this.shadowRoot.getElementById( 'notification' );

		if ( notification ) {
			notification.style.animation = 'slideIn 0.3s forwards';
		}

		// Event listener for close button
		const closeButton = this.shadowRoot.querySelector( '#close-notification' );

		if ( closeButton ) {

			closeButton.addEventListener( 'click', () => {

				notification.style.animation = 'slideOut 0.3s forwards';

				setTimeout( () => this.remove(), 300 ); // Remove after animation.
			} );
		}
	}
}

( function() {

	customElements.define( 'affiliatewp-notification', AffiliateWPNotification );

	/**
	 * Extracts the affiliate identifier from the current URL.
	 *
	 * @since 2.23.0
	 *
	 * Attempts to retrieve the affiliate ID using the query parameter first; if not found,
	 * falls back to parsing the URL's path segments.
	 *
	 * @return {string|null} The affiliate identifier if found, otherwise null.
	 */
	function getAffiliate() {

		const urlParams = new URLSearchParams( window.location.search );
		let affiliate = urlParams.get( affiliateWPFENotifications.referralVar );

		if ( ! affiliate ) {

			// For pretty permalink structure.
			const pathArray = window.location.pathname.split( '/' );
			const refIndex = pathArray.indexOf( affiliateWPFENotifications.referralVar );

			if ( refIndex > -1 && pathArray.length > refIndex + 1 ) {
				affiliate = pathArray[ refIndex + 1 ];
			}
		}

		return affiliate;
	}

	/**
	 * Creates and appends a custom notification element to the body of the document.
	 *
	 * @since 2.23.0
	 *
	 * This function is typically called in response to a specific event, such as a successful
	 * application of an affiliate coupon.
	 */
	function showCustomNotification() {
		document.body.appendChild( document.createElement( 'affiliatewp-notification' ) );
	}

	/**
	 * Event listener for the DOMContentLoaded event.
	 *
	 * @since 2.23.0
	 *
	 * Fetches and applies an affiliate coupon if an affiliate identifier is present in the URL.
	 * Upon successful application, displays a custom notification.
	 */
	document.addEventListener( 'DOMContentLoaded', () => {

		const affiliate = getAffiliate();

		if ( affiliate ) {

			const params = new URLSearchParams( {
				action: 'apply_affiliate_coupon',
				affiliate
			} );

			fetch( `${affiliateWPFENotifications.ajaxUrl}?${params.toString()}`, {
				method: 'POST',
				credentials: 'same-origin'
			} )

			.then( response => response.json() )

			.then( data => {
				if ( data.couponApplied ) {
					showCustomNotification();
				}
			} )

			.catch( error => window.console.error( 'Error:', error ) );
		}
	} );
} () );
