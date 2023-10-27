const defaultConfig = require( '@wordpress/scripts/config/webpack.config' );
const path = require( 'path' );

module.exports = {
	...defaultConfig,
	entry: {
		index: './index.js',
		editor: './editor.js'
	},
	output: {
		path: path.resolve( process.cwd(), 'dist' ),
	},
};
