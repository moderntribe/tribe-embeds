<?php declare(strict_types=1);

namespace Tribe\Tribe_Embed\Util;

use Tribe\Tribe_Embed\Providers\Provider;
use Tribe\Tribe_Embed\Providers\Provider_Factory;

/**
 * Finds the provider for a video URL and gets its thumbnails.
 */
final class Thumbnail_Service {

	public function __construct(
		private Provider_Factory $factory,
		private Hooks $hooks
	) {
	}

	/**
	 * Get the provider, video id and thumbnails of a video URL.
	 *
	 * @param array<string,mixed> $video_url_data Parsed URL parts.
	 * @param array<string,mixed> $block          The embed block.
	 *
	 * @return array{provider:\Tribe\Tribe_Embed\Providers\Provider, video_id:string, thumb:array<string,mixed>}|null
	 */
	public function resolve_thumb( array $video_url_data, array $block ): ?array {
		$provider = $this->factory->resolve( $video_url_data, $block );

		if ( $provider === null ) {
			return null;
		}

		$video_id = $provider->get_video_id();

		if ( $video_id === '' ) {
			return null;
		}

		$thumbnails = $this->get_thumbnails( $provider );

		if ( $thumbnails === [] ) {
			return null;
		}

		return [
			'provider' => $provider,
			'video_id' => $video_id,
			'thumb'    => $thumbnails,
		];
	}

	/**
	 * @return array<string,mixed>
	 */
	private function get_thumbnails( Provider $provider ): array {
		try {
			$thumbnails = $provider->get_thumbnail_data();
		} catch ( \Throwable ) {
			$thumbnails = [];
		}

		$image_sizes = $this->factory->image_sizes_for( $provider::class );
		$thumbnails  = $this->hooks->filter( 'tribe_embed_thumbnail_data', $thumbnails, $provider, $image_sizes );

		if ( ! is_array( $thumbnails ) ) {
			return [];
		}

		return $thumbnails;
	}

}
