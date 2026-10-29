/**
 * ESLint configuration: the @wordpress/scripts defaults plus an explicit list
 * of experimental WordPress APIs this plugin relies on.
 *
 * The design settings are added to the block editor's own Color, Border and
 * Dimensions panels, which core builds from these components. Keeping them
 * listed here makes every experimental dependency visible in one place.
 */
const defaultConfig = require( '@wordpress/scripts/config/eslint.config.cjs' );

module.exports = [
	...defaultConfig,
	{
		rules: {
			'@wordpress/no-unsafe-wp-apis': [
				'error',
				{
					'@wordpress/block-editor': [
						'__experimentalColorGradientSettingsDropdown',
						'__experimentalSpacingSizesControl',
						'__experimentalUseMultipleOriginColorsAndGradients',
					],
					'@wordpress/components': [
						'__experimentalToggleGroupControl',
						'__experimentalToggleGroupControlOption',
						'__experimentalToolsPanel',
						'__experimentalToolsPanelItem',
						'__experimentalUnitControl',
						'__experimentalUseCustomUnits',
					],
				},
			],
		},
	},
];
