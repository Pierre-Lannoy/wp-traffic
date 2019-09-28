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

wp_enqueue_script( 'moment-with-locale' );
wp_enqueue_script( 'daterangepicker' );
wp_enqueue_script( 'switchery' );
wp_enqueue_script( TRAFFIC_ASSETS_ID );
wp_enqueue_style( TRAFFIC_ASSETS_ID );
wp_enqueue_style( 'daterangepicker' );
wp_enqueue_style( 'switchery' );
wp_enqueue_style( 'traffic-tooltip' );

?>

<div class="wrap">
	<div class="traffic-dashboard">
		<div class="traffic-row">
			<?php echo $analytics->get_title_bar() ?>
		</div>
        <div class="traffic-row">
	        <?php echo $analytics->get_kpi_bar() ?>
        </div>
        <?php if ( 'summary' === $analytics->type) { ?>
            <div class="traffic-row">
                <div class="traffic-box traffic-box-40-60-line">
                    <?php echo $analytics->get_top_domain_box() ?>
                    <?php echo $analytics->get_map_box() ?>
                </div>
            </div>
		<?php } ?>
		<?php if ( 'domain' === $analytics->type) { ?>
            <div class="traffic-row">
                <div class="traffic-box traffic-box-40-60-line">
					<?php echo $analytics->get_top_authority_box() ?>
					<?php echo $analytics->get_map_box() ?>
                </div>
            </div>
		<?php } ?>
		<?php if ( 'authority' === $analytics->type) { ?>
            <div class="traffic-row">
                <div class="traffic-box traffic-box-40-60-line">
					<?php echo $analytics->get_top_endpoint_box() ?>
					<?php echo $analytics->get_map_box() ?>
                </div>
            </div>
		<?php } ?>
		<?php if ( 'domains' === $analytics->type) { ?>
            <div class="traffic-row">
	            <?php echo $analytics->get_domains_list() ?>
            </div>
		<?php } ?>
		<?php if ( 'authorities' === $analytics->type) { ?>
            <div class="traffic-row">
				<?php echo $analytics->get_authorities_list() ?>
            </div>
		<?php } ?>
		<?php if ( 'endpoints' === $analytics->type) { ?>
            <div class="traffic-row">
				<?php echo $analytics->get_endpoints_list() ?>
            </div>
		<?php } ?>
		<div class="traffic-row">

		</div>

	</div>


</div>
