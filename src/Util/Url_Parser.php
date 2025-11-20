<?php declare(strict_types=1);

namespace Tribe\Tribe_Embed\Util;

/**
 * Normalize and parse video URLs.
 */
final class Url_Parser {

	/**
	 * Parse URL safely.
	 *
	 * @return array<string,mixed>|null
	 */
	public function parse_url( string $url ): ?array {
		$url = trim( $url );
		if ( $url === '' ) {
			return null;
		}

		if ( str_starts_with( $url, '//' ) ) {
			$url = 'https:' . $url;
		} elseif ( ! preg_match( '#^https?://#i', $url ) ) {
			$url = 'https://' . ltrim( $url, '/' );
		}

		$parts = parse_url( $url );
		if ( ! is_array( $parts ) || empty( $parts['host'] ) ) {
			return null;
		}

		$parts['host'] = strtolower( (string) $parts['host'] );
		$parts['url']  = $url;

		return $parts;
	}

}
