<?php
/**
 * Plugin cache handling.
 *
 * @package System
 * @author  Pierre Lannoy <https://pierre.lannoy.fr/>.
 * @since   1.0.0
 * @noinspection PhpCSValidationInspection
 */

namespace Traffic\System;

/**
 * The class responsible to handle cache management.
 *
 * @package System
 * @author  Pierre Lannoy <https://pierre.lannoy.fr/>.
 * @since   1.0.0
 */
class Cache {

	/**
	 * The pool's name, specific to the calling plugin.
	 *
	 * @since  1.0.0
	 * @var    string    $pool_name    The pool's name.
	 */
	private static $pool_name = TRAFFIC_SLUG;

	/**
	 * Available TTLs.
	 *
	 * @since  1.0.0
	 * @var    array    $ttls    The TTLs array.
	 */
	private static $ttls;

	/**
	 * Default TTL.
	 *
	 * @since  1.0.0
	 * @var    integer    $default_ttl    The default TTL in seconds.
	 */
	private static $default_ttl = 3600;

	/**
	 * Initializes the class and set its properties.
	 *
	 * @since 1.0.0
	 */
	public function __construct() {
		self::init();
	}

	/**
	 * Initializes properties.
	 *
	 * @since 1.0.0
	 */
	public static function init() {
		self::$ttls = [
			'ephemeral'         => -1,
			'infinite'          => 10 * YEAR_IN_SECONDS,
			'diagnosis'         => HOUR_IN_SECONDS,
			'plugin-statistics' => DAY_IN_SECONDS,
		];
	}

	/**
	 * Get an ID for caching.
	 *
	 * @since 1.0.0
	 */
	public static function id( $args, $path = 'data/' ) {
		if ( '/' === $path[0] ) {
			$path = substr( $path, 1 );
		}
		if ( '/' !== $path[strlen( $path ) - 1] ) {
			$path = $path . '/';
		}
		return $path . md5( (string) $args );
	}

	/**
	 * Full item name.
	 *
	 * @param  string $item_name Item name. Expected to not be SQL-escaped.
	 * @param  boolean $blog_aware   Optional. Has the name must take care of blog.
	 * @param  boolean $locale_aware Optional. Has the name must take care of locale.
	 * @param  boolean $user_aware   Optional. Has the name must take care of user.
	 * @return string The full item name.
	 * @since  1.0.0
	 */
	private static function full_item_name( $item_name, $blog_aware = false, $locale_aware = false, $user_aware = false ) {
		$name = '';
		if ( $blog_aware ) {
			$name .= (string) get_current_blog_id() . '/';
		}
		if ( $locale_aware ) {
			$name .= (string) L10n::get_display_locale() . '/';
		}
		if ( $user_aware ) {
			$name .= (string) User::get_current_user_id() . '/';
		}
		$name .= $item_name;
		return substr( trim( $name ), 0, 172 - strlen( self::$pool_name ) );
	}

	/**
	 * Normalized item name.
	 *
	 * @param  string $item_name Item name. Expected to not be SQL-escaped.
	 * @return string The normalized item name.
	 * @since  1.0.0
	 */
	private static function normalized_item_name( $item_name ) {
		while ( 0 !== substr_count( $item_name, '//' ) ) {
			$item_name = str_replace( '//', '/', $item_name );
		}
		$item_name = str_replace( '/', '_', $item_name );
		return strtolower( $item_name );
	}

	/**
	 * Get the value of a fully named cache item.
	 *
	 * If the item does not exist, does not have a value, or has expired,
	 * then the return value will be false.
	 *
	 * @param  string $item_name Item name. Expected to not be SQL-escaped.
	 * @return mixed Value of item.
	 * @since  1.0.0
	 */
	private static function get_for_full_name( $item_name ) {
		if ( wp_using_ext_object_cache() ) {
			return wp_cache_get( $item_name, self::$pool_name );
		} else {
			return get_transient( self::$pool_name . '_' . self::normalized_item_name( $item_name ) );
		}
	}

	/**
	 * Get the value of a global cache item.
	 *
	 * If the item does not exist, does not have a value, or has expired,
	 * then the return value will be false.
	 *
	 * @param  string $item_name Item name. Expected to not be SQL-escaped.
	 * @return mixed Value of item.
	 * @since  1.0.0
	 */
	public static function get_global( $item_name ) {
		return self::get_for_full_name( self::full_item_name( $item_name ) );
	}

	/**
	 * Get the value of a standard cache item.
	 *
	 * If the item does not exist, does not have a value, or has expired,
	 * then the return value will be false.
	 *
	 * @param  string $item_name Item name. Expected to not be SQL-escaped.
	 * @param  boolean $blog_aware   Optional. Has the name must take care of blog.
	 * @param  boolean $locale_aware Optional. Has the name must take care of locale.
	 * @param  boolean $user_aware   Optional. Has the name must take care of user.
	 * @return mixed Value of item.
	 * @since  1.0.0
	 */
	public static function get( $item_name, $blog_aware = false, $locale_aware = false, $user_aware = false ) {
		return self::get_for_full_name( self::full_item_name( $item_name, $blog_aware, $locale_aware, $user_aware ) );
	}

	/**
	 * Set the value of a fully named cache item.
	 *
	 * You do not need to serialize values. If the value needs to be serialized, then
	 * it will be serialized before it is set.
	 *
	 * @param  string $item_name Item name. Expected to not be SQL-escaped.
	 * @param  mixed  $value     Item value. Must be serializable if non-scalar.
	 *                           Expected to not be SQL-escaped.
	 * @param  string $ttl       Optional. The previously defined ttl @see self::init().
	 * @return bool False if value was not set and true if value was set.
	 * @since  1.0.0
	 */
	private static function set_for_full_name( $item_name, $value, $ttl = 'default' ) {
		$expiration = self::$default_ttl;
		if ( array_key_exists( $ttl, self::$ttls ) ) {
			$expiration = self::$ttls[ $ttl ];
		}
		Logger::warning('SET     '  . self::normalized_item_name( $item_name ) . '   ' .  $value . '   ' . $expiration);
		if ( $expiration >= 0 ) {
			if ( wp_using_ext_object_cache() ) {
				return wp_cache_set( self::normalized_item_name( $item_name ), $value, self::$pool_name, $expiration );
			} else {
				return set_transient( self::$pool_name . '_' . self::normalized_item_name( $item_name ), $value, $expiration );
			}
		} else {
			return false;
		}
	}

	/**
	 * Set the value of a global cache item.
	 *
	 * You do not need to serialize values. If the value needs to be serialized, then
	 * it will be serialized before it is set.
	 *
	 * @param  string $item_name Item name. Expected to not be SQL-escaped.
	 * @param  mixed  $value     Item value. Must be serializable if non-scalar.
	 *                           Expected to not be SQL-escaped.
	 * @param  string $ttl       Optional. The previously defined ttl @see self::init().
	 * @return bool False if value was not set and true if value was set.
	 * @since  1.0.0
	 */
	public static function set_global( $item_name, $value, $ttl = 'default' ) {
		return self::set_for_full_name( self::full_item_name( $item_name ), $value, $ttl );
	}

	/**
	 * Set the value of a standard cache item.
	 *
	 * You do not need to serialize values. If the value needs to be serialized, then
	 * it will be serialized before it is set.
	 *
	 * @param  string  $item_name    Item name. Expected to not be SQL-escaped.
	 * @param  mixed   $value        Item value. Must be serializable if non-scalar.
	 *                               Expected to not be SQL-escaped.
	 * @param  string  $ttl          Optional. The previously defined ttl @see self::init().
	 * @param  boolean $blog_aware   Optional. Has the name must take care of blog.
	 * @param  boolean $locale_aware Optional. Has the name must take care of locale.
	 * @param  boolean $user_aware   Optional. Has the name must take care of user.
	 * @return bool False if value was not set and true if value was set.
	 * @since  1.0.0
	 */
	public static function set( $item_name, $value, $ttl = 'default', $blog_aware = false, $locale_aware = false, $user_aware = false ) {
		return self::set_for_full_name( self::full_item_name( $item_name, $blog_aware, $locale_aware, $user_aware ), $value, $ttl );
	}

	/**
	 * Delete the value of a fully named cache item.
	 *
	 * This function accepts generic car "*".
	 *
	 * @param  string $item_name Item name. Expected to not be SQL-escaped.
	 * @return integer Number of deleted items.
	 * @since  1.0.0
	 */
	private static function delete_for_ful_name( $item_name ) {
		global $wpdb;
		while ( 0 !== substr_count( $item_name, '//' ) ) {
			$item_name = str_replace( '//', '/', $item_name );
		}
		$item_name = str_replace( '/', '_', $item_name );
		$result    = 0;
		if ( strlen( $item_name ) - 1 === strpos( $item_name, '/*' ) && '/' === $item_name[0] ) {
			// phpcs:ignore
			$delete = $wpdb->get_col( "SELECT option_name FROM {$wpdb->options} WHERE option_name = '_transient_timeout_" . str_replace( '_*', '', $item_name ) . "' OR option_name LIKE '_transient_timeout_" . str_replace( '_*', '_%', $item_name ) . "';" );
		} else {
			// phpcs:ignore
			$delete = $wpdb->get_col( "SELECT option_name FROM {$wpdb->options} WHERE option_name = '_transient_timeout_" . $item_name . "';" );
		}
		foreach ( $delete as $transient ) {
			$key = str_replace( '_transient_timeout_', '', $transient );
			if ( delete_transient( $key ) ) {
				++$result;
			}
		}
		return $result;
	}

	/**
	 * Delete the value of a global cache item.
	 *
	 * This function accepts generic car "*".
	 *
	 * @param  string $item_name Item name. Expected to not be SQL-escaped.
	 * @return integer Number of deleted items.
	 * @since  1.0.0
	 */
	public static function delete_global( $item_name ) {
		return self::delete_for_ful_name( self::$pool_name . '/' . $item_name );
	}

	/**
	 * Delete the value of a standard cache item.
	 *
	 * This function accepts generic car "*".
	 *
	 * @param  string $item_name Item name. Expected to not be SQL-escaped.
	 * @return integer Number of deleted items.
	 * @since  1.0.0
	 */
	public static function delete( $item_name ) {
		return self::delete_for_ful_name( self::full_item_name( $item_name ) );
	}

}