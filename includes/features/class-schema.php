<?php
/**
 * Traffic schema
 *
 * Handles all schema operations.
 *
 * @package Features
 * @author  Pierre Lannoy <https://pierre.lannoy.fr/>.
 * @since   1.0.0
 */

namespace Traffic\Plugin\Feature;

use Traffic\System\Blog;
use Traffic\System\Option;
use Traffic\System\Database;
use Traffic\System\Http;
use Traffic\System\Favicon;
use Traffic\System\Logger;
use Traffic\System\Cache;
use Traffic\System\GeoIP;

/**
 * Define the schema functionality.
 *
 * Handles all schema operations.
 *
 * @package Features
 * @author  Pierre Lannoy <https://pierre.lannoy.fr/>.
 * @since   1.0.0
 */
class Schema {

	/**
	 * Statistics table name.
	 *
	 * @since  1.0.0
	 * @var    string    $statistics    The statistics table name.
	 */
	private static $statistics = TRAFFIC_SLUG . '_statistics';

	/**
	 * Statistics buffer.
	 *
	 * @since  1.0.0
	 * @var    array    $statistics    The statistics buffer.
	 */
	private static $statistics_buffer = [];

	/**
	 * GeoIP instance.
	 *
	 * @since  1.0.0
	 * @var    GeoIP    $geo_ip    Maintain the GeoIP instance..
	 */
	private static $geo_ip = null;

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
		add_action( 'shutdown', [ 'Traffic\Plugin\Feature\Schema', 'write' ], 90, 0 );
		self::$geo_ip = new GeoIP();
	}

	/**
	 * Write all buffers to database.
	 *
	 * @since    1.0.0
	 */
	public static function write() {
		self::write_statistics();
	}

	/**
	 * Write statistics.
	 *
	 * @param boolean $final Optional. If false, allows recursive calls. This is to allow to write
	 *                                 records generated while executing 'shutdown' hook.
	 * @since    1.0.0
	 */
	private static function write_statistics( $final = false ) {
		foreach ( self::$statistics_buffer as $key => $record ) {
			self::write_statistics_records_to_database( $record );
			unset( self::$statistics_buffer[ $key ] );
		}
		if ( 0 < count( self::$statistics_buffer ) && ! $final ) {
			self::write_statistics( true );
		}
		self::purge();
	}

	/**
	 * Effectively write a buffer element in the database.
	 *
	 * @param   array $record     The record to write.
	 * @since    1.0.0
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
		if ( count( $field_insert ) > 0 ) {
			global $wpdb;
			$sql  = 'INSERT INTO `' . $wpdb->base_prefix . self::$statistics . '` ';
			$sql .= '(' . implode( ',', $field_insert ) . ') ';
			$sql .= 'VALUES (' . implode( ',', $value_insert ) . ') ';
			$sql .= 'ON DUPLICATE KEY UPDATE ' . implode( ',', $value_update ) . ';';
			// phpcs:ignore
			$wpdb->query( $sql );
		}
	}

	/**
	 * Store statistics in buffer.
	 *
	 * @param   array $record     The record to bufferize.
	 * @since    1.0.0
	 */
	public static function store_statistics( $record ) {
		self::$statistics_buffer[] = $record;
	}

	/**
	 * Initialize the schema.
	 *
	 * @since    1.1.0
	 */
	public function initialize() {
		global $wpdb;
		try {
			$this->create_table();
			Logger::debug( sprintf( 'Table "%s" created.', $wpdb->base_prefix . self::$statistics ) );
			Logger::info( 'Schema installed.' );
		} catch ( \Throwable $e ) {
			Logger::alert( sprintf( 'Unable to create "%s" table: %s', $wpdb->base_prefix . self::$statistics, $e->getMessage() ), $e->getCode() );
			Logger::alert( 'Schema not installed.', $e->getCode() );
		}
	}

	/**
	 * Update the schema.
	 *
	 * @since    1.1.0
	 */
	public function update() {
		global $wpdb;
		try {
			$this->create_table();
			Logger::debug( sprintf( 'Table "%s" updated.', $wpdb->base_prefix . self::$statistics ) );
			Logger::info( 'Schema updated.' );
		} catch ( \Throwable $e ) {
			Logger::alert( sprintf( 'Unable to update "%s" table: %s', $wpdb->base_prefix . self::$statistics, $e->getMessage() ), $e->getCode() );
		}
	}

	/**
	 * Purge old records.
	 *
	 * @since    1.0.0
	 */
	private static function purge() {
		$days = (int) Option::network_get( 'history' );
		if ( ! is_numeric( $days ) || 30 > $days ) {
			$days = 30;
			Option::network_set( 'history', $days );
		}
		$database = new Database();
		$count    = $database->purge( self::$statistics, 'timestamp', 24 * $days );
		if ( 0 === $count ) {
			Logger::debug( 'No old records to delete.' );
		} elseif ( 1 === $count ) {
			Logger::debug( '1 old record deleted.' );
			Cache::delete_global( 'data/oldestdate' );
		} else {
			Logger::debug( sprintf( '%1$s old records deleted.', $count ) );
			Cache::delete_global( 'data/oldestdate' );
		}
	}

	/**
	 * Create the table.
	 *
	 * @since    1.0.0
	 */
	private function create_table() {
		global $wpdb;
		$charset_collate = 'DEFAULT CHARACTER SET utf8 COLLATE utf8_unicode_ci';
		$sql             = 'CREATE TABLE IF NOT EXISTS ' . $wpdb->base_prefix . self::$statistics;
		$sql            .= " (`timestamp` date NOT NULL DEFAULT '0000-00-00',";
		$sql            .= " `site` bigint(20) NOT NULL DEFAULT '0',";
		$sql            .= " `context` enum('" . implode( "','", Http::$contexts ) . "') NOT NULL DEFAULT 'unknown',";
		$sql            .= " `country` varchar(2) DEFAULT NULL,";
		$sql            .= " `id` varchar(40) NOT NULL DEFAULT '-',";
		$sql            .= " `verb` enum('" . implode( "','", Http::$verbs ) . "') NOT NULL DEFAULT 'unknown',";
		$sql            .= " `scheme` enum('" . implode( "','", Http::$schemes ) . "') NOT NULL DEFAULT 'unknown',";
		$sql            .= " `authority` varchar(250) NOT NULL DEFAULT '-',";
		$sql            .= " `endpoint` varchar(250) NOT NULL DEFAULT '-',";
		$sql            .= " `code` smallint UNSIGNED NOT NULL DEFAULT '0',";
		$sql            .= " `hit` int(11) UNSIGNED NOT NULL DEFAULT '0',";
		$sql            .= " `latency_min` smallint UNSIGNED NOT NULL DEFAULT '0',";
		$sql            .= " `latency_avg` smallint UNSIGNED NOT NULL DEFAULT '0',";
		$sql            .= " `latency_max` smallint UNSIGNED NOT NULL DEFAULT '0',";
		$sql            .= " `kb_in` int(11) UNSIGNED NOT NULL DEFAULT '0',";
		$sql            .= " `kb_out` int(11) UNSIGNED NOT NULL DEFAULT '0',";
		$sql            .= ' UNIQUE KEY u_stat (timestamp, site, context, id, verb, scheme, authority, endpoint, code)';
		$sql            .= ") $charset_collate;";
		// phpcs:ignore
		$wpdb->query( $sql );
	}

	/**
	 * Finalize the schema.
	 *
	 * @since    1.0.0
	 */
	public function finalize() {
		global $wpdb;
		$sql = 'DROP TABLE IF EXISTS ' . $wpdb->base_prefix . self::$statistics;
		// phpcs:ignore
		$wpdb->query( $sql );
		Logger::debug( sprintf( 'Table "%s" removed.', $wpdb->base_prefix . self::$statistics ) );
		Logger::debug( 'Schema destroyed.' );
	}

	/**
	 * Get an empty record.
	 *
	 * @return  array   An empty, ready to use, record.
	 * @since    1.0.0
	 */
	public static function init_record() {
		$record = [
			'timestamp'   => '0000-00-00',
			'site'        => 0,
			'context'     => 'unknown',
			'id'          => '-',
			'verb'        => 'unknown',
			'scheme'      => 'unknown',
			'authority'   => '-',
			'endpoint'    => '-',
			'code'        => 0,
			'hit'         => 1,
			'latency_min' => 0,
			'latency_avg' => 0,
			'latency_max' => 0,
			'kb_in'       => 0,
			'kb_out'      => 0,
		];
		return $record;
	}

	/**
	 * Get "where" clause of a query.
	 *
	 * @param array $filters Optional. An array of filters.
	 * @return string The "where" clause.
	 * @since 1.0.0
	 */
	private static function get_where_clause( $filters = [] ) {
		$result = '';
		if ( 0 < count( $filters ) ) {
			$w = [];
			foreach ( $filters as $key => $filter ) {
				if ( is_array( $filter ) ) {
					$w[] = '`' . $key . '` IN (' . implode( ',', $filter ) . ')';
				} else {
					$w[] = '`' . $key . '`="' . $filter . '"';
				}
			}
			$result = 'WHERE (' . implode( ' AND ', $w ) . ')';
		}
		return $result;
	}

	/**
	 * Get the oldest date.
	 *
	 * @return  string   The oldest timestamp in the statistics table.
	 * @since    1.0.0
	 */
	public static function get_oldest_date() {
		$result = Cache::get_global( 'data/oldestdate' );
		if ( $result ) {
			return $result;
		}
		global $wpdb;
		$sql = 'SELECT * FROM ' . $wpdb->base_prefix . self::$statistics . ' ORDER BY `timestamp` ASC LIMIT 1';
		// phpcs:ignore
		$result = $wpdb->get_results( $sql, ARRAY_A );
		if ( is_array( $result ) && 0 < count( $result ) && array_key_exists( 'timestamp', $result[0] ) ) {
			Cache::set_global( 'data/oldestdate', $result[0]['timestamp'], 'infinite' );
			return $result[0]['timestamp'];
		}
		return '';
	}

	/**
	 * Get the authority.
	 *
	 * @param   array $filter   The filter of the query.
	 * @return  string   The authority.
	 * @since    1.0.0
	 */
	public static function get_authority( $filter ) {
		// phpcs:ignore
		$id = Cache::id( __FUNCTION__ . serialize( $filter ) );
		$result = Cache::get_global( $id );
		if ( $result ) {
			return $result;
		}
		global $wpdb;
		$sql = 'SELECT authority FROM ' . $wpdb->base_prefix . self::$statistics . ' WHERE (' . implode( ' AND ', $filter ) . ') LIMIT 1';
		// phpcs:ignore
		$result = $wpdb->get_results( $sql, ARRAY_A );
		if ( is_array( $result ) && 0 < count( $result ) ) {
			$authority = $result[0]['authority'];
			Cache::set_global( $id, $authority, 'infinite' );
			return $authority;
		}
		return '';
	}

	/**
	 * Get the distinct contexts.
	 *
	 * @param   array   $filter The filter of the query.
	 * @param   boolean $cache  Optional. Has this query to be cached.
	 * @return  array   The distinct contexts.
	 * @since    1.0.0
	 */
	public static function get_distinct_context( $filter, $cache = true ) {
		if ( array_key_exists( 'context', $filter ) ) {
			unset( $filter['context'] );
		}
		// phpcs:ignore
		$id = Cache::id( __FUNCTION__ . serialize( $filter ) );
		if ( $cache ) {
			$result = Cache::get_global( $id );
			if ( $result ) {
				return $result;
			}
		}
		global $wpdb;
		$sql = 'SELECT DISTINCT context FROM ' . $wpdb->base_prefix . self::$statistics . ' WHERE (' . implode( ' AND ', $filter ) . ')';
		// phpcs:ignore
		$result = $wpdb->get_results( $sql, ARRAY_A );
		if ( is_array( $result ) && 0 < count( $result ) ) {
			$contexts = [];
			foreach ( $result as $item ) {
				$contexts[] = $item['context'];
			}
			if ( $cache ) {
				Cache::set_global( $id, $contexts, 'infinite' );
			}
			return $contexts;
		}
		return [];
	}

	/**
	 * Get the standard KPIs.
	 *
	 * @param   array   $filter      The filter of the query.
	 * @param   boolean $cache       Has the query to be cached.
	 * @param   string  $extra_field Optional. The extra field to filter.
	 * @param   array   $extras      Optional. The extra values to match.
	 * @param   boolean $not         Optional. Exclude extra filter.
	 * @return  array   The standard KPIs.
	 * @since    1.0.0
	 */
	public static function get_std_kpi( $filter, $cache = true, $extra_field = '', $extras = [], $not = false ) {
		if ( array_key_exists( 'context', $filter ) ) {
			unset( $filter['context'] );
		}
		// phpcs:ignore
		$id = Cache::id( __FUNCTION__ . serialize( $filter ) . $extra_field . serialize( $extras ) . ( $not ? 'no' : 'yes') );
		if ( $cache ) {
			$result = Cache::get_global( $id );
			if ( $result ) {
				return $result;
			}
		}
		$where_extra = '';
		if ( 0 < count( $extras ) && '' !== $extra_field ) {
			$where_extra = ' AND ' . $extra_field . ( $not ? ' NOT' : '' ) . " IN ( '" . implode( "', '", $extras ) . "' )";
		}
		global $wpdb;
		$sql = 'SELECT sum(hit) as sum_hit, sum(kb_in) as sum_kb_in, sum(kb_out) as sum_kb_out, sum(hit*latency_avg)/sum(hit) as avg_latency, min(latency_min) as min_latency, max(latency_max) as max_latency FROM ' . $wpdb->base_prefix . self::$statistics . ' WHERE (' . implode( ' AND ', $filter ) . ') ' . $where_extra;
		// phpcs:ignore
		$result = $wpdb->get_results( $sql, ARRAY_A );
		if ( is_array( $result ) && 1 === count( $result ) ) {
			if ( $cache ) {
				Cache::set_global( $id, $result[0], 'infinite' );
			}
			return $result[0];
		}
		return [];
	}

	/**
	 * Get a time series.
	 *
	 * @param   array   $filter      The filter of the query.
	 * @param   boolean $cache       Has the query to be cached.
	 * @param   string  $extra_field Optional. The extra field to filter.
	 * @param   array   $extras      Optional. The extra values to match.
	 * @param   boolean $not         Optional. Exclude extra filter.
	 * @param   integer $limit       Optional. The number of results to return.
	 * @return  array   The time series.
	 * @since    1.0.0
	 */
	public static function get_time_series( $filter, $cache = true, $extra_field = '', $extras = [], $not = false, $limit = 0 ) {
		$data   = self::get_grouped_list( 'timestamp', [], $filter, $cache, $extra_field, $extras, $not, 'ORDER BY timestamp ASC', $limit );
		$result = [];
		foreach ( $data as $datum ) {
			$result[ $datum['timestamp'] ] = $datum;
		}
		return $result;
	}

	/**
	 * Get the standard KPIs.
	 *
	 * @param   string  $group       The group of the query.
	 * @param   array   $count       The sub-groups of the query.
	 * @param   array   $filter      The filter of the query.
	 * @param   boolean $cache       Has the query to be cached.
	 * @param   string  $extra_field Optional. The extra field to filter.
	 * @param   array   $extras      Optional. The extra values to match.
	 * @param   boolean $not         Optional. Exclude extra filter.
	 * @param   string  $order       Optional. The sort order of results.
	 * @param   integer $limit       Optional. The number of results to return.
	 * @return  array   The standard KPIs.
	 * @since    1.0.0
	 */
	public static function get_grouped_list( $group, $count, $filter, $cache = true, $extra_field = '', $extras = [], $not = false, $order = '', $limit = 0 ) {
		// phpcs:ignore
		$id = Cache::id( __FUNCTION__ . $group . serialize( $count ) . serialize( $filter ) . $extra_field . serialize( $extras ) . ( $not ? 'no' : 'yes') . $order . (string) $limit);
		if ( $cache ) {
			$result = Cache::get_global( $id );
			if ( $result ) {
				return $result;
			}
		}
		$where_extra = '';
		if ( 0 < count( $extras ) && '' !== $extra_field ) {
			$where_extra = ' AND ' . $extra_field . ( $not ? ' NOT' : '' ) . " IN ( '" . implode( "', '", $extras ) . "' )";
		}
		$cnt = [];
		foreach ( $count as $c ) {
			$cnt[] = 'count(distinct(' . $c . ')) as cnt_' . $c;
		}
		$c = implode( ', ', $cnt );
		if ( 0 < strlen( $c ) ) {
			$c = $c . ', ';
		}
		global $wpdb;
		$sql  = 'SELECT ' . ( '' !== $group && 'id' !== $group && 'authority' !== $group ? $group . ', ' : '' ) . $c . 'id, authority, sum(hit) as sum_hit, sum(kb_in) as sum_kb_in, sum(kb_out) as sum_kb_out, sum(hit*latency_avg)/sum(hit) as avg_latency, min(latency_min) as min_latency, max(latency_max) as max_latency FROM ';
		$sql .= $wpdb->base_prefix . self::$statistics . ' WHERE (' . implode( ' AND ', $filter ) . ') ' . $where_extra . ' GROUP BY ' . $group . ' ' . $order . ( $limit > 0 ? 'LIMIT ' . $limit : '') .';';
		// phpcs:ignore
		$result = $wpdb->get_results( $sql, ARRAY_A );
		if ( is_array( $result ) && 0 < count( $result ) ) {
			if ( $cache ) {
				Cache::set_global( $id, $result, 'infinite' );
			}
			return $result;
		}
		return [];
	}
}

Schema::init();
