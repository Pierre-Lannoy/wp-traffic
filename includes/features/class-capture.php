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

use Traffic\System\Blog;
use Traffic\System\Environment;
use Traffic\System\Logger;
use Traffic\Plugin\Feature\Schema;
use Traffic\System\Option;
use Traffic\System\Timezone;
use Traffic\System\Http;
use Traffic\System\Favicon;
use Traffic\System\IP;

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
		$started = false;
		if ( Option::network_get( 'outbound_capture' ) ) {
			add_filter( 'pre_http_request', [ 'Traffic\Plugin\Feature\Capture', 'pre_http_request' ], 10, 3 );
			add_filter( 'http_api_debug', [ 'Traffic\Plugin\Feature\Capture', 'http_api_debug' ], 10, 5 );
			$started = true;
		}
		if ( Option::network_get( 'inbound_capture' ) ) {
			add_filter( 'rest_pre_echo_response', [ 'Traffic\Plugin\Feature\Capture', 'rest_pre_echo_response' ], 10, 3 );
			$started = true;
		}
		if ( $started ) {
			self::$default_chrono = microtime( true );
			self::$local_timezone = Timezone::network_get();
			Logger::debug( 'Capture engine started.' );
		}
	}

	/**
	 * Clean the endpoint.
	 *
	 * @param   string $host       The host for the request.
	 * @param   string $endpoint   The endpoint to clean.
	 * @param   string $bound      Maybe 'inbound', 'outbound' or 'unknown'.
	 * @param   int    $cut        Optional. The number of path levels to let.
	 * @return string   The cleaned endpoint.
	 * @since    1.0.0
	 */
	private static function clean_endpoint( $host, $endpoint, $bound, $cut = 3 ) {

		/**
		 * Filters the cut level.
		 *
		 * @since 1.0.0
		 *
		 * @param   int    $cut        The number of path levels to let.
		 * @param   string $host       The host for the request.
		 * @param   string $endpoint   The endpoint to clean.
		 * @param   string $bound      Maybe 'inbound', 'outbound' or 'unknown'.
		 */
		$cut = (int) apply_filters( 'traffic_path_level', $cut, $host, $endpoint, $bound );

		if ( '/' !== substr( $endpoint, 0, 1 ) ) {
			$endpoint = '/' . $endpoint;
		}
		$endpoint = str_replace( '/://', '/', $endpoint );
		while ( 0 !== substr_count( $endpoint, '//' ) ) {
			$endpoint = str_replace( '//', '/', $endpoint );
		}
		$cpt = 0;
		$ep  = '';
		while ( $cpt < $cut ) {
			if ( 0 === substr_count( $endpoint, '/' ) ) {
				break;
			}
			do {
				$ep       = $ep . substr( $endpoint, 0, 1 );
				$endpoint = substr( $endpoint, 1 );
				$length   = strlen( $endpoint );
			} while ( ( 0 < $length ) && ( '/' !== substr( $endpoint, 0, 1 ) ) );
			++$cpt;
		}
		return $ep;
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
		return $preempt;
	}

	/**
	 * Records an entry.
	 *
	 * @param array|WP_Error $response HTTP response or WP_Error object.
	 * @param array          $args     HTTP request arguments.
	 * @param string         $url      The request URL.
	 * @param string         $bound    Optional. The bound.
	 * @param int            $b_in     Optional. Inbound bytes.
	 * @param int            $b_out    Optional. Outbound bytes.
	 * @since    1.0.0
	 */
	private static function record( $response, $args, $url, $bound = 'unknown', $b_in = 0, $b_out = 0 ) {
		try {
			$host                = '';
			$url_parts           = wp_parse_url( $url );
			$record              = Schema::init_record();
			$datetime            = new \DateTime( 'now', self::$local_timezone );
			$record['timestamp'] = $datetime->format( 'Y-m-d' );
			$record['site']      = get_current_blog_id();
			$record['context']   = $bound;
			if ( array_key_exists( 'host', $url_parts ) && isset( $url_parts['host'] ) ) {
				if ( 'outbound' === $bound ) {
					$record['id'] = Http::top_domain( $url_parts['host'], false );
				}
				if ( 'inbound' === $bound ) {
					$record['id'] = $args['remote_ip'];
				}
				$host                = $url_parts['host'];
				$record['authority'] = $host;
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
				$record['endpoint'] = self::clean_endpoint( $host, $url_parts['path'], $bound, Option::network_get( $bound . '_cut_path', 3 ) );
			}
			if ( '/wp-cron.php' === $record['endpoint'] && false !== strpos( Blog::get_blog_url( get_current_blog_id() ), $record['id'] ) ) {
				return;
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
			if ( $b_in > 0 && $b_in < 1024 ) {
				$record['kb_in'] = 1;
			} else {
				$record['kb_in'] = (int) round( $b_in / 1024, 0 );
			}
			if ( $b_out > 0 && $b_out < 1024 ) {
				$record['kb_out'] = 1;
			} else {
				$record['kb_out'] = (int) round( $b_out / 1024, 0 );
			}
			if ( 'outbound' === $bound ) {
				$record['latency_min'] = self::outbound_stop( $url, $args );
			}
			if ( 'inbound' === $bound ) {
				$record['latency_min'] = self::inbound_stop( $url, $args );
			}
			$record['latency_avg'] = $record['latency_min'];
			$record['latency_max'] = $record['latency_min'];
			if ( '-' !== $record['id'] && '-' !== $record['authority'] ) {
				Schema::store_statistics( $record );
			}
		} catch ( \Throwable $t ) {
			Logger::warning( ucfirst( $bound ) . ' API record: ' . $t->getMessage(), $t->getCode() );
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
		try {
			$b_in  = 0;
			$b_out = 0;
			// Outbound request.
			$header = '';
			if ( array_key_exists( 'header', $args ) ) {
				foreach ( $args['headers'] as $key => $value ) {
					$header .= $key . ': ' . $value . PHP_EOL;
				}
			}
			foreach ( headers_list() as $value ) {
				$header .= $value . PHP_EOL;
			}
			if ( array_key_exists( 'user-agent', $args ) ) {
				$header .= 'User-Agent: ' . $args['user-agent'] . PHP_EOL;
			}
			$url_parts = wp_parse_url( $url );
			if ( array_key_exists( 'path', $url_parts ) ) {
				$header .= 'XXXX ' . $url_parts['path'] . ' HTTP/1.1' . PHP_EOL;
			}
			if ( array_key_exists( 'host', $url_parts ) ) {
				$header .= 'Host: ' . $url_parts['host'] . PHP_EOL;
			}
			$cookie = '';
			if ( array_key_exists( 'cookies', $args ) ) {
				$c = [];
				foreach ( $args['cookies'] as $key => $value ) {
					if ( is_scalar( $value ) ) {
						$c[] = $key . '=' . $value;
					} else {
						// phpcs:ignore
						$c[] = $key . '=' . serialize( $value );
					}
				}
				$cookie = 'Cookie: ' . implode( '; ', $c ) . PHP_EOL;
			}
			if ( array_key_exists( 'body', $args ) ) {
				// phpcs:ignore
				$body = serialize( $args['body'] ) . PHP_EOL;
			} else {
				$body = $args['body'] . PHP_EOL;
			}
			$b_out = strlen( $header ) + strlen( $cookie ) + strlen( $body );
			$sized = false;
			if ( is_array( $response ) ) {
				$oheaders = wp_remote_retrieve_headers( $response );
				if ( method_exists( $oheaders, 'getAll' ) ) {
					$headers = $oheaders->getAll();
					if ( array_key_exists( 'content-length', $headers ) && array_key_exists( 'accept-ranges', $headers ) && 'bytes' === $headers['accept-ranges'] ) {
						$header = '';
						foreach ( $headers as $key => $value ) {
							// phpcs:ignore
							$header .= $key . ': ' . serialize( $value ) . PHP_EOL;
						}
						$b_in  = (int) $headers['content-length'] + strlen( $header );
						$sized = true;
					}
				}
				if ( ! $sized && $response['http_response'] instanceof \WP_HTTP_Requests_Response ) {
					$r    = $response['http_response']->get_response_object();
					$b_in = strlen( $r->raw );
				}
				if ( array_key_exists( 'filename', $args ) && $args['filename'] ) {
					global $wp_filesystem;
					if ( is_null( $wp_filesystem ) ) {
						require_once ABSPATH . '/wp-admin/includes/file.php';
						WP_Filesystem();
					}
					if ( $wp_filesystem->exists( $args['filename'] ) ) {
						$b_in += $wp_filesystem->size( $args['filename'] );
					}
				}
			}
		} catch ( \Throwable $t ) {
			Logger::warning( 'Outbound API post-analysis: ' . $t->getMessage(), $t->getCode() );
		}
		self::record( $response, $args, $url, 'outbound', $b_in, $b_out );
	}

	/**
	 * Get all files in a flat array.
	 *
	 * @return array The files.
	 * @since    1.0.0
	 */
	private static function incoming_files() {
		$files  = $_FILES;
		$files2 = [];
		foreach ( $files as $input => $info_arr ) {
			$files_by_input = [];
			foreach ( $info_arr as $key => $value_arr ) {
				if ( is_array( $value_arr ) ) {
					foreach ( $value_arr as $i => $value ) {
						$files_by_input[ $i ][ $key ] = $value;
					}
				} else {
					$files_by_input[] = $info_arr;
					break;
				}
			}
			$files2 = array_merge( $files2, $files_by_input );
		}
		$files3 = [];
		foreach ( $files2 as $file ) {
			if ( ! $file['error'] ) {
				$files3[] = $file;
			}
		}
		return $files3;
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
		if ( 2 === Environment::exec_mode() ) {
			return $result;
		}
		try {
			$b_in                 = 0;
			$b_out                = 0;
			$response             = [];
			$response['response'] = [];
			if ( $server instanceof \WP_REST_Server ) {
				// Inbound request.
				$header = '';
				foreach ( $server->get_headers( $_SERVER ) as $key => $value ) {
					$header .= $key . ': ' . $value . PHP_EOL;
				}
				$body   = $server->get_raw_data();
				$f_size = 0;
				foreach ( self::incoming_files() as $file ) {
					if ( array_key_exists( 'size', $file ) ) {
						$f_size = $f_size + (int) $file['size'];
					}
				}
				$b_in = strlen( $header ) + strlen( $body ) + $f_size;

				// Outbound response.
				$header = '';
				foreach ( headers_list() as $value ) {
					$header .= $value . PHP_EOL;
				}
				$body  = wp_json_encode( $result );
				$b_out = strlen( $header ) + strlen( $body );
			}
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
			$args                         = [
				'method'    => filter_input( INPUT_SERVER, 'REQUEST_METHOD', FILTER_SANITIZE_STRING ),
				'remote_ip' => IP::get_current(),
			];
			$response['response']['code'] = 200;
			if ( is_array( $result ) && array_key_exists( 'data', $result ) && is_array( $result['data'] ) && array_key_exists( 'status', $result['data'] ) ) {
				$response['response']['code'] = (int) $result['data']['status'];
			}
		} catch ( \Throwable $t ) {
			Logger::warning( 'Inbound API pre-analysis: ' . $t->getMessage(), $t->getCode() );
		}
		self::record( $response, $args, $url, 'inbound', $b_in, $b_out );
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
