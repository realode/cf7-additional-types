/**
 * Editor for the Contact Form 7 block: a styled live preview of the selected
 * form, with Contact Form 7's own form picker one click away.
 */
import {
	BlockControls,
	InspectorControls,
	useBlockProps,
} from '@wordpress/block-editor';
import {
	Button,
	ExternalLink,
	PanelBody,
	ToolbarButton,
} from '@wordpress/components';
import { useEffect, useState } from '@wordpress/element';
import { __ } from '@wordpress/i18n';
import { addQueryArgs } from '@wordpress/url';

import DesignPanels from './design-panels';
import FormPreview from './form-preview';
import {
	ATTRIBUTE,
	isUnstyled,
	wrapperClasses,
	wrapperStyle,
} from './style-utils';

function PreviewEdit( { attributes, onChangeForm } ) {
	const design = attributes[ ATTRIBUTE ] ?? {};
	const unstyled = isUnstyled( attributes.className );
	const blockProps = useBlockProps( {
		className: unstyled ? undefined : wrapperClasses( design ).join( ' ' ),
		style: unstyled ? undefined : wrapperStyle( design ),
	} );
	const { canEdit, formsUrl } = window.cf7atEditor ?? {};

	return (
		<>
			<InspectorControls>
				<PanelBody title={ attributes.title }>
					{ canEdit && attributes.id && (
						<p>
							<ExternalLink
								href={ addQueryArgs( formsUrl, {
									post: attributes.id,
									action: 'edit',
								} ) }
							>
								{ __(
									'Edit this contact form',
									'cf7-additional-types'
								) }
							</ExternalLink>
						</p>
					) }
					<Button variant="secondary" onClick={ onChangeForm }>
						{ __(
							'Change form or form attributes',
							'cf7-additional-types'
						) }
					</Button>
				</PanelBody>
			</InspectorControls>

			<div { ...blockProps }>
				<FormPreview
					formId={ attributes.hash || String( attributes.id ) }
					themeButton={ ! unstyled && design.themeButton !== false }
				/>
			</div>
		</>
	);
}

export default function DesignEdit( { BlockEdit, ...props } ) {
	const { attributes } = props;
	const hasForm = Boolean( attributes.hash || attributes.id );
	const [ isEditingForm, setIsEditingForm ] = useState( false );

	// Go back to the preview once another form is chosen.
	useEffect( () => setIsEditingForm( false ), [ attributes.id ] );

	return (
		<>
			{ hasForm && ! isEditingForm ? (
				<PreviewEdit
					attributes={ attributes }
					onChangeForm={ () => setIsEditingForm( true ) }
				/>
			) : (
				<BlockEdit { ...props } />
			) }

			{ hasForm && (
				<BlockControls group="other">
					<ToolbarButton
						isPressed={ ! isEditingForm }
						onClick={ () => setIsEditingForm( ! isEditingForm ) }
					>
						{ __( 'Preview', 'cf7-additional-types' ) }
					</ToolbarButton>
				</BlockControls>
			) }

			<DesignPanels { ...props } />
		</>
	);
}
