// noinspection JSUnresolvedReference -- Some variables are injected via PHP dynamically.

/**
 * AffiliateWP License Activator callback scripts.
 *
 * @since  2.26.1
 * @author Darvin da Silveira <ddasilveira@awesomeomotive.com>
 */

'use strict';

/* eslint-disable no-console, no-undef, jsdoc/no-undefined-types */
affiliatewp.attach(
	'licenseActivator',
	/**
	 * License Activator Component.
	 *
	 * @since 2.26.1
	 */
	{
		/**
		 * Populated dynamically via wp_add_inline_script().
		 *
		 * @since 2.26.1
		 * @member {Object} data Data injected by wp_inline_script()
		 */
		data: {},

		/**
		 * Launch a Modal
		 *
		 * Uses jQuery Confirm.
		 *
		 * @see https://craftpip.github.io/jquery-confirm/
		 *
		 * @since 2.26.1
		 *
		 * @param {string} title   Title.
		 * @param {string} content Content.
		 * @param {string} icon    Icon classes.
		 * @param {Object} buttons Buttons.
		 * @param {Object} args    Overriding Args.
		 *
		 * @return {Object} Modal Object.
		 */
		modal( title, content, icon, buttons, args ) {
			return jQuery.alert(
				jQuery.extend(
					{
						backgroundDismiss: true,
						title,
						icon,
						type: 'green',
						content,
						boxWidth: 400,
						useBootstrap: false,
						theme: 'modern,affiliatewp-education',
						closeIcon: true,
						draggable: false,
						dragWindowBorder: false,
						buttons,
					},
					args
				)
			);
		},

		/**
		 * Bind events to handle the activator logics.
		 *
		 * @since 2.26.1
		 */
		init() {
			document.addEventListener( 'DOMContentLoaded', function() {
				const buttons = document.querySelectorAll( '.affwp-settings-license-activator__button' );
				const modalButtonArgs = {
					confirm: {
						text: 'OK',
						btnClass: 'btn-confirm',
						keys: [ 'enter' ],
					},
				};

				buttons.forEach( function( button ) {
					button.addEventListener( 'click', function() {
						const targetInput = document.querySelector( `input[name="${ button.dataset.target }"]` );
						const key = targetInput.value ?? '';

						if ( '' === key ) {
							affiliatewp.licenseActivator.modal(
								'',
								button.dataset.invalidMessage,
								'fa fa-info-circle',
								modalButtonArgs,
								{
									closeIcon: false,
									type: 'orange',
								}
							);

							return;
						}

						const settingsToWatch = JSON.parse( button.dataset.settingsToWatch ?? [] );
						const additionalData = {};

						settingsToWatch.forEach( function( settingName ) {
							const settingInput = document.querySelector( `[name="affwp_settings[${ settingName }]"]` );
							if ( settingInput ) {
								additionalData[ settingName ] = settingInput.value;
							}
						} );

						let isActivated = parseInt( button.dataset.isActivated ?? 0 );

						const data = {
							action: isActivated
								? button.dataset.deactivateAjaxAction
								: button.dataset.activateAjaxAction,
							key,
							nonce: affiliatewp.licenseActivator.data.nonce ?? '',
						};

						for ( const _key in additionalData ) {
							if ( additionalData.hasOwnProperty( _key ) ) {
								data[ `additional_data[${ _key }]` ] = additionalData[ _key ];
							}
						}

						button.classList.add( 'affwp-button--is-loading' );

						const container = button?.parentElement?.parentElement;
						const statusEl = container.querySelector( '.affwp-settings-license-activator__status' );

						fetch( ajaxurl, {
							method: 'POST',
							headers: {
								'Content-Type': 'application/x-www-form-urlencoded; charset=UTF-8',
							},
							body: new URLSearchParams( data ).toString(),
						} )
							.then( ( response ) => {
								button.classList.remove( 'affwp-button--is-loading' );

								if ( ! response.ok ) {
									// TODO: Replace with a translatable message.
									throw new Error( 'Unknown. Please try again.' );
								}
								return response.json();
							} )
							.then( ( result ) => {
								// Intention to connect to the API was successful.
								if ( ! isActivated && result.success ) {
									isActivated = 1;
									targetInput.disabled = true;
									button.textContent = button.dataset.labelActivated;

									affiliatewp.licenseActivator.modal(
										'',
										button.dataset.activationMessage,
										'fa fa-check-circle',
										modalButtonArgs,
										{
											closeIcon: false,
										}
									);
								// Intention to connect to the API failed.
								} else if ( ! isActivated && ! result.success ) {
									affiliatewp.licenseActivator.modal(
										'',
										result?.data.errorMessage ?? button.dataset.activationErrorMessage,
										'fa fa-info-circle',
										modalButtonArgs,
										{
											closeIcon: false,
											type: 'red',
										}
									);
								// Intention to disconnect the API was successful.
								} else if ( isActivated && result.success ) {
									isActivated = 0;
									targetInput.disabled = false;
									button.textContent = button.dataset.labelDeactivated;

									affiliatewp.licenseActivator.modal(
										'',
										button.dataset.deactivationMessage,
										'fa fa-info-circle',
										modalButtonArgs,
										{
											closeIcon: false,
											type: 'blue',
										}
									);
								// Intention to disconnect the API failed.
								} else if ( isActivated && ! result.success ) {
									affiliatewp.licenseActivator.modal(
										'',
										button.dataset.deactivationErrorMessage,
										'fa fa-info-circle',
										modalButtonArgs,
										{
											closeIcon: false,
											type: 'red',
										}
									);
								}

								if ( statusEl ) {
									statusEl.innerHTML = result?.data?.status ?? '';
								}

								button.setAttribute( 'data-is-activated', isActivated );

								button.dispatchEvent(
									new CustomEvent(									'affwpOnLicenseActivatorEnd',
										{
											detail: {
												isActivated,
												success: true,
												container,
											},
										}
									)
								);
							} )
							.catch( ( error ) => {
								if ( ! error ) {
									return;
								}

								affiliatewp.licenseActivator.modal(
									'',
									error,
									'fa fa-info-circle',
									modalButtonArgs,
									{
										closeIcon: false,
										type: 'red',
									}
								);
							} );
					} );
				} );
			} );
		},
	}
);
