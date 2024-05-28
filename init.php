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
define( 'TRAFFIC_VERSION', '3.0.0' );
define( 'TRAFFIC_API_VERSION', '2' );
define( 'TRAFFIC_CODENAME', '"-"' );

define( 'TRAFFIC_CDN_AVAILABLE', true );

global $timestart;

if ( ! defined( 'TRAFFIC_INBOUND_CHRONO' ) ) {
	if ( defined( 'POWP_START_TIMESTAMP' ) ) {
		define( 'TRAFFIC_INBOUND_CHRONO', POWP_START_TIMESTAMP );
	} elseif ( isset( $timestart ) && is_numeric( $timestart ) ) {
		define( 'TRAFFIC_INBOUND_CHRONO', $timestart );
	} else {
		define( 'TRAFFIC_INBOUND_CHRONO', microtime( true ) );
	}
}
