<?php declare(strict_types=1);

namespace Tribe\Tribe_Embed\Util;

/**
 * Applies the plugin's filters.
 *
 * Some filters were called `tribe_embeds_*` before 2.1.0 and are now `tribe_embed_*`.
 * Callbacks still attached to an old name keep working: they run first (so callbacks on the
 * new name get the last word) and WordPress logs a deprecation notice when WP_DEBUG is on.
 * Nothing extra runs when nothing is attached to an old name.
 */
final class Hooks {

	public const RENAMED_IN = '2.1.0';

	private const CURRENT_PREFIX = 'tribe_embed_';
	private const LEGACY_PREFIX  = 'tribe_embeds_';

	/**
	 * Renamed hooks, without prefix.
	 */
	private const RENAMED = [
		'allowed_provider_hosts',
		'image_sizes',
		'provider_classes',
		'video_provider',
		'facade_html',
		'thumbnail_data',
		'video_button_html',
		'video_embed_template',
		'video_thumb_classes',
		'video_thumb_markup',
		'video_wrapper_classes',
	];

	/**
	 * Renamed hooks that continue with a dynamic part, e.g. `allowed_provider_hosts_{slug}`.
	 */
	private const RENAMED_PREFIXES = [
		'allowed_provider_hosts_',
		'image_sizes_',
	];

	/**
	 * Apply a filter under its current name and, if renamed, under its legacy name.
	 *
	 * @param string $hook    Current hook name, starting with `tribe_embed_`.
	 * @param mixed  $value   The value to filter.
	 * @param mixed  ...$args Extra arguments passed to the callbacks.
	 */
	public function filter( string $hook, mixed $value, mixed ...$args ): mixed {
		$legacy_hook = $this->get_legacy_hook( $hook );

		if ( $legacy_hook !== null ) {
			$value = $this->filter_deprecated( $legacy_hook, $hook, $value, ...$args );
		}

		return apply_filters( $hook, $value, ...$args );
	}

	/**
	 * Apply a deprecated filter, only when something is attached to it.
	 *
	 * @param string $legacy_hook The deprecated hook name.
	 * @param string $replacement What to use instead, shown in the deprecation notice.
	 * @param mixed  $value       The value to filter.
	 * @param mixed  ...$args     Extra arguments passed to the callbacks.
	 */
	public function filter_deprecated( string $legacy_hook, string $replacement, mixed $value, mixed ...$args ): mixed {
		if ( ! has_filter( $legacy_hook ) ) {
			return $value;
		}

		return apply_filters_deprecated( $legacy_hook, [ $value, ...$args ], self::RENAMED_IN, $replacement );
	}

	private function get_legacy_hook( string $hook ): ?string {
		if ( ! str_starts_with( $hook, self::CURRENT_PREFIX ) ) {
			return null;
		}

		$name = substr( $hook, strlen( self::CURRENT_PREFIX ) );

		if ( in_array( $name, self::RENAMED, true ) ) {
			return self::LEGACY_PREFIX . $name;
		}

		foreach ( self::RENAMED_PREFIXES as $prefix ) {
			if ( str_starts_with( $name, $prefix ) ) {
				return self::LEGACY_PREFIX . $name;
			}
		}

		return null;
	}

}
