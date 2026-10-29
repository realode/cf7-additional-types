import 'cally';

import { createPopover, supportsPopover } from './popover';
import {
	__,
	createDisplayInput,
	onFormReset,
	parseJson,
} from './display-input';

const ARROW_PREVIOUS =
	'<svg viewBox="0 0 24 24" width="20" height="20" aria-hidden="true"><path fill="currentColor" d="M14.6 7l-5 5 5 5 1.4-1.4-3.6-3.6 3.6-3.6z"/></svg>';
const ARROW_NEXT =
	'<svg viewBox="0 0 24 24" width="20" height="20" aria-hidden="true"><path fill="currentColor" d="M9.4 17l5-5-5-5L8 8.4l3.6 3.6L8 15.6z"/></svg>';

/**
 * Formats ISO dates (YYYY-MM-DD) for display in the site language.
 *
 * @param {string|undefined} locale  BCP 47 language tag.
 * @param {Object|null}      options Intl.DateTimeFormat options.
 */
function dateFormatter( locale, options ) {
	let format;

	try {
		format = new Intl.DateTimeFormat( locale, {
			...( options ?? { dateStyle: 'medium' } ),
			timeZone: 'UTC',
		} );
	} catch {
		format = new Intl.DateTimeFormat( undefined, {
			dateStyle: 'medium',
			timeZone: 'UTC',
		} );
	}

	return ( value ) =>
		value ? format.format( new Date( `${ value }T00:00:00Z` ) ) : '';
}

/**
 * Turns a native date input into a text field with a calendar popover.
 *
 * @param {HTMLElement} container The .cf7at-date element.
 */
export function enhanceDatePicker( container ) {
	const native = container.querySelector( 'input[type="date"]' );

	if ( ! native || ! supportsPopover() ) {
		return;
	}

	const doc = container.ownerDocument;
	const { locale, firstDay } = container.dataset;
	const allowedDays = parseJson( container.dataset.weekdays );
	const format = dateFormatter(
		locale,
		parseJson( container.dataset.format )
	);
	const monthFormat = dateFormatter( locale, {
		month: 'long',
		year: 'numeric',
	} );
	const formatMonth = ( date ) =>
		monthFormat( date.toISOString().slice( 0, 10 ) );
	const { min, max, required } = native;
	const isReadOnly = native.readOnly;

	const display = createDisplayInput( native, { popup: 'dialog' } );
	const popover = createPopover( display, {
		className: 'cf7at-date__popover',
		role: 'dialog',
		label: __( 'chooseDate' ),
	} );
	let calendar = null;

	container.append( popover.element );

	const refresh = () => {
		display.value = format( native.value );
	};

	const select = ( value ) => {
		native.value = value;
		refresh();
		display.dispatchEvent( new Event( 'change', { bubbles: true } ) );
	};

	const buildCalendar = () => {
		calendar = doc.createElement( 'calendar-date' );
		calendar.className = 'cf7at-calendar';
		calendar.setAttribute( 'first-day-of-week', firstDay || '1' );

		if ( locale ) {
			calendar.setAttribute( 'locale', locale );
		}

		if ( min ) {
			calendar.setAttribute( 'min', min );
		}

		if ( max ) {
			calendar.setAttribute( 'max', max );
		}

		if ( allowedDays ) {
			calendar.isDateDisallowed = ( date ) =>
				! allowedDays.includes( date.getUTCDay() || 7 );
		}

		// Slotted icons replace Cally's English "Previous" and "Next" labels.
		for ( const [ slot, icon, text ] of [
			[ 'previous', ARROW_PREVIOUS, __( 'previousMonth' ) ],
			[ 'next', ARROW_NEXT, __( 'nextMonth' ) ],
		] ) {
			const arrow = doc.createElement( 'span' );
			arrow.slot = slot;
			arrow.setAttribute( 'role', 'img' );
			arrow.setAttribute( 'aria-label', text );
			arrow.innerHTML = icon;
			calendar.append( arrow );
		}

		calendar.append( doc.createElement( 'calendar-month' ) );

		const heading = doc.createElement( 'div' );
		heading.slot = 'heading';

		// Long ranges, such as birth dates, get month and year menus.
		const spanYears =
			min && max
				? Number( max.slice( 0, 4 ) ) - Number( min.slice( 0, 4 ) )
				: 0;

		if ( spanYears > 1 ) {
			heading.className = 'cf7at-calendar__menus';

			for ( const [ tagName, text ] of [
				[ 'calendar-select-month', __( 'month' ) ],
				[ 'calendar-select-year', __( 'year' ) ],
			] ) {
				const menu = doc.createElement( tagName );
				const label = doc.createElement( 'span' );

				label.slot = 'label';
				label.textContent = text;
				menu.append( label );

				if ( tagName === 'calendar-select-year' ) {
					menu.setAttribute( 'max-years', String( spanYears + 1 ) );
				}

				heading.append( menu );
			}
		} else {
			// Cally announces the month itself; this is the visible heading.
			heading.className = 'cf7at-calendar__heading';
			heading.setAttribute( 'aria-hidden', 'true' );

			calendar.addEventListener( 'focusday', ( event ) => {
				heading.textContent = formatMonth( event.detail );
			} );
		}

		calendar.prepend( heading );

		calendar.addEventListener( 'change', () => {
			select( calendar.value );
			popover.close( true );
		} );

		popover.element.append( calendar );

		if ( ! required ) {
			const clear = doc.createElement( 'button' );
			clear.type = 'button';
			clear.className = 'cf7at-popover__action';
			clear.textContent = __( 'clear' );
			clear.addEventListener( 'click', () => {
				select( '' );
				popover.close( true );
			} );

			const footer = doc.createElement( 'div' );
			footer.className = 'cf7at-popover__footer';
			footer.append( clear );
			popover.element.append( footer );
		}
	};

	const open = () => {
		if ( isReadOnly ) {
			return;
		}

		if ( ! calendar ) {
			buildCalendar();
		}

		calendar.value = native.value;
		calendar.focusedDate = native.value || undefined;

		const heading = calendar.querySelector( '.cf7at-calendar__heading' );

		if ( heading ) {
			heading.textContent = formatMonth(
				new Date( `${ native.value || startDate() }T00:00:00Z` )
			);
		}

		popover.open();
		focusCalendar();
	};

	// The month a calendar without a value opens on: today, kept within min and max.
	const startDate = () => {
		const today = new Date().toISOString().slice( 0, 10 );

		if ( min && today < min ) {
			return min;
		}

		return max && today > max ? max : today;
	};

	// Cally renders asynchronously, so wait for the day buttons before focusing.
	const focusCalendar = async () => {
		await calendar.updated;
		await calendar.querySelector( 'calendar-month' )?.updated;
		doc.defaultView.requestAnimationFrame( () => calendar.focus() );
	};

	display.addEventListener( 'click', () =>
		popover.isOpen() ? popover.close() : open()
	);

	display.addEventListener( 'keydown', ( event ) => {
		if ( [ 'Enter', ' ', 'ArrowDown' ].includes( event.key ) ) {
			event.preventDefault();
			open();
		}
	} );

	popover.onToggle( ( isOpen ) =>
		display.setAttribute( 'aria-expanded', String( isOpen ) )
	);

	onFormReset( display, refresh );
	refresh();
}
