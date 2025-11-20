<?php declare(strict_types=1);

namespace Tribe\Tribe_Embed\Util;

use Tribe\Tribe_Embed\Providers\Provider_Factory;

/**
 * Resolves provider thumbnails for generic facades.
 */
final class Thumbnail_Service {

	private \Tribe\Tribe_Embed\Providers\Provider_Factory $factory;

	public function __construct( Provider_Factory $factory ) {
		$this->factory = $factory;
	}

	/**
	 * Get provider, video id, and thumbnails.
	 *
	 * @return array{provider:object, video_id:string, thumb:array<string,mixed>}|null
	 */
	public function resolve_thumb( array $video_url_data, array $block ): ?array {
		$provider = $this->factory->resolve( $video_url_data, $block );
		if ( ! is_object( $provider ) ) {
			return null;
		}

		$video_id = $provider->get_video_id();

		if ( ! $video_id ) {
			return null;
		}

		$image_sizes = $this->factory->image_sizes_for( $provider::class );

		$thumb = $this->safe_get_thumbnail_data( $provider, $image_sizes );
		if ( empty( $thumb ) ) {
			return null;
		}

		return [
			'provider' => $provider,
			'video_id' => $video_id,
			'thumb'    => $thumb,
		];
	}

	/** Call provider get_thumbnail_data and filter results */
	private function safe_get_thumbnail_data( object $provider, array $image_sizes ): array {
		if ( ! method_exists( $provider, 'get_thumbnail_data' ) ) {
			return apply_filters( 'tribe_embeds_thumbnail_data', [], $provider, $image_sizes );
		}

		try {
			$data = $provider->get_thumbnail_data( $image_sizes );
		} catch ( \Throwable $e ) {
			$data = [];
		}

		return apply_filters( 'tribe_embeds_thumbnail_data', is_array( $data ) ? $data : [], $provider, $image_sizes );
	}

}
