/**
 * Affiliate Registration Form Terms of Use Edit Component.
 *
 * @since 2.10.0
 */

/**
 * External dependencies
 */
import classnames from 'classnames';

/**
 * WordPress dependencies
 */
import { __, _x, sprintf } from '@wordpress/i18n';
import {
	InspectorControls,
	useBlockProps,
	RichText,
	LinkControl as __stableLinkControl,
	__experimentalLinkControl,
} from '@wordpress/block-editor';

import {
	PanelBody,
	TextControl,
	Placeholder,
	ExternalLink,
	Icon,
	RadioControl,
	BaseControl,
	ToggleControl,
} from '@wordpress/components';

import { useState, useEffect } from '@wordpress/element';

import { useSelect } from '@wordpress/data';

function AffiliateWPFieldTermsOfUse( { attributes, setAttributes, isSelected, resetFocus, name, context, clientId, } ) {

	const {
		required,
		label,
		link,
		id,
		style,
	} = attributes;

	const [placeholder, setPlaceholder] = useState( false );
	const [newLink, setNewLink] = useState( attributes.link );

	const LinkControl = __stableLinkControl
		? __stableLinkControl
		: __experimentalLinkControl;

	const pageContent = useSelect((select) => {
		if ( ! id ) {
			return;
		}

		let pageContent = select('core').getEntityRecord('postType', 'page', id );
		return pageContent?.content?.raw
	});

	const blockProps = useBlockProps();

	const fieldClassNames = classnames(
		'affwp-field',
		'affwp-field-terms-of-use'
	);

	const icon = <Icon
		icon={
		<svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" height={24} width={24} ><g><path d="M17.25.75H3.75a3,3,0,0,0-3,3v18a1.5,1.5,0,0,0,1.5,1.5H3.68" fill="none" stroke="#000000" strokeLinecap="round" strokeLinejoin="round" strokeWidth="2px" /><line x1={3.75} y1={5.5} x2={11} y2={5.5} fill="none" stroke="#000000" strokeLinecap="round" strokeLinejoin="round" strokeWidth="2px" /><line x1={3.75} y1={9.5} x2={8.61} y2={9.5} fill="none" stroke="#000000" strokeLinecap="round" strokeLinejoin="round" strokeWidth="2px" /><path d="M15,9.75V3A2.25,2.25,0,0,1,17.25.75h0A2.25,2.25,0,0,1,19.5,3V5.5H15" fill="none" stroke="#000000" strokeLinecap="round" strokeLinejoin="round" strokeWidth="2px" /><g><line x1={21.75} y1={19.61} x2={16.96} y2={20.57} fill="none" stroke="#000000" strokeLinecap="round" strokeLinejoin="round" strokeWidth="2px" /><path d="M17.44,15.14l-2.26.95a1.41,1.41,0,0,1-1.12,0A1.52,1.52,0,0,1,14,13.35l2.26-1.13a2,2,0,0,1,.9-.22,1.8,1.8,0,0,1,.69.13L22.47,14" fill="none" stroke="#000000" strokeLinecap="round" strokeLinejoin="round" strokeWidth="2px" /><path d="M8.2,20.61H9.79l3.05,2.32A.82.82,0,0,0,14,23l4.26-3.52a.83.83,0,0,0,.13-1.16L16,15.73" fill="none" stroke="#000000" strokeLinecap="round" strokeLinejoin="round" strokeWidth="2px" /><path d="M13.74,13.51l-.25-.21A1.83,1.83,0,0,0,12.43,13a1.93,1.93,0,0,0-.67.12L8.19,14.6" fill="none" stroke="#000000" strokeLinecap="round" strokeLinejoin="round" strokeWidth="2px" /><path d="M6.75,21.36h.3a1.14,1.14,0,0,0,1.2-1.08V14.93a1.14,1.14,0,0,0-1.2-1.07h-.3" fill="none" stroke="#000000" strokeLinecap="round" strokeLinejoin="round" strokeWidth="2px" /><path d="M23.25,21.36H23a1.14,1.14,0,0,1-1.2-1.08V14.93A1.14,1.14,0,0,1,23,13.86h.3" fill="none" stroke="#000000" strokeLinecap="round" strokeLinejoin="round" strokeWidth="2px" /></g></g></svg>
		}
	/>

	useEffect(() => {
		if( link ) {
			return
		}

		if( 1 === style ) {
			setPlaceholder( true )
		}

		return () => { setPlaceholder( false ) }

	}, [link]);

	const updateLink = (newLink) => {
		setNewLink(newLink);

		setAttributes({
			link: newLink.url,
			id: 'URL' !== newLink.type ? newLink.id : undefined,
			// Set the label for the first time.
			label: sprintf(
				// translators: %1$s: open link tag, %2$s close link tag
				__( 'Agree to our %1$sTerms of Use and Privacy Policy%2$s', 'affiliate-wp' ),
				`<a href="${newLink.url}" target="_blank">`,
				'</a>'
			)
		})
	};

	return (
		<div {...blockProps}>

			{ false === placeholder &&
			<InspectorControls>
				<PanelBody
					title={ __( 'Field settings', 'affiliate-wp' ) }
					initialOpen={true}
					className="panel-terms-of-use"
				>

					<ToggleControl
						label={ __( 'Required', 'affiliate-wp' ) }
						className="affwp-field-label__required"
						checked={required}
						onChange={( required ) => setAttributes( { required } )}
					/>

					<RadioControl
						label={ __( 'Display Style', 'affiliate-wp' ) }
						selected={ style }
						options={ [
							{
								label: __( 'Default', 'affiliate-wp' ),
								value: 1
							},
							{
								label: __( 'Show above checkbox', 'affiliate-wp' ),
								value: 2
							},
						] }
						onChange={ ( value ) =>
							setAttributes( {
								style: parseInt(value),
							} )
						}
					/>

					{ 2 === style &&
					<BaseControl label={ __( 'Page Content to Display', 'affiliate-wp' ) } __nextHasNoMarginBottom={ true }>
						<LinkControl
							searchInputPlaceholder={ __( 'Terms of Use Page', 'affiliate-wp' ) }
							value={{
								url: attributes.link,
							}}
							onChange={(value) => {
								setAttributes({
									link: value.url,
									id: 'URL' !== value.type ? value.id : undefined,
								})
							}}
							onRemove={ () => {
								setAttributes({
									link: undefined,
									id: undefined,
								})
							}}
							settings={ [] }
							suggestionsQuery={{ type: "post", subtype: 'page' }}
						/>
					</BaseControl>
					}

					<TextControl
						label={ __( 'Field Label', 'affiliate-wp' ) }
						value={ label }
						onChange={ ( label ) => setAttributes( { label } ) }
					/>

				</PanelBody>
			</InspectorControls>
		}

		{ true === placeholder ? (
		/* The Placeholder component is only shown when the block is first inserted, and there's no Terms of Use page selected in the settings */
		<Placeholder icon={ icon } label={ __( 'Affiliate Terms of Use', 'affiliate-wp' ) }>

			<p>{ __( 'Select your Affiliate Terms of Use page below.', 'affiliate-wp' ) } <ExternalLink href={ affwp_blocks.terms_of_use_generator }>{ __( 'Create one using a template', 'affiliate-wp' ) }</ExternalLink></p>

			<LinkControl
				value={newLink}
				onChange={updateLink}
				searchInputPlaceholder={ __( 'Select a Terms of Use Page', 'affiliate-wp' ) }
				settings={ [] }
				suggestionsQuery={{ type: "post", subtype: 'page' }}
			/>
		</Placeholder>

		) : (
		<>
		{ 1 === style &&
			<>
				<input
					className={fieldClassNames}
					type="checkbox"
				/>

				<RichText
					identifier={'label'}
					tagName="label"
					value={label}
					onChange={( label ) => {
						if ( resetFocus ) {
							resetFocus();
						}
						setAttributes( { label } );
					}}
					placeholder={ __( 'Add label ...', 'affiliate-wp' ) }
				/>
			</>
		}

		{ 2 === style &&
			<>
				{ ( ! id ) &&
				<p className="affwp-error-notice">{ __( 'No Terms of Use page selected.', 'affiliate-wp' ) }</p>
				}

				<div className="affwp-field-terms-of-use-content" dangerouslySetInnerHTML={{__html: pageContent}} />

				<input
					className={fieldClassNames}
					type="checkbox"
				/>

				<RichText
					identifier={'label'}
					tagName="label"
					value={label}
					onChange={( label ) => {
						if ( resetFocus ) {
							resetFocus();
						}
						setAttributes( { label } );
					}}
					placeholder={ __( 'Add label ...', 'affiliate-wp' ) }
				/>
			</>
		}
		</>
		)}
		</div>
	)
}
export default AffiliateWPFieldTermsOfUse;