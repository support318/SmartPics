const path = require( 'path' );
const defaultConfig = require( '@wordpress/scripts/config/webpack.config' );

module.exports = {
	...defaultConfig,
	entry: {
		'secure-block': './includes/admin/gutenberg/src/index.js',
		'givewp-integration': './includes/integrations/give/src/index.js',
		'suremembers-integration':
			'./includes/integrations/suremembers/src/index.js',
		'tribe-tickets-integration':
			'./includes/integrations/tribe-tickets/src/index.js',
	},
	output: {
		path: path.resolve( __dirname, 'build' ),
		filename: '[name].js',
	},
};
