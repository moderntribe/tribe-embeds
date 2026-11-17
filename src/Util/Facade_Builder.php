<?php declare(strict_types=1);

namespace Tribe\Tribe_Embed\Util;

use Tribe\Tribe_Embed\Providers\Provider;

/**
 * Prepares the data for the facade view templates and renders them.
 * The markup lives in `templates/` and can be overridden from a theme (see Template_Loader).
 */
final class Facade_Builder {

	/**
	 * HTML allowed in the original embed markup: what supported players render, plus caption formatting.
	 */
	private const ALLOWED_EMBED_HTML = [
		'iframe'        => [
			'src'             => true,
			'height'          => true,
			'width'           => true,
			'frameborder'     => true,
			'allowfullscreen' => true,
			'title'           => true,
			'allow'           => true,
			'loading'         => true,
			'referrerpolicy'  => true,
		],
		'style'         => [],
		'wistia-player' => [
			'media-id' => true,
			'dnt'      => true,
			'aspect'   => true,
		],
		'script'        => [
			'src'   => true,
			'type'  => true,
			'async' => true,
		],
		'figure'        => [ 'class' => true ],
		'figcaption'    => [ 'class' => true ],
		'div'           => [ 'class' => true ],
		'a'             => [
			'class'     => true,
			'href'      => true,
			'data-type' => true,
		],
		'strong'        => [],
		'em'            => [],
		'sub'           => [],
		'sup'           => [],
		's'             => [],
		'kbd'           => [],
		'img'           => [
			'class' => true,
			'style' => true,
			'src'   => true,
			'alt'   => true,
		],
		'code'          => [],
		'mark'          => [
			'style' => true,
			'class' => true,
		],
	];

	/** Thumbnails whose aspect ratio differs from the largest one by more than this are left out of srcset. */
	private const RATIO_TOLERANCE = 0.02;

	// The wrapper filters below are deprecated, see render_wrapper_with_legacy_filters().
	private const LEGACY_OPEN_FILTER   = 'tribe_embeds_video_thumb_open_markup';
	private const LEGACY_CLOSE_FILTER  = 'tribe_embeds_video_thumb_close_markup';
	private const LEGACY_INLINE_FILTER = 'tribe_embeds_video_embed_template';
	private const INNER_MARKER         = "\x1ainner\x1a";
	private const AFTER_MARKER         = "\x1aafter\x1a";

	public function __construct(
		private Template_Loader $templates,
		private Hooks $hooks
	) {
	}

	/**
	 * Build the facade HTML.
	 *
	 * @param array<string,mixed> $thumb    Thumbnail data keyed by size.
	 * @param array<string,mixed> $block    The embed block.
	 * @param string              $video_id The provider's video ID.
	 */
	public function build( array $thumb, array $block, string $video_id, Provider $provider ): string {
		$context = $this->create_context( $thumb, $block, $video_id, $provider );

		$inner = $this->render_play_button( $context ) . $this->render_thumbnail( $context );
		$after = '';

		if ( $provider->uses_inline_embed() ) {
			$inner .= $this->render_inline_embed( $context, $provider );
		} else {
			$after = $this->render_embed_template( $context, $provider );
		}

		return $this->render_wrapper( $context, $inner, $after );
	}

	/**
	 * The data every template receives.
	 *
	 * @param array<string,mixed> $thumb
	 * @param array<string,mixed> $block
	 *
	 * @return array<string,mixed>
	 */
	private function create_context( array $thumb, array $block, string $video_id, Provider $provider ): array {
		$embed_html   = wp_kses( (string) ( $block['innerHTML'] ?? '' ), self::ALLOWED_EMBED_HTML );
		$caption_html = $this->get_caption( $embed_html );

		// Inline embeds already contain their caption.
		$wrapper_caption = $caption_html;

		if ( $provider->uses_inline_embed() ) {
			$wrapper_caption = '';
		}

		return [
			'block'           => $block,
			'video_id'        => $video_id,
			'thumbnail_data'  => $thumb,
			'wrapper_classes' => $this->get_wrapper_classes( $block, $video_id, $thumb ),
			'embed_html'      => $embed_html,
			'caption_html'    => $wrapper_caption,
			'label'           => $this->get_play_label( $caption_html ),
			'video_url'       => (string) ( $block['attrs']['url'] ?? '' ),
		];
	}

	/**
	 * @param array<string,mixed> $block
	 * @param array<string,mixed> $thumbnail_data
	 *
	 * @return string[]
	 */
	private function get_wrapper_classes( array $block, string $video_id, array $thumbnail_data ): array {
		$classes = [
			'wp-block-image',
			'tribe-embed',
			'is--' . ( $block['attrs']['providerNameSlug'] ?? 'default' ),
		];

		if ( ! empty( $block['attrs']['className'] ) ) {
			$classes = array_merge( $classes, explode( ' ', $block['attrs']['className'] ) );
		}

		if ( ! empty( $block['attrs']['align'] ) ) {
			$classes[] = 'align' . $block['attrs']['align'];
		}

		return $this->hooks->filter( 'tribe_embed_video_wrapper_classes', $classes, $block, $video_id, $thumbnail_data );
	}

	/**
	 * The caption HTML of the original embed block, if any.
	 */
	private function get_caption( string $embed_html ): string {
		if ( ! preg_match( '#<figcaption[^>]*>(.*?)</figcaption>#is', $embed_html, $matches ) ) {
			return '';
		}

		return trim( $matches[1] );
	}

	/**
	 * Accessible name of the play button. It includes the caption so each button on a page is distinct.
	 */
	private function get_play_label( string $caption_html ): string {
		$caption = trim( wp_strip_all_tags( $caption_html ) );

		if ( $caption === '' ) {
			return __( 'Play Video', 'tribe-embeds' );
		}

		/* translators: %s: video caption */
		return sprintf( __( 'Play video: %s', 'tribe-embeds' ), $caption );
	}

	/**
	 * Apply the filter of a rendered chunk. All chunk filters receive the block, video ID,
	 * thumbnail data and wrapper classes after the HTML.
	 *
	 * @param array<string,mixed> $context
	 */
	private function filter_chunk( string $hook, string $html, array $context, mixed ...$extra_args ): string {
		$filtered = $this->hooks->filter(
			$hook,
			$html,
			$context['block'],
			$context['video_id'],
			$context['thumbnail_data'],
			$context['wrapper_classes'],
			...$extra_args
		);

		return (string) $filtered;
	}

	/**
	 * @param array<string,mixed> $context
	 */
	private function render_play_button( array $context ): string {
		$html = $this->templates->render( 'parts/play-button', $context );

		return $this->filter_chunk( 'tribe_embed_video_button_html', $html, $context );
	}

	/**
	 * @param array<string,mixed> $context
	 */
	private function render_thumbnail( array $context ): string {
		if ( $context['thumbnail_data'] === [] ) {
			return '';
		}

		$html = $this->templates->render( 'parts/thumbnail', $context + $this->get_thumbnail_args( $context ) );

		return $this->filter_chunk( 'tribe_embed_video_thumb_markup', $html, $context );
	}

	/**
	 * @param array<string,mixed> $context
	 *
	 * @return array<string,mixed>
	 */
	private function get_thumbnail_args( array $context ): array {
		$thumbnails = $context['thumbnail_data'];
		$largest    = $this->get_largest_thumbnail( $thumbnails );
		$width      = absint( $largest['width'] );

		$classes = $this->hooks->filter(
			'tribe_embed_video_thumb_classes',
			[ 'tribe-embed__thumbnail' ],
			$context['block'],
			$context['video_id'],
			$thumbnails,
			$context['wrapper_classes']
		);

		return [
			'url'           => (string) $largest['url'],
			'width'         => $width,
			'height'        => absint( $largest['height'] ),
			'classes'       => (array) $classes,
			'srcset'        => $this->get_srcset( $thumbnails, $largest ),
			'sizes'         => $this->get_sizes( $width, $context ),
			'loading'       => $this->get_loading( $context ),
			'fetchpriority' => $this->get_fetchpriority( $context ),
		];
	}

	/**
	 * The thumbnail with the largest width. The last one wins on ties.
	 *
	 * @param array<string,mixed> $thumbnails
	 *
	 * @return array<string,mixed>
	 */
	private function get_largest_thumbnail( array $thumbnails ): array {
		$largest = [];

		foreach ( $thumbnails as $thumbnail ) {
			if ( $largest !== [] && $thumbnail['width'] < $largest['width'] ) {
				continue;
			}

			$largest = $thumbnail;
		}

		return $largest;
	}

	/**
	 * srcset of the thumbnails that have the same aspect ratio as the largest one:
	 * some providers also return cropped or letterboxed variants.
	 *
	 * @param array<string,mixed> $thumbnails
	 * @param array<string,mixed> $largest
	 */
	private function get_srcset( array $thumbnails, array $largest ): string {
		$largest_ratio = $this->get_aspect_ratio( $largest );
		$sources       = [];

		foreach ( $thumbnails as $thumbnail ) {
			if ( abs( $this->get_aspect_ratio( $thumbnail ) - $largest_ratio ) > self::RATIO_TOLERANCE ) {
				continue;
			}

			$sources[] = esc_url_raw( (string) $thumbnail['url'] ) . ' ' . absint( $thumbnail['width'] ) . 'w';
		}

		return implode( ',', $sources );
	}

	/**
	 * @param array<string,mixed> $thumbnail
	 */
	private function get_aspect_ratio( array $thumbnail ): float {
		return absint( $thumbnail['width'] ) / max( 1, absint( $thumbnail['height'] ) );
	}

	/**
	 * @param array<string,mixed> $context
	 */
	private function get_sizes( int $largest_width, array $context ): string {
		$default = '(max-width: ' . $largest_width . 'px) 100vw,' . $largest_width . 'px';

		return (string) $this->hooks->filter(
			'tribe_embed_thumbnail_sizes',
			$default,
			$largest_width,
			$context['block'],
			$context['video_id']
		);
	}

	/**
	 * @param array<string,mixed> $context
	 */
	private function get_loading( array $context ): string {
		$loading = $this->hooks->filter( 'tribe_embed_thumbnail_loading', 'lazy', $context['block'], $context['video_id'] );

		if ( $loading === 'eager' ) {
			return 'eager';
		}

		return 'lazy';
	}

	/**
	 * @param array<string,mixed> $context
	 */
	private function get_fetchpriority( array $context ): string {
		$priority = $this->hooks->filter(
			'tribe_embed_thumbnail_fetchpriority',
			'auto',
			$context['block'],
			$context['video_id']
		);

		if ( in_array( $priority, [ 'high', 'low' ], true ) ) {
			return $priority;
		}

		return 'auto';
	}

	/**
	 * The original embed markup in a <template>, cloned into the page when the thumbnail is clicked.
	 *
	 * @param array<string,mixed> $context
	 */
	private function render_embed_template( array $context, Provider $provider ): string {
		$html = $this->templates->render( 'parts/embed-template', $context );

		return $this->filter_chunk( 'tribe_embed_video_embed_template', $html, $context, $provider );
	}

	/**
	 * The original embed markup rendered directly inside the wrapper.
	 *
	 * @param array<string,mixed> $context
	 */
	private function render_inline_embed( array $context, Provider $provider ): string {
		$html = $this->templates->render( 'parts/embed-inline', $context );

		// Inline embeds used to share the filter of the <template> output.
		$html = $this->hooks->filter_deprecated(
			self::LEGACY_INLINE_FILTER,
			'tribe_embed_video_embed_inline',
			$html,
			$context['block'],
			$context['video_id'],
			$context['thumbnail_data'],
			$context['wrapper_classes'],
			$provider
		);

		return $this->filter_chunk( 'tribe_embed_video_embed_inline', (string) $html, $context, $provider );
	}

	/**
	 * @param array<string,mixed> $context
	 */
	private function render_wrapper( array $context, string $inner, string $after ): string {
		if ( has_filter( self::LEGACY_OPEN_FILTER ) || has_filter( self::LEGACY_CLOSE_FILTER ) ) {
			return $this->render_wrapper_with_legacy_filters( $context, $inner, $after );
		}

		return $this->templates->render( 'facade', $context + [
			'inner' => $inner,
			'after' => $after,
		] );
	}

	/**
	 * Keeps the deprecated open/close wrapper filters working.
	 *
	 * The wrapper template is rendered around two markers. The HTML before the first marker is the
	 * "open" markup, the HTML between the markers is the "close" markup. Each goes through its
	 * deprecated filter, then the real inner and after HTML take the place of the markers.
	 *
	 * @param array<string,mixed> $context
	 */
	private function render_wrapper_with_legacy_filters( array $context, string $inner, string $after ): string {
		$html = $this->templates->render( 'facade', $context + [
			'inner' => self::INNER_MARKER,
			'after' => self::AFTER_MARKER,
		] );

		// A theme override that ignores the placeholders cannot be split, so use it as it is.
		if ( ! str_contains( $html, self::INNER_MARKER ) || ! str_contains( $html, self::AFTER_MARKER ) ) {
			return str_replace( [ self::INNER_MARKER, self::AFTER_MARKER ], [ $inner, $after ], $html );
		}

		[ $open, $rest ]  = explode( self::INNER_MARKER, $html, 2 );
		[ $close, $tail ] = explode( self::AFTER_MARKER, $rest, 2 );

		$replacement = 'the tribe-embed/facade.php template';
		$args        = [
			$context['block'],
			$context['video_id'],
			$context['thumbnail_data'],
			$context['wrapper_classes'],
		];

		$open  = $this->hooks->filter_deprecated( self::LEGACY_OPEN_FILTER, $replacement, rtrim( $open ), ...$args );
		$close = $this->hooks->filter_deprecated( self::LEGACY_CLOSE_FILTER, $replacement, trim( $close ), ...$args );

		return $open . $inner . $close . $after . trim( $tail );
	}

}
