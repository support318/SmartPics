/**
 * Affiliate registration Terms of Use field Block.
 *
 * @since 2.10.0
 */

/**
 * Internal dependencies
 */
import edit from './edit';

/**
 * WordPress dependencies
 */
import { __ } from '@wordpress/i18n';
import { Icon } from '@wordpress/components';

const icon = <Icon
	icon={
	<svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" height={24} width={24} ><g><path d="M17.25.75H3.75a3,3,0,0,0-3,3v18a1.5,1.5,0,0,0,1.5,1.5H3.68" fill="none" stroke="#000000" strokeLinecap="round" strokeLinejoin="round" strokeWidth="2px" /><line x1={3.75} y1={5.5} x2={11} y2={5.5} fill="none" stroke="#000000" strokeLinecap="round" strokeLinejoin="round" strokeWidth="2px" /><line x1={3.75} y1={9.5} x2={8.61} y2={9.5} fill="none" stroke="#000000" strokeLinecap="round" strokeLinejoin="round" strokeWidth="2px" /><path d="M15,9.75V3A2.25,2.25,0,0,1,17.25.75h0A2.25,2.25,0,0,1,19.5,3V5.5H15" fill="none" stroke="#000000" strokeLinecap="round" strokeLinejoin="round" strokeWidth="2px" /><g><line x1={21.75} y1={19.61} x2={16.96} y2={20.57} fill="none" stroke="#000000" strokeLinecap="round" strokeLinejoin="round" strokeWidth="2px" /><path d="M17.44,15.14l-2.26.95a1.41,1.41,0,0,1-1.12,0A1.52,1.52,0,0,1,14,13.35l2.26-1.13a2,2,0,0,1,.9-.22,1.8,1.8,0,0,1,.69.13L22.47,14" fill="none" stroke="#000000" strokeLinecap="round" strokeLinejoin="round" strokeWidth="2px" /><path d="M8.2,20.61H9.79l3.05,2.32A.82.82,0,0,0,14,23l4.26-3.52a.83.83,0,0,0,.13-1.16L16,15.73" fill="none" stroke="#000000" strokeLinecap="round" strokeLinejoin="round" strokeWidth="2px" /><path d="M13.74,13.51l-.25-.21A1.83,1.83,0,0,0,12.43,13a1.93,1.93,0,0,0-.67.12L8.19,14.6" fill="none" stroke="#000000" strokeLinecap="round" strokeLinejoin="round" strokeWidth="2px" /><path d="M6.75,21.36h.3a1.14,1.14,0,0,0,1.2-1.08V14.93a1.14,1.14,0,0,0-1.2-1.07h-.3" fill="none" stroke="#000000" strokeLinecap="round" strokeLinejoin="round" strokeWidth="2px" /><path d="M23.25,21.36H23a1.14,1.14,0,0,1-1.2-1.08V14.93A1.14,1.14,0,0,1,23,13.86h.3" fill="none" stroke="#000000" strokeLinecap="round" strokeLinejoin="round" strokeWidth="2px" /></g></g></svg>
	}
/>

const name = 'affiliatewp/field-terms-of-use';
const termsOfUse = affwp_blocks?.terms_of_use
const termsOfUseLink = affwp_blocks?.terms_of_use_link
const termsOfUseLabel = affwp_blocks?.terms_of_use_label

const settings = {
	/* translators: block name */
	title: __('Affiliate Terms of Use', 'affiliate-wp' ),
	category: 'affiliatewp',
	parent: ['affiliatewp/registration'],
	icon,
	attributes: {
		label: {
			type: 'string',
			default: termsOfUseLabel
		},
		required: {
			type: 'boolean',
			default: true,
		},
		link: {
			type: 'string',
			default: termsOfUseLink
		},
		id: {
			type: 'number',
			default: termsOfUse
		},
		style: {
			type: 'number',
			default: 1
		},
	},
	/* translators: block description */
	description: __('Display an Affiliate Terms of Use checkbox which affiliates must agree to.', 'affiliate-wp' ),
	keywords: [
		'affiliatewp',
		/* translators: block keyword */
		__('checkbox', 'affiliate-wp' ),
		/* translators: block keyword */
		__('terms of use', 'affiliate-wp' ),
		/* translators: block keyword */
		__('affiliate terms', 'affiliate-wp' )
	],
	supports: {
		html: false,
		lightBlockWrapper: true,
	},
	edit,
	save: () => null,
};

export { name, settings };
