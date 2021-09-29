<?php
/**
 * Fired during plugin activation.
 *
 * @since      1.0.0
 * @package    wc-spod
 * @subpackage wc-spod/classes
 */
class SpodPodActivator {

	public static function activate()
	{
        global $wpdb;

        // set option
        update_option( 'spodpod_flush_rewrite_rules_flag', true );

        add_rewrite_rule('^wc-spod-webhook/([^/]*)/?', 'index.php?wcspodhooktype=$matches[1]', 'top');
        flush_rewrite_rules();

        $charset_collate = $wpdb->get_charset_collate();
        $table_name = SPOD_SHOP_IMPORT_IMAGES;
        $sql = "CREATE TABLE $table_name (
          ID mediumint(9) NOT NULL AUTO_INCREMENT,
          product_id int(128) NOT NULL,
          images_data text,
          action varchar(255) NOT NULL,
          status int(11) NOT NULL,
          attachment_id int(11),	
          created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
          PRIMARY KEY  (id)
        ) $charset_collate;";

        $table_product = SPOD_SHOP_IMPORT_PRODUCTS;
        $sql_product = "CREATE TABLE $table_product (
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

        require_once( ABSPATH . 'wp-admin/includes/upgrade.php' );
        dbDelta($sql);
        dbDelta($sql_product);
	}
	
}