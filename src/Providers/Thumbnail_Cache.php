<?php declare(strict_types=1);

namespace Tribe\Tribe_Embed\Providers;

/**
 * Caches provider thumbnail data in transients.
 */
final class Thumbnail_Cache {

	/** Prefix of every transient this class writes. */
	public const KEY_PREFIX = 'tribe-embed_';

	/** Seconds found thumbnails are kept. */
	private const TTL = DAY_IN_SECONDS;

	/** Seconds a failed lookup is remembered, so a broken API is not called on every page view. */
	private const FAILURE_TTL = 300;

	/**
	 * @return array<string,mixed>|null Null when nothing, not even a failure, is cached.
	 */
	public function get( string $provider_slug, string $video_id ): ?array {
		$cached = get_transient( $this->get_key( $provider_slug, $video_id ) );

		if ( ! is_array( $cached ) ) {
			return null;
		}

		return $cached;
	}

	/**
	 * @param array<string,mixed> $thumbnails An empty array is cached briefly, as a failure.
	 */
	public function set( string $provider_slug, string $video_id, array $thumbnails ): void {
		$ttl = self::TTL;

		if ( $thumbnails === [] ) {
			$ttl = self::FAILURE_TTL;
		}

		set_transient( $this->get_key( $provider_slug, $video_id ), $thumbnails, $ttl );
	}

	private function get_key( string $provider_slug, string $video_id ): string {
		return self::KEY_PREFIX . $provider_slug . '_' . $video_id;
	}

}
