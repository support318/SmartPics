/**
 * Affiliate Area Block.
 *
 * @since 2.8
 */

/**
 * Internal Dependencies
 */
import icon from '../../components/icon';
import edit from './edit';
import save from './save';

/**
 * WordPress Dependencies
 */
import { __ } from '@wordpress/i18n';

const name = 'affiliatewp/affiliate-area';

const settings = {
	title: __( 'Affiliate Area', 'affiliate-wp' ),
	description: __(
		'Displays the Affiliate Area for logged-in users.',
		'affiliate-wp'
	),
	keywords: [
		__( 'Affiliate Area', 'affiliate-wp' ),
		__( 'Area', 'affiliate-wp' ),
		__( 'Dashboard', 'affiliate-wp' )
	],
	category: 'affiliatewp',
	icon,
	apiVersion: 2,
	supports: {
		html: false,
	},
	edit,
	save,
};

export { name, settings };
