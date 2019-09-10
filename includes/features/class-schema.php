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
	private $statistics = TRAFFIC_SLUG . '_statistics';

	/**
	 * Initialize the class and set its properties.
	 *
	 * @since    1.0.0
	 */
	public function __construct() {
	}

	/**
	 * Initialize the schema.
	 *
	 * @since    1.0.0
	 */
	public function initialize() {
		global $wpdb;
		$charset_collate = $wpdb->get_charset_collate();
		$sql             = 'CREATE TABLE IF NOT EXISTS ' . $wpdb->prefix . $this->statistics;
		$sql            .= " (`timestamp` date NOT NULL DEFAULT '0000-00-00',";
		$sql            .= " `context` enum('inbound','outbound','unknown') NOT NULL DEFAULT 'unknown',";
		$sql            .= " `id` varchar(40) NOT NULL DEFAULT '-',";
		$sql            .= " `verb` enum('get','post','head','put','delete','trace','options','patch','unknown') NOT NULL DEFAULT 'unknown',";
		$sql            .= " `scheme` enum('http','https','unknown') NOT NULL DEFAULT 'unknown',";
		$sql            .= " `authority` varchar(250) NOT NULL DEFAULT '-',";
		$sql            .= " `endpoint` varchar(250) NOT NULL DEFAULT '-',";
		$sql            .= " `code` smallint UNSIGNED NOT NULL DEFAULT '0',";
		$sql            .= " `hit` int(11) UNSIGNED NOT NULL DEFAULT '0',";
		$sql            .= " `latency_min` smallint UNSIGNED NOT NULL DEFAULT '0',";
		$sql            .= " `latency_avg` smallint UNSIGNED NOT NULL DEFAULT '0',";
		$sql            .= " `latency_max` smallint UNSIGNED NOT NULL DEFAULT '0',";
		$sql            .= " UNIQUE KEY perf (timestamp, context, id, verb, scheme, authority, endpoint, code)";
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
		$sql = 'DROP TABLE IF EXISTS ' . $wpdb->prefix . $this->statistics;
		// phpcs:ignore
		$wpdb->query( $sql );
	}

}
