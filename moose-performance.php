<?php declare(strict_types=1);

/*
Plugin Name:       Moose Performance
Plugin URI:        https://github.com/moderntribe/moose-performance
Description:       A Moose Performance Plugin.
Version:           0.0.0
Requires at least: 6.3
Requires PHP:      8.0
Author:            Modern Tribe
Author URI:        https://github.com/moderntribe
License:           GPL v2 or later
License URI:       https://www.gnu.org/licenses/gpl-2.0.html
Text Domain:       tribe
Domain Path:       /languages
*/

use Tribe\Moose_Performance\Core;

require_once  'vendor/autoload.php';

register_activation_hook( __FILE__, [ Core::class, 'activate' ] );
register_deactivation_hook( __FILE__, [ Core::class, 'deactivate' ] );

add_action( 'plugins_loaded', static function (): void {
	moose_performance_core()->init( __file__ );
} );

function moose_performance_core(): Core {
	return Core::instance();
}
