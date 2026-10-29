/**
 * Replaces a native date or time input with a read-only text input that shows
 * the localized value, while the native input keeps the machine value.
 *
 * The visible input takes over the id, classes and ARIA state, so labels,
 * Contact Form 7's validation messages and theme styles apply to it. The
 * native input becomes hidden and is the one that gets submitted.
 *
 * @param {HTMLInputElement} native        The server-rendered native input.
 * @param {Object}           options
 * @param {string}           options.popup Value for aria-haspopup.
 * @return {HTMLInputElement} The visible input.
 */
export function createDisplayInput( native, { popup } ) {
	const display = native.ownerDocument.createElement( 'input' );

	display.type = 'text';
	display.readOnly = true;
	display.className = `${ native.className } cf7at-picker__display`;
	display.autocomplete = 'off';
	display.setAttribute( 'aria-haspopup', popup );
	display.setAttribute( 'aria-expanded', 'false' );

	for ( const name of [
		'id',
		'tabindex',
		'placeholder',
		'aria-required',
		'aria-invalid',
		'aria-describedby',
	] ) {
		if ( native.hasAttribute( name ) ) {
			display.setAttribute( name, native.getAttribute( name ) );
			native.removeAttribute( name );
		}
	}

	if ( native.readOnly ) {
		display.setAttribute( 'aria-disabled', 'true' );
	}

	native.className = 'cf7at-picker__value';
	native.type = 'hidden';
	native.before( display );
	native.closest( '.cf7at-picker' )?.classList.add( 'is-enhanced' );

	return display;
}

/**
 * Keeps the visible input in sync after the form is reset, which Contact
 * Form 7 does after a successful submission.
 *
 * @param {HTMLInputElement} input  Any input of the form.
 * @param {() => void}       update Callback that refreshes the display.
 */
export function onFormReset( input, update ) {
	input.form?.addEventListener( 'reset', () => {
		input.ownerDocument.defaultView.requestAnimationFrame( update );
	} );
}

/**
 * Reads a JSON data attribute, returning null when it is missing or invalid.
 *
 * @param {string|undefined} value Attribute value.
 */
export function parseJson( value ) {
	try {
		return value ? JSON.parse( value ) : null;
	} catch {
		return null;
	}
}

/**
 * Translated strings, printed by PHP before the script.
 *
 * @param {string} key String key.
 */
export function __( key ) {
	return window.cf7atL10n?.[ key ] ?? key;
}
