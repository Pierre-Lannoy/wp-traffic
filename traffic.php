<?php
/**
 * Main plugin file.
 *
 * @package Bootstrap
 * @author  Pierre Lannoy <https://pierre.lannoy.fr/>.
 * @since   1.0.0
 *
 * @wordpress-plugin
 * Plugin Name:       Traffic
 * Plugin URI:        https://perfops.one/traffic
 * Description:       Full featured monitoring & analytics for WordPress APIs.
 * Version:           3.0.0
 * Requires at least: 6.2
 * Requires PHP:      8.1
 * Author:            Pierre Lannoy / PerfOps One
 * Author URI:        https://perfops.one
 * License:           GPLv3
 * License URI:       https://www.gnu.org/licenses/gpl-3.0.html
 * Text Domain:       traffic
 * Domain Path:       /languages
 */

// If this file is called directly, abort.
if ( ! defined( 'WPINC' ) ) {
	die;
}

require_once __DIR__ . '/init.php';
require_once __DIR__ . '/functions.php';
require_once __DIR__ . '/includes/system/class-option.php';
require_once __DIR__ . '/includes/system/class-environment.php';
require_once __DIR__ . '/autoload.php';
require_once __DIR__ . '/includes/libraries/class-libraries.php';
require_once __DIR__ . '/includes/libraries/autoload.php';
require_once __DIR__ . '/includes/features/class-wpcli.php';
require_once __DIR__ . '/includes/features/class-memory.php';

/**
 * The code that runs during plugin activation.
 *
 * @since 1.0.0
 */
function traffic_activate() {
	Traffic\Plugin\Activator::activate();
}

/**
 * The code that runs during plugin deactivation.
 *
 * @since 1.0.0
 */
function traffic_deactivate() {
	Traffic\Plugin\Deactivator::deactivate();
}

/**
 * The code that runs during plugin uninstallation.
 *
 * @since 1.0.0
 */
function traffic_uninstall() {
	Traffic\Plugin\Uninstaller::uninstall();
}

/**
 * Begins execution of the plugin.
 *
 * @since 1.0.0
 */
function traffic_run() {
	\DecaLog\Engine::initPlugin( TRAFFIC_SLUG, TRAFFIC_PRODUCT_NAME, TRAFFIC_VERSION, \Traffic\Plugin\Core::get_base64_logo() );
	$plugin = new Traffic\Plugin\Core();
	$plugin->run();
}

register_activation_hook( __FILE__, 'traffic_activate' );
register_deactivation_hook( __FILE__, 'traffic_deactivate' );
register_uninstall_hook( __FILE__, 'traffic_uninstall' );
traffic_run();
