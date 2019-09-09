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



		$sql .= " (`timestamp` date NOT NULL DEFAULT '0000-00-00',";
		$sql .= " `service` varchar(30) NOT NULL DEFAULT 'N/A',";
		$sql .= " `post` int(11) NOT NULL DEFAULT '0',";
		$sql .= " `get` int(11) NOT NULL DEFAULT '0',";
		$sql .= " `put` int(11) NOT NULL DEFAULT '0',";
		$sql .= " `patch` int(11) NOT NULL DEFAULT '0',";
		$sql .= " `delete` int(11) NOT NULL DEFAULT '0',";
		$sql .= " UNIQUE KEY perf (timestamp, service)";
		$sql .= ") $charset_collate;";





		$sql            .= ' (`id` int(11) UNSIGNED NOT NULL AUTO_INCREMENT,';
		$sql            .= " `timestamp` datetime NOT NULL DEFAULT '0000-00-00 00:00:00',";
		$sql            .= " `level` enum('emergency','alert','critical','error','warning','notice','info','debug','unknown') NOT NULL DEFAULT 'unknown',";
		$sql            .= " `channel` enum('cli','cron','ajax','xmlrpc','api','feed','wback','wfront','unknown') NOT NULL DEFAULT 'unknown',";
		$sql            .= ' `class` enum(' . $classes . ") NOT NULL DEFAULT 'unknown',";
		$sql            .= " `component` varchar(26) NOT NULL DEFAULT 'Unknown',";
		$sql            .= " `version` varchar(13) NOT NULL DEFAULT 'N/A',";
		$sql            .= " `code` int(11) UNSIGNED NOT NULL DEFAULT '0',";
		$sql            .= " `message` varchar(1000) NOT NULL DEFAULT '-',";
		$sql            .= " `site_id` int(11) UNSIGNED NOT NULL DEFAULT '0',";
		$sql            .= " `site_name` varchar(250) NOT NULL DEFAULT 'Unknown',";
		$sql            .= " `user_id` varchar(66) NOT NULL DEFAULT '0',";  // Needed by SHA-256 pseudonymization.
		$sql            .= " `user_name` varchar(250) NOT NULL DEFAULT 'Unknown',";
		$sql            .= " `remote_ip` varchar(66) NOT NULL DEFAULT '0',";  // Needed by SHA-256 obfuscation.
		$sql            .= " `url` varchar(2083) NOT NULL DEFAULT '-',";
		$sql            .= ' `verb` enum(' . $verbs . ") NOT NULL DEFAULT 'unknown',";
		$sql            .= " `server` varchar(250) NOT NULL DEFAULT 'unknown',";
		$sql            .= " `referrer` varchar(250) NOT NULL DEFAULT '-',";
		$sql            .= " `file` varchar(250) NOT NULL DEFAULT 'unknown',";
		$sql            .= " `line` int(11) UNSIGNED NOT NULL DEFAULT '0',";
		$sql            .= " `classname` varchar(100) NOT NULL DEFAULT 'unknown',";
		$sql            .= " `function` varchar(100) NOT NULL DEFAULT 'unknown',";
		$sql            .= ' `trace` varchar(10000),';
		$sql            .= ' PRIMARY KEY (`id`)';
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
		$sql = 'DROP TABLE IF EXISTS ' . $wpdb->prefix . $this->statistics;
		// phpcs:ignore
		$wpdb->query( $sql );
	}

}
