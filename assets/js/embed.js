const EMBED_SELECTOR = '.tribe-embed';
const CLICK_SELECTOR = '.tribe-embed__thumbnail, .play-button';
const INIT_ATTRIBUTE = 'data-tribe-embed-init';

const YOUTUBE_HOSTS = [ 'www.youtube.com', 'youtube.com' ];

const preconnected = new Set();
let listening = false;
let scanScheduled = false;

/**
 * Add autoplay to the iframe src, using the provider's own query args.
 * YouTube also loads from the no cookie domain.
 * The URL API is used so the args are valid whether or not the src already has a query string.
 *
 * @param {Element} iframe
 */
function setIframeAttributes( iframe ) {
	let url;

	try {
		url = new URL( iframe.getAttribute( 'src' ), window.location.href );
	} catch {
		return;
	}

	if ( YOUTUBE_HOSTS.includes( url.hostname ) ) {
		url.hostname = 'www.youtube-nocookie.com';
		url.searchParams.set( 'rel', '0' );
		url.searchParams.set( 'showinfo', '0' );
	}

	url.searchParams.set( 'autoplay', '1' );

	// set the new iframe src including autoplay true.
	iframe.setAttribute( 'src', url.toString() );

	// set an allows attribute on the iframe with an autoplay value.
	iframe.setAttribute( 'allow', 'autoplay' );
}

/**
 * Configure and trigger play for <wistia-player> (Aurora web component).
 * Uses the element's autoplay attribute and api-ready event; window._wq does not apply.
 * See https://docs.wistia.com/docs/player-attributes-and-properties#autoplay
 * and https://docs.wistia.com/docs/player-events
 *
 * @param {Element} playerEl The <wistia-player> DOM element (in the document).
 */
function playWistiaPlayer( playerEl ) {
	if ( ! playerEl || playerEl.tagName !== 'WISTIA-PLAYER' ) {
		return;
	}
	playerEl.setAttribute( 'autoplay', '' );
	playerEl.addEventListener(
		'api-ready',
		() => {
			if ( typeof playerEl.play === 'function' ) {
				playerEl.play();
			}
		},
		{ once: true }
	);
}

/**
 * Get the <template> holding the original embed, if this facade has one.
 * It is the element right after the wrapper (not used by inline providers).
 *
 * @param {Element} embed
 * @return {HTMLTemplateElement|null} The template, or null.
 */
function getTemplate( embed ) {
	const template = embed.nextElementSibling;

	return template && template.tagName === 'TEMPLATE' ? template : null;
}

/**
 * Swap the facade for the real embed.
 *
 * @param {Element} embed
 * @param {Element} template
 */
function playEmbed( embed, template ) {
	// clone the template element.
	const contentOuter = template.content.cloneNode( true );

	// grab just the first child of the template - this is the figure block element which wraps the iframe.
	const content = contentOuter.children[ 0 ];

	if ( ! content ) {
		return;
	}

	// Wistia: set autoplay on <wistia-player> in the clone so it plays once injected (web component uses attributes, not _wq).
	const playerInClone = content.querySelector( 'wistia-player' );
	if ( playerInClone ) {
		playerInClone.setAttribute( 'autoplay', '' );
	}

	// add the iframe embed content before the embed wrapper.
	embed.before( content );

	// remove the embed wrapper including thumbnail.
	embed.remove();

	// remove the template item which holds the iframe.
	template.remove();

	// Wistia: ensure play when API is ready (handles async script load / custom element upgrade).
	const playerEl = content.querySelector( 'wistia-player' );
	if ( playerEl ) {
		playWistiaPlayer( playerEl );
	}

	// The play button that had focus is gone: move focus to the player so keyboard users are not dropped on <body>.
	const focusTarget = content.querySelector( 'iframe, wistia-player' );
	if ( focusTarget ) {
		if (
			focusTarget.tagName !== 'IFRAME' &&
			! focusTarget.hasAttribute( 'tabindex' )
		) {
			focusTarget.setAttribute( 'tabindex', '-1' );
		}
		focusTarget.focus();
	}
}

/**
 * Prepare a single facade: add autoplay args to the stored iframe.
 * Safe to call more than once.
 *
 * @param {Element} embed
 */
function prepareEmbed( embed ) {
	if ( embed.hasAttribute( INIT_ATTRIBUTE ) ) {
		return;
	}

	const template = getTemplate( embed );
	const embedContent = template && template.content.children[ 0 ];

	if ( ! embedContent ) {
		return;
	}

	embed.setAttribute( INIT_ATTRIBUTE, '' );

	const iframe = embedContent.querySelector( 'iframe' );
	if ( iframe ) {
		setIframeAttributes( iframe );
	}
}

/**
 * Prepare every facade that has not been prepared yet.
 */
function prepareEmbeds() {
	scanScheduled = false;

	document
		.querySelectorAll( `${ EMBED_SELECTOR }:not([${ INIT_ATTRIBUTE }])` )
		.forEach( prepareEmbed );
}

/**
 * Single delegated click handler, so facades added after load
 * (client-side navigation, AJAX, infinite scroll) work without re-binding.
 *
 * @param {MouseEvent} event
 */
function onClick( event ) {
	const trigger = event.target.closest( CLICK_SELECTOR );
	const embed = trigger && trigger.closest( EMBED_SELECTOR );

	if ( ! embed ) {
		return;
	}

	const template = getTemplate( embed );

	if ( ! template ) {
		return;
	}

	// make sure the iframe has its autoplay args even if the scan has not reached it yet.
	prepareEmbed( embed );
	playEmbed( embed, template );
}

/**
 * Warm the connection to the player's origin when the user shows intent (hover or keyboard focus).
 *
 * @param {Event} event
 */
function onIntent( event ) {
	const target = event.target;
	const embed =
		target && typeof target.closest === 'function'
			? target.closest( EMBED_SELECTOR )
			: null;
	const template = embed && getTemplate( embed );
	const iframe = template && template.content.querySelector( 'iframe' );

	if ( ! iframe ) {
		return;
	}

	let origin;

	try {
		origin = new URL( iframe.getAttribute( 'src' ), window.location.href )
			.origin;
	} catch {
		return;
	}

	if ( preconnected.has( origin ) ) {
		return;
	}

	preconnected.add( origin );

	const link = document.createElement( 'link' );
	link.rel = 'preconnect';
	link.href = origin;
	document.head.appendChild( link );
}

/**
 * Watch for facades inserted after the initial load.
 */
function observeNewEmbeds() {
	if ( ! window.MutationObserver ) {
		return;
	}

	new window.MutationObserver( () => {
		if ( scanScheduled ) {
			return;
		}
		scanScheduled = true;
		window.requestAnimationFrame( prepareEmbeds );
	} ).observe( document.body, { childList: true, subtree: true } );
}

export default function initEmbed() {
	prepareEmbeds();

	if ( listening ) {
		return;
	}
	listening = true;

	document.addEventListener( 'click', onClick );
	document.addEventListener( 'pointerover', onIntent, { passive: true } );
	document.addEventListener( 'focusin', onIntent );
	observeNewEmbeds();
}
