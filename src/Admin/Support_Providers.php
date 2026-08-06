<?php declare(strict_types=1);

namespace Tribe\Tribe_Embed\Admin;

class Support_Providers {

	private const WISTIA_REGEX  = '#https?://[^\.]+\.wistia\.com/medias/[a-zA-Z0-9]+(?:\?.*)?$#i';
	private const WISTIA_OEMBED = 'https://fast.wistia.com/oembed';

	public function register(): void {
		add_filter( 'oembed_providers', [ $this, 'add_wistia_oembed' ], 10, 1 );
		add_filter( 'the_content', [ $GLOBALS['wp_embed'], 'autoembed' ], 8 );
	}

	/**
	 * @param array<string,array> $providers
	 *
	 * @return array<string,array>
	 */
	public function add_wistia_oembed( array $providers ): array {
		$providers[ self::WISTIA_REGEX ] = [ self::WISTIA_OEMBED, true ];

		return $providers;
	}

}
