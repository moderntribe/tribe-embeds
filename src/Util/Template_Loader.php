<?php declare(strict_types=1);

namespace Tribe\Tribe_Embed\Util;

/**
 * Locates and renders view templates.
 *
 * Lookup order (first match wins):
 *  1. Child theme, then parent theme: `{theme}/tribe-embed/{name}.php`
 *  2. Directories added via the `tribe_embed_template_dirs` filter
 *  3. The plugin's own `templates/` directory
 */
final class Template_Loader {

	/** Sub-folder inside a theme that holds overrides. */
	public const THEME_DIR = 'tribe-embed';

	private string $base_path;

	/**
	 * @param string $base_path Absolute path to the plugin templates directory.
	 */
	public function __construct( string $base_path ) {
		$this->base_path = trailingslashit( $base_path );
	}

	/**
	 * Find the file for a template name, honouring theme/plugin overrides.
	 *
	 * @param string $name Template name relative to the templates dir, without extension, e.g. `parts/thumbnail`.
	 *
	 * @return string|null Absolute file path, or null when nothing is found or the name is invalid.
	 */
	public function locate( string $name ): ?string {
		if ( ! $this->is_valid_name( $name ) ) {
			return null;
		}

		$path = $this->find_file( $name . '.php', $name );

		/**
		 * Filter the resolved template file.
		 *
		 * @param string $path Absolute path of the resolved file, empty if none was found.
		 * @param string $name Template name, e.g. `parts/thumbnail`.
		 */
		$path = apply_filters( 'tribe_embed_template_path', $path, $name );

		if ( ! is_string( $path ) || ! is_readable( $path ) ) {
			return null;
		}

		return $path;
	}

	/**
	 * Render a template and return its output.
	 * Templates receive a single `$args` array. Whitespace containing a newline or tab between tags is stripped.
	 *
	 * @param string              $name Template name, e.g. `parts/thumbnail`.
	 * @param array<string,mixed> $args Variables made available to the template as `$args`.
	 */
	public function render( string $name, array $args = [] ): string {
		$path = $this->locate( $name );

		if ( $path === null ) {
			return '';
		}

		ob_start();
		( static function ( string $template_file, array $args ): void {
			include $template_file;
		} )( $path, $args );
		$html = (string) ob_get_clean();

		return trim( (string) preg_replace( '/>\s*[\r\n\t]\s*</', '><', $html ) );
	}

	/**
	 * The first existing file: theme, then extra directories, then the plugin's own templates.
	 */
	private function find_file( string $file, string $name ): string {
		$theme_file = locate_template( self::THEME_DIR . '/' . $file );

		if ( $theme_file !== '' ) {
			return $theme_file;
		}

		/**
		 * Filter the extra directories searched for templates, after the theme and before the plugin default.
		 *
		 * @param string[] $dirs Absolute directory paths, highest priority first.
		 * @param string   $name Template name, e.g. `parts/thumbnail`.
		 */
		$dirs   = (array) apply_filters( 'tribe_embed_template_dirs', [], $name );
		$dirs[] = $this->base_path;

		foreach ( $dirs as $dir ) {
			$candidate = trailingslashit( (string) $dir ) . $file;

			if ( is_readable( $candidate ) ) {
				return $candidate;
			}
		}

		return '';
	}

	/** Only lowercase-ish path segments, no traversal, no extension. */
	private function is_valid_name( string $name ): bool {
		return (bool) preg_match( '#^[a-z0-9_-]+(?:/[a-z0-9_-]+)*$#i', $name );
	}

}
