<?php declare(strict_types=1);

namespace Tribe\Tribe_Embed\Providers;

use Tribe\Tribe_Embed\Admin\Credentials;
use Tribe\Tribe_Embed\Util\Hooks;
use Tribe\Tribe_Embed\Util\Remote_Client;

/**
 * Finds the provider that handles a video URL.
 */
final class Provider_Factory {

	private const DEFAULT_PROVIDERS = [
		YouTube::class,
		Vimeo::class,
		Dailymotion::class,
		Wistia::class,
	];

	/** @var array<int,string> */
	private array $provider_classes;

	public function __construct(
		private Hooks $hooks,
		private Remote_Client $remote,
		private Thumbnail_Cache $cache,
		private Credentials $credentials
	) {
		$classes = $this->hooks->filter( 'tribe_embed_provider_classes', self::DEFAULT_PROVIDERS );

		$this->provider_classes = array_values(
			array_filter( array_map( 'strval', (array) $classes ), 'class_exists' )
		);
	}

	/**
	 * Find the provider for a video URL.
	 *
	 * @param array<string,mixed> $video_url_data Parsed URL parts incl. 'host'.
	 * @param array<string,mixed> $block          Gutenberg block array.
	 */
	public function resolve( array $video_url_data, array $block ): ?Provider {
		// Allow short-circuit with a ready-made provider instance.
		$provider = $this->hooks->filter( 'tribe_embed_video_provider', null, $video_url_data, $block );

		if ( $provider instanceof Provider ) {
			return $provider;
		}

		$host = strtolower( (string) ( $video_url_data['host'] ?? '' ) );

		if ( $host === '' ) {
			return null;
		}

		foreach ( $this->provider_classes as $class ) {
			if ( $this->accepts_host( $class, $host ) ) {
				return $this->create_provider( $class, $video_url_data );
			}
		}

		return null;
	}

	/**
	 * Image sizes for a provider, adjustable with the `tribe_embed_image_sizes_{slug}` and
	 * `tribe_embed_image_sizes` filters.
	 *
	 * @return array<int|string,mixed>
	 */
	public function image_sizes_for( string $provider_class ): array {
		$sizes = [];

		if ( is_subclass_of( $provider_class, Provider::class ) ) {
			$sizes = $provider_class::IMAGE_SIZES;
		}

		$slug  = $this->get_slug( $provider_class );
		$sizes = $this->hooks->filter( 'tribe_embed_image_sizes_' . $slug, $sizes, $provider_class );
		$sizes = $this->hooks->filter( 'tribe_embed_image_sizes', $sizes, $provider_class );

		return (array) $sizes;
	}

	/**
	 * A provider accepts a host that equals one of its hosts or is a subdomain of one.
	 */
	private function accepts_host( string $provider_class, string $host ): bool {
		foreach ( $this->get_allowed_hosts( $provider_class ) as $allowed_host ) {
			$allowed_host = strtolower( $allowed_host );

			if ( $host === $allowed_host || str_ends_with( $host, '.' . $allowed_host ) ) {
				return true;
			}
		}

		return false;
	}

	/**
	 * Hosts for a provider, adjustable with the `tribe_embed_allowed_provider_hosts_{slug}` and
	 * `tribe_embed_allowed_provider_hosts` filters.
	 *
	 * @return array<int,string>
	 */
	private function get_allowed_hosts( string $provider_class ): array {
		$hosts = [];

		if ( is_subclass_of( $provider_class, Provider::class ) ) {
			$hosts = $provider_class::ALLOWED_HOSTS;
		}

		$slug  = $this->get_slug( $provider_class );
		$hosts = $this->hooks->filter( 'tribe_embed_allowed_provider_hosts_' . $slug, $hosts, $provider_class );
		$hosts = $this->hooks->filter( 'tribe_embed_allowed_provider_hosts', $hosts, $provider_class );

		return array_filter( (array) $hosts, 'is_string' );
	}

	/**
	 * The provider's SLUG constant, or its lowercased class name without a "Provider" suffix.
	 */
	private function get_slug( string $provider_class ): string {
		if ( is_subclass_of( $provider_class, Provider::class ) && $provider_class::SLUG !== '' ) {
			return $provider_class::SLUG;
		}

		$class_name = strtolower( substr( strrchr( '\\' . $provider_class, '\\' ), 1 ) );
		$slug       = sanitize_key( (string) preg_replace( '/_?provider$/', '', $class_name ) );

		if ( $slug === '' ) {
			return 'provider';
		}

		return $slug;
	}

	/**
	 * @param array<string,mixed> $video_url_data
	 */
	private function create_provider( string $provider_class, array $video_url_data ): ?Provider {
		$provider = match ( ltrim( $provider_class, '\\' ) ) {
			YouTube::class     => new YouTube( $video_url_data, $this->remote, $this->cache ),
			Dailymotion::class => new Dailymotion( $video_url_data, $this->remote, $this->cache ),
			Vimeo::class       => new Vimeo( $video_url_data, $this->remote, $this->cache, $this->credentials ),
			Wistia::class      => new Wistia( $video_url_data, $this->remote, $this->cache, $this->credentials ),
			default            => null,
		};

		if ( $provider !== null ) {
			return $provider;
		}

		return $this->create_custom_provider( $provider_class, $video_url_data );
	}

	/**
	 * Providers added with the `tribe_embed_provider_classes` filter take the parsed URL only.
	 *
	 * @param array<string,mixed> $video_url_data
	 */
	private function create_custom_provider( string $provider_class, array $video_url_data ): ?Provider {
		if ( ! is_subclass_of( $provider_class, Provider::class ) ) {
			return null;
		}

		try {
			return new $provider_class( $video_url_data );
		} catch ( \Throwable ) {
			return null;
		}
	}

}
