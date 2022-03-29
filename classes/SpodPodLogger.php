<?php
/**
 * Save events for reporting
 *
 * @since      1.2.0
 * @package    wc-spod
 * @subpackage wc-spod/classes
 */
class SpodPodLogger {

    /**
     * limit to show and hold entries in table
     * @var int
     */
    private $limit = 50;

    /**
     * get latest entries
     *
     * @since    1.1.1
     */
    public function getLatestEvents() {
        global $wpdb;
        $table_import_log = SPOD_SHOP_IMPORT_LOGS;
        $sql = "SELECT * FROM $table_import_log ORDER BY created_at DESC LIMIT $this->limit";
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
            'created_at' => date('Y-m-d H:i:s')
        ]);
    }

    /**
     * cleanup old entries
     */
    public function cleanupTable()
    {
        global $wpdb;
        $table_import_log = SPOD_SHOP_IMPORT_LOGS;
        $sql = "SELECT ID FROM $table_import_log ORDER BY created_at DESC LIMIT $this->limit, 1";
        $latestEntry = $wpdb->get_row($sql);

        if (isset($latestEntry) && $latestEntry->ID>0) {
            $sql = "DELETE FROM $table_import_log WHERE ID < $latestEntry->ID";
            $wpdb->query($sql);
        }
    }
}