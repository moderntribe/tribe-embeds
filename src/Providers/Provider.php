<?php declare(strict_types=1);

namespace Tribe\Tribe_Embed\Providers;

abstract class Provider {

	/** Short name used in hook names and cache keys, e.g. `youtube`. */
	public const SLUG          = '';
	public const BASE_URL      = '';
	public const IMAGE_SIZES   = [];
	public const ALLOWED_HOSTS = [];

	// phpcs:ignore SlevomatCodingStandard.TypeHints.PropertyTypeHint.MissingTraversableTypeHintSpecification
	protected array $video_url;
	protected string $video_id;

	/**
	 * Return thumbnail data for the video.
	 *
	 * @return array<string,array{url:string,width:int,height:int}>
	 */
	abstract public function get_thumbnail_data(): array;

	abstract protected function set_video_id(): string;

	public function __construct( array $video_url = [] ) {
		$this->video_url = $video_url;
		$this->video_id  = $this->set_video_id();
	}

	public function get_video_id(): string {
		return $this->video_id;
	}

	/**
	 * Whether this provider uses inline embed (raw HTML inside facade) instead of a template.
	 * When true, Facade_Builder will not wrap the embed in a template element.
	 */
	public function uses_inline_embed(): bool {
		return false;
	}

	/**
	 * Return the ID if it matches the provider's ID format, otherwise an empty string.
	 * Keeps arbitrary input out of API URLs, cache keys and markup.
	 */
	protected function valid_video_id( string $video_id, string $pattern ): string {
		if ( preg_match( $pattern, $video_id ) !== 1 ) {
			return '';
		}

		return $video_id;
	}

}
