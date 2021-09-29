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
    public function registerRewritePage()
    {
        global $wp_rewrite;
        add_rewrite_rule('^wc-spod-webhook/([^/]*)/?', 'index.php?wcspodhooktype=$matches[1]', 'top');

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
        return $query_vars;
    }

    /**
     *
     * @param string $type
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