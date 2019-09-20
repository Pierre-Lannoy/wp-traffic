<?php
/**
 * Traffic analytics
 *
 * Handles all analytics operations.
 *
 * @package Features
 * @author  Pierre Lannoy <https://pierre.lannoy.fr/>.
 * @since   1.0.0
 */

namespace Traffic\Plugin\Feature;

use Traffic\Plugin\Feature\Schema;
use Traffic\System\Role;
use Traffic\System\Logger;

/**
 * Define the analytics functionality.
 *
 * Handles all analytics operations.
 *
 * @package Features
 * @author  Pierre Lannoy <https://pierre.lannoy.fr/>.
 * @since   1.0.0
 */
class Analytics {

	/**
	 * The query filter.
	 *
	 * @since  1.0.0
	 * @var    array    $filter    The query filter.
	 */
	private $filter = [];

	/**
	 * The data.
	 *
	 * @since  1.0.0
	 * @var    array    $data    The data.
	 */
	private $data = [];

	/**
	 * Initialize the class and set its properties.
	 *
	 * @param   string $type    The type of analytics ( summary, domain, authority, endpoint, country).
	 * @param   string $context The context of analytics (both, inbound, outbound).
	 * @param   string $site    The site to analyze (all or ID).
	 * @param   string $start   The start date.
	 * @param   string $end     The end date.
	 * @since    1.0.0
	 */
	public function __construct( $type, $context, $site, $start, $end, $id = '' ) {
		if ( Role::LOCAL_ADMIN === Role::admin_type() ) {
			$site = get_current_blog_id();
		}
		if ( 'inbound' === $context || 'outbound' === $context ) {
			$this->filter[] = "context='" . $context . "'";
		}
		if ( 'all' !== $site ) {
			$this->filter[] = "site='" . $site . "'";
		}
		if ( $start === $end ) {
			$this->filter[] = "timestamp='" . $start . "'";
		} else {
			$this->filter[] = "timestamp>='" . $start . "' and timestamp<='" . $end . "'";
		}
		if ( '' !== $id ) {
			switch ( $type ) {
				case 'domain':
					$this->filter[] = "id='" . $id . "'";
					break;
				case 'authority':
					$this->filter[] = "authority='" . $id . "'";
					break;
				case 'endpoint':
					$this->filter[] = "endpoint='" . $id . "'";
					break;
				case 'country':
					$this->filter[] = "country='" . strtoupper( $id ) . "'";
					break;
			}
		}
	}

}
