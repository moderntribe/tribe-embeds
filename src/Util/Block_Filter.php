<?php declare(strict_types=1);

namespace Tribe\Tribe_Embed\Util;

/**
 * Replaces the output of `core/embed` blocks with a facade.
 */
final class Block_Filter {

	public function __construct(
		private Url_Parser $url_parser,
		private Thumbnail_Service $thumbnail_service,
		private Facade_Builder $facade_builder,
		private Assets $assets,
		private Hooks $hooks
	) {
	}

	public function register_hooks(): void {
		add_filter( 'render_block', [ $this, 'filter_render_block' ], 10, 2 );
	}

	/**
	 * Filter callback for render_block. Only `core/embed` blocks with a supported video are changed.
	 *
	 * @param string              $html  Original block HTML.
	 * @param array<string,mixed> $block Block data.
	 */
	public function filter_render_block( string $html, array $block ): string {
		if ( ( $block['blockName'] ?? '' ) !== 'core/embed' ) {
			return $html;
		}

		// Feed readers can't run the play script, so they keep the original embed markup.
		if ( is_feed() ) {
			return $html;
		}

		$facade_html = $this->build_facade( $block, $html );

		if ( $facade_html === null ) {
			return $html;
		}

		// Enqueue the script/style, and print the style now (once) so the facade is never unstyled.
		$this->assets->enqueue_public_assets();

		ob_start();
		$this->assets->print_public_styles();
		echo $facade_html;

		return (string) ob_get_clean();
	}

	/**
	 * Build the facade for an embed block, without touching assets.
	 *
	 * @param array<string,mixed> $block         Block data (`attrs`, `innerHTML`).
	 * @param string              $original_html Original rendered block HTML, passed to filters.
	 *
	 * @return string|null Facade HTML, or null when the URL is not a supported video or has no thumbnail.
	 */
	public function build_facade( array $block, string $original_html = '' ): ?string {
		$url = (string) ( $block['attrs']['url'] ?? '' );

		if ( $url === '' ) {
			return null;
		}

		$video_url_data = $this->url_parser->parse_url( $url );

		if ( $video_url_data === null ) {
			return null;
		}

		$video = $this->thumbnail_service->resolve_thumb( $video_url_data, $block );

		if ( $video === null ) {
			return null;
		}

		$facade_html = $this->facade_builder->build( $video['thumb'], $block, $video['video_id'], $video['provider'] );

		return (string) $this->hooks->filter(
			'tribe_embed_facade_html',
			$facade_html,
			$video['provider'],
			$block,
			$original_html
		);
	}

}
