/**
 * AffiliateWP Tooltips.
 *
 * An abstraction layer for the Tippy JS library.
 *
 * @since 2.16.0
 */

'use strict';

/* eslint-disable no-console, no-undef */
affiliatewp.attach(
	'tooltip',
	/**
	 * Tooltip Component.
	 *
	 * Displays tooltip messages on specified elements.
	 *
	 * @example
	 * affiliatewp.tooltip.show(selector, content, settings);
	 *
	 * See documentation for detailed usage and parameter information.
	 *
	 * @since 2.16.0
	 */
	{
		/**
		 * Default settings.
		 *
		 * @since 2.16.0
		 *
		 * @param    {Object}  settings    Plugin settings.
		 * @property {string}  trigger     The trigger event.
		 *                                 Accepts: mouseenter, manual.
		 * @property {string}  placement   Tooltip position.
		 *                                 Accepts: top, right, bottom, left or auto.
		 * @property {number}  hideDelay   Time in milliseconds before hiding the tooltip.
		 * @property {Array}   duration    Array with time in milliseconds for the show and hide animations.
		 * @property {boolean} hideOnClick Whether it should hide the tooltip when clicking on it again.
		 */
		settings: {
			trigger: 'click',
			placement: 'top',
			hideDelay: 5000,
			duration: [ 300, 250 ],
			hideOnClick: false,
		},

		/**
		 * Initialize tooltip buttons.
		 *
		 * @since 2.16.0
		 *
		 * @param {string} selector A string representing a selector.
		 * @param {string} content  The content to display.
		 * @param {Object} settings Additional settings. See @param settings for all available settings.
		 */
		show( selector, content, settings = {} ) {
			const elements = document.querySelectorAll( selector );

			settings = affiliatewp.parseArgs(
				{
					...settings,
					...{ content },
				},
				this.settings
			);

			elements.forEach( ( el ) => {
				const tooltip = tippy( el, settings );

				if ( settings.trigger === 'manual' ) {
					tooltip.show();
				}

				if ( settings.hideDelay ) {
					setTimeout( () => {
						tooltip.hide();
					}, settings.hideDelay );
				}
			} );
		},

		/**
		 * Hide all active tooltips.
		 *
		 * @since 2.16.0
		 */
		hideAll() {
			tippy.hideAll();
		},
	}
);
