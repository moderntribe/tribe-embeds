<?php declare(strict_types=1);

namespace Tribe\Tribe_Embed\Util;

use Tribe\Tribe_Embed\Admin\Credentials;
use Tribe\Tribe_Embed\Providers\Thumbnail_Cache;
use wpdb;

/**
 * Deletes everything the plugin stores. Runs when the plugin is deleted, not when it is deactivated.
 */
final class Data_Cleaner {

	public function __construct( private wpdb $wpdb ) {
	}

	public function delete_all_data(): void {
		if ( ! is_multisite() ) {
			$this->delete_site_data();

			return;
		}

		foreach ( get_sites( [ 'fields' => 'ids', 'number' => 0 ] ) as $site_id ) {
			switch_to_blog( (int) $site_id );
			$this->delete_site_data();
			restore_current_blog();
		}
	}

	private function delete_site_data(): void {
		$options = array_merge(
			[ Credentials::VIMEO_OPTION, Credentials::WISTIA_OPTION ],
			array_values( Credentials::LEGACY_OPTIONS )
		);

		foreach ( $options as $option ) {
			delete_option( $option );
		}

		$this->delete_thumbnail_transients();
	}

	private function delete_thumbnail_transients(): void {
		$name_pattern = $this->wpdb->esc_like( Thumbnail_Cache::KEY_PREFIX ) . '%';

		$this->wpdb->query(
			$this->wpdb->prepare(
				"DELETE FROM {$this->wpdb->options} WHERE option_name LIKE %s OR option_name LIKE %s",
				$this->wpdb->esc_like( '_transient_' ) . $name_pattern,
				$this->wpdb->esc_like( '_transient_timeout_' ) . $name_pattern
			)
		);
	}

}
