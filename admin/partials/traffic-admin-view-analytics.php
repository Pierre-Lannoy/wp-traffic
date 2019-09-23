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






		<div class="traffic-row">

		</div>

	</div>


</div>
