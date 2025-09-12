import WpfSelect from '@verygoodplugins/wpfselect';
import { BaseControl } from '@wordpress/components';
import { __, sprintf } from '@wordpress/i18n';

/**
 * Format an amount with a currency symbol.
 *
 * @param {string} level The donation level.
 * @return {string} Formatted amount with currency symbol.
 */
const formatAmount = ( level ) => {
	// Check if the level label is numeric
	if ( isNaN( Number( level ) ) ) {
		return level;
	}

	// Get currency symbol from GiveCampaignOptions
	let currencySymbol = '$';
	
	if ( window.GiveCampaignOptions && window.GiveCampaignOptions.currencySymbol ) {
		currencySymbol = window.GiveCampaignOptions.currencySymbol;
	}

	return currencySymbol + level;
};

export default function AmountTagSelector( { level, updateLevelTags } ) {
	const formattedLabel = formatAmount( level.label );

	return (
		<BaseControl
			id={ `wpf-tag-select-${ level.id }` }
			label={ sprintf( __( 'Apply Tags - %s', 'wp-fusion' ), formattedLabel ) }
		>
			<WpfSelect
				id={ `wpf-tag-select-${ level.id }` }
				existingTags={ level.tags }
				onChange={ ( value ) => {
					updateLevelTags( level.id, value );
				} }
			/>
		</BaseControl>
	);
}
