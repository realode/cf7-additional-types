import { onFormReset, parseJson } from './display-input';

/**
 * Keeps the value bubble, aria-valuetext and the filled part of the track in
 * sync with a range slider.
 *
 * @param {HTMLElement} container The .cf7at-range element.
 */
export function enhanceRangeSlider( container ) {
	const input = container.querySelector( 'input[type="range"]' );
	const output = container.querySelector( '.cf7at-range__output' );

	if ( ! input || ! output ) {
		return;
	}

	const labels = parseJson( input.dataset.labels );
	const { prefix = '', suffix = '', currency } = input.dataset;
	const decimals = ( input.step.split( '.' )[ 1 ] || '' ).length;
	const locale = input.ownerDocument.documentElement.lang || undefined;
	const digits = {
		minimumFractionDigits: decimals,
		maximumFractionDigits: decimals,
	};

	let numberFormat;
	try {
		numberFormat = new Intl.NumberFormat(
			locale,
			currency ? { ...digits, style: 'currency', currency } : digits
		);
	} catch {
		numberFormat = new Intl.NumberFormat( locale, digits );
	}

	const update = () => {
		const value = Number( input.value );
		const min = Number( input.min );
		const max = Number( input.max );
		const text = labels
			? ( labels[ value ] ?? '' )
			: prefix + numberFormat.format( value ) + suffix;

		output.textContent = text;
		input.setAttribute( 'aria-valuetext', text );
		container.style.setProperty(
			'--cf7at-progress',
			String( max > min ? ( value - min ) / ( max - min ) : 0 )
		);
	};

	input.addEventListener( 'input', update );
	onFormReset( input, update );
	update();
}
