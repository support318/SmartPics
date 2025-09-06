/**
 * Affiliate registration Radio field Block.
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
		<svg width="25" height="25" viewBox="0 0 25 25" fill="none" xmlns="http://www.w3.org/2000/svg">
		<path d="M12.25 23.5C18.4632 23.5 23.5 18.4632 23.5 12.25C23.5 6.0368 18.4632 1 12.25 1C6.0368 1 1 6.0368 1 12.25C1 18.4632 6.0368 23.5 12.25 23.5Z" stroke="black" strokeWidth="2.0" strokeLinecap="round" strokeLinejoin="round" fill="none" />
		<path d="M12.25 17C14.8734 17 17 14.8734 17 12.25C17 9.62665 14.8734 7.5 12.25 7.5C9.62665 7.5 7.5 9.62665 7.5 12.25C7.5 14.8734 9.62665 17 12.25 17Z" stroke="black" strokeWidth="2.0" strokeLinecap="round" strokeLinejoin="round" fill="none"/>
		</svg>

	  }
  />

 const name = 'affiliatewp/field-radio';

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
	 title: __( 'Single Choice (Radio)', 'affiliate-wp' ),
	 category: 'affiliatewp',
	 parent: ['affiliatewp/registration'],
	 icon,
	 attributes: {
	 label: {
		 type: 'string',
		 default: 'Choose one option',
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
	 description: __( 'Add one or more radio buttons.', 'affiliate-wp' ),
	 keywords: [
		 'affiliatewp',
		 /* translators: block keyword */
		 __('radio', 'affiliatewp'),
		  /* translators: block keyword */
		  __('option', 'affiliatewp'),
	 ],
	 supports: {
		 reusable: false,
		 html: false,
	 },
	 edit: editMultiField( 'radio' ),
	 save: () => null,
 };

 export { name, settings };
