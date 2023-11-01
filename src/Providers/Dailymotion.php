<?php declare(strict_types=1);

namespace Tribe\Tribe_Embed\Providers;

final class Dailymotion {

	public const BASE_URL = 'https://api.dailymotion.com/video/';

	// API docs: https://developers.dailymotion.com/api/#video-fields
	// How many images do we want to retrieve realistically?
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
		'www.dailymotion.com',
		'dailymotion.com',
		'dai.ly',
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
	 * Return the vimeo video thumbnail urls.
	 */
	public function get_thumbnail_data(): array {

		// if we have no video id.
		if ( '' === $this->get_video_id() ) {
			return [];
		}

		// get the URL from the transient.
		// $image_data = get_transient( 'tribe-embed_' . $this->get_video_id() );
		$image_data = false;

		// if we don't have a transient.
		if ( false === $image_data ) {
			$image_data = [];

			foreach ( self::IMAGE_SIZES as $resolution ) {
				// get the video details from the api.
				$video_details = wp_remote_get( self::BASE_URL . $this->get_video_id() . '?fields=' . $resolution );

				// if the request to the image errors or returns anything other than a http 200 response code.
				if ( ( is_wp_error( $video_details )) && ( 200 !== wp_remote_retrieve_response_code( $video_details ) ) ) {
					return '';
				}

				// grab the body of the response.
				$response_body = json_decode(
					wp_remote_retrieve_body(
						$video_details
					)
				);

				// get the image url from the json.
				$image_url = $response_body->$resolution;

				$image_size = getimagesize( $image_url );
				$width      = $image_size[0];
				$height     = $image_size[1];

				// set the image data
				$image_data[ $resolution ] = [
					'url'    => $image_url,
					'width'  => $width,
					'height' => $height,
				];
			}

			// set the transient, storing the image url.
			set_transient( 'tribe-embed_' . $this->get_video_id(), $image_data, DAY_IN_SECONDS );
		}

		// return the url.
		return apply_filters( 'tribe-embed_dailymotion_video_thumbnail_url', $image_data, $this->get_video_id() );
	}

	private function set_video_id(): string {
		switch ( $this->video_url['host'] ) {
			case 'www.dailymotion.com':
			case 'dailymotion.com':
				// if we have a path.
				if ( empty( $this->video_url['path'] ) ) {
					return '';
				}

				// remove the preceeding slash.
				return str_replace( '/video/', '', $this->video_url['path'] );

				break;
			case 'dai.ly':
				// if we have a path.
				if ( empty( $this->video_url['path'] ) ) {
					return '';
				}

				// remove the preceeding slash.
				return str_replace( '/', '', $this->video_url['path'] );

				break;
		}
	}

}
