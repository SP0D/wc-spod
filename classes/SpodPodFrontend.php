<?php
/**
 * The public-facing functionality of the plugin.
 *
 * @since      1.0.0
 * @package    wc-spod
 * @subpackage wc-spod/classes
 **/

class SpodPodFrontend
{

    /**
     * The ID of this plugin.
     *
     * @since    1.0.0
     * @var      string $plugin_name The ID of this plugin.
     */
    private $plugin_name;

    /**
     * The version of this plugin.
     *
     * @since    1.0.0
     * @var      string $version The current version of this plugin.
     */
    private $version;

    /**
     * Initialize the class and set its properties.
     *
     * @param string $plugin_name The name of this plugin.
     * @param string $version The version of this plugin.
     * @since    1.0.0
     */
    public function __construct($plugin_name, $version)
    {
        $this->plugin_name = $plugin_name;
        $this->version = $version;
    }

    /**
     * webhook rewrite
     *
     * @since 1.0.0
     */
    public function registerRewritePages()
    {
        global $wp_rewrite;
        add_rewrite_rule('^wc-spod-webhook/([^/]*)/?', 'index.php?wcspodhooktype=$matches[1]', 'top');
        add_rewrite_rule('^wc-spod-product/(\d+)/(\d+)/?', 'index.php?check=123&offset=$matches[1]&limit=$matches[2]', 'top');

        if( get_option('spodpod_flush_rewrite_rules_flag')==1 ) {
            flush_rewrite_rules();
            update_option('spodpod_flush_rewrite_rules_flag', 0);
        }
    }

    /**
     * query get parameter
     *
     * @param array queryWebhookVars
     * @since 1.0.0
     */
    function parseWebhookVars(&$wp)
    {
        if ($wp->matched_rule=="^wc-spod-webhook/([^/]*)/?" && isset($wp->query_vars['wcspodhooktype'])) {
            $this->handleWebhook($wp->query_vars['wcspodhooktype']);
        }

        if ($wp->matched_rule=="^wc-spod-product/(\d+)/(\d+)/?") {
            $this->handleProduct($wp->query_vars['offset'], $wp->query_vars['limit']);
        }

    }

    /**
     * query get parameter
     *
     * @param int $offset
     * @param int $limit
     * @since 2.0.0
     */
    protected function handleProduct($offset = 0, $limit = 1000)
    {
        global $wpdb;
        // count
        $countStmt = "SELECT COUNT(*) as maxProduct
                FROM $wpdb->postmeta pm 
                LEFT JOIN $wpdb->posts as p ON p.ID = pm.post_id 
                WHERE pm.meta_key='_spod_product' AND pm.meta_value = 'spod_product'";
        $counter = $wpdb->get_row($countStmt)->maxProduct;

        // entries
        $productStmt = "SELECT pm.post_id,p.post_type, p.post_parent  
                FROM $wpdb->postmeta pm 
                LEFT JOIN $wpdb->posts as p ON p.ID = pm.post_id 
                WHERE pm.meta_key='_spod_product' AND pm.meta_value = 'spod_product'
                LIMIT %d,%d";
        $query = $wpdb->prepare($productStmt, $offset, $limit);
        $spodProducts = $wpdb->get_results( $query );

        #if ( $wpdb->last_error ) {
        #    echo 'wpdb error: ' . $wpdb->last_error;
        #}

        $productsArray = [
            'count' => $counter,
            'offset' => $offset,
            'limit' => $limit,
            'items' => []
        ];

        if ($wpdb->num_rows>0) {
            foreach ($spodProducts as $spodProduct) {
                $postHelper = get_post($spodProduct->post_id);

                if (isset($postHelper) && $postHelper->ID) {
                    $externalArticleId = ($spodProduct->post_type=='product' ? $postHelper->ID : $postHelper->post_parent);
                    $externalVariantId = $postHelper->ID;
                    $externalImageId = (int) get_post_meta($postHelper->ID, '_thumbnail_id', true);
                    $sku = get_post_meta($postHelper->ID, '_sku', true);
                    $spod_sku = get_post_meta($postHelper->ID, '_spod_sku', true);

                    $productsArray['items'][] = [
                        'externalArticleId' => $externalArticleId,
                        'externalVariantId' => $externalVariantId,
                        'externalImageId' => $externalImageId,
                        'SKU' => $sku,
                        'spod_sku' => $spod_sku
                    ];
                }
            }
        }

        header('Content-Type: application/json; charset=utf-8');
        echo json_encode($productsArray);
        exit();
    }

    /**
     * parse parameter
     *
     * @param array queryWebhookVars
     * @return array queryWebhookVars
     * @since 1.0.0
     */
    function queryWebhookVars($query_vars)
    {
        $query_vars[] = 'wcspodhooktype';
        $query_vars[] = 'offset';
        $query_vars[] = 'limit';

        return $query_vars;
    }

    /**
     *
     * @param string $type
     * @return string
     * @since 1.0.0
     */
    protected function handleWebhook($type)
    {

        http_response_code(202);
        // response need spod api
        echo '[accepted]';
        // webhook data
        $webhook_data = file_get_contents("php://input");
        $spodData = json_decode($webhook_data);

        if (isset($spodData) && isset($spodData->eventType)) {
            $event_type = $spodData->eventType;
            switch ($type):
                case 'order_cancelled':
                    $spod_order_id = $spodData->data->order->id;
                    if (isset($spod_order_id) && $spod_order_id>0) {
                        $ApiOrder = new SpodPodApiOrders();
                        $ApiOrder->webHookOrder($spod_order_id, $type);
                    }
                    break;

                case 'order_processed':
                    $spod_order_id = $spodData->data->order->id;
                    if (isset($spod_order_id) && $spod_order_id>0) {
                        if (isset($spod_order_id) && $spod_order_id>0) {
                            $ApiOrder = new SpodPodApiOrders();
                            $ApiOrder->webHookOrder($spod_order_id, $type);
                        }
                    }
                    break;

                case 'shipment_sent':
                    $spod_order_id = $spodData->data->shipment->orderId;
                    if (isset($spod_order_id) && $spod_order_id>0) {
                        if (isset($spod_order_id) && $spod_order_id>0) {
                            $infos = '';
                            if (isset($spodData->data->shipment->tracking[0]->url)) {
                                $infos = $spodData->data->shipment->tracking[0]->url;
                            }
                            $ApiOrder = new SpodPodApiOrders();
                            $ApiOrder->webHookOrder($spod_order_id, $type, $infos);
                        }
                    }
                    break;

                case 'article_added':
                    if (isset($spodData->data->article->variants[0]->sku)) {
                        $ApiArticle = new SpodPodApiArticles();
                        $ApiArticle->prepareArticle($spodData->data->article);
                    }
                    break;

                case 'article_updated':
                    if (isset($spodData->data->article->variants[0]->sku)) {
                        $ApiArticle = new SpodPodApiArticles();
                        $ApiArticle->prepareArticle($spodData->data->article, 'updated');
                    }
                    break;

                case 'article_removed':
                    if (isset($spodData->data->article->variants[0]->sku)) {
                        $ApiArticle = new SpodPodApiArticles();
                        $ApiArticle->prepareArticle($spodData->data->article, 'delete');
                    }
                    break;

                default:
                    break;

            endswitch;
        }

        exit();
    }
}