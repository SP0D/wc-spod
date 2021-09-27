<?php
require_once plugin_dir_path( dirname( __FILE__ ) ) . 'classes/SpodPodApiHandler.php';
require_once plugin_dir_path( dirname( __FILE__ ) ) . 'classes/SpodPodApiArticles.php';
require_once plugin_dir_path( dirname( __FILE__ ) ) . 'classes/SpodPodApiOrders.php';
require_once plugin_dir_path( dirname( __FILE__ ) ) . 'classes/SpodPodApiSubscriptions.php';
require_once plugin_dir_path( dirname( __FILE__ ) ) . 'classes/SpodPodApiArticles.php';
require_once plugin_dir_path( dirname( __FILE__ ) ) . 'classes/SpodPodApiAuthentication.php';

/**
 * The admin-specific functionality of the plugin.
 *
 * @since      1.0.0
 * @package    wc-spod
 * @subpackage wc-spod/classes
 */
class SpodPodAdmin {

	/**
	 * The ID of this plugin.
	 *
	 * @since    1.0.0
	 * @var      string    $plugin_name    The ID of this plugin.
	 */
	private $plugin_name;

	/**
	 * The version of this plugin.
	 *
	 * @since    1.0.0
	 * @var      string    $version    The current version of this plugin.
	 */
	private $version;

	/**
	 * Initialize the class and set its properties.
	 *
	 * @since    1.0.0
	 * @param      string    $plugin_name       The name of this plugin.
	 * @param      string    $version    The version of this plugin.
	 */
	public function __construct( $plugin_name, $version )
    {
		$this->plugin_name = $plugin_name;
		$this->version = $version;
	}

	/**
	 * Register the stylesheets for the admin area.
	 *
	 * @since    1.0.0
	 */
	public function enqueueStyles()
    {
		wp_enqueue_style( $this->plugin_name, plugin_dir_url( __FILE__ ) . '../admin/css/spod_pod-admin.css', array(), $this->version, 'all' );
	}

	/**
	 * Register the JavaScript for the admin area.
	 *
	 * @since    1.0.0
	 */
	public function enqueueScripts()
    {
        wp_enqueue_script('wc-spod-admin', plugin_dir_url(__FILE__) . '../admin/js/spod_pod-admin.js', array('jquery'), $this->version, false);
        wp_localize_script('wc-spod-admin', 'ng_spod_pod_unique', ['ajaxurl' => admin_url('admin-ajax.php'),]);
	}

    /**
     * Admin page.
     *
     * @since    1.0.0
     */
    public function addPages()
    {
        add_menu_page( __('Spod', 'wc-spod'), __('Spod','wc-spod'), 'manage_woocommerce', 'wc-spod', array(&$this, 'adminDisplay'), ' data:image/png;base64,iVBORw0KGgoAAAANSUhEUgAAABQAAAATCAYAAACQjC21AAAEumlUWHRYTUw6Y29tLmFkb2JlLnhtcAAAAAAAPD94cGFja2V0IGJlZ2luPSLvu78iIGlkPSJXNU0wTXBDZWhpSHpyZVN6TlRjemtjOWQiPz4KPHg6eG1wbWV0YSB4bWxuczp4PSJhZG9iZTpuczptZXRhLyIgeDp4bXB0az0iWE1QIENvcmUgNS41LjAiPgogPHJkZjpSREYgeG1sbnM6cmRmPSJodHRwOi8vd3d3LnczLm9yZy8xOTk5LzAyLzIyLXJkZi1zeW50YXgtbnMjIj4KICA8cmRmOkRlc2NyaXB0aW9uIHJkZjphYm91dD0iIgogICAgeG1sbnM6dGlmZj0iaHR0cDovL25zLmFkb2JlLmNvbS90aWZmLzEuMC8iCiAgICB4bWxuczpleGlmPSJodHRwOi8vbnMuYWRvYmUuY29tL2V4aWYvMS4wLyIKICAgIHhtbG5zOnBob3Rvc2hvcD0iaHR0cDovL25zLmFkb2JlLmNvbS9waG90b3Nob3AvMS4wLyIKICAgIHhtbG5zOnhtcD0iaHR0cDovL25zLmFkb2JlLmNvbS94YXAvMS4wLyIKICAgIHhtbG5zOnhtcE1NPSJodHRwOi8vbnMuYWRvYmUuY29tL3hhcC8xLjAvbW0vIgogICAgeG1sbnM6c3RFdnQ9Imh0dHA6Ly9ucy5hZG9iZS5jb20veGFwLzEuMC9zVHlwZS9SZXNvdXJjZUV2ZW50IyIKICAgdGlmZjpJbWFnZUxlbmd0aD0iMTkiCiAgIHRpZmY6SW1hZ2VXaWR0aD0iMjAiCiAgIHRpZmY6UmVzb2x1dGlvblVuaXQ9IjIiCiAgIHRpZmY6WFJlc29sdXRpb249IjcyLjAiCiAgIHRpZmY6WVJlc29sdXRpb249IjcyLjAiCiAgIGV4aWY6UGl4ZWxYRGltZW5zaW9uPSIyMCIKICAgZXhpZjpQaXhlbFlEaW1lbnNpb249IjE5IgogICBleGlmOkNvbG9yU3BhY2U9IjEiCiAgIHBob3Rvc2hvcDpDb2xvck1vZGU9IjMiCiAgIHBob3Rvc2hvcDpJQ0NQcm9maWxlPSJzUkdCIElFQzYxOTY2LTIuMSIKICAgeG1wOk1vZGlmeURhdGU9IjIwMjEtMDMtMTdUMTM6NDA6MjMrMDE6MDAiCiAgIHhtcDpNZXRhZGF0YURhdGU9IjIwMjEtMDMtMTdUMTM6NDA6MjMrMDE6MDAiPgogICA8eG1wTU06SGlzdG9yeT4KICAgIDxyZGY6U2VxPgogICAgIDxyZGY6bGkKICAgICAgc3RFdnQ6YWN0aW9uPSJwcm9kdWNlZCIKICAgICAgc3RFdnQ6c29mdHdhcmVBZ2VudD0iQWZmaW5pdHkgRGVzaWduZXIgKEZlYiAgMiAyMDIxKSIKICAgICAgc3RFdnQ6d2hlbj0iMjAyMS0wMy0xN1QxMzo0MDoyMyswMTowMCIvPgogICAgPC9yZGY6U2VxPgogICA8L3htcE1NOkhpc3Rvcnk+CiAgPC9yZGY6RGVzY3JpcHRpb24+CiA8L3JkZjpSREY+CjwveDp4bXBtZXRhPgo8P3hwYWNrZXQgZW5kPSJyIj8+vnWhKwAAAYJpQ0NQc1JHQiBJRUM2MTk2Ni0yLjEAACiRdZHfK4NRGMc/24iYEBcuXCxtrkZMLVLKlkYtrZny62Z77Yfaj7f33dJyq9yuKHHj1wV/AbfKtVJESu6Ua+IGvZ7X1JbsOT3n+ZzvOc/TOc8BayStZPS6Achk81o44HPMzS84Gp6w0Y4dF6NRRVfHQ6EgNe39FosZr/vMWrXP/WvNy3FdAUuj8JiiannhSeHgal41eUu4U0lFl4VPhN2aXFD4xtRjZX42OVnmT5O1SNgP1jZhR7KKY1WspLSMsLwcZyZdUH7vY77EHs/OzkjsEe9GJ0wAHw6mmMCPl0FGZPbSh4d+WVEjf+Anf5qc5CoyqxTRWCFJijxuUQtSPS4xIXpcRpqi2f+/fdUTQ55ydbsP6h8N49UFDZvwVTKMjwPD+DoE2wOcZyv5uX0YfhO9VNGce9C6DqcXFS22DWcb0HWvRrXoj2QTtyYS8HIMLfPQcQVNi+We/e5zdAeRNfmqS9jZhV4537r0DY7jZ/jyYKgCAAAACXBIWXMAAAsTAAALEwEAmpwYAAACO0lEQVQ4ja1UPWgUURD+Zveyl2SDEKIx1aGNIIiFCIJaWCmCgpWQwka7q1QUFAI2gdipWEULKwuLpLWwFLESVFCCin8pDmyEu9v3ZmazbywumrvcbkyRKd/M98335n3zgB0O2k4Rq5IVhBAUaZraVrVRVcKJ7PYiTc/y0wwBkQWKa2uO5XnGckxERrat0IlcINAzAMn6UQ4gAKgBiAHAzN6HIj41kdZ+b6kwY75HoGUAicG+kNlxg00abNLMpsxwG8AaER2O4uIzM08PKZI83+9EzjvmphctvKhlnptd5mSoGIBj3utYPnhR86KPBhVlWexFeT1pXtQc87VWywgAvEjDi97PPC85ketONe7h/LRjmfcsh4Zm6FjuEuEgQAXMXoSIHqdJsuZYzxHhKYBdfZiPZjg5PpoMzO6/YWaxZ/3er/zfDUQeVOEqfei8P0JR/KaiWT5WT0aJKGzOVfrQLBoq7ouwurpamqgkTMfrbwF8K++Gh41Go7ThACFrPtF1fLXdbtd6OJs1s2JQubWA+A4AeO+nPMuiY1l2rLeG2D3Lq97g8/m/Z45lj1Od9aJzrHqiUxQJAHQ6nQkn+qPvsdrDhCJH140dHMucmZWvpuo+J7rS86y+85pfzpw/A2x6ZeccIYpvENECejv72oI9MbKXMZCFgAOI6CIRXQIwZoZPZnY2Hat/LWu8Qcx8xbH+KvPgxjbpkvcysxlb6cNut5tGtZGbRHQawAx6P03bQlixiBbSer3UozsefwAUyqPWXPUQ2gAAAABJRU5ErkJggg==', 90 );
    }

    /**
     * hook in order status change to processing
     * this happens in shopping process, after payment was sucessfully, or by change status in backend
     *
     * @param $order_id
     * @since 1.0.0
     * @throws Exception
     */
    public function hookOrderStatusProcessing($order_id)
    {
        $ApiOrders = new SpodPodApiOrders();
        $ApiOrders->sendOrders([$order_id]);
    }

    /**
     * hook in order status change to cancelled
     * this can happens in shopping process or manually in the backend
     *
     * @param $order_id
     * @since 1.0.0
     */
    public function hookOrderStatusCancelled($order_id)
    {
        $Order = new WC_Order($order_id);
        $spod_order_id = $Order->get_meta('_spod_order_id');

        if ((int) $spod_order_id>0) {
            $ApiOrder = new SpodPodApiOrders();
            $ApiOrder->cancelOrder($spod_order_id);
        }
    }

    /**
     * register own state for spod orders > shipped
     *
     * @since      1.0.0
     * @return void
     */
    public function registerShippedOrderState()
    {
        register_post_status( 'wc-spod-shipped', [
            'label' => __('Spod Order Shipped', 'wc-spod'),
            'public' => true,
            'exclude_from_search' => true,
            'show_in_admin_all_list' => true,
            'show_in_admin_status_list' => true,
            'label_count' => _n_noop( 'Shipped <span class="count">(%s)</span>', 'Shipped <span class="count">(%s)</span>', 'wc-spod'),
            'post_type' => ['order']
        ]);

        register_post_status( 'wc-spod-processed', [
            'label' => __('Spod Order Processed','wc-spod'),
            'public' => true,
            'exclude_from_search' => true,
            'show_in_admin_all_list' => true,
            'show_in_admin_status_list' => true,
            'label_count' => _n_noop( 'Shipped <span class="count">(%s)</span>', 'Shipped <span class="count">(%s)</span>', 'wc-spod'),
            'post_type' => ['order']
        ]);
    }

    /**
     * add new shipping states to dropdowns
     *
     * @since      1.0.0
     * @param $order_statuses
     * @return array
     */
    public function addShippedOrderState( $order_statuses )
    {
        $order_statuses['wc-spod-shipped'] = __('Spod Order Shipped','wc-spod');
        $order_statuses['wc-spod-processed'] = __('Spod Order Processed','wc-spod');

        return $order_statuses;
    }

    /**
     * notice missing wocoommerce installation
     *
     * @since      1.0.0
     */
    public function showAdminNotices()
    {
        if ( ! function_exists( 'WC' ) ) {
            deactivate_plugins( 'wc-spod/wc-spod.php', false );?>
            <div class="error notice is-dismissible">
                <p><?php _e( 'Woocommerce is not installed. The Spod plugin needs <a href="https://woocommerce.com/" title="wocommerce" target="_blank">woocommerce</a> installed and activated.', 'wc-spod'); ?></p>
                <button type="button" class="notice-dismiss"><span class="screen-reader-text"><?php _e( 'Do not show this notice.', 'wc-spod'); ?></span></button>
            </div>
        <?php
        }
    }

    /**
     * Show options page content.
     *
     * @since    1.0.0
     */
    public function adminDisplay()
    {
        $api_token = get_option('ng_spod_pod_token');
        $api_connected = get_option('ng_spod_pod_isconnected');
        $ApiAuthentication = new SpodPodApiAuthentication();
        $api_state = $api_token!=='' ? $ApiAuthentication->testAuthentication($api_token) :  false;

        include (dirname(__FILE__)).'/../admin/partials/ng_spod_pod-admin-display.php';
    }

    /**
     * add mime typ image/png, because spod deliveres png
     *
     * @since      1.0.0
     */
    public function addMimeType($mimes)
    {
        $mimes['png'] = 'image/png';
        return $mimes;
    }

    /**
     * ajax helper for plugin connect and disconnect to spod api
     *
     * @since      1.0.0
     */
    public function serversideAjax()
    {
        $return = [];

        // disconnect
        if(sanitize_text_field($_POST['method'])=='disconnect') {
            // delete subscriptions
            $Api = new SpodPodApiAuthentication();
            $Api->disconnectPlugin();
            $return = ['notice' => 'success'];
        }

        // connect
        if(sanitize_text_field($_REQUEST['method'])=='connect') {
            $token = sanitize_text_field($_REQUEST['token']);
            $Api = new SpodPodApiAuthentication();
            if($Api->testAuthentication($token)) {
                if ($return = $Api->connectPlugin($token)) {
                    if($_REQUEST['import'] == "true"){
                        $SpodPodApiArticles = new SpodPodApiArticles();
                        $SpodPodApiArticles->insertAllProductsDatabase($return);
                    }
                    $return['notice'] = 'success';
                }
                else {
                    $return['notice'] = 'error';
                }
            }
            else {
                $return['notice'] = 'error';
            }
        }

        // request products
        if(sanitize_text_field($_REQUEST['method'])=='getproducts') {
            $token = sanitize_text_field($_REQUEST['token']);
            $Api = new SpodPodApiAuthentication();
            if($Api->testAuthentication($token)) {
                if ($return = $Api->connectPlugin($token)) {
                    $return['notice'] = 'success';
                }
                else {
                    $return['notice'] = 'error';
                }
            }
            else {
                $return['notice'] = 'error';
            }
        }

        // import product
        if(sanitize_text_field($_REQUEST['method'])=='importArticle') {
            $article_data = $_REQUEST['product'];
            // ApiArticles need php object
            $article_data = json_decode(json_encode($article_data));
            $ApiArticle = new SpodPodApiArticles();
            $ApiArticle->prepareArticle($article_data, 'added');

            $return = [
                'notice' => 'success',
                'connected_date' => date('Y-m-d h:i:s')
            ];
        }

        foreach ($return as $key => $value) {
            $return[$key] = sanitize_text_field($value);
        }

        $return_string = json_encode($return);
        echo $return_string;

        // must be
        die();
    }
}