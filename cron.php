<?php
/**
 * cron file - setup background tasks
 *
 * @since             1.0.0
 * @package           wc-spod
 *
 */

function spodpod_scheduler( $schedules ) {
    $schedules['spodpod_image_add_delete_cron_event'] = [
        'interval'  => 900,
        'display'   => __( 'Every 15 Minutes' )
    ];
    $schedules['spodpod_logger_cleanup_event'] = [
        'interval'  => 3600,
        'display'   => __( 'Every Hour' )
    ];
    return $schedules;
}
add_filter( 'cron_schedules', 'spodpod_scheduler' );


function spodpod_schedule_my_cron(){
    if ( ! wp_next_scheduled( 'spodpod_scheduler_image_add_delete' ) ) {
        wp_schedule_event( time(), 'spodpod_image_add_delete_cron_event', 'spodpod_scheduler_image_add_delete' );
    }
    if ( ! wp_next_scheduled( 'spodpod_logger_cleanup' ) ) {
        wp_schedule_event( time(), 'spodpod_logger_cleanup_event', 'spodpod_logger_cleanup' );
    }
}
add_action( 'wp', 'spodpod_schedule_my_cron' );


function spodpod_image_add_delete_cron_events_func() {
    SpodPodLogger::logEvent('cron product import', 'spodpod_image_add_delete_cron_events_func');

    $SpodPodApiArticles = new SpodPodApiArticles();
    $SpodPodApiArticles->addSaveProducts();
    $SpodPodApiArticles->saveProductImages();
    $SpodPodApiArticles->deleteProductImages();
}
add_action( 'spodpod_scheduler_image_add_delete', 'spodpod_image_add_delete_cron_events_func' );


function spodpod_logger_cleanup_func(){
    SpodPodLogger::logEvent('cron logger cleanup', 'spodpod_logger_cleanup_func');

    $SpodLogger = new SpodPodLogger();
    $SpodLogger->cleanupTable();
}
add_action( 'spodpod_logger_cleanup', 'spodpod_logger_cleanup_func' );
