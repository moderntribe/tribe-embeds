<?php declare(strict_types=1);

namespace Tribe\Tribe_Embed\Providers;

use Tribe\Tribe_Embed\Admin\Settings_Page;

class Wistia extends Provider {

	public const BASE_URL = 'https://api.wistia.com/v1/medias/';

	public const ALLOWED_HOSTS = [
		'(^|\.)wistia\.com$',
		'(^|\.)wi\.st$',
	];

	// Example: https://embed-ssl.wistia.com/deliveries/be29ff2d1c1e783bda383f30d4ec027152fcc6be.jpg?image_crop_resized=200x1200
	public const IMAGE_SIZES = [
		'thumbnail_320_url',
		'thumbnail_640_url',
		'thumbnail_url', // original max resolution
	];

	public function get_thumbnail_data(): array {
		$token = $this->get_token();
		// if we have no video id.
		if ( '' === $this->get_video_id() || empty( $token ) ) {
			return [];
		}

		// get the URL from the transient.
		$image_data = get_transient( 'tribe-embed_' . $this->get_video_id() );

		if ( false === $image_data ) {
			$image_data = [];

			$video_details = wp_remote_get( self::BASE_URL . $this->get_video_id() . '.json', [
				'headers' => [
					'Authorization' => 'Bearer ' . $token,
					'accept'        => 'application/json',
				],
			] );

			// if the request to the image errors or returns anything other than a http 200 response code.
			if ( ( is_wp_error( $video_details ) ) || ( 200 !== wp_remote_retrieve_response_code( $video_details ) ) ) {
				return [];
			}

			// grab the body of the response.
			$response_body = json_decode(
				wp_remote_retrieve_body(
					$video_details
				)
			);

			if ( $response_body === null ) {
				return [];
			}

			foreach ( self::IMAGE_SIZES as $resolution ) {
				if ( empty( $response_body->thumbnail ) || empty( $response_body->thumbnail->url ) ) {
					continue;
				}

				$image_url = strtok( $response_body->thumbnail->url, '?' );
				switch ( $resolution ) {
					case 'thumbnail_640_url':
						$image_url = add_query_arg( [
							'image_crop_resized' => '640x360',
						], $image_url );
						break;
					case 'thumbnail_320_url':
						$image_url = add_query_arg( [
							'image_crop_resized' => '320x260',
						], $image_url );
						break;
				}


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

		// Prevent edge case when `$image_data` may have boolean value
		if ( ! is_array( $image_data ) || empty( $image_data ) ) {
			$image_data = [];
		}

		// return the url.
		return apply_filters( 'tribe-embed_wistia_video_thumbnail_url', $image_data, $this->get_video_id() );
	}

	protected function set_video_id(): string {
		if ( empty( $this->video_url['path'] ) ) {
			return '';
		}

		// remove the preceeding slash.
		return str_replace( '/medias/', '', $this->video_url['path'] );
	}

	protected function get_token(): string {
		if ( defined( 'WISTIA_API_KEY' ) && ! empty( WISTIA_API_KEY ) ) {
			return WISTIA_API_KEY;
		}

		$settings = Settings_Page::get_stored_settings();

		if ( ! empty( $settings[ Settings_Page::WISTIA_TOKEN ] ) ) {
			return (string) $settings[ Settings_Page::WISTIA_TOKEN ];
		}

		return '';
	}

}
