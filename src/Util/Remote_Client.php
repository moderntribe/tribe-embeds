<?php declare(strict_types=1);

namespace Tribe\Tribe_Embed\Util;

/**
 * Remote requests made while a page renders. Every request has a short timeout.
 */
final class Remote_Client {

	private const TIMEOUT = 5;

	/**
	 * GET a URL.
	 *
	 * @param array<string,mixed> $args wp_remote_get arguments.
	 *
	 * @return array<string,mixed>|null The response, or null on an error or a non-200 status.
	 */
	public function get( string $url, array $args = [] ): ?array {
		$response = wp_remote_get( $url, $args + [ 'timeout' => self::TIMEOUT ] );

		if ( is_wp_error( $response ) ) {
			return null;
		}

		if ( wp_remote_retrieve_response_code( $response ) !== 200 ) {
			return null;
		}

		return $response;
	}

	/**
	 * Whether a URL answers a HEAD request with a 200 status.
	 */
	public function url_exists( string $url ): bool {
		$response = wp_remote_head( $url, [ 'timeout' => self::TIMEOUT ] );

		if ( is_wp_error( $response ) ) {
			return false;
		}

		return wp_remote_retrieve_response_code( $response ) === 200;
	}

	/**
	 * GET a URL and decode the JSON body (an object or a list).
	 *
	 * @param array<string,mixed> $args wp_remote_get arguments.
	 *
	 * @return array<int|string,mixed>|null
	 */
	public function get_json( string $url, array $args = [] ): ?array {
		$response = $this->get( $url, $args );

		if ( $response === null ) {
			return null;
		}

		$data = json_decode( wp_remote_retrieve_body( $response ), true );

		if ( ! is_array( $data ) ) {
			return null;
		}

		return $data;
	}

	/**
	 * Download an image to read its dimensions.
	 *
	 * @return array{url:string,width:int,height:int}|null
	 */
	public function get_image_data( string $url ): ?array {
		$response = $this->get( $url );

		if ( $response === null ) {
			return null;
		}

		// Only parse what the server says is an image.
		$content_type = wp_remote_retrieve_header( $response, 'content-type' );

		if ( ! is_string( $content_type ) || ! str_starts_with( $content_type, 'image/' ) ) {
			return null;
		}

		$size = getimagesizefromstring( wp_remote_retrieve_body( $response ) );

		if ( $size === false ) {
			return null;
		}

		return [
			'url'    => $url,
			'width'  => $size[0],
			'height' => $size[1],
		];
	}

}
