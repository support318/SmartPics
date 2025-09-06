window.affwpAdvancedCoupons = window.affwpAdvancedCoupons || ( function( $ ) {

	/**
	 * Back-end vars.
	 *
	 * @since 2.21.0
	 *
	 * @type {object}
	 */
	const vars = affwpAdvancedCouponsVars;

	/**
	 * jQuery elements.
	 *
	 * @since 2.21.0
	 *
	 * @type {object}
	 */
	let el = {}

	/**
	 * Public functions and properties.
	 *
	 * @since 2.21.0
	 *
	 * @type {object}
	 */
	const app = {

		/**
		 * Initiate.
		 *
		 * @since 2.21.0
		 */
		init: function() {
			$( app.ready );
		},

		/**
		 * Set elements and events.
		 *
		 * @since 2.21.0
		 */
		ready: function() {
			el = {
				$table: $( '#affwp-affiliate-dashboard-coupons .affwp-table' ),
			}

			app.events();
		},

		/**
		 * Set jQuery events.
		 *
		 * @since 2.21.0
		 */
		events: function() {
			// Bind events.
			el.$table.on( 'click', '.affwp-coupon-url', app.onCopy );

			// Initialize all tooltips.
			app.updateTooltips();
		},

		/**
		 * Initialize action tooltips not initialized yet.
		 *
		 * @since 2.21.0
		 */
		updateTooltips: function() {
			affiliatewp.tooltip.show(
				'.affwp-row-header span.affwp-tooltip-help:not(.affwp-tooltip-initialized)',
				null,
				{
					trigger: 'mouseenter focus',
					hideDelay: 0,
				}
			);

			// Initialize URL copy tooltip.
			app.initializeButtons(
				'.affwp-tooltip-url-copy:not(.affwp-tooltip-initialized)',
				'click',
				vars.i18n.copySuccess,
				1000
			);

			// Initialize copy button tooltip.
			app.initializeButtons(
				'.affwp-tooltip-button-copy:not(.affwp-tooltip-initialized)',
				'mouseenter focus',
				vars.i18n.copySuccess,
				1000,
				vars.i18n.copyCouponURL,
				true
			);
		},

		/**
		 * Initialize tooltip buttons.
		 *
		 * @since 2.21.0
		 *
		 * @param className
		 * @param trigger
		 * @param content
		 * @param hideDelay
		 * @param resetContent
		 * @param manualTrigger
		 */
		initializeButtons: function(
			className,
			trigger,
			content,
			hideDelay,
			resetContent = null,
			manualTrigger = false
		) {
			const buttons = document.querySelectorAll( className );
			buttons.forEach(( button ) => {
				const instance = tippy( button, {
					trigger: trigger,
					duration: [300, 250],
					hideOnClick: false,
					onCreate: ( instance ) => {
						instance.reference.classList.add( 'affwp-tooltip-initialized' );
					}
				} );

				button.addEventListener(
					'click',
					() => app.handleButtonClick( instance, content, hideDelay, resetContent, manualTrigger )
				);
			} );
		},

		/**
		 * Handle tooltip click buttons.
		 *
		 * @since 2.21.0
		 *
		 * @param instance
		 * @param content
		 * @param hideDelay
		 * @param resetContent
		 * @param manualTrigger
		 */
		handleButtonClick: function(
			instance,
			content,
			hideDelay,
			resetContent = null,
			manualTrigger = false
		) {
			instance.setContent( content );
			instance.show();

			if ( manualTrigger ) {
				instance.setProps( { trigger: 'manual' } );
			}

			setTimeout(
				() => {
					instance.hide();
					if ( manualTrigger ) {
						instance.setProps( { trigger: 'mouseenter focus' } );
					}
				},
				hideDelay
			);

			if ( ! resetContent ) {
				return;
			}

			instance.setProps( {
				onHidden: () => {
					instance.setContent( resetContent );
					instance.setProps( { onHidden: null } );
				},
			} );
		},

		/**
		 * Runs every time the copy link is clicked.
		 *
		 * @since 2.21.0
		 */
		onCopy: function( event ) {
			/**
			 * Prevent default behavior of hyperlinks.
			 *
			 * @since 2.21.0
			 */
			event.preventDefault();

			app.copyToClipboard( $( this ).closest( 'div' ).data( 'coupon-id' ) );
		},

		/**
		 * Copy a url to the clipboard and optionally display a small tooltip to the user.
		 *
		 * @since 2.21.0
		 * @param customLinkID The coupon link ID to be copied.
		 */
		copyToClipboard: function( couponID ) {
			// The row to be copied.
			const $row = el.$table.find( `tbody tr div[data-coupon-id="${couponID}"]` );

			if ( ! $row.length ) {
				return; // Do nothing, invalid row.
			}

			// Copy button jQuery object.
			const $copyBtn = $row.find( '.affwp-copy-coupon-url' );

			if ( $copyBtn.hasClass( 'copied' ) ) {
				return; // Copy animation in progress, prevent multiple clicks.
			}

			// Bail if the browser doesn't support the clipboard API.
			if ( ! navigator || ! navigator.clipboard ) {
				return;
			}

			navigator.clipboard.writeText( $row.data( 'coupon-url' ) );
		},

	}

	return app;

} )( jQuery );

// Initialize.
affwpAdvancedCoupons.init();
