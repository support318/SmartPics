/**
 * AffiliateWP Utils.
 *
 * This file contains global utility methods for various purposes, not specific to any particular entity.
 *
 * @since 2.23.2
 */

'use strict';

/* eslint-disable no-console, no-undef */
affiliatewp.attach(
	'utils',
	/**
	 * Utils Component.
	 *
	 * Includes the following utilities:
	 *  - Copy
	 *
	 * @since 2.23.2
	 */
	{

		/**
		 * Populated dynamically via wp_add_inline_script().
		 */
		data: {},

		/**
		 * Prepare elements to behave like a copy button, when clicked will copy the content
		 * determined by the own element.
		 *
		 * @since 2.23.2
		 *
		 * @param {string} querySelector The DOM elements to query.
		 * @param {string} content The content to be copied. You can suppress this and use data-content in the element instead.
		 */
		copyButton: ( querySelector, content = '' ) => {

			const hideDelay = 1000;

			const showTempTooltip = ( instance, newContent, originalContent ) => {

				instance.setContent( newContent );
				instance.show();

				instance.setProps( {
					onHidden: () => {
						instance.setContent( originalContent );
					},
				} );

				setTimeout(
					() => {
						instance.hide();
						instance.setProps( { trigger: 'mouseenter focus' } );
					},
					hideDelay
				);
			}

			const handleOnClick = (
				instance,
				content,
				hoverText,
				successText,
				errorText
			) => {

				if ( ! ( navigator && navigator.clipboard ) ) {

					showTempTooltip(
						instance,
						errorText,
						hoverText
					);
					return;
				}

				navigator.clipboard
					.writeText( content )
					.then( () => {
						showTempTooltip(
							instance,
							successText,
							hoverText
						);
					} )
					.catch( () => {
						showTempTooltip(
							instance,
							errorText,
							hoverText
						);
					} );
			}

			document.querySelectorAll( querySelector ).forEach( ( el ) => {

				const instance = tippy( el, {
					trigger: 'mouseenter focus',
					content: el.dataset.hoverText || affiliatewp.utils.data.i18n.copyHover,
					placement: 'top',
					duration: [300, 250]
				} );

				el.addEventListener(
					'click',
					() => handleOnClick(
						instance,
						content !== '' ? content : el.dataset.content,
						el.dataset.hoverText || affiliatewp.utils.data.i18n.copyHover,
						el.dataset.successText || affiliatewp.utils.data.i18n.copySuccess,
						el.dataset.errorText || affiliatewp.utils.data.i18n.copyError
					)
				);
			} );
		}
	}
);
