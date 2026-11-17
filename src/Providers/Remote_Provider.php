<?php declare(strict_types=1);

namespace Tribe\Tribe_Embed\Providers;

use Tribe\Tribe_Embed\Util\Remote_Client;

/**
 * A provider whose thumbnails are fetched from a remote service and cached.
 */
abstract class Remote_Provider extends Provider {

	/** Name of the filter applied to the thumbnail data. */
	public const THUMBNAIL_FILTER = '';

	/**
	 * Ask the remote service for the thumbnails.
	 *
	 * @return array<string,array{url:string,width:int,height:int}> Empty when they cannot be fetched.
	 */
	abstract protected function fetch_thumbnail_data(): array;

	public function __construct(
		array $video_url,
		protected Remote_Client $remote,
		protected Thumbnail_Cache $cache
	) {
		parent::__construct( $video_url );
	}

	public function get_thumbnail_data(): array {
		if ( $this->video_id === '' || ! $this->is_configured() ) {
			return [];
		}

		$thumbnails = $this->cache->get( static::SLUG, $this->video_id );

		if ( $thumbnails === null ) {
			$thumbnails = $this->fetch_thumbnail_data();
			$this->cache->set( static::SLUG, $this->video_id, $thumbnails );
		}

		return apply_filters( static::THUMBNAIL_FILTER, $thumbnails, $this->video_id );
	}

	/**
	 * Whether the provider has what it needs (for example API credentials) to ask for thumbnails.
	 */
	protected function is_configured(): bool {
		return true;
	}

}
