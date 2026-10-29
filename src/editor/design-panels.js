/**
 * Design settings in the block's "Styles" tab.
 *
 * They are added to the same Color, Border and Dimensions panels that core
 * blocks use, so they look and reset like every other block setting.
 */
import {
	InspectorControls,
	__experimentalColorGradientSettingsDropdown as ColorGradientSettingsDropdown,
	__experimentalSpacingSizesControl as SpacingSizesControl,
	__experimentalUseMultipleOriginColorsAndGradients as useMultipleOriginColorsAndGradients,
	useSettings,
} from '@wordpress/block-editor';
import {
	ToggleControl,
	__experimentalToggleGroupControl as ToggleGroupControl,
	__experimentalToggleGroupControlOption as ToggleGroupControlOption,
	__experimentalToolsPanel as ToolsPanel,
	__experimentalToolsPanelItem as ToolsPanelItem,
	__experimentalUnitControl as UnitControl,
	__experimentalUseCustomUnits as useCustomUnits,
} from '@wordpress/components';
import { __ } from '@wordpress/i18n';

import {
	ATTRIBUTE,
	cssToSpacing,
	fromStoredColor,
	spacingToCss,
	toStoredColor,
	withoutEmptyValues,
} from './style-utils';

const COLORS = [
	{ key: 'accentColor', label: __( 'Accent', 'cf7-additional-types' ) },
	{ key: 'labelColor', label: __( 'Labels', 'cf7-additional-types' ) },
	{ key: 'fieldText', label: __( 'Field text', 'cf7-additional-types' ) },
	{
		key: 'fieldBackground',
		label: __( 'Field background', 'cf7-additional-types' ),
	},
	{ key: 'fieldBorder', label: __( 'Field border', 'cf7-additional-types' ) },
	{ key: 'buttonText', label: __( 'Button text', 'cf7-additional-types' ) },
	{
		key: 'buttonBackground',
		label: __( 'Button background', 'cf7-additional-types' ),
	},
];

const SHOWN_BY_DEFAULT = [ 'accentColor', 'fieldBackground', 'fieldBorder' ];

export default function DesignPanels( {
	attributes,
	setAttributes,
	clientId,
} ) {
	const design = attributes[ ATTRIBUTE ] ?? {};
	const colorSettings = useMultipleOriginColorsAndGradients();
	const palette = colorSettings.colors.flatMap(
		( origin ) => origin.colors ?? []
	);
	const [ availableUnits ] = useSettings( 'spacing.units' );
	const units = useCustomUnits( {
		availableUnits: availableUnits || [ 'px', 'em', 'rem' ],
	} );

	const update = ( changes ) =>
		setAttributes( {
			[ ATTRIBUTE ]: withoutEmptyValues( { ...design, ...changes } ),
		} );

	// Used by the panels' "Reset all".
	const resetFilter = ( keys ) => ( blockAttributes ) => {
		const next = { ...( blockAttributes[ ATTRIBUTE ] ?? {} ) };
		keys.forEach( ( key ) => delete next[ key ] );

		return { ...blockAttributes, [ ATTRIBUTE ]: next };
	};

	const lengthItem = ( key, label, control ) => (
		<ToolsPanelItem
			hasValue={ () => !! design[ key ] }
			label={ label }
			onDeselect={ () => update( { [ key ]: undefined } ) }
			resetAllFilter={ resetFilter( [ key ] ) }
			isShownByDefault
			panelId={ clientId }
		>
			{ control }
		</ToolsPanelItem>
	);

	return (
		<>
			<InspectorControls group="color">
				<ColorGradientSettingsDropdown
					__experimentalIsRenderedInSidebar
					settings={ COLORS.map( ( { key, label } ) => ( {
						label,
						colorValue: fromStoredColor( design[ key ], palette ),
						onColorChange: ( value ) =>
							update( {
								[ key ]: toStoredColor( value, palette ),
							} ),
						isShownByDefault: SHOWN_BY_DEFAULT.includes( key ),
						resetAllFilter: resetFilter( [ key ] ),
						enableAlpha: true,
						clearable: true,
					} ) ) }
					panelId={ clientId }
					{ ...colorSettings }
					gradients={ [] }
					disableCustomGradients
				/>
			</InspectorControls>

			<InspectorControls group="border">
				{ lengthItem(
					'radius',
					__( 'Field radius', 'cf7-additional-types' ),
					<UnitControl
						__next40pxDefaultSize
						label={ __( 'Field radius', 'cf7-additional-types' ) }
						value={ design.radius ?? '' }
						units={ units }
						min={ 0 }
						onChange={ ( value ) =>
							update( { radius: value || undefined } )
						}
					/>
				) }
				{ lengthItem(
					'borderWidth',
					__( 'Field border width', 'cf7-additional-types' ),
					<UnitControl
						__next40pxDefaultSize
						label={ __(
							'Field border width',
							'cf7-additional-types'
						) }
						value={ design.borderWidth ?? '' }
						units={ units.filter( ( unit ) =>
							[ 'px', 'em', 'rem' ].includes( unit.value )
						) }
						min={ 0 }
						onChange={ ( value ) =>
							update( { borderWidth: value || undefined } )
						}
					/>
				) }
			</InspectorControls>

			<InspectorControls group="dimensions">
				{ lengthItem(
					'gap',
					__( 'Space between fields', 'cf7-additional-types' ),
					<SpacingSizesControl
						label={ __(
							'Space between fields',
							'cf7-additional-types'
						) }
						sides={ [ 'top' ] }
						showSideInLabel={ false }
						minimumCustomValue={ 0 }
						values={ { top: cssToSpacing( design.gap ) } }
						onChange={ ( { top } ) =>
							update( { gap: spacingToCss( top ) } )
						}
					/>
				) }
			</InspectorControls>

			<InspectorControls group="styles">
				<ToolsPanel
					label={ __( 'Form fields', 'cf7-additional-types' ) }
					resetAll={ () =>
						update( {
							fieldSize: undefined,
							themeButton: undefined,
							buttonWidth: undefined,
						} )
					}
					panelId={ clientId }
				>
					<ToolsPanelItem
						hasValue={ () => !! design.fieldSize }
						label={ __( 'Field size', 'cf7-additional-types' ) }
						onDeselect={ () => update( { fieldSize: undefined } ) }
						isShownByDefault
						panelId={ clientId }
					>
						<ToggleGroupControl
							__next40pxDefaultSize
							__nextHasNoMarginBottom
							isBlock
							label={ __( 'Field size', 'cf7-additional-types' ) }
							value={ design.fieldSize ?? 'medium' }
							onChange={ ( value ) =>
								update( {
									fieldSize:
										value === 'medium' ? undefined : value,
								} )
							}
						>
							<ToggleGroupControlOption
								value="small"
								label={ __( 'S', 'cf7-additional-types' ) }
								aria-label={ __(
									'Small',
									'cf7-additional-types'
								) }
								showTooltip
							/>
							<ToggleGroupControlOption
								value="medium"
								label={ __( 'M', 'cf7-additional-types' ) }
								aria-label={ __(
									'Medium',
									'cf7-additional-types'
								) }
								showTooltip
							/>
							<ToggleGroupControlOption
								value="large"
								label={ __( 'L', 'cf7-additional-types' ) }
								aria-label={ __(
									'Large',
									'cf7-additional-types'
								) }
								showTooltip
							/>
						</ToggleGroupControl>
					</ToolsPanelItem>

					<ToolsPanelItem
						hasValue={ () => design.themeButton === false }
						label={ __( 'Button style', 'cf7-additional-types' ) }
						onDeselect={ () =>
							update( { themeButton: undefined } )
						}
						isShownByDefault
						panelId={ clientId }
					>
						<ToggleControl
							__nextHasNoMarginBottom
							label={ __(
								'Use the theme’s button style',
								'cf7-additional-types'
							) }
							help={ __(
								'Off: the button uses the accent color.',
								'cf7-additional-types'
							) }
							checked={ design.themeButton !== false }
							onChange={ ( checked ) =>
								update( {
									themeButton: checked ? undefined : false,
								} )
							}
						/>
					</ToolsPanelItem>

					<ToolsPanelItem
						hasValue={ () => design.buttonWidth === 'full' }
						label={ __( 'Button width', 'cf7-additional-types' ) }
						onDeselect={ () =>
							update( { buttonWidth: undefined } )
						}
						isShownByDefault
						panelId={ clientId }
					>
						<ToggleControl
							__nextHasNoMarginBottom
							label={ __(
								'Full-width button',
								'cf7-additional-types'
							) }
							checked={ design.buttonWidth === 'full' }
							onChange={ ( checked ) =>
								update( {
									buttonWidth: checked ? 'full' : undefined,
								} )
							}
						/>
					</ToolsPanelItem>
				</ToolsPanel>
			</InspectorControls>
		</>
	);
}
