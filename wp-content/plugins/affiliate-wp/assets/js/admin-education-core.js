/* global affiliatewp_education */
/**
 * AffiliateWP Education Core.
 *
 * @since 2.18.0
 */

'use strict';

const AffiliateWPEducation = window.AffiliateWPEducation || {};

AffiliateWPEducation.core = AffiliateWPEducation.core || ( function( document, window, $ ) {

	/**
	 * Spinner markup.
	 *
	 * @since 2.18.0
	 *
	 * @type {string}
	 */
	const spinner = '<div class="affwp-spinner"><svg viewBox="0 0 50 50"><circle cx="25" cy="25" r="20"></circle><circle cx="25" cy="25" r="20"></circle></svg></div>';

	/**
	 * Public functions and properties.
	 *
	 * @since 2.18.0
	 *
	 * @type {Object}
	 */
	const app = {

		/**
		 * Start the engine.
		 *
		 * @since 2.18.0
		 */
		init() {
			$( app.ready );
		},

		/**
		 * Document ready.
		 *
		 * @since 2.18.0
		 */
		ready() {
			app.events();
		},

		/**
		 * Register JS events.
		 *
		 * @since 2.18.0
		 */
		events() {
			app.trackClickEvents();
		},

		/**
		 * Get the name of the element.
		 *
		 * @since 2.18.0
		 *
		 * @param {Element} el Element.
		 *
		 * @return {string} The element name.
		 */
		getNameValue( el ) {

			if ( ! el.hasAttribute( 'data-name' ) ) {
				throw new Error( 'Can not find a UTM value. You need to add data-utm-content or data-name to the target element.' );
			}

			return el.dataset.name;
		},

		/**
		 * Get the feature name of the element.
		 *
		 * @since 2.23.2
		 *
		 * @param {Element} el Element.
		 *
		 * @return {string} The element name.
		 */
		getFeatureName( el ) {

			if ( el.hasAttribute( 'data-feature-name' ) ) {
				return el.dataset.featureName;
			}

			return '';
		},

		/**
		 * Get UTM content for different elements.
		 *
		 * @since 2.18.0
		 *
		 * @param {Element} el Element.
		 *
		 * @return {string} UTM content string.
		 */
		getUTMContentValue( el ) {

			if ( el.hasAttribute( 'data-utm-content' ) ) {
				return el.dataset.utmContent;
			}

			return this.getNameValue( el );
		},

		/**
		 * Get license level required for the feature.
		 *
		 * @since 2.26.1
		 *
		 * @param {Element} el Element.
		 *
		 * @return {string} License level. Returns pro if no license level can be found.
		 */
		getLicenseLevel( el ) {

			if ( el.hasAttribute( 'data-license-level' ) ) {
				return el.dataset.licenseLevel;
			}

			return 'pro';
		},

		/**
		 * Convert slug to UTM content.
		 *
		 * @since 2.18.0
		 *
		 * @param {string} slug Slug.
		 *
		 * @return {string} UTM content string.
		 */
		slugToUTMContent( slug ) {

			if ( ! slug ) {
				return '';
			}

			return slug.toString()

				// Replace all non-alphanumeric characters with space.
				.replace( /[^a-z\d ]/gi, ' ' )

				// Uppercase each word.
				.replace( /\b[a-z]/g, function( char ) {
					return char.toUpperCase();
				} );
		},

		/**
		 * Get upgrade URL according to the UTM content and license type.
		 *
		 * @since 2.18.0
		 *
		 * @param {string} utmContent UTM content.
		 * @param {string} type       Feature license type: pro or elite.
		 * @param {string} addon      Addon name. Optional.
		 *
		 * @return {string} Upgrade URL.
		 */
		getUpgradeURL( utmContent, type, addon ) {

			let	baseURL = affiliatewp_education.upgrade[ type ].hasOwnProperty( addon )
				? affiliatewp_education.upgrade[ type ][ addon ].url
				: affiliatewp_education.upgrade[ type ]['default'].url;

			// Test if the base URL already contains `?`.
			let appendChar = /(\?)/.test( baseURL ) ? '&' : '?';

			// If the upgrade link is changed by partners, appendChar has to be encoded.
			if ( baseURL.indexOf( 'https://affiliatewp.com' ) === -1 ) {
				appendChar = encodeURIComponent( appendChar );
			}

			return baseURL + appendChar + 'utm_content=' + encodeURIComponent( utmContent.trim() );
		},

		/**
		 * Get spinner markup.
		 *
		 * @since 2.18.0
		 *
		 * @return {string} Spinner markup.
		 */
		getSpinner() {
			return spinner;
		},

		/**
		 * Get upgrade modal width.
		 *
		 * @since 2.18.0
		 *
		 * @param {boolean} isVideoModal Upgrade modal type (with video or not).
		 *
		 * @return {string} Modal width in pixels.
		 */
		getUpgradeModalWidth( isVideoModal ) {

			const windowWidth = $( window ).width();

			if ( windowWidth <= 300 ) {
				return '250px';
			}

			if ( windowWidth <= 750 ) {
				return '350px';
			}

			if ( ! isVideoModal || windowWidth <= 1024 ) {
				return '550px';
			}

			return windowWidth > 1070 ? '1040px' : '994px';
		},

		/**
		 * Get install modal width.
		 *
		 * @since 2.18.0
		 *
		 * @return {string} Modal width in pixels.
		 */
		getInstallModalWidth() {
			return $( window ).width() < 480 ? '320px' : '430px';
		},

		/**
		 * Handle the click events.
		 *
		 * @since 2.18.0
		 */
		trackClickEvents() {

			$( document ).on( 'click', '.affwp-education-modal[data-action]', function( e ) {

				// Prevent any possible action.
				e.preventDefault();
				e.stopImmediatePropagation();

				const $self = $( this );

				switch ( $self.data( 'action' ) ) {
					case 'activate':
						app.activateModal( $self );
						break;
					case 'install':
						app.installModal( $self );
						break;
				}
			} );
		},

		/**
		 * Addon activate modal.
		 *
		 * @since 2.18.0
		 *
		 * @param {jQuery} $button jQuery button element.
		 */
		activateModal( $button  ) {

			const feature = $button.data('name');

			$.alert( {
				title  : false,
				content: affiliatewp_education.activate_prompt.replace( /%name%/g, feature ),
				icon   : 'fa fa-info-circle',
				type   : 'lightgreen',
				boxWidth : app.getInstallModalWidth(),
				theme : 'modern,affiliatewp-education',
				useBootstrap : false,
				buttons: {
					confirm: {
						text    : affiliatewp_education.activate_confirm,
						btnClass: 'btn-confirm',
						keys    : [ 'enter' ],
						action() {

							this.$$confirm
								.prop( 'disabled', true )
								.html( spinner + affiliatewp_education.activating );

							this.$$cancel
								.prop( 'disabled', true );

							app.activateAddon( $button, this );

							return false;
						},
					},
					cancel : {
						text: affiliatewp_education.cancel,
					},
				},
			} );
		},

		/**
		 * Activate addon via AJAX.
		 *
		 * @since 2.18.0
		 *
		 * @param {jQuery} $button       jQuery button element.
		 * @param {object} previousModal Previous modal instance.
		 */
		activateAddon( $button, previousModal ) {

			$.post(
				affiliatewp_education.ajax_url,
				{
					action: 'affwp_activate_addons_page_plugin',
					nonce: $button.data( 'nonce' ),
					plugin: $button.data( 'plugin' ),
					addonID: $button.data( 'id' )
				},
				function( res ) {

					previousModal.close();

					if ( res.success ) {

						// Prevent modal events to occur again.
						$button.removeClass( 'affwp-education-modal' );

						// Display the save modal.
						app.saveModal( $button, affiliatewp_education.addon_activated, false );
					} else {

						$.alert( {
							title  : false,
							content: res.data.error,
							icon   : 'fa fa-exclamation-circle',
							type   : 'red',
							boxWidth : app.getInstallModalWidth(),
							theme : 'modern,affiliatewp-education',
							useBootstrap : false,
							buttons: {
								confirm: {
									text    : affiliatewp_education.close,
									btnClass: 'btn-confirm',
									keys    : [ 'enter' ],
								},
							},
						} );
					}
				}
			);
		},

		/**
		 * Ask user if they would like to save the form and refresh the page.
		 *
		 * @since 2.18.0
		 *
		 * @param {jQuery} $button       jQuery button element.
		 * @param {string}      title   Modal title.
		 * @param {string|bool} content Modal content.
		 */
		saveModal( $button, title, content ) {

			title = title || affiliatewp_education.addon_activated;
			content = content || affiliatewp_education.save_prompt;

			$.alert( {
				title  : title.replace( /\.$/, '' ), // Remove a dot in the title end.
				content: content,
				icon   : 'fa fa-check-circle',
				type   : 'lightgreen',
				boxWidth : app.getInstallModalWidth(),
				theme : 'modern,affiliatewp-education',
				useBootstrap : false,
				buttons: {
					confirm: {
						text    : affiliatewp_education.save_confirm,
						btnClass: 'btn-confirm',
						keys    : [ 'enter' ],
						action  : function() {

							this.$$confirm
								.prop( 'disabled', true )
								.html( spinner + affiliatewp_education.saving );

							this.$$cancel
								.prop( 'disabled', true );

							const tag = $button.get( 0 ).tagName;

							// Enable the setting.
							if ( tag === 'INPUT' && $button.attr( 'type' ) === 'checkbox' && ! $button.is( ':checked' ) ) {
								$button.trigger( 'click' );
							}

							// Save settings and reload.
							$( '#submit' ).trigger( 'click' );

							return false;
						},
					},
					cancel : {
						text: affiliatewp_education.close,
					},
				},
			} );
		},

		/**
		 * Addon install modal.
		 *
		 * @since 2.18.0
		 *
		 * @param {jQuery} $button jQuery button element.
		 */
		installModal( $button ) {

			const feature = $button.data( 'name' );

			$.alert( {
				title   : false,
				content : affiliatewp_education.install_prompt.replace( /%name%/g, feature ),
				icon    : 'fa fa-info-circle',
				type    : 'lightgreen',
				boxWidth : app.getInstallModalWidth(),
				theme : 'modern,affiliatewp-education',
				useBootstrap : false,
				buttons : {
					confirm: {
						text    : affiliatewp_education.install_confirm,
						btnClass: 'btn-confirm',
						keys    : [ 'enter' ],
						isHidden: ! affiliatewp_education.can_install_addons,
						action  : function() {

							this.$$confirm.prop( 'disabled', true )
								.html( spinner + affiliatewp_education.installing );

							this.$$cancel
								.prop( 'disabled', true );

							app.installAddon( $button, this );

							return false;
						},
					},
					cancel : {
						text: affiliatewp_education.cancel,
					},
				},
			} );
		},

		/**
		 * Install addon via AJAX.
		 *
		 * @since 2.18.0
		 *
		 * @param {jQuery} $button       Button object.
		 * @param {object} previousModal Previous modal instance.
		 */
		installAddon( $button, previousModal ) {

			$.post(
				affiliatewp_education.ajax_url,
				{
					action: 'affwp_install_addons_page_plugin',
					nonce: $button.data('nonce'),
					plugin: $button.data('plugin'),
					addonID: $button.data('id')
				},
				function (res) {

					previousModal.close();

					if ( res.success ) {

						// Prevent modal events to occur again.
						$button.removeClass( 'affwp-education-modal' );

						app.saveModal( $button, affiliatewp_education.addon_installed, false );
					} else {

						$.alert({
							title: false,
							content: res.data.error,
							icon: 'fa fa-exclamation-circle',
							type: 'red',
							boxWidth : app.getInstallModalWidth(),
							theme : 'modern,affiliatewp-education',
							useBootstrap : false,
							buttons: {
								confirm: {
									text: affiliatewp_education.close,
									btnClass: 'btn-confirm',
									keys: ['enter'],
								},
							},
						});
					}
				}
			);
		}
	}

	// Provide access to public functions/properties.
	return app;

}( document, window, jQuery ) );

AffiliateWPEducation.core.init();
