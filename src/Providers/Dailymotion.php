<?php declare(strict_types=1);

namespace Tribe\Tribe_Embed\Providers;

final class Dailymotion {

	public const BASE_URL = 'https://api.dailymotion.com/video/';

	public const IMAGE_SIZES = [
		'mqdefault',
		'hqdefault',
		'sddefault',
		'maxresdefault',
	];

	public const ALLOWED_HOSTS = [
		'www.dailymotion.com',
		'dailymotion.com',
	];

	private array $video_url;
	private string $video_id;

	private static self $instance;

	public function __construct( array $video_url = [] ) {
		$this->video_url = $video_url;
		$this->video_id  = $this->set_video_id();
	}

	public static function instance( array $video_url ): self {
		if ( ! isset( self::$instance ) ) {
			self::$instance = new self( $video_url );
		}

		return self::$instance;
	}

	public function get_video_id(): string {
		return $this->video_id;
	}

	/**
	 * TODO: Fetch all image sizes
	 * Return the vimeo video thumbnail url.
	 */
	public function get_thumbnail_data(): array {

		// if we have no video id.
		if ( '' === $this->get_video_id() ) {
			return '';
		}

		// get the URL from the transient.
		$image_url = get_transient( 'tribe-embed_' . $this->get_video_id() );

		// if we don't have a transient.
		if ( false === $image_url ) {
			// get the video details from the api.
			$video_details = wp_remote_get( self::BASE_URL . $this->get_video_id() . '?fields=thumbnail_url' );

			// if the request to the hi res image errors or returns anything other than a http 200 response code.
			if ( ( is_wp_error( $video_details )) && ( 200 !== wp_remote_retrieve_response_code( $video_details ) ) ) {
				return '';
			}

			// grab the body of the response.
			$video_details = json_decode(
				wp_remote_retrieve_body(
					$video_details
				)
			);

			// get the image url from the json.
			$image_url = $video_details->thumbnail_url;

			// set the transient, storing the image url.
			set_transient( 'tribe-embed_' . $this->get_video_id(), $image_url, DAY_IN_SECONDS );
		}

		// return the url.
		return apply_filters( 'tribe-embed_dailymotion_video_thumbnail_url', $image_url, $this->get_video_id() );
	}

	private function set_video_id(): string {

		switch ( $this->video_url['host'] ) {
			case 'www.dailymotion.com':
			case 'dailymotion.com':
				// if we have a path.
				if ( empty( $parsed_video_url['path'] ) ) {
					return '';
				}

				// remove the preceeding slash.
				return str_replace( '/video/', '', $parsed_video_url['path'] );

				break;
		}
	}

}
