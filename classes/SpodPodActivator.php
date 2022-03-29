<?php
/**
 * Fired during plugin activation.
 *
 * @since      1.0.0
 * @package    wc-spod
 * @subpackage wc-spod/classes
 */
class SpodPodActivator
{
    /**
     * create tables and options
     * @since    1.0.0
     */
	public static function activate()
	{
        global $wpdb;

        // set option
        update_option('spodpod_flush_rewrite_rules_flag', true);
        update_option('spodpod_plugin_version', SPOD_POD_VERSION);

        add_rewrite_rule('^wc-spod-webhook/([^/]*)/?', 'index.php?wcspodhooktype=$matches[1]', 'top');
        flush_rewrite_rules();

        $charset_collate = $wpdb->get_charset_collate();
        $table_import_images = SPOD_SHOP_IMPORT_IMAGES;
        $table_import_products = SPOD_SHOP_IMPORT_PRODUCTS;
        $table_import_log = SPOD_SHOP_IMPORT_LOGS;

        $sql1 = "CREATE TABLE $table_import_images (
          ID mediumint(9) NOT NULL AUTO_INCREMENT,
          product_id int(128) NOT NULL,
          images_data text,
          action varchar(255) NOT NULL,
          status int(11) NOT NULL,
          attachment_id int(11),	
          created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
          PRIMARY KEY  (id)
        ) $charset_collate;";


        $sql2 = "CREATE TABLE $table_import_products (
          ID mediumint(9) NOT NULL AUTO_INCREMENT,
          product_id int(128) NOT NULL,
          title varchar(255) NOT NULL,
          description text,
          variants_data text,
          images_data text,
          status int(11) NOT NULL,
          created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
          PRIMARY KEY  (id)
        ) $charset_collate;";

        $sql3 = "CREATE TABLE $table_import_log (
          ID mediumint(9) NOT NULL AUTO_INCREMENT,
          title varchar(255) NOT NULL,
          description text,
          created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
          PRIMARY KEY  (id)
        ) $charset_collate;";

        require_once(ABSPATH . 'wp-admin/includes/upgrade.php');
        dbDelta($sql1);
        dbDelta($sql2);
        dbDelta($sql3);
	}
}