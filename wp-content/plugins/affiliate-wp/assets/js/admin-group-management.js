/**
 * Select2 Initializations.
 *
 * @see includes/utils/trait-select2.php::init_select2() where you can
 *      automatically enqueue this script and set it up for select2 selectors.
 *
 * @since 2.12.0
 */

/* eslint no-alert: off */

( function () {

	if (
		! window.hasOwnProperty( 'jQuery' ) ||
		! window.hasOwnProperty( 'affwpGroupManagment' )
	) {
		return; // We need these to be enqueued and localized by the trait.
	}

	/**
	 * Confirm Deletion
	 *
	 * @since  2.12.0
	 *
	 * @return {void}
	 */
	function confirmDeletions() {

		const $elements = window.jQuery( window.affwpGroupManagment.delete.selector );

		if ( ! $elements.length ) {
			return; // Fail gracefully, there may not be groups to select (no <select>).
		}

		$elements.each( function() {

			window.jQuery( this ).on( 'click', function( e ) {

				e.preventDefault();

				if ( ! window.confirm( window.affwpGroupManagment.delete.message ) ) {
					return;
				}

				window.location.href = window.jQuery( this ).attr( 'href' );

			} );

		} );
	}

	confirmDeletions();

} ) ();