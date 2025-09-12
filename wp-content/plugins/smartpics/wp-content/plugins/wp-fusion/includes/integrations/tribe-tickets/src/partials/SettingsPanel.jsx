import { BaseControl, ToggleControl } from '@wordpress/components';
import { __, sprintf } from '@wordpress/i18n';
import WpfSelect from '@verygoodplugins/wpfselect';

export const SettingsPanel = ( {
	tagValues,
	setTagValues,
	isDisabled = false,
} ) => {
	const { applyTags, applyDeletedTags, applyCheckInTags, addAttendees } =
		tagValues;

	const setTags = ( key, value ) => {
		setTagValues( ( prev ) => ( { ...prev, [ key ]: value } ) );
	};

	return (
		<>
			<BaseControl
				id="wpf-tag-select-apply"
				label={ __( 'Apply Tags', 'wp-fusion' ) }
			>
				<WpfSelect
					id="wpf-tag-select-apply"
					existingTags={ applyTags || [] }
					onChange={ ( value ) => setTags( 'applyTags', value ) }
					isDisabled={ isDisabled }
				/>
			</BaseControl>
			<BaseControl
				id="wpf-tag-select-apply-deleted"
				label={ __( 'Apply Tags - Deleted', 'wp-fusion' ) }
			>
				<WpfSelect
					id="wpf-tag-select-apply-deleted"
					existingTags={ applyDeletedTags || [] }
					onChange={ ( value ) =>
						setTags( 'applyDeletedTags', value )
					}
					isDisabled={ isDisabled }
				/>
			</BaseControl>
			<BaseControl
				id="wpf-tag-select-apply-check-in"
				label={ __( 'Apply Tags - Check-In', 'wp-fusion' ) }
			>
				<WpfSelect
					id="wpf-tag-select-apply-check-in"
					existingTags={ applyCheckInTags || [] }
					onChange={ ( value ) =>
						setTags( 'applyCheckInTags', value )
					}
					isDisabled={ isDisabled }
				/>
			</BaseControl>
			<BaseControl id="wpf-tag-select-add-attendees">
				<ToggleControl
					__nextHasNoMarginBottom
					checked={ addAttendees }
					label={ __( 'Add Attendees', 'wp-fusion' ) }
					onChange={ () => setTags( 'addAttendees', ! addAttendees ) }
					disabled={ isDisabled }
					help={
						<p
							dangerouslySetInnerHTML={ {
								__html: sprintf(
									'Add each event attendee as a separate contact in FluentCRM. Requires <a href="%s" target="_blank">Individual Attendee Collection</a> to be enabled for this ticket.',
									'https://theeventscalendar.com/knowledgebase/k/collecting-attendee-information/'
								),
							} }
						/>
					}
				/>
			</BaseControl>
			<p
				dangerouslySetInnerHTML={ {
					__html: sprintf(
						// translators: Documentation URL.
						__(
							'For more information on these settings, <a href="%s" target="_blank">see our documentation</a>.',
							'wp-fusion'
						),
						window.wpfTribeTickets.docs_url
					),
				} }
			></p>
		</>
	);
};
