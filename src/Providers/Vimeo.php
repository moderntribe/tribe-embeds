<?php declare(strict_types=1);

namespace Tribe\Tribe_Embed\Providers;

final class Vimeo {

	public const BASE_URL = 'https://vimeo.com/api/v2/video/';

	public const IMAGE_SIZES = [
		'thumbnail_small',
		'thumbnail_medium',
		'thumbnail_large',
	];

	public const ALLOWED_HOSTS = [
		'www.vimeo.com',
		'vimeo.com',
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
			return [];
		}

		// get the URL from the transient.
		// $image_data = get_transient( 'tribe-embed_' . $this->get_video_id() );
		$image_data = false;

		// if we don't have a transient.
		if ( false === $image_data ) {
			$image_data = [];

			// get the video details from the api.
			$video_details = wp_remote_get(
				self::BASE_URL . esc_attr( $this->get_video_id() ) . '.json'
			);

			// if the request to the hi res image errors or returns anything other than a http 200 response code.
			if ( ( is_wp_error( $video_details )) && ( 200 !== wp_remote_retrieve_response_code( $video_details ) ) ) {
				return [];
			}

			// grab the body of the response.
			$video_details = json_decode(
				wp_remote_retrieve_body(
					$video_details
				)
			);

			foreach ( self::IMAGE_SIZES as $resolution ) {
				// get the image url from the json.
				$image_url = $video_details[0]->$resolution;

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
		return apply_filters( 'tribe-embed_vimeo_video_thumbnail_url', $image_data, $this->get_video_id() );
	}

	private function set_video_id(): string {
		switch ( $this->video_url['host'] ) {
			case 'vimeo.com':
			case 'www.vimeo.com':
				// if we have a path.
				if ( $this->video_url['path'] === '' ) {
					return $this->video_url['path'];
				}

				// remove the preceeding slash.
				return str_replace( '/', '', $this->video_url['path'] );

				break;
		}
	}

}
