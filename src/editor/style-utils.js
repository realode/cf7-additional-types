/**
 * Turns the block's design settings into wrapper classes and CSS custom
 * properties. Mirrors CF7AT\Styler\Block_Styler, which does the same when the
 * block is rendered on the front end.
 */

export const BLOCK_NAME = 'contact-form-7/contact-form-selector';
export const ATTRIBUTE = 'cf7atStyle';

export const COLOR_PROPERTIES = {
	accentColor: '--cf7at-accent',
	labelColor: '--cf7at-label',
	fieldText: '--cf7at-field-text',
	fieldBackground: '--cf7at-field-bg',
	fieldBorder: '--cf7at-field-border',
	buttonText: '--cf7at-button-text',
	buttonBackground: '--cf7at-button-bg',
};

export const LENGTH_PROPERTIES = {
	radius: '--cf7at-radius',
	borderWidth: '--cf7at-border-width',
	gap: '--cf7at-gap',
};

/**
 * @param {string|undefined} className The block's className attribute.
 */
export const isUnstyled = ( className = '' ) =>
	className.split( ' ' ).includes( 'is-style-unstyled' );

/**
 * @param {Object} design Design settings.
 * @return {string[]} Wrapper classes.
 */
export function wrapperClasses( design ) {
	const classes = [ 'cf7at-form' ];

	if ( [ 'small', 'large' ].includes( design.fieldSize ) ) {
		classes.push( `cf7at-size-${ design.fieldSize }` );
	}

	if ( design.buttonWidth === 'full' ) {
		classes.push( 'cf7at-button-full' );
	}

	if ( design.themeButton !== false ) {
		classes.push( 'cf7at-theme-button' );
	}

	return classes;
}

/**
 * @param {Object} design Design settings.
 * @return {Object} Inline style with CSS custom properties.
 */
export function wrapperStyle( design ) {
	const style = {};

	for ( const [ key, property ] of Object.entries( {
		...COLOR_PROPERTIES,
		...LENGTH_PROPERTIES,
	} ) ) {
		if ( design[ key ] ) {
			style[ property ] = design[ key ];
		}
	}

	return style;
}

/**
 * Removes empty values, so reset settings disappear from the saved attribute.
 *
 * @param {Object} design Design settings.
 */
export function withoutEmptyValues( design ) {
	return Object.fromEntries(
		Object.entries( design ).filter(
			( [ , value ] ) => value !== undefined && value !== ''
		)
	);
}

/**
 * Palette colors are stored as CSS variables, so they follow the theme when
 * its palette changes. Custom colors are stored as they are.
 *
 * @param {string|undefined} value  Color from the color picker.
 * @param {Object[]}         colors Palette colors ({ slug, color }).
 */
export function toStoredColor( value, colors ) {
	if ( ! value ) {
		return undefined;
	}

	const preset = colors.find(
		( color ) => color.color?.toLowerCase() === value.toLowerCase()
	);

	return preset ? `var(--wp--preset--color--${ preset.slug })` : value;
}

/**
 * @param {string|undefined} value  Stored color.
 * @param {Object[]}         colors Palette colors ({ slug, color }).
 */
export function fromStoredColor( value, colors ) {
	const slug = value?.match( /^var\(--wp--preset--color--([a-z0-9-]+)\)$/ );

	if ( ! slug ) {
		return value;
	}

	return colors.find( ( color ) => color.slug === slug[ 1 ] )?.color;
}

/**
 * Spacing presets: "var:preset|spacing|40" in the control, a CSS variable in the attribute.
 *
 * @param {string|undefined} value Value from SpacingSizesControl.
 */
export function spacingToCss( value ) {
	const preset = value?.match( /^var:preset\|spacing\|([a-z0-9-]+)$/ );

	return preset ? `var(--wp--preset--spacing--${ preset[ 1 ] })` : value;
}

/**
 * @param {string|undefined} value Stored spacing value.
 */
export function cssToSpacing( value ) {
	const preset = value?.match(
		/^var\(--wp--preset--spacing--([a-z0-9-]+)\)$/
	);

	return preset ? `var:preset|spacing|${ preset[ 1 ] }` : value;
}
