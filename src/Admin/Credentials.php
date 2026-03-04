<?php declare(strict_types=1);

namespace Tribe\Tribe_Embed\Admin;

/**
 * Central source for API credentials (env or stored settings).
 * Providers depend on this instead of Settings_Page directly.
 */
final class Credentials {

	public static function get_vimeo_token(): string {
		if ( defined( 'VIMEO_ACCESS_TOKEN' ) && VIMEO_ACCESS_TOKEN !== '' ) {
			return (string) VIMEO_ACCESS_TOKEN;
		}

		$settings = Settings_Page::get_stored_settings();

		return isset( $settings[ Settings_Page::VIMEO_TOKEN ] ) ? (string) $settings[ Settings_Page::VIMEO_TOKEN ] : '';
	}

	public static function get_wistia_token(): string {
		if ( defined( 'WISTIA_API_KEY' ) && WISTIA_API_KEY !== '' ) {
			return (string) WISTIA_API_KEY;
		}

		$settings = Settings_Page::get_stored_settings();

		return isset( $settings[ Settings_Page::WISTIA_TOKEN ] ) ? (string) $settings[ Settings_Page::WISTIA_TOKEN ] : '';
	}

}
