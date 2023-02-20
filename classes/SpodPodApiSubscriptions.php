<?php
/**
 * handle api orders to export to spod
 *
 * @link       https://www.spod.com
 * @since      1.0.0
 * @see        https://rest.spod.com/docs/#tag/Articles
 * @package    wc-spod
 * @subpackage wc-spod/classes
 */

class SpodPodApiSubscriptions extends SpodPodApiHandler
{
    private $resource_subscriptions = 'subscriptions';
    private $subscription_types = [
        'shipment_sent'     => 'Shipment.sent',
        'order_cancelled'   => 'Order.cancelled',
        'order_processed'   => 'Order.processed',
        'article_added'     => 'Article.added',
        'article_removed'   => 'Article.removed',
        'article_updated'   => 'Article.updated',
    ];

    /**
     * get all subscriptions from spod
     * @since      1.0.0
     */
    public function getAllSubscriptions()
    {
        $subscriptions = $this->setRequest($this->api_url.$this->resource_subscriptions);

        return $subscriptions;
    }

    /**
     * add subscription
     * @since      1.0.0
     * @param $type
     * @return bool
     */
    public function addSubscription($type)
    {
        $hook_url = $this->getWebhookFileUrl().$type;
        $data = [
            'eventType' => $this->subscription_types[$type],
            'url' => $hook_url,
            'secret' => get_option('ng_spod_pod_token')
        ];
        $this->setRequest($this->api_url.$this->resource_subscriptions,'POST', [],$data);

        return true;
    }

    /**
     * helper function the release webhook file
     * @since      1.0.0
     * @return string
     */
    protected function getWebhookFileUrl()
    {
        return get_bloginfo('url').'/wc-spod-webhook/';
    }

    /**
     * delete single subscription by id
     * @since      1.0.0
     * @param $id_subscription
     */
    protected function deleteSubscription($id_subscription)
    {
        $this->setRequest($this->api_url.$this->resource_subscriptions.'/'.$id_subscription,'GET', [], [], 'DELETE');
    }

    /**
     * get all webhook subscriptions and delete all
     * @since      1.0.0
     */
    public function deleteSubscriptions()
    {
        $subscriptions = $this->setRequest($this->api_url.$this->resource_subscriptions);

        if (isset($subscriptions) && count((array)$subscriptions)>0) {
            foreach($subscriptions as $subscription) {
                $this->deleteSubscription($subscription->id);
            }
        }
    }
}