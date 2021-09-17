<?php
/**
 * mysql tables - conjob will use this table to import images and products in the background, to prevent timeouts
 *
 * @since             1.0.0
 * @package           wc-spod
 *
 */
global $wpdb;

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