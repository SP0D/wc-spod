<?php
require_once plugin_dir_path( dirname( __FILE__ ) ) . 'classes/SpodPodApiAuthentication.php';
/**
 * Fired during plugin deactivation.
 *
 * @since      1.0.0
 * @package    wc-spod
 * @subpackage wc-spod/includes
 */
class SpodPodDeactivator {

	/**
	 * delete tables and plugin options
	 * @since    1.0.0
	 */
	public static function deactivate()
    {
        global $wpdb;

        update_option( 'spodpod_flush_rewrite_rules_flag', false );
        flush_rewrite_rules();

        delete_option('spodpod_plugin_version');

        // plugin disconnection and product deleting only with woocommerce possible
        if ( function_exists( 'WC' ) ) {
            $Api = new SpodPodApiAuthentication();
            $Api->disconnectPlugin();
	    }

        // delete tables
        $table_name = SPOD_SHOP_IMPORT_IMAGES;
        $wpdb->query("TRUNCATE TABLE $table_name");
        $wpdb->query("DROP TABLE $table_name");

        $table_name = SPOD_SHOP_IMPORT_PRODUCTS;
        $wpdb->query("TRUNCATE TABLE $table_name");
        $wpdb->query("DROP TABLE $table_name");

        $table_name = SPOD_SHOP_IMPORT_LOGS;
        $wpdb->query("TRUNCATE TABLE $table_name");
        $wpdb->query("DROP TABLE $table_name");

    }

}