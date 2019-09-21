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

wp_enqueue_style( TRAFFIC_ASSETS_ID );

?>

<div class="wrap">
	<div class="traffic-dashboard">
		<div class="traffic-row">
			<?php echo $analytics->get_title() ?>
		</div>
		<div class="traffic-row">
			<?php echo $analytics->get_large_kpi( 'server-error-rate') ?>
		</div>

	</div>


</div>
