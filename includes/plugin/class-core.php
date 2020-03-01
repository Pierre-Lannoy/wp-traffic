<?php
/**
 * The file that defines the core plugin class
 *
 * A class definition that includes attributes and functions used across both the
 * public-facing side of the site and the admin area.
 *
 * @package Plugin
 * @author  Pierre Lannoy <https://pierre.lannoy.fr/>.
 * @since   1.0.0
 */

namespace Traffic\Plugin;

use Traffic\System\Loader;
use Traffic\System\I18n;
use Traffic\System\Assets;
use Traffic\Library\Libraries;
use Traffic\System\Nag;
use Traffic\System\Role;

/**
 * The core plugin class.
 *
 * This is used to define internationalization, admin-specific hooks, and
 * public-facing site hooks.
 *
 * @package Plugin
 * @author  Pierre Lannoy <https://pierre.lannoy.fr/>.
 * @since   1.0.0
 */
class Core {

	/**
	 * The loader that's responsible for maintaining and registering all hooks that power
	 * the plugin.
	 *
	 * @since  1.0.0
	 * @access protected
	 * @var    Loader    $loader    Maintains and registers all hooks for the plugin.
	 */
	protected $loader;

	/**
	 * Define the core functionality of the plugin.
	 *
	 * Load the dependencies, define the locale, and set the hooks for the admin area and
	 * the public-facing side of the site.
	 *
	 * @since 1.0.0
	 */
	public function __construct() {
		$this->loader = new Loader();
		$this->set_locale();
		$this->define_global_hooks();
		$this->define_admin_hooks();
		$this->define_public_hooks();
	}

	/**
	 * Define the locale for this plugin for internationalization.
	 *
	 * Uses the Adr_Sync_i18n class in order to set the domain and to register the hook
	 * with WordPress.
	 *
	 * @since  1.0.0
	 * @access private
	 */
	private function set_locale() {
		$plugin_i18n = new I18n();
		$this->loader->add_action( 'plugins_loaded', $plugin_i18n, 'load_plugin_textdomain' );
	}

	/**
	 * Register all of the hooks related to the features of the plugin.
	 *
	 * @since  1.0.0
	 * @access private
	 */
	private function define_global_hooks() {
		$bootstrap = new Initializer();
		$assets    = new Assets();
		$updater   = new Updater();
		$libraries = new Libraries();
		$this->loader->add_filter( 'perfopsone_plugin_info', self::class, 'perfopsone_plugin_info' );
		$this->loader->add_action( 'init', $bootstrap, 'initialize' );
		$this->loader->add_action( 'init', $bootstrap, 'late_initialize', PHP_INT_MAX );
		$this->loader->add_action( 'wp_head', $assets, 'prefetch' );
		add_shortcode( 'traffic-changelog', [ $updater, 'sc_get_changelog' ] );
		add_shortcode( 'traffic-libraries', [ $libraries, 'sc_get_list' ] );
		add_shortcode( 'traffic-statistics', [ 'Traffic\System\Statistics', 'sc_get_raw' ] );
	}

	/**
	 * Register all of the hooks related to the admin area functionality
	 * of the plugin.
	 *
	 * @since  1.0.0
	 * @access private
	 */
	private function define_admin_hooks() {
		$plugin_admin = new Traffic_Admin();
		$nag          = new Nag();
		$this->loader->add_action( 'admin_enqueue_scripts', $plugin_admin, 'enqueue_styles' );
		$this->loader->add_action( 'admin_enqueue_scripts', $plugin_admin, 'enqueue_scripts' );
		$this->loader->add_action( 'admin_menu', $plugin_admin, 'init_admin_menus' );
		$this->loader->add_action( 'admin_init', $plugin_admin, 'init_settings_sections' );
		$this->loader->add_filter( 'plugin_action_links_' . plugin_basename( TRAFFIC_PLUGIN_DIR . TRAFFIC_SLUG . '.php' ), $plugin_admin, 'add_actions_links', 10, 4 );
		$this->loader->add_filter( 'plugin_row_meta', $plugin_admin, 'add_row_meta', 10, 2 );
		$this->loader->add_action( 'admin_notices', $nag, 'display' );
		$this->loader->add_action( 'wp_ajax_hide_traffic_nag', $nag, 'hide_callback' );
		$this->loader->add_action( 'wp_ajax_traffic_get_stats', 'Traffic\Plugin\Feature\AnalyticsFactory', 'get_stats_callback' );
		$this->loader->add_filter( 'myblogs_blog_actions', $plugin_admin, 'blog_action', 10, 2 );
		$this->loader->add_filter( 'manage_sites_action_links', $plugin_admin, 'site_action', 10, 3 );
	}

	/**
	 * Register all of the hooks related to the public-facing functionality
	 * of the plugin.
	 *
	 * @since  1.0.0
	 * @access private
	 */
	private function define_public_hooks() {
		$plugin_public = new Traffic_Public();
		$this->loader->add_action( 'wp_enqueue_scripts', $plugin_public, 'enqueue_styles' );
		$this->loader->add_action( 'wp_enqueue_scripts', $plugin_public, 'enqueue_scripts' );
	}

	/**
	 * Run the loader to execute all of the hooks with WordPress.
	 *
	 * @since 1.0.0
	 */
	public function run() {
		$this->loader->run();
	}

	/**
	 * The reference to the class that orchestrates the hooks with the plugin.
	 *
	 * @since  1.0.0
	 * @return Loader    Orchestrates the hooks of the plugin.
	 */
	public function get_loader() {
		return $this->loader;
	}

	/**
	 * Adds full plugin identification.
	 *
	 * @param array $plugin The already set identification information.
	 * @return array The extended identification information.
	 * @since 1.0.0
	 */
	public static function perfopsone_plugin_info( $plugin ) {
		$plugin[ TRAFFIC_SLUG ] = [
			'name'    => TRAFFIC_PRODUCT_NAME,
			'code'    => TRAFFIC_CODENAME,
			'version' => TRAFFIC_VERSION,
			'url'     => TRAFFIC_PRODUCT_URL,
			'icon'    => self::get_base64_logo(),
		];
		return $plugin;
	}

	/**
	 * Returns a base64 svg resource for the plugin logo.
	 *
	 * @return string The svg resource as a base64.
	 * @since 1.0.0
	 */
	public static function get_base64_logo() {
		$source  = '<svg width="100%" height="100%" viewBox="0 0 1001 1001" version="1.1" xmlns="http://www.w3.org/2000/svg" xmlns:xlink="http://www.w3.org/1999/xlink" xml:space="preserve" xmlns:serif="http://www.serif.com/" style="fill-rule:evenodd;clip-rule:evenodd;stroke-miterlimit:10;">';
		$source .= '<g id="Traffic" transform="matrix(10.0067,0,0,10.0067,0,0)">';
		$source .= '<rect x="0" y="0" width="100" height="100" style="fill:none;"/>';
		$source .= '<clipPath id="_clip1"><rect x="0" y="0" width="100" height="100"/></clipPath>';
		$source .= '<g clip-path="url(#_clip1)">';
		$source .= '<g id="Icon" transform="matrix(0.964549,0,0,0.964549,-0.63865,1.78035)">';
		$source .= '<g transform="matrix(0,106.221,106.221,0,52.4976,-19.9011)"><path d="M0.42,-0.324C0.421,-0.324 0.421,-0.324 0.421,-0.324C0.431,-0.398 0.495,-0.456 0.572,-0.456C0.656,-0.456 0.724,-0.388 0.724,-0.305L0.724,0.293C0.725,0.383 0.651,0.456 0.56,0.456C0.48,0.456 0.414,0.399 0.399,0.323C0.356,0.291 0.328,0.241 0.328,0.183C0.328,0.156 0.335,0.13 0.346,0.106C0.26,0.076 0.197,-0.006 0.197,-0.103L0.197,-0.103C0.197,-0.225 0.297,-0.324 0.42,-0.324Z" style="fill:url(#_Linear2);fill-rule:nonzero;"/></g>';
		$source .= '<g transform="matrix(0,1,1,0,60.0193,87.9577)"><path d="M-7.5,-7.5L7.5,-7.5" style="fill:none;fill-rule:nonzero;stroke:rgb(87,159,244);stroke-width:1.73px;"/></g>';
		$source .= '<g transform="matrix(1,0,0,1,7.51934,95.4577)"><path d="M0,0L90,0" style="fill:none;fill-rule:nonzero;stroke:rgb(87,159,244);stroke-width:1.73px;"/></g>';
		$source .= '<g transform="matrix(-1,0,0,1,103.001,83.2866)"><rect x="44" y="9" width="13" height="6" style="fill:rgb(171,207,249);stroke:rgb(171,207,249);stroke-width:1.73px;stroke-linecap:round;stroke-linejoin:round;"/></g>';
		$source .= '<g transform="matrix(0,-51.5803,-51.5803,0,52.5046,84.6977)"><path d="M0.95,0.611C0.95,0.632 0.933,0.649 0.911,0.649L0.174,0.649C0.153,0.649 0.136,0.632 0.136,0.611L0.136,-0.611C0.136,-0.632 0.153,-0.649 0.174,-0.649L0.911,-0.649C0.933,-0.649 0.95,-0.632 0.95,-0.611L0.95,0.611Z" style="fill:url(#_Linear3);fill-rule:nonzero;"/></g>';
		$source .= '<g transform="matrix(1,0,0,1,28.1936,53.5793)"><path d="M0,15.324L15.325,3.648L29.189,15.324L46.163,0" style="fill:none;fill-rule:nonzero;stroke:rgb(65,172,255);stroke-width:0.63px;"/></g>';
		$source .= '<g transform="matrix(0,-1,-1,0,28.0046,66.6977)"><path d="M-2,-2C-3.104,-2 -4,-1.104 -4,0C-4,1.104 -3.104,2 -2,2C-0.896,2 0,1.104 0,0C0,-1.104 -0.896,-2 -2,-2" style="fill:white;fill-rule:nonzero;"/></g>';
		$source .= '<g transform="matrix(0,-1,-1,0,44.0046,55.6977)"><path d="M-2,-2C-3.104,-2 -4,-1.104 -4,0C-4,1.104 -3.104,2 -2,2C-0.896,2 0,1.104 0,0C0,-1.104 -0.896,-2 -2,-2" style="fill:white;fill-rule:nonzero;"/></g>';
		$source .= '<g transform="matrix(0,-1,-1,0,57.0046,66.6977)"><path d="M-2,-2C-3.104,-2 -4,-1.104 -4,0C-4,1.104 -3.104,2 -2,2C-0.896,2 0,1.104 0,0C0,-1.104 -0.896,-2 -2,-2" style="fill:white;fill-rule:nonzero;"/></g>';
		$source .= '<g transform="matrix(0,-1,-1,0,74.0046,51.6977)"><path d="M-2,-2C-3.104,-2 -4,-1.104 -4,0C-4,1.104 -3.104,2 -2,2C-0.896,2 0,1.104 0,0C0,-1.104 -0.896,-2 -2,-2" style="fill:white;fill-rule:nonzero;"/></g>';
		$source .= '<g transform="matrix(-1,0,0,1,145.005,34.6977)"><rect x="65" y="34" width="12" height="4" style="fill:white;"/></g>';
		$source .= '<g transform="matrix(-1,0,0,1,61.0046,-5.3023)"><rect x="23" y="54" width="12" height="4" style="fill:white;"/></g>';
		$source .= '<g transform="matrix(1,0,0,1,3.00458,6.6977)"><g opacity="0.3"><g transform="matrix(1,0,0,1,83,29)"><path d="M0,6L-67,6L-67,2C-67,0.896 -66.104,0 -65,0L-2,0C-0.896,0 0,0.896 0,2L0,6Z" style="fill:white;fill-rule:nonzero;"/></g></g></g>';
		$source .= '<g transform="matrix(1,0,0,1,3.00458,6.6977)"><g opacity="0.3"><g transform="matrix(1,0,0,1,0,6)"><rect x="20" y="33" width="59" height="28" style="fill:white;"/></g></g></g>';
		$source .= '</g>';
		$source .= '</g>';
		$source .= '</g>';
		$source .= '<defs>';
		$source .= '<linearGradient id="_Linear2" x1="0" y1="0" x2="1" y2="0" gradientUnits="userSpaceOnUse" gradientTransform="matrix(1,0,0,-1,0,-2.11822e-06)"><stop offset="0" style="stop-color:rgb(248,247,252);stop-opacity:1"/><stop offset="0.08" style="stop-color:rgb(248,247,252);stop-opacity:1"/><stop offset="1" style="stop-color:rgb(65,172,255);stop-opacity:1"/></linearGradient>';
		$source .= '<linearGradient id="_Linear3" x1="0" y1="0" x2="1" y2="0" gradientUnits="userSpaceOnUse" gradientTransform="matrix(1,0,0,-1,0,0)"><stop offset="0" style="stop-color:rgb(25,39,131);stop-opacity:1"/><stop offset="1" style="stop-color:rgb(65,172,255);stop-opacity:1"/></linearGradient>';
		$source .= '</defs>';
		$source .= '</svg>';
		// phpcs:ignore
		return 'data:image/svg+xml;base64,' . base64_encode( $source );
	}

}
