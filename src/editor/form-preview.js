/**
 * Live preview of a contact form, rendered by the server.
 */
import apiFetch from '@wordpress/api-fetch';
import { Notice, Spinner } from '@wordpress/components';
import { useEffect, useRef, useState } from '@wordpress/element';
import { __ } from '@wordpress/i18n';
import { addQueryArgs } from '@wordpress/url';

import { enhance } from '../fields';

// Form markup per form and button setting, shared by all blocks in the editor.
const cache = new Map();

export default function FormPreview( { formId, themeButton } ) {
	const key = `${ formId }:${ themeButton }`;
	const [ state, setState ] = useState( () =>
		cache.has( key ) ? { html: cache.get( key ) } : { isLoading: true }
	);
	const ref = useRef();

	useEffect( () => {
		if ( cache.has( key ) ) {
			setState( { html: cache.get( key ) } );
			return;
		}

		let isCurrent = true;
		setState( { isLoading: true } );

		apiFetch( {
			path: addQueryArgs( '/cf7at/v1/form-preview', {
				id: formId,
				themeButton,
			} ),
		} )
			.then( ( { html } ) => {
				cache.set( key, html );

				if ( isCurrent ) {
					setState( { html } );
				}
			} )
			.catch( ( error ) => {
				if ( isCurrent ) {
					setState( { error: error.message } );
				}
			} );

		return () => {
			isCurrent = false;
		};
	}, [ key, formId, themeButton ] );

	// Show the date and time fields as visitors see them. The preview is
	// inert, so the pickers never open.
	useEffect( () => {
		if ( ref.current && state.html ) {
			ref.current.setAttribute( 'inert', '' );
			enhance( ref.current );
		}
	}, [ state.html ] );

	if ( state.isLoading ) {
		return <Spinner />;
	}

	if ( state.error ) {
		return (
			<Notice status="error" isDismissible={ false }>
				{ __(
					'The form preview could not be loaded.',
					'cf7-additional-types'
				) }{ ' ' }
				{ state.error }
			</Notice>
		);
	}

	return (
		<div
			ref={ ref }
			className="cf7at-preview"
			// The markup comes from Contact Form 7, rendered by the server.
			dangerouslySetInnerHTML={ { __html: state.html } }
		/>
	);
}
