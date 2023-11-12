let embedBlocks = null;

/**
 * Add autoplay on the iframe as well as loading from youtube no cookie.
 * Grabs the iframe from the embed template.
 * Adds the autoplay and other attrs to the iframe src URL
 * Replaces the standard youtube domain with the no cookie version.
 *
 * @param {Element} iframe
 */
function setIframeAttributes( iframe ) {
	let iframeSrc =
		iframe.getAttribute( 'src' ) + '&rel=0&showinfo=0&autoplay=1';
	iframeSrc = iframeSrc.replace(
		'www.youtube.com',
		'www.youtube-nocookie.com'
	);

	// set the new iframe src including autoplay true.
	iframe.setAttribute( 'src', iframeSrc );

	// set an allows attribute on the iframe with an autoplay value.
	iframe.setAttribute( 'allow', 'autoplay' );
}

/**
 * Creates a new caption ellement
 * @param {Element} caption
 *
 * @return {Element} figcaption to insert into DOM
 */
function createCaptionEl( caption ) {
	const captionEl = document.createElement( 'figcaption' );
	captionEl.classList.add( 'wp-element-caption' );
	captionEl.innerHTML = caption.innerHTML;

	return captionEl;
}

/**
 * Setup event handlers
 *
 * @param {Element} embed
 * @param {Element} template
 */
function setupEventHandlers( embed, template ) {
	// create an array for storing the click event elements.
	const clickEls = [];

	// grab the thumbnail and play button of this embed.
	clickEls.push( embed.querySelector( '.tribe-embed__thumbnail' ) );
	clickEls.push( embed.querySelector( '.play-button' ) );

	if ( clickEls.length === 0 ) return;

	// loop through each click event - play button and thumbnail.
	clickEls.forEach( ( clickEl ) => {
		// when the element is clicked.
		clickEl.addEventListener( 'click', () => {
			// clone the template element.
			const contentOuter = template.content.cloneNode( true );

			// grab just the first child of the template - this is the figure block element which wraps the iframe.
			const content = contentOuter.children[ 0 ];

			// add the iframe embed content before the embed wrapper.
			embed.before( content );

			// remove the embed wrapper including thumbnail.
			embed.remove();

			// remove the template item which holds the iframe.
			template.remove();
		} );
	} );
}

/**
 * Core function which calls various actions on the embed block
 */
function updateEmbeds() {
	embedBlocks.forEach( ( embed ) => {
		// get the associated template element which holds the embed code.
		// it is the next element after the wrapper.
		const template = embed.nextElementSibling;

		const iframe = template.content.children[ 0 ].querySelector( 'iframe' );
		setIframeAttributes( iframe );

		// get the first child of the figure and add after tumbnail if present
		const caption =
			template.content.children[ 0 ].querySelector( 'figcaption' );

		if ( caption ) {
			const captionEl = createCaptionEl( caption );
			embed.append( captionEl );
		}

		// add event listeners to thumbnail and play button
		setupEventHandlers( embed, template );
	} );
}

export default function initEmbed() {
	embedBlocks = document.querySelectorAll( '.tribe-embed' );

	if ( embedBlocks.length === 0 ) return;

	updateEmbeds();
}
