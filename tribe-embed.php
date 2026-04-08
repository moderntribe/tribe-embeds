<?php declare(strict_types=1);

/**
 * Plugin Name:       Tribe Embed
 * Plugin URI:        https://github.com/moderntribe/tribe-embed
 * Description:       A Tribe Embed Plugin.
 * Version:           2.0.0
 * Requires at least: 6.3
 * Requires PHP:      8.0
 * Author:            Modern Tribe
 * Author URI:        https://github.com/moderntribe
 * License:           GPL v2 or later
 * License URI:       https://www.gnu.org/licenses/gpl-2.0.html
 * Text Domain:       tribe-embeds
 * Domain Path:       /languages
 * Update URI:        false
*/

use Tribe\Tribe_Embed\Core;

include dirname( __FILE__ ) . '/vendor/autoload.php';

if ( ! defined( 'TRIBE_MP_PATH' ) ) {
	define( 'TRIBE_MP_PATH', trailingslashit( plugin_dir_path( __FILE__ ) ) );
}
if ( ! defined( 'TRIBE_MP_URL' ) ) {
	define( 'TRIBE_MP_URL', plugin_dir_url( __FILE__ ) );
}
if ( ! defined( 'TRIBE_MP_VERSION' ) ) {
	define( 'TRIBE_MP_VERSION', Core::VERSION );
}

register_activation_hook( __FILE__, [ Core::class, 'activate' ] );
register_deactivation_hook( __FILE__, [ Core::class, 'deactivate' ] );

add_action( 'plugins_loaded', static function (): void {
	$core = tribe_embed_core();
	$core->register_hooks();
} );

function tribe_embed_core(): Core {
	return Core::instance();
}

