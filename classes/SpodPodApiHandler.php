<?php
/**
 * Base API Class
 *
 * @link       https://www.spod.com
 * @since      1.0.0
 * @package    wc-spod
 * @subpackage wc-spod/classes
 */

class SpodPodApiHandler
{
    protected $api_url = 'https://rest.spod.com/';

    /**
     * get main curl options
     * @since      1.0.0
     * @return array $options
     */
     protected function getOptions()
     {
         return [
             'accept' => 'application/json',
             'content-Type' => 'application/json',
             'user-agent' => 'WooCommerce/'.SPOD_POD_VERSION,
             'X-SPOD-ACCESS-TOKEN' => get_option('ng_spod_pod_token')
         ];
     }

    /**
     * set request via curl
     * @since      1.0.0
     * @param string $api_url
     * @param string $method
     * @param array $params
     * @param array $data
     * @param string $request
     * @return string $data
     */
    public function setRequest($api_url, $method = 'get', $params = [], $data = null, $request = '')
    {
        $url_params = '';
        $return_data = [];

        if ( count($params)>0 ) {
            $url_params = '?';
            foreach($params as $key => $value) {
                $url_params.= $key.'='.$value.'&';
            }
            $api_url.=$url_params;
        }

        $args_array = [
            'headers' => $this->getOptions(),
            'method' => strtoupper($method),
            'user-agent' => 'WooCommerce/1.0',
            'data_format' => 'body'
        ];
        if ($data!=null) {
            $args_array['body'] = json_encode($data);
        }

        if (strtoupper($request)=='DELETE') {
            $args_array['method'] = $request;
        }

        $return_data = wp_remote_get(
            $api_url,
            $args_array
        );
        $return_data = wp_remote_retrieve_body($return_data);

        if ($return_data!='') {
            return json_decode($return_data);
        }
    }


}