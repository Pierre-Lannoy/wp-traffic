<?php
/**
 * WP-CLI for Traffic.
 *
 * Adds WP-CLI commands to Traffic
 *
 * @package Features
 * @author  Pierre Lannoy <https://pierre.lannoy.fr/>.
 * @since   2.0.0
 */

namespace Traffic\Plugin\Feature;

use Traffic\Plugin\Feature\Memory;
use Traffic\System\Cache;
use Traffic\System\Conversion;
use Traffic\System\Logger;
use Traffic\System\Date;
use Traffic\System\EmojiFlag;
use Traffic\System\Environment;
use Traffic\System\Markdown;
use Traffic\System\Option;
use Traffic\System\GeoIP;
use Traffic\System\Timezone;
use Traffic\System\UUID;

/**
 * WP-CLI for Traffic.
 *
 * Defines methods and properties for WP-CLI commands.
 *
 * @package Features
 * @author  Pierre Lannoy <https://pierre.lannoy.fr/>.
 * @since   2.0.0
 */
class Wpcli {

	/**
	 * List of color format per level.
	 *
	 * @since    2.0.0
	 * @var array $bound_color Level colors.
	 */
	private static $bound_color = [
		'inbound'  => [ '%4%c', '%0%c' ],
		'outbound' => [ '%3%r', '%0%Y' ],
	];

	/**
	 * List of exit codes.
	 *
	 * @since    2.0.0
	 * @var array $exit_codes Exit codes.
	 */
	private static $exit_codes = [
		0   => 'operation successful.',
		1   => 'invalid logger type supplied.',
		2   => 'invalid logger uuid supplied.',
		3   => 'system loggers can\'t be managed.',
		4   => 'unable to create a new logger.',
		5   => 'unable to modify this logger.',
		6   => 'invalid listener id supplied.',
		7   => 'unrecognized setting.',
		8   => 'unrecognized action.',
		255 => 'unknown error.',
	];

	/**
	 * Write ids as clean stdout.
	 *
	 * @param   array   $ids   The ids.
	 * @param   string  $field  Optional. The field to output.
	 * @since   2.0.0
	 */
	private static function write_ids( $ids, $field = '' ) {
		$result = '';
		$last   = end( $ids );
		foreach ( $ids as $key => $id ) {
			if ( '' === $field ) {
				$result .= $key;
			} else {
				$result .= $id[$field];
			}
			if ( $id !== $last ) {
				$result .= ' ';
			}
		}
		// phpcs:ignore
		fwrite( STDOUT, $result );
	}

	/**
	 * Write an error.
	 *
	 * @param   integer  $code      Optional. The error code.
	 * @param   boolean  $stdout    Optional. Clean stdout output.
	 * @since   2.0.0
	 */
	private static function error( $code = 255, $stdout = false ) {
		if ( \WP_CLI\Utils\isPiped() ) {
			// phpcs:ignore
			fwrite( STDOUT, '' );
			// phpcs:ignore
			exit( $code );
		} elseif ( $stdout ) {
			// phpcs:ignore
			fwrite( STDERR, ucfirst( self::$exit_codes[ $code ] ) );
			// phpcs:ignore
			exit( $code );
		} else {
			\WP_CLI::error( self::$exit_codes[ $code ] );
		}
	}

	/**
	 * Write a warning.
	 *
	 * @param   string   $msg       The message.
	 * @param   string   $result    Optional. The result.
	 * @param   boolean  $stdout    Optional. Clean stdout output.
	 * @since   2.0.0
	 */
	private static function warning( $msg, $result = '', $stdout = false ) {
		if ( \WP_CLI\Utils\isPiped() || $stdout ) {
			// phpcs:ignore
			fwrite( STDOUT, $result );
		} else {
			\WP_CLI::warning( $msg );
		}
	}

	/**
	 * Write a success.
	 *
	 * @param   string   $msg       The message.
	 * @param   string   $result    Optional. The result.
	 * @param   boolean  $stdout    Optional. Clean stdout output.
	 * @since   2.0.0
	 */
	private static function success( $msg, $result = '', $stdout = false ) {
		if ( \WP_CLI\Utils\isPiped() || $stdout ) {
			// phpcs:ignore
			fwrite( STDOUT, $result );
		} else {
			\WP_CLI::success( $msg );
		}
	}

	/**
	 * Write a wimple line.
	 *
	 * @param   string   $msg       The message.
	 * @param   string   $result    Optional. The result.
	 * @param   boolean  $stdout    Optional. Clean stdout output.
	 * @since   2.0.0
	 */
	private static function line( $msg, $result = '', $stdout = false ) {
		if ( \WP_CLI\Utils\isPiped() || $stdout ) {
			// phpcs:ignore
			fwrite( STDOUT, $result );
		} else {
			\WP_CLI::line( $msg );
		}
	}

	/**
	 * Write a wimple log line.
	 *
	 * @param   string   $msg       The message.
	 * @param   boolean  $stdout    Optional. Clean stdout output.
	 * @since   2.0.0
	 */
	private static function log( $msg, $stdout = false ) {
		if ( ! \WP_CLI\Utils\isPiped() && ! $stdout ) {
			\WP_CLI::log( $msg );
		}
	}

	/**
	 * Get params from command line.
	 *
	 * @param   array   $args   The command line parameters.
	 * @return  array The true parameters.
	 * @since   2.0.0
	 */
	private static function get_params( $args ) {
		$result = '';
		if ( array_key_exists( 'settings', $args ) ) {
			$result = \json_decode( $args['settings'], true );
		}
		if ( ! $result || ! is_array( $result ) ) {
			$result = [];
		}
		return $result;
	}

	/**
	 * Filters records.
	 *
	 * @param   array   $records    The records to filter.
	 * @param   array   $filters    Optional. The filter to apply.
	 * @param   string  $index      Optional. The starting index.
	 * @return  array   The filtered records.
	 * @since   2.0.0
	 */
	public static function records_filter( $records, $filters = [], $index = '' ) {
		$result = [];
		foreach ( $records as $idx => $record ) {
			foreach ( $filters as $key => $filter ) {
				switch ( $key ) {
					case 'bound':
						if ( $record['bound'] !== strtoupper( $filter ) ) {
							continue 3;
						}
						break;
					default:
						if ( ! preg_match( $filter, $record[ $key ] ) ) {
							continue 3;
						}
				}
			}
			$result[ $idx ] = $record;
		}
		if ( '' !== $index ) {
			$tmp = [];
			foreach ( $result as $key => $record ) {
				if ( 0 < strcmp( $key, $index ) ) {
					$tmp[ $key ] = $record;
				}
			}
			$result = $tmp;
		}
		uksort($result, 'strcmp' );
		return $result;
	}

	/**
	 * Format records records.
	 *
	 * @param   array   $records    The records to display.
	 * @param   boolean $soft       Optional. Soften colors.
	 * @param   integer $pad        Optional. Line padding.
	 * @return  array   The ready to print records.
	 * @since   2.0.0
	 */
	public static function records_format( $records, $soft = false, $pad = 160 ) {
		$result = [];
		$geoip  = new GeoIP();
		foreach ( $records as $idx => $record ) {
			$line  = '[' . $record['timestamp'] . '] ';
			$line .= strtoupper( str_pad( $record['bound'], 8 ) ) . ' ';
			$line .= strtoupper( str_pad( $record['verb'], 7 ) ) . ' ';
			$line .= str_pad( $record['code'], 3, '0', STR_PAD_LEFT ) . ' ';
			$line .= str_pad( Conversion::data_shorten( $record['size'] ), 7, ' ', STR_PAD_LEFT ) . ' ';
			$line .= str_pad( $record['latency'] . 'ms', 7, ' ', STR_PAD_LEFT ) . ' ';
			if ( !Environment::is_wordpress_multisite() ) {
				$sid = ' SID:' . str_pad( (string) $record['site_id'], 4, '0', STR_PAD_LEFT ) . ' ';
			} else {
				$sid = ' ';
			}
			$line .= $sid;
			if ( $geoip->is_installed() ) {
				$country = EmojiFlag::get( $record['country'] ) . ' ';
			} else {
				$country = '';
			}

			switch ( $record['bound'] ) {
				case 'INBOUND':
					$line .= '← ' . $country . $record['id'];
					break;
				case 'OUTBOUND':
					$line .= '→ ' . $country . $record['authority'] . $record['endpoint'];
					break;
			}



			$line = preg_replace( '/[\x00-\x1F\x7F\xA0]/u', '', $line );
			if ( $pad - 1 < strlen( $line ) ) {
				$line = substr( $line, 0, $pad - 1 ) . '…';
			}
			$result[ $idx ] = [ 'bound' => strtolower( $record['bound'] ), 'line' => traffic_mb_str_pad( $line, $pad ) ];
		}
		return $result;
	}

	/**
	 * Displays records.
	 *
	 * @param   array   $records    The records to display.
	 * @param   boolean $soft       Optional. Soften colors.
	 * @param   integer $pad        Optional. Line padding.
	 * @since   2.0.0
	 */
	private static function records_display( $records, $soft = false, $pad = 160 ) {
		foreach ( self::records_format( $records, $soft, $pad ) as $record ) {
			\WP_CLI::line( \WP_CLI::colorize( self::$bound_color[ strtolower( $record['bound'] ) ][$soft ? 1 : 0] ) . $record['line'] . \WP_CLI::colorize( '%n' ) );
		}
	}

	/**
	 * Get DecaLog details and operation modes.
	 *
	 * ## EXAMPLES
	 *
	 * wp log status
	 *
	 *
	 *   === For other examples and recipes, visit https://github.com/Pierre-Lannoy/wp-traffic/blob/master/WP-CLI.md ===
	 *
	 */
	public static function status( $args, $assoc_args ) {
		$run  = 0;
		$list = 0;
		foreach ( Option::network_get( 'loggers' ) as $key => $logger ) {
			if ( $logger['running'] ) {
				$run++;
			}
		}
		if ( Option::network_get( 'autolisteners' ) ) {
			$list = 'on all available listeners';
		} else {
			$listeners = ListenerFactory::$infos;
			foreach ( $listeners as $listener ) {
				if ( $listener['available'] && in_array( $listener['id'], Option::network_get( 'listeners' ), true ) ) {
					$list++;
				}
			}
			if ( 0 === $list) {
				$list = 'on no listener';
			} elseif ( 1 === $list) {
				$list = 'on 1 listener';
			} else {
				$list = sprintf( 'on %d listeners', $list );
			}
		}
		if ( 0 === $run) {
			$run  = '';
			$list = '';
		} elseif ( 1 === $run) {
			$run = '1 logger';
		} else {
			$run = sprintf( '%d loggers', $run );
		}
		\WP_CLI::line( sprintf( '%s running %s %s.', Environment::plugin_version_text(), $run, $list ) );
		if ( Option::network_get( 'earlyloading' ) ) {
			\WP_CLI::line( 'Early-Loading: enabled.' );
		} else {
			\WP_CLI::line( 'Early-Loading: disabled.' );
		}
		if ( Option::network_get( 'logger_autostart' ) ) {
			\WP_CLI::line( 'Auto-Start: enabled.' );
		} else {
			\WP_CLI::line( 'Auto-Start: disabled.' );
		}
		if ( Autolog::is_enabled() ) {
			\WP_CLI::line( 'Auto-Logging: enabled.' );
		} else {
			\WP_CLI::line( 'Auto-Logging: disabled.' );
		}
		$geo = new GeoIP();
		if ( $geo->is_installed() ) {
			\WP_CLI::line( 'IP information support: yes (' . $geo->get_full_name() . ').');
		} else {
			\WP_CLI::line( 'IP information support: no.' );
		}
		if ( class_exists( 'PODeviceDetector\API\Device' ) ) {
			\WP_CLI::line( 'Device detection support: yes (Device Detector v' . PODD_VERSION . ').');
		} else {
			\WP_CLI::line( 'Device detection support: no.' );
		}
	}

	/**
	 * Get information on logger types.
	 *
	 * ## OPTIONS
	 *
	 * <list|describe>
	 * : The action to take.
	 * ---
	 * options:
	 *  - list
	 *  - describe
	 * ---
	 *
	 * [<logger_type>]
	 * : The type of the logger to describe.
	 *
	 * [--format=<format>]
	 * : Allows overriding the output of the command when listing types.
	 * ---
	 * default: table
	 * options:
	 *  - table
	 *  - json
	 *  - csv
	 *  - yaml
	 *  - ids
	 *  - count
	 * ---
	 *
	 * ## EXAMPLES
	 *
	 * Lists available types:
	 * + wp log type list
	 * + wp log type list --format=json
	 *
	 * Details the WordpressHandler logger type:
	 * + wp log type describe WordpressHandler
	 *
	 *
	 *   === For other examples and recipes, visit https://github.com/Pierre-Lannoy/wp-traffic/blob/master/WP-CLI.md ===
	 *
	 */
	public static function handler( $args, $assoc_args ) {
		$stdout        = \WP_CLI\Utils\get_flag_value( $assoc_args, 'stdout', false );
		$format        = \WP_CLI\Utils\get_flag_value( $assoc_args, 'format', 'table' );
		$handler_types = new HandlerTypes();
		$handlers      = [];
		foreach ( $handler_types->get_all() as $key => $handler ) {
			if ( 'system' !== $handler['class'] ) {
				$handler['type']                        = $handler['id'];
				$handlers[strtolower( $handler['id'] )] = $handler;
			}
		}
		uasort(
			$handlers,
			function ( $a, $b ) {
				return strcmp( strtolower( $a[ 'name' ] ), strtolower( $b[ 'name' ] ) );
			}
		);
		$uuid   = '';
		$action = isset( $args[0] ) ? $args[0] : 'list';
		if ( isset( $args[1] ) ) {
			$uuid = strtolower( $args[1] );
			if ( ! array_key_exists( $uuid, $handlers ) ) {
				$uuid = '';
			}
		}
		if ( 'list' !== $action && '' === $uuid ) {
			self::error( 1, $stdout );
		}
		switch ( $action ) {
			case 'list':
				if ( 'ids' === $format ) {
					self::write_ids( $handlers, 'type' );
				} else {
					\WP_CLI\Utils\format_items( $format, $handlers, [ 'type', 'class', 'name', 'version' ] );
				}
				break;
			case 'describe':
				$example = [];
				$handler = $handlers[$uuid];
				\WP_CLI::line( '' );
				\WP_CLI::line( \WP_CLI::colorize( '%8' . $handler['name'] . ' - ' . $handler['id'] . '%n' ) );
				\WP_CLI::line( $handler['help'] );
				\WP_CLI::line( '' );
				\WP_CLI::line( \WP_CLI::colorize( '%UMinimal Level%n' ) );
				\WP_CLI::line( '' );
				\WP_CLI::line( '  ' .  strtolower( Log::level_name( $handler['minimal'] ) ) );
				\WP_CLI::line( '' );
				\WP_CLI::line( \WP_CLI::colorize( '%UParameters%n' ) );
				\WP_CLI::line( '' );
				$param = '  * ';
				$elem  = '    - ';
				$list  = '       ';
				\WP_CLI::line( $param . 'Name - Used only in admin dashboard.' );
				\WP_CLI::line( $elem . 'field name: name' );
				\WP_CLI::line( $elem . 'field type: string' );
				\WP_CLI::line( $elem . 'default value: "New Logger"' );
				\WP_CLI::line( '' );
				\WP_CLI::line( $param . 'Minimal level - Minimal reported level.' );
				\WP_CLI::line( $elem . 'field name: level' );
				\WP_CLI::line( $elem . 'field type: string' );
				\WP_CLI::line( $elem . 'default value: "' . strtolower( Log::level_name( $handler['minimal'] ) ) . '"' );
				\WP_CLI::line( $elem . 'available values:' );
				foreach ( Log::get_levels( EventTypes::$levels[ strtolower( Log::level_name( $handler['minimal'] ) ) ] ) as $level ) {
					\WP_CLI::line( $list . '"' . strtolower( $level[1] ) . '": ' . $level[2]);
				}
				\WP_CLI::line( '' );
				foreach ( $handler['configuration'] as $key => $conf ) {
					if ( ! $conf['show'] || ! $conf['control']['enabled'] ) {
						continue;
					}
					\WP_CLI::line( $param . $conf['name'] . ' - ' . $conf['help'] );
					\WP_CLI::line( $elem . 'field name: ' . $key );
					\WP_CLI::line( $elem . 'field type: ' . $conf['type'] );
					switch ( $conf['control']['type'] ) {
						case 'field_input_integer':
							\WP_CLI::line( $elem . 'default value: ' . $conf['default'] );
							\WP_CLI::line( $elem . 'range: [' . $conf['control']['min'] . '-' . $conf['control']['max'] . ']' );
							$example[] = '"' . $key . '": ' . $conf['default'];
							break;
						case 'field_checkbox':
							\WP_CLI::line( $elem . 'default value: ' . ( $conf['default'] ? 'true' : 'false' ) );
							$example[] = '"' . $key . '": ' . ( $conf['default'] ? 'true' : 'false' );
							break;
						case 'field_input_text':
							\WP_CLI::line( $elem . 'default value: "' . $conf['default'] . '"' );
							$example[] = '"' . $key . '": "' . $conf['default'] . '"';
							break;
						case 'field_select':
							switch ( $conf['control']['cast'] ) {
								case 'integer':
									\WP_CLI::line( $elem . 'default value: ' . $conf['default'] );
									$example[] = '"' . $key . '": ' . $conf['default'];
									break;
								case 'string':
									\WP_CLI::line( $elem . 'default value: "' . $conf['default'] . '"' );
									$example[] = '"' . $key . '": "' . $conf['default'] . '"';
									break;
							}
							\WP_CLI::line( $elem . 'available values:' );
							foreach ( $conf['control']['list'] as $point ) {
								switch ( $conf['control']['cast'] ) {
									case 'integer':
										\WP_CLI::line( $list . $point[0] . ': ' . $point[1]);
										break;
									case 'string':
										\WP_CLI::line( $list . '"' . $point[0] . '": ' . $point[1]);
										break;
								}
							}
							break;
					}
					\WP_CLI::line( '' );
				}
				\WP_CLI::line( $param . 'IP obfuscation - Log fields will contain hashes instead of real IPs.' );
				\WP_CLI::line( $elem . 'field name: obfuscation' );
				\WP_CLI::line( $elem . 'field type: boolean' );
				\WP_CLI::line( $elem . 'default value: false' );
				\WP_CLI::line( '' );
				\WP_CLI::line( $param . 'User pseudonymization - Log fields will contain hashes instead of user IDs & names.' );
				\WP_CLI::line( $elem . 'field name: pseudonymization' );
				\WP_CLI::line( $elem . 'field type: boolean' );
				\WP_CLI::line( $elem . 'default value: false' );
				\WP_CLI::line( '' );
				\WP_CLI::line( $param . 'Reported details: WordPress - Allows to log site, user and remote IP of the current request.' );
				\WP_CLI::line( $elem . 'field name: proc_wp' );
				\WP_CLI::line( $elem . 'field type: boolean' );
				\WP_CLI::line( $elem . 'default value: true' );
				\WP_CLI::line( '' );
				\WP_CLI::line( $param . 'Reported details: HTTP request - Allows to log url, method, referrer and remote IP of the current web request.' );
				\WP_CLI::line( $elem . 'field name: proc_http' );
				\WP_CLI::line( $elem . 'field type: boolean' );
				\WP_CLI::line( $elem . 'default value: true' );
				\WP_CLI::line( '' );
				\WP_CLI::line( $param . 'Reported details: PHP introspection - Allows to log line, file, class and function generating the event.' );
				\WP_CLI::line( $elem . 'field name: proc_php' );
				\WP_CLI::line( $elem . 'field type: boolean' );
				\WP_CLI::line( $elem . 'default value: true' );
				\WP_CLI::line( '' );
				\WP_CLI::line( $param . 'Reported details: Backtrace - Allows to log the full PHP and WordPress call stack.' );
				\WP_CLI::line( $elem . 'field name: proc_trace' );
				\WP_CLI::line( $elem . 'field type: boolean' );
				\WP_CLI::line( $elem . 'default value: false' );
				\WP_CLI::line( '' );
				\WP_CLI::line( \WP_CLI::colorize( '%UExample%n' ) );
				\WP_CLI::line( '' );
				\WP_CLI::line( '  {' . implode( ', ', $example ) . '}' );
				\WP_CLI::line( '' );
				break;
		}

	}

	/**
	 * Manage Decalog loggers.
	 *
	 * ## OPTIONS
	 *
	 * <list|start|pause|clean|purge|remove|add|set>
	 * : The action to take.
	 * ---
	 * options:
	 *  - list
	 *  - start
	 *  - pause
	 *  - clean
	 *  - purge
	 *  - remove
	 *  - add
	 *  - set
	 * ---
	 *
	 * [<uuid_or_type>]
	 * : The uuid of the logger to perform an action on or the type of the logger to add.
	 *
	 * [--settings=<settings>]
	 * : The settings needed by "add" and "modify" actions.
	 * MUST be a string containing a json configuration.
	 * ---
	 * default: '{}'
	 * example: '{"host": "syslog.collection.eu.sumologic.com", "timeout": 800, "ident": "DecaLog", "format": 1}'
	 * ---
	 *
	 * [--detail=<detail>]
	 * : The details of the output when listing loggers.
	 * ---
	 * default: short
	 * options:
	 *  - short
	 *  - full
	 * ---
	 *
	 * [--format=<format>]
	 * : Allows overriding the output of the command when listing loggers.
	 * ---
	 * default: table
	 * options:
	 *  - table
	 *  - json
	 *  - csv
	 *  - yaml
	 *  - ids
	 *  - count
	 * ---
	 *
	 * [--yes]
	 * : Answer yes to the confirmation message, if any.
	 *
	 * [--stdout]
	 * : Use clean STDOUT output to use results in scripts. Unnecessary when piping commands because piping is detected by DecaLog.
	 *
	 * ## EXAMPLES
	 *
	 * Lists configured loggers:
	 * + wp log logger list
	 * + wp log logger list --detail=full
	 * + wp log logger list --format=json
	 *
	 * Starts a logger:
	 * + wp log logger start 37cf1c00-d67d-4e7d-9518-e579f01407a7
	 *
	 * Pauses a logger:
	 * + wp log logger pause 37cf1c00-d67d-4e7d-9518-e579f01407a7
	 *
	 * Deletes old records of a logger:
	 * + wp log logger clean 37cf1c00-d67d-4e7d-9518-e579f01407a7
	 *
	 * Deletes all records of a logger:
	 * + wp log logger purge 37cf1c00-d67d-4e7d-9518-e579f01407a7
	 * + wp log logger purge 37cf1c00-d67d-4e7d-9518-e579f01407a7 --yes
	 *
	 * Permanently deletes a logger:
	 * + wp log logger remove 37cf1c00-d67d-4e7d-9518-e579f01407a7
	 * + wp log logger remove 37cf1c00-d67d-4e7d-9518-e579f01407a7 --yes
	 *
	 * Adds a new logger:
	 * + wp log logger add WordpressHandler --settings='{"rotate": 8000, "purge": 5, "level":"warning", "proc_wp": true}'
	 *
	 * Change the settings of a logger
	 * + wp log logger set 37cf1c00-d67d-4e7d-9518-e579f01407a7 --settings='{"proc_trace": false, "level":"warning"}'
	 *
	 *
	 *   === For other examples and recipes, visit https://github.com/Pierre-Lannoy/wp-traffic/blob/master/WP-CLI.md ===
	 *
	 */
	public static function logger( $args, $assoc_args ) {
		$stdout       = \WP_CLI\Utils\get_flag_value( $assoc_args, 'stdout', false );
		$format       = \WP_CLI\Utils\get_flag_value( $assoc_args, 'format', 'table' );
		$detail       = \WP_CLI\Utils\get_flag_value( $assoc_args, 'detail', 'short' );
		$uuid         = '';
		$ilog         = Log::bootstrap( 'plugin', DECALOG_PRODUCT_SHORTNAME, DECALOG_VERSION );
		$action       = isset( $args[0] ) ? $args[0] : 'list';
		$loggers_list = Option::network_get( 'loggers' );
		if ( isset( $args[1] ) ) {
			$uuid = $args[1];
			if ( 'add' === $action ) {
				$handler_types = new HandlerTypes();
				$t             = '';
				foreach ( $handler_types->get_all() as $handler ) {
					if ( 'system' !== $handler['class'] && strtolower( $uuid ) === strtolower( $handler['id'] ) ) {
						$t = $uuid;
					}
					if ( 'system' === $handler['class'] && strtolower( $uuid ) === strtolower( $handler['id'] ) ) {
						$t = 'system';
					}
				}
				$uuid = $t;
			} else {
				if ( ! array_key_exists( $uuid, $loggers_list ) ) {
					$uuid = '';
				} else {
					$handler_types = new HandlerTypes();
					foreach ( $handler_types->get_all() as $handler ) {
						if ( 'system' === $handler['class'] && $loggers_list[$uuid]['handler'] === $handler['id'] ) {
							$uuid = 'system';
						}
					}
				}
			}
		}
		if ( 'add' === $action && '' === $uuid ) {
			self::error( 1, $stdout );
		} elseif ( 'system' === $uuid ) {
			self::error( 3, $stdout );
		} elseif ( 'list' !== $action && '' === $uuid ) {
			self::error( 2, $stdout );
		}
		switch ( $action ) {
			case 'list':
				$handler_types   = new HandlerTypes();
				$processor_types = new ProcessorTypes();
				$loggers         = [];
				foreach ( $loggers_list as $key => $logger ) {
					$handler           = $handler_types->get( $logger['handler'] );
					$logger['type']    = $handler['name'];
					$logger['uuid']    = $key;
					$logger['level']   = strtolower( Log::level_name( $logger['level'] ) );
					$logger['running'] = $logger['running'] ? 'yes' : 'no';
					$list              = [ 'Standard' ];
					foreach ( $logger['processors'] as $processor ) {
						$list[] = $processor_types->get( $processor )['name'];
					}
					$logger['processors'] =  implode( ', ', $list );
					$loggers[]            = $logger;
				}
				usort(
					$loggers,
					function ( $a, $b ) {
						return strcmp( strtolower( $a[ 'name' ] ), strtolower( $b[ 'name' ] ) );
					}
				);
				if ( 'full' === $detail ) {
					$detail = [ 'uuid', 'type', 'name', 'running', 'level', 'processors' ];
				} else {
					$detail = [ 'uuid', 'type', 'name', 'running' ];
				}
				if ( 'ids' === $format ) {
					self::write_ids( $loggers, 'uuid' );
				} else {
					\WP_CLI\Utils\format_items( $format, $loggers, $detail );
				}
				break;
			case 'start':
				if ( $loggers_list[$uuid]['running'] ) {
					self::line( sprintf( 'The logger %s is already running.', $uuid ), $uuid, $stdout );
				} else {
					$loggers_list[$uuid]['running'] = true;
					Option::network_set( 'loggers', $loggers_list );
					$ilog->info( sprintf( 'Logger "%s" has started.', $loggers_list[ $uuid ]['name'] ) );
					self::success( sprintf( 'logger %s is now running.', $uuid ), $uuid, $stdout );
				}
				break;
			case 'pause':
				if ( ! $loggers_list[$uuid]['running'] ) {
					self::line( sprintf( 'The logger %s is already paused.', $uuid ), $uuid, $stdout );
				} else {
					$loggers_list[$uuid]['running'] = false;
					$ilog->info( sprintf( 'Logger "%s" has been paused.', $loggers_list[ $uuid ]['name'] ) );
					Option::network_set( 'loggers', $loggers_list );
					self::success( sprintf( 'logger %s is now paused.', $uuid ), $uuid, $stdout );
				}
				break;
			case 'purge':
				$loggers_list[$uuid]['uuid'] = $uuid;
				if ( 'WordpressHandler' !== $loggers_list[$uuid]['handler'] ) {
					self::warning( sprintf( 'logger %s can\'t be purged.', $uuid ), $uuid, $stdout );
				} else {
					\WP_CLI::confirm( sprintf( 'Are you sure you want to purge logger %s?', $uuid ), $assoc_args );
					$factory = new LoggerFactory();
					$factory->purge( $loggers_list[$uuid] );
					$ilog->notice( sprintf( 'Logger "%s" has been purged.', $loggers_list[ $uuid ]['name'] ) );
					self::success( sprintf( 'logger %s successfully purged.', $uuid ), $uuid, $stdout );
				}
				break;
			case 'clean':
				$loggers_list[$uuid]['uuid'] = $uuid;
				if ( 'WordpressHandler' !== $loggers_list[$uuid]['handler'] ) {
					self::warning( sprintf( 'logger %s can\'t be cleaned.', $uuid ), $uuid, $stdout );
				} else {
					$factory = new LoggerFactory();
					$count   = $factory->clean( $loggers_list[$uuid] );
					self::log( sprintf( '%d record(s) deleted.', $count ), $stdout );
					self::success( sprintf( 'logger %s successfully cleaned.', $uuid ), $uuid, $stdout );
				}
				break;
			case 'remove':
				$loggers_list[$uuid]['uuid'] = $uuid;
				\WP_CLI::confirm( sprintf( 'Are you sure you want to remove logger %s?', $uuid ), $assoc_args );
				$factory = new LoggerFactory();
				$factory->destroy( $loggers_list[$uuid] );
				$ilog->notice( sprintf( 'Logger "%s" has been removed.', $loggers_list[ $uuid ]['name'] ) );
				unset( $loggers_list[$uuid] );
				Option::network_set( 'loggers', $loggers_list );
				self::success( sprintf( 'logger %s successfully removed.', $uuid ), $uuid, $stdout );
				break;
			case 'add':
				$result = self::logger_add( $uuid, $assoc_args );
				if ( '' === $result ) {
					$ilog->error( 'Unable to add a logger.', 1 );
					self::error( 4, $stdout );
				} else {
					$loggers_list = Option::network_get( 'loggers' );
					$ilog->notice( sprintf( 'Logger "%s" has been saved.', $loggers_list[ $result ]['name'] ) );
					self::success( sprintf( 'logger %s successfully created.', $result ), $result, $stdout );
				}
				break;
			case 'set':
				$result = self::logger_modify( $uuid, $assoc_args );
				if ( '' === $result ) {
					$ilog->error( 'Unable to modify a logger.', 1 );
					self::error( 5, $stdout );
				} else {
					$loggers_list = Option::network_get( 'loggers' );
					$ilog->notice( sprintf( 'Logger "%s" has been saved.', $loggers_list[ $result ]['name'] ) );
					self::success( sprintf( 'logger %s successfully saved.', $result ), $result, $stdout );
				}
				break;
		}
	}

	/**
	 * Manage Decalog listener.
	 *
	 * ## OPTIONS
	 *
	 * <list|enable|disable|auto-on|auto-off>
	 * : The action to take.
	 * ---
	 * default: list
	 * options:
	 *  - list
	 *  - enable
	 *  - disable
	 *  - auto-on
	 *  - auto-off
	 * ---
	 *
	 * [<listener_id>]
	 * : The id of the listener to perform an action on.
	 *
	 * [--detail=<detail>]
	 * : The details of the output when listing listeners.
	 * ---
	 * default: short
	 * options:
	 *  - short
	 *  - full
	 * ---
	 *
	 * [--format=<format>]
	 * : Allows overriding the output of the command when listing listeners.
	 * ---
	 * default: table
	 * options:
	 *  - table
	 *  - json
	 *  - csv
	 *  - yaml
	 *  - ids
	 *  - count
	 * ---
	 *
	 * [--yes]
	 * : Answer yes to the confirmation message, if any.
	 *
	 * [--stdout]
	 * : Use clean STDOUT output to use results in scripts. Unnecessary when piping commands because piping is detected by DecaLog.
	 *
	 * ## EXAMPLES
	 *
	 * Lists configured listeners:
	 * + wp log listener list
	 * + wp log listener list --detail=full
	 * + wp log listener list --format=json
	 *
	 * Enables a listener:
	 * + wp log listener enable wpdb
	 *
	 * Disables a listener:
	 * wp log listener disable wpdb
	 *
	 * Activates auto-listening:
	 * + wp log listener auto-on
	 * + wp log listener auto-on --yes
	 *
	 * Deactivates auto-listening:
	 * + wp log listener auto-off
	 * + wp log listener auto-off --yes
	 *
	 *
	 *   === For other examples and recipes, visit https://github.com/Pierre-Lannoy/wp-traffic/blob/master/WP-CLI.md ===
	 *
	 */
	public static function listener( $args, $assoc_args ) {
		$stdout    = \WP_CLI\Utils\get_flag_value( $assoc_args, 'stdout', false );
		$format    = \WP_CLI\Utils\get_flag_value( $assoc_args, 'format', 'table' );
		$detail    = \WP_CLI\Utils\get_flag_value( $assoc_args, 'detail', 'short' );
		$activated = Option::network_get( 'listeners' );
		$listeners = [];
		foreach ( ListenerFactory::$infos as $listener ) {
			$listener['enabled']        = Option::network_get( 'autolisteners') ? 'auto' : ( in_array( $listener['id'], $activated, true ) ? 'yes' : 'no' );
			$listener['available']      = $listener['available'] ? 'yes' : 'no';
			$listeners[$listener['id']] = $listener;
		}
		uasort(
			$listeners,
			function ( $a, $b ) {
				return strcmp( strtolower( $a[ 'name' ] ), strtolower( $b[ 'name' ] ) );
			}
		);
		$uuid   = '';
		$ilog   = Log::bootstrap( 'plugin', DECALOG_PRODUCT_SHORTNAME, DECALOG_VERSION );
		$action = isset( $args[0] ) ? $args[0] : 'list';
		if ( isset( $args[1] ) ) {
			$uuid = strtolower( $args[1] );
			if ( ! array_key_exists( $uuid, $listeners ) ) {
				$uuid = '';
			}
		}
		if ( 'list' !== $action && 'auto-on' !== $action && 'auto-off' !== $action && '' === $uuid ) {
			self::error( 6, $stdout );
		}
		switch ( $action ) {
			case 'list':
				if ( 'full' === $detail ) {
					$detail = [ 'id', 'class', 'name', 'product', 'version', 'available', 'enabled' ];
				} else {
					$detail = [ 'id', 'name', 'available', 'enabled' ];
				}
				if ( 'ids' === $format ) {
					self::write_ids( $listeners, 'id' );
				} else {
					\WP_CLI\Utils\format_items( $format, $listeners, $detail );
				}
				break;
			case 'enable':
				if ( in_array( $uuid, $activated, true ) ) {
					self::line( sprintf( 'the listener %s is already enabled.', $uuid ), $uuid, $stdout );
				} else {
					$activated[] = $uuid;
					Option::network_set( 'listeners', $activated );
					$ilog->info( 'Listeners settings updated.' );
					self::success( sprintf( 'the listener %s is now enabled.', $uuid ), $uuid, $stdout );
				}
				break;
			case 'disable':
				if ( ! in_array( $uuid, $activated, true ) ) {
					self::line( sprintf( 'the listener %s is already disabled.', $uuid ), $uuid, $stdout );
				} else {
					$list = [];
					foreach ( $activated as $listener ) {
						if ( $listener !== $uuid ) {
							$list[] = $listener;
						}
					}
					Option::network_set( 'listeners', $list );
					$ilog->info( 'Listeners settings updated.' );
					self::success( sprintf( 'the listener %s is now disabled.', $uuid ), $uuid, $stdout );
				}
				break;
			case 'auto-on':
				if ( Option::network_get( 'autolisteners' ) ) {
					self::line( 'auto-listening is already activated.', '', $stdout );
				} else {
					\WP_CLI::confirm( 'Are you sure you want to activate auto-listening?', $assoc_args );
					Option::network_set( 'autolisteners', true );
					$ilog->info( 'Listeners settings updated.' );
					self::success( 'auto-listening is now activated.', '', $stdout );
				}
				break;
			case 'auto-off':
				if ( ! Option::network_get( 'autolisteners' ) ) {
					self::line( 'auto-listening is already deactivated.', '', $stdout );
				} else {
					\WP_CLI::confirm( 'Are you sure you want to deactivate auto-listening?', $assoc_args );
					Option::network_set( 'autolisteners', false );
					$ilog->info( 'Listeners settings updated.' );
					self::success( 'auto-listening is now deactivated.', '', $stdout );
				}
				break;
		}

	}

	/**
	 * Modify DecaLog main settings.
	 *
	 * <enable|disable>
	 * : The action to take.
	 *
	 * <early-loading|auto-logging|auto-start>
	 * : The setting to change.
	 *
	 * [--yes]
	 * : Answer yes to the confirmation message, if any.
	 *
	 * [--stdout]
	 * : Use clean STDOUT output to use results in scripts. Unnecessary when piping commands because piping is detected by DecaLog.
	 *
	 * ## EXAMPLES
	 *
	 * wp log settings enable auto-logging
	 * wp log settings disable early-loading --yes
	 *
	 *
	 *   === For other examples and recipes, visit https://github.com/Pierre-Lannoy/wp-traffic/blob/master/WP-CLI.md ===
	 *
	 */
	public static function settings( $args, $assoc_args ) {
		$stdout  = \WP_CLI\Utils\get_flag_value( $assoc_args, 'stdout', false );
		$action  = isset( $args[0] ) ? (string) $args[0] : '';
		$setting = isset( $args[1] ) ? (string) $args[1] : '';
		switch ( $action ) {
			case 'enable':
				switch ( $setting ) {
					case 'early-loading':
						Option::network_set( 'earlyloading', true );
						self::success( 'early-loading is now activated.', '', $stdout );
						break;
					case 'auto-start':
						Option::network_set( 'logger_autostart', true );
						self::success( 'auto-start is now activated.', '', $stdout );
						break;
					case 'auto-logging':
						Autolog::activate();
						self::success( 'auto-logging is now activated.', '', $stdout );
						break;
					default:
						self::error( 7, $stdout );
				}
				break;
			case 'disable':
				switch ( $setting ) {
					case 'early-loading':
						\WP_CLI::confirm( 'Are you sure you want to deactivate early-loading?', $assoc_args );
						Option::network_set( 'earlyloading', false );
						self::success( 'early-loading is now deactivated.', '', $stdout );
						break;
					case 'auto-start':
						\WP_CLI::confirm( 'Are you sure you want to deactivate auto-start?', $assoc_args );
						Option::network_set( 'logger_autostart', false );
						self::success( 'auto-start is now deactivated.', '', $stdout );
						break;
					case 'auto-logging':
						\WP_CLI::confirm( 'Are you sure you want to deactivate auto-logging?', $assoc_args );
						Autolog::deactivate();
						self::success( 'auto-logging is now deactivated.', '', $stdout );
						break;
					default:
						self::error( 7, $stdout );
				}
				break;
			default:
				self::error( 8, $stdout );
		}
	}

	/**
	 * Send a message to all running loggers.
	 *
	 * <info|notice|warning|error|critical|alert>
	 * : The level of the event.
	 *
	 * <message>
	 * : The message.
	 *
	 * [--code=<code>]
	 * : The code of the event. Must be a positive integer. Default is 0.
	 *
	 * ## EXAMPLES
	 *
	 * wp log send info 'This is an informational message'
	 * wp log send warning 'Page not found' --code=404
	 *
	 *
	 *   === For other examples and recipes, visit https://github.com/Pierre-Lannoy/wp-traffic/blob/master/WP-CLI.md ===
	 *
	 */
	public static function send( $args, $assoc_args ) {
		$level   = isset( $args[0] ) ? strtolower( $args[0] ) : '';
		$message = isset( $args[1] ) ? (string) $args[1] : '';
		$code    = isset( $assoc_args['code'] ) ? (int) $assoc_args['code'] : 0;
		if ( 0 > $code ) {
			$code = 0;
		}
		if ( ! in_array( $level, ['info', 'notice', 'warning', 'error', 'critical', 'alert' ], true ) ) {
			\WP_CLI::error( 'forbidden or unknown level.' );
		}
		$logger = Log::bootstrap( 'core', 'WP-CLI', defined( 'WP_CLI_VERSION' ) ? WP_CLI_VERSION : 'x' );
		$logger->log( $level, $message, $code );
		\WP_CLI::success( 'message sent.' );
	}

	/**
	 * Get information on exit codes.
	 *
	 * ## OPTIONS
	 *
	 * <list>
	 * : The action to take.
	 * ---
	 * options:
	 *  - list
	 * ---
	 *
	 * [--format=<format>]
	 * : Allows overriding the output of the command when listing types.
	 * ---
	 * default: table
	 * options:
	 *  - table
	 *  - json
	 *  - csv
	 *  - yaml
	 *  - ids
	 *  - count
	 * ---
	 *
	 * ## EXAMPLES
	 *
	 * Lists available exit codes:
	 * + wp log exitcode list
	 * + wp log exitcode list --format=json
	 *
	 *
	 *   === For other examples and recipes, visit https://github.com/Pierre-Lannoy/wp-traffic/blob/master/WP-CLI.md ===
	 *
	 */
	public static function exitcode( $args, $assoc_args ) {
		$stdout = \WP_CLI\Utils\get_flag_value( $assoc_args, 'stdout', false );
		$format = \WP_CLI\Utils\get_flag_value( $assoc_args, 'format', 'table' );
		$action = isset( $args[0] ) ? $args[0] : 'list';
		$codes  = [];
		foreach ( self::$exit_codes as $key => $msg ) {
			$codes[ $key ] = [ 'code' => $key, 'meaning' => ucfirst( $msg ) ];
		}
		switch ( $action ) {
			case 'list':
				if ( 'ids' === $format ) {
					self::write_ids( $codes );
				} else {
					\WP_CLI\Utils\format_items( $format, $codes, [ 'code', 'meaning' ] );
				}
				break;
		}
	}

	/**
	 * Display past or current calls.
	 *
	 * [<count>]
	 * : An integer value [1-60] indicating how many most recent calls to display. If 0 or nothing is supplied as value, a live session is launched, displaying calls as soon as they occur.
	 *
	 * [--direction=<direction>]
	 * : The directions to display.
	 * ---
	 * default: both
	 * options:
	 *  - both
	 *  - inbound
	 *  - outbound
	 * ---
	 *
	 *[--filter=<filter>]
	 * : The misc. filters to apply. Show only calls matching the specified pattern.
	 * MUST be a json string containing pairs "field":"regexp".
	 * ---
	 * default: '{}'
	 * available fields: 'authority', 'scheme', 'endpoint', 'verb', 'code', 'message', 'size', 'latency', 'site_id'
	 * example: '{"authority":"/wordpress\.org/", "verb":"/GET/"}'
	 * ---
	 *
	 * [--col=<columns>]
	 * : The Number of columns (char in a row) to display. Default is 160. Min is 80 and max is 400.
	 *
	 * [--soft]
	 * : Soften the colors to save your eyes.
	 *
	 * [--yes]
	 * : Answer yes to the confirmation message, if any.
	 *
	 * ## NOTES
	 *
	 * + This command needs shared memory support for PHP: the PHP module "shmop" must be activated in your PHP web configuration AND in your PHP command-line configuration.
	 * + This command relies on an internal monitor. If this monitor is not started at launch time, you will be prompted to starting it.
	 * + If the monitor has just been started there will not be much to display if <count> is different from 0...
	 * + In a live session, just use CTRL-C to terminate it.
	 *
	 * ## EXAMPLES
	 *
	 * wp api tail
	 * wp api tail 20
	 * wp api tail 20 --direction=outbound
	 * wp api tail --filter='{"authority":"/wordpress\.org/", "verb":"/GET/"}'
	 *
	 *
	 *   === For other examples and recipes, visit https://github.com/Pierre-Lannoy/wp-traffic/blob/master/WP-CLI.md ===
	 *
	 */
	public static function tail( $args, $assoc_args ) {
		if ( ! function_exists( 'shmop_open' ) || ! function_exists( 'shmop_read' ) || ! function_exists( 'shmop_write' ) || ! function_exists( 'shmop_delete' ) || ! function_exists( 'shmop_close' )) {
			\WP_CLI::error( 'unable to launch tail command, no shared memory manager found.' );
		}
		if ( ! Option::network_get( 'livelog' ) ) {
			\WP_CLI::warning( 'monitoring is currently disabled. The tail command needs monitoring...' );
			\WP_CLI::confirm( 'Would you like to enable monitoring and to resume command?', $assoc_args );
			Option::network_set( 'livelog', true );
		}
		$filters = [];
		$count   = isset( $args[0] ) ? (int) $args[0] : 0;
		if ( 0 > $count || 60 < $count ) {
			$count = 0;
		}
		$col = isset( $assoc_args['col'] ) ? (int) $assoc_args['col'] : 160;
		if ( 80 > $col ) {
			$col = 80;
		}
		if ( 400 < $col ) {
			$col = 400;
		}
		$filter = \json_decode( isset( $assoc_args['filter'] ) ? (string) $assoc_args['filter'] : '{}', true );
		if ( is_array( $filter ) ) {
			foreach( [ 'authority', 'scheme', 'endpoint', 'verb', 'code', 'message', 'size', 'latency', 'site_id' ] as $field ) {
				if ( array_key_exists( $field, $filter ) ) {
					$value = (string) $filter[$field];
					if ( '' === $value ) {
						continue;
					}
					$filters[$field] = $value;
				}
			}
		}
		$direction = isset( $assoc_args['direction'] ) ? (string) $assoc_args['direction'] : 'both';
		switch ( $direction ) {
			case 'inbound':
			case 'outbound':
				$filters['bound'] = $direction;
				break;
			default:
				if ( isset( $filters['bound'] ) ) {
					unset( $filters['bound'] );
				}
		}
		$records = Memory::read();
		if ( 0 === $count ) {
			Logger::notice( 'Live console launched.' );
			while ( true ) {
				self::records_display( self::records_filter( Memory::read(), $filters ), isset( $assoc_args['soft'] ), $col );
			}
		} else {
			self::records_display( array_slice( self::records_filter( $records, $filters ), -$count ), isset( $assoc_args['soft'] ), $col );
		}
	}

	/**
	 * Get the WP-CLI help file.
	 *
	 * @param   array $attributes  'style' => 'markdown', 'html'.
	 *                             'mode'  => 'raw', 'clean'.
	 * @return  string  The output of the shortcode, ready to print.
	 * @since 1.0.0
	 */
	public static function sc_get_helpfile( $attributes ) {
		$md = new Markdown();
		return $md->get_shortcode(  'WP-CLI.md', $attributes  );
	}

}

add_shortcode( 'traffic-wpcli', [ 'Traffic\Plugin\Feature\Wpcli', 'sc_get_helpfile' ] );

if ( defined( 'WP_CLI' ) && WP_CLI ) {
	\WP_CLI::add_command( 'api tail', [ Wpcli::class, 'tail' ] );
	/*\WP_CLI::add_command( 'log status', [ Wpcli::class, 'status' ] );
	\WP_CLI::add_command( 'log logger', [ Wpcli::class, 'logger' ] );
	\WP_CLI::add_command( 'log type', [ Wpcli::class, 'handler' ] );
	\WP_CLI::add_command( 'log listener', [ Wpcli::class, 'listener' ] );
	\WP_CLI::add_command( 'log tail', [ Wpcli::class, 'tail' ] );
	\WP_CLI::add_command( 'log settings', [ Wpcli::class, 'settings' ] );
	\WP_CLI::add_command( 'log send', [ Wpcli::class, 'send' ] );
	\WP_CLI::add_command( 'log exitcode', [ Wpcli::class, 'exitcode' ] );*/
}