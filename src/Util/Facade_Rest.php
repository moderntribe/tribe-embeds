<?php declare(strict_types=1);

namespace Tribe\Tribe_Embed\Util;

use WP_REST_Request;
use WP_REST_Response;

/**
 * REST endpoint the block editor uses to preview an embed as the same facade shown on the front end.
 */
final class Facade_Rest {

	public const REST_NAMESPACE = 'tribe-embed/v1';

	private Block_Filter $block_filter;

	public function __construct( Block_Filter $block_filter ) {
		$this->block_filter = $block_filter;
	}

	public function register_routes(): void {
		register_rest_route( self::REST_NAMESPACE, '/facade', [
			'methods'             => 'GET',
			'callback'            => [ $this, 'get_facade' ],
			'permission_callback' => static fn (): bool => current_user_can( 'edit_posts' ),
			'args'                => [
				'url'      => [
					'type'              => 'string',
					'required'          => true,
					'sanitize_callback' => 'esc_url_raw',
				],
				'provider' => [
					'type'              => 'string',
					'default'           => '',
					'sanitize_callback' => 'sanitize_key',
				],
			],
		] );
	}

	/**
	 * Facade markup for a URL, or an empty string when the URL is not a supported video (the editor then
	 * falls back to the core embed preview).
	 */
	public function get_facade( WP_REST_Request $request ): WP_REST_Response {
		$attrs = [ 'url' => (string) $request->get_param( 'url' ) ];
		$slug  = (string) $request->get_param( 'provider' );

		if ( $slug !== '' ) {
			$attrs['providerNameSlug'] = $slug;
		}

		// Alignment and custom classes are applied by the editor's own block wrapper, so they are not added here.
		$html = $this->block_filter->build_facade( [
			'blockName' => 'core/embed',
			'attrs'     => $attrs,
			'innerHTML' => '',
		] );

		return new WP_REST_Response( [ 'html' => $html ?? '' ] );
	}

}
