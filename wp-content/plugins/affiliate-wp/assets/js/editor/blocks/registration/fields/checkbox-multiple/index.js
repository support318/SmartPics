/**
 * Affiliate registration Checkbox (multiple) field Block.
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
		<svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" height={24} width={24} ><defs /><title>{"checklist"}</title><rect x={0.75} y={0.749} width={22.5} height={22.5} rx={1.5} ry={1.5} fill="none" stroke="#000000" strokeLinecap="round" strokeLinejoin="round" strokeWidth="2px" /><polyline points="12 4.499 7.5 10.499 4.5 7.499" fill="none" stroke="#000000" strokeLinecap="round" strokeLinejoin="round" strokeWidth="2px" /><line x1={14.25} y1={8.249} x2={18.75} y2={8.249} fill="none" stroke="#000000" strokeLinecap="round" strokeLinejoin="round" strokeWidth="2px" /><polyline points="12 13.499 7.5 19.499 4.5 16.499" fill="none" stroke="#000000" strokeLinecap="round" strokeLinejoin="round" strokeWidth="2px" /><line x1={14.25} y1={17.249} x2={18.75} y2={17.249} fill="none" stroke="#000000" strokeLinecap="round" strokeLinejoin="round" strokeWidth="2px" /></svg>
	  }
  />

 const name = 'affiliatewp/field-checkbox-multiple';

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
	 title: __( 'Multiple Choice (Checkbox)', 'affiliate-wp' ),
	 category: 'affiliatewp',
	 parent: ['affiliatewp/registration'],
	 icon,
	 attributes: {
	 label: {
		 type: 'string',
		 default: 'Choose several options',
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
	 description: __( 'Add several checkbox items.', 'affiliate-wp' ),
	 keywords: [
		 'affiliatewp',
		 /* translators: block keyword */
		 __('option', 'affiliatewp'),
	 ],
	 supports: {
		 reusable: false,
		 html: false,
	 },
	 edit: editMultiField( 'checkbox' ),
	 save: () => null,
 };

 export { name, settings };
