/**
 * Affiliate registration Select field Block.
 *
 * @since 2.10.0
 */

import AffiliateWPFieldMultiple from '../../components/field-multiple';

/**
 * WordPress dependencies
 */
import { __ } from '@wordpress/i18n';

import { Icon } from '@wordpress/components';
import { getBlockType } from '@wordpress/blocks';

const icon = <Icon
	icon={
		<svg width="24" height="24" viewBox="0 0 24 24" fill="none" xmlns="http://www.w3.org/2000/svg">
			<path d="M2.75 23.25L21.25 23.25C22.3546 23.25 23.25 22.3546 23.25 21.25L23.25 2.75C23.25 1.64543 22.3546 0.75 21.25 0.75L2.75 0.75C1.64543 0.75 0.75 1.64543 0.75 2.75L0.75 21.25C0.75 22.3546 1.64543 23.25 2.75 23.25Z" stroke="black" strokeWidth="2" strokeLinecap="round" strokeLinejoin="round" fill="none" />
			<path d="M17.25 9L12.592 14.989C12.5219 15.0791 12.4321 15.1521 12.3295 15.2023C12.2269 15.2524 12.1142 15.2785 12 15.2785C11.8858 15.2785 11.7731 15.2524 11.6705 15.2023C11.5679 15.1521 11.4781 15.0791 11.408 14.989L6.75 9" stroke="black" strokeWidth="2" strokeLinecap="round" strokeLinejoin="round" fill="none" />
		</svg>
	 }
 />

const name = 'affiliatewp/field-select';

const getFieldLabel = ( { attributes, name: blockName } ) => {
	return null === attributes.label ? getBlockType( blockName ).title : attributes.label;
};

const editMultiField = type => props => (
<AffiliateWPFieldMultiple
	label={ getFieldLabel( props ) }
	required={ props.attributes.required }
	options={ props.attributes.options }
	setAttributes={ props.setAttributes }
	type={ type }
	isSelected={ props.isSelected }
	id={ props.attributes.id }
/>
);

const settings = {
	apiVersion: 2,
	/* translators: block name */
	title: __( 'Dropdown Field', 'affiliate-wp' ),
	category: 'affiliatewp',
	parent: ['affiliatewp/registration'],
	icon,
	attributes: {
		label: {
			type: 'string',
			default: 'Select one',
		},
	required: {
		type: 'boolean',
		default: false,
	},
	options: {
		type: 'array',
		default: [],
	},
	defaultValue: {
		type: 'string',
		default: '',
	},
	placeholder: {
		type: 'string',
		default: '',
	},
	id: {
		type: 'string',
		default: '',
	},
	},
	/* translators: block description */
	description: __( 'Add a select box with several items.', 'affiliate-wp' ),
	keywords: [
		'affiliatewp',
		/* translators: block keyword */
		__('select', 'affiliatewp'),
	],
	supports: {
		reusable: false,
		html: false,
	},
	edit: editMultiField( 'select' ),
	save: () => null,
};

export { name, settings };
