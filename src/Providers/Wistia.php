<?php declare(strict_types=1);

namespace Tribe\Tribe_Embed\Providers;

use Tribe\Tribe_Embed\Admin\Credentials;
use Tribe\Tribe_Embed\Util\Remote_Client;

final class Wistia extends Remote_Provider {

	public const SLUG             = 'wistia';
	public const THUMBNAIL_FILTER = 'tribe_embed_wistia_video_thumbnail_url';
	public const BASE_URL         = 'https://api.wistia.com/v1/medias?type=Video&hashed_id=';

	// A host also matches its subdomains, e.g. `account.wistia.com`.
	public const ALLOWED_HOSTS = [
		'wistia.com',
		'wi.st',
	];

	// Example: https://embed-ssl.wistia.com/deliveries/be29ff2d1c1e783bda383f30d4ec027152fcc6be.jpg?image_crop_resized=200x1200
	public const IMAGE_SIZES = [
		'thumbnail_320_url',
		'thumbnail_640_url',
		'thumbnail_url', // original max resolution
	];

	private const CROP_SIZES = [
		'thumbnail_320_url' => '320x260',
		'thumbnail_640_url' => '640x360',
		'thumbnail_url'     => '320x260',
	];

	private const ID_PATTERN = '/^[A-Za-z0-9]+$/';

	public function __construct(
		array $video_url,
		Remote_Client $remote,
		Thumbnail_Cache $cache,
		private Credentials $credentials
	) {
		parent::__construct( $video_url, $remote, $cache );
	}

	protected function is_configured(): bool {
		return $this->credentials->get_wistia_token() !== '';
	}

	protected function fetch_thumbnail_data(): array {
		$medias = $this->remote->get_json(
			self::BASE_URL . rawurlencode( $this->video_id ),
			[
				'headers' => [
					'authorization' => 'Bearer ' . $this->credentials->get_wistia_token(),
					'accept'        => 'application/json',
					'content-type'  => 'application/json',
				],
			]
		);

		if ( $medias === null ) {
			return [];
		}

		$thumbnail_url = $medias[0]['thumbnail']['url'] ?? '';

		if ( ! is_string( $thumbnail_url ) || $thumbnail_url === '' ) {
			return [];
		}

		$base_url   = strtok( $thumbnail_url, '?' );
		$thumbnails = [];

		foreach ( self::IMAGE_SIZES as $size ) {
			$url = add_query_arg( [ 'image_crop_resized' => self::CROP_SIZES[ $size ] ], $base_url );

			$image = $this->remote->get_image_data( $url );

			if ( $image === null ) {
				continue;
			}

			$thumbnails[ $size ] = $image;
		}

		return $thumbnails;
	}

	protected function set_video_id(): string {
		// /medias/HASHED_ID
		$path = trim( (string) ( $this->video_url['path'] ?? '' ), '/' );

		if ( ! str_starts_with( $path, 'medias/' ) ) {
			return '';
		}

		$segments = explode( '/', $path );

		return $this->valid_video_id( $segments[1] ?? '', self::ID_PATTERN );
	}

}
