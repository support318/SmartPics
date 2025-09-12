import { BaseControl, PanelRow } from '@wordpress/components';

export default function SettingsSection( { label, id, help, children } ) {
	return (
		<PanelRow>
			<BaseControl
				__nextHasNoMarginBottom
				label={ label }
				id={ id }
				help={ help }
			>
				{ children }
			</BaseControl>
		</PanelRow>
	);
}
