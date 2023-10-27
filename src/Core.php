<?php declare(strict_types=1);

namespace Tribe\Tribe_Embed;

final class Core {

	public const VERSION     = '0.0.0';
	public const PLUGIN_NAME = 'tribe-embed';

	private static self $instance;

	private function __construct() {
		define( 'TRIBE_MP_PATH', trailingslashit( plugin_dir_path( dirname( __FILE__ ) ) ) );
		define( 'TRIBE_MP_URL', plugin_dir_url( TRIBE_MP_PATH . self::PLUGIN_NAME ) );
		define( 'TRIBE_MP_VERSION', self::VERSION );
	}

	public static function instance(): self {
		if ( ! isset( self::$instance ) ) {
			self::$instance = new self();
		}

		return self::$instance;
	}

	public function init( string $file ): void {
		add_action( 'admin_enqueue_scripts', [ $this, 'register_admin_scripts' ] );
		add_action( 'wp_enqueue_scripts', [ $this, 'register_public_scripts' ] );
	}

	/**
	 * Registers the admin scripts
	 */
	public function register_admin_scripts(): void {
		$asset_file = include  TRIBE_MP_PATH . 'dist/editor.asset.php';
		wp_enqueue_script( self::PLUGIN_NAME . '-admin', TRIBE_MP_URL . 'dist/editor.js', $asset_file['dependencies'], $asset_file['version'] );
		wp_enqueue_style( self::PLUGIN_NAME . '-admin', TRIBE_MP_URL . 'dist/editor.css', $asset_file['version'] );
	}

	/**
	 * Registers the public scripts
	 */
	public function register_public_scripts(): void {
		$asset_file = include  TRIBE_MP_PATH . 'dist/index.asset.php';
		wp_enqueue_script( self::PLUGIN_NAME . '-public', TRIBE_MP_URL . 'dist/index.js', $asset_file['dependencies'], $asset_file['version'] );
		wp_enqueue_style( self::PLUGIN_NAME . '-public', TRIBE_MP_URL . 'dist/style-index.css', $asset_file['version'] );
	}

	/**
	 * Any code you want to run when deactivating the plugin.
	 */
	public static function activate(): void {
		return;
	}

	/**
	 * Any code that you want to run when deactivating the plugin.
	 */
	public static function deactivate(): void {
		return;
	}

	private function __clone() {
	}

}
