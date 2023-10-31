import domReady from '@wordpress/dom-ready';

domReady( () => {
	// loop through each of the hd core embed wrapper elements on the page.
	// these are the elements that should contain the thumbnail.
	document.querySelectorAll( '.tribe-embed__wrapper' ).forEach( ( el, i ) => {
		// get the associated template element which holds the embed code.
		// it is the next element after the wrapper.
		const template = el.nextElementSibling;

		/**
		 * Add autoplay on the iframe as well as loading from youtube no cookie.
		 * Grabs the iframe from the embed template.
		 * Adds the autoplay and other attrs to the iframe src URL
		 * Replaces the standard youtube domain with the no cookie version.
		 */
		const iframe = template.content.children[ 0 ].querySelector( 'iframe' );
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

		// get the first child of the figure - the caption.
		const caption =
			template.content.children[ 0 ].querySelector( 'figcaption' );

		// if we have a caption.
		if ( caption ) {
			// create an element for the embed caption.
			const captionEl = document.createElement( 'figcaption' );
			captionEl.classList.add( 'wp-element-caption' );
			captionEl.innerHTML = caption.innerHTML;

			// add the caption, after the thumbnail.
			el.append( captionEl );
		}

		// create an array for storing the click event elements.
		const clickEls = [];

		// grab the thumbnail and play button of this embed.
		clickEls.push( el.querySelector( '.tribe-embed__thumbnail' ) );
		clickEls.push( el.querySelector( '.play-button' ) );

		// loop through each click event - play button and thumbnail.
		clickEls.forEach( ( clickEls, i ) => {
			// when the element is clicked.
			clickEls.addEventListener( 'click', () => {
				console.log( 'Click!' );
				// clone the template element.
				const contentOuter = template.content.cloneNode( true );

				// grab just the first child of the template - this is the figure block element which wraps the iframe.
				const content = contentOuter.children[ 0 ];

				// add the iframe embed content before the embed wrapper.
				el.before( content );

				// remove the embed wrapper including thumbnail.
				el.remove();

				// remove the template item which holds the iframe.
				template.remove();
			} );
		} );
	} );
} );
