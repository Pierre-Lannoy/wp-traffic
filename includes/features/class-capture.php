<?php
/**
 * Traffic capture
 *
 * Handles all captures operations.
 *
 * @package Features
 * @author  Pierre Lannoy <https://pierre.lannoy.fr/>.
 * @since   1.0.0
 */

namespace Traffic\Plugin\Feature;

use Traffic\System\Logger;

/**
 * Define the captures functionality.
 *
 * Handles all captures operations.
 *
 * @package Features
 * @author  Pierre Lannoy <https://pierre.lannoy.fr/>.
 * @since   1.0.0
 */
class Capture {

	/**
	 * Start times.
	 *
	 * @since  1.0.0
	 * @var    array    $chrono    The start times.
	 */
	private static $chrono = [];

	/**
	 * Default start times.
	 *
	 * @since  1.0.0
	 * @var    float    $default_chrono    The default start times.
	 */
	private static $default_chrono = null;

	/**
	 * Initialize the class and set its properties.
	 *
	 * @since    1.0.0
	 */
	public function __construct() {
	}

	/**
	 * Initialize static properties and hooks.
	 *
	 * @since    1.0.0
	 */
	public static function init() {
		add_filter( 'pre_http_request', [ 'Traffic\Plugin\Feature\Capture', 'pre_http_request' ], 10, 3 );
		add_filter( 'http_api_debug', [ 'Traffic\Plugin\Feature\Capture', 'http_api_debug' ], 10, 5 );
		self::$default_chrono = microtime( true );
	}

	/**
	 * Filters whether to preempt an HTTP request's return value.
	 *
	 * Returning a non-false value from the filter will short-circuit the HTTP request and return
	 * early with that value. A filter should return either:
	 *
	 *  - An array containing 'headers', 'body', 'response', 'cookies', and 'filename' elements
	 *  - A WP_Error instance
	 *  - boolean false (to avoid short-circuiting the response)
	 *
	 * Returning any other value may result in unexpected behaviour.
	 *
	 * @param false|array|WP_Error $preempt Whether to preempt an HTTP request's return value. Default false.
	 * @param array                $args     HTTP request arguments.
	 * @param string               $url      The request URL.
	 * @return  FALSE|array|object FALSE if everything is okay, an array of request
	 *                             results or an WP_Error instance.
	 * @since    1.0.0
	 */
	public static function pre_http_request( $preempt, $args, $url ) {
		self::start( $url, $args );
		return false;
	}

	/**
	 * Fires after an HTTP API response is received and before the response is returned.
	 *
	 * @param array|WP_Error $response HTTP response or WP_Error object.
	 * @param string         $context  Context under which the hook is fired.
	 * @param string         $class    HTTP transport used.
	 * @param array          $args     HTTP request arguments.
	 * @param string         $url      The request URL.
	 * @since    1.0.0
	 */
	public static function http_api_debug( $response, $context, $class, $args, $url ) {

		$arrURL = @parse_url( $url );

		Logger::emergency($arrURL['host']);
	}

	/**
	 * Get an "unique" id.
	 *
	 * @param   string $url    The request URL.
	 * @param   array  $args   HTTP request arguments.
	 * @return  string  The id corresponding to url and args.
	 * @since    1.0.0
	 */
	private static function get_id( $url, $args ) {
		// phpcs:ignore
		return md5( $url . serialize( $args ) );
	}

	/**
	 * Starts the chrono.
	 *
	 * @param   string $url    The request URL.
	 * @param   array  $args   HTTP request arguments.
	 * @since    1.0.0
	 */
	public static function start( $url, $args ) {
		self::$chrono[ self::get_id( $url, $args ) ] = microtime( true );
	}

}
