/* global affiliate_wp_setup_screen */

/**
 * Handles the Setup Screen that follows the Onboarding Wizard.
 *
 * @since 2.13.0
 */
( function( document, window, $ ) {
	'use strict';

	/**
	 * Elements.
	 *
	 * @since 2.13.0
	 *
	 * @type {object}
	 */
	var el = {};

	/**
	 * Public functions and properties.
	 *
	 * @since 2.13.0
	 *
	 * @type {object}
	 */
	var app = {
		/**
		 * Start the engine.
		 *
		 * @since 2.13.0
		 */
		init: function() {

			$( app.ready );
		},

		/**
		 * Document ready.
		 *
		 * @since 2.13.0
		 */
		ready: function() {

			app.initVars();
			app.events();
		},

		/**
		 * Init variables.
		 *
		 * @since 2.13.0
		 */
		initVars: function() {

			el = {
				$stepAddYourself: $( 'section.step-add-yourself' ),
				$stepAddYourselfNum: $( 'section.step-add-yourself .num img' ),
				$stepPortalAddon: $( 'section.step-portal-addon' ),
				$stepPortalAddonNum: $( 'section.step-portal-addon .num img' ),
			};
		},

		/**
		 * Register JS events.
		 *
		 * @since 2.13.0
		 */
		events: function() {
			// Step 'Add Yourself' button click.
			el.$stepAddYourself.on( 'click', 'button', app.stepAddYourselfClick );
			// Step 'Portal Addon' button click.
			el.$stepPortalAddon.on( 'click', 'button', app.stepPortalAddonClick );
		},
		/**
		 * Step 'Add Yourself' button click.
		 *
		 * @since 2.13.0
		 */
		stepAddYourselfClick: function() {

			var $btn          = $( this ),
				$error        = $( this ).next( 'p' ),
				btnTextOrigin = $btn.text(),
				ajaxAction    = 'affwp_add_yourself_step';

			// Bail if button is disabled.
			if ( $btn.hasClass( 'disabled' ) ) {
				return;
			}

			// Reset error if there is one.
			if ( $error.length > 0 ) {
				$error.remove();
			}

			// Disable and temporarily change button text.
			$btn.text( affiliatewpSetupScreen.adding )
				.addClass( 'disabled' );

			// Show spinner.
			app.showSpinner( el.$stepAddYourselfNum );

			$.post(
				affiliatewpSetupScreen.ajax_url,
				{
					action: ajaxAction,
					nonce: affiliatewpSetupScreen.nonce,
				}
			)
				.done(
					function( res ) {
						// If unsuccessful, return text to original status and show error.
						if ( ! res.success ) {
							$btn.text( btnTextOrigin )
								.removeClass( 'disabled' )
								.parent()
								.after( '<p class="error">' + affiliatewpSetupScreen.setup_screen_error + '</p>' );
							return;
						}

						// Otherwise, update the button text and keep it disabled.
						$btn.text( affiliatewpSetupScreen.add_affiliate_step_complete )
							.addClass( 'grey' )
							.removeClass( 'button-primary' );

						// Display number as completed.
						el.$stepAddYourselfNum.attr(
							'src',
							el.$stepAddYourselfNum
								.attr( 'src' )
								.replace( 'step-2.', 'step-complete.' )
						);
						// Reload so we can display the edit link.
						location.reload();
					}
				)
				.always(
					function() {
						app.hideSpinner( el.$stepAddYourselfNum );
					}
				);
		},

		/**
		 * Step 'Portal Addon' button click.
		 *
		 * @since 2.13.0
		 */
		stepPortalAddonClick: function() {

			var $btn          = $( this ),
				action        = $btn.attr( 'data-action' ),
				plugin        = $btn.attr( 'data-plugin' ),
				btnTextOrigin = $btn.text(),
				ajaxAction    = '';

			// Bail if button is disabled.
			if ( $btn.hasClass( 'disabled' ) ) {
				return;
			}

			// Proceed based on action: activate, install, or go to the url.
			switch ( action ) {
				case 'activate':
					ajaxAction = 'affwp_activate_plugin';
					$btn.text( affiliatewpSetupScreen.activating );
					break;

				case 'install':
					ajaxAction = 'affwp_install_plugin';
					$btn.text( affiliatewpSetupScreen.installing );
					break;

				case 'goto-url':
					window.location.href = $btn.attr( 'data-url' );
					return;

				default:
					return;
			}

			// Disable button.
			$btn.addClass( 'disabled' );

			// Show spinner.
			app.showSpinner( el.$stepPortalAddonNum );

			$.post(
				affiliatewpSetupScreen.ajax_url,
				{
					action: ajaxAction,
					nonce: affiliatewpSetupScreen.nonce,
					plugin: plugin
				}
			)
				.done(
					function( res ) {
						app.stepPortalAddonDone( res, $btn, action );
					}
				)
				.fail(
					function() {
						$btn.removeClass( 'disabled' )
							.text( btnTextOrigin );
					}
				)
				.always(
					function() {
						app.hideSpinner( el.$stepPortalAddonNum );
					}
				);
		},

		/**
		 * Done part of the 'Portal Addon' step.
		 *
		 * @since 2.13.0
		 *
		 * @param {object} res    Result of $.post() query.
		 * @param {jQuery} $btn   Button.
		 * @param {string} action Action (for more info look at the app.stepPortalAddonClick() function).
		 */
		stepPortalAddonDone: function( res, $btn, action ) {

			// If installing or activation was successful.
			if ( 'install' === action ? res.success && res.data.is_activated : res.success ) {

				// Update the button text and keep it disabled.
				$btn.text( affiliatewpSetupScreen.portal_step_complete )
					.addClass( 'grey' )
					.removeClass( 'button-primary' );

				// Display number as completed.
				el.$stepPortalAddonNum.attr(
					'src',
					el.$stepPortalAddonNum
						.attr( 'src' )
						.replace( 'step-3.', 'step-complete.' )
				);

				return;
			}

			// If unsuccessful, provide install/activation error and link button to manually download or activate.
			var activationFail = ( 'install' === action && res.success && ! res.data.is_activated ) || 'activate' === action,
				url            = ! activationFail ? affiliatewpSetupScreen.manual_install_url : affiliatewpSetupScreen.manual_activate_url,
				msg            = ! activationFail ? affiliatewpSetupScreen.error_could_not_install : affiliatewpSetupScreen.error_could_not_activate,
				btn            = ! activationFail ? affiliatewpSetupScreen.download_now : affiliatewpSetupScreen.plugins_page;

			$btn.removeClass( 'disabled' )
				.text( btn )
				.attr( 'data-action', 'goto-url' )
				.attr( 'data-url', url )
				.after( '<p class="error">' + msg + '</p>' );
		},

		/**
		 * Display spinner.
		 *
		 * @since 2.13.0
		 *
		 * @param {jQuery} $el Section number image jQuery object.
		 */
		showSpinner: function( $el ) {
			$el.siblings( '.loader' ).removeClass( 'hidden' );
		},

		/**
		 * Hide spinner.
		 *
		 * @since 2.13.0
		 *
		 * @param {jQuery} $el Section number image jQuery object.
		 */
		hideSpinner: function( $el ) {
			$el.siblings( '.loader' ).addClass( 'hidden' );
		}
	};

	// Provide access to public functions/properties.
	return app;

}( document, window, jQuery ) ).init();
