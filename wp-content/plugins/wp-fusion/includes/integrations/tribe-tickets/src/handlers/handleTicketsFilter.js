import { createHigherOrderComponent } from '@wordpress/compose';
import { subscribe, select } from '@wordpress/data';
import { TicketSettings } from '../partials/TicketSettings';

// We don't use state here because we're using the subscribe function to check if the post is being saved.
// If we used state, the component would re-render and the save function would be called again, which would cause an infinite loop.
let saveCounter = 0; // Track save operations
let isSaveInProgress = false;

export const handleTicketsFilter = createHigherOrderComponent(
	( BlockEdit ) => {
		return ( props ) => {
			if ( props.name !== 'tribe/tickets-item' ) {
				return <BlockEdit { ...props } />;
			}

			subscribe( () => {
				const isSaving = select( 'core/editor' ).isSavingPost();
				const isAutosaving = select( 'core/editor' ).isAutosavingPost();

				if ( isSaving && ! isAutosaving && ! isSaveInProgress ) {
					const currentSaveCounter = ++saveCounter; // Increment and capture current counter
					isSaveInProgress = true;

					if (
						window.wpfTribeTickets &&
						window.wpfTribeTickets.saveTicketsSettings
					) {
						window.wpfTribeTickets.saveTicketsSettings();

						const unsubscribe = subscribe( () => {
							const didSucceed =
								select(
									'core/editor'
								).didPostSaveRequestSucceed();
							const didFail =
								select(
									'core/editor'
								).didPostSaveRequestFail();

							if (
								( didSucceed || didFail ) &&
								saveCounter === currentSaveCounter
							) {
								isSaveInProgress = false;
								unsubscribe();
							}
						} );
					}
				}
			} );

			return (
				<>
					<BlockEdit { ...props } />
					<TicketSettings { ...props } />
				</>
			);
		};
	},
	'withInspectorControls'
);
