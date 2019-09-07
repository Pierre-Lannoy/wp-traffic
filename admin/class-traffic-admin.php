<?php
/**
 * The admin-specific functionality of the plugin.
 *
 * @package    Plugin
 * @author  Pierre Lannoy <https://pierre.lannoy.fr/>.
 * @since   1.0.0
 */

namespace Traffic\Plugin;

use Traffic\System\Assets;
use Traffic\System\Role;
use Traffic\System\Option;
use Traffic\Plugin\Feature\InlineHelp;

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
		if ( isset( $this->current_view ) ) {
			$this->current_view->get();
		} else {
			include TRAFFIC_ADMIN_DIR . 'partials/traffic-admin-view-statistics.php';
		}
	}

	/**
	 * Get the content of the settings page.
	 *
	 * @since 1.0.0
	 */
	public function get_settings_page() {
		/*$this->current_handler = null;
		$this->current_logger  = null;
		if ( ! ( $action = filter_input( INPUT_GET, 'action' ) ) ) {
			$action = filter_input( INPUT_POST, 'action' );
		}
		if ( ! ( $tab = filter_input( INPUT_GET, 'tab' ) ) ) {
			$tab = filter_input( INPUT_POST, 'tab' );
		}
		if ( ! ( $handler = filter_input( INPUT_GET, 'handler' ) ) ) {
			$handler = filter_input( INPUT_POST, 'handler' );
		}
		if ( ! ( $uuid = filter_input( INPUT_GET, 'uuid' ) ) ) {
			$uuid = filter_input( INPUT_POST, 'uuid' );
		}
		$nonce = filter_input( INPUT_GET, 'nonce' );
		if ( $uuid ) {
			$loggers = Option::get( 'loggers' );
			if ( array_key_exists( $uuid, $loggers ) ) {
				$this->current_logger         = $loggers[ $uuid ];
				$this->current_logger['uuid'] = $uuid;
			}
		}
		if ( $handler ) {
			$handlers              = new HandlerTypes();
			$this->current_handler = $handlers->get( $handler );
		} elseif ( $this->current_logger ) {
			$handlers              = new HandlerTypes();
			$this->current_handler = $handlers->get( $this->current_logger['handler'] );
		}
		if ( $this->current_handler && ! $this->current_logger ) {
			$this->current_logger = [
				'uuid'    => $uuid = UUID::generate_v4(),
				'name'    => esc_html__( 'New logger', 'traffic' ),
				'handler' => $this->current_handler['id'],
				'running' => Option::get( 'logger_autostart' ),
			];
		}
		if ( $this->current_logger ) {
			$factory              = new LoggerFactory();
			$this->current_logger = $factory->check( $this->current_logger );
		}*/
		$view = 'traffic-admin-settings-main';
		/*if ( $action && $tab ) {
			switch ( $tab ) {
				case 'loggers':
					switch ( $action ) {
						case 'form-edit':
							if ( Role::SUPER_ADMIN === Role::admin_type() || Role::SINGLE_ADMIN === Role::admin_type() ) {
								$current_logger  = $this->current_logger;
								$current_handler = $this->current_handler;
								$args            = compact( 'current_logger', 'current_handler' );
								$view            = 'traffic-admin-settings-logger-edit';
							}
							break;
						case 'form-delete':
							if ( Role::SUPER_ADMIN === Role::admin_type() || Role::SINGLE_ADMIN === Role::admin_type() ) {
								$current_logger  = $this->current_logger;
								$current_handler = $this->current_handler;
								$args            = compact( 'current_logger', 'current_handler' );
								$view            = 'traffic-admin-settings-logger-delete';
							}
							break;
						case 'do-edit':
							if ( Role::SUPER_ADMIN === Role::admin_type() || Role::SINGLE_ADMIN === Role::admin_type() ) {
								$this->save_current();
							}
							break;
						case 'do-delete':
							if ( Role::SUPER_ADMIN === Role::admin_type() || Role::SINGLE_ADMIN === Role::admin_type() ) {
								$this->delete_current();
							}
							break;
						case 'start':
							if ( Role::SUPER_ADMIN === Role::admin_type() || Role::SINGLE_ADMIN === Role::admin_type() ) {
								if ( $nonce && $uuid && wp_verify_nonce( $nonce, 'traffic-logger-start-' . $uuid ) ) {
									$loggers = Option::get( 'loggers' );
									if ( array_key_exists( $uuid, $loggers ) ) {
										$loggers[ $uuid ]['running'] = true;
										Option::set( 'loggers', $loggers );
										$this->logger = Log::bootstrap( 'plugin', TRAFFIC_PRODUCT_SHORTNAME, TRAFFIC_VERSION );
										$message      = sprintf( esc_html__( 'Logger %s has started.', 'traffic' ), '<em>' . $loggers[ $uuid ]['name'] . '</em>' );
										$code         = 0;
										add_settings_error( 'traffic_no_error', $code, $message, 'updated' );
										$this->logger->info( sprintf( 'Logger "%s" has started.', $loggers[ $uuid ]['name'] ), $code );
									}
								}
							}
							break;
						case 'pause':
							if ( Role::SUPER_ADMIN === Role::admin_type() || Role::SINGLE_ADMIN === Role::admin_type() ) {
								if ( $nonce && $uuid && wp_verify_nonce( $nonce, 'traffic-logger-pause-' . $uuid ) ) {
									$loggers = Option::get( 'loggers' );
									if ( array_key_exists( $uuid, $loggers ) ) {
										$message = sprintf( esc_html__( 'Logger %s has been paused.', 'traffic' ), '<em>' . $loggers[ $uuid ]['name'] . '</em>' );
										$code    = 0;
										$this->logger->notice( sprintf( 'Logger "%s" has been paused.', $loggers[ $uuid ]['name'] ), $code );
										$loggers[ $uuid ]['running'] = false;
										Option::set( 'loggers', $loggers );
										$this->logger = Log::bootstrap( 'plugin', TRAFFIC_PRODUCT_SHORTNAME, TRAFFIC_VERSION );
										add_settings_error( 'traffic_no_error', $code, $message, 'updated' );
									}
								}
							}
						case 'test':
							if ( Role::SUPER_ADMIN === Role::admin_type() || Role::SINGLE_ADMIN === Role::admin_type() ) {
								if ( $nonce && $uuid && wp_verify_nonce( $nonce, 'traffic-logger-test-' . $uuid ) ) {
									$loggers = Option::get( 'loggers' );
									if ( array_key_exists( $uuid, $loggers ) ) {
										$test = Log::bootstrap( 'plugin', TRAFFIC_PRODUCT_SHORTNAME, TRAFFIC_VERSION, $uuid );
										$done = true;
										$done = $done & $test->debug( 'Debug test message.', 210871 );
										$done = $done & $test->info( 'Info test message.', 210871 );
										$done = $done & $test->notice( 'Notice test message.', 210871 );
										$done = $done & $test->warning( 'Warning test message.', 210871 );
										$done = $done & $test->error( 'Error test message.', 210871 );
										$done = $done & $test->critical( 'Critical test message.', 210871 );
										$done = $done & $test->alert( 'Alert test message.', 210871 );
										$done = $done & $test->emergency( 'Emergency test message.', 210871 );
										if ( $done ) {
											$message = sprintf( esc_html__( 'Test messages have been sent to logger %s.', 'traffic' ), '<em>' . $loggers[ $uuid ]['name'] . '</em>' );
											$code    = 0;
											$this->logger->info( sprintf( 'Logger "%s" has been tested.', $loggers[ $uuid ]['name'] ), $code );
											add_settings_error( 'traffic_no_error', $code, $message, 'updated' );
										} else {
											$message = sprintf( esc_html__( 'Test messages have not been sent to logger %s. Please check the logger\'s settings.', 'traffic' ), '<em>' . $loggers[ $uuid ]['name'] . '</em>' );
											$code    = 1;
											$this->logger->warning( sprintf( 'Logger "%s" has been unsuccessfully tested.', $loggers[ $uuid ]['name'] ), $code );
											add_settings_error( 'traffic_error', $code, $message, 'error' );
										}
									}
								}
							}
					}
					break;
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
				case 'listeners':
					switch ( $action ) {
						case 'do-save':
							if ( Role::SUPER_ADMIN === Role::admin_type() || Role::SINGLE_ADMIN === Role::admin_type() ) {
								if ( ! empty( $_POST ) && array_key_exists( 'submit', $_POST ) ) {
									$this->save_listeners();
								} elseif ( ! empty( $_POST ) && array_key_exists( 'reset-to-defaults', $_POST ) ) {
									$this->reset_listeners();
								}
							}
							break;
					}
					break;
			}
		}*/
		include TRAFFIC_ADMIN_DIR . 'partials/' . $view . '.php';
	}

}
