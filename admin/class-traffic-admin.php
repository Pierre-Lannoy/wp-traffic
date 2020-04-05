<?php
/**
 * The admin-specific functionality of the plugin.
 *
 * @package    Plugin
 * @author  Pierre Lannoy <https://pierre.lannoy.fr/>.
 * @since   1.0.0
 */

namespace Traffic\Plugin;

use Traffic\Plugin\Feature\Analytics;
use Traffic\Plugin\Feature\AnalyticsFactory;
use Traffic\System\Assets;
use Traffic\System\Logger;
use Traffic\System\Role;
use Traffic\System\Option;
use Traffic\System\Form;
use Traffic\System\Blog;
use Traffic\System\Date;
use Traffic\System\Timezone;
use Traffic\System\GeoIP;
use Traffic\System\Environment;
use PerfOpsOne\AdminMenus;

/**
 * The admin-specific functionality of the plugin.
 *
 * @package    Plugin
 * @author  Pierre Lannoy <https://pierre.lannoy.fr/>.
 * @since   1.0.0
 */
class Traffic_Admin {

	/**
	 * The assets manager that's responsible for handling all assets of the plugin.
	 *
	 * @since  1.0.0
	 * @access protected
	 * @var    Assets    $assets    The plugin assets manager.
	 */
	protected $assets;

	/**
	 * Initialize the class and set its properties.
	 *
	 * @since 1.0.0
	 */
	public function __construct() {
		$this->assets = new Assets();
	}

	/**
	 * Register the stylesheets for the admin area.
	 *
	 * @since 1.0.0
	 */
	public function enqueue_styles() {
		$this->assets->register_style( TRAFFIC_ASSETS_ID, TRAFFIC_ADMIN_URL, 'css/traffic.min.css' );
		$this->assets->register_style( 'traffic-daterangepicker', TRAFFIC_ADMIN_URL, 'css/daterangepicker.min.css' );
		$this->assets->register_style( 'traffic-switchery', TRAFFIC_ADMIN_URL, 'css/switchery.min.css' );
		$this->assets->register_style( 'traffic-tooltip', TRAFFIC_ADMIN_URL, 'css/tooltip.min.css' );
		$this->assets->register_style( 'traffic-chartist', TRAFFIC_ADMIN_URL, 'css/chartist.min.css' );
		$this->assets->register_style( 'traffic-chartist-tooltip', TRAFFIC_ADMIN_URL, 'css/chartist-plugin-tooltip.min.css' );
		$this->assets->register_style( 'traffic-jvectormap', TRAFFIC_ADMIN_URL, 'css/jquery-jvectormap-2.0.3.min.css' );

	}

	/**
	 * Register the JavaScript for the admin area.
	 *
	 * @since 1.0.0
	 */
	public function enqueue_scripts() {
		$this->assets->register_script( TRAFFIC_ASSETS_ID, TRAFFIC_ADMIN_URL, 'js/traffic.min.js', [ 'jquery' ] );
		$this->assets->register_script( 'traffic-moment-with-locale', TRAFFIC_ADMIN_URL, 'js/moment-with-locales.min.js', [ 'jquery' ] );
		$this->assets->register_script( 'traffic-daterangepicker', TRAFFIC_ADMIN_URL, 'js/daterangepicker.min.js', [ 'jquery' ] );
		$this->assets->register_script( 'traffic-switchery', TRAFFIC_ADMIN_URL, 'js/switchery.min.js', [ 'jquery' ] );
		$this->assets->register_script( 'traffic-chartist', TRAFFIC_ADMIN_URL, 'js/chartist.min.js', [ 'jquery' ] );
		$this->assets->register_script( 'traffic-chartist-tooltip', TRAFFIC_ADMIN_URL, 'js/chartist-plugin-tooltip.min.js', [ 'traffic-chartist' ] );
		$this->assets->register_script( 'traffic-jvectormap', TRAFFIC_ADMIN_URL, 'js/jquery-jvectormap-2.0.3.min.js', [ 'jquery' ] );
		$this->assets->register_script( 'traffic-jvectormap-world', TRAFFIC_ADMIN_URL, 'js/jquery-jvectormap-world-mill.min.js', [ 'jquery' ] );
	}

	/**
	 * Init PerfOps admin menus.
	 *
	 * @param array $perfops    The already declared menus.
	 * @return array    The completed menus array.
	 * @since 1.0.0
	 */
	public function init_perfops_admin_menus( $perfops ) {
		if ( Role::SUPER_ADMIN === Role::admin_type() || Role::SINGLE_ADMIN === Role::admin_type() ) {
			$perfops['settings'][] = [
				'name'          => TRAFFIC_PRODUCT_NAME,
				'description'   => '',
				'icon_callback' => [ \Traffic\Plugin\Core::class, 'get_base64_logo' ],
				'slug'          => 'traffic-settings',
				/* translators: as in the sentence "Traffic Settings" or "WordPress Settings" */
				'page_title'    => sprintf( esc_html__( '%s Settings', 'traffic' ), TRAFFIC_PRODUCT_NAME ),
				'menu_title'    => TRAFFIC_PRODUCT_NAME,
				'capability'    => 'manage_options',
				'callback'      => [ $this, 'get_settings_page' ],
				'position'      => 50,
				'plugin'        => TRAFFIC_SLUG,
				'version'       => TRAFFIC_VERSION,
				'activated'     => true,
				'remedy'        => '',
				'statistics'    => [ '\Traffic\System\Statistics', 'sc_get_raw' ],
			];
		}
		if ( Role::SUPER_ADMIN === Role::admin_type() || Role::SINGLE_ADMIN === Role::admin_type() || Role::LOCAL_ADMIN === Role::admin_type() ) {
			$perfops['analytics'][] = [
				'name'          => esc_html__( 'API Traffic', 'traffic' ),
				/* translators: as in the sentence "Find out inbound and outbound API calls made to/from your network." or "Find out inbound and outbound API calls made to/from your website." */
				'description'   => sprintf( esc_html__( 'Find out inbound and outbound API calls made to/from your %s.', 'traffic' ), Environment::is_wordpress_multisite() ? esc_html__( 'network', 'traffic' ) : esc_html__( 'website', 'traffic' ) ),
				'icon_callback' => [ \Traffic\Plugin\Core::class, 'get_base64_logo' ],
				'slug'          => 'traffic-viewer',
				/* translators: as in the sentence "DecaLog Viewer" */
				'page_title'    => sprintf( esc_html__( 'API Traffic', 'traffic' ), TRAFFIC_PRODUCT_NAME ),
				'menu_title'    => esc_html__( 'API Traffic', 'traffic' ),
				'capability'    => 'manage_options',
				'callback'      => [ $this, 'get_viewer_page' ],
				'position'      => 50,
				'plugin'        => TRAFFIC_SLUG,
				'activated'     => true,
				'remedy'        => '',
			];
		}
		return $perfops;
	}

	/**
	 * Set the items in the settings menu.
	 *
	 * @since 1.0.0
	 */
	public function init_admin_menus() {
		add_filter( 'init_perfops_admin_menus', [ $this, 'init_perfops_admin_menus' ] );
		AdminMenus::initialize();
	}

	/**
	 * Get actions links for myblogs_blog_actions hook.
	 *
	 * @param string $actions   The HTML site link markup.
	 * @param object $user_blog An object containing the site data.
	 * @return string   The action string.
	 * @since 1.2.0
	 */
	public function blog_action( $actions, $user_blog ) {
		if ( Role::SUPER_ADMIN === Role::admin_type() || Role::LOCAL_ADMIN === Role::admin_type() ) {
			$actions .= " | <a href='" . esc_url( admin_url( 'admin.php?page=traffic-viewer&site=' . $user_blog->userblog_id ) ) . "'>" . __( 'API usage', 'traffic' ) . '</a>';
		}
		return $actions;
	}

	/**
	 * Get actions for manage_sites_action_links hook.
	 *
	 * @param string[] $actions  An array of action links to be displayed.
	 * @param int      $blog_id  The site ID.
	 * @param string   $blogname Site path, formatted depending on whether it is a sub-domain
	 *                           or subdirectory multisite installation.
	 * @return array   The actions.
	 * @since 1.2.0
	 */
	public function site_action( $actions, $blog_id, $blogname ) {
		if ( Role::SUPER_ADMIN === Role::admin_type() || Role::LOCAL_ADMIN === Role::admin_type() ) {
			$actions['api_usage'] = "<a href='" . esc_url( admin_url( 'admin.php?page=traffic-viewer&site=' . $blog_id ) ) . "' rel='bookmark'>" . __( 'API usage', 'traffic' ) . '</a>';
		}
		return $actions;
	}

	/**
	 * Initializes settings sections.
	 *
	 * @since 1.0.0
	 */
	public function init_settings_sections() {
		add_settings_section( 'traffic_inbound_options_section', esc_html__( 'Inbound APIs', 'traffic' ), [ $this, 'inbound_options_section_callback' ], 'traffic_inbound_options_section' );
		add_settings_section( 'traffic_outbound_options_section', esc_html__( 'Outbound APIs', 'traffic' ), [ $this, 'outbound_options_section_callback' ], 'traffic_outbound_options_section' );
		add_settings_section( 'traffic_plugin_features_section', esc_html__( 'Plugin features', 'traffic' ), [ $this, 'plugin_features_section_callback' ], 'traffic_plugin_features_section' );
		add_settings_section( 'traffic_plugin_options_section', esc_html__( 'Plugin options', 'traffic' ), [ $this, 'plugin_options_section_callback' ], 'traffic_plugin_options_section' );
	}

	/**
	 * Add links in the "Actions" column on the plugins view page.
	 *
	 * @param string[] $actions     An array of plugin action links. By default this can include 'activate',
	 *                              'deactivate', and 'delete'.
	 * @param string   $plugin_file Path to the plugin file relative to the plugins directory.
	 * @param array    $plugin_data An array of plugin data. See `get_plugin_data()`.
	 * @param string   $context     The plugin context. By default this can include 'all', 'active', 'inactive',
	 *                              'recently_activated', 'upgrade', 'mustuse', 'dropins', and 'search'.
	 * @return array Extended list of links to print in the "Actions" column on the Plugins page.
	 * @since 1.0.0
	 */
	public function add_actions_links( $actions, $plugin_file, $plugin_data, $context ) {
		$actions[] = sprintf( '<a href="%s">%s</a>', esc_url( admin_url( 'admin.php?page=traffic-settings' ) ), esc_html__( 'Settings', 'traffic' ) );
		$actions[] = sprintf( '<a href="%s">%s</a>', esc_url( admin_url( 'admin.php?page=traffic-viewer' ) ), esc_html__( 'Statistics', 'traffic' ) );
		return $actions;
	}

	/**
	 * Add links in the "Description" column on the plugins view page.
	 *
	 * @param array  $links List of links to print in the "Description" column on the Plugins page.
	 * @param string $file Path to the plugin file relative to the plugins directory.
	 * @return array Extended list of links to print in the "Description" column on the Plugins page.
	 * @since 1.0.0
	 */
	public function add_row_meta( $links, $file ) {
		if ( 0 === strpos( $file, TRAFFIC_SLUG . '/' ) ) {
			$links[] = '<a href="https://wordpress.org/support/plugin/' . TRAFFIC_SLUG . '/">' . __( 'Support', 'traffic' ) . '</a>';
			$links[] = '<a href="https://github.com/Pierre-Lannoy/wp-traffic">' . __( 'GitHub repository', 'traffic' ) . '</a>';
		}
		return $links;
	}

	/**
	 * Get the content of the tools page.
	 *
	 * @since 1.0.0
	 */
	public function get_viewer_page() {
		$analytics = AnalyticsFactory::get_analytics();
		include TRAFFIC_ADMIN_DIR . 'partials/traffic-admin-view-analytics.php';
	}

	/**
	 * Get the content of the settings page.
	 *
	 * @since 1.0.0
	 */
	public function get_settings_page() {
		if ( ! ( $tab = filter_input( INPUT_GET, 'tab' ) ) ) {
			$tab = filter_input( INPUT_POST, 'tab' );
		}
		if ( ! ( $action = filter_input( INPUT_GET, 'action' ) ) ) {
			$action = filter_input( INPUT_POST, 'action' );
		}
		if ( $action && $tab ) {
			switch ( $tab ) {
				case 'misc':
					switch ( $action ) {
						case 'do-save':
							if ( Role::SUPER_ADMIN === Role::admin_type() || Role::SINGLE_ADMIN === Role::admin_type() ) {
								if ( ! empty( $_POST ) && array_key_exists( 'submit', $_POST ) ) {
									$this->save_options();
								} elseif ( ! empty( $_POST ) && array_key_exists( 'reset-to-defaults', $_POST ) ) {
									$this->reset_options();
								}
							}
							break;
					}
					break;
			}
		}
		include TRAFFIC_ADMIN_DIR . 'partials/traffic-admin-settings-main.php';
	}

	/**
	 * Save the plugin options.
	 *
	 * @since 1.0.0
	 */
	private function save_options() {
		if ( ! empty( $_POST ) ) {
			if ( array_key_exists( '_wpnonce', $_POST ) && wp_verify_nonce( $_POST['_wpnonce'], 'traffic-plugin-options' ) ) {
				Option::network_set( 'use_cdn', array_key_exists( 'traffic_plugin_options_usecdn', $_POST ) ? (bool) filter_input( INPUT_POST, 'traffic_plugin_options_usecdn' ) : false );
				Option::network_set( 'download_favicons', array_key_exists( 'traffic_plugin_options_favicons', $_POST ) ? (bool) filter_input( INPUT_POST, 'traffic_plugin_options_favicons' ) : false );
				Option::network_set( 'display_nag', array_key_exists( 'traffic_plugin_options_nag', $_POST ) ? (bool) filter_input( INPUT_POST, 'traffic_plugin_options_nag' ) : false );
				Option::network_set( 'inbound_capture', array_key_exists( 'traffic_inbound_options_capture', $_POST ) ? (bool) filter_input( INPUT_POST, 'traffic_inbound_options_capture' ) : false );
				Option::network_set( 'outbound_capture', array_key_exists( 'traffic_outbound_options_capture', $_POST ) ? (bool) filter_input( INPUT_POST, 'traffic_outbound_options_capture' ) : false );
				Option::network_set( 'inbound_cut_path', array_key_exists( 'traffic_inbound_options_cut_path', $_POST ) ? (int) filter_input( INPUT_POST, 'traffic_inbound_options_cut_path' ) : Option::network_get( 'traffic_inbound_options_cut_path' ) );
				Option::network_set( 'outbound_cut_path', array_key_exists( 'traffic_outbound_options_cut_path', $_POST ) ? (int) filter_input( INPUT_POST, 'traffic_outbound_options_cut_path' ) : Option::network_get( 'traffic_outbound_options_cut_path' ) );
				Option::network_set( 'history', array_key_exists( 'traffic_plugin_features_history', $_POST ) ? (string) filter_input( INPUT_POST, 'traffic_plugin_features_history', FILTER_SANITIZE_NUMBER_INT ) : Option::network_get( 'history' ) );
				$message = esc_html__( 'Plugin settings have been saved.', 'traffic' );
				$code    = 0;
				add_settings_error( 'traffic_no_error', $code, $message, 'updated' );
				Logger::info( 'Plugin settings updated.', $code );
			} else {
				$message = esc_html__( 'Plugin settings have not been saved. Please try again.', 'traffic' );
				$code    = 2;
				add_settings_error( 'traffic_nonce_error', $code, $message, 'error' );
				Logger::warning( 'Plugin settings not updated.', $code );
			}
		}
	}

	/**
	 * Reset the plugin options.
	 *
	 * @since 1.0.0
	 */
	private function reset_options() {
		if ( ! empty( $_POST ) ) {
			if ( array_key_exists( '_wpnonce', $_POST ) && wp_verify_nonce( $_POST['_wpnonce'], 'traffic-plugin-options' ) ) {
				Option::reset_to_defaults();
				$message = esc_html__( 'Plugin settings have been reset to defaults.', 'traffic' );
				$code    = 0;
				add_settings_error( 'traffic_no_error', $code, $message, 'updated' );
				Logger::info( 'Plugin settings reset to defaults.', $code );
			} else {
				$message = esc_html__( 'Plugin settings have not been reset to defaults. Please try again.', 'traffic' );
				$code    = 2;
				add_settings_error( 'traffic_nonce_error', $code, $message, 'error' );
				Logger::warning( 'Plugin settings not reset to defaults.', $code );
			}
		}
	}

	/**
	 * Callback for plugin options section.
	 *
	 * @since 1.0.0
	 */
	public function plugin_options_section_callback() {
		$form = new Form();
		add_settings_field(
			'traffic_plugin_options_favicons',
			__( 'Favicons', 'traffic' ),
			[ $form, 'echo_field_checkbox' ],
			'traffic_plugin_options_section',
			'traffic_plugin_options_section',
			[
				'text'        => esc_html__( 'Download and display', 'traffic' ),
				'id'          => 'traffic_plugin_options_favicons',
				'checked'     => Option::network_get( 'download_favicons' ),
				'description' => esc_html__( 'If checked, Traffic will download favicons of websites to display them in reports.', 'traffic' ) . '<br/>' . esc_html__( 'Note: This feature uses the (free) Google Favicon Service.', 'traffic' ),
				'full_width'  => false,
				'enabled'     => true,
			]
		);
		register_setting( 'traffic_plugin_options_section', 'traffic_plugin_options_favicons' );
		$geo_ip = new GeoIP();
		if ( $geo_ip->is_installed() ) {
			$help  = '<img style="width:16px;vertical-align:text-bottom;" src="' . \Feather\Icons::get_base64( 'thumbs-up', 'none', '#00C800' ) . '" />&nbsp;';
			$help .= sprintf( esc_html__('Your site is currently using %s.', 'traffic' ), '<em>' . $geo_ip->get_full_name() .'</em>' );
		} else {
			$help  = '<img style="width:16px;vertical-align:text-bottom;" src="' . \Feather\Icons::get_base64( 'alert-triangle', 'none', '#FF8C00' ) . '" />&nbsp;';
			$help .= sprintf( esc_html__('Your site does not use any IP geographic information plugin. To take advantage of the geographical distribution of calls in Traffic, I recommend you to install the excellent (and free) %s. But it is not mandatory.', 'traffic' ), '<a href="https://wordpress.org/plugins/ip-locator/">IP Locator</a>' );
		}
		add_settings_field(
			'traffic_plugin_options_geoip',
			__( 'IP information', 'traffic' ),
			[ $form, 'echo_field_simple_text' ],
			'traffic_plugin_options_section',
			'traffic_plugin_options_section',
			[
				'text' => $help
			]
		);
		register_setting( 'traffic_plugin_options_section', 'traffic_plugin_options_geoip' );
		
		if ( defined( 'DECALOG_VERSION' ) ) {
			$help  = '<img style="width:16px;vertical-align:text-bottom;" src="' . \Feather\Icons::get_base64( 'thumbs-up', 'none', '#00C800' ) . '" />&nbsp;';
			$help .= sprintf( esc_html__('Your site is currently using %s.', 'traffic' ), '<em>DecaLog v' . DECALOG_VERSION .'</em>' );
		} else {
			$help  = '<img style="width:16px;vertical-align:text-bottom;" src="' . \Feather\Icons::get_base64( 'alert-triangle', 'none', '#FF8C00' ) . '" />&nbsp;';
			$help .= sprintf( esc_html__('Your site does not use any logging plugin. To log all events triggered in Traffic, I recommend you to install the excellent (and free) %s. But it is not mandatory.', 'traffic' ), '<a href="https://wordpress.org/plugins/decalog/">DecaLog</a>' );
		}
		add_settings_field(
			'traffic_plugin_options_logger',
			__( 'Logging', 'traffic' ),
			[ $form, 'echo_field_simple_text' ],
			'traffic_plugin_options_section',
			'traffic_plugin_options_section',
			[
				'text' => $help
			]
		);
		register_setting( 'traffic_plugin_options_section', 'traffic_plugin_options_logger' );
		add_settings_field(
			'traffic_plugin_options_usecdn',
			__( 'Resources', 'traffic' ),
			[ $form, 'echo_field_checkbox' ],
			'traffic_plugin_options_section',
			'traffic_plugin_options_section',
			[
				'text'        => esc_html__( 'Use public CDN', 'traffic' ),
				'id'          => 'traffic_plugin_options_usecdn',
				'checked'     => Option::network_get( 'use_cdn' ),
				'description' => esc_html__( 'If checked, Traffic will use a public CDN (jsDelivr) to serve scripts and stylesheets.', 'traffic' ),
				'full_width'  => false,
				'enabled'     => true,
			]
		);
		register_setting( 'traffic_plugin_options_section', 'traffic_plugin_options_usecdn' );
		add_settings_field(
			'traffic_plugin_options_nag',
			__( 'Admin notices', 'traffic' ),
			[ $form, 'echo_field_checkbox' ],
			'traffic_plugin_options_section',
			'traffic_plugin_options_section',
			[
				'text'        => esc_html__( 'Display', 'traffic' ),
				'id'          => 'traffic_plugin_options_nag',
				'checked'     => Option::network_get( 'display_nag' ),
				'description' => esc_html__( 'Allows Traffic to display admin notices throughout the admin dashboard.', 'traffic' ) . '<br/>' . esc_html__( 'Note: Traffic respects DISABLE_NAG_NOTICES flag.', 'traffic' ),
				'full_width'  => false,
				'enabled'     => true,
			]
		);
		register_setting( 'traffic_plugin_options_section', 'traffic_plugin_options_nag' );
	}

	/**
	 * Callback for plugin features section.
	 *
	 * @since 1.0.0
	 */
	public function plugin_features_section_callback() {
		$form = new Form();
		add_settings_field(
			'traffic_plugin_features_history',
			esc_html__( 'Historical data', 'traffic' ),
			[ $form, 'echo_field_select' ],
			'traffic_plugin_features_section',
			'traffic_plugin_features_section',
			[
				'list'        => $this->get_retentions_array(),
				'id'          => 'traffic_plugin_features_history',
				'value'       => Option::network_get( 'history' ),
				'description' => esc_html__( 'Maximum age of data to keep for statistics.', 'traffic' ),
				'full_width'  => false,
				'enabled'     => true,
			]
		);
		register_setting( 'traffic_plugin_features_section', 'traffic_plugin_features_history' );
	}

	/**
	 * Get the available history retentions.
	 *
	 * @return array An array containing the history modes.
	 * @since  3.2.0
	 */
	protected function get_retentions_array() {
		$result = [];
		for ( $i = 1; $i < 7; $i++ ) {
			// phpcs:ignore
			$result[] = [ (int) ( 30 * $i ), esc_html( sprintf( _n( '%d month', '%d months', $i, 'traffic' ), $i ) ) ];
		}
		for ( $i = 1; $i < 7; $i++ ) {
			// phpcs:ignore
			$result[] = [ (int) ( 365 * $i ), esc_html( sprintf( _n( '%d year', '%d years', $i, 'traffic' ), $i ) ) ];
		}
		return $result;
	}

	/**
	 * Callback for inbound APIs section.
	 *
	 * @since 1.0.0
	 */
	public function inbound_options_section_callback() {
		$form = new Form();
		add_settings_field(
			'traffic_inbound_options_capture',
			__( 'Analytics', 'traffic' ),
			[ $form, 'echo_field_checkbox' ],
			'traffic_inbound_options_section',
			'traffic_inbound_options_section',
			[
				'text'        => esc_html__( 'Activated', 'traffic' ),
				'id'          => 'traffic_inbound_options_capture',
				'checked'     => Option::network_get( 'inbound_capture' ),
				'description' => esc_html__( 'If checked, Traffic will analyze inbound API calls (the calls made by external sites or apps to your site).', 'traffic' ),
				'full_width'  => false,
				'enabled'     => true,
			]
		);
		register_setting( 'traffic_inbound_options_section', 'traffic_inbound_options_capture' );
		add_settings_field(
			'traffic_inbound_options_cut_path',
			__( 'Path cut', 'traffic' ),
			[ $form, 'echo_field_input_integer' ],
			'traffic_inbound_options_section',
			'traffic_inbound_options_section',
			[
				'id'          => 'traffic_inbound_options_cut_path',
				'value'       => Option::network_get( 'inbound_cut_path' ),
				'min'         => 0,
				'max'         => 10,
				'step'        => 1,
				'description' => esc_html__( 'Allows to keep only the first most significative elements of the endpoint path.', 'traffic' ),
				'full_width'  => false,
				'enabled'     => true,
			]
		);
		register_setting( 'traffic_inbound_options_section', 'traffic_inbound_options_cut_path' );
	}

	/**
	 * Callback for outbound APIs section.
	 *
	 * @since 1.0.0
	 */
	public function outbound_options_section_callback() {
		$form = new Form();
		add_settings_field(
			'traffic_outbound_options_capture',
			__( 'Analytics', 'traffic' ),
			[ $form, 'echo_field_checkbox' ],
			'traffic_outbound_options_section',
			'traffic_outbound_options_section',
			[
				'text'        => esc_html__( 'Activated', 'traffic' ),
				'id'          => 'traffic_outbound_options_capture',
				'checked'     => Option::network_get( 'outbound_capture' ),
				'description' => esc_html__( 'If checked, Traffic will analyze outbound API calls (the calls made by your site to external services).', 'traffic' ),
				'full_width'  => false,
				'enabled'     => true,
			]
		);
		register_setting( 'traffic_outbound_options_section', 'traffic_outbound_options_capture' );
		add_settings_field(
			'traffic_outbound_options_cut_path',
			__( 'Path cut', 'traffic' ),
			[ $form, 'echo_field_input_integer' ],
			'traffic_outbound_options_section',
			'traffic_outbound_options_section',
			[
				'id'          => 'traffic_outbound_options_cut_path',
				'value'       => Option::network_get( 'outbound_cut_path' ),
				'min'         => 0,
				'max'         => 10,
				'step'        => 1,
				'description' => esc_html__( 'Allows to keep only the first most significative elements of the endpoint path.', 'traffic' ),
				'full_width'  => false,
				'enabled'     => true,
			]
		);
		register_setting( 'traffic_outbound_options_section', 'traffic_outbound_options_cut_path' );
	}

}
