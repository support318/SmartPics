/**
 * Dependant Fields
 *
 * Helps hide fields (tr rows in admin) that are dependant on other input fields.
 *
 * To setup a field (a <tr> row), add:
 *
 *     data-trigger-on (Event e.g. change)
 *     data-trigger-id (The ID of the <input id="" />)
 *
 * ...and make sure the <tr> row has a `hidden` class to hide it initially.
 * then, for checkboxes, add:
 *
 *     data-trigger-is (e.g. ":checked")
 *
 * This would keep the:
 *
 *     <tr
 *         data-trigger-on="change"
 *         data-trigger-id="my-id"
 *         data-trigger-is=":checked">
 *
 * ...row hidden until <input id="my-id" class="hidden">'s change event is
 * triggered and .is( :checked ) is true.
 *
 * @since 2.9.6 Right now this has implimentation for jQuery is( ... ),
 *              but could be extended to do other tests, like
 *              setting data-trigger-val="value" and we could add
 *              a test for .val() ===, etc.
 */

/**
 * DOM Ready.
 *
 * @since 2.9.6
 *
 * @param {Object} $ jQuery
 */
( function ( $ ) {

	if ( false === $ ) {

		// Requires jQuery.
		return;
	}

	const dataAttr = 'data-trigger-id'; // The attribute hidden <tr>'s should have.

	const $hidden = $( 'tr.hidden[' + dataAttr + ']' ); // Only <tr class="hidden" data-trigger-id="...">.

	if ( $hidden.length <= 0 ) {
		return; // No hideen fields found.
	}

	// Loop over each and setup their trigger to show them.
	$hidden.each( function( i, row ) {

		const $row = $( row );

		if ( $row.length <= 0 ) {
			return; // Can't find it in the DOM anymore.
		}

		// Find the input designated as this row's trigger...
		const $trigger = $( 'input#' + $row.attr( dataAttr ) );

		if ( $trigger.length <= 0 ) {
			return; // The trigger isn't in the DOM anymore.
		}

		// Your row needs to know what even on the <input> to trigger a test.
		const onEvent = $row.data( 'trigger-on' );

		if ( 'string' !== typeof onEvent ) {
			return; // Required event data not found.
		}

		// Bind a test (on th event specified) to the <input>...
		$trigger.on( onEvent, function( event ) {

			// Do an .is() check...
			const is = $row.data( 'trigger-is' );

			if (
				'string' === typeof is &&
				true === $( event.currentTarget ).is( is )
			) {

				$row.removeClass( 'hidden' );

				return;
			}

			// No test passed, so keep hidden.
			$row.addClass( 'hidden' );
		} );
	} );
} ( window.jQuery || false ) );
