/**
 * Front-end enhancements for the plugin's Contact Form 7 fields.
 *
 * Every field is rendered as a working native input first; these scripts
 * upgrade it once the page has loaded.
 */
import { enhanceDatePicker } from './date-picker';
import { enhanceRangeSlider } from './range-slider';
import { enhanceTimePicker } from './time-picker';

const enhancers = {
	range: enhanceRangeSlider,
	date: enhanceDatePicker,
	time: enhanceTimePicker,
};

/**
 * Enhances all fields inside a root element. Safe to call more than once.
 *
 * @param {Document|Element} root Element or document to search.
 */
export function enhance( root = document ) {
	root.querySelectorAll( '[data-cf7at]' ).forEach( ( element ) => {
		const enhancer = enhancers[ element.dataset.cf7at ];

		if ( enhancer && ! element.hasAttribute( 'data-cf7at-ready' ) ) {
			element.setAttribute( 'data-cf7at-ready', '' );
			enhancer( element );
		}
	} );
}

if ( document.readyState === 'loading' ) {
	document.addEventListener( 'DOMContentLoaded', () => enhance() );
} else {
	enhance();
}
