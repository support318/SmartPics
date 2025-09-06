import { useState } from '@wordpress/element';
import { __, sprintf } from '@wordpress/i18n';
import WpfSelect from '@verygoodplugins/wpfselect';
import SettingsSection from '../components/SettingsSection';

const formatTags = ( tags ) => {
	if ( Array.isArray( tags ) ) {
		return tags;
	}

	if ( typeof tags === 'object' ) {
		return [ tags ];
	}

	return [];
};

export default function WPFusionSettings( {
	settings: propSettings,
	setSettings,
} ) {
	const localSettings = window.wpfGiveSettings;
	const initialSettings = localSettings.settings;
	const settings = propSettings || initialSettings;

	const [ applyTags, setApplyTags ] = useState(
		formatTags( window.wpfGiveSettings?.apply_tags || [] )
	);

	const [ applyTagsRecurring, setApplyTagsRecurring ] = useState(
		formatTags( window.wpfGiveSettings?.apply_tags_recurring || [] )
	);

	const [ applyTagsCancelled, setApplyTagsCancelled ] = useState(
		formatTags( window.wpfGiveSettings?.apply_tags_cancelled || [] )
	);

	const [ applyTagsFailed, setApplyTagsFailed ] = useState(
		formatTags( window.wpfGiveSettings?.apply_tags_failed || [] )
	);

	const [ applyTagsOffline, setApplyTagsOffline ] = useState(
		formatTags( window.wpfGiveSettings?.apply_tags_offline || [] )
	);

	const handleTagsChange = ( field, value ) => {
		const newSettings = {
			apply_tags: applyTags.map( ( tag ) => tag.value ),
			apply_tags_recurring: applyTagsRecurring.map(
				( tag ) => tag.value
			),
			apply_tags_cancelled: applyTagsCancelled.map(
				( tag ) => tag.value
			),
			apply_tags_failed: applyTagsFailed.map( ( tag ) => tag.value ),
			apply_tags_offline: applyTagsOffline.map( ( tag ) => tag.value ),
			...settings,
		};

		newSettings[ field ] = value ? value.map( ( tag ) => tag.value ) : [];

		setSettings( newSettings );
	};

	return (
		<div className="givewp-form-settings__content">
			<div className="givewp-form-settings__section">
				<div className="givewp-form-settings__section__header">
					<h4>{ __( 'Tags', 'wp-fusion' ) }</h4>
					<p>
						{ sprintf(
							// translators: %s is the CRM name
							__(
								'Configure which tags will be applied in %s when donations are processed.',
								'wp-fusion'
							),
							window.wpf_admin.crm_name
						) }
					</p>
				</div>
				<div className="givewp-form-settings__section__body">
					<SettingsSection
						label={ __( 'Apply Tags', 'wp-fusion' ) }
						id="wpf-give-tags"
						help={ sprintf(
							// translators: %s is the CRM name
							__(
								'Apply these tags in %s when a donation is given.',
								'wp-fusion'
							),
							window.wpf_admin.crm_name
						) }
					>
						<WpfSelect
							existingTags={ applyTags }
							onChange={ ( value ) => {
								setApplyTags( value );
								handleTagsChange( 'apply_tags', value );
							} }
							elementID="wpf-give-tags"
						/>
					</SettingsSection>

					<SettingsSection
						label={ __( 'Apply Tags - Offline', 'wp-fusion' ) }
						id="wpf-give-tags-offline"
						help={ sprintf(
							// translators: %s is the CRM name
							__(
								'Apply these tags in %s when an offline donation is given.',
								'wp-fusion'
							),
							window.wpf_admin.crm_name
						) }
					>
						<WpfSelect
							existingTags={ applyTagsOffline }
							onChange={ ( value ) => {
								setApplyTagsOffline( value );
								handleTagsChange( 'apply_tags_offline', value );
							} }
							elementID="wpf-give-tags-offline"
						/>
					</SettingsSection>

					{ localSettings.recurringEnabled && (
						<>
							<SettingsSection
								label={ __(
									'Apply Tags - Recurring',
									'wp-fusion'
								) }
								id="wpf-give-tags-recurring"
								help={ __(
									'Apply these tags when a recurring donation is given (in addition to Apply Tags).',
									'wp-fusion'
								) }
							>
								<WpfSelect
									existingTags={ applyTagsRecurring }
									onChange={ ( value ) => {
										setApplyTagsRecurring( value );
										handleTagsChange(
											'apply_tags_recurring',
											value
										);
									} }
									elementID="wpf-give-tags-recurring"
								/>
							</SettingsSection>

							<SettingsSection
								label={ __(
									'Apply Tags - Cancelled',
									'wp-fusion'
								) }
								id="wpf-give-tags-cancelled"
								help={ __(
									'Apply these tags when a recurring donation is cancelled.',
									'wp-fusion'
								) }
							>
								<WpfSelect
									existingTags={ applyTagsCancelled }
									onChange={ ( value ) => {
										setApplyTagsCancelled( value );
										handleTagsChange(
											'apply_tags_cancelled',
											value
										);
									} }
									elementID="wpf-give-tags-cancelled"
								/>
							</SettingsSection>

							<SettingsSection
								label={ __(
									'Apply Tags - Failed',
									'wp-fusion'
								) }
								id="wpf-give-tags-failed"
								help={ __(
									'Apply these tags when a recurring donation payment has failed.',
									'wp-fusion'
								) }
							>
								<WpfSelect
									existingTags={ applyTagsFailed }
									onChange={ ( value ) => {
										setApplyTagsFailed( value );
										handleTagsChange(
											'apply_tags_failed',
											value
										);
									} }
									elementID="wpf-give-tags-failed"
								/>
							</SettingsSection>
						</>
					) }
				</div>
			</div>
		</div>
	);
}
