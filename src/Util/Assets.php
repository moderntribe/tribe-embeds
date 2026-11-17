<?php declare(strict_types=1);

namespace Tribe\Tribe_Embed\Util;

final class Assets {

	private string $plugin_name;

	private string $version;

	public function __construct( string $plugin_name, string $version ) {
		$this->plugin_name = $plugin_name;
		$this->version     = $version;
	}

	/**
	 * Handle shared by the public script and style.
	 */
	public function public_handle(): string {
		return $this->plugin_name . '-public';
	}

	/**
	 * Registers the editor script, only where the block editor loads.
	 */
	public function register_admin_scripts(): void {
		$asset_file = $this->read_asset_file( 'editor' );

		if ( $asset_file === null ) {
			return;
		}

		wp_enqueue_script(
			$this->plugin_name . '-admin',
			TRIBE_MP_URL . 'dist/editor.js',
			$asset_file['dependencies'],
			$asset_file['version'],
			true
		);
	}

	/**
	 * Registers (does not enqueue) the public script and style.
	 * They are enqueued by enqueue_public_assets() when a facade is rendered.
	 */
	public function register_public_scripts(): void {
		$asset_file = $this->read_asset_file( 'index' );

		if ( $asset_file === null ) {
			return;
		}

		wp_register_script(
			$this->public_handle(),
			TRIBE_MP_URL . 'dist/index.js',
			$asset_file['dependencies'],
			$asset_file['version'],
			true
		);

		wp_register_style(
			$this->public_handle(),
			TRIBE_MP_URL . 'dist/style-index.css',
			[],
			$asset_file['version']
		);
	}

	/**
	 * Styles for blocks inside the editor content area (an iframe, so styles must come through `enqueue_block_assets`):
	 * the front-end facade styles, plus editor-only rules.
	 */
	public function enqueue_editor_content_assets(): void {
		if ( ! is_admin() ) {
			return;
		}

		if ( ! wp_style_is( $this->public_handle(), 'registered' ) ) {
			$this->register_public_scripts();
		}

		if ( wp_style_is( $this->public_handle(), 'registered' ) ) {
			wp_enqueue_style( $this->public_handle() );
		}

		$asset_file = $this->read_asset_file( 'editor' );

		if ( $asset_file === null ) {
			return;
		}

		wp_enqueue_style( $this->plugin_name . '-editor-content', TRIBE_MP_URL . 'dist/editor.css', [ $this->public_handle() ], $asset_file['version'] );
	}

	/**
	 * Enqueue the public script and style, registering them first when the hook did not (e.g. REST or late renders).
	 */
	public function enqueue_public_assets(): void {
		if ( ! wp_script_is( $this->public_handle(), 'registered' ) ) {
			$this->register_public_scripts();
		}

		if ( wp_script_is( $this->public_handle(), 'registered' ) ) {
			wp_enqueue_script( $this->public_handle() );
		}

		if ( ! wp_style_is( $this->public_handle(), 'registered' ) ) {
			return;
		}

		wp_enqueue_style( $this->public_handle() );
	}

	/**
	 * Print the public stylesheet right away (once), so the facade is styled before the footer.
	 */
	public function print_public_styles(): void {
		if ( ! wp_style_is( $this->public_handle(), 'enqueued' ) ) {
			return;
		}

		wp_print_styles( $this->public_handle() );
	}

	/**
	 * @return array{dependencies:array<int,string>,version:string}|null Null when the build is missing.
	 */
	private function read_asset_file( string $entry ): ?array {
		$path = TRIBE_MP_PATH . 'dist/' . $entry . '.asset.php';

		if ( ! is_readable( $path ) ) {
			return null;
		}

		$asset_file = include $path;

		if ( ! is_array( $asset_file ) ) {
			return null;
		}

		return [
			'dependencies' => (array) ( $asset_file['dependencies'] ?? [] ),
			'version'      => (string) ( $asset_file['version'] ?? $this->version ),
		];
	}

}
