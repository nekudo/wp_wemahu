<?php
/**
 * Wemahu
 *
 * @package   wp_wemahu
 * @author    Simon Samtleben <support@nekudo.com>
 * @license   GPL-2.0+
 * @link      http://nekudo.com/wemahu
 * @copyright 2013 nekudo.com
 *
 * @wordpress-plugin
 * Plugin Name:       Wemahu
 * Plugin URI:        http://nekudo.com/wemahu
 * Description:       A crowd based malware scanner for wordpress.
 * Version:           1.0.2
 * Author:            Simon Samtleben
 * Author URI:        http://nekudo.com
 * License:           GPL-2.0+
 * License URI:       http://www.gnu.org/licenses/gpl-2.0.txt
 */

// If this file is called directly, abort.
if(!defined('WPINC'))
{
	die;
}

require_once(plugin_dir_path( __FILE__ ) . 'public/class.wemahu.php');

// install/uninstall stuff:
register_activation_hook(__FILE__, array('Wemahu', 'activate'));
register_deactivation_hook(__FILE__, array('Wemahu', 'deactivate'));
add_action('plugins_loaded', array('Wemahu', 'get_instance'));

if(is_admin())
{
	require_once(plugin_dir_path(__FILE__) . 'admin/class.wemahu-admin.php');
	add_action('plugins_loaded', array('Wemahu_Admin', 'get_instance'));
}