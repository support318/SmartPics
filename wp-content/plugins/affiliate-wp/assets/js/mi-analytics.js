/* global affiliate_wp_analytics */

/**
 * Handles installation of the MonsterInsights plugin on the Analytics product recommendation page.
 *
 * @since 2.9.5
 */

( function( document, window, $ ) {
	'use strict';

	/**
	 * Elements.
	 *
	 * @since 2.9.5
	 *
	 * @type {object}
	 */
	var el = {};

	/**
	 * Public functions and properties.
	 *
	 * @since 2.9.5
	 *
	 * @type {object}
	 */
	var app = {

		/**
		 * Start the engine.
		 *
		 * @since 2.9.5
		 */
		init: function() {

			$( app.ready );
		},

		/**
		 * Document ready.
		 *
		 * @since 2.9.5
		 */
		ready: function() {

			app.initVars();
			app.events();
		},

		/**
		 * Init variables.
		 *
		 * @since 2.9.5
		 */
		initVars: function() {

			el = {
				$stepInstall: $( 'section.step-install' ),
				$stepInstallNum: $( 'section.step-install .num img' ),
				$stepSetup: $( 'section.step-setup' ),
				$stepSetupNum: $( 'section.step-setup .num img' ),
				$stepAddon: $( 'section.step-addon' ),
				$stepAddonNum: $( 'section.step-addon .num img' )
			};
		},

		/**
		 * Register JS events.
		 *
		 * @since 2.9.5
		 */
		events: function() {

			// Step 'Install' button click.
			el.$stepInstall.on( 'click', 'button', app.stepInstallClick );

			// Step 'Setup' button click.
			el.$stepSetup.on( 'click', 'button', app.gotoURL );

			// Step 'Addon' button click.
			el.$stepAddon.on( 'click', 'button', app.gotoURL );
		},

		/**
		 * Step 'Install' button click.
		 *
		 * @since 2.9.5
		 */
		stepInstallClick: function() {

			var $btn       = $( this ),
				action     = $btn.attr( 'data-action' ),
				plugin     = $btn.attr( 'data-plugin' ),
				ajaxAction = '';

			if ( $btn.hasClass( 'disabled' ) ) {
				return;
			}

			switch ( action ) {
				case 'activate':
					ajaxAction = 'affwp_activate_plugin';
					$btn.text( affiliate_wp_analytics.activating );
					break;

				case 'install':
					ajaxAction = 'affwp_install_plugin';
					$btn.text( affiliate_wp_analytics.installing );
					break;

				case 'goto-url':
					window.location.href = $btn.attr( 'data-url' );
					return;

				default:
					return;
			}

			$btn.addClass( 'disabled' );
			app.showSpinner( el.$stepInstallNum );

			$.post(
				affiliate_wp_analytics.ajax_url,
				{
					action: ajaxAction,
					nonce: affiliate_wp_analytics.nonce,
					plugin: plugin
				}
			)
				.done(
					function( res ) {
						app.stepInstallDone( res, $btn, action );
					}
				)
				.always(
					function() {
						app.hideSpinner( el.$stepInstallNum );
					}
				);
		},

		/**
		 * Done part of the step 'Install'.
		 *
		 * @since 2.9.5
		 *
		 * @param {object} res    Result of $.post() query.
		 * @param {jQuery} $btn   Button.
		 * @param {string} action Action (for more info look at the app.stepInstallClick() function).
		 */
		stepInstallDone: function( res, $btn, action ) {

			if ( 'install' === action ? res.success && res.data.is_activated : res.success ) {

				el.$stepInstallNum.attr(
					'src',
					el.$stepInstallNum
						.attr( 'src' )
						.replace( 'step-1.', 'step-complete.' )
				);

				$btn.addClass( 'grey' )
					.removeClass( 'button-primary' )
					.text( affiliate_wp_analytics.activated );

				app.stepInstallPluginStatus();

				return;
			}

			var activationFail = ('install' === action && res.success && ! res.data.is_activated) || 'activate' === action,
				url            = ! activationFail ? affiliate_wp_analytics.mi_manual_install_url : affiliate_wp_analytics.mi_manual_activate_url,
				msg            = ! activationFail ? affiliate_wp_analytics.error_could_not_install : affiliate_wp_analytics.error_could_not_activate,
				btn            = ! activationFail ? affiliate_wp_analytics.download_now : affiliate_wp_analytics.plugins_page;

			$btn.removeClass( 'grey disabled' )
				.text( btn )
				.attr( 'data-action', 'goto-url' )
				.attr( 'data-url', url )
				.after( '<p class="error">' + msg + '</p>' );
		},

		/**
		 * Callback for step 'Install' completion.
		 *
		 * @since 2.9.5
		 */
		stepInstallPluginStatus: function() {

			$.post(
				affiliate_wp_analytics.ajax_url,
				{
					action: 'affwp_analytics_page_check_plugin_status',
					nonce: affiliate_wp_analytics.nonce
				}
			)
				.done( app.stepInstallPluginStatusDone );
		},

		/**
		 * Done part of the callback for step 'Install' completion.
		 *
		 * @since 2.9.5
		 *
		 * @param {object} res Result of $.post() query.
		 */
		stepInstallPluginStatusDone: function( res ) {

			if ( ! res.success ) {
				return;
			}

			el.$stepSetup.removeClass( 'grey' );
			el.$stepSetupBtn = el.$stepSetup.find( 'button' );

			if ( res.data.setup_status > 0 ) {

				el.$stepSetupNum.attr(
					'src',
					el.$stepSetupNum.attr( 'src' )
						.replace( 'step-2.svg', 'step-complete.svg' )
				);

				el.$stepAddon
					.removeClass( 'grey' )
					.find( 'button' )
					.attr( 'data-url', res.data.step3_button_url )
					.removeClass( 'grey disabled' ).addClass( 'button-primary' );

				if ( res.data.license_level === 'pro' ) {
					el.$stepAddon.find( 'button' ).text( res.data.addon_installed > 0 ? affiliate_wp_analytics.activate_now : affiliate_wp_analytics.install_now );
				}

				return;
			}

			el.$stepSetupBtn
				.removeClass( 'grey disabled' )
				.addClass( 'button-primary' );
		},

		/**
		 * Go to URL by click on the button.
		 *
		 * @since 2.9.5
		 */
		gotoURL: function() {

			var $btn = $( this );

			if ( $btn.hasClass( 'disabled' ) ) {
				return;
			}

			window.location.href = $btn.attr( 'data-url' );
		},

		/**
		 * Display spinner.
		 *
		 * @since 2.9.5
		 *
		 * @param {jQuery} $el Section number image jQuery object.
		 */
		showSpinner: function( $el ) {

			$el.siblings( '.loader' )
				.removeClass( 'hidden' );
		},

		/**
		 * Hide spinner.
		 *
		 * @since 2.9.5
		 *
		 * @param {jQuery} $el Section number image jQuery object.
		 */
		hideSpinner: function( $el ) {

			$el.siblings( '.loader' )
				.addClass( 'hidden' );
		}
	};

	// Provide access to public functions/properties.
	return app;

} ( document, window, jQuery ) ).init();
