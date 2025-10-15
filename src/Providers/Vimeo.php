<?php declare(strict_types=1);

namespace Tribe\Tribe_Embed\Providers;

use Tribe\Tribe_Embed\Admin\Settings_Page;

final class Vimeo extends Provider {

	public const BASE_URL = 'https://api.vimeo.com/videos/%s/pictures';

	public const ALLOWED_HOSTS = [
		'www.vimeo.com',
		'vimeo.com',
	];

	/**
	 * Return the vimeo video thumbnail urls.
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
			$image_data = [];

			$response_body = $this->get_video_pictures();

			if ( empty( $response_body ) || empty( $response_body['data'] ) || empty( $response_body['data'][0]['sizes'] ) ) {
				return [];
			}

			foreach ( $response_body['data'][0]['sizes'] as $resolution ) {
				// get the image url from the json.
				$image_url = $resolution['link'];
				$width     = $resolution['width'];
				$height    = $resolution['height'];

				$resolution_name = sprintf( 'thumbnail_%s_%s', $width, $height );

				// set the image data
				$image_data[ $resolution_name ] = [
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

	protected function get_video_pictures(): array {
		$token = $this->get_token();

		if ( empty( $token ) ) {
			return [];
		}

		// get the video details from the api.
		$video_details = wp_remote_get(
			sprintf( self::BASE_URL, $this->get_video_id() ),
			[
				'headers' => [
					'Authorization' => sprintf( 'Bearer %s', $token ),
					'Accept'        => 'application/json',
				],
			]
		);

		// if the request to the hi res image errors or returns anything other than a http 200 response code.
		if ( ( is_wp_error( $video_details )) && ( 200 !== wp_remote_retrieve_response_code( $video_details ) ) ) {
			return [];
		}

		// grab the body of the response.
		$response_body = json_decode(
			wp_remote_retrieve_body(
				$video_details
			),
			true
		);

		if ( $response_body === null ) {
			return [];
		}

		return $response_body;
	}

	protected function set_video_id(): string {
		switch ( $this->video_url['host'] ) {
			case 'vimeo.com':
			case 'www.vimeo.com':
				if ( empty( $this->video_url['path'] ) ) {
					return '';
				}

				$maybe_find_correct_id = explode( '/', trim( $this->video_url['path'] ) );

				if ( is_array( $maybe_find_correct_id ) && isset( $maybe_find_correct_id[2] ) ) {
					// urls like https://vimeo.com/1083696811/fd0767701e
					return $maybe_find_correct_id[1];
				}

				// remove the preceeding slash.
				return str_replace( '/', '', $this->video_url['path'] );

			default:
				return '';
		}
	}

	protected function get_token(): string {
		if ( defined( 'VIMEO_ACCESS_TOKEN' ) && ! empty( VIMEO_ACCESS_TOKEN ) ) {
			return VIMEO_ACCESS_TOKEN;
		}

		$settings = Settings_Page::get_stored_settings();

		if ( ! empty( $settings[ Settings_Page::VIMEO_TOKEN ] ) ) {
			return $settings[ Settings_Page::VIMEO_TOKEN ];
		}

		return '';
	}

}
