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
use Traffic\Plugin\Feature\Schema;
use Traffic\System\Timezone;
use Traffic\System\Http;
use function GuzzleHttp\Psr7\str;

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
	 * Local time zone.
	 *
	 * @since  1.0.0
	 * @var    \Traffic\System\Timezone    $local_timezone    The local timezone.
	 */
	private static $local_timezone = null;

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
		add_filter( 'rest_pre_echo_response', [ 'Traffic\Plugin\Feature\Capture', 'rest_pre_echo_response' ], 10, 3 );
		self::$default_chrono = microtime( true );
		self::$local_timezone = Timezone::network_get();
		Logger::debug( 'Capture engine started.' );
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
		self::outbound_start( $url, $args );
		return false;
	}

	/**
	 * Records an entry.
	 *
	 * @param array|WP_Error $response HTTP response or WP_Error object.
	 * @param array          $args     HTTP request arguments.
	 * @param string         $url      The request URL.
	 * @since    1.0.0
	 */
	private static function record( $response, $args, $url, $bound = 'unknown' ) {
		try {
			$url_parts           = wp_parse_url( $url );
			$record              = Schema::init_record();
			$datetime            = new \DateTime( 'now', self::$local_timezone );
			$record['timestamp'] = $datetime->format( 'Y-m-d' );
			$record['site']      = get_current_blog_id();
			$record['context']   = $bound;
			if ( array_key_exists( 'host', $url_parts ) && isset( $url_parts['host'] ) ) {
				if ( 'outbound' === $bound ) {
					$record['id'] = Http::top_domain( $url_parts['host'] );
				}
				if ( 'inbound' === $bound ) {
					$record['id'] = $args['remote_ip'];
				}
				$record['authority'] = $url_parts['host'];
			}
			if ( array_key_exists( 'user', $url_parts ) && array_key_exists( 'pass', $url_parts ) && isset( $url_parts['user'] ) && isset( $url_parts['pass'] ) ) {
				$record['authority'] = $url_parts['user'] . ':' . $url_parts['pass'] . '@' . $record['authority'];
			}
			if ( array_key_exists( 'port', $url_parts ) && isset( $url_parts['port'] ) ) {
				$record['authority'] = $record['authority'] . ':' . $url_parts['port'];
			}
			if ( array_key_exists( 'method', $args ) && isset( $args['method'] ) ) {
				$record['verb'] = strtolower( $args['method'] );
			}
			if ( array_key_exists( 'scheme', $url_parts ) && isset( $url_parts['scheme'] ) ) {
				$record['scheme'] = $url_parts['scheme'];
			}
			if ( array_key_exists( 'path', $url_parts ) && isset( $url_parts['path'] ) ) {
				$record['endpoint'] = $url_parts['path'];
				$pos                = strpos( $record['endpoint'], ':' );
				if ( false !== $pos ) {
					$record['endpoint'] = substr( $record['endpoint'], 0, $pos );
				}
			}
			$code = 0;
			if ( isset( $response ) && is_array( $response ) && array_key_exists( 'response', $response ) && array_key_exists( 'code', $response['response'] ) ) {
				$code = (int) $response['response']['code'];
			} elseif ( function_exists( 'is_wp_error' ) && is_wp_error( $response ) ) {
				$code = (int) $response->get_error_code();
			}
			if ( array_key_exists( $code, Http::$http_status_codes ) ) {
				$record['code'] = $code;
			}
			if ( 'outbound' === $bound ) {
				$record['latency_min'] = self::outbound_stop( $url, $args );
			}
			if ( 'inbound' === $bound ) {
				$record['latency_min'] = self::inbound_stop( $url, $args );
			}
			$record['latency_avg'] = $record['latency_min'];
			$record['latency_max'] = $record['latency_min'];
			Schema::store_statistics( $record );
		} catch ( \Throwable $t ) {
			Logger::warning( ucfirst( $bound ) . ' API analysis: ' . $t->getMessage(), $t->getCode() );
		}
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
		self::record( $response, $args, $url, 'outbound' );
	}

	/**
	 * Filters the API response.
	 *
	 * Allows modification of the response data after inserting
	 * embedded data (if any) and before echoing the response data.
	 *
	 * @param array            $result  Response data to send to the client.
	 * @param \WP_REST_Server  $server    Server instance.
	 * @param \WP_REST_Request $request Request used to generate the response.
	 * @return array Response data to send to the client.
	 * @since    1.0.0
	 */
	public static function rest_pre_echo_response( $result, $server, $request ) {
		try {
			$url = filter_input( INPUT_SERVER, 'REQUEST_URI', FILTER_SANITIZE_STRING );
			if ( 0 !== strpos( strtolower( $url ), 'http' ) ) {
				$scheme = strtolower( filter_input( INPUT_SERVER, 'REQUEST_SCHEME', FILTER_SANITIZE_STRING ) );
				$server = strtolower( filter_input( INPUT_SERVER, 'SERVER_NAME', FILTER_SANITIZE_STRING ) );
				$port   = filter_input( INPUT_SERVER, 'SERVER_PORT', FILTER_SANITIZE_NUMBER_INT );
				if ( ( 'http' === $scheme && 80 === (int) $port ) || ( 'https' === $scheme && 443 === (int) $port ) ) {
					$port = '';
				} else {
					$port = ':' . $port;
				}
				$url = $scheme . '://' . $server . $port . $url;
			}
			$args = [
				'method'    => filter_input( INPUT_SERVER, 'REQUEST_METHOD', FILTER_SANITIZE_STRING ),
				'remote_ip' => filter_input( INPUT_SERVER, 'REMOTE_ADDR', FILTER_SANITIZE_STRING ),
			];
			if ( array_key_exists( 'HTTP_X_REAL_IP', $_SERVER ) ) {
				$args['remote_ip'] = filter_input( INPUT_SERVER, 'HTTP_X_REAL_IP', FILTER_SANITIZE_STRING );
			}
			if ( array_key_exists( 'data', $result ) && array_key_exists( 'status', $result['data'] ) ) {
				$code = (int) $result['data']['status'];
			} elseif ( ( array_key_exists( 'route', $result ) || array_key_exists( 'routes', $result ) ) && ( array_key_exists( 'namespace', $result ) || array_key_exists( 'namespaces', $result ) ) ) {
				$code = 200;
			} else {
				$code = 0;
			}
		} catch ( \Throwable $t ) {
			Logger::warning( 'Inbound API pre-analysis: ' . $t->getMessage(), $t->getCode() );
		}
		self::record( [ 'response' => [ 'code' => $code ] ], $args, $url, 'inbound' );
		return $result;
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
	public static function outbound_start( $url, $args ) {
		self::$chrono[ self::get_id( $url, $args ) ] = microtime( true );
	}

	/**
	 * Stops the chrono.
	 *
	 * @param   string $url    The request URL.
	 * @param   array  $args   HTTP request arguments.
	 * @return  int The query latency, in ms.
	 * @since    1.0.0
	 */
	private static function outbound_stop( $url, $args ) {
		$id   = self::get_id( $url, $args );
		$stop = microtime( true );
		if ( array_key_exists( $id, self::$chrono ) ) {
			$start = self::$chrono[ $id ];
			unset( self::$chrono[ $id ] );
		} else {
			Logger::debug( sprintf( 'Unmatched query for %s.', $url ) );
			$start = self::$default_chrono;
		}
		return (int) round( 1000 * ( $stop - $start ), 0 );
	}

	/**
	 * Stops the chrono.
	 *
	 * @param   string $url    The request URL.
	 * @param   array  $args   HTTP request arguments.
	 * @return  int The query latency, in ms.
	 * @since    1.0.0
	 */
	private static function inbound_stop( $url, $args ) {
		$stop = microtime( true );
		if ( defined( 'TRAFFIC_INBOUND_CHRONO' ) ) {
			$start = TRAFFIC_INBOUND_CHRONO;
		} else {
			$start = self::$default_chrono;
		}
		if ( array_key_exists( 'REQUEST_TIME_FLOAT', $_SERVER ) ) {
			$time = filter_input( INPUT_SERVER, 'REQUEST_TIME_FLOAT', FILTER_SANITIZE_NUMBER_FLOAT );
			if ( $time ) {
				$start = (float) $time;
			}
		}
		return (int) round( 1000 * ( $stop - $start ), 0 );
	}

}
