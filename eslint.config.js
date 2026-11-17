/**
 * ESLint flat config.
 *
 * Extends the default config shipped with `@wordpress/scripts` (WordPress recommended rules
 * and the Babel parser defaults) with the project's own overrides.
 */
const wordPressConfig = require( '@wordpress/scripts/config/eslint.config.cjs' );

module.exports = [
	{ ignores: [ 'dist/**' ] },
	...wordPressConfig,
	{
		// `@wordpress/*` packages are provided by WordPress at runtime (webpack externals), not bundled,
		// so they are intentionally not listed in package.json.
		settings: {
			'import/core-modules': [
				'@wordpress/api-fetch',
				'@wordpress/block-editor',
				'@wordpress/components',
				'@wordpress/compose',
				'@wordpress/dom-ready',
				'@wordpress/element',
				'@wordpress/hooks',
				'@wordpress/i18n',
				'@wordpress/url',
			],
		},
		rules: {
			'no-console': 'off',
			'import/no-unresolved': 'off',
		},
	},
];
