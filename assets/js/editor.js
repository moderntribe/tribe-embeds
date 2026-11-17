/**
 * Block editor: show supported video embeds as the same facade (thumbnail + play button)
 * visitors see on the front end, instead of the live player preview.
 *
 * The facade HTML comes from the plugin's REST endpoint, so it is built by the same PHP and templates
 * as the front end (including theme overrides). Unsupported URLs fall back to the core embed preview.
 */
import apiFetch from '@wordpress/api-fetch';
import {
	BlockControls,
	RichText,
	useBlockProps,
} from '@wordpress/block-editor';
import { createHigherOrderComponent } from '@wordpress/compose';
import { ToolbarButton, ToolbarGroup, Spinner } from '@wordpress/components';
import { useEffect, useRef, useState } from '@wordpress/element';
import { addFilter } from '@wordpress/hooks';
import { __ } from '@wordpress/i18n';
import { addQueryArgs } from '@wordpress/url';

const requests = new Map();

/**
 * Fetch the facade markup for a URL. Results are cached per URL and provider for the editor session;
 * failures are not cached so a later attempt can succeed.
 *
 * @param {string} url
 * @param {string} provider
 * @return {Promise<string>} Facade HTML, or an empty string when unsupported.
 */
function fetchFacade( url, provider ) {
	const path = addQueryArgs( '/tribe-embed/v1/facade', { url, provider } );

	if ( ! requests.has( path ) ) {
		requests.set(
			path,
			apiFetch( { path } )
				.then( ( response ) => response.html || '' )
				.catch( () => {
					requests.delete( path );
					return '';
				} )
		);
	}

	return requests.get( path );
}

/**
 * @param {string} url
 * @param {string} provider
 * @return {string|null} Facade HTML, an empty string when unsupported, null while loading.
 */
function useFacade( url, provider ) {
	const [ html, setHtml ] = useState( null );

	useEffect( () => {
		let cancelled = false;

		if ( ! url ) {
			setHtml( '' );
			return undefined;
		}

		setHtml( null );
		fetchFacade( url, provider || '' ).then( ( result ) => {
			if ( ! cancelled ) {
				setHtml( result );
			}
		} );

		return () => {
			cancelled = true;
		};
	}, [ url, provider ] );

	return html;
}

/**
 * Static facade preview. Made inert so it neither takes focus nor plays: clicking selects the block.
 *
 * @param {Object} props
 * @param {string} props.html
 */
function FacadePreview( { html } ) {
	const ref = useRef();

	useEffect( () => {
		if ( ref.current ) {
			ref.current.setAttribute( 'inert', '' );
		}
	}, [ html ] );

	return (
		<div
			ref={ ref }
			className="tribe-embed-editor__preview"
			dangerouslySetInnerHTML={ { __html: html } }
		/>
	);
}

function FacadeEdit( { BlockEdit, ...props } ) {
	const { attributes, setAttributes, isSelected } = props;
	const { url, providerNameSlug, caption } = attributes;

	const [ showCoreEdit, setShowCoreEdit ] = useState( false );
	const html = useFacade( url, providerNameSlug );
	const blockProps = useBlockProps( {
		className: 'wp-block-embed tribe-embed-editor',
	} );

	// Go back to the facade once the block loses selection.
	useEffect( () => {
		if ( ! isSelected ) {
			setShowCoreEdit( false );
		}
	}, [ isSelected ] );

	if ( ! url || html === '' || showCoreEdit ) {
		return (
			<>
				{ showCoreEdit && html ? (
					<BlockControls group="other">
						<ToolbarGroup>
							<ToolbarButton
								onClick={ () => setShowCoreEdit( false ) }
							>
								{ __( 'Show facade preview', 'tribe-embeds' ) }
							</ToolbarButton>
						</ToolbarGroup>
					</BlockControls>
				) : null }
				<BlockEdit { ...props } />
			</>
		);
	}

	if ( html === null ) {
		return (
			<div { ...blockProps }>
				<Spinner />
			</div>
		);
	}

	return (
		<>
			<BlockControls group="other">
				<ToolbarGroup>
					<ToolbarButton onClick={ () => setShowCoreEdit( true ) }>
						{ __( 'Edit embed', 'tribe-embeds' ) }
					</ToolbarButton>
				</ToolbarGroup>
			</BlockControls>
			<div { ...blockProps }>
				<FacadePreview html={ html } />
				{ ( ! RichText.isEmpty( caption ) || isSelected ) && (
					<RichText
						tagName="figcaption"
						className="wp-element-caption"
						value={ caption }
						placeholder={ __( 'Add caption', 'tribe-embeds' ) }
						onChange={ ( value ) =>
							setAttributes( { caption: value } )
						}
						inlineToolbar
					/>
				) }
			</div>
		</>
	);
}

const withFacadePreview = createHigherOrderComponent(
	( BlockEdit ) =>
		function TribeEmbedEdit( props ) {
			if ( props.name !== 'core/embed' ) {
				return <BlockEdit { ...props } />;
			}

			return <FacadeEdit BlockEdit={ BlockEdit } { ...props } />;
		},
	'withFacadePreview'
);

addFilter(
	'editor.BlockEdit',
	'tribe-embed/facade-preview',
	withFacadePreview
);
