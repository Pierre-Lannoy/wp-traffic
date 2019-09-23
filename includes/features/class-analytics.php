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
use Traffic\System\Cache;
use Traffic\System\Date;
use Traffic\System\Role;
use Traffic\System\Logger;
use Traffic\System\L10n;
use Feather;
use Traffic\System\Timezone;

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
	 * The unique ID of the instance.
	 *
	 * @since  1.0.0
	 * @var    string    $uniqid    The unique ID of the instance.
	 */
	private $uniqid = '';

	/**
	 * The dashboard type.
	 *
	 * @since  1.0.0
	 * @var    string    $title    The dashboard type.
	 */
	private $type = '';

	/**
	 * The queried ID.
	 *
	 * @since  1.0.0
	 * @var    string    $id    The queried ID.
	 */
	private $id = '';

	/**
	 * The queried site.
	 *
	 * @since  1.0.0
	 * @var    string    $site    The queried site.
	 */
	private $site = 'all';

	/**
	 * The start date.
	 *
	 * @since  1.0.0
	 * @var    string    $start    The start date.
	 */
	private $start = '';

	/**
	 * The end date.
	 *
	 * @since  1.0.0
	 * @var    string    $end    The end date.
	 */
	private $end = '';

	/**
	 * The timezone.
	 *
	 * @since  1.0.0
	 * @var    string    $timezone    The timezone.
	 */
	private $timezone = 'UTC';

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
	 * Is the start date today's date.
	 *
	 * @since  1.0.0
	 * @var    boolean    $today    Is the start date today's date.
	 */
	private $is_today = false;

	/**
	 * Has the dataset inbound context.
	 *
	 * @since  1.0.0
	 * @var    boolean    $has_inbound    Has the dataset inbound context.
	 */
	private $has_inbound = false;

	/**
	 * Has the dataset inbound context.
	 *
	 * @since  1.0.0
	 * @var    boolean    $has_outbound    Has the dataset inbound context.
	 */
	private $has_outbound = false;

	/**
	 * Is the inbound context in query.
	 *
	 * @since  1.0.0
	 * @var    boolean    $has_inbound    Is the inbound context in query.
	 */
	private $is_inbound = false;

	/**
	 * Is the outbound context in query.
	 *
	 * @since  1.0.0
	 * @var    boolean    $has_outbound    Is the outbound context in query.
	 */
	private $is_outbound = false;

	/**
	 * Initialize the class and set its properties.
	 *
	 * @param   string $type    The type of analytics ( summary, domain, authority, endpoint, country).
	 * @param   string $context The context of analytics (both, inbound, outbound).
	 * @param   string $site    The site to analyze (all or ID).
	 * @param   string $start   The start date.
	 * @param   string $end     The end date.
	 * @param   string $id      The queried ID.
	 * @since    1.0.0
	 */
	public function __construct( $type, $context, $site, $start, $end, $id = '' ) {
		$this->uniqid = substr( uniqid( '', true ), 10, 13 );
		$this->id     = $id;
		$this->site   = $site;
		if ( Role::LOCAL_ADMIN === Role::admin_type() ) {
			$site = get_current_blog_id();
		}
		if ( 'all' !== $site ) {
			$this->filter[] = "site='" . $site . "'";
		}
		if ( $start === $end ) {
			$this->filter[] = "timestamp='" . $start . "'";
		} else {
			$this->filter[] = "timestamp>='" . $start . "' and timestamp<='" . $end . "'";
		}
		$this->start = $start;
		$this->end   = $end;
		$this->type  = 'summary';
		if ( '' !== $id ) {
			$this->type = $type;
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
				default:
					$this->type = 'summary';
			}
		}
		$this->timezone     = Timezone::network_get();
		$datetime           = new \DateTime( 'now', $this->timezone );
		$this->is_today     = ( $this->start === $datetime->format( 'Y-m-d' ) );
		$bounds             = Schema::get_distinct_context( $this->filter, ! $this->is_today );
		$this->has_inbound  = ( in_array( 'inbound', $bounds, true ) );
		$this->has_outbound = ( in_array( 'outbound', $bounds, true ) );
		$this->is_inbound   = ( 'inbound' === $context || 'both' === $context );
		$this->is_outbound  = ( 'outbound' === $context || 'both' === $context );
		if ( 'inbound' === $context && ! $this->has_inbound ) {
			$this->is_inbound  = false;
			$this->is_outbound = true;
		}
		if ( 'outbound' === $context && ! $this->has_outbound ) {
			$this->is_inbound  = true;
			$this->is_outbound = false;
		}
		if ( $this->is_inbound xor $this->is_outbound ) {
			$context = 'outbound';
			if ( $this->is_inbound ) {
				$context = 'inbound';
			}
			$this->filter[] = "context='" . $context . "'";
		}
		add_action( 'wp_ajax_traffic_' . $this->uniqid, [ $this, 'statistics_callback' ] );
	}

	/**
	 * Get the title bar.
	 *
	 * @return string  The bar ready to print.
	 * @since    1.0.0
	 */
	public function get_title_bar() {
		switch ( $this->type ) {
			case 'summary':
				$title    = esc_html__( 'Summary', 'traffic' );
				$subtitle = '';
				break;
			case 'domain':
				$title    = esc_html__( 'Top Domain', 'traffic' );
				$subtitle = $this->id;
				break;
			case 'authority':
				$title    = esc_html__( 'Service', 'traffic' );
				$subtitle = $this->id;
				break;
			case 'endpoint':
				$title    = esc_html__( 'Endpoint', 'traffic' );
				$subtitle = $this->id;
				break;
			case 'country':
				$title    = esc_html__( 'Country', 'traffic' );
				$subtitle = L10n::get_country_name( $this->id );
				break;
		}
		if ( 'summary' === $this->type ) {
			$home = '';
		} else {
			$home = '<a href="' . $this->get_url( [ 'type', 'id' ] ) . '"><img style="width:20px;vertical-align:middle;" src="' . Feather\Icons::get_base64( 'home', 'none', '#73879C' ) . '" /></a>&nbsp;<img style="width:16px;vertical-align:middle;" src="' . Feather\Icons::get_base64( 'chevron-right', 'none', '#73879C' ) . '" />';
		}
		$result  = '<div class="traffic-box traffic-box-full-line">';
		$result .= '<span class="traffic-home">' . $home . '</span>';
		$result .= '<span class="traffic-title">' . $title . '</span>';
		$result .= '<span class="traffic-subtitle">' . $subtitle . '</span>';
		$result .= '<span class="traffic-datepicker">' . $this->get_date_box() . '</span>';
		$result .= '<span class="traffic-switch">' . $this->get_switch_box( 'inbound' ) . '</span>';
		$result .= '<span class="traffic-switch">' . $this->get_switch_box( 'outbound' ) . '</span>';
		$result .= '</div>';
		return $result;
	}

	/**
	 * Get the KPI bar.
	 *
	 * @return string  The bar ready to print.
	 * @since    1.0.0
	 */
	public function get_kpi_bar() {
		$result  = '<div class="traffic-box traffic-box-full-line">';
		$result  .= '<div class="traffic-kpi-bar">';
		$result .= '<div class="traffic-kpi-large">' . $this->get_large_kpi( 'call' ) . '</div>';
		$result .= '<div class="traffic-kpi-large">' . $this->get_large_kpi( 'data' ) . '</div>';
		$result .= '<div class="traffic-kpi-large">' . $this->get_large_kpi( 'server' ) . '</div>';
		$result .= '<div class="traffic-kpi-large">' . $this->get_large_kpi( 'quota' ) . '</div>';
		$result .= '<div class="traffic-kpi-large">' . $this->get_large_kpi( 'pass' ) . '</div>';
		$result .= '<div class="traffic-kpi-large">' . $this->get_large_kpi( 'uptime' ) . '</div>';
		$result .= '</div>';
		$result .= '</div>';
		return $result;
	}

	/**
	 * Get a large kpi box.
	 *
	 * @param   string $kpi     The kpi to render.
	 * @return string  The box ready to print.
	 * @since    1.0.0
	 */
	private function get_large_kpi( $kpi ) {
		switch ( $kpi ) {
			case 'call':
				$icon  = Feather\Icons::get_base64( 'hash', 'none', '#73879C' );
				$title = esc_html_x( 'Number of Calls', 'Noun - Number API calls.', 'traffic' );
				$help  = esc_html__( 'Number of API calls', 'traffic' );
				break;
			case 'data':
				$icon  = Feather\Icons::get_base64( 'activity', 'none', '#73879C' );
				$title = esc_html_x( 'Data Volume', 'Noun - Volume of transferred data.', 'traffic' );
				$help  = esc_html__( 'Volume of transferred data', 'traffic' );
				break;
			case 'server':
				$icon  = Feather\Icons::get_base64( 'x-octagon', 'none', '#73879C' );
				$title = esc_html_x( 'Server Error Rate', 'Noun - Ratio of the number of HTTP errors to the total number of calls.', 'traffic' );
				$help  = esc_html__( 'Ratio of the number of HTTP errors to the total number of calls.', 'traffic' );
				break;
			case 'quota':
				$icon  = Feather\Icons::get_base64( 'shield-off', 'none', '#73879C' );
				$title = esc_html_x( 'Quotas Error Rate', 'Noun - Ratio of the quota enforcement number to the total number of calls.', 'traffic' );
				$help  = esc_html__( 'Ratio of the quota enforcement number to the total number of calls.', 'traffic' );
				break;
			case 'pass':
				$icon  = Feather\Icons::get_base64( 'check-circle', 'none', '#73879C' );
				$title = esc_html_x( 'Effective Pass Rate', 'Noun - Ratio of the number of HTTP success to the total number of calls.', 'traffic' );
				$help  = esc_html__( 'Ratio of the number of HTTP success to the total number of calls.', 'traffic' );
				break;
			case 'uptime':
				$icon  = Feather\Icons::get_base64( 'power', 'none', '#73879C' );
				$title = esc_html_x( 'Perceived Uptime', 'Noun - Perceived uptime, from the viewpoint of the site.', 'traffic' );
				$help  = esc_html__( 'Perceived uptime, from the viewpoint of the site.', 'traffic' );
				break;
		}
		$top = '<img style="width:12px;vertical-align:baseline;"src="' . $icon . '" />&nbsp;&nbsp;<span style="cursor:help;" class="traffic-kpi-large-top-text bottom" data-position="bottom" data-tooltip="' . $help . '">' . $title . '</span>';

		$result = '<div class="traffic-kpi-large-top">' . $top . '</div>';
		$result .= '<div class="traffic-kpi-large-middle">' . 'MIDDLE' . '</div>';
		$result .= '<div class="traffic-kpi-large-bottom">' . 'BOTTOM' . '</div>';

		return $result;
	}

	/**
	 * Get the url.
	 *
	 * @param   array $exclude Optional. The args to exclude.
	 * @return string  The url.
	 * @since    1.0.0
	 */
	private function get_url( $exclude = [] ) {
		$params         = [];
		$params['type'] = $this->type;
		$params['site'] = $this->site;
		if ( '' !== $this->id ) {
			$params['id'] = $this->id;
		}
		$params['start'] = $this->start;
		$params['end']   = $this->end;
		if ( ! ( $this->is_inbound && $this->is_outbound ) ) {
			if ( $this->is_inbound ) {
				$params['context'] = 'inbound';
			}
			if ( $this->is_outbound ) {
				$params['context'] = 'outbound';
			}
		}
		foreach ( $exclude as $arg ) {
			unset( $params[ $arg ] );
		}
		$url = admin_url( 'tools.php?page=traffic-viewer' );
		foreach ( $params as $key => $arg ) {
			$url .= '&' . $key . '=' . $arg;
		}
		return $url;
	}

	/**
	 * Get a large kpi box.
	 *
	 * @return string  The box ready to print.
	 * @since    1.0.0
	 */
	private function get_switch_box( $bound ) {
		$enabled = false;
		$other   = false;
		$other_t = 'both';
		if ( 'inbound' === $bound ) {
			$enabled = $this->has_inbound;
			$other   = $this->is_outbound;
			$other_t = 'outbound';
		}
		if ( 'outbound' === $bound ) {
			$enabled = $this->has_outbound;
			$other   = $this->is_inbound;
			$other_t = 'inbound';
		}
		if ( $enabled ) {
			$opacity = '';
			if ( 'inbound' === $bound ) {
				$checked = $this->is_inbound;
			}
			if ( 'outbound' === $bound ) {
				$checked = $this->is_outbound;
			}
		} else {
			$opacity = ' style="opacity:0.4"';
			$checked = false;
		}
		$result = '<input type="checkbox" class="traffic-input-' . $bound . '-switch"' . ( $checked ? ' checked' : '' ) . ' />';
		// phpcs:ignore
		$result .= '&nbsp;<span class="traffic-text-' . $bound . '-switch"' . $opacity . '>' . esc_html__( $bound, 'traffic' ) . '</span>';
		$result .= '<script>';
		$result .= 'jQuery(function ($) {';
		$result .= ' var elem = document.querySelector(".traffic-input-' . $bound . '-switch");';
		$result .= ' var params = {size: "small", color: "#5A738E", disabledOpacity:0.6 };';
		$result .= ' var ' . $bound . ' = new Switchery(elem, params);';
		if ( $enabled ) {
			$result .= ' ' . $bound . '.enable();';
		} else {
			$result .= ' ' . $bound . '.disable();';
		}
		$result .= ' elem.onchange = function() {';
		$result .= '  var url="' . $this->get_url( [ 'context' ] ) . '";';
		if ( $other ) {
			$result .= ' if (!elem.checked) {url = url + "&context=' . $other_t . '";}';
		} else {
			$result .= ' if (elem.checked) {url = url + "&context=' . $other_t . '";}';
		}
		$result .= '  $(location).attr("href", url);';
		$result .= ' };';
		$result .= '});';
		$result .= '</script>';
		return $result;
	}

	/**
	 * Get a date picker box.
	 *
	 * @return string  The box ready to print.
	 * @since    1.0.0
	 */
	private function get_date_box() {
		$result  = '<img style="width:13px;vertical-align:middle;" src="' . Feather\Icons::get_base64( 'calendar', 'none', '#5A738E' ) . '" />&nbsp;&nbsp;<span class="traffic-datepicker-value"></span>';
		$result .= '<script>';
		$result .= 'jQuery(function ($) {';
		$result .= ' moment.locale("' . L10n::get_display_locale() . '");';
		$result .= ' var start = moment("' . $this->start . '");';
		$result .= ' var end = moment("' . $this->end . '");';
		$result .= ' function changeDate(start, end) {';
		$result .= '  $("span.traffic-datepicker-value").html(start.format("ll") + " - " + end.format("ll"));';
		$result .= ' }';
		$result .= ' $(".traffic-datepicker").daterangepicker({';
		$result .= '  opens: "left",';
		$result .= '  startDate: start,';
		$result .= '  endDate: end,';
		$result .= '  minDate: moment("' . Schema::get_oldest_date() . '"),';
		$result .= '  maxDate: moment(),';
		$result .= '  showCustomRangeLabel: true,';
		$result .= '  alwaysShowCalendars: true,';
		$result .= '  locale: {cancelLabel: "' . esc_html__( 'Cancel', 'traffic' ) . '", applyLabel: "' . esc_html__( 'Apply', 'traffic' ) . '"},';
		$result .= '  ranges: {';
		$result .= '    "' . esc_html__( 'Today', 'traffic' ) . '": [moment(), moment()],';
		$result .= '    "' . esc_html__( 'Yesterday', 'traffic' ) . '": [moment().subtract(1, "days"), moment().subtract(1, "days")],';
		$result .= '    "' . esc_html__( 'This Month', 'traffic' ) . '": [moment().startOf("month"), moment().endOf("month")],';
		$result .= '    "' . esc_html__( 'Last Month', 'traffic' ) . '": [moment().subtract(1, "month").startOf("month"), moment().subtract(1, "month").endOf("month")],';
		$result .= '  }';
		$result .= ' }, changeDate);';
		$result .= ' changeDate(start, end);';
		$result .= ' $(".traffic-datepicker").on("apply.daterangepicker", function(ev, picker) {';
		$result .= '  var url = "' . $this->get_url( [ 'start', 'end' ] ) . '" + "&start=" + picker.startDate.format("YYYY-MM-DD") + "&end=" + picker.endDate.format("YYYY-MM-DD");';
		$result .= '  $(location).attr("href", url);';
		$result .= ' });';
		$result .= '});';
		$result .= '</script>';
		return $result;
	}

}
