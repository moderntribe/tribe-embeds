<?php declare(strict_types=1);

namespace Tribe\Tribe_Embed\Providers;

use Tribe\Tribe_Embed\Admin\Credentials;
use Tribe\Tribe_Embed\Util\Remote_Client;

final class Vimeo extends Remote_Provider {

	public const SLUG             = 'vimeo';
	public const THUMBNAIL_FILTER = 'tribe_embed_vimeo_video_thumbnail_url';
	public const BASE_URL         = 'https://api.vimeo.com/videos/%s/pictures';

	public const ALLOWED_HOSTS = [
		'vimeo.com',
	];

	public function __construct(
		array $video_url,
		Remote_Client $remote,
		Thumbnail_Cache $cache,
		private Credentials $credentials
	) {
		parent::__construct( $video_url, $remote, $cache );
	}

	protected function is_configured(): bool {
		return $this->credentials->get_vimeo_token() !== '';
	}

	protected function fetch_thumbnail_data(): array {
		$pictures = $this->remote->get_json(
			sprintf( self::BASE_URL, rawurlencode( $this->video_id ) ),
			[
				'headers' => [
					'Authorization' => 'Bearer ' . $this->credentials->get_vimeo_token(),
					'Accept'        => 'application/json',
				],
			]
		);

		if ( $pictures === null ) {
			return [];
		}

		$sizes = $pictures['data'][0]['sizes'] ?? [];

		if ( ! is_array( $sizes ) ) {
			return [];
		}

		$thumbnails = [];

		foreach ( $sizes as $size ) {
			$name = sprintf( 'thumbnail_%s_%s', $size['width'], $size['height'] );

			$thumbnails[ $name ] = [
				'url'    => $size['link'],
				'width'  => $size['width'],
				'height' => $size['height'],
			];
		}

		return $thumbnails;
	}

	protected function set_video_id(): string {
		$path = (string) ( $this->video_url['path'] ?? '' );

		// The ID is the first all-digit segment: /123, /123/hash, /video/123, /channels/name/123.
		foreach ( explode( '/', trim( $path, '/' ) ) as $segment ) {
			if ( preg_match( '/^\d+$/', $segment ) === 1 ) {
				return $segment;
			}
		}

		return '';
	}

}
