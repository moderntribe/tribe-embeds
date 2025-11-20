<?php declare(strict_types=1);

namespace Tribe\Tribe_Embed\Admin;

class Support_Providers {

	public function register(): void {
		add_action( 'init', function (): void {
			$this->add_wistia_support();
		}, 10, 0 );
		add_filter( 'oembed_providers', function( $providers ) {
			// Match subdomains and optional query strings
			$providers['#https?://[^\.]+\.wistia\.com/medias/[a-zA-Z0-9]+(?:\?.*)?$#i'] = [
				'https://fast.wistia.com/oembed',
				true, // regex
			];
			return $providers;
		});
		add_filter( 'the_content', [ $GLOBALS['wp_embed'], 'autoembed' ], 8 );
	}

	protected function add_wistia_support(): void {
		wp_oembed_add_provider(
			'#https?://[^\.]+\.wistia\.com/medias/[a-zA-Z0-9]+(?:\?.*)?$#i',
			'https://fast.wistia.com/oembed',
			true
		);
	}

}
