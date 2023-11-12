<?php declare(strict_types=1);

namespace Tribe\Tribe_Embed\Providers;

abstract class Provider {

	public const BASE_URL      = '';
	public const IMAGE_SIZES   = [];
	public const ALLOWED_HOSTS = [];

	protected array $video_url;
	protected string $video_id;

	protected static self $instance;

	abstract public function get_thumbnail_data(): array;

	abstract protected function set_video_id(): string;

	public function __construct( array $video_url = [] ) {
		$this->video_url = $video_url;
		$this->video_id  = $this->set_video_id();
	}

	public static function instance( array $video_url ): self {
		if ( ! isset( self::$instance ) ) {
			self::$instance = new self( $video_url );
		}

		return self::$instance;
	}

	public function get_video_id(): string {
		return $this->video_id ?? '';
	}

}
