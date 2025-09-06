/**
 * Admin addons page functionality.
 *
 * Allows user to install and activate or deactivate plugins.
 *
 * @since 2.9.6
 */

( function( document, window, $ ) {
	'use strict';

	/**
	 * Public functions and properties.
	 *
	 * @since 2.9.6
	 *
	 * @type {object}
	 */
	var app = {

		/**
		 * Start the engine.
		 *
		 * @since 2.9.6
		 */
		init: function() {

			$( app.ready );

		},

		/**
		 * Document ready.
		 *
		 * @since 2.9.6
		 */
		ready: function() {

			app.events();

		},

		/**
		 * Register JS events.
		 *
		 * @since 2.9.6
		 */
		events: function() {

			// 'Activate' button click.
			$( 'button.affwp-styled-checkbox' ).on( 'click', '', app.addonToggleClick );

		},

		/**
		 * Addon Toggle click.
		 *
		 * @since 2.9.6
		 */
		addonToggleClick: function() {

			var $toggle          = $( this ).find('input'),
				$toggleLabel     = $( this ).find( 'span' ),
				$toggleStatus    = $( this ).prev( 'p.affwp-status' ),
				action           = $toggle.attr( 'data-action' ),
				plugin           = $toggle.attr( 'data-plugin' ),
				plugin_id        = $toggle.attr( 'data-plugin-id' ),
				toggleTextOrigin = $toggleLabel.text(),
				toggleTextNew    = affwp_admin_addons_vars.deactivate,
				toggleStatusNew  = '',
				ajaxAction       = '';

			if ( $toggle.hasClass( 'disabled' ) ) {
				return;
			}

			// Adjust ajax action and toggle label text based on the addon's action.
			switch ( action ) {
				case 'install':

					ajaxAction      = 'affwp_install_addons_page_plugin';
					toggleStatusNew = affwp_admin_addons_vars.status_active;
					$toggleLabel.text( affwp_admin_addons_vars.installing );
					break;

				case 'activate':

					ajaxAction      = 'affwp_activate_addons_page_plugin';
					toggleStatusNew = affwp_admin_addons_vars.status_active;
					$toggleLabel.text( affwp_admin_addons_vars.activating );
					break;

				case 'deactivate':

					ajaxAction      = 'affwp_deactivate_addons_page_plugin';
					toggleTextNew   = affwp_admin_addons_vars.activate;
					toggleStatusNew = affwp_admin_addons_vars.status_inactive;
					$toggleLabel.text( affwp_admin_addons_vars.deactivating );
					break;

				default:
					return;
			}

			// Disable to prevent re-toggling before the POST is complete.
			$toggle.attr( 'disabled', true );

			$.post(
				affwp_admin_addons_vars.ajax_url,
				{
					action: ajaxAction,
					nonce: affwp_admin_addons_vars.nonce,
					plugin: plugin,
					addonID: plugin_id,
				}
			)
				.done(
					function( res ) {

						// Check or uncheck the toggle.
						if ( $toggle.hasClass( 'checked' ) ) {
							$toggle.removeClass( 'checked' );
						} else {
							$toggle.addClass( 'checked' );
						}
						// Update toggle text and status.
						$toggleLabel.text( toggleTextNew );
						$toggleStatus.html( toggleStatusNew );

						app.addonToggleDone( res, $toggle, action );
					}
				)
				.fail(
					function() {
						// Get error message.
						var msg = app.addonErrorMsg( action );

						// Revert toggle text to origin, leave disabled, and add error.
						$toggleLabel.text( toggleTextOrigin )
							.parent()
							.after( '<p class="affwp-addon-error">' + msg + '</p>' );
					}
				)

		},
		/**
		 * Handle being done with the toggle.
		 *
		 * @since 2.9.6
		 *
		 * @param {object} res    Result of $.post() query.
		 * @param {jQuery} toggle Toggle.
		 * @param {string} action Action (for more info look at the app.addonToggleClick() function).
		 */
		addonToggleDone: function( res, $toggle, action ) {

			// Check if it's a successful install or activation action.
			if ( ( 'install' === action || 'activate' === action ) && res.success ) {
				$toggle.attr( { 'disabled': false, 'data-action': 'deactivate' } );
				return;
			}

			// Check if it's a successful deactivate action.
			if ( 'deactivate' === action ? res.success : res.success ) {
				$toggle.attr( { 'disabled': false, 'data-action': 'activate' } );
				return;
			}

			var msg = app.addonErrorMsg( action );

			// Leave disabled and add error message.
			$toggle.parent()
				.after( '<p class="affwp-addon-error">' + msg + '</p>' );

		},
		/**
		 * Get the approprate error message.
		 *
		 * @since 2.9.6
		 */
		addonErrorMsg: function( action ) {

			switch( action ) {
				case 'activate':
					var msg = affwp_admin_addons_vars.error_could_not_activate;
					break;
				case 'deactivate':
					var msg = affwp_admin_addons_vars.error_could_not_deactivate;
					break;
				default:
					var msg = affwp_admin_addons_vars.error_could_not_install;
			}

			return msg;

		},
	};

	// Provide access to public functions/properties.
	return app;

}( document, window, jQuery ) ).init();
