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
use Traffic\System\Conversion;
use Traffic\System\Role;
use Traffic\System\Logger;
use Traffic\System\L10n;
use Traffic\System\Http;
use Traffic\System\Favicon;
use Traffic\System\Timezone;
use Traffic\System\UUID;
use Feather;


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
	 * The domain name.
	 *
	 * @since  1.0.0
	 * @var    string    $domain    The domain name.
	 */
	public $domain = '';

	/**
	 * The subdomain name.
	 *
	 * @since  1.0.0
	 * @var    string    $subdomain    The subdomain name.
	 */
	public $subdomain = '';

	/**
	 * The dashboard type.
	 *
	 * @since  1.0.0
	 * @var    string    $title    The dashboard type.
	 */
	public $type = '';

	/**
	 * The dashboard extra.
	 *
	 * @since  1.0.0
	 * @var    string    $extra    The dashboard extra.
	 */
	public $extra = '';

	/**
	 * The dashboard context.
	 *
	 * @since  1.0.0
	 * @var    string    $context    The dashboard context.
	 */
	private $context = '';

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
	 * The period duration in seconds.
	 *
	 * @since  1.0.0
	 * @var    integer    $duration    The period duration in seconds.
	 */
	private $duration = 0;

	/**
	 * The timezone.
	 *
	 * @since  1.0.0
	 * @var    string    $timezone    The timezone.
	 */
	private $timezone = 'UTC';

	/**
	 * The main query filter.
	 *
	 * @since  1.0.0
	 * @var    array    $filter    The main query filter.
	 */
	private $filter = [];

	/**
	 * The query filter fro the previous range.
	 *
	 * @since  1.0.0
	 * @var    array    $previous    The query filter fro the previous range.
	 */
	private $previous = [];

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
	 * Colors for graphs.
	 *
	 * @since  1.0.0
	 * @var    array    $colors    The colors array.
	 */
	private $colors = [ '#27B999', '#3398DB', '#73879C', '#9B59B6', '#BDC3C6',];

	/**
	 * Initialize the class and set its properties.
	 *
	 * @param   string  $domain  The domain name, if disambiguation is needed.
	 * @param   string  $type    The type of analytics ( summary, domain, authority, endpoint, country).
	 * @param   string  $context The context of analytics (both, inbound, outbound).
	 * @param   string  $site    The site to analyze (all or ID).
	 * @param   string  $start   The start date.
	 * @param   string  $end     The end date.
	 * @param   string  $id      The queried ID.
	 * @param   boolean $reload  Is it a reload of an already displayed analytics.
	 * @param   string  $extra   The extra view to render.
	 * @since    1.0.0
	 */
	public function __construct( $domain, $type, $context, $site, $start, $end, $id, $reload, $extra ) {
		$this->id      = $id;
		$this->extra   = $extra;
		$this->context = $context;
		if ( Role::LOCAL_ADMIN === Role::admin_type() ) {
			$site = get_current_blog_id();
		}
		$this->site = $site;
		if ( 'all' !== $site ) {
			$this->filter[]   = "site='" . $site . "'";
			$this->previous[] = "site='" . $site . "'";
		}
		if ( '' !== $domain ) {
			$this->domain     = $domain;
			$this->filter[]   = "id='" . $domain . "'";
			$this->previous[] = "id='" . $domain . "'";
		}
		if ( $start === $end ) {
			$this->filter[] = "timestamp='" . $start . "'";
		} else {
			$this->filter[] = "timestamp>='" . $start . "' and timestamp<='" . $end . "'";
		}
		$this->start = $start;
		$this->end   = $end;
		$this->type  = $type;
		if ( '' !== $id ) {
			switch ( $type ) {
				case 'domain':
				case 'authorities':
					$this->filter[]   = "id='" . $id . "'";
					$this->previous[] = "id='" . $id . "'";
					break;
				case 'authority':
				case 'endpoints':
					$this->filter[]   = "authority='" . $id . "'";
					$this->previous[] = "authority='" . $id . "'";
					$this->subdomain  = Schema::get_authority( $this->filter );
					break;
				case 'endpoint':
					$this->filter[]   = "endpoint='" . $id . "'";
					$this->previous[] = "endpoint='" . $id . "'";
					$this->subdomain  = Schema::get_authority( $this->filter );
					break;
				case 'country':
					$this->filter[]   = "country='" . strtoupper( $id ) . "'";
					$this->previous[] = "country='" . strtoupper( $id ) . "'";
					break;
				default:
					$this->type = 'summary';
			}
		}
		if ( '' !== $domain && 'domain' !== $type && 'authorities' !== $type ) {
			$this->domain     = $domain;
			$this->filter[]   = "id='" . $domain . "'";
			$this->previous[] = "id='" . $domain . "'";
		}
		$this->timezone     = Timezone::network_get();
		$datetime           = new \DateTime( 'now', $this->timezone );
		$this->is_today     = ( $this->start === $datetime->format( 'Y-m-d' ) || $this->end === $datetime->format( 'Y-m-d' ) );
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
			$this->filter[]   = "context='" . $context . "'";
			$this->previous[] = "context='" . $context . "'";
		}
		$start = new \DateTime( $this->start, $this->timezone );
		$end   = new \DateTime( $this->end, $this->timezone );
		$start->sub( new \DateInterval( 'P1D' ) );
		$end->sub( new \DateInterval( 'P1D' ) );
		$delta = $start->diff( $end, true );
		if ( $delta ) {
			$start->sub( $delta );
			$end->sub( $delta );
		}
		$this->duration = $delta->d + 1;
		if ( $start === $end ) {
			$this->previous[] = "timestamp='" . $start->format( 'Y-m-d' ) . "'";
		} else {
			$this->previous[] = "timestamp>='" . $start->format( 'Y-m-d' ) . "' and timestamp<='" . $end->format( 'Y-m-d' ) . "'";
		}
	}

	/**
	 * Query statistics table.
	 *
	 * @param   string $query   The query type.
	 * @param   mixed  $queried The query params.
	 * @return array  The result of the query, ready to encode.
	 * @since    1.0.0
	 */
	public function query( $query, $queried ) {
		switch ( $query ) {
			case 'kpi':
				return $this->query_kpi( $queried );
			case 'top-domains':
				return $this->query_top( 'domains', (int) $queried );
			case 'top-authorities':
				return $this->query_top( 'authorities', (int) $queried );
			case 'top-endpoints':
				return $this->query_top( 'endpoints', (int) $queried );
			case 'domains':
				return $this->query_list( 'domains' );
			case 'authorities':
				return $this->query_list( 'authorities' );
			case 'endpoints':
				return $this->query_list( 'endpoints' );
			case 'codes':
				return $this->query_list( 'codes' );
			case 'schemes':
				return $this->query_list( 'schemes' );
			case 'methods':
				return $this->query_list( 'methods' );
			case 'countries':
				return $this->query_list( 'countries' );
			case 'code':
				return $this->query_pie( 'code', (int) $queried );
			case 'security':
				return $this->query_pie( 'security', (int) $queried );
			case 'method':
				return $this->query_pie( 'method', (int) $queried );
		}
		return [];
	}

	/**
	 * Query statistics table.
	 *
	 * @param   string  $type    The type of pie.
	 * @param   integer $limit  The number to display.
	 * @return array  The result of the query, ready to encode.
	 * @since    1.0.0
	 */
	private function query_pie( $type, $limit ) {
		$extra_field = '';
		$extra       = [];
		$not         = false;
		$uuid        = UUID::generate_unique_id( 5 );
		switch ( $type ) {
			case 'code':
				$group       = 'code';
				$follow      = 'authority';
				$extra_field = 'code';
				$extra       = [ 0 ];
				$not         = true;
				break;
			case 'security':
				$group       = 'scheme';
				$follow      = 'endpoint';
				$extra_field = 'scheme';
				$extra       = [ 'http', 'https' ];
				$not         = false;
				break;
			case 'method':
				$group  = 'verb';
				$follow = 'domain';
				break;

		}
		$data  = Schema::get_grouped_list( $group, [], $this->filter, ! $this->is_today, $extra_field, $extra, $not, 'ORDER BY sum_hit DESC' );
		$total = 0;
		$other = 0;
		foreach ( $data as $key => $row ) {
			$total = $total + $row['sum_hit'];
			if ( $limit <= $key ) {
				$other = $other + $row['sum_hit'];
			}
		}
		$result = '';
		$cpt    = 0;
		$labels = [];
		$series = [];
		while ( $cpt < $limit && array_key_exists( $cpt, $data ) ) {
			if ( 0 < $total ) {
				$percent = round( 100 * $data[ $cpt ]['sum_hit'] / $total, 1 );
			} else {
				$percent = 100;
			}
			if ( 0.1 > $percent ) {
				$percent = 0.1;
			}
			$meta = strtoupper( $data[ $cpt ][ $group ] );
			if ( 'code' === $type ) {
				$meta = $data[ $cpt ][ $group ] . ' ' . Http::$http_status_codes[ (int) $data[ $cpt ][ $group ] ];
			}
			$labels[] = strtoupper( $data[ $cpt ][ $group ] );
			$series[] = [ 'meta' => $meta, 'value' => (float) $percent ];
			++$cpt;
		}
		if ( 0 < $other ) {
			if ( 0 < $total ) {
				$percent = round( 100 * $other / $total, 1 );
			} else {
				$percent = 100;
			}
			if ( 0.1 > $percent ) {
				$percent = 0.1;
			}
			$labels[] = esc_html__( 'Other', 'traffic' );
			$series[] = [ 'meta' => esc_html__( 'Other', 'traffic' ), 'value' => (float) $percent ];
		}
		$result  = '<div class="traffic-pie-box">';
		$result .= '<div class="traffic-pie-graph">';
		$result .= '<div class="traffic-pie-graph-handler" id="traffic-pie-' . $group . '"></div>';
		$result .= '</div>';
		$result .= '<div class="traffic-pie-legend">';
		foreach ( $labels as $key => $label ) {
			$icon = '<img style="width:12px;vertical-align:baseline;" src="' . Feather\Icons::get_base64( 'square', $this->colors[ $key ], $this->colors[ $key ] ) . '" />';
			$result .= '<div class="traffic-pie-legend-item">' . $icon . '&nbsp;&nbsp;' . $label . '</div>';
		}
		$result .= '';
		$result .= '</div>';
		$result .= '</div>';
		$result .= '<script>';
		$result .= 'jQuery(function ($) {';
		$result .= ' var data' . $uuid . ' = ' . wp_json_encode( [ 'labels' => $labels, 'series' => $series]) . ';';
		$result .= ' var tooltip' . $uuid . ' = Chartist.plugins.tooltip({percentage: true, appendToBody: true});';
		$result .= ' var option' . $uuid . ' = {width: 120, height: 120, showLabel: false, donut: true, donutWidth: "40%", startAngle: 270, plugins: [tooltip' . $uuid . ']};';
		$result .= ' new Chartist.Pie("#traffic-pie-' . $group . '", data' . $uuid . ', option' . $uuid . ');';
		$result .= '});';
		$result .= '</script>';
		return [ 'traffic-' . $type => $result ];
	}

	/**
	 * Query statistics table.
	 *
	 * @param   string  $type    The type of top.
	 * @param   integer $limit  The number to display.
	 * @return array  The result of the query, ready to encode.
	 * @since    1.0.0
	 */
	private function query_top( $type, $limit ) {
		switch ( $type ) {
			case 'authorities':
				$group  = 'authority';
				$follow = 'authority';
				break;
			case 'endpoints':
				$group  = 'endpoint';
				$follow = 'endpoint';
				break;
			default:
				$group  = 'id';
				$follow = 'domain';
				break;

		}
		$data  = Schema::get_grouped_list( $group, [], $this->filter, ! $this->is_today, '', [], false, 'ORDER BY sum_hit DESC' );
		$total = 0;
		$other = 0;
		foreach ( $data as $key => $row ) {
			$total = $total + $row['sum_hit'];
			if ( $limit <= $key ) {
				$other = $other + $row['sum_hit'];
			}
		}
		$result = '';
		$cpt    = 0;
		while ( $cpt < $limit && array_key_exists( $cpt, $data ) ) {
			if ( 0 < $total ) {
				$percent = round( 100 * $data[ $cpt ]['sum_hit'] / $total, 1 );
			} else {
				$percent = 100;
			}
			$url = $this->get_url(
				[],
				[
					'type'   => $follow,
					'id'     => $data[ $cpt ][ $group ],
					'domain' => $data[ $cpt ]['id'],
				]
			);
			if ( 0.5 > $percent ) {
				$percent = 0.5;
			}
			$result .= '<div class="traffic-top-line">';
			$result .= '<div class="traffic-top-line-title">';
			$result .= '<img style="width:16px;vertical-align:bottom;" src="' . Favicon::get_base64( $data[ $cpt ]['id'] ) . '" />&nbsp;&nbsp;<span class="traffic-top-line-title-text"><a href="' . $url . '">' . $data[ $cpt ][ $group ] . '</a></span>';
			$result .= '</div>';
			$result .= '<div class="traffic-top-line-content">';
			$result .= '<div class="traffic-bar-graph"><div class="traffic-bar-graph-value" style="width:' . $percent . '%"></div></div>';
			$result .= '<div class="traffic-bar-detail">' . Conversion::number_shorten( $data[ $cpt ]['sum_hit'], 2 ) . '</div>';
			$result .= '</div>';
			$result .= '</div>';
			++$cpt;
		}
		if ( 0 < $total ) {
			$percent = round( 100 * $other / $total, 1 );
		} else {
			$percent = 100;
		}
		$result .= '<div class="traffic-top-line traffic-minor-data">';
		$result .= '<div class="traffic-top-line-title">';
		$result .= '<span class="traffic-top-line-title-text">' . esc_html__( 'Other', 'traffic' ) . '</span>';
		$result .= '</div>';
		$result .= '<div class="traffic-top-line-content">';
		$result .= '<div class="traffic-bar-graph"><div class="traffic-bar-graph-value" style="width:' . $percent . '%"></div></div>';
		$result .= '<div class="traffic-bar-detail">' . Conversion::number_shorten( $other, 2 ) . '</div>';
		$result .= '</div>';
		$result .= '</div>';
		return [ 'traffic-top-' . $type => $result ];
	}

	/**
	 * Query statistics table.
	 *
	 * @param   string $type    The type of list.
	 * @return array  The result of the query, ready to encode.
	 * @since    1.0.0
	 */
	private function query_list( $type ) {
		$follow     = '';
		$has_detail = false;
		$detail     = '';
		switch ( $type ) {
			case 'domains':
				$group      = 'id';
				$follow     = 'domain';
				$has_detail = true;
				break;
			case 'authorities':
				$group      = 'authority';
				$follow     = 'authority';
				$has_detail = true;
				break;
			case 'endpoints':
				$group  = 'endpoint';
				$follow = 'endpoint';
				break;
			case 'codes':
				$group = 'code';
				break;
			case 'schemes':
				$group = 'scheme';
				break;
			case 'methods':
				$group = 'verb';
				break;
			case 'countries':
				$group = 'country';
				break;
		}
		$data         = Schema::get_grouped_list( $group, [ 'authority', 'endpoint' ], $this->filter, ! $this->is_today, '', [], false, 'ORDER BY sum_hit DESC' );
		$detail_name  = esc_html__( 'Details', 'traffic' );
		$calls_name   = esc_html__( 'Calls', 'traffic' );
		$data_name    = esc_html__( 'Data Volume', 'traffic' );
		$latency_name = esc_html__( 'Latency', 'traffic' );
		$result       = '<table class="traffic-table">';
		$result      .= '<tr>';
		$result      .= '<th>&nbsp;</th>';
		if ( $has_detail ) {
			$result .= '<th>' . $detail_name . '</th>';
		}
		$result .= '<th>' . $calls_name . '</th>';
		$result .= '<th>' . $data_name . '</th>';
		$result .= '<th>' . $latency_name . '</th>';
		$result .= '</tr>';
		foreach ( $data as $row ) {
			$url         = $this->get_url(
				[],
				[
					'type'   => $follow,
					'id'     => $row[ $group ],
					'domain' => $row['id'],
				]
			);
			$name        = $row[ $group ];
			$authorities = sprintf( esc_html( _n( '%d subdomain', '%d subdomains', $row['cnt_authority'], 'traffic' ) ), $row['cnt_authority'] );
			$endpoints   = sprintf( esc_html( _n( '%d endpoint', '%d endpoints', $row['cnt_endpoint'], 'traffic' ) ), $row['cnt_endpoint'] );
			switch ( $type ) {
				case 'domains':
					$detail = $authorities . ' - ' . $endpoints;
					$name   = '<img style="width:16px;vertical-align:bottom;" src="' . Favicon::get_base64( $row['id'] ) . '" />&nbsp;&nbsp;<span class="traffic-table-text"><a href="' . $url . '">' . $name . '</a></span>';
					break;
				case 'authorities':
					$detail = $endpoints;
					$name   = '<img style="width:16px;vertical-align:bottom;" src="' . Favicon::get_base64( $row['id'] ) . '" />&nbsp;&nbsp;<span class="traffic-table-text"><a href="' . $url . '">' . $name . '</a></span>';
					break;
				case 'endpoints':
					$name = '<img style="width:16px;vertical-align:bottom;" src="' . Favicon::get_base64( $row['id'] ) . '" />&nbsp;&nbsp;<span class="traffic-table-text"><a href="' . $url . '">' . $name . '</a></span>';
					break;
				case 'codes':
					if ( '0' === $name ) {
						$name = '000';
					}
					$code = (int) $name;
					if ( 100 > $code ) {
						$http = '0xx';
					} elseif ( 200 > $code ) {
						$http = '1xx';
					} elseif ( 300 > $code ) {
						$http = '2xx';
					} elseif ( 400 > $code ) {
						$http = '3xx';
					} elseif ( 500 > $code ) {
						$http = '4xx';
					} elseif ( 600 > $code ) {
						$http = '5xx';
					} else {
						$http = 'nxx';
					}
					$name  = '<span class="traffic-http traffic-http-' . $http . '">' . $name . '</span>&nbsp;&nbsp;<span class="traffic-table-text">' . Http::$http_status_codes[ $code ] . '</span>';
					$group = 'code';
					break;
				case 'schemes':
					$icon = Feather\Icons::get_base64( 'unlock', 'none', '#E74C3C' );
					if ( 'HTTPS' === strtoupper( $name ) ) {
						$icon = Feather\Icons::get_base64( 'lock', 'none', '#18BB9C' );
					}
					$name  = '<img style="width:14px;vertical-align:text-top;" src="' . $icon . '" />&nbsp;&nbsp;<span class="traffic-table-text">' . strtoupper( $name ) . '</span>';
					$group = 'scheme';
					break;
				case 'methods':
					$name  = '<img style="width:14px;vertical-align:text-bottom;" src="' . Feather\Icons::get_base64( 'code', 'none', '#73879C' ) . '" />&nbsp;&nbsp;<span class="traffic-table-text">' . strtoupper( $name ) . '</span>';
					$group = 'verb';
					break;
				case 'countries':
					$group = 'country';
					break;
			}
			$calls = Conversion::number_shorten( $row['sum_hit'], 2 );
			$in    = '<img style="width:12px;vertical-align:baseline;" src="' . Feather\Icons::get_base64( 'arrow-down-right', 'none', '#73879C' ) . '" /><span class="traffic-table-text">' . Conversion::data_shorten( $row['sum_kb_in'] * 1024, 2 ) . '</span>';
			$out   = '<span class="traffic-table-text">' . Conversion::data_shorten( $row['sum_kb_out'] * 1024, 2 ) . '</span><img style="width:12px;vertical-align:baseline;" src="' . Feather\Icons::get_base64( 'arrow-up-right', 'none', '#73879C' ) . '" />';
			$data  = $in . ' &nbsp;&nbsp; ' . $out;
			if ( 1 < $row['sum_hit'] ) {
				$min = Conversion::number_shorten( $row['min_latency'], 0 );
				if ( false !== strpos( $min, 'K' ) ) {
					$min = str_replace( 'K', 's', $min );
				} else {
					$min = $min . 'ms';
				}
				$max = Conversion::number_shorten( $row['max_latency'], 0 );
				if ( false !== strpos( $max, 'K' ) ) {
					$max = str_replace( 'K', 's', $max );
				} else {
					$max = $max . 'ms';
				}
				$latency = (int) $row['avg_latency'] . 'ms&nbsp;<small>' . $min . '→' . $max . '</small>';
			} else {
				$latency = (int) $row['avg_latency'] . 'ms';
			}
			if ( 'codes' === $type && '0' === $row[ $group ] ) {
				$latency = '-';
			}
			$result .= '<tr>';
			$result .= '<td data-th="">' . $name . '</td>';
			if ( $has_detail ) {
				$result .= '<td data-th="' . $detail_name . '">' . $detail . '</td>';
			}
			$result .= '<td data-th="' . $calls_name . '">' . $calls . '</td>';
			$result .= '<td data-th="' . $data_name . '">' . $data . '</td>';
			$result .= '<td data-th="' . $latency_name . '">' . $latency . '</td>';
			$result .= '</tr>';
		}
		$result .= '</table>';
		return [ 'traffic-' . $type => $result ];
	}

	/**
	 * Query statistics table.
	 *
	 * @param   mixed $queried The query params.
	 * @return array  The result of the query, ready to encode.
	 * @since    1.0.0
	 */
	private function query_kpi( $queried ) {
		$result = [];
		if ( 'call' === $queried ) {
			$data     = Schema::get_std_kpi( $this->filter, ! $this->is_today );
			$pdata    = Schema::get_std_kpi( $this->previous );
			$current  = 0.0;
			$previous = 0.0;
			if ( is_array( $data ) && array_key_exists( 'sum_hit', $data ) && ! empty( $data['sum_hit'] ) ) {
				$current = (float) $data['sum_hit'];
			}
			if ( is_array( $pdata ) && array_key_exists( 'sum_hit', $pdata ) && ! empty( $pdata['sum_hit'] ) ) {
				$previous = (float) $pdata['sum_hit'];
			}
			$result[ 'kpi-main-' . $queried ] = Conversion::number_shorten( $current, 1 );
			if ( 0.0 !== $current && 0.0 !== $previous ) {
				$percent = round( 100 * ( $current - $previous ) / $previous, 1 );
				if ( 0.1 > abs( $percent ) ) {
					$percent = 0;
				}
				$result[ 'kpi-index-' . $queried ] = '<span style="color:' . ( 0 <= $percent ? '#18BB9C' : '#E74C3C' ) . ';">' . ( 0 < $percent ? '+' : '' ) . $percent . '%</span>';
			} elseif ( 0.0 === $previous ) {
				$result[ 'kpi-index-' . $queried ] = '<span style="color:#18BB9C;">+∞</span>';
			} elseif ( 0.0 === $current ) {
				$result[ 'kpi-index-' . $queried ] = '<span style="color:#E74C3C;">-∞</span>';
			}
			if ( is_array( $data ) && array_key_exists( 'avg_latency', $data ) && ! empty( $data['avg_latency'] ) ) {
				$result[ 'kpi-bottom-' . $queried ] = '<span class="traffic-kpi-large-bottom-text">' . sprintf( esc_html__( 'avg latency: %sms.', 'traffic' ), (int) $data['avg_latency'] ) . '</span>';
			}
		}
		if ( 'data' === $queried ) {
			$data         = Schema::get_std_kpi( $this->filter, ! $this->is_today );
			$pdata        = Schema::get_std_kpi( $this->previous );
			$current_in   = 0.0;
			$current_out  = 0.0;
			$previous_in  = 0.0;
			$previous_out = 0.0;
			if ( is_array( $data ) && array_key_exists( 'sum_kb_in', $data ) && ! empty( $data['sum_kb_in'] ) ) {
				$current_in = (float) $data['sum_kb_in'] * 1024;
			}
			if ( is_array( $data ) && array_key_exists( 'sum_kb_out', $data ) && ! empty( $data['sum_kb_out'] ) ) {
				$current_out = (float) $data['sum_kb_out'] * 1024;
			}
			if ( is_array( $pdata ) && array_key_exists( 'sum_kb_in', $pdata ) && ! empty( $pdata['sum_kb_in'] ) ) {
				$previous_in = (float) $pdata['sum_kb_in'] * 1024;
			}
			if ( is_array( $pdata ) && array_key_exists( 'sum_kb_out', $pdata ) && ! empty( $pdata['sum_kb_out'] ) ) {
				$previous_out = (float) $pdata['sum_kb_out'] * 1024;
			}
			$current                          = $current_in + $current_out;
			$previous                         = $previous_in + $previous_out;
			$result[ 'kpi-main-' . $queried ] = Conversion::data_shorten( $current, 1 );
			if ( 0.0 !== $current && 0.0 !== $previous ) {
				$percent = round( 100 * ( $current - $previous ) / $previous, 1 );
				if ( 0.1 > abs( $percent ) ) {
					$percent = 0;
				}
				$result[ 'kpi-index-' . $queried ] = '<span style="color:' . ( 0 <= $percent ? '#18BB9C' : '#E74C3C' ) . ';">' . ( 0 < $percent ? '+' : '' ) . $percent . '%</span>';
			} elseif ( 0.0 === $previous ) {
				$result[ 'kpi-index-' . $queried ] = '<span style="color:#18BB9C;">+∞</span>';
			} elseif ( 0.0 === $current ) {
				$result[ 'kpi-index-' . $queried ] = '<span style="color:#E74C3C;">-∞</span>';
			}
			$in                                 = '<img style="width:12px;vertical-align:baseline;" src="' . Feather\Icons::get_base64( 'arrow-down-right', 'none', '#73879C' ) . '" /><span class="traffic-kpi-large-bottom-text">' . Conversion::data_shorten( $current_in, 2 ) . '</span>';
			$out                                = '<span class="traffic-kpi-large-bottom-text">' . Conversion::data_shorten( $current_out, 2 ) . '</span><img style="width:12px;vertical-align:baseline;" src="' . Feather\Icons::get_base64( 'arrow-up-right', 'none', '#73879C' ) . '" />';
			$result[ 'kpi-bottom-' . $queried ] = $in . ' &nbsp;&nbsp; ' . $out;
		}
		if ( 'server' === $queried || 'quota' === $queried || 'pass' === $queried || 'uptime' === $queried ) {
			$not = false;
			if ( 'server' === $queried ) {
				$codes = Http::$http_error_codes;
			} elseif ( 'quota' === $queried ) {
				$codes = Http::$http_quota_codes;
			} elseif ( 'pass' === $queried ) {
				$codes = Http::$http_effective_pass_codes;
			} elseif ( 'uptime' === $queried ) {
				$codes = Http::$http_failure_codes;
				$not   = true;
			}
			$base        = Schema::get_std_kpi( $this->filter, ! $this->is_today );
			$pbase       = Schema::get_std_kpi( $this->previous );
			$data        = Schema::get_std_kpi( $this->filter, ! $this->is_today, 'code', $codes, $not );
			$pdata       = Schema::get_std_kpi( $this->previous, true, 'code', $codes, $not );
			$base_value  = 0.0;
			$pbase_value = 0.0;
			$data_value  = 0.0;
			$pdata_value = 0.0;
			$current     = 0.0;
			$previous    = 0.0;
			if ( is_array( $data ) && array_key_exists( 'sum_hit', $base ) && ! empty( $base['sum_hit'] ) ) {
				$base_value = (float) $base['sum_hit'];
			}
			if ( is_array( $pbase ) && array_key_exists( 'sum_hit', $pbase ) && ! empty( $pbase['sum_hit'] ) ) {
				$pbase_value = (float) $pbase['sum_hit'];
			}
			if ( is_array( $data ) && array_key_exists( 'sum_hit', $data ) && ! empty( $data['sum_hit'] ) ) {
				$data_value = (float) $data['sum_hit'];
			}
			if ( is_array( $pdata ) && array_key_exists( 'sum_hit', $pdata ) && ! empty( $pdata['sum_hit'] ) ) {
				$pdata_value = (float) $pdata['sum_hit'];
			}
			if ( 0.0 !== $base_value && 0.0 !== $data_value ) {
				$current                          = 100 * $data_value / $base_value;
				$result[ 'kpi-main-' . $queried ] = round( $current, 1 ) . '%';
			} else {
				if ( 0.0 !== $data_value ) {
					$result[ 'kpi-main-' . $queried ] = '100%';
				}
				if ( 0.0 !== $base_value ) {
					$result[ 'kpi-main-' . $queried ] = '0%';
				}
			}
			if ( 0.0 !== $pbase_value && 0.0 !== $pdata_value ) {
				$previous = 100 * $pdata_value / $pbase_value;
			} else {
				if ( 0.0 !== $pdata_value ) {
					$previous = 100;
				}
				if ( 0.0 !== $pbase_value ) {
					$previous = 0;
				}
			}
			if ( 0.0 !== $current && 0.0 !== $previous ) {
				$percent = round( 100 * ( $current - $previous ) / $previous, 1 );
				if ( 0.1 > abs( $percent ) ) {
					$percent = 0;
				}
				$result[ 'kpi-index-' . $queried ] = '<span style="color:' . ( 0 <= $percent ? '#18BB9C' : '#E74C3C' ) . ';">' . ( 0 < $percent ? '+' : '' ) . $percent . '%</span>';
			} elseif ( 0.0 === $previous ) {
				$result[ 'kpi-index-' . $queried ] = '<span style="color:#18BB9C;">+∞</span>';
			} elseif ( 0.0 === $current ) {
				$result[ 'kpi-index-' . $queried ] = '<span style="color:#E74C3C;">-∞</span>';
			}
			switch ( $queried ) {
				case 'server':
					$result[ 'kpi-bottom-' . $queried ] = '<span class="traffic-kpi-large-bottom-text">' . sprintf( esc_html__( '%s calls in error', 'traffic' ), Conversion::number_shorten( $data_value, 2 ) ) . '</span>';
					break;
				case 'quota':
					$result[ 'kpi-bottom-' . $queried ] = '<span class="traffic-kpi-large-bottom-text">' . sprintf( esc_html__( '%s blocked calls', 'traffic' ), Conversion::number_shorten( $data_value, 2 ) ) . '</span>';
					break;
				case 'pass':
					$result[ 'kpi-bottom-' . $queried ] = '<span class="traffic-kpi-large-bottom-text">' . sprintf( esc_html__( '%s successful calls', 'traffic' ), Conversion::number_shorten( $data_value, 2 ) ) . '</span>';
					break;
				case 'uptime':
					if ( 0.0 !== $base_value ) {
						$duration = implode( ', ', Date::get_age_array_from_seconds( $this->duration * DAY_IN_SECONDS * ( 1 - ( $data_value / $base_value ) ), true, true ) );
						if ( '' === $duration ) {
							$duration = esc_html__( 'no downtime', 'traffic' );
						} else {
							$duration = sprintf( esc_html__( 'down %s', 'traffic' ), $duration );
						}
						$result[ 'kpi-bottom-' . $queried ] = '<span class="traffic-kpi-large-bottom-text">' . $duration . '</span>';
					}
					break;
			}
		}
		return $result;
	}

	/**
	 * Get the title selector.
	 *
	 * @return string  The selector ready to print.
	 * @since    1.0.0
	 */
	public function get_title_selector() {
		switch ( $this->type ) {
			case 'domains':
				$title = esc_html__( 'Domains Details', 'traffic' );
				break;
			case 'domain':
				$title = esc_html__( 'Domain Summary', 'traffic' );
				break;
			case 'authorities':
				$title         = esc_html__( 'Domain Details', 'traffic' );
				$breadcrumbs[] = [
					'title'    => esc_html__( 'Domain Summary', 'traffic' ),
					'subtitle' => sprintf( esc_html__( 'Return to %s', 'traffic' ), $this->domain ),
					'url'      => $this->get_url(
						[ 'extra' ],
						[
							'type'   => 'domain',
							'domain' => $this->domain,
							'id'     => $this->domain,
						]
					),
				];
				break;
			case 'authority':
				$title         = esc_html__( 'Subdomain Summary', 'traffic' );
				$breadcrumbs[] = [
					'title'    => esc_html__( 'Domain Summary', 'traffic' ),
					'subtitle' => sprintf( esc_html__( 'Return to %s', 'traffic' ), $this->domain ),
					'url'      => $this->get_url(
						[ 'extra' ],
						[
							'type'   => 'domain',
							'domain' => $this->domain,
							'id'     => $this->domain,
						]
					),
				];
				break;
			case 'endpoints':
				$title         = esc_html__( 'Subdomain Details', 'traffic' );
				$breadcrumbs[] = [
					'title'    => esc_html__( 'Subdomain Summary', 'traffic' ),
					'subtitle' => sprintf( esc_html__( 'Return to %s', 'traffic' ), $this->subdomain ),
					'url'      => $this->get_url(
						[ 'extra' ],
						[
							'type'   => 'authority',
							'domain' => $this->domain,
							'id'     => $this->subdomain,
						]
					),
				];
				$breadcrumbs[] = [
					'title'    => esc_html__( 'Domain Summary', 'traffic' ),
					'subtitle' => sprintf( esc_html__( 'Return to %s', 'traffic' ), $this->domain ),
					'url'      => $this->get_url(
						[ 'extra' ],
						[
							'type'   => 'domain',
							'domain' => $this->domain,
							'id'     => $this->domain,
						]
					),
				];
				break;
			case 'endpoint':
				$title         = esc_html__( 'Endpoint Summary', 'traffic' );
				$breadcrumbs[] = [
					'title'    => esc_html__( 'Subdomain Summary', 'traffic' ),
					'subtitle' => sprintf( esc_html__( 'Return to %s', 'traffic' ), $this->subdomain ),
					'url'      => $this->get_url(
						[ 'extra' ],
						[
							'type'   => 'authority',
							'domain' => $this->domain,
							'id'     => $this->subdomain,
						]
					),
				];
				$breadcrumbs[] = [
					'title'    => esc_html__( 'Domain Summary', 'traffic' ),
					'subtitle' => sprintf( esc_html__( 'Return to %s', 'traffic' ), $this->domain ),
					'url'      => $this->get_url(
						[ 'extra' ],
						[
							'type'   => 'domain',
							'domain' => $this->domain,
							'id'     => $this->domain,
						]
					),
				];
				break;
			case 'country':
				$title    = esc_html__( 'Country', 'traffic' );
				$subtitle = L10n::get_country_name( $this->id );
				break;

		}
		$breadcrumbs[] = [
			'title'    => esc_html__( 'Main Summary', 'traffic' ),
			'subtitle' => sprintf( esc_html__( 'Return to Traffic main page.', 'traffic' ) ),
			'url'      => $this->get_url( [ 'domain', 'id', 'extra', 'type' ] ),
		];
		$result        = '<select name="sources" id="sources" class="traffic-select sources" placeholder="' . $title . '" style="display:none;">';
		foreach ( $breadcrumbs as $breadcrumb ) {
			$result .= '<option value="' . $breadcrumb['url'] . '">' . $breadcrumb['title'] . '~-' . $breadcrumb['subtitle'] . '-~</span></option>';
		}
		$result .= '</select>';
		$result .= '';

		return $result;
	}

	/**
	 * Get the title bar.
	 *
	 * @return string  The bar ready to print.
	 * @since    1.0.0
	 */
	public function get_title_bar() {
		$subtitle = $this->id;
		switch ( $this->type ) {
			case 'summary':
				$title = esc_html__( 'Main Summary', 'traffic' );
				break;
			case 'domain':
			case 'authority':
			case 'endpoint':
			case 'domains':
			case 'authorities':
			case 'endpoints':
				$title = $this->get_title_selector();
				break;
		}
		$result  = '<div class="traffic-box traffic-box-full-line">';
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
		$result .= '<div class="traffic-kpi-bar">';
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
	 * Get the main chart.
	 *
	 * @return string  The main chart ready to print.
	 * @since    1.0.0
	 */
	public function get_main_chart() {
		$result  = '<div class="traffic-row">';
		$result .= '<div class="traffic-box traffic-box-full-line">';
		$result .= '<div class="traffic-module-title-bar"><span class="traffic-module-title">' . esc_html__( 'Volumetry', 'traffic' ) . ' - ' . esc_html__( 'Calls', 'traffic' ) . '</span></div>';
		$result .= '<div class="traffic-module-content" id="traffic-main-chart">' . $this->get_graph_placeholder( 200 ) . '</div>';
		$result .= '</div>';
		$result .= '</div>';
		/*$result .= $this->get_refresh_script(
			[
				'query'   => 'domains',
				'queried' => 0,
			]
		);*/
		return $result;
	}

	/**
	 * Get the domains list.
	 *
	 * @return string  The table ready to print.
	 * @since    1.0.0
	 */
	public function get_domains_list() {
		$result  = '<div class="traffic-box traffic-box-full-line">';
		$result .= '<div class="traffic-module-title-bar"><span class="traffic-module-title">' . esc_html__( 'All Domains', 'traffic' ) . '</span></div>';
		$result .= '<div class="traffic-module-content" id="traffic-domains">' . $this->get_graph_placeholder( 200 ) . '</div>';
		$result .= '</div>';
		$result .= $this->get_refresh_script(
			[
				'query'   => 'domains',
				'queried' => 0,
			]
		);
		return $result;
	}

	/**
	 * Get the authorities list.
	 *
	 * @return string  The table ready to print.
	 * @since    1.0.0
	 */
	public function get_authorities_list() {
		$result  = '<div class="traffic-box traffic-box-full-line">';
		$result .= '<div class="traffic-module-title-bar"><span class="traffic-module-title">' . esc_html__( 'All Subdomains', 'traffic' ) . '</span></div>';
		$result .= '<div class="traffic-module-content" id="traffic-authorities">' . $this->get_graph_placeholder( 200 ) . '</div>';
		$result .= '</div>';
		$result .= $this->get_refresh_script(
			[
				'query'   => 'authorities',
				'queried' => 0,
			]
		);
		return $result;
	}

	/**
	 * Get the endpoints list.
	 *
	 * @return string  The table ready to print.
	 * @since    1.0.0
	 */
	public function get_endpoints_list() {
		$result  = '<div class="traffic-box traffic-box-full-line">';
		$result .= '<div class="traffic-module-title-bar"><span class="traffic-module-title">' . esc_html__( 'All Endpoints', 'traffic' ) . '</span></div>';
		$result .= '<div class="traffic-module-content" id="traffic-endpoints">' . $this->get_graph_placeholder( 200 ) . '</div>';
		$result .= '</div>';
		$result .= $this->get_refresh_script(
			[
				'query'   => 'endpoints',
				'queried' => 0,
			]
		);
		return $result;
	}

	/**
	 * Get the extra list.
	 *
	 * @return string  The table ready to print.
	 * @since    1.0.0
	 */
	public function get_extra_list() {
		switch ( $this->extra ) {
			case 'codes':
				$title = esc_html__( 'All HTTP Codes', 'traffic' );
				break;
			case 'schemes':
				$title = esc_html__( 'All Schemes', 'traffic' );
				break;
			case 'methods':
				$title = esc_html__( 'All Methods', 'traffic' );
				break;
			case 'countries':
				$title = esc_html__( 'All Countries', 'traffic' );
				break;
			default:
				$title = esc_html__( 'All Endpoints', 'traffic' );

		}
		$result  = '<div class="traffic-box traffic-box-full-line">';
		$result .= '<div class="traffic-module-title-bar"><span class="traffic-module-title">' . $title . '</span></div>';
		$result .= '<div class="traffic-module-content" id="traffic-' . $this->extra . '">' . $this->get_graph_placeholder( 200 ) . '</div>';
		$result .= '</div>';
		$result .= $this->get_refresh_script(
			[
				'query'   => $this->extra,
				'queried' => 0,
			]
		);
		return $result;
	}

	/**
	 * Get the top domains box.
	 *
	 * @return string  The box ready to print.
	 * @since    1.0.0
	 */
	public function get_top_domain_box() {
		$url     = $this->get_url( [ 'domain' ], [ 'type' => 'domains' ] );
		$detail  = '<a href="' . $url . '"><img style="width:12px;vertical-align:baseline;" src="' . Feather\Icons::get_base64( 'zoom-in', 'none', '#73879C' ) . '" /></a>';
		$result  = '<div class="traffic-40-module" style="height:290px">';
		$result .= '<div class="traffic-module-title-bar"><span class="traffic-module-title">' . esc_html__( 'Top Domains', 'traffic' ) . '</span><span class="traffic-module-more">' . $detail . '</span></div>';
		$result .= '<div class="traffic-module-content" id="traffic-top-domains">' . $this->get_graph_placeholder( 200 ) . '</div>';
		$result .= '</div>';
		$result .= $this->get_refresh_script(
			[
				'query'   => 'top-domains',
				'queried' => 5,
			]
		);
		return $result;
	}

	/**
	 * Get the top authority box.
	 *
	 * @return string  The box ready to print.
	 * @since    1.0.0
	 */
	public function get_top_authority_box() {
		$url     = $this->get_url(
			[],
			[
				'type'   => 'authorities',
				'domain' => $this->domain,
			]
		);
		$detail  = '<a href="' . $url . '"><img style="width:12px;vertical-align:baseline;" src="' . Feather\Icons::get_base64( 'zoom-in', 'none', '#73879C' ) . '" /></a>';
		$result  = '<div class="traffic-40-module" style="height:290px">';
		$result .= '<div class="traffic-module-title-bar"><span class="traffic-module-title">' . esc_html__( 'Top Subdomains', 'traffic' ) . '</span><span class="traffic-module-more">' . $detail . '</span></div>';
		$result .= '<div class="traffic-module-content" id="traffic-top-authorities">' . $this->get_graph_placeholder( 200 ) . '</div>';
		$result .= '</div>';
		$result .= $this->get_refresh_script(
			[
				'query'   => 'top-authorities',
				'queried' => 5,
			]
		);
		return $result;
	}

	/**
	 * Get the top endpoint box.
	 *
	 * @return string  The box ready to print.
	 * @since    1.0.0
	 */
	public function get_top_endpoint_box() {
		$url     = $this->get_url(
			[],
			[
				'type'   => 'endpoints',
				'domain' => $this->domain,
			]
		);
		$detail  = '<a href="' . $url . '"><img style="width:12px;vertical-align:baseline;" src="' . Feather\Icons::get_base64( 'zoom-in', 'none', '#73879C' ) . '" /></a>';
		$result  = '<div class="traffic-40-module" style="height:290px">';
		$result .= '<div class="traffic-module-title-bar"><span class="traffic-module-title">' . esc_html__( 'Top Endpoints', 'traffic' ) . '</span><span class="traffic-module-more">' . $detail . '</span></div>';
		$result .= '<div class="traffic-module-content" id="traffic-top-endpoints">' . $this->get_graph_placeholder( 200 ) . '</div>';
		$result .= '</div>';
		$result .= $this->get_refresh_script(
			[
				'query'   => 'top-endpoints',
				'queried' => 5,
			]
		);
		return $result;
	}

	/**
	 * Get the map box.
	 *
	 * @return string  The box ready to print.
	 * @since    1.0.0
	 */
	public function get_map_box() {
		switch ( $this->type ) {
			case 'domain':
				$url = $this->get_url(
					[],
					[
						'type'   => 'authorities',
						'domain' => $this->domain,
						'extra'  => 'countries',
					]
				);
				break;
			case 'authority':
				$url = $this->get_url(
					[],
					[
						'type'   => 'endpoints',
						'domain' => $this->domain,
						'extra'  => 'countries',
					]
				);
				break;
			default:
				$url = $this->get_url( [ 'domain' ], [ 'type' => 'domains', 'extra'  => 'countries' ] );
		}
		$detail  = '<a href="' . $url . '"><img style="width:12px;vertical-align:baseline;" src="' . Feather\Icons::get_base64( 'zoom-in', 'none', '#73879C' ) . '" /></a>';
		$result  = '<div class="traffic-60-module">';
		$result .= '<div class="traffic-module-title-bar"><span class="traffic-module-title">' . esc_html__( 'Countries', 'traffic' ) . '</span><span class="traffic-module-more">' . $detail . '</span></div>';
		$result .= '<div class="traffic-module-content">' . 'content' . '</div>';
		$result .= '</div>';
		return $result;
	}

	/**
	 * Get the map box.
	 *
	 * @return string  The box ready to print.
	 * @since    1.0.0
	 */
	public function get_codes_box() {
		switch ( $this->type ) {
			case 'domain':
				$url = $this->get_url(
					[],
					[
						'type'   => 'authorities',
						'domain' => $this->domain,
						'extra'  => 'codes',
					]
				);
				break;
			case 'authority':
				$url = $this->get_url(
					[],
					[
						'type'   => 'endpoints',
						'domain' => $this->domain,
						'extra'  => 'codes',
					]
				);
				break;
			default:
				$url = $this->get_url( [ 'domain' ], [ 'type' => 'domains', 'extra'  => 'codes' ] );
		}
		$detail  = '<a href="' . $url . '"><img style="width:12px;vertical-align:baseline;" src="' . Feather\Icons::get_base64( 'zoom-in', 'none', '#73879C' ) . '" /></a>';
		$result  = '<div class="traffic-33-module traffic-33-left-module">';
		$result .= '<div class="traffic-module-title-bar"><span class="traffic-module-title">' . esc_html__( 'HTTP codes', 'traffic' ) . '</span><span class="traffic-module-more">' . $detail . '</span></div>';
		$result .= '<div class="traffic-module-content" id="traffic-code">' . $this->get_graph_placeholder( 90 ) . '</div>';
		$result .= '</div>';
		$result .= $this->get_refresh_script(
			[
				'query'   => 'code',
				'queried' => 4,
			]
		);
		return $result;
	}

	/**
	 * Get the map box.
	 *
	 * @return string  The box ready to print.
	 * @since    1.0.0
	 */
	public function get_security_box() {
		switch ( $this->type ) {
			case 'domain':
				$url = $this->get_url(
					[],
					[
						'type'   => 'authorities',
						'domain' => $this->domain,
						'extra'  => 'schemes',
					]
				);
				break;
			case 'authority':
				$url = $this->get_url(
					[],
					[
						'type'   => 'endpoints',
						'domain' => $this->domain,
						'extra'  => 'schemes',
					]
				);
				break;
			default:
				$url = $this->get_url( [ 'domain' ], [ 'type' => 'domains', 'extra'  => 'schemes' ] );
		}
		$detail  = '<a href="' . $url . '"><img style="width:12px;vertical-align:baseline;" src="' . Feather\Icons::get_base64( 'zoom-in', 'none', '#73879C' ) . '" /></a>';
		$result  = '<div class="traffic-33-module traffic-33-center-module">';
		$result .= '<div class="traffic-module-title-bar"><span class="traffic-module-title">' . esc_html__( 'Schemes', 'traffic' ) . '</span><span class="traffic-module-more">' . $detail . '</span></div>';
		$result .= '<div class="traffic-module-content" id="traffic-security">' . $this->get_graph_placeholder( 90 ) . '</div>';
		$result .= '</div>';
		$result .= $this->get_refresh_script(
			[
				'query'   => 'security',
				'queried' => 4,
			]
		);
		return $result;
	}

	/**
	 * Get the map box.
	 *
	 * @return string  The box ready to print.
	 * @since    1.0.0
	 */
	public function get_method_box() {
		switch ( $this->type ) {
			case 'domain':
				$url = $this->get_url(
					[],
					[
						'type'   => 'authorities',
						'domain' => $this->domain,
						'extra'  => 'methods',
					]
				);
				break;
			case 'authority':
				$url = $this->get_url(
					[],
					[
						'type'   => 'endpoints',
						'domain' => $this->domain,
						'extra'  => 'methods',
					]
				);
				break;
			default:
				$url = $this->get_url( [ 'domain' ], [ 'type' => 'domains', 'extra'  => 'methods' ] );
		}
		$detail  = '<a href="' . $url . '"><img style="width:12px;vertical-align:baseline;" src="' . Feather\Icons::get_base64( 'zoom-in', 'none', '#73879C' ) . '" /></a>';
		$result  = '<div class="traffic-33-module traffic-33-right-module">';
		$result .= '<div class="traffic-module-title-bar"><span class="traffic-module-title">' . esc_html__( 'Methods', 'traffic' ) . '</span><span class="traffic-module-more">' . $detail . '</span></div>';
		$result .= '<div class="traffic-module-content" id="traffic-method">' . $this->get_graph_placeholder( 90 ) . '</div>';
		$result .= '</div>';
		$result .= $this->get_refresh_script(
			[
				'query'   => 'method',
				'queried' => 4,
			]
		);
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
				$icon  = Feather\Icons::get_base64( 'link-2', 'none', '#73879C' );
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
				$icon  = Feather\Icons::get_base64( 'activity', 'none', '#73879C' );
				$title = esc_html_x( 'Perceived Uptime', 'Noun - Perceived uptime, from the viewpoint of the site.', 'traffic' );
				$help  = esc_html__( 'Perceived uptime, from the viewpoint of the site.', 'traffic' );
				break;
		}
		$top       = '<img style="width:12px;vertical-align:baseline;" src="' . $icon . '" />&nbsp;&nbsp;<span style="cursor:help;" class="traffic-kpi-large-top-text bottom" data-position="bottom" data-tooltip="' . $help . '">' . $title . '</span>';
		$indicator = '&nbsp;';
		$bottom    = '<span class="traffic-kpi-large-bottom-text">&nbsp;</span>';
		$result    = '<div class="traffic-kpi-large-top">' . $top . '</div>';
		$result   .= '<div class="traffic-kpi-large-middle"><div class="traffic-kpi-large-middle-left" id="kpi-main-' . $kpi . '">' . $this->get_value_placeholder() . '</div><div class="traffic-kpi-large-middle-right" id="kpi-index-' . $kpi . '">' . $indicator . '</div></div>';
		$result   .= '<div class="traffic-kpi-large-bottom" id="kpi-bottom-' . $kpi . '">' . $bottom . '</div>';
		$result   .= $this->get_refresh_script(
			[
				'query'   => 'kpi',
				'queried' => $kpi,
			]
		);
		return $result;
	}

	/**
	 * Get a placeholder for graph.
	 *
	 * @param   integer $height The height of the placeholder.
	 * @return string  The placeholder, ready to print.
	 * @since    1.0.0
	 */
	private function get_graph_placeholder( $height ) {
		return '<p style="text-align:center;line-height:' . $height . 'px;"><img style="width:40px;vertical-align:middle;" src="' . TRAFFIC_ADMIN_URL . 'medias/bars.svg" /></p>';
	}

	/**
	 * Get a placeholder for value.
	 *
	 * @return string  The placeholder, ready to print.
	 * @since    1.0.0
	 */
	private function get_value_placeholder() {
		return '<img style="width:26px;vertical-align:middle;" src="' . TRAFFIC_ADMIN_URL . 'medias/three-dots.svg" />';
	}

	/**
	 * Get refresh script.
	 *
	 * @param   array $args Optional. The args for the ajax call.
	 * @return string  The script, ready to print.
	 * @since    1.0.0
	 */
	private function get_refresh_script( $args = [] ) {
		$result  = '<script>';
		$result .= 'jQuery(document).ready( function($) {';
		$result .= ' var data = {';
		$result .= '  action:"traffic_get_stats",';
		$result .= '  nonce:"' . wp_create_nonce( 'ajax_traffic' ) . '",';
		foreach ( $args as $key => $val ) {
			$s = '  ' . $key . ':';
			if ( is_string( $val ) ) {
				$s .= '"' . $val . '"';
			} elseif ( is_numeric( $val ) ) {
				$s .= $val;
			} elseif ( is_bool( $val ) ) {
				$s .= $val ? 'true' : 'false';
			}
			$result .= $s . ',';
		}
		if ( '' !== $this->id ) {
			$result .= '  id:"' . $this->id . '",';
		}
		$result .= '  type:"' . $this->type . '",';
		if ( '' !== $this->context ) {
			$result .= '  context:"' . $this->context . '",';
		}
		$result .= '  site:"' . $this->site . '",';
		$result .= '  start:"' . $this->start . '",';
		$result .= '  end:"' . $this->end . '",';
		$result .= ' };';
		$result .= ' $.post(ajaxurl, data, function(response) {';
		$result .= ' var val = JSON.parse(response);';
		$result .= ' $.each(val, function(index, value) {$("#" + index).html(value);console.log(index+" "+value)})';
		$result .= ' })';
		$result .= '});';
		$result .= '</script>';
		return $result;
	}

	/**
	 * Get the url.
	 *
	 * @param   array $exclude Optional. The args to exclude.
	 * @param   array $replace Optional. The args to replace or add.
	 * @return string  The url.
	 * @since    1.0.0
	 */
	private function get_url( $exclude = [], $replace = [] ) {
		$params         = [];
		$params['type'] = $this->type;
		$params['site'] = $this->site;
		if ( '' !== $this->id ) {
			$params['id'] = $this->id;
		}
		if ( '' !== $this->extra ) {
			$params['extra'] = $this->extra;
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
		foreach ( $replace as $key => $arg ) {
			$params[ $key ] = $arg;
		}
		$url = admin_url( 'tools.php?page=traffic-viewer' );
		foreach ( $params as $key => $arg ) {
			if ( '' !== $arg ) {
				$url .= '&' . $key . '=' . $arg;
			}
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
		$result .= '  var url="' . $this->get_url( [ 'context' ], [ 'domain' => $this->domain ] ) . '";';
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
		$result .= '  var url = "' . $this->get_url( [ 'start', 'end' ], [ 'domain' => $this->domain ] ) . '" + "&start=" + picker.startDate.format("YYYY-MM-DD") + "&end=" + picker.endDate.format("YYYY-MM-DD");';
		$result .= '  $(location).attr("href", url);';
		$result .= ' });';
		$result .= '});';
		$result .= '</script>';
		return $result;
	}

}
