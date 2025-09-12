import { InspectorControls } from '@wordpress/block-editor';
import { PanelBody } from '@wordpress/components';
import { __ } from '@wordpress/i18n';
import { useState, useCallback, useEffect, useRef } from '@wordpress/element';
import apiFetch from '@wordpress/api-fetch';
import { select } from '@wordpress/data';
import { SettingsPanel } from './SettingsPanel';

const globalRsvpSettings = window.wpfTribeTickets.rsvp || {};

export const RSVPSettings = () => {
	const [ error, setError ] = useState( null );
	const [ tagValues, setTagValues ] = useState( {
		applyTags: globalRsvpSettings.apply_tags || [],
		applyDeletedTags: globalRsvpSettings.apply_tags_deleted || [],
		applyCheckInTags: globalRsvpSettings.apply_tags_checkin || [],
		addAttendees: globalRsvpSettings.add_attendees || false,
	} );

	const postId = select( 'core/editor' ).getCurrentPostId();
	const saveRequestInProgress = useRef( false );
	const lastSavedData = useRef( null );

	// eslint-disable-next-line react-hooks/exhaustive-deps
	const saveTags = useCallback( async ( newTagValues ) => {
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

		if (
			JSON.stringify( {
				apply_tags: globalRsvpSettings.apply_tags || [],
				apply_tags_deleted: globalRsvpSettings.apply_tags_deleted || [],
				apply_tags_checkin: globalRsvpSettings.apply_tags_checkin || [],
				add_attendees: globalRsvpSettings.add_attendees || false,
			} ) === dataString
		) {
			return;
		}

		saveRequestInProgress.current = true;

		try {
			await apiFetch( {
				path: '/wp-fusion/v1/tribe-tickets/update-rsvp-tags',
				method: 'POST',
				data: {
					nonce: window.wpfTribeTickets.nonce,
					postId,
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
	} );

	// Add the saveTagSettings function to the window object to be executed when the save button is clicked.
	useEffect( () => {
		if ( window.wpfTribeTickets ) {
			window.wpfTribeTickets.saveRsvpSettings = () => {
				saveTags( tagValues );
			};
		}
		return () => {
			if ( window.wpfTribeTickets ) {
				delete window.wpfTribeTickets.saveRsvpSettings;
			}
		};
	}, [ tagValues, saveTags ] );

	return (
		<InspectorControls>
			<PanelBody title={ __( 'WP Fusion', 'wp-fusion' ) } initialOpen>
				<SettingsPanel
					tagValues={ tagValues }
					setTagValues={ setTagValues }
				/>
				{ error && (
					<p
						className="components-panel__body-content"
						style={ { color: 'red' } }
					>
						{ error }
					</p>
				) }
			</PanelBody>
		</InspectorControls>
	);
};
