<?php declare(strict_types=1);

namespace Tribe\Tribe_Embed\Admin;

/**
 * API credentials: a constant in wp-config.php wins over the value saved on the settings page.
 */
final class Credentials {

	public const VIMEO_OPTION  = 'tribe_embed_vimeo_access_token';
	public const WISTIA_OPTION = 'tribe_embed_wistia_api_key';

	/**
	 * Option names used before 2.1.0. They are read until the setting is saved under the current name.
	 */
	public const LEGACY_OPTIONS = [
		self::VIMEO_OPTION  => 'tribe_embeds_vimeo_access_token',
		self::WISTIA_OPTION => 'tribe_embeds_wistia_api_key',
	];

	public function get_vimeo_token(): string {
		return $this->get_token( 'VIMEO_ACCESS_TOKEN', self::VIMEO_OPTION );
	}

	public function get_wistia_token(): string {
		return $this->get_token( 'WISTIA_API_KEY', self::WISTIA_OPTION );
	}

	/**
	 * The value saved in the database for an option, ignoring constants.
	 */
	public function get_stored_value( string $option ): string {
		$value = get_option( $option, false );

		if ( $value === false ) {
			$value = get_option( self::LEGACY_OPTIONS[ $option ], '' );
		}

		return (string) $value;
	}

	private function get_token( string $constant, string $option ): string {
		if ( defined( $constant ) && constant( $constant ) !== '' ) {
			return (string) constant( $constant );
		}

		return $this->get_stored_value( $option );
	}

}
