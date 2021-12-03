<?php
/**
 * Traffic shared memory
 *
 * Handles all shared memory operations.
 *
 * @package Features
 * @author  Pierre Lannoy <https://pierre.lannoy.fr/>.
 * @since   2.0.0
 */

namespace Traffic\Plugin\Feature;

use Traffic\System\Blog;
use Traffic\System\Option;
use Traffic\System\Database;
use Traffic\System\Http;
use Traffic\System\Favicon;
use Traffic\System\Cache;
use Traffic\System\GeoIP;
use Traffic\System\Environment;
use Traffic\System\SharedMemory;
use malkusch\lock\mutex\FlockMutex;

/**
 * Define the shared memory functionality.
 *
 * Handles all shared memory operations.
 *
 * @package Features
 * @author  Pierre Lannoy <https://pierre.lannoy.fr/>.
 * @since   2.0.0
 */
class Memory {

	/**
	 * Statistics filter.
	 *
	 * @since  2.0.0
	 * @var    array    $statistics_filter    The statistics filters.
	 */
	private static $statistics_filter = [];

	/**
	 * Statistics buffer.
	 *
	 * @since  2.0.0
	 * @var    array    $statistics    The statistics buffer.
	 */
	private static $statistics_buffer = [];

	/**
	 * Messages buffer.
	 *
	 * @since  2.0.0
	 * @var    array    $statistics    The statistics buffer.
	 */
	private static $messages_buffer = [];

	/**
	 * The buffer size.
	 *
	 * @since  2.0.0
	 * @var    integer    $buffer    The number of messages in buffer.
	 */
	private static $buffer = 4000;

	/**
	 * The read index.
	 *
	 * @since  2.0.0
	 * @var    string    $index    The index for data.
	 */
	private static $index = '';

	/**
	 * Initialize the class and set its properties.
	 *
	 * @since    2.0.0
	 */
	public function __construct() {
	}

	/**
	 * Initialize static properties and hooks.
	 *
	 * @since    2.0.0
	 */
	public static function init() {
		self::$statistics_filter['endpoint'] = [ '/\/livelog/iU', '/\/beacon/iU' ];
		add_action( 'shutdown', [ 'Traffic\Plugin\Feature\Memory', 'write' ], DECALOG_MAX_SHUTDOWN_PRIORITY, 0 );
		if ( \DecaLog\Engine::isDecalogActivated() && Option::network_get( 'metrics' ) ) {
			add_action( 'shutdown', [ 'Traffic\Plugin\Feature\Memory', 'collate_metrics' ], DECALOG_MAX_SHUTDOWN_PRIORITY - 1, 0 );
		}
	}

	/**
	 * Verify if auto-logging is enabled.
	 *
	 * @since    2.0.0
	 */
	public static function is_enabled() {
		return Option::network_get( 'livelog' );
	}

	/**
	 * Write all buffers to shared memory.
	 *
	 * @since    2.0.0
	 */
	public static function write() {
		if ( self::is_enabled() ) {
			self::format_records();
			self::write_records_to_memory();
		}
	}

	/**
	 * Format records.
	 *
	 * @param boolean $final Optional. If false, allows recursive calls. This is to allow to format
	 *                                 records generated while executing 'shutdown' hook.
	 * @since    2.0.0
	 */
	private static function format_records( $final = false ) {
		foreach ( self::$statistics_buffer as $key => $record ) {
			self::$messages_buffer[ $key ] = Http::format_record( $record );
			unset( self::$statistics_buffer[ $key ] );
		}
		if ( 0 < count( self::$statistics_buffer ) && ! $final ) {
			self::format_records( true );
		}
	}

	/**
	 * Get relevant ftok.
	 *
	 * @since    2.0.0
	 */
	private static function ftok() {
		if ( 1 === Environment::exec_mode() ) {
			return ftok( __FILE__, 'c' );
		} else {
			return ftok( __FILE__, 'w' );
		}
	}

	/**
	 * Effectively write the message buffer to shared memory.
	 *
	 * @since    2.0.0
	 */
	private static function write_records_to_memory() {
		$messages = self::$messages_buffer;
		// phpcs:ignore
		$mutex = new FlockMutex( fopen( __FILE__, 'r' ), 1 );
		$ftok  = self::ftok();
		$mutex->synchronized(
			function () use ( $messages, $ftok ) {
				$sm   = new SharedMemory( $ftok );
				$data = $sm->read();
				foreach ( $messages as $key => $message ) {
					if ( is_array( $message ) ) {
						$data[ $key ] = $message;
					}
				}
				$data = array_slice( $data, -self::$buffer );
				if ( false === $sm->write( $data ) ) {
					//error_log( 'ERROR' );
				}
			}
		);
	}

	/**
	 * Read the current records.
	 *
	 * @return  array   The current records, ordered.
	 * @since    2.0.0
	 */
	public static function read(): array {
		try {
			// phpcs:ignore
			$mutex = new FlockMutex( fopen( __FILE__, 'r' ), 1 );
			$ftok  = ftok( __FILE__, 'w' );
			$data1 = $mutex->synchronized(
				function () use ( $ftok ) {
					$log  = new SharedMemory( $ftok );
					$data = $log->read();
					return $data;
				}
			);
			$ftok  = ftok( __FILE__, 'c' );
			$data2 = $mutex->synchronized(
				function () use ( $ftok ) {
					$log  = new SharedMemory( $ftok );
					$data = $log->read();
					return $data;
				}
			);
			$data  = array_merge( $data1, $data2 );
			uksort( $data, 'strcmp' );
		} catch ( \Throwable $e ) {
			$data = [];
		}
		$result = [];
		foreach ( $data as $key => $line ) {
			if ( 0 < strcmp( $key, self::$index ) ) {
				$result[ $key ] = $line;
				self::$index    = $key;
			}
		}
		return $result;
	}

	/**
	 * Store statistics in buffer.
	 *
	 * @param   array $record     The record to bufferize.
	 * @since    2.0.0
	 */
	public static function store_statistics( $record ) {
		if ( Option::network_get( 'smart_filter' ) ) {
			foreach ( self::$statistics_filter as $field => $filter ) {
				foreach ( $filter as $f ) {
					if ( preg_match( $f, $record[ $field ] ) ) {
						return;
					}
				}
			}
		}
		$date = new \DateTime();
		self::$statistics_buffer[ $date->format( 'YmdHisu' ) ] = $record;
	}

	/**
	 * Publish metrics.
	 *
	 * @since    2.3.0
	 */
	public static function collate_metrics() {
		$span    = \DecaLog\Engine::tracesLogger( TRAFFIC_SLUG )->startSpan( 'Metrics collation', DECALOG_SPAN_SHUTDOWN );
		$metrics = \DecaLog\Engine::metricsLogger( TRAFFIC_SLUG );
		$in      = 0;
		$out     = 0;
		$cpt     = [
			'inbound'  => [
				'count'   => 0,
				'latency' => 0,
			],
			'outbound' => [
				'count'   => 0,
				'latency' => 0,
			],
		];
		foreach ( self::$statistics_buffer as $record ) {
			$bound = $record['context'];
			$verb  = $record['verb'];
			$code  = (int) ( $record['code'] / 100 );
			if ( ! in_array( $code, Http::$http_summary_codes, true ) ) {
				continue;
			}
			$cpt[ $bound ]['count']++;
			$cpt[ $bound ]['latency'] += $record['latency_avg'];
			$in                       += $record['kb_in'];
			$out                      += $record['kb_out'];
			$metrics->incProdCounter( $bound . '_http_' . $code . 'xx_total' );
			$metrics->incProdCounter( $bound . '_' . $verb . '_total' );
		}
		foreach ( array_diff( Http::$contexts, [ 'unknown' ] ) as $known_context ) {
			if ( 0 < $cpt[ $known_context ]['count'] ) {
				$metrics->incProdCounter( $known_context . '_latency_avg', $cpt[ $known_context ]['latency'] / ( $cpt[ $known_context ]['count'] * 1000 ) );
				$metrics->incProdCounter( $known_context . '_total', $cpt[ $known_context ]['count'] );
			}
		}
		$metrics->incProdCounter( 'data_in_total', $in * 1024 );
		$metrics->incProdCounter( 'data_out_total', $out * 1024 );
		\DecaLog\Engine::tracesLogger( TRAFFIC_SLUG )->endSpan( $span );
	}
}

if ( ! defined( 'DECALOG_MAX_SHUTDOWN_PRIORITY' ) ) {
	define( 'DECALOG_MAX_SHUTDOWN_PRIORITY', PHP_INT_MAX - 1000 );
}

Memory::init();
