<?php
/**
 * Provide a admin-facing view for the plugin
 *
 * This file is used to markup the admin-facing aspects of the plugin.
 *
 * @package    Plugin
 * @author  Pierre Lannoy <https://pierre.lannoy.fr/>.
 * @since   1.0.0
 */

use Traffic\System\Option;

if ( ! Option::network_get( 'livelog' ) ) {
	Option::network_set( 'livelog', true );
}

wp_localize_script(
	TRAFFIC_LIVELOG_ID,
	'livelog',
	[
		'restUrl'   => esc_url_raw( rest_url() . TRAFFIC_REST_NAMESPACE . '/livelog' ),
		'restNonce' => wp_create_nonce( 'wp_rest' ),
		'buffer'    => 200,
		'frequency' => 750,
	]
);

wp_enqueue_style( TRAFFIC_LIVELOG_ID );
wp_enqueue_script( TRAFFIC_LIVELOG_ID );
?>

<div class="wrap">
	<h2><?php echo sprintf( esc_html__( '%s Live API Calls', 'traffic' ), TRAFFIC_PRODUCT_NAME );?></h2>
    <div class="media-toolbar wp-filter traffic-pilot-toolbar" style="border-radius:4px;">
        <div class="media-toolbar-secondary" data-children-count="2">
            <div class="view-switch media-grid-view-switch">
                <span class="dashicons dashicons-controls-play traffic-control traffic-control-inactive" id="traffic-control-play"></span>
                <span class="dashicons dashicons-controls-pause traffic-control traffic-control-inactive" id="traffic-control-pause"></span>
            </div>
            <select id="traffic-select-bound" class="attachment-filters">
                <option value="both"><?php echo esc_html__( 'Both', 'traffic' );?></option>
                <option value="inbound"><?php echo esc_html__( 'Inbounds', 'traffic' );?></option>
                <option value="outbound"><?php echo esc_html__( 'Outbounds', 'traffic' );?>
            </select>
            <div class="view-switch media-grid-view-switch" style="display: inline;">
                <span class="traffic-control-hint" style="float: right">initializing&nbsp;&nbsp;&nbsp;⚪</span>
            </div>
        </div></div>

    <div class="traffic-logger-view"><div id="traffic-logger-lines"></div></div>

</div>
