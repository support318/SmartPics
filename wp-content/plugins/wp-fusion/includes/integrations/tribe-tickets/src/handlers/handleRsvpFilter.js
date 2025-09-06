import { createHigherOrderComponent } from '@wordpress/compose';
import { subscribe, select } from '@wordpress/data';
import { RSVPSettings } from '../partials/RSVPSettings';

// We don't use state here because we're using the subscribe function to check if the post is being saved.
// If we used state, the component would re-render and the save function would be called again, which would cause an infinite loop.
let isSaveInProgress = false;
let lastSaveTimestamp = 0;

export const handleRsvpFilter = createHigherOrderComponent( ( BlockEdit ) => {
	return ( props ) => {
		if ( props.name !== 'tribe/rsvp' ) {
			return <BlockEdit { ...props } />;
		}

		subscribe( () => {
			const isSaving = select( 'core/editor' ).isSavingPost();
			const isAutosaving = select( 'core/editor' ).isAutosavingPost();
			const currentTime = Date.now();

			if ( isSaving && ! isAutosaving ) {
				if (
					! isSaveInProgress &&
					currentTime - lastSaveTimestamp > 3000 &&
					window.wpfTribeTickets &&
					window.wpfTribeTickets.saveRsvpSettings
				) {
					isSaveInProgress = true;
					lastSaveTimestamp = currentTime;
					window.wpfTribeTickets.saveRsvpSettings();

					setTimeout( () => {
						isSaveInProgress = false;
					}, 3000 );
				}
			}
		} );

		return (
			<>
				<BlockEdit { ...props } />
				<RSVPSettings { ...props } />
			</>
		);
	};
}, 'handleRsvpFilter' );
