/**
 * Internal dependencies
 */
import AffiliateWPFieldControls from './field-controls';
import AffiliateWPFieldLabel from './field-label';
import AffiliateWPOption from './option';

/**
 * WordPress dependencies
 */
import { Button } from '@wordpress/components';
import { withInstanceId } from '@wordpress/compose';
import { useState } from '@wordpress/element';
import { __ } from '@wordpress/i18n';
import { useBlockProps } from '@wordpress/block-editor';

function AffiliateWPFieldMultiple( props ) {
	const {
		id,
		type,
		instanceId,
		required,
		label,
		setAttributes,
		isSelected,
		options,
	} = props;

	const [ inFocus, setInFocus ] = useState( null );

	const onChangeOption = ( key = null, option = null ) => {
		const newOptions = options.slice( 0 );

		if ( null === option ) {
			// Remove a key
			newOptions.splice( key, 1 );
			if ( key > 0 ) {
				setInFocus( key - 1 );
			}
		} else {
			// update a key
			newOptions.splice( key, 1, option );
			setInFocus( key ); // set the focus.
		}
		setAttributes( { options: newOptions } );
	};

	const addNewOption = ( key = null ) => {
		const newOptions = options.slice( 0 );
		let newInFocus = 0;

		if ( 'object' === typeof key ) {
			newOptions.push( '' );
			newInFocus = newOptions.length - 1;
		} else {
			newOptions.splice( key + 1, 0, '' );
			newInFocus = key + 1;
		}

		setInFocus( newInFocus );
		setAttributes( { options: newOptions } );
	};

	const blockProps = useBlockProps();

	return (
		<>
			<div { ...blockProps }>
				<AffiliateWPFieldLabel
					required={ required }
					label={ label }
					setAttributes={ setAttributes }
					isSelected={ isSelected }
					resetFocus={ () => setInFocus( null ) }
				/>

				<ol
					className="affiliatewp-field-multiple__list"
					id={ `affiliatewp-field-multiple-${ instanceId }` }
				>
					{ options.map( ( option, index ) => (
						<AffiliateWPOption
							type={ type }
							key={ index }
							option={ option }
							index={ index }
							onChangeOption={ onChangeOption }
							onAddOption={ addNewOption }
							isInFocus={ index === inFocus && isSelected }
							isSelected={ isSelected }
						/>
					) ) }
				</ol>
				{ isSelected && (
					<Button
						className="affiliatewp-field-multiple__add-option"
						icon="insert"
						label={ __( 'Insert option', 'affiliate-wp' ) }
						onClick={ addNewOption }
					>
						{ __( 'Add option', 'affiliate-wp' ) }
					</Button>
				) }

				<AffiliateWPFieldControls
					id={ id }
					required={ required }
					setAttributes={ setAttributes }
				/>
			</div>
		</>
	);
}
export default withInstanceId( AffiliateWPFieldMultiple );
