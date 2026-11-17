<?php declare(strict_types=1);

namespace Tribe\Tribe_Embed\Providers;

final class YouTube extends Remote_Provider {

	public const SLUG             = 'youtube';
	public const THUMBNAIL_FILTER = 'tribe_embed_youtube_video_thumbnail_data';
	public const BASE_URL         = 'https://img.youtube.com/vi/';

	public const IMAGE_SIZES = [
		'mqdefault',
		'hqdefault',
		'sddefault',
		'maxresdefault',
	];

	public const ALLOWED_HOSTS = [
		'youtube.com',
		'youtu.be',
	];

	/**
	 * Fixed [ width, height ] of each thumbnail, so the images do not have to be downloaded to be measured.
	 */
	private const IMAGE_DIMENSIONS = [
		'mqdefault'     => [ 320, 180 ],
		'hqdefault'     => [ 480, 360 ],
		'sddefault'     => [ 640, 480 ],
		'maxresdefault' => [ 1280, 720 ],
	];

	/** YouTube video IDs are 11 characters of [A-Za-z0-9_-]. */
	private const ID_PATTERN = '/^[A-Za-z0-9_-]{11}$/';

	protected function fetch_thumbnail_data(): array {
		$thumbnails = [];

		foreach ( self::IMAGE_SIZES as $size ) {
			$url = self::BASE_URL . rawurlencode( $this->video_id ) . '/' . $size . '.jpg';

			// Only check the image exists: its size is known.
			if ( ! $this->remote->url_exists( $url ) ) {
				continue;
			}

			[ $width, $height ] = self::IMAGE_DIMENSIONS[ $size ];

			$thumbnails[ $size ] = [
				'url'    => $url,
				'width'  => $width,
				'height' => $height,
			];
		}

		return $thumbnails;
	}

	protected function set_video_id(): string {
		$path = (string) ( $this->video_url['path'] ?? '' );

		if ( $this->video_url['host'] === 'youtu.be' ) {
			$segments = explode( '/', trim( $path, '/' ) );

			return $this->valid_video_id( $segments[0], self::ID_PATTERN );
		}

		return $this->get_video_id_from_youtube_url( $path );
	}

	/**
	 * The ID of a `youtube.com/shorts/ID` or `youtube.com/watch?v=ID` URL.
	 */
	private function get_video_id_from_youtube_url( string $path ): string {
		if ( str_starts_with( $path, '/shorts/' ) ) {
			$segments = explode( '/', trim( $path, '/' ) );

			return $this->valid_video_id( $segments[1] ?? '', self::ID_PATTERN );
		}

		parse_str( (string) ( $this->video_url['query'] ?? '' ), $query );

		// `v` is missing, or an array for `?v[]=x`.
		if ( ! isset( $query['v'] ) || ! is_string( $query['v'] ) ) {
			return '';
		}

		return $this->valid_video_id( $query['v'], self::ID_PATTERN );
	}

}
