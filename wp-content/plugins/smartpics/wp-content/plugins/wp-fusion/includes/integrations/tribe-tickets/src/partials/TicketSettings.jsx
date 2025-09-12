import { InspectorControls } from '@wordpress/block-editor';
import { useState, useCallback, useEffect, useRef } from '@wordpress/element';
import apiFetch from '@wordpress/api-fetch';
import { PanelBody } from '@wordpress/components';
import { __ } from '@wordpress/i18n';
import { SettingsPanel } from './SettingsPanel';

const ticketSettings = window.wpfTribeTickets.tickets;

export const TicketSettings = ( { attributes } ) => {
	const [ error, setError ] = useState( '' );
	const [ tagValues, setTagValues ] = useState( {
		applyTags: [],
		applyDeletedTags: [],
		applyCheckInTags: [],
		addAttendees: false,
	} );

	const saveRequestInProgress = useRef( false );
	const lastSavedData = useRef( null );

	const { ticketId } = attributes;
	const isDisabled = ticketId === 0;
	const ticketData = ticketId
		? ticketSettings.find( ( ticket ) => ticket[ ticketId ] )
		: false;

	useEffect( () => {
		if ( ! ticketData ) {
			return;
		}

		const settings = ticketData[ ticketId ];
		setTagValues( ( prev ) => ( {
			...prev,
			applyTags: settings.apply_tags || prev.applyTags,
			applyDeletedTags:
				settings.apply_tags_deleted || prev.applyDeletedTags,
			applyCheckInTags:
				settings.apply_tags_checkin || prev.applyCheckInTags,
			addAttendees: settings.add_attendees || prev.addAttendees,
		} ) );
	}, [ ticketData, ticketId ] );

	// eslint-disable-next-line react-hooks/exhaustive-deps
	const saveTags = useCallback(
		async ( newTagValues ) => {
			const snakeCaseValues = {
				apply_tags: newTagValues.applyTags,
				apply_tags_deleted: newTagValues.applyDeletedTags,
				apply_tags_checkin: newTagValues.applyCheckInTags,
				add_attendees: newTagValues.addAttendees,
			};

			const dataString = JSON.stringify( snakeCaseValues );

			if (
				saveRequestInProgress.current ||
				dataString === lastSavedData.current
			) {
				return;
			}

			// Skip if no actual data to send
			if (
				ticketData &&
				JSON.stringify( ticketData[ ticketId ] ) === dataString
			) {
				return;
			}

			// Set flag to prevent concurrent requests
			saveRequestInProgress.current = true;

			try {
				await apiFetch( {
					path: '/wp-fusion/v1/tribe-tickets/update-ticket-tags',
					method: 'POST',
					data: {
						nonce: window.wpfTribeTickets.nonce,
						ticketId,
						...newTagValues,
					},
				} );

				lastSavedData.current = dataString;
			} catch ( err ) {
				setError( 'Failed to update tags: ' + err );
			} finally {
				setTimeout( () => {
					saveRequestInProgress.current = false;
				}, 2000 );
			}
		},
		[ ticketData, ticketId ]
	);

	// Add the saveTagSettings function to the window object to be executed when the save button is clicked.
	useEffect( () => {
		if ( window.wpfTribeTickets ) {
			window.wpfTribeTickets.saveTicketsSettings = () => {
				if ( ! isDisabled ) {
					saveTags( tagValues );
				}
			};
		}
		return () => {
			if ( window.wpfTribeTickets ) {
				delete window.wpfTribeTickets.saveTicketsSettings;
			}
		};
	}, [ tagValues, isDisabled, saveTags ] );

	return (
		<InspectorControls>
			<PanelBody title={ __( 'WP Fusion', 'wp-fusion' ) } initialOpen>
				<SettingsPanel
					tagValues={ tagValues }
					setTagValues={ setTagValues }
					isDisabled={ isDisabled }
				/>

				{ isDisabled && (
					<p
						className="components-panel__body-content"
						style={ { color: 'red' } }
					>
						{ error
							? error
							: __(
									'You should first save the ticket before setting the WP Fusion settings.',
									'wp-fusion'
							  ) }
					</p>
				) }
			</PanelBody>
		</InspectorControls>
	);
};
