<?php
/**
 * cron file - setup background tasks
 *
 * @since             1.0.0
 * @package           wc-spod
 *
 */
//add cron scheduler to run in every 15 minutes
add_filter( 'cron_schedules', 'spodpod_scheduler_image_add_delete' );
function spodpod_scheduler_image_add_delete( $schedules ) {
    $schedules['spodpod_image_add_delete_cron_event'] = array(
            'interval'  => 900,
            'display'   => __( 'Every 15 Minutes' )
    );
    return $schedules;
}

function spodpod_schedule_my_cron(){
    if ( ! wp_next_scheduled( 'spodpod_scheduler_image_add_delete' ) ) {
        wp_schedule_event( time(), 'spodpod_image_add_delete_cron_event', 'spodpod_scheduler_image_add_delete' );
    }
}
add_action( 'wp', 'spodpod_schedule_my_cron' );

add_action( 'spodpod_scheduler_image_add_delete', 'spodpod_image_add_delete_cron_events_func' );
function spodpod_image_add_delete_cron_events_func() {
   $SpodPodApiArticles = new SpodPodApiArticles();
   $SpodPodApiArticles->addSaveProducts();
   $SpodPodApiArticles->saveProductImages();
   $SpodPodApiArticles->deleteProductImages();
}
// add_action("wp_ajax_spodpod_image_add_delete_cron_events_func","spodpod_image_add_delete_cron_events_func");
// add_action("wp_ajax_nopriv_spodpod_image_add_delete_cron_events_func","spodpod_image_add_delete_cron_events_func");