<?php
/**
 * Rest API Class
 *
 * @link       https://www.spod.com
 * @since      2.0.0
 * @package    wc-spod
 * @subpackage wc-spod/classes
 */

class SpodPodRestApiHandler
{
    /**
     * check woocommerce api key
     *
     * @since      2.0.0
     */
    public function checkRestApi()
    {
        global $wpdb, $table_prefix;

        $stmt = "SELECT consumer_key, consumer_secret FROM ".$table_prefix."woocommerce_api_keys WHERE description LIKE '%spod - API%' ORDER BY key_id DESC LIMIT 1";
        return $wpdb->get_row($stmt);
    }
}