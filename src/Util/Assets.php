<?php declare(strict_types=1);

namespace Tribe\Tribe_Embed\Util;

final class Assets {

	private string $plugin_name;

	// @phpstan-ignore-next-line
	private string $version;

	public function __construct( string $plugin_name, string $version ) {
		$this->plugin_name = $plugin_name;
		$this->version     = $version;

		define( 'TRIBE_MP_URL', plugin_dir_url( TRIBE_MP_PATH . $plugin_name ) );
		define( 'TRIBE_MP_VERSION', $version );
	}

	/**
	 * Registers the admin scripts
	 */
	public function register_admin_scripts(): void {
		$asset_file = include TRIBE_MP_PATH . 'dist/editor.asset.php';
		wp_enqueue_script( $this->plugin_name . '-admin', TRIBE_MP_URL . 'dist/editor.js', $asset_file['dependencies'], $asset_file['version'] );
		wp_enqueue_style( $this->plugin_name . '-admin', TRIBE_MP_URL . 'dist/editor.css', $asset_file['version'] );
	}

	/**
	 * Registers the public scripts
	 */
	public function register_public_scripts(): void {
		$asset_file = include TRIBE_MP_PATH . 'dist/index.asset.php';
		wp_enqueue_script( $this->plugin_name . '-public', TRIBE_MP_URL . 'dist/index.js', $asset_file['dependencies'], $asset_file['version'] );
		wp_enqueue_style( $this->plugin_name . '-public', TRIBE_MP_URL . 'dist/style-index.css', $asset_file['version'] );
	}

}
