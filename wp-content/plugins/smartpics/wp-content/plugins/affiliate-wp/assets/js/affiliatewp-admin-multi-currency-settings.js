/**
 * AffiliateWP Multi-Currency admin
 *
 * @since  2.26.1
 * @since  2.27.3 Added support for adding currencies we detect in WooCommerce, see https://github.com/awesomemotive/affiliate-wp/issues/5299.
 *
 * @author Darvin da Silveira <ddasilveira@awesomeomotive.com>
 * @author Aubrey Portwood    <aportwood@am.co>
 */

/* eslint-disable prefer-const */
/* eslint-disable padded-blocks */
/* eslint-disable no-shadow */

'use strict';

/* eslint-disable no-console, no-undef, jsdoc/no-undefined-types */
affiliatewp.attach(
	'multiCurrencySettings',
	/**
	 * Multi-Currency Component.
	 *
	 * @since 2.26.1
	 */
	{
		/**
		 * Populated dynamically via wp_add_inline_script().
		 */
		data: {},

		/**
		 * Initiate select2 in the given elements.
		 *
		 * @since 2.26.1
		 * @param {jQuery} $elements
		 */
		initSelect2( $elements ) {
			$elements.each( function() {
				const $self = jQuery( this );

				$self.select2( affiliatewp.multiCurrencySettings.data.currency_select2_settings );

				affiliatewp.multiCurrencySettings.bindSelect2Events( $self );
			} );
		},

		/**
		 * Bind events to the select2 currency element.
		 *
		 * @since 2.26.1
		 * @param {jQuery} $select2Element
		 */
		bindSelect2Events( $select2Element ) {
			affiliatewp.multiCurrencySettings.addPlaceholderToSelect2SearchInput( $select2Element );
			affiliatewp.multiCurrencySettings.onSelect2ChangeCurrency( $select2Element );
		},

		/**
		 * Add a custom placeholder to a select2 element.
		 *
		 * @since 2.26.1
		 * @param {jQuery} $select2Element
		 */
		addPlaceholderToSelect2SearchInput( $select2Element ) {
			$select2Element.one( 'select2:open', function() {
				jQuery( '.select2-search__field' ).prop( 'placeholder', affiliatewp.multiCurrencySettings.data.currency_select2_settings.placeholder );
			} );
		},

		/**
		 * Retrieve an exchange rate number easier for humans to read.
		 *
		 * @since 2.26.1
		 * @param {string} exchangeRate
		 * @return {string} The new exchange rate.
		 */
		formatExchangeRate( exchangeRate ) {
			return parseFloat( exchangeRate ).toFixed( 4 );
		},

		/**
		 * Retrieve a cached exchange rate for a currency code.
		 *
		 * @since 1.0.0
		 * @param {string} currency The currency code, like USD, BRL.
		 */
		getExchangeRate( currency ) {
			return affiliatewp?.multiCurrency?.data?.exchangeRates.hasOwnProperty( currency )
				? affiliatewp.multiCurrency.data.exchangeRates[ currency ]
				: null;
		},

		/**
		 * Add a custom event to a select2 element to update the exchange rate on change.
		 *
		 * @since 2.26.1
		 * @param {jQuery} $select2Element
		 */
		onSelect2ChangeCurrency( $select2Element ) {
			$select2Element.on( 'select2:select', function() {
				const $self = jQuery( this );
				const $row = $self.closest( '.affwp-multi-currency-row' );
				const currency = $self.val();
				const exchangeRate = affiliatewp.multiCurrencySettings.getExchangeRate( currency );
				const $exchangeRateField = $row.find( '.affwp-multi-currency-field-exchange-rate' );

				$exchangeRateField.val( exchangeRate );

				if ( $exchangeRateField.attr( 'type' ) === 'number' ) {
					$exchangeRateField.focus();
				}

				$row.find( '.affwp-multi-currency-exchange-rate' ).text( affiliatewp.multiCurrencySettings.formatExchangeRate( exchangeRate ) );
				$row.find( '.affwp-multi-currency-currency' ).text( currency );
			} );
		},

		/**
		 * Toggle the remove button visibility.
		 *
		 * The remove button should be visible only if we have three or more tiers on the screen,
		 * since this is a multi-tier system, we always need more than one tier configured.
		 *
		 * @since 2.26.1
		 */
		toggleRemoveButtonVisibility() {
			const $removeTier = jQuery( '.affwp-remove-exchange-rate' );

			// Determine if the remove tier button should be shown or hidden.
			if ( jQuery( '.affwp-multi-currency-row' ).length >= 2 ) {
				$removeTier.css( 'display', 'block' );
				return;
			}

			$removeTier.css( 'display', 'none' );
		},

		/**
		 * Setup Missing (WooCommerce) Currencies.
		 *
		 * This gets the currencies in WooCommerce (localized in PHP)
		 * and sets them up if they are not in the list of currencies
		 * in the settings page.
		 *
		 * Runs on DOM ready.
		 *
		 * @since 2.27.3
		 */
		setupWooCommerceCurrencies() {

			if ( ! window.hasOwnProperty( 'affiliatewpRecentCurrencies' ) ) {
				return; // These should have been localized in PHP.
			}

			if ( jQuery( '#affwp_settings[multi_currency]' ).is( ':checked' ) ) {
				return; // Was already enabled, don't add missing currencies when MTC is already enabled.
			}

			const $addedCurrencies = []; // Repeaters/Currencies we add to the DOM.

			// When we disable multi-currency (checkbox) remove and currencies we added.
			jQuery( 'input[id="affwp_settings[multi_currency]"]' ).on( 'change', function() {

				// When we enable multi-currency...
				if ( jQuery( this ).is( ':checked' ) ) {

					// Setup the currencies we detected in WooCommerce in PHP...
					jQuery( window.affiliatewpRecentCurrencies ?? [] ).each( function( i, currency ) {

						let presentCurrencies = [];

						// Push currencies that are already setup.
						jQuery( '.affwp-multi-currency-field-currency' ).each( ( i, select ) => presentCurrencies.push( jQuery( select ).val() ) );

						if ( presentCurrencies.includes( currency ) ) {
							return; // The currency is already setup on the screen, don't re-add another one.
						}

						// Setup the Add Exchange Rate button.
						jQuery( '#affwp-new-exchange-rate' )

							// When clicked (once), get the added row and set it's currency.
							.one( 'click', function() {

								const $lastRow = jQuery( '.affwp-multi-currency-row' ).last(); // Get the new repeater/row we add when we click the button.

								$addedCurrencies.push( $lastRow ); // Keep track of the one's we add so we can remove them later.

								// The <select> in the last row...
								jQuery( '.affwp-multi-currency-field-currency', $lastRow )
									.val( currency ) // Set the currency of the dropdown to the currency from WooCommerce.
									.trigger( 'select2:select' ) // This causes the Exchange Rate to be properly set once changed (in this file).
									.trigger( 'change' ); // Yes we also need to trigger change for other event handlers.
							} )

							// And click the button (runs the .one() above).
							.trigger( 'click' );
					} );

					return; // Enabled multi-currency...
				}

				// When we disable multi-currency remove any currencies we added to prevent save issues.
				$addedCurrencies.forEach( ( $row ) => $row.remove() );
			} );
		},

		/**
		 * Initiate the Tiers repeater in the Settings screen.
		 *
		 * @since 2.26.1
		 */
		initRepeater() {

			// The table body jQuery object.
			const $root = jQuery( '#affwp-multi-currency-rows' );

			// Add a new button jQuery object.
			const $addNewButton = jQuery( '#affwp-new-exchange-rate' );

			// Tracks the total number of rows added so far.
			let total = $root.find( '.affwp-multi-currency-row' ).length;

			const getRowTemplate = function( fields ) {
				// Copy the row HTML template.
				let row = affiliatewp.multiCurrencySettings.data.rowTemplate;

				// Replace all the {{var}} found in the HTML template.
				Object.entries( fields ).forEach( ( [ key, value ] ) => {
					row = row.replace( new RegExp( `{{\\b${ key }\\b}}`, 'g' ), value );
				} );

				return row;
			};

			// Add a new row/currency.
			$addNewButton.on( 'click', function( e ) {
				e.preventDefault();

				const exchangeRate = affiliatewp.multiCurrencySettings.getExchangeRate(
					affiliatewp.multiCurrencySettings.data.firstAvailableCurrencyCode
				);

				const method = jQuery( 'select[name="affwp_settings[multi_currency_rates_update_method]"]' ).val();

				const $row = jQuery( getRowTemplate(
					{
						index: total,
						exchange_rate: exchangeRate,
						exchange_rate_formatted: affiliatewp.multiCurrencySettings.formatExchangeRate( exchangeRate ),
						currency: affiliatewp.multiCurrencySettings.data.firstAvailableCurrencyCode,
						currency_options: '',
					}
				) );

				$row.find( '.affwp-multi-currency-exchange-rate' ).toggle( 'manual' !== method );

				$row.find( '.affwp-multi-currency-field-exchange-rate' ).attr(
					'type',
					'manual' === method
						? 'number'
						: 'hidden'
				);

				$root.append( $row );

				// Update total of rows.
				total = $root.find( '.affwp-multi-currency-row' ).length;

				affiliatewp.multiCurrencySettings.initSelect2( $row.find( 'select' ) );

				affiliatewp.multiCurrencySettings.toggleRemoveButtonVisibility();

				// Focus on the input field in the new row.
				$root.find( '.affwp-multi-currency-row' ).find( 'input' ).focus();
			} );

			// Remove row/currency.
			jQuery( document ).on( 'click', '.affwp-remove-exchange-rate', function( e ) {
				e.preventDefault();

				// Remove the row.
				jQuery( this ).parent().remove();

				affiliatewp.multiCurrencySettings.toggleRemoveButtonVisibility();
			} );

			affiliatewp.multiCurrencySettings.toggleRemoveButtonVisibility();

			// Bind select2 events to existent items.
			$root.find( 'select' ).each( function() {
				affiliatewp.multiCurrencySettings.bindSelect2Events( jQuery( this ) );
			} );

			jQuery( document ).on( 'ready', this.setupWooCommerceCurrencies );
		},
	}
);
