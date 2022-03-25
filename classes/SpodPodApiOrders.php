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

class SpodPodApiOrders extends SpodPodApiHandler
{

    private $resource_orders = 'orders';

    /**
     * ApiOrders constructor.
     * @since      1.0.0
     */
    public function __construct()
    {
    }

    /**
     * main method: read from api and start syncing
     * @since      1.0.0
     * @param array $order_ids
     * @throws Exception
     */
    public function sendOrders($order_ids = [])
    {
        // get just order ids from orders with state processing (state after payment)
        if (count($order_ids)==0) {
            $args = array(
                'limit' => -1,
                'return' => 'ids',
                'status' => ['processing']
            );
            $query = new WC_Order_Query( $args );
            $order_ids = $query->get_orders();
        }

        foreach( $order_ids as $order_id ) {
            // check spod meta field if order is already commited
            $spod_order = get_post_meta($order_id, '_spod_order_commited', true);

            if ($spod_order=='') {
                $order_data = $this->buildOrder($order_id);

                // check if order has order items
                if (isset($order_data['orderItems'])) {
                    $check = $this->submitOrder($order_data);
                }
            }
        }
    }

    /**
     * get all woocommerce data, order details and so on
     * @since      1.0.0
     * @param int $order_id
     * @return array $return_order
     */
    protected function buildOrder($order_id)
    {
        global $wpdb;
        $return_order = [];

        $order = wc_get_order($order_id);
        $order_items = $order->get_items();
        $tax = new WC_Tax();
        $price_amount = $tax_amount = 0;

        $count_order_items = 1;
        $count_spod_products = 1;

        foreach ($order_items as $order_item) {
            $order_item_data = $order_item->get_data();
            $count_order_items++;
            $product_id = ( $order_item_data['variation_id']!=0 ? $order_item_data['variation_id'] : $order_item_data['product_id'] );

            if ((int) $product_id >0) {
                $sql = "SELECT post_id FROM $wpdb->postmeta WHERE post_id ='%d' AND meta_key ='_spod_product' AND meta_value='spod_product'";
                $meta_data_product = $wpdb->get_var($wpdb->prepare( $sql, $product_id));
                if($meta_data_product>0) {
                    // get tax based on product
                    $tax_classes = $tax->get_rates($order_item->get_tax_class());
                    $taxes = array_shift($tax_classes);

                    $return_order['orderItems'][] = [
                        'sku' => get_post_meta($meta_data_product, '_spod_sku', true),
                        'quantity' => $order_item_data['quantity'],
                        'externalOrderItemReference' => $product_id,
                        'customerPrice' => [
                            'amount' => round(($order_item_data['subtotal']+$order_item_data['subtotal_tax']), 2),
                            'taxRate' => $taxes['rate'],
                            'taxAmount' => round($order_item_data['subtotal_tax'], 2),
                            'currency' => $order->get_currency(),
                        ]
                    ];
                    $price_amount+=$order_item_data['subtotal'];
                    $tax_amount+=$order_item_data['subtotal_tax'];

                    $count_spod_products++;
                }
            }

        }

        // go on if order items exists
        if(isset($return_order['orderItems']) && count($return_order['orderItems'])>0) {

            // setup order spod state
            #update_post_meta($order_id, '_spod_order', $count_order_items==$count_spod_products ? true : false);
            #update_post_meta($order_id, '_spod_order', true);

            // multiple taxes - pick up only first
            $taxes = $order->get_taxes();
            if (count($taxes)>0) {
                $tax_rate = array_shift($taxes);
                $shipping_tax_total = $tax_rate->get_shipping_tax_total();
                $rate_percent = $tax_rate->get_rate_percent();
            }
            else {
                $tax_rate = [];
                $shipping_tax_total = 0;
                $rate_percent = 0;
            }
            
            $billing_address = [
                'company' => trim($order->get_billing_company()),
                'firstName' => trim($order->get_billing_first_name()),
                'lastName' => trim($order->get_billing_last_name()),
                'street' => trim($order->get_billing_address_1()),
                'streetAnnex' => trim($order->get_billing_address_2()),
                'city' => trim($order->get_billing_city()),
                'country' => trim($order->get_billing_country()),
                'state' => trim($order->get_billing_state()),
                'zipCode' => trim($order->get_billing_postcode()),
            ];

            if ($order->get_shipping_address_1()!='' && $order->get_shipping_city()!='' && $order->get_shipping_postcode()!='') {
                $return_order['shipping']['address'] = [
                    'company' => trim($order->get_shipping_company()),
                    'firstName' => trim($order->get_shipping_first_name()),
                    'lastName' => trim($order->get_shipping_last_name()),
                    'street' => trim($order->get_shipping_address_1()),
                    'streetAnnex' => trim($order->get_shipping_address_2()),
                    'city' => trim($order->get_shipping_city()),
                    'country' => trim($order->get_shipping_country()),
                    'state' => trim($order->get_shipping_state()),
                    'zipCode' => trim($order->get_shipping_postcode()),
                ];
            }
            else {
                $return_order['shipping']['address'] = $billing_address;
            }
            $return_order['shipping']['fromAddress'] = $billing_address;
            // shipping costs
            $return_order['shipping']['customerPrice'] = [
                'amount' => ($order->get_shipping_total()+$shipping_tax_total),
                'taxRate' => $rate_percent,
                'taxAmount' => $shipping_tax_total,
                'currency' => $order->get_currency()
            ];

            $shipping_preferredType = 'STANDARD';
            $shipping_method = $order->get_shipping_method();
            if (strpos(strtolower($shipping_method), 'express')) {
                $shipping_preferredType = 'EXPRESS';
            }
            $return_order['shipping']['preferredType'] = $shipping_preferredType;

            $return_order['billingAddress'] = $billing_address;
            $return_order['phone'] = $order->get_billing_phone();
            $return_order['email'] =$order->get_billing_email();
            $return_order['externalOrderReference'] = $order->get_order_key().'--'.$order_id;
            $return_order['state'] = 'CONFIRMED';
            $return_order['customerTaxTyp'] = 'NOT_TAXABLE';
            $return_order['order_id'] = $order_id;

            return $return_order;
        }
        else {
            return [];
        }
    }

    /**
     * submit order via api to spod spreadshirt
     * @since      1.0.0
     * @param array $order_data
     */
    protected function submitOrder($order_data)
    {
        $check = $this->setRequest($this->api_url.$this->resource_orders, 'post', [], $order_data);
        // if response data exists
        if (isset($check->id) && $check->id>0 && isset($check->orderReference)) {
            update_post_meta($order_data['order_id'], '_spod_order_commited', date('Y-m-d h:i:s'));
            update_post_meta($order_data['order_id'], '_spod_order_id', $check->id);
        }
    }

    /**
     * submit cancel info to spod
     * @param int $spod_order_id
     */
    public function cancelOrder($spod_order_id)
    {
        $order_data = [
            'orderId' => $spod_order_id,
            'reason' => 'order-canceling'
        ];
        $this->setRequest($this->api_url.$this->resource_orders.'/'.$spod_order_id.'/cancel', 'post', [], $order_data);
    }

    /**
     * @since      1.0.0
     * @param int $spod_order_id
     * @param string $method
     * @param string $infos
     */
    public function webHookOrder($spod_order_id, $method = 'order_cancelled', $infos = '')
    {
        global $wpdb;

        $stmt = "SELECT pm1.post_id, pm2.meta_value AS _spod_order FROM $wpdb->postmeta AS pm1
                            LEFT JOIN $wpdb->postmeta AS pm2 ON pm2.post_id = pm1.post_id 
                            WHERE pm1.meta_key='_spod_order_id' AND pm1.meta_value=%s";
        $order_check = $wpdb->get_row($wpdb->prepare( $stmt, $spod_order_id));

        if (isset($order_check->post_id) && isset($order_check->_spod_order) ) {
            $Order = wc_get_order($order_check->post_id);
            if($method=='order_cancelled') {
                if ($Order->get_status()!=='cancelled') {
                    $Order->set_status('cancelled');
                }
            }
            if($method=='order_processed') {
                $Order->set_status('wc-spod-processed');
                $Order->add_order_note( __('Spod Order Processed','spod_plugin') );
            }
            if($method=='shipment_sent') {
                $Order->set_status('wc-spod-shipped');
                $Order->add_order_note( __('Spod Order Shipped','spod_plugin') );
                $Order->add_order_note( 'Tracking Infos: '.$infos );
            }
            $Order->save();
        }
    }
}