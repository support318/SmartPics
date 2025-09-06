/**
 * Creative Privacy
 *
 * This file mostly controls the UI when doing the edit/adding of creatives.
 *
 * When Public is set, it clears (and hides) the Select2's (making it public), when
 * Private it shows the fields and let's them select who.
 *
 * @since 2.15.0
 */

/* globals jQuery */

( function () {

	if ( ! window.hasOwnProperty( 'jQuery' ) ) {
		return;
	}

	jQuery( document ).ready( function() {

		/**
		 * Get the parent field for a select.
		 *
		 * @since 2.15.0
		 *
		 * @param {Object} selectElement Select Element from Select2.
		 *
		 * @return {boolean|Object} False if none, or element.
		 */
		function getSelectField( selectElement ) {

			if ( ! selectElement.length ) {
				return false;
			}

			const fieldElement = selectElement.parent().parent();

			if ( ! fieldElement.hasClass( 'form-row' ) ) {
				return false;
			}

			return fieldElement;
		}

		/**
		 * Is a select a privacy select element?
		 *
		 * @since 2.15.0
		 *
		 * @param {Object} selectElement Select Element from Select2.
		 *
		 * @return {boolean} True if it is, false if not.
		 */
		function isPrivacySelect( selectElement ) {

			if ( ! selectElement.length ) {
				return false;
			}

			return -1 !== selectElement.attr( 'id' ).indexOf( 'privacy:' );
		}

		/**
		 * Reset a Select2 Element.
		 *
		 * @since 2.15.0
		 *
		 * @param {Object} selectElement Select2 jQuery Element.
		 *
		 * @return {void}
		 */
		function resetSelect( selectElement ) {

			if ( ! selectElement.length ) {
				window.console.error( 'selectElement is not an element.' );
			}

			selectElement.val( '' ).trigger( 'change' );
		}

		/**
		 * Initialize (and return) the Privacy Toggle Element.
		 *
		 * @since 2.15.0
		 *
		 * @return {boolean|Object} Object of the privacy toggle, false otherwise.
		 */
		function initializePrivacyToggle() {

			/**
			 * Toggle Element for selecting Private/Public.
			 *
			 * @type {Object}
			 */
			const privacyToggleElement = jQuery( '#creative-privacy' );

			if ( ! privacyToggleElement.length ) {
				return false;
			}

			/**
			 * Row element for the Private/Public selector.
			 *
			 * @type {Object}
			 */
			const trElement = privacyToggleElement.parent().parent();

			if ( trElement.length ) {
				trElement.removeClass( 'hidden' );
			}

			return privacyToggleElement;
		}

		/**
		 * Initialize the Privacy Fields.
		 *
		 * @since 2.15.0
		 *
		 * @param {Object} selectElements       The Select2 elements on the page.
		 * @param {Object} privacyToggleElement The privacy toggle.
		 *
		 * @return {void}
		 */
		function initializePrivacyFields( selectElements, privacyToggleElement ) {

			// The initial state of the UI...
			if ( 'public' !== privacyToggleElement.val() ) {
				return; // We should be hiding the fields already when public.
			}

			jQuery( selectElements ).each( function( index, select ) {

				const selectElement = jQuery( select );

				if ( ! isPrivacySelect( selectElement ) ) {
					return;
				}

				const fieldElement = getSelectField( selectElement );

				if ( ! fieldElement ) {
					return;
				}

				fieldElement.hide();

				resetSelect( selectElement );
			} );
		}

		/**
		 * Watch for changes (and make UI changes) when Privacy toggle changes.
		 *
		 * @since 2.15.0
		 *
		 * @param {Object} selectElements       Select2 Elements on the page.
		 * @param {Object} privacyToggleElement The privacy toggle element.
		 *
		 * @return {void}
		 */
		function watchPrivacyToggle( selectElements, privacyToggleElement ) {

			if ( ! privacyToggleElement.length ) {
				return;
			}

			if ( ! selectElements.length ) {
				return;
			}

			// When the Private/Public toggle changes...
			privacyToggleElement.on( 'change', function() {

				// Each <select>...
				jQuery( selectElements ).each( function( index, select ) {

					const selectElement = jQuery( select );

					if ( ! isPrivacySelect( selectElement ) ) {
						return;
					}

					const fieldElement = getSelectField( selectElement );

					if ( false === fieldElement ) {
						return;
					}

					// When it's public, hide the fields and reset them to nothing so, on save, they are indeed public.
					if ( 'public' === privacyToggleElement.val() ) {

						fieldElement.hide();

						resetSelect( selectElement );

						return;
					}

					// When private, show the fields.
					fieldElement.show();
				} );
			} );
		}

		/**
		 * Change the UI State when the Privacy toggle is changed.
		 *
		 * @since 2.15.0
		 *
		 * @param {Object} privacyToggleElement The privacy toggle jQuery element.
		 *
		 * @return {void}
		 */
		function initialize( privacyToggleElement ) {

			if ( ! privacyToggleElement ) {
				return;
			}

			if ( ! privacyToggleElement.length ) {
				return;
			}

			// When the Select2's are setup...
			jQuery( document ).on( 'affwp-select2-init', function( event, selectElements ) {

				initializePrivacyFields( selectElements, privacyToggleElement );
				watchPrivacyToggle( selectElements, privacyToggleElement )
			} );
		}

		initialize( initializePrivacyToggle() );

	} );
} ) ();