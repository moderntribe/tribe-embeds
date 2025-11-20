<?php declare(strict_types=1);

namespace Tribe\Tribe_Embed\Providers;

final class YouTube extends Provider {

	public const BASE_URL = 'https://img.youtube.com/vi/';

	public const IMAGE_SIZES = [
		'mqdefault',
		'hqdefault',
		'sddefault',
		'maxresdefault',
	];

	public const ALLOWED_HOSTS = [
		'www.youtube.com',
		'youtube.com',
		'youtu.be',
	];

	/**
	 * Accepts a video id and returns an array of thumbnail data
	 */
	public function get_thumbnail_data(): array {

		// if we have no video id.
		if ( '' === $this->get_video_id() ) {
			return [];
		}

		// get the URL from the transient.
		$image_data = get_transient( 'tribe-embed_' . $this->get_video_id() );

		// if we don't have a transient.
		if ( false === $image_data ) {
			// Initialize image data array
			$image_data = [];

			foreach ( self::IMAGE_SIZES as $resolution ) {
				$location  = self::BASE_URL . esc_attr( $this->get_video_id() ) . '/' . $resolution . '.jpg';
				$image_url = wp_remote_get( $location );

				// if the request to the image doesn't error and returns a http 200 response code.
				if ( ( is_wp_error( $image_url ) ) || ( 200 !== wp_remote_retrieve_response_code( $image_url ) ) ) {
					continue;
				}

				$image_size = getimagesize( $location );
				$width      = $image_size[0];
				$height     = $image_size[1];

				// set the image data
				$image_data[ $resolution ] = [
					'url'    => $location,
					'width'  => $width,
					'height' => $height,
				];
			}

			// set the transient, storing the image url.
			set_transient( 'tribe-embed_' . $this->get_video_id(), $image_data, DAY_IN_SECONDS );
		}

		// return the thumbnail urls.
		return apply_filters( 'tribe-embed_youtube_video_thumbnail_data', $image_data, $this->get_video_id() );
	}

	protected function set_video_id(): string {

		switch ( $this->video_url['host'] ) {
			// for standard youtube URLs
			case 'www.youtube.com':
			case 'youtube.com':
				// parse the query part of the URL into its arguments.
				if ( ! empty( $this->video_url['path'] ) && str_starts_with( $this->video_url['path'], '/shorts/' ) ) {
					// Extract video ID from /shorts/VIDEO_ID path
					$path_parts = explode( '/', trim( $this->video_url['path'], '/' ) );
					if ( count( $path_parts ) >= 2 && $path_parts[0] === 'shorts' ) {
						return $path_parts[1];
					}

					return '';
				}

				// Handle standard YouTube URLs with query parameters
				if ( empty( $this->video_url['query'] ) ) {
					return '';
				}

				// parse the query part of the URL into its arguments.
				parse_str( $this->video_url['query'], $video_url_query_args );

				// if we cannot find a youtube video id.
				if ( empty( $video_url_query_args['v'] ) ) {
					return '';
				}

				// set the video id to the v query arg.
				return $video_url_query_args['v'];

			case 'youtu.be':
				// if we have a path.
				if ( empty( $this->video_url['path'] ) ) {
					return '';
				}

				// remove the preceeding slash.
				return str_replace( '/', '', $this->video_url['path'] );
		}

		return '';
	}

}
