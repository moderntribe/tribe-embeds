<?php declare(strict_types=1);

namespace Tribe\Tribe_Embed\Providers;

final class Dailymotion extends Remote_Provider {

	public const SLUG             = 'dailymotion';
	public const THUMBNAIL_FILTER = 'tribe_embed_dailymotion_video_thumbnail_url';
	public const BASE_URL         = 'https://api.dailymotion.com/video/';

	// API docs: https://developers.dailymotion.com/api/#video-fields
	public const IMAGE_SIZES = [
		'thumbnail_60_url',
		'thumbnail_120_url',
		'thumbnail_180_url',
		'thumbnail_240_url',
		'thumbnail_480_url',
		'thumbnail_720_url',
		'thumbnail_1080_url',
		'thumbnail_url', // max res
	];

	public const ALLOWED_HOSTS = [
		'dailymotion.com',
		'dai.ly',
	];

	private const ID_PATTERN = '/^[A-Za-z0-9]+$/';

	protected function fetch_thumbnail_data(): array {
		// One request returns every thumbnail URL.
		$video = $this->remote->get_json(
			self::BASE_URL . rawurlencode( $this->video_id ) . '?fields=' . implode( ',', self::IMAGE_SIZES )
		);

		if ( $video === null ) {
			return [];
		}

		$thumbnails = [];

		foreach ( self::IMAGE_SIZES as $size ) {
			$url = $video[ $size ] ?? '';

			if ( ! is_string( $url ) || $url === '' ) {
				continue;
			}

			$image = $this->remote->get_image_data( $url );

			if ( $image === null ) {
				continue;
			}

			$thumbnails[ $size ] = $image;
		}

		return $thumbnails;
	}

	protected function set_video_id(): string {
		$path = trim( (string) ( $this->video_url['path'] ?? '' ), '/' );

		if ( $this->video_url['host'] === 'dai.ly' ) {
			$segments = explode( '/', $path );

			return $this->valid_video_id( $segments[0], self::ID_PATTERN );
		}

		return $this->get_video_id_from_video_path( $path );
	}

	/**
	 * The ID in `video/ID`. Older URLs append `_slug` to it.
	 */
	private function get_video_id_from_video_path( string $path ): string {
		if ( ! str_starts_with( $path, 'video/' ) ) {
			return '';
		}

		$segments = explode( '/', $path );
		$id_parts = explode( '_', $segments[1] ?? '' );

		return $this->valid_video_id( $id_parts[0], self::ID_PATTERN );
	}

}
