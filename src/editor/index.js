/**
 * Adds design settings and a live preview to Contact Form 7's own block.
 */
import { createHigherOrderComponent } from '@wordpress/compose';
import { addFilter } from '@wordpress/hooks';

import DesignEdit from './design-edit';
import { ATTRIBUTE, BLOCK_NAME } from './style-utils';

// The server registers the attribute as well; this covers blocks registered
// before the server definitions are applied.
addFilter(
	'blocks.registerBlockType',
	'cf7at/design-attribute',
	( settings, name ) => {
		if ( name !== BLOCK_NAME ) {
			return settings;
		}

		return {
			...settings,
			attributes: {
				...settings.attributes,
				[ ATTRIBUTE ]: { type: 'object', default: {} },
			},
		};
	}
);

addFilter(
	'editor.BlockEdit',
	'cf7at/design-edit',
	createHigherOrderComponent(
		( BlockEdit ) => ( props ) =>
			props.name === BLOCK_NAME ? (
				<DesignEdit { ...props } BlockEdit={ BlockEdit } />
			) : (
				<BlockEdit { ...props } />
			),
		'withCf7atDesign'
	)
);
