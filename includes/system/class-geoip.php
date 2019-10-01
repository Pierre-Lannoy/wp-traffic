<?php
/**
 * GeoIP handling
 *
 * Handles all GeoIP operations.
 *
 * @package System
 * @author  Pierre Lannoy <https://pierre.lannoy.fr/>.
 * @since   1.0.0
 */

namespace Traffic\System;

use Traffic\System\Logger;

/**
 * Define the GeoIP functionality.
 *
 * Handles all GeoIP operations.
 *
 * @package System
 * @author  Pierre Lannoy <https://pierre.lannoy.fr/>.
 * @since   1.0.0
 */
class GeoIP {

	/**
	 * Already loaded raw flags.
	 *
	 * @since  1.0.0
	 * @var    array    $flags    Already loaded raw flags.
	 */
	private static $flags = [];

	/**
	 * Is IPv4 supported.
	 *
	 * @since  1.0.0
	 * @var    boolean    $ipv4    Is IPv4 supported.
	 */
	private $ipv4 = false;

	/**
	 * Is IPv6 supported.
	 *
	 * @since  1.0.0
	 * @var    boolean    $ipv6    Is IPv6 supported.
	 */
	private $ipv6 = false;

	/**
	 * The version of the provider.
	 *
	 * @since  1.0.0
	 * @var    string    $provider_version    The version of the provider.
	 */
	private $provider_version = '';

	/**
	 * The name of the provider.
	 *
	 * @since  1.0.0
	 * @var    string    $provider_name    The name of the provider.
	 */
	private $provider_name = '';

	/**
	 * The id of the provider.
	 *
	 * @since  1.0.0
	 * @var    string    $provider_id    The id of the provider.
	 */
	private $provider_id = '';

	/**
	 * Initializes the class and set its properties.
	 *
	 * @since 1.0.0
	 */
	public function __construct() {
		$this->detect();
	}

	/**
	 * Initializes the class and set its properties.
	 *
	 * @since 1.0.0
	 */
	private function detect() {
		if ( defined( 'GEOIP_DETECT_VERSION' ) && function_exists( 'geoip_detect2_get_info_from_ip' ) ) {
			$this->provider_id      = 'geoip-detect';
			$this->provider_name    = 'GeoIP Detection';
			$this->provider_version = GEOIP_DETECT_VERSION;
			$this->ipv4             = true;
			if ( defined( 'GEOIP_DETECT_IPV6_SUPPORTED' ) ) {
				$this->ipv6 = GEOIP_DETECT_IPV6_SUPPORTED;
			}
		}
	}

	/**
	 * Initializes the class and set its properties.
	 *
	 * @param   string $host The host name. May be an IP or an url.
	 * @return  null|string The ISO 3166-1 / Alpha 2 country code if detected, null otherwise.
	 * @since 1.0.0
	 */
	public function get_iso3166_alpha2( $host ) {
		if ( '' === $this->provider_id ) {
			return null;
		}
		$ip      = '';
		$country = null;
		if ( ! filter_var( $host, FILTER_VALIDATE_IP, FILTER_FLAG_IPV6 | FILTER_FLAG_IPV4 | FILTER_FLAG_NO_RES_RANGE | FILTER_FLAG_NO_PRIV_RANGE ) ) {
			$url_parts = wp_parse_url( 'http://' . $host );
			$host      = $url_parts['host'];
		}
		if ( ! filter_var( $host, FILTER_VALIDATE_IP, FILTER_FLAG_IPV6 | FILTER_FLAG_IPV4 ) ) {
			$host = gethostbyname( $host );
		}
		if ( filter_var( $host, FILTER_VALIDATE_IP, FILTER_FLAG_IPV4 | FILTER_FLAG_NO_RES_RANGE | FILTER_FLAG_NO_PRIV_RANGE ) ) {
			$ip = $host;
		}
		if ( '' === $ip && $this->ipv6 && filter_var( $host, FILTER_VALIDATE_IP, FILTER_FLAG_IPV6 | FILTER_FLAG_NO_RES_RANGE | FILTER_FLAG_NO_PRIV_RANGE ) ) {
			$ip = $host;
		}
		// GeoIP Detect.
		if ( '' !== $ip && 'geoip-detect' === $this->provider_id ) {
			$info    = geoip_detect2_get_info_from_ip( $ip );
			$country = strtoupper( $info->country->isoCode );
			if ( empty( $country ) || 2 !== strlen( $country ) ) {
				$country = strtoupper( $info->registeredCountry->isoCode );
			}
			if ( empty( $country ) || 2 !== strlen( $country ) ) {
				$country = strtoupper( $info->representedCountry->isoCode );
			}
			if ( empty( $country ) || 2 !== strlen( $country ) ) {
				$country = null;
			}
		}
		return $country;
	}

	/**
	 * Get a raw favicon.
	 *
	 * @param   string $name    Optional. The top domain of the site.
	 * @return  string  The raw value of the favicon.
	 * @since   1.0.0
	 */
	public static function get_raw( $name = 'wordpress.org' ) {
		$dir      = WP_CONTENT_DIR . '/cache/site-favicons/';
		$name     = strtolower( $name );
		$filename = $dir . $name . '.png';
		if ( array_key_exists( $name, self::$icons ) ) {
			return self::$icons[ $name ];
		}
		if ( ! file_exists( $dir ) ) {
			try {
				mkdir( $dir, 0755, true );
				Logger::info( 'Created: "' . $dir . '" favicons cache directory.' );
			} catch ( \Exception $ex ) {
				Logger::error( 'Unable to create "' . $dir . '" favicons cache directory.' );
				return self::get_default();
			}
		}
		if ( ! file_exists( $filename ) ) {
			$response = wp_remote_get( 'https://www.google.com/s2/favicons?domain=' . $name );
			if ( is_wp_error( $response ) ) {
				Logger::error( 'Unable to download "' . $name . '" favicon: ' . $response->get_error_message(), $response->get_error_code() );
				return self::get_default();
			}
			if ( 200 !== wp_remote_retrieve_response_code( $response ) ) {
				Logger::error( 'Unable to download "' . $name . '" favicon.', wp_remote_retrieve_response_code( $response ) );
				return self::get_default();
			}
			global $wp_filesystem;
			if ( empty( $wp_filesystem ) ) {
				require_once ABSPATH . '/wp-admin/includes/file.php';
				WP_Filesystem();
			}
			$wp_filesystem->put_contents(
				$filename,
				$response['body'],
				FS_CHMOD_FILE
			);
			if ( $wp_filesystem->errors->has_errors() ) {
				foreach ( $wp_filesystem->errors->get_error_messages() as $message ) {
					Logger::error( 'Unable to download "' . $name . '" favicon: ' . $message );
				}
				return self::get_default();
			}
			Logger::debug( 'Favicon downloaded for "' . $name . '".' );
		}
		// phpcs:ignore
		self::$icons[ $name ] = file_get_contents( $filename );
		return ( self::get_raw( $name ) );
	}

	/**
	 * Returns default (unknown) favicon.
	 *
	 * @return string The default favicon.
	 * @since 1.0.0
	 */
	private static function get_default() {
		return '';
	}

	/**
	 * Returns a base64 png resource for the icon.
	 *
	 * @param   string $name    Optional. The top domain of the site.
	 * @return string The resource as a base64.
	 * @since 1.0.0
	 */
	public static function get_base64( $name = 'wordpress.org' ) {
		$source = self::get_raw( $name );
		// phpcs:ignore
		return 'data:image/png;base64,' . base64_encode( $source );
	}

}
