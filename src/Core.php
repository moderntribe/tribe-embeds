<?php declare(strict_types=1);

namespace Tribe\Tribe_Embed;

use Tribe\Tribe_Embed\Admin\Credentials;
use Tribe\Tribe_Embed\Admin\Settings_Page;
use Tribe\Tribe_Embed\Admin\Support_Providers;
use Tribe\Tribe_Embed\Providers\Provider_Factory;
use Tribe\Tribe_Embed\Providers\Thumbnail_Cache;
use Tribe\Tribe_Embed\Util\Assets;
use Tribe\Tribe_Embed\Util\Block_Filter;
use Tribe\Tribe_Embed\Util\Data_Cleaner;
use Tribe\Tribe_Embed\Util\Facade_Builder;
use Tribe\Tribe_Embed\Util\Facade_Rest;
use Tribe\Tribe_Embed\Util\Hooks;
use Tribe\Tribe_Embed\Util\Remote_Client;
use Tribe\Tribe_Embed\Util\Template_Loader;
use Tribe\Tribe_Embed\Util\Thumbnail_Service;
use Tribe\Tribe_Embed\Util\Url_Parser;

/**
 * Creates the plugin's objects and registers their WordPress hooks.
 */
final class Core {

	public const VERSION     = '2.1.0';
	public const PLUGIN_NAME = 'tribe-embed';

	public function register_hooks(): void {
		$hooks       = new Hooks();
		$credentials = new Credentials();
		$assets      = new Assets( self::PLUGIN_NAME, self::VERSION );

		$provider_factory = new Provider_Factory(
			$hooks,
			new Remote_Client(),
			new Thumbnail_Cache(),
			$credentials
		);

		$block_filter = new Block_Filter(
			new Url_Parser(),
			new Thumbnail_Service( $provider_factory, $hooks ),
			new Facade_Builder( new Template_Loader( TRIBE_MP_PATH . 'templates/' ), $hooks ),
			$assets,
			$hooks
		);

		add_action( 'enqueue_block_editor_assets', [ $assets, 'register_admin_scripts' ] );
		// Fires for the editor content iframe as well as the front end; the callback only acts in the admin.
		add_action( 'enqueue_block_assets', [ $assets, 'enqueue_editor_content_assets' ] );
		add_action( 'wp_enqueue_scripts', [ $assets, 'register_public_scripts' ] );
		add_action( 'rest_api_init', [ new Facade_Rest( $block_filter ), 'register_routes' ] );

		$block_filter->register_hooks();
		( new Settings_Page( $credentials ) )->register();
		( new Support_Providers() )->register();
	}

	/**
	 * Any code you want to run when activating the plugin.
	 */
	public static function activate(): void {
		return;
	}

	/**
	 * Any code that you want to run when deactivating the plugin.
	 * Deactivating is often temporary, so stored credentials are kept; see uninstall().
	 */
	public static function deactivate(): void {
		return;
	}

	/**
	 * Runs when the plugin is deleted: removes the stored credentials and cached thumbnails.
	 */
	public static function uninstall(): void {
		global $wpdb;

		( new Data_Cleaner( $wpdb ) )->delete_all_data();
	}

}
