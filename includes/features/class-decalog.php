<?php
/**
 * Traffic DecaLog integration
 *
 * Handles all calls logging operations.
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
use Traffic\System\Logger;
use Traffic\System\Cache;
use Traffic\System\Conversion;

/**
 * Define the calls logging functionality.
 *
 * Handles all calls logging operations.
 *
 * @package Features
 * @author  Pierre Lannoy <https://pierre.lannoy.fr/>.
 * @since   2.0.0
 */
class DecaLog {

	/**
	 * Statistics filter.
	 *
	 * @since  2.0.0
	 * @var    array    $statistics_filter    The statistics filters.
	 */
	private static $statistics_filter = [];

	/**
	 * Initialize the class and set its properties.
	 *
	 * @since    2.0.0
	 */
	public function __construct() {}

	/**
	 * Initialize static properties and hooks.
	 *
	 * @since    2.0.0
	 */
	public static function init() {
		self::$statistics_filter['endpoint'] = [ '/\/livelog/' ];
	}

	/**
	 * Effectively write a buffer element in the database.
	 *
	 * @param   array $record     The record to write.
	 * @since    2.0.0
	 */
	private static function write_statistics_records_to_database( $record ) {
		$host = '';
		if ( array_key_exists( 'authority', $record ) ) {
			$host = $record['authority'];
		}
		if ( array_key_exists( 'context', $record ) && array_key_exists( 'id', $record ) && 'inbound' === $record['context'] ) {
			$host = $record['id'];
		}
		if ( '' !== $host ) {
			$country = self::$geo_ip->get_iso3166_alpha2( $host );
			if ( ! empty( $country ) ) {
				$record['country'] = $country;
			}
		}
		$record['id'] = Http::top_domain( $record['id'] );
		Favicon::get_raw( $record['id'], true );
		$site = Blog::get_blog_url( $record['site'] );
		if ( '' !== $site ) {
			Favicon::get_raw( $site, true );
		}
		$field_insert = [];
		$value_insert = [];
		$value_update = [];
		foreach ( $record as $k => $v ) {
			$field_insert[] = '`' . $k . '`';
			$value_insert[] = "'" . $v . "'";
			if ( 'country' === $k ) {
				$value_update[] = '`country`="' . $v . '"';
			}
			if ( 'hit' === $k ) {
				$value_update[] = '`hit`=hit + 1';
			}
			if ( 'kb_in' === $k ) {
				$value_update[] = '`kb_in`=kb_in + ' . $v;
			}
			if ( 'kb_out' === $k ) {
				$value_update[] = '`kb_out`=kb_out + ' . $v;
			}
			if ( 'latency_min' === $k ) {
				$value_update[] = '`latency_min`=if(latency_min>' . $v . ',' . $v . ',latency_min)';
			}
			if ( 'latency_avg' === $k ) {
				$value_update[] = '`latency_avg`=((latency_avg*hit)+' . $v . ')/(hit+1)';
			}
			if ( 'latency_max' === $k ) {
				$value_update[] = '`latency_max`=if(latency_max<' . $v . ',' . $v . ',latency_max)';
			}
		}
	}

	/**
	 * Log API call.
	 *
	 * @param   array $record     The record to log.
	 * @since    2.0.0
	 */
	public static function log( $record ) {
		$record = Http::format_record( $record );
		foreach ( self::$statistics_filter as $field => $filter ) {
			foreach ( $filter as $f ) {
				if ( preg_match( $f, $record[ $field ] ) ) {
					return;
				}
			}
		}
		$level = Option::network_get( strtolower( $record['bound'] ) . '_level', 'unknown' );
		if ( ! in_array( $level, [ 'debug', 'info', 'notice', 'warning' ], true ) ) {
			$level = 'info';
			Option::network_set( strtolower( $record['bound'] ) . '_level', $level );
		}
		switch ( $record['bound'] ) {
			case 'INBOUND':
				$message = ucfirst( strtolower( $record['bound'] ) ) . ' ' . $record['verb'] . ' from ' . $record['id'];
				break;
			case 'OUTBOUND':
				$message = ucfirst( strtolower( $record['bound'] ) ). ' ' . $record['verb'] . ' to ' . $record['id'];
				break;
			default:
				$message = '';
		}
		$message .= ' [size=' . Conversion::data_shorten( $record['size'] ) . ']';
		$message .= ' [latency=' . $record['latency'] . 'ms]';
		$message .= ' [response="' . $record['code'] . '/' . $record['message'] . '"]';
		$message .= ' [endpoint="' . $record['endpoint'] . '"]';
		Logger::log( $level, $message, (int) $record['code'] );
	}
}

DecaLog::init();
