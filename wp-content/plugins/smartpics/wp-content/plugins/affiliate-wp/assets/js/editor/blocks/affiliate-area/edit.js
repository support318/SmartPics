/**
 * Affiliate Content Edit Component.
 *
 * @since 2.8
 */

/**
 * WordPress dependencies
 */
import { __ } from '@wordpress/i18n';
import { Placeholder, Icon } from '@wordpress/components';
import { useBlockProps, useInnerBlocksProps } from '@wordpress/block-editor';
import icon from '../../components/icon';

const ALLOWED_BLOCKS = [
	'affiliatewp/registration',
	'affiliatewp/login',
	'wpforms/form-selector',
];

/**
 * Affiliate Area.
 *
 * Affiliate area block component.
 *
 * @since 2.8
 * @since 2.25.0 Block just return a placeholder.
 *
 * @return {JSX.Element} The rendered component.
 */
function AffiliateArea() {
	const blockProps = useBlockProps();

	const innerBlocksProps = useInnerBlocksProps(
		blockProps,
		{
			allowedBlocks: ALLOWED_BLOCKS,
			directInsert: true,
			templateInsertUpdatesSelection: true,
			template: [],
		}
	);

	const affiliateWpIcon = () => {
		return (
			<Icon
				icon={ icon }
				color={ true }
			/>
		);
	};

	return (
		<>
			<Placeholder icon={ affiliateWpIcon } label={ __( 'Affiliate Area', 'affiliate-wp' ) }>
				<p>{ __( 'Displays the Affiliate Area for logged-in users.', 'affiliate-wp' ) }</p>
			</Placeholder>
			<div { ...innerBlocksProps } />
		</>
	);
}

export default AffiliateArea;
