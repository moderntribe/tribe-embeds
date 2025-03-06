<?php declare(strict_types=1);

namespace Tribe\Tribe_Embed;

use Tribe\Tribe_Embed\Providers\Dailymotion;
use Tribe\Tribe_Embed\Providers\Vimeo;
use Tribe\Tribe_Embed\Providers\YouTube;
use WP_Block;

final class Core {

	public const VERSION     = '1.0.3';
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

		add_filter( 'render_block_core/embed', [ $this, 'filter_embed_block' ], 10, 3 );

		add_action( 'video_thumbnail_markup', [ $this, 'open_markup_figure_element' ], 10, 4 );
		add_action( 'video_thumbnail_markup', [ $this, 'add_video_play_button' ], 20, 4 );
		add_action( 'video_thumbnail_markup', [ $this, 'add_video_thumbnail_markup' ], 30, 4 );
		add_action( 'video_thumbnail_markup', [ $this, 'close_markup_figure_element' ], 40, 4 );
		add_action( 'video_thumbnail_markup', [ $this, 'add_original_embed_template' ], 50, 4 );
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
	 * Filters the code embed block output for improved performance on Youtube videos.
	 *
	 * @param string   $block_content The block content.
	 * @param array    $block         The full block, including name and attributes.
	 * @param \Tribe\Tribe_Embed\WP_Block $instance      The block instance.
	 *
	 * @return string  $block_content The block content.
	 */
	public function filter_embed_block( string $block_content, array $block, WP_Block $instance ): string {

		// if the provider slug name is empty.
		if ( empty( $block['attrs']['providerNameSlug'] ) ) {
			return $block_content;
		}

		// if for some reason there is no embed URL.
		if ( empty( $block['attrs']['url'] ) ) {
			return $block_content;
		}

		// setup some base variables and get the video url
		$provider         = null;
		$thumbnail_data   = [];
		$parsed_video_url = parse_url( $block['attrs']['url'] );

		// Only continue for allowed providers
		if ( ! $this->is_allowed_host( $parsed_video_url['host'] ) ) {
			return $block_content;
		}

		// switch based on the host.
		switch ( $parsed_video_url['host'] ) {
			// for youtube urls
			case in_array( $parsed_video_url['host'], YouTube::ALLOWED_HOSTS ):
				$provider = new YouTube( $parsed_video_url );
				break;

			// for vimeo urls.
			case in_array( $parsed_video_url['host'], Vimeo::ALLOWED_HOSTS ):
				$provider = new Vimeo( $parsed_video_url );
				break;

			// for dailymotion urls.
			case in_array( $parsed_video_url['host'], Dailymotion::ALLOWED_HOSTS ):
				$provider = new Dailymotion( $parsed_video_url );
				break;

			default:
				/**
				 * Returns Custom Provider class object
				 *
				 * @var mixed|null $provider  Provider object
				 * @var array $video_url_data Video url parsed with parse_url
				 * @var array $block          The full block, including name and attributes.
				 */
				$provider = apply_filters( 'tribe-embeds_video_provider', null, $parsed_video_url, $block );
				break;
		}

		// Bail if empty/wrong provider is provided
		if ( empty( $provider ) ) {
			return $block_content;
		}

		// get thumbnail data.
		$video_id       = $provider->get_video_id();
		$thumbnail_data = $provider->get_thumbnail_data();

		// if we don't have any video thumbnails.
		if ( count( $thumbnail_data ) === 0 ) {
			return $block_content;
		}

		// create an array of classes to add to the placeholder image wrapper.
		$wrapper_classes = [
			'wp-block-image',
			'tribe-embed',
			'is--' . $block['attrs']['providerNameSlug'],
		];

		// if we have classNames on the embed block.
		if ( ! empty( $block['attrs']['className'] ) ) {
			// explode the className string into array.
			$class_names = explode( ' ', $block['attrs']['className'] );

			// merge the class names into the figures classes array.
			$wrapper_classes = array_merge( $wrapper_classes, $class_names );
		}

		// if the embed block has an alignment.
		if ( ! empty( $block['attrs']['align'] ) ) {
			// add the alignment class to the figure classes.
			$wrapper_classes[] = 'align' . $block['attrs']['align'];
		}

		// allow the classes to be filtered.
		$wrapper_classes = apply_filters( '', $wrapper_classes, $block, $video_id, $thumbnail_data );

		// buffer the output as we need to return not echo.
		ob_start();

		// output the registered "block" styles for the thubmnail.
		wp_print_styles( 'tribe-embeds-styles' );

		/**
		 * Fires and action to which the new block markup is added too.
		 *
		 * @hooked open_markup_figure_element - 10
		 * @hooked add_video_play_button - 20
		 * @hooked add_video_thumbnail_markup - 30
		 * @hooked hd_bvce_close_markup_figure_element - 40
		 * @hooked add_original_embed_template - 50
		 */
		do_action( 'video_thumbnail_markup', $block, $video_id, $thumbnail_data, $wrapper_classes );

		// return the new block markup.
		return ob_get_clean();
	}

	/**
	 * Creates a escaping function to allowed certain HTML for embed content.
	 * Needed for when echoing the innerblock HTML.
	 *
	 * @param array An array of HTML elements allowed.
	 */
	public function allowed_innerblock_html(): array {
		/**
		 * Return the allowed html
		 * These are the elements in the rendered embed block for supported videos.
		 * This also includes everything you can add to an embed caption.
		 * Therefore we need to allow these to keep the same structure.
		 */
		return [
			'iframe'     => [
				'src'             => true,
				'height'          => true,
				'width'           => true,
				'frameborder'     => true,
				'allowfullscreen' => true,
			],
			'figure'     => [
				'class' => true,
			],
			'figcaption' => [
				'class' => true,
			],
			'div'        => [
				'class' => true,
			],
			'a'          => [
				'class'     => true,
				'href'      => true,
				'data-type' => true,
			],
			'strong'     => [],
			'em'         => [],
			'sub'        => [],
			'sup'        => [],
			's'          => [],
			'kbd'        => [],
			'img'        => [
				'class' => true,
				'style' => true,
				'src'   => true,
				'alt'   => true,
			],
			'code'       => [],
			'mark'       => [
				'style' => true,
				'class' => true,
			],
		];
	}

	/**
	 * Adds the opening figure element to the thumbnail markup.
	 *
	 * @param array  $block           The block array.
	 * @param string $video_id        The ID of the embedded video.
	 * @param array $thumbnail_data  The URL of the video thumbnail.
	 * @param array  $wrapper_classes An array of CSS classes to add to the wrapper.
	 */
	public function open_markup_figure_element( array $block, string $video_id, array $thumbnail_data, array $wrapper_classes ): void {

		?>
<figure class="<?php echo esc_attr( implode( ' ', $wrapper_classes ) ); ?>"
	data-id="<?php echo esc_attr( $video_id ); ?>">
	<div class="tribe-embed__inner">
		<?php
	}

	/**
	 * Adds the play button div to the markup.
	 *
	 * @param array  $block           The block array.
	 * @param string $video_id        The ID of the embedded video.
	 * @param array $thumbnail_data  The URL of the video thumbnail.
	 * @param array  $wrapper_classes An array of CSS classes to add to the wrapper.
	 */
	public function add_video_play_button( array $block, string $video_id, array $thumbnail_data, array $wrapper_classes ): void {

		?>
	<button class="play-button" aria-label="<?php echo __( 'Play Video', 'tribe' ) ?>"></button>
		<?php
	}

	/**
	 * Adds the video thumbnail markup output.
	 *
	 * @param array  $block           The block array.
	 * @param string $video_id        The ID of the embedded video.
	 * @param array $thumbnail_data  The URL of the video thumbnail.
	 * @param array  $wrapper_classes An array of CSS classes to add to the wrapper.
	 */
	public function add_video_thumbnail_markup( array $block, string $video_id, array $thumbnail_data, array $wrapper_classes ): void {

		$max_res_image = end( $thumbnail_data );
		$srcset        = [];
		$sizes         = [ '(max-width: ' . $max_res_image['width'] . 'px) 100vw', $max_res_image['width'] . 'px' ];

		foreach ( $thumbnail_data as $data ) {
			$srcset[] = $data['url'] . ' ' . $data['width'] . 'w';
		}

		?>
	<img loading="lazy" width=<?php echo $max_res_image['width']; ?> height=<?php echo $max_res_image['height']; ?>
		class="tribe-embed__thumbnail" alt="" src="<?php echo $max_res_image['url']; ?>"
		srcset="<?php echo implode( ',', $srcset ) ?>" sizes="<?php echo implode( ',', $sizes ) ?>" />
		<?php
	}

	/**
	 * Adds the closing figure element to the thumbnail markup.
	 *
	 * @param array  $block           The block array.
	 * @param string $video_id        The ID of the embedded video.
	 * @param array $thumbnail_data  The URL of the video thumbnail.
	 * @param array  $wrapper_classes An array of CSS classes to add to the wrapper.
	 */
	public function close_markup_figure_element( array $block, string $video_id, array $thumbnail_data, array $wrapper_classes ): void {

		?>
	</div>
</figure>
		<?php
	}

	/**
	 * Adds the original block markup to the template element.
	 * This is used when the item is cloned when the thumbnail is clicked.
	 *
	 * @param array  $block           The block array.
	 * @param string $video_id        The ID of the embedded video.
	 * @param array $thumbnail_data  The URL of the video thumbnail.
	 * @param array  $wrapper_classes An array of CSS classes to add to the wrapper.
	 */
	public function add_original_embed_template( array $block, string $video_id, array $thumbnail_data, array $wrapper_classes ): void {

		?>
<template id=tribe-embed-embed-html-<?php echo esc_attr( $video_id ); ?>">
		<?php echo wp_kses( $block['innerHTML'], $this->allowed_innerblock_html() ); ?>
</template>
		<?php
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

	/**
	 * @param string $host
	 * @param array  $allowed_hosts
	 */
	private function is_allowed_host( string $host, array $allowed_hosts = [] ): bool {
		// Use default list of allowed hosts if nothing has been specified
		if ( empty( $allowed_hosts ) ) {
			$allowed_hosts = array_merge( YouTube::ALLOWED_HOSTS, Vimeo::ALLOWED_HOSTS, Dailymotion::ALLOWED_HOSTS );
		}

		/**
		 * Allows to inject custom provider hosts
		 * @var array $allowed_hosts List of allowed hosts
		 * @var string $host         Current video hostname
		 */
		$allowed_hosts = apply_filters( 'tribe-embeds_allowed_provider_hosts', $allowed_hosts, $host );

		if ( in_array( $host, $allowed_hosts ) ) {
			return true;
		}

		/**
		 * Check if requested host is in the list of allowed ones. This is a wildcard check with regex
		 * and it is specifically handles problem with provider dynamic urls
		 *
		 * Example:
		 * Wistia video embed: https://tri-4.wistia.com/medias/7c1s0ftfl3?embedType=web_component&seo=true&videoWidth=960
		 * The `tri-4` part may vary and be different for different videos or/and accounts
		 */
		$allowed_hosts = array_filter( $allowed_hosts, static function ( $allowed ) use ( $host ) {
			return (bool) preg_match( "/$allowed/i", $host );
		} );

		return ! empty( $allowed_hosts );
	}

	private function __clone() {
	}

}
