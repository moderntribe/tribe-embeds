<?php declare(strict_types=1);

namespace Tribe\Tribe_Embed\Providers;

/**
 * Resolves providers and exposes per-provider hooks.
 */
final class Provider_Factory {

	/** @var array<int, class-string> */
	private array $provider_classes;

	/**
	 * @param array<int, class-string> $provider_classes Optional override list.
	 */
	public function __construct( array $provider_classes = [] ) {
		$defaults = [
			'\\Tribe\\Tribe_Embed\\Providers\\YouTube',
			'\\Tribe\\Tribe_Embed\\Providers\\Vimeo',
			'\\Tribe\\Tribe_Embed\\Providers\\Dailymotion',
			'\\Tribe\\Tribe_Embed\\Providers\\Wistia',
		];

		/** Allow external override of provider class list */
		$filtered = apply_filters( 'tribe_embeds_provider_classes', $provider_classes ?: $defaults );

		$this->provider_classes = array_values( array_filter(
			array_map( 'strval', (array) $filtered ),
			static function ( string $class ): bool {
				return class_exists( $class );
			}
		) );
	}

	/**
	 * Resolve provider instance or null.
	 *
	 * @param array<string,mixed> $video_url_data Parsed URL parts incl. 'host'
	 * @param array<string,mixed> $block          Gutenberg block array
	 */
	public function resolve( array $video_url_data, array $block ): ?object {
		/** Allow short-circuit with a ready-made provider instance */
		$maybe = apply_filters( 'tribe_embeds_video_provider', null, $video_url_data, $block );
		if ( is_object( $maybe ) ) {
			return $maybe;
		}

		/** Match by per-provider allowed hosts */
		$host = strtolower( (string) ($video_url_data['host'] ?? '') );

		if ( $host !== '' ) {
			foreach ( $this->provider_classes as $class ) {
				if ( in_array( $host, $this->allowed_hosts_for( $class ), true ) ) {
					return $this->instantiate( $class, $video_url_data );
				}
			}
		}

		return null;
	}

	/**
	 * Get allowed hosts for a provider (constant + filters).
	 *
	 * Filters:
	 * - tribe_embeds_allowed_provider_hosts_{slug}
	 * - tribe_embeds_allowed_provider_hosts
	 *
	 * @return array<int,string> Lowercased hostnames.
	 */
	public function allowed_hosts_for( string $provider_class ): array {
		$base = [];

		if ( defined( $provider_class . '::ALLOWED_HOSTS' ) ) {
			/** @var array<int,string> $base */
			$base = (array) $provider_class::ALLOWED_HOSTS;
		}

		$slug = $this->provider_slug( $provider_class );

		$by_provider = apply_filters( 'tribe_embeds_allowed_provider_hosts_' . $slug, $base, $provider_class );
		$global      = apply_filters( 'tribe_embeds_allowed_provider_hosts', $by_provider, $provider_class );

		return array_values( array_unique( array_map( 'strtolower', array_filter( (array) $global, 'is_string' ) ) ) );
	}

	/**
	 * Get image sizes for a provider (constant + filters).
	 *
	 * Filters:
	 * - tribe_embeds_image_sizes_{slug}
	 * - tribe_embeds_image_sizes
	 *
	 * @return array<string,mixed>
	 */
	public function image_sizes_for( string $provider_class ): array {
		$base = [];

		if ( defined( $provider_class . '::IMAGE_SIZES' ) ) {
			/** @var array<string,mixed> $base */
			$base = (array) $provider_class::IMAGE_SIZES;
		}

		$slug = $this->provider_slug( $provider_class );

		$by_provider = apply_filters( 'tribe_embeds_image_sizes_' . $slug, $base, $provider_class );
		$global      = apply_filters( 'tribe_embeds_image_sizes', $by_provider, $provider_class );

		return (array) $global;
	}

	/**
	 * Slug used in filter names.
	 * Uses class SLUG constant if present; otherwise derived from class name.
	 */
	public function provider_slug( string $provider_class ): string {
		if ( defined( $provider_class . '::SLUG' ) ) {
			$slug = (string) $provider_class::SLUG;
			if ( $slug !== '' ) {
				return sanitize_key( $slug );
			}
		}

		$base = strtolower( trim( (string) strrchr( '\\' . ltrim( $provider_class, '\\' ), '\\' ), '\\' ) );
		$base = preg_replace( '/_provider$|provider$/', '', $base );

		return sanitize_key( $base ?: 'provider' );
	}

	/**
	 * Instantiate provider safely.
	 */
	private function instantiate( string $class, array $video_url_data ): ?object {
		try {
			return new $class( $video_url_data );
		} catch ( \Throwable $e ) {
			try {
				return new $class();
			} catch ( \Throwable ) {
				return null;
			}
		}
	}

}
