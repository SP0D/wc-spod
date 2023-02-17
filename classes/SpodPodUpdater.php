<?php
/**
 * plugin updater.
 *
 * @since      1.2.0
 * @package    wc-spod
 * @subpackage wc-spod/classes
 */
class SpodPodUpdater
{
    /**
     * add sql tables for background cronjobs
     * @since 1.2.0
     */
	public static function update120()
	{
        global $wpdb;

        $plugin_version = get_option('spodpod_plugin_version');
        $new_plugin_version = "1.2.0";
        if (version_compare($plugin_version, $new_plugin_version, '<')) {
            // version 1.2.0 update
            $table_import_log = SPOD_SHOP_IMPORT_LOGS;
            $charset_collate = $wpdb->get_charset_collate();

            $sql = "CREATE TABLE IF NOT EXISTS $table_import_log (
              ID mediumint(9) NOT NULL AUTO_INCREMENT,
              title varchar(255) NOT NULL,
              description text,
              created_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
              PRIMARY KEY  (id)
            )";

            require_once( ABSPATH . 'wp-admin/includes/upgrade.php' );
            dbDelta($sql);

            update_option('spodpod_plugin_version', $new_plugin_version);
        }
	}

    /**
     * flush rewrites after update and save new version number
     * @since 1.2.0
     */
    public static function update210() {
        $plugin_version = get_option('spodpod_plugin_version');
        $new_plugin_version = "2.1.0";

        if (version_compare($plugin_version, $new_plugin_version, '<')) {
            update_option('spodpod_plugin_version', $new_plugin_version);
            flush_rewrite_rules();
        }
    }
}