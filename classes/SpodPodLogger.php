<?php
/**
 * Save events for reporting
 *
 * @since      1.1.1
 * @package    wc-spod
 * @subpackage wc-spod/classes
 */
class SpodPodLogger {

    /**
     * get latest entries
     *
     * @since    1.1.1
     */
    public function getLatestEvents($limit = 50) {
        global $wpdb;
        $table_import_log = SPOD_SHOP_IMPORT_LOGS;
        $sql = "select * from $table_import_log ORDER BY created_at DESC LIMIT $limit";
        return $wpdb->get_results($sql);
    }

    /**
     * static helper to log everywhere into log table
     *
     */
    public static function logEvent($title, $text)
    {
        global $wpdb;
        $table_import_log = SPOD_SHOP_IMPORT_LOGS;
        $wpdb->insert($table_import_log, [
            'title' => $title,
            'description' => $text,
            'created_at' => date('Y-m-d h:i:s')
        ]);
    }
}


