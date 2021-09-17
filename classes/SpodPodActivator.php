<?php
/**
 * Fired during plugin activation.
 *
 * @since      1.0.0
 * @package    wc-spod
 * @subpackage wc-spod/classes
 */
class SpodPodActivator {

	public static function activate()
	{
        flush_rewrite_rules();
	}
	
}