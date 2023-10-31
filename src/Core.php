<?php declare(strict_types=1);

namespace Tribe\Tribe_Embed;

use WP_Block;

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

		// create a default video id, url and thumbnail url.
		$video_id       = '';
		$thumbnail_data = [];
		$image_data     = [];

		// grab the video id.
		$video_url        = $block['attrs']['url'];
		$parsed_video_url = parse_url( $video_url );

		// switch based on the host.
		switch ( $parsed_video_url['host'] ) {
			// for standard youtube URLs
			case 'www.youtube.com':
			case 'youtube.com':
				// parse the query part of the URL into its arguments.
				parse_str( $parsed_video_url['query'], $video_url_query_args );

				// if we cannot find a youtube video id.
				if ( empty( $video_url_query_args['v'] ) ) {
					return $block_content;
				}

				// set the video id to the v query arg.
				$video_id = $video_url_query_args['v'];

				// get the youtube thumbnail url.
				$thumbnail_data = $this->get_youtube_thumbnail_data( $video_id );
				// $image_data     = [
				// 	'mq',
				// ]

				// break out the switch.
				break;

			// for youtube short urls.
			case 'youtu.be':
				// if we have a path.
				if ( empty( $parsed_video_url['path'] ) ) {
					return $block_content;
				}

				// remove the preceeding slash.
				$video_id = str_replace( '/', '', $parsed_video_url['path'] );

				// get the youtube thumbnail url.
				$thumbnail_data = $this->get_youtube_thumbnail_data( $video_id );

				// break out the switch.
				break;

			// // for vimeo urls.
			// case 'vimeo.com':
			// case 'www.vimeo.com':
			// 	// if we have a path.
			// 	if ( empty( $parsed_video_url['path'] ) ) {
			// 		return $block_content;
			// 	}

			// 	// remove the preceeding slash.
			// 	$video_id = str_replace( '/', '', $parsed_video_url['path'] );

			// 	// get the vimeo thumbnail url for this video.
			// 	$thumbnail_url = $this->get_vimeo_video_thumbnail_url( $video_id );

			// 	// break out the switch.
			// 	break;

			// // for vimeo urls.
			// case 'www.dailymotion.com':
			// case 'dailymotion.com':
			// 	// if we have a path.
			// 	if ( empty( $parsed_video_url['path'] ) ) {
			// 		return $block_content;
			// 	}

			// 	// remove the preceeding slash.
			// 	$video_id = str_replace( '/video/', '', $parsed_video_url['path'] );

			// 	// get the vimeo thumbnail url for this video.
			// 	$thumbnail_url = $this->get_dailymotion_video_thumbnail_url( $video_id );

			// 	// break out the switch.
			// 	break;
		}

		// if we don't have a video id.
		if ( '' === $video_id ) {
			return $block_content;
		}

		// if we don't have a video thumbnail url.
		if ( count( $thumbnail_data ) === 0 ) {
			return $block_content;
		}

		// create an array of classes to add to the placeholder image wrapper.
		$wrapper_classes = [
			'wp-block-image',
			'tribe-embed__wrapper',
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
		wp_print_styles( 'better-core-video-embeds-styles' );

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
	public function allowed_innerblock_html() {
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
	 * @param string $thumbnail_data  The URL of the video thumbnail.
	 * @param array  $wrapper_classes An array of CSS classes to add to the wrapper.
	 */
	public function open_markup_figure_element( array $block, string $video_id, array $thumbnail_data, array $wrapper_classes ): void {

		?>
<figure class="<?php echo esc_attr( implode( ' ', $wrapper_classes ) ); ?>"
	data-id="<?php echo esc_attr( $video_id ); ?>">
		<?php
	}

	/**
	 * Adds the play button div to the markup.
	 *
	 * @param array  $block           The block array.
	 * @param string $video_id        The ID of the embedded video.
	 * @param string $thumbnail_data  The URL of the video thumbnail.
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
	 * @param string $thumbnail_data  The URL of the video thumbnail.
	 * @param array  $wrapper_classes An array of CSS classes to add to the wrapper.
	 */
	public function add_video_thumbnail_markup( array $block, string $video_id, array $thumbnail_data, array $wrapper_classes ): void {

		?>
	<img loading="lazy" width=<?php echo $thumbnail_data['maxresdefault']['width']; ?>
		height=<?php echo $thumbnail_data['maxresdefault']['height']; ?> class="tribe-embed__thumbnail" alt=""
		src="<?php echo $thumbnail_data['maxresdefault']['url']; ?>" />
		<?php
	}

	/**
	 * Adds the closing figure element to the thumbnail markup.
	 *
	 * @param array  $block           The block array.
	 * @param string $video_id        The ID of the embedded video.
	 * @param string $thumbnail_data  The URL of the video thumbnail.
	 * @param array  $wrapper_classes An array of CSS classes to add to the wrapper.
	 */
	public function close_markup_figure_element( array $block, string $video_id, array $thumbnail_data, array $wrapper_classes ): void {

		?>
</figure>
		<?php
	}

	/**
	 * Adds the original block markup to the template element.
	 * This is used when the item is cloned when the thumbnail is clicked.
	 *
	 * @param array  $block           The block array.
	 * @param string $video_id        The ID of the embedded video.
	 * @param string $thumbnail_data  The URL of the video thumbnail.
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
	 * Accepts a video id and returns an array of thumbnail data
	 */
	private function get_youtube_thumbnail_data( string $video_id = '' ): array {

		// if we have no video id.
		if ( '' === $video_id ) {
			return '';
		}

		// get the URL from the transient.
		// $image_urls = get_transient( 'tribe-embed_' . $video_id );
		$image_data = false;

		// if we don't have a transient.
		if ( false === $image_data ) {
			// Initialize image data
			$image_data = [
				'mqdefault'     => [],
				'hqdefault'     => [],
				'sddefault'     => [],
				'maxresdefault' => [],
			];

			foreach ( $image_data as $path => $url ) {
				$location  = 'https://img.youtube.com/vi/' . esc_attr( $video_id ) . '/' . $path . '.jpg';
				$image_url = wp_remote_get( $location );

				// if the request to the image doesn't error and returns a http 200 response code.
				if ( ( is_wp_error( $image_url ) ) || ( 200 !== wp_remote_retrieve_response_code( $image_url ) ) ) {
					continue;
				}

				$image_size = getimagesize( $location );
				$width      = $image_size[0];
				$height     = $image_size[1];

				// set the image data
				$image_data[ $path ] = [
					'url'    => $location,
					'width'  => $width,
					'height' => $height,
				];
			}

			// set the transient, storing the image url.
			set_transient( 'tribe-embed_' . $video_id, $image_data, DAY_IN_SECONDS );
		}

		// return the thumbnail urls.
		return apply_filters( 'tribe-embed_youtube_video_thumbnail_data', $image_data, $video_id );
	}


	/**
	 * TODO: Fetch all image sizes
	 * Return the vimeo video thumbnail url.
	 *
	 * @param string  $video_id The ID of the video.
	 *
	 * @return string $url      The URL of the thumbnail or an empty string if no URL found.
	 */
	private function get_vimeo_video_thumbnail_url( string $video_id = '' ): string {

		// if we have no video id.
		if ( '' === $video_id ) {
			return '';
		}

		// get the URL from the transient.
		$image_url = get_transient( 'tribe-embed_' . $video_id );

		// if we don't have a transient.
		if ( false === $image_url ) {
			// get the video details from the api.
			$video_details = wp_remote_get(
				'https://vimeo.com/api/v2/video/' . esc_attr( $video_id ) . '.json'
			);

			// if the request to the hi res image errors or returns anything other than a http 200 response code.
			if ( ( is_wp_error( $video_details )) && ( 200 !== wp_remote_retrieve_response_code( $video_details ) ) ) {
				return '';
			}

			// grab the body of the response.
			$video_details = json_decode(
				wp_remote_retrieve_body(
					$video_details
				)
			);

			// get the image url from the json.
			$image_url = $video_details[0]->thumbnail_large;

			// set the transient, storing the image url.
			set_transient( 'tribe-embed_' . $video_id, $image_url, DAY_IN_SECONDS );
		}

		// return the url.
		return apply_filters( 'tribe-embed_vimeo_video_thumbnail_url', $image_url, $video_id );
	}


	/**
	 * TODO: Fetch all image sizes
	 * Return the dailymotion video thumbnail url.
	 *
	 * @param string  $video_id The ID of the video.
	 *
	 * @return string $url      The URL of the thumbnail or an empty string if no URL found.
	 */
	private function get_dailymotion_video_thumbnail_url( string $video_id = '' ): string {

		// if we have no video id.
		if ( '' === $video_id ) {
			return '';
		}

		// get the URL from the transient.
		$image_url = get_transient( 'tribe-embed_' . $video_id );

		// if we don't have a transient.
		if ( false === $image_url ) {
			// get the video details from the api.
			$video_details = wp_remote_get( 'https://api.dailymotion.com/video/' . $video_id . '?fields=thumbnail_url' );

			// if the request to the hi res image errors or returns anything other than a http 200 response code.
			if ( ( is_wp_error( $video_details )) && ( 200 !== wp_remote_retrieve_response_code( $video_details ) ) ) {
				return '';
			}

			// grab the body of the response.
			$video_details = json_decode(
				wp_remote_retrieve_body(
					$video_details
				)
			);

			// get the image url from the json.
			$image_url = $video_details->thumbnail_url;

			// set the transient, storing the image url.
			set_transient( 'tribe-embed_' . $video_id, $image_url, DAY_IN_SECONDS );
		}

		// return the url.
		return apply_filters( 'tribe-embed_dailymotion_video_thumbnail_url', $image_url, $video_id );
	}



	private function __clone() {
	}/* Return the youtube video thumbnail url.
	*
	* @param string  $video_id The ID of the video.
	* @return string $url      The URL of the thumbnail or an empty string if no URL found.
	*/

}
