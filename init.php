<?php
/**
 * Initialization of globals.
 *
 * @package Bootstrap
 * @author  Pierre Lannoy <https://pierre.lannoy.fr/>.
 * @since   1.0.0
 */

define( 'TRAFFIC_PRODUCT_NAME', 'Traffic' );
define( 'TRAFFIC_PRODUCT_URL', 'https://github.com/Pierre-Lannoy/wp-traffic' );
define( 'TRAFFIC_PRODUCT_SHORTNAME', 'Traffic' );
define( 'TRAFFIC_PRODUCT_ABBREVIATION', 'traffic' );
define( 'TRAFFIC_SLUG', 'traffic' );
define( 'TRAFFIC_VERSION', '1.7.0' );
define( 'TRAFFIC_CODENAME', '"-"' );

define( 'TRAFFIC_CDN_AVAILABLE', true );

if ( ! defined( 'TRAFFIC_INBOUND_CHRONO' ) ) {
	define( 'TRAFFIC_INBOUND_CHRONO', microtime( true ) );
}
