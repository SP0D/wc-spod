<?php
require_once plugin_dir_path( dirname( __FILE__ ) ) . 'classes/SpodPodApiAuthentication.php';
/**
 * Fired during plugin deactivation.
 *
 * @since      1.0.0
 * @package    wc-spod
 * @subpackage wc-spod/includes
 */
class SpodPodDeactivator {

	/**
	 *
	 * @since    1.0.0
	 */
	public static function deactivate()
    {
        // plugin disconnection and product deleting only with woocommerce possible
        if ( function_exists( 'WC' ) ) {
            $Api = new SpodPodApiAuthentication();
            $Api->disconnectPlugin();
	    }
	}

}