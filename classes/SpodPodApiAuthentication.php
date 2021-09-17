<?php
/**
 * handle api authentication
 *
 * @link       https://www.spod.com
 * @since      1.0.0
 * @see        https://rest.spod.com/docs/#section/Authentication
 * @package    wc-spod
 * @subpackage wc-spod/classes
 */

class SpodPodApiAuthentication extends SpodPodApiHandler
{

    private $resource_authentication = 'authentication';

    /**
     * ApiOrders constructor.
     * @since      1.0.0
     */
    public function __construct()
    {
        // not needed
    }

    /**
     * test if api token is valid
     * @since      1.0.0
     * @param string $token
     * @return bool
     */
    public function testAuthentication($token)
    {
        $token = get_option('ng_spod_pod_token')!==$token ? update_option('ng_spod_pod_token', $token) : $token;
        $check = $this->setRequest($this->api_url.$this->resource_authentication);
        if ( isset($check->status) && ($check->status==401) ) {
            update_option('ng_spod_pod_token', null);
            update_option('ng_spod_pod_isconnected', null);
            return false;
        }
        if ( isset($check->merchantId) &&  ($check->merchantId>0) ) {
            return true;
        }
    }

    /**
     * disconnect plugin from api and delete spod products with its attachements
     * @since      1.0.0
     * @return bool
     */
    public function disconnectPlugin()
    {
        global $wpdb;

        // remove webhooks
        $Subscriptions = new SpodPodApiSubscriptions();
        $Subscriptions->deleteSubscriptions();

        // deactivate/delete only spod products
        $sql = "SELECT pm.post_id, pm.meta_value FROM $wpdb->postmeta as pm WHERE pm.meta_key='_spod_sku'";
        $product_data = $wpdb->get_results($sql);
        $article = new SpodPodApiArticles();
        if(count($product_data)>0) {
            foreach ($product_data as $sku) {
                $article->deleteArticle($sku->meta_value);
            }
        }

        // update wp options
        update_option('ng_spod_pod_token', NULL);
        update_option('ng_spod_pod_isconnected', NULL);

        return true;
    }

    /**
     * get data from spod: subscriptions, products
     * @since      1.0.0
     * @param string $token
     * @return bool $check
     */
    public function connectPlugin($token)
    {
        //update wp options
        $connected_date = date('Y-m-d H:i:s');
        update_option('ng_spod_pod_isconnected', $connected_date);
        update_option('ng_spod_pod_token', $token);

        // add webhooks
        $Subscriptions = new SpodPodApiSubscriptions();
        $Subscriptions->addSubscription('shipment_sent');
        $Subscriptions->addSubscription('order_cancelled');
        $Subscriptions->addSubscription('order_processed');
        $Subscriptions->addSubscription('article_added');
        $Subscriptions->addSubscription('article_removed');
        $Subscriptions->addSubscription('article_updated');

        // load products
        $ApiArticles = new SpodPodApiArticles();
        $articles = $ApiArticles->getArticles();

        return [
            'connected_date' => $connected_date,
            'count' => count($articles),
            'articles' => $articles,
        ];
    }
}
