<?php
require_once plugin_dir_path( dirname( __FILE__ ) ) . 'classes/SpodPodApiHandler.php';
require_once plugin_dir_path( dirname( __FILE__ ) ) . 'classes/SpodPodRestApiHandler.php';
require_once plugin_dir_path( dirname( __FILE__ ) ) . 'classes/SpodPodApiArticles.php';
require_once plugin_dir_path( dirname( __FILE__ ) ) . 'classes/SpodPodApiOrders.php';
require_once plugin_dir_path( dirname( __FILE__ ) ) . 'classes/SpodPodApiSubscriptions.php';
require_once plugin_dir_path( dirname( __FILE__ ) ) . 'classes/SpodPodApiArticles.php';
require_once plugin_dir_path( dirname( __FILE__ ) ) . 'classes/SpodPodApiAuthentication.php';
require_once plugin_dir_path( dirname( __FILE__ ) ) . 'classes/SpodPodLogger.php';

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
        add_menu_page( __('Spod', 'wc-spod'), __('Spod','wc-spod'), 'manage_woocommerce', 'wc-spod', array(&$this, 'adminDisplay'), get_site_url(null, '/wp-content/plugins/wc-spod/admin/images/spod-icon-white.png'), 90 );
        add_submenu_page('wc-spod', __('System Report', 'wc-spod'), __('System Report', 'wc-spod'), 'manage_woocommerce', 'wc-spod-requirements', array(&$this, 'adminRequirments'), 1);
        add_submenu_page('wc-spod', __('Support', 'wc-spod'), __('Support', 'wc-spod'), 'manage_woocommerce', 'wc-spod-support', array(&$this, 'adminSupport'), 2);
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
                <p><?php _e( 'WooCommerce is not installed. The Spod plugin needs <a href="https://woocommerce.com/" title="wocommerce" target="_blank">woocommerce</a> installed and activated.', 'wc-spod'); ?></p>
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
        if (trim($api_token)=='') {
            $this->adminIframe();
        }
        else {
            $api_connected = get_option('ng_spod_pod_isconnected');
            $ApiAuthentication = new SpodPodApiAuthentication();
            $api_state = $api_token!=='' ? $ApiAuthentication->testAuthentication($api_token) :  false;

            include (dirname(__FILE__)).'/../admin/partials/ng_spod_pod-admin-display.php';
        }
    }

    /**
     * show submenu page requirements
     *
     * @since      1.1.0
     */
    public function adminRequirments()
    {
        $Logger = new SpodPodLogger();
        $loggerEntries = $Logger->getLatestEvents();

        include (dirname(__FILE__)).'/../admin/partials/ng_spod_pod-admin-requirements.php';
    }

    /**
     * show subemnu page with iframe, calculate necessary frame parameters
     *
     * @since      2.0.0
     */
    public function adminIframe()
    {
        $shopUrl = get_bloginfo('url');
        $hmac = 'install';

        // check woocommerce rest api key
        $SpodRestApi = new SpodPodRestApiHandler();
        $WCRestApi = $SpodRestApi->checkRestApi();

        if ($WCRestApi!==null) {
            $hashmacUrl = 'https://app.spod.com/fulfillment/woo-commerce/module?shopUrl='.$shopUrl.'&apiKey=';
            $hmac = hash_hmac('sha256', $hashmacUrl, $WCRestApi->consumer_secret);
        }

        include (dirname(__FILE__)).'/../admin/partials/ng_spod_pod-admin-iframe.php';
    }


    /**
     * show submenu page support form
     *
     * @since      1.1.0
     */
    public function adminSupport()
    {
        require_once './includes/class-wp-debug-data.php';

        $form_errors = [];
        $form_fields = [];
        $form_error = $form_success = false;
        $form_name = $form_mail = $form_message = $message = '';

        // form validation
        if (isset($_POST['submit']) && is_user_logged_in()) {

            if (!wp_verify_nonce($_POST['support-form-nonce'],'support-form-nonce')) {
                $form_name = sanitize_text_field($_POST['spod-support-name']);
                $form_mail = sanitize_text_field($_POST['spod-support-mail']);
                $form_message = sanitize_text_field($_POST['spod-support-message']);
                $form_report = sanitize_text_field($_POST['spod-support-report']);

                if ($form_name=='') {
                    $form_errors['name'] = __('Please insert your name','wc-spod');
                    $form_error = true;
                }

                if ($form_mail=='' || !filter_var($form_mail, FILTER_VALIDATE_EMAIL)) {
                    $form_errors['mail'] = __('Please insert a valid mail', 'wc-spod');
                    $form_error = true;
                }

                if ($form_error===false) {
                    $headers = [
                        'From: '.$form_name.'<'.$form_mail.'>',
                        'Reply-To: '.$form_mail,
                        'Content-Type: text/html; charset=UTF-8'
                    ];
                    $message = 'Name: '.$form_name.'<br>';
                    $message.= 'Mail: '.$form_mail.'<br>';
                    $message.= 'Message: '.$form_message.'<br>';

                    if ($form_report==1) {
                        $message.='<hr style="height: 1px;">';
                        $message.='Report: '.nl2br($this->buildDebugReport());
                    }

                    $form_success = wp_mail('woocommerce@spod.com', 'wc-spod support request', $message, $headers);
                }
            }
            else {
                $form_errors['token'] = __('Token Error','wc-spod');
            }
        }

        include (dirname(__FILE__)).'/../admin/partials/ng_spod_pod-admin-support.php';
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

    /**
     * build system report for internal plugin page
     *
     * @since 1.1.0
     * @return string
     */
    protected function buildDebugReport()
    {
        $return_string = '';
        require_once './includes/class-wp-debug-data.php';
        // Debug Data Wordpress
        $debug_infos = WP_Debug_Data::debug_data();
        $fields = ['wp-core', 'wp-media', 'wp-active-theme', 'wp-parent-theme', 'wp-plugins-active', 'wp-plugins-inactive', 'wp-server', 'wp-database', 'wp-constants'];
        foreach ($fields as $field) {
            if ( is_array($debug_infos[$field]) ) {
                $return_string.= $debug_infos[$field]['label']."\n";
                if ( isset($debug_infos[$field]['fields']) ) {
                    foreach ($debug_infos[$field]['fields'] as $debug_field) {
                        $return_string.= $debug_field['label'].": ".$debug_field['value']."\n";
                    }
                }
            }
        }

        // Debug Data Spod Logger
        $Logger = new SpodPodLogger();
        $latestEntries = $Logger->getLatestEvents(100);
        if (count($latestEntries)) {
            $return_string.= "\n".'Log Entries: '."\n";
            foreach ($latestEntries as $latestEntry) {
                $return_string.= $latestEntry->title.": ".$latestEntry->description." ".$latestEntry->created_at."\n";
            }
        }

        return $return_string;
    }

    /**
     * add content security rule for iframe
     *
     * @since 2.0.0
     */
    public function updateHeaders()
    {
        header( "Content-Security-Policy: frame-src app.spod-staging.com" );
        header( "Content-Security-Policy: child-src app.spod-staging.com" );
    }

    /**
     * add header for spod plugin iframe integration
     *
     * @since 2.0.0
     */
    public function adminHttpHeaders()
    {
        header( "Content-Security-Policy: frame-src app.spod-staging.com" );
        header( "Content-Security-Policy: child-src app.spod-staging.com" );
    }
}