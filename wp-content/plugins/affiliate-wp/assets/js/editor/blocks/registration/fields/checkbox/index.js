/**
 * Affiliate registration checkbox field Block.
 *
 * @since 2.8
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
		<svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" height={24} width={24} ><defs /><title>{"check-2"}</title><path d="M6,13.223,8.45,16.7a1.049,1.049,0,0,0,1.707.051L18,6.828" fill="none" stroke="#000000" strokeLinecap="round" strokeLinejoin="round" strokeWidth="2px" /><rect x={0.75} y={0.749} width={22.5} height={22.5} rx={1.5} ry={1.5} fill="none" stroke="#000000" strokeLinecap="round" strokeLinejoin="round" strokeWidth="2px" /></svg>
	}
/>

const name = 'affiliatewp/field-checkbox';

const settings = {
	/* translators: block name */
	title: __('Checkbox', 'affiliate-wp' ),
	category: 'affiliatewp',
	parent: ['affiliatewp/registration'],
	icon,
	attributes: {
		label: {
			type: 'string',
			default: __('Option', 'affiliate-wp' )
		},
		required: {
			type: 'boolean',
			default: false,
		},
	},
	/* translators: block description */
	description: __('Add a single checkbox.', 'affiliate-wp' ),
	keywords: [
		'affiliatewp',
		/* translators: block keyword */
		__('checkbox', 'affiliate-wp' )
	],
	supports: {
		html: false,
		lightBlockWrapper: true,
	},
	edit,
	save: () => null,
};

export { name, settings };
