/**
 * Popover anchored to a field, built on the native Popover API.
 *
 * The popover lives in the top layer, so it is never clipped by overflow or
 * covered by z-index stacking. It closes on outside clicks and on Escape.
 */

const GAP = 6;

export const supportsPopover = () =>
	typeof window.HTMLElement !== 'undefined' &&
	Object.prototype.hasOwnProperty.call(
		window.HTMLElement.prototype,
		'popover'
	);

let popoverCount = 0;

/**
 * @param {HTMLElement} anchor            Element the popover is positioned against.
 * @param {Object}      options
 * @param {string}      options.className Additional class name.
 * @param {string}      [options.role]    ARIA role of the popover element.
 * @param {string}      [options.label]   Accessible name of the popover.
 */
export function createPopover( anchor, { className, role, label } ) {
	const doc = anchor.ownerDocument;
	const win = doc.defaultView;
	const element = doc.createElement( 'div' );

	element.className = `cf7at-popover ${ className }`;
	element.id = `cf7at-popover-${ ++popoverCount }`;
	element.setAttribute( 'popover', 'auto' );

	if ( role ) {
		element.setAttribute( 'role', role );
	}

	if ( label ) {
		element.setAttribute( 'aria-label', label );
	}

	let closedAt = 0;
	let returnFocus = false;
	const listeners = new Set();

	const position = () => {
		const rect = anchor.getBoundingClientRect();
		const { offsetWidth: width, offsetHeight: height } = element;
		const spaceBelow = win.innerHeight - rect.bottom;
		const fitsBelow = spaceBelow >= height + GAP || spaceBelow >= rect.top;
		const top = fitsBelow ? rect.bottom + GAP : rect.top - height - GAP;
		const left = Math.min( rect.left, win.innerWidth - width - GAP );

		element.style.top = `${ Math.max( GAP, top ) }px`;
		element.style.left = `${ Math.max( GAP, left ) }px`;
	};

	element.addEventListener( 'keydown', ( event ) => {
		if ( event.key === 'Escape' ) {
			returnFocus = true;
		}
	} );

	element.addEventListener( 'toggle', ( event ) => {
		const isOpen = event.newState === 'open';

		if ( isOpen ) {
			position();
			win.addEventListener( 'scroll', position, true );
			win.addEventListener( 'resize', position );
		} else {
			closedAt = win.performance.now();
			win.removeEventListener( 'scroll', position, true );
			win.removeEventListener( 'resize', position );

			if ( returnFocus ) {
				anchor.focus();
			}
		}

		returnFocus = false;
		listeners.forEach( ( listener ) => listener( isOpen ) );
	} );

	const isOpen = () => element.matches( ':popover-open' );

	return {
		element,
		isOpen,
		/**
		 * Opens the popover, unless it was just closed by the click that
		 * would reopen it (outside clicks close auto popovers first).
		 */
		open() {
			if ( ! isOpen() && win.performance.now() - closedAt > 200 ) {
				element.showPopover();
				position();
			}
		},
		/**
		 * @param {boolean} [focusAnchor] Move focus back to the anchor.
		 */
		close( focusAnchor = false ) {
			if ( isOpen() ) {
				returnFocus = focusAnchor;
				element.hidePopover();
			}
		},
		/**
		 * @param {(isOpen: boolean) => void} listener Called on open and close.
		 */
		onToggle( listener ) {
			listeners.add( listener );
		},
	};
}
