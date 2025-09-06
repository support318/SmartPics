/**
 * Affiliate registration field label component
 *
 * @since 2.8
 */

/**
 * External dependencies
 */
import { __ } from '@wordpress/i18n';
import { RichText } from '@wordpress/block-editor';

const AffiliateWPFieldLabel = ({
	setAttributes,
	label,
	labelFieldName,
	placeholder,
	resetFocus,
	required,
}) => {

	return (
		<div className="affwp-field-label">
			<RichText
				tagName="label"
				value={label}
				onChange={ value => {

					if ( resetFocus ) {
						resetFocus();
					}

					if ( labelFieldName ) {
						setAttributes( { [ labelFieldName ]: value } );
						return;
					}

					setAttributes( { label: value } );

				} }
				placeholder={ placeholder ?? __( 'Add label…', 'affiliate-wp' ) }
				withoutInteractiveFormatting
				allowedFormats={[]}
			/>

			{ required && (
				<span className="required">{ __( '(required)', 'affiliate-wp' ) }</span>
			) }

		</div>
	);
};

export default AffiliateWPFieldLabel;
