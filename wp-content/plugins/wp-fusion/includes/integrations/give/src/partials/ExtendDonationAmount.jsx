import { InspectorControls } from '@wordpress/block-editor';
import { PanelBody, ExternalLink } from '@wordpress/components';
import { useEffect, useCallback } from '@wordpress/element';
import { __, sprintf } from '@wordpress/i18n';
import AmountTagSelector from '../components/AmountTagSelector';

export default function ExtendDonationAmount( { attributes, setAttributes } ) {
	const { levels = [], tagLevels = [] } = attributes;
	const applyTags = window.wpfGiveSettings.settings.apply_tags_level;

	useEffect( () => {
		const newLevels = levels.map( ( level, index ) => {
			const id = level.id || level.value;
			const label =
				level.label && level.label.length > 0
					? level.label
					: level.value;

			const tags = applyTags ? applyTags[ index ] : false;
			const attributeTags = tagLevels.find(
				( obj ) => obj.id === id
			)?.tags;

			return {
				id,
				label,
				tags: attributeTags ?? tags,
			};
		} );

		setAttributes( { tagLevels: newLevels } );
		// We disable the line because adding tagLevels to the dependency array causes a loop.
		// eslint-disable-next-line react-hooks/exhaustive-deps
	}, [ levels, setAttributes ] );

	const updateLevelTags = useCallback(
		( levelId, tags ) => {
			setAttributes( {
				tagLevels: tagLevels.map( ( level ) =>
					level.id === levelId ? { ...level, tags } : level
				),
			} );
		},
		[ tagLevels, setAttributes ]
	);

	return (
		<>
			<InspectorControls>
				<PanelBody
					title={ __( 'WP Fusion', 'wp-fusion' ) }
					initialOpen={ false }
				>
					<p>
						{ sprintf(
							// translators: %s is the CRM name
							__(
								'For each donation level select one or more tags to apply in %s when a donation is given. For more information, ',
								'wp-fusion'
							),
							window.wpf_admin.crm_name
						) }
						{ ' ' }
						<ExternalLink href="https://wpfusion.com/documentation/ecommerce/give/">
							{ __( 'see the documentation', 'wp-fusion' ) }
						</ExternalLink>
					</p>
					{ tagLevels.map( ( level ) => (
						<AmountTagSelector
							key={ `wpf-level-selector-${ level.id }` }
							level={ level }
							updateLevelTags={ updateLevelTags }
						/>
					) ) }
				</PanelBody>
			</InspectorControls>
		</>
	);
}
