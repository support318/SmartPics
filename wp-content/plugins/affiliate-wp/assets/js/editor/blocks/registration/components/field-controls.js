import {
	InspectorControls,
} from '@wordpress/block-editor';
import { PanelBody, ToggleControl } from '@wordpress/components';

import { __ } from '@wordpress/i18n';

const AffiliateWPFieldControls = ( { setAttributes, width, id, required } ) => {
	return (
		<>
			<InspectorControls>

				<PanelBody title={ __( 'Field Settings', 'affiliate-wp' ) }>
					<ToggleControl
						label={ __( 'Field is required', 'affiliate-wp' ) }
						className="affiliatewp-field-label__required"
						checked={ required }
						onChange={ value => setAttributes( { required: value } ) }
						help={ __(
							'Does this field have to be completed for the form to be submitted?',
							'affiliate-wp'
						) }
					/>
				</PanelBody>
			</InspectorControls>
		</>
	);
};

export default AffiliateWPFieldControls;
