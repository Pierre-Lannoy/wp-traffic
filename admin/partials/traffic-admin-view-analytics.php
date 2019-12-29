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

use Traffic\System\Role;

wp_enqueue_script( 'traffic-moment-with-locale' );
wp_enqueue_script( 'traffic-daterangepicker' );
wp_enqueue_script( 'traffic-switchery' );
wp_enqueue_script( 'traffic-chartist' );
wp_enqueue_script( 'traffic-chartist-tooltip' );
wp_enqueue_script( 'traffic-jvectormap' );
wp_enqueue_script( 'traffic-jvectormap-world' );
wp_enqueue_script( TRAFFIC_ASSETS_ID );
wp_enqueue_style( TRAFFIC_ASSETS_ID );
wp_enqueue_style( 'traffic-daterangepicker' );
wp_enqueue_style( 'traffic-switchery' );
wp_enqueue_style( 'traffic-tooltip' );
wp_enqueue_style( 'traffic-chartist' );
wp_enqueue_style( 'traffic-chartist-tooltip' );
wp_enqueue_style( 'traffic-jvectormap' );


?>

<div class="wrap">
	<div class="traffic-dashboard">
		<div class="traffic-row">
			<?php echo $analytics->get_title_bar() ?>
		</div>
        <div class="traffic-row">
	        <?php echo $analytics->get_kpi_bar() ?>
        </div>
        <?php if ( 'summary' === $analytics->type && '' === $analytics->extra ) { ?>
            <div class="traffic-row">
                <div class="traffic-box traffic-box-40-60-line">
                    <?php echo $analytics->get_top_domain_box() ?>
                    <?php echo $analytics->get_map_box() ?>
                </div>
            </div>
		<?php } ?>
		<?php if ( 'domain' === $analytics->type && '' === $analytics->extra ) { ?>
            <div class="traffic-row">
                <div class="traffic-box traffic-box-40-60-line">
					<?php echo $analytics->get_top_authority_box() ?>
					<?php echo $analytics->get_map_box() ?>
                </div>
            </div>
		<?php } ?>
		<?php if ( 'authority' === $analytics->type && '' === $analytics->extra ) { ?>
            <div class="traffic-row">
                <div class="traffic-box traffic-box-40-60-line">
					<?php echo $analytics->get_top_endpoint_box() ?>
					<?php echo $analytics->get_map_box() ?>
                </div>
            </div>
		<?php } ?>
		<?php if ( ( 'summary' === $analytics->type || 'domain' === $analytics->type || 'authority' === $analytics->type || 'endpoint' === $analytics->type ) && '' === $analytics->extra ) { ?>
			<?php echo $analytics->get_main_chart() ?>
            <div class="traffic-row">
                <div class="traffic-box traffic-box-33-33-33-line">
					<?php echo $analytics->get_codes_box() ?>
					<?php echo $analytics->get_security_box() ?>
					<?php echo $analytics->get_method_box() ?>
                </div>
            </div>
			<?php if ( Role::SUPER_ADMIN === Role::admin_type() && 'all' === $analytics->site) { ?>
                <div class="traffic-row last-row">
					<?php echo $analytics->get_sites_list() ?>
                </div>
			<?php } ?>
		<?php } ?>

		<?php if ( 'domains' === $analytics->type && '' === $analytics->extra ) { ?>
            <div class="traffic-row">
	            <?php echo $analytics->get_domains_list() ?>
            </div>
		<?php } ?>
		<?php if ( 'authorities' === $analytics->type && '' === $analytics->extra ) { ?>
            <div class="traffic-row">
				<?php echo $analytics->get_authorities_list() ?>
            </div>
		<?php } ?>
		<?php if ( 'endpoints' === $analytics->type && '' === $analytics->extra ) { ?>
            <div class="traffic-row">
				<?php echo $analytics->get_endpoints_list() ?>
            </div>
		<?php } ?>
		<?php if ( '' !== $analytics->extra ) { ?>
            <div class="traffic-row">
				<?php echo $analytics->get_extra_list() ?>
            </div>
		<?php } ?>
	</div>
</div>
