import { createPopover, supportsPopover } from './popover';
import { __, createDisplayInput, onFormReset } from './display-input';

const toMinutes = ( time ) => {
	const [ hours, minutes ] = time.split( ':' ).map( Number );
	return hours * 60 + minutes;
};

const toTime = ( minutes ) =>
	[ Math.floor( minutes / 60 ), minutes % 60 ]
		.map( ( part ) => String( part ).padStart( 2, '0' ) )
		.join( ':' );

/**
 * Turns a native time input into a select-only combobox with a list of times
 * (WAI-ARIA "Select-Only Combobox" pattern): focus stays on the field and the
 * arrow keys move through the options.
 *
 * @param {HTMLElement} container The .cf7at-time element.
 */
export function enhanceTimePicker( container ) {
	const native = container.querySelector( 'input[type="time"]' );

	if ( ! native || ! supportsPopover() ) {
		return;
	}

	const doc = container.ownerDocument;
	const { locale, hour12 } = container.dataset;
	const step = Math.max(
		1,
		Math.round( Number( native.step || 1800 ) / 60 )
	);
	const first = native.min ? toMinutes( native.min ) : 0;
	const last = native.max ? toMinutes( native.max ) : 24 * 60 - 1;
	const isReadOnly = native.readOnly;

	let format;
	try {
		format = new Intl.DateTimeFormat( locale, {
			hour: 'numeric',
			minute: '2-digit',
			hour12: hour12 === 'true',
			timeZone: 'UTC',
		} );
	} catch {
		format = new Intl.DateTimeFormat( undefined, {
			hour: 'numeric',
			minute: '2-digit',
			timeZone: 'UTC',
		} );
	}

	const label = ( time ) =>
		time ? format.format( new Date( `1970-01-01T${ time }:00Z` ) ) : '';

	const display = createDisplayInput( native, { popup: 'listbox' } );
	const popover = createPopover( display, {
		className: 'cf7at-time__popover',
	} );
	const listbox = doc.createElement( 'ul' );

	listbox.id = `${ popover.element.id }-listbox`;
	listbox.className = 'cf7at-time__list';
	listbox.setAttribute( 'role', 'listbox' );
	listbox.setAttribute( 'aria-label', __( 'chooseTime' ) );

	for ( let minutes = first; minutes <= last; minutes += step ) {
		const option = doc.createElement( 'li' );
		const time = toTime( minutes );

		option.id = `${ listbox.id }-${ time.replace( ':', '' ) }`;
		option.className = 'cf7at-time__option';
		option.setAttribute( 'role', 'option' );
		option.setAttribute( 'aria-selected', 'false' );
		option.dataset.value = time;
		option.textContent = label( time );
		listbox.append( option );
	}

	popover.element.append( listbox );
	container.append( popover.element );

	display.setAttribute( 'role', 'combobox' );
	display.setAttribute( 'aria-controls', listbox.id );

	const options = Array.from( listbox.children );
	let activeIndex = -1;

	const setActive = ( index ) => {
		options[ activeIndex ]?.classList.remove( 'is-active' );
		activeIndex = Math.max( 0, Math.min( options.length - 1, index ) );

		const option = options[ activeIndex ];

		if ( option ) {
			option.classList.add( 'is-active' );
			option.scrollIntoView( { block: 'nearest' } );
			display.setAttribute( 'aria-activedescendant', option.id );
		}
	};

	const refresh = () => {
		display.value = label( native.value );
		options.forEach( ( option ) =>
			option.setAttribute(
				'aria-selected',
				String( option.dataset.value === native.value )
			)
		);
	};

	const select = ( option ) => {
		native.value = option.dataset.value;
		refresh();
		display.dispatchEvent( new Event( 'change', { bubbles: true } ) );
		popover.close( true );
	};

	const open = () => {
		if ( isReadOnly || ! options.length ) {
			return;
		}

		popover.open();

		const selected = options.findIndex(
			( option ) => option.dataset.value === native.value
		);
		setActive( selected >= 0 ? selected : 0 );
	};

	display.addEventListener( 'click', () =>
		popover.isOpen() ? popover.close() : open()
	);

	display.addEventListener( 'keydown', ( event ) => {
		if ( ! popover.isOpen() ) {
			if (
				[ 'Enter', ' ', 'ArrowDown', 'ArrowUp' ].includes( event.key )
			) {
				event.preventDefault();
				open();
			}
			return;
		}

		const pageSize = Math.max( 1, Math.round( 60 / step ) );
		const moves = {
			ArrowDown: activeIndex + 1,
			ArrowUp: activeIndex - 1,
			PageDown: activeIndex + pageSize,
			PageUp: activeIndex - pageSize,
			Home: 0,
			End: options.length - 1,
		};

		if ( event.key in moves ) {
			event.preventDefault();
			setActive( moves[ event.key ] );
		} else if ( event.key === 'Enter' || event.key === ' ' ) {
			event.preventDefault();
			select( options[ activeIndex ] );
		} else if ( event.key === 'Tab' ) {
			select( options[ activeIndex ] );
		} else if ( event.key === 'Escape' ) {
			event.preventDefault();
			popover.close( true );
		}
	} );

	// Keep focus on the field while choosing with the mouse.
	listbox.addEventListener( 'mousedown', ( event ) =>
		event.preventDefault()
	);

	listbox.addEventListener( 'click', ( event ) => {
		const option = event.target.closest( '[role="option"]' );

		if ( option ) {
			select( option );
		}
	} );

	popover.onToggle( ( isOpen ) => {
		display.setAttribute( 'aria-expanded', String( isOpen ) );

		if ( ! isOpen ) {
			display.removeAttribute( 'aria-activedescendant' );
		}
	} );

	onFormReset( display, refresh );
	refresh();
}
