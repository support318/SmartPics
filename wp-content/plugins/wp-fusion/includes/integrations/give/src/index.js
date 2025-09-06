import { addFilter } from '@wordpress/hooks';
import { __ } from '@wordpress/i18n';
import WPFusionSettings from './partials/WPFusionSettings';
import { createHigherOrderComponent } from '@wordpress/compose';
import ExtendDonationAmount from './partials/ExtendDonationAmount';

const newRoute = {
	name: __( 'WP Fusion', 'wp-fusion' ),
	path: 'wp-fusion',
	element: WPFusionSettings,
};

// Register the settings route.
addFilter(
	'givewp_form_builder_settings_additional_routes',
	'wp-fusion/wp-fusion-route',
	( routes ) => {
		return [ ...routes, newRoute ];
	}
);

// Add amount block settings UI.
addFilter(
	'editor.BlockEdit',
	'wp-fusion/wp-fusion-donation-extension',
	createHigherOrderComponent( ( BlockEdit ) => {
		return ( props ) => {
			if ( props.name !== 'givewp/donation-amount' ) {
				return <BlockEdit { ...props } />;
			}

			return (
				<>
					<BlockEdit { ...props } />
					<ExtendDonationAmount { ...props } />
				</>
			);
		};
	}, 'withInspectorControls' )
);
