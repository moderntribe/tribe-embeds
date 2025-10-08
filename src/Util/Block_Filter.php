<?php declare(strict_types=1);

namespace Tribe\Tribe_Embed\Util;

use Tribe\Tribe_Embed\Providers\Provider_Factory;

/**
 * Handles render_block filter.
 * Decides whether to replace core/embed with facade.
 */
final class Block_Filter {

	private Url_Parser $url_parser;

	// @phpstan-ignore-next-line
	private Provider_Factory $factory;
	private Thumbnail_Service $thumbs;
	private Facade_Builder $facade;
	private bool $hooks_added = false;

	public function __construct(
		Url_Parser $url_parser,
		Provider_Factory $factory,
		Thumbnail_Service $thumbs,
		Facade_Builder $facade
	) {
		$this->url_parser = $url_parser;
		$this->factory    = $factory;
		$this->thumbs     = $thumbs;
		$this->facade     = $facade;
	}

	/** Attach filter once */
	public function register_hooks(): void {
		if ( $this->hooks_added ) {
			return;
		}
		add_filter( 'render_block', [ $this, 'filter_render_block' ], 10, 2 );
		$this->hooks_added = true;
	}

	/**
	 * Filter callback for render_block
	 * - Only processes core/embed blocks
	 * - Provider may render fully or fallback to facade
	 *
	 * @param string $html  Original block HTML
	 * @param array  $block Block data
	 */
	public function filter_render_block( string $html, array $block ): string {
		if ( ! isset( $block['blockName'] ) || $block['blockName'] !== 'core/embed' ) {
			return $html;
		}

		$attrs = (array) ($block['attrs'] ?? []);
		$url   = isset( $attrs['url'] ) && is_string( $attrs['url'] ) ? $attrs['url'] : '';
		if ( $url === '' ) {
			return $html;
		}

		$video_url_data = $this->url_parser->parse_url( $url );
		if ( $video_url_data === null ) {
			return $html;
		}

		// Fallback: use thumbnails and facade
		$result = $this->thumbs->resolve_thumb( $video_url_data, $block );
		if ( $result === null ) {
			return $html;
		}

		// buffer the output as we need to return not echo.
		ob_start();

		// output the registered "block" styles for the thumbnail.
		wp_print_styles( 'tribe-embeds-styles' );

		$facade_html = $this->facade->build( $result['thumb'], $block, $result['video_id'] );

		/**
		 * Fires and action to which the new block markup is added too.
		 */
		echo apply_filters( 'tribe_embeds_facade_html', $facade_html, $result['provider'], $block, $html );

		return ob_get_clean();
	}

}
