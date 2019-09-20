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
use Traffic\System\Assets;
use Traffic\System\Logger;
use Traffic\System\Role;
use Traffic\System\Option;
use Traffic\System\Form;
use Traffic\System\Blog;
use Traffic\System\Date;
use Traffic\System\Timezone;

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
	}

	/**
	 * Register the JavaScript for the admin area.
	 *
	 * @since 1.0.0
	 */
	public function enqueue_scripts() {
		$this->assets->register_script( TRAFFIC_ASSETS_ID, TRAFFIC_ADMIN_URL, 'js/traffic.min.js', [ 'jquery' ] );
	}

	/**
	 * Set the items in the settings menu.
	 *
	 * @since 1.0.0
	 */
	public function init_admin_menus() {
		wp_deregister_script('wp-a11y');
		if ( Role::SUPER_ADMIN === Role::admin_type() || Role::SINGLE_ADMIN === Role::admin_type() ) {
			/* translators: as in the sentence "Traffic Settings" or "WordPress Settings" */
			$settings = add_submenu_page( 'options-general.php', sprintf( esc_html__( '%s Settings', 'traffic' ), TRAFFIC_PRODUCT_NAME ), TRAFFIC_PRODUCT_NAME, 'manage_options', 'traffic-settings', [ $this, 'get_settings_page' ] );
			//add_action( 'load-' . $settings, [ new InlineHelp(), 'set_contextual_settings' ] );
			$name = add_submenu_page(
				'tools.php',
				/* translators: as in the sentence "Traffic Viewer" */
				sprintf( esc_html__( '%s Viewer', 'traffic' ), TRAFFIC_PRODUCT_NAME ),
				TRAFFIC_PRODUCT_NAME,
				'manage_options',
				'traffic-viewer',
				[ $this, 'get_tools_page' ]
			);
			//add_action( 'load-' . $name, [ new InlineHelp(), 'set_contextual_viewer' ] );
		}
	}

	/**
	 * Initializes settings sections.
	 *
	 * @since 1.0.0
	 */
	public function init_settings_sections() {
		add_settings_section( 'traffic_inbound_options_section', esc_html__( 'Inbound APIs', 'traffic' ), [ $this, 'inbound_options_section_callback' ], 'traffic_inbound_options_section' );
		add_settings_section( 'traffic_outbound_options_section', esc_html__( 'Outbound APIs', 'traffic' ), [ $this, 'outbound_options_section_callback' ], 'traffic_outbound_options_section' );
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
		$actions[] = sprintf( '<a href="%s">%s</a>', admin_url( 'options-general.php?page=traffic-settings' ), esc_html__( 'Settings', 'traffic' ) );
		$actions[] = sprintf( '<a href="%s">%s</a>', admin_url( 'tools.php?page=traffic-viewer' ), esc_html__( 'Statistics', 'traffic' ) );
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
	public function get_tools_page() {
		$timezone = Timezone::network_get();
		// ID.
		if ( ! ( $id = filter_input( INPUT_GET, 'id' ) ) ) {
			$id = filter_input( INPUT_POST, 'id' );
		}
		if ( empty( $id ) ) {
			$id = '';
		}
		// Analytics type.
		if ( ! ( $type = filter_input( INPUT_GET, 'type' ) ) ) {
			$type = filter_input( INPUT_POST, 'type' );
		}
		if ( empty( $type ) || ( 'domain' !== $type && 'authority' !== $type  && 'endpoint' !== $type && 'country' !== $type  ) ) {
			$type = 'summary';
		}
		// Filters.
		if ( ! ( $context = filter_input( INPUT_GET, 'context' ) ) ) {
			$context = filter_input( INPUT_POST, 'context' );
		}
		if ( empty( $context ) || ( 'inbound' !== $context && 'outbound' !== $context ) ) {
			$context = 'both';
		}
		if ( ! ( $site = filter_input( INPUT_GET, 'site' ) ) ) {
			$site = filter_input( INPUT_POST, 'site' );
		}
		if ( empty( $site ) || ! Blog::is_blog_exists( (int) $site ) ) {
			$site = 'all';
		}
		if ( ! ( $start = filter_input( INPUT_GET, 'start' ) ) ) {
			$start = filter_input( INPUT_POST, 'start' );
		}
		if ( empty( $start ) || ! Date::is_date_exists( $start, 'Y-m-d' ) ) {
			$sdatetime = new \DateTime( 'now', $timezone );
			$start     = $sdatetime->format( 'Y-m-d' );
		} else {
			$sdatetime = new \DateTime( $start, $timezone );
		}
		if ( ! ( $end = filter_input( INPUT_GET, 'end' ) ) ) {
			$end = filter_input( INPUT_POST, 'end' );
		}
		if ( empty( $end ) || ! Date::is_date_exists( $end, 'Y-m-d' ) ) {
			$edatetime = new \DateTime( 'now', $timezone );
			$end       = $edatetime->format( 'Y-m-d' );
		} else {
			$edatetime = new \DateTime( $end, $timezone );
		}
		if ( $edatetime->getTimestamp() < $sdatetime->getTimestamp() ) {
			$start = $edatetime->format( 'Y-m-d' );
			$end   = $sdatetime->format( 'Y-m-d' );
		}
		$analytics = new Analytics( $type, $context, $site, $start, $end, $id );
		include TRAFFIC_ADMIN_DIR . 'partials/traffic-admin-view-' . strtolower( $type ) . '.php';
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
				Option::network_set( 'auto_update', array_key_exists( 'traffic_plugin_options_autoupdate', $_POST ) );
				Option::network_set( 'display_nag', array_key_exists( 'traffic_plugin_options_nag', $_POST ) );
				Option::network_set( 'inbound_capture', array_key_exists( 'traffic_inbound_options_capture', $_POST ) );
				Option::network_set( 'outbound_capture', array_key_exists( 'traffic_outbound_options_capture', $_POST ) );
				Option::network_set( 'inbound_cut_path', array_key_exists( 'traffic_inbound_options_cut_path', $_POST ) ? (int) filter_input( INPUT_POST, 'traffic_inbound_options_cut_path' ) : Option::network_get( 'traffic_inbound_options_cut_path' ) );
				Option::network_set( 'outbound_cut_path', array_key_exists( 'traffic_outbound_options_cut_path', $_POST ) ? (int) filter_input( INPUT_POST, 'traffic_outbound_options_cut_path' ) : Option::network_get( 'traffic_outbound_options_cut_path' ) );
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
			'traffic_plugin_options_autoupdate',
			__( 'Plugin updates', 'traffic' ),
			[ $form, 'echo_field_checkbox' ],
			'traffic_plugin_options_section',
			'traffic_plugin_options_section',
			[
				'text'        => esc_html__( 'Automatic (recommended)', 'traffic' ),
				'id'          => 'traffic_plugin_options_autoupdate',
				'checked'     => Option::network_get( 'auto_update' ),
				'description' => esc_html__( 'If checked, Traffic will update itself as soon as a new version is available.', 'traffic' ),
				'full_width'  => true,
				'enabled'     => true,
			]
		);
		register_setting( 'traffic_plugin_options_section', 'traffic_plugin_options_autoupdate' );
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
				'full_width'  => true,
				'enabled'     => true,
			]
		);
		register_setting( 'traffic_plugin_options_section', 'traffic_plugin_options_nag' );
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
				'full_width'  => true,
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
				'full_width'  => true,
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
				'full_width'  => true,
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
				'full_width'  => true,
				'enabled'     => true,
			]
		);
		register_setting( 'traffic_outbound_options_section', 'traffic_outbound_options_cut_path' );
	}

}
