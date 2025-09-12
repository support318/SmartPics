/* global AffiliateWPEducation, affiliatewp_education */
/**
 * AffiliateWP DRM.
 *
 * @since 2.21.1
 */

'use strict';

AffiliateWPEducation.drm = AffiliateWPEducation.drm || ( function( document, window, $ ) {

	/**
	 * Public functions and properties.
	 *
	 * @since 2.21.1
	 *
	 * @type {Object}
	 */
	const app = {

		/**
		 * Start the engine.
		 *
		 * @since 2.21.1
		 */
		init() {

			$( app.ready );
		},

		/**
		 * Document ready.
		 *
		 * @since 2.21.1
		 */
		ready() {

			$( '#wpbody' ).append( '<div id="affwp-drm-modal"></div>' );

			app.drmModal();

			app.handleLicenseFormSubmission();
		},

		/**
		 * DRM modal.
		 *
		 * @since 2.21.1
		 */
		drmModal() {

			const modal = $.alert( {
				backgroundDismiss: false,
				title            : affiliatewp_education.drm.title,
				icon             : 'fa fa-exclamation-triangle',
				content          : affiliatewp_education.drm.message,
				boxWidth         : app.getDrmModalWidth(),
				container        : '#affwp-drm-modal',
				useBootstrap     : false,
				theme            : 'modern,affiliatewp-education',
				closeIcon        : false,
				buttons          : false,
				animation        : 'none'
			} );

			$( window ).on( 'resize', function() {

				if ( ! modal.isOpen() ) {
					return;
				}
				
				modal.setBoxWidth( app.getDrmModalWidth() );
			} );
		},

		/**
		 * Get DRM modal width.
		 *
		 * @since 2.21.1
		 *
		 * @return {string} Modal width in pixels.
		 */
		getDrmModalWidth() {

			const windowWidth = $( window ).width();

			if ( windowWidth <= 300 ) {
				return '250px';
			}

			if ( windowWidth <= 750 ) {
				return '450px';
			}

			if ( windowWidth <= 1024 ) {
				return '650px';
			}

			return windowWidth > 1070 ? '850px' : '650px';
		},

		/**
		 * Handle AJAX requests coming from the License Form submissions.
		 *
		 * @since 2.21.1
		 */
		handleLicenseFormSubmission() {

			/**
			 * Simple method to help displaying ajax feedbacks.
			 *
			 * Messages will be displayed beneath the form tag.
			 *
			 * @since 2.21.1
			 *
			 * @param {string} message The message to display.
			 * @param {string} type The type of the message (success, error)
			 */
			const showMessage = ( message, type = 'error' ) => {

				const $modalContainer = $( '#affwp-drm-modal .jconfirm-content' );

				let $messageContainer = $modalContainer.find( '#affwp-drm-ajax-messages' );

				if ( ! $messageContainer.length ) {

					$messageContainer = $( '<div id="affwp-drm-ajax-messages"></div>' );
					$modalContainer.find( 'form' ).after( $messageContainer );
				}

				$messageContainer
					.attr( 'data-type', type === 'success' ? 'success' : 'error' )
					.html( message );
			}

			$( document ).on( 'submit', '#affwp-drm-ajax-license-activation', function( event ) {

				event.preventDefault();
				event.stopPropagation();

				const $form = $( this );
				const $submitBtn = $form.find( 'button' );

				let buttonText = affiliatewp_education.drm.ajax.buttonText;

				$.ajax( {
					type : 'POST',
					url  : affiliatewp_education.ajax_url,
					data : {
						nonce: affiliatewp_education.nonce,
						action: 'affiliatewp_handle_license_form_submission',
						license_key: $form.find( 'input[name="license_key"]' ).val()
					},
					beforeSend: function() {

						$submitBtn
							.attr( 'disabled', true )
							.html( AffiliateWPEducation.core.getSpinner() + buttonText );
					},
					success: function ( response ) {

						if ( ! response.success ) {
							// Probably an expired nonce or unknown error.
							showMessage( affiliatewp_education.drm.ajax.error );
							return;
						}

						const licenseStatus = response?.data?.license_data?.license;

						switch ( licenseStatus ) {
							case 'valid':
								showMessage( affiliatewp_education.drm.ajax.success, 'success' );
								setTimeout( () => {
									location.reload();
								}, 3000 );
								break;

							case 'expired':
								showMessage( affiliatewp_education.drm.ajax.expired );
								break;

							case 'invalid':
								showMessage( affiliatewp_education.drm.ajax.invalid );
								break;

							default:

								// Our License API can not be reached.
								if ( response?.data?.affwp_notice === 'license-http-failure' ) {

									console.log(affiliatewp_education.drm.ajax.licenseHttpFailure.message)

									// Alternative message for http failed requests.
									showMessage( affiliatewp_education.drm.ajax.licenseHttpFailure.message );

									// Update the button text variable, the value will be outputed on ajax.complete().
									buttonText = affiliatewp_education.drm.ajax.licenseHttpFailure.buttonText;
									return;
								}

								// Display a generic error message.
								showMessage( affiliatewp_education.drm.ajax.onError );
						}
					},
					error: function() {
						showMessage( affiliatewp_education.drm.ajax.onError );
					},
					complete: function() {

						$submitBtn
							.removeAttr( 'disabled' )
							.html( buttonText );
					}
				} );
			});
		},
	};

	// Provide access to public functions/properties.
	return app;

}( document, window, jQuery ) );

// Initialize.
AffiliateWPEducation.drm.init();

