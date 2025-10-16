<?php declare(strict_types=1);

namespace Tribe\Tribe_Embed;

use Tribe\Tribe_Embed\Admin\Settings_Page;
use Tribe\Tribe_Embed\Providers\Provider_Factory;
use Tribe\Tribe_Embed\Util\Assets;
use Tribe\Tribe_Embed\Util\Block_Filter;
use Tribe\Tribe_Embed\Util\Facade_Builder;
use Tribe\Tribe_Embed\Util\Thumbnail_Service;
use Tribe\Tribe_Embed\Util\Url_Parser;

/**
 * Builds and shares single service instances.
 * Delegates hook registration to Block_Filter.
 */
final class Core {

	public const VERSION     = '1.1.1';
	public const PLUGIN_NAME = 'tribe-embed';

	private Provider_Factory|null $factory  = null;
	private Url_Parser|null $url_parser     = null;
	private Thumbnail_Service|null $thumbs  = null;
	private Facade_Builder|null $facade     = null;
	private Block_Filter|null $block_filter = null;

	/** @var self|null Singleton instance */
	private static ?self $instance = null;

	private function __construct() {
		define( 'TRIBE_MP_PATH', trailingslashit( plugin_dir_path( dirname( __FILE__ ) ) ) );
	}

	/** Get Core singleton */
	public static function instance(): self {
		if ( ! self::$instance instanceof self ) {
			self::$instance = new self();
		}

		return self::$instance;
	}

	/** Register WP hooks via Block_Filter */
	public function register_hooks(): void {
		$assets = new Assets( self::PLUGIN_NAME, self::VERSION );

		add_action( 'admin_enqueue_scripts', [ $assets, 'register_admin_scripts' ] );
		add_action( 'wp_enqueue_scripts', [ $assets, 'register_public_scripts' ] );
		add_action( 'init', [ $this, 'register_settings' ] );

		$this->block_filter()->register_hooks();
	}

	public function register_settings(): void {
		( new Settings_Page() );
	}

	/** Get Provider_Factory */
	public function provider_factory(): Provider_Factory {
		if ( ! $this->factory instanceof Provider_Factory ) {
			$this->factory = new Provider_Factory();
		}

		return $this->factory;
	}

	/** Get Url_Parser */
	public function url_parser(): Url_Parser {
		if ( ! $this->url_parser instanceof Url_Parser ) {
			$this->url_parser = new Url_Parser();
		}

		return $this->url_parser;
	}

	/** Get Thumbnail_Service */
	public function thumbnail_service(): Thumbnail_Service {
		if ( ! $this->thumbs instanceof Thumbnail_Service ) {
			$this->thumbs = new Thumbnail_Service( $this->provider_factory() );
		}

		return $this->thumbs;
	}

	/** Get Facade_Builder */
	public function facade_builder(): Facade_Builder {
		if ( ! $this->facade instanceof Facade_Builder ) {
			$this->facade = new Facade_Builder();
		}

		return $this->facade;
	}

	/** Get Block_Filter */
	public function block_filter(): Block_Filter {
		if ( ! $this->block_filter instanceof Block_Filter ) {
			$this->block_filter = new Block_Filter(
				$this->url_parser(),
				$this->provider_factory(),
				$this->thumbnail_service(),
				$this->facade_builder()
			);
		}

		return $this->block_filter;
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

}
