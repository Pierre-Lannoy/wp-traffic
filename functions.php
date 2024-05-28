<?php
/**
 * Global functions.
 *
 * @package Functions
 * @author  Pierre Lannoy <https://pierre.lannoy.fr/>.
 * @since   2.0.0
 */

if ( ! function_exists('decalog_get_psr_log_version') ) {
	/**
	 * Get the needed version of PSR-3.
	 *
	 * @return  int  The PSR-3 needed version.
	 * @since 4.0.0
	 */
	function decalog_get_psr_log_version() {
		$required = 1;
		if ( ! defined( 'DECALOG_PSR_LOG_VERSION') ) {
			define( 'DECALOG_PSR_LOG_VERSION', 'V1' );
		}
		switch ( strtolower( DECALOG_PSR_LOG_VERSION ) ) {
			case 'v3':
				$required = 3;
				break;
			case 'auto':
				if ( class_exists( '\Psr\Log\NullLogger') ) {
					$reflection = new \ReflectionMethod(\Psr\Log\NullLogger::class, 'log');
					foreach ( $reflection->getParameters() as $param ) {
						if ( 'message' === $param->getName() ) {
							if ( str_contains($param->getType() ?? '', '|') ) {
								$required = 3;
							}
						}
					}
				}
		}
		return $required;
	}
}

/**
 * Multibyte String Pad
 *
 * Functionally, the equivalent of the standard str_pad function, but is capable of successfully padding multibyte strings.
 *
 * @param string $input The string to be padded.
 * @param int $length The length of the resultant padded string.
 * @param string $padding The string to use as padding. Defaults to space.
 * @param int $padType The type of padding. Defaults to STR_PAD_RIGHT.
 * @param string $encoding The encoding to use, defaults to UTF-8.
 *
 * @return string A padded multibyte string.
 * @since   2.0.0
 */
function traffic_mb_str_pad( $input, $length, $padding = ' ', $padType = STR_PAD_RIGHT, $encoding = 'UTF-8' ) {
	$result = $input;
	if ( ( $padding_required = $length - mb_strlen( $input, $encoding ) ) > 0 ) {
		switch ( $padType ) {
			case STR_PAD_LEFT:
				$result =
					mb_substr( str_repeat( $padding, $padding_required ), 0, $padding_required, $encoding ) .
					$input;
				break;
			case STR_PAD_RIGHT:
				$result =
					$input .
					mb_substr( str_repeat( $padding, $padding_required ), 0, $padding_required, $encoding );
				break;
			case STR_PAD_BOTH:
				$left_padding_length  = floor( $padding_required / 2 );
				$right_padding_length = $padding_required - $left_padding_length;
				$result             =
					mb_substr( str_repeat( $padding, $left_padding_length ), 0, $left_padding_length, $encoding ) .
					$input .
					mb_substr( str_repeat( $padding, $right_padding_length ), 0, $right_padding_length, $encoding );
				break;
		}
	}
	return $result;
}

/**
 * Close a shmop resource.
 *
 * @since 2.8.0
 *
 * @param mixed $shmop  The shmop resource to close.
 */
function traffic_shmop_close( $shmop ){
	if ( defined( 'PHP_VERSION' ) && version_compare( PHP_VERSION, '8.0.0', '<' ) ) {
		shmop_close( $shmop );
	}
}

/**
 * Verify if a resource is a shmop resource.
 *
 * @since 2.8.0
 *
 * @param mixed     $value  URL to retrieve.
 * @return bool     True if it's a shmop resource, false otherwise.
 */
function traffic_is_shmop_resource( $value ) {
	if ( class_exists( 'Shmop' ) ) {
		return $value instanceof Shmop;
	}
	return ( is_resource( $value ) );
}
