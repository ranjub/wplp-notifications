<?php

/**
 * The plugin bootstrap file
 *
 * This file is read by WordPress to generate the plugin information in the plugin
 * admin area. This file also includes all of the dependencies used by the plugin,
 * registers the activation and deactivation functions, and defines a function
 * that starts the plugin.
 *
 * @link              https://whitelabelwp.io
 * @since             1.0.0
 * @package           Wplp_notification
 *
 * @wordpress-plugin
 * Plugin Name:       WPLP Notification
 * Plugin URI:        https://whitelabelwp.io
 * Description:       Sends notifications on ticket updates
 * Version:           1.0.0
 * Author:            Ranju , Prashna
 * Author URI:        https://whitelabelwp.io/
 * License:           GPL-2.0+
 * License URI:       http://www.gnu.org/licenses/gpl-2.0.txt
 * Text Domain:       wplp_notification
 * Domain Path:       /languages
 */

// If this file is called directly, abort.
if (!defined('WPINC')) {
	die;
}

/**
 * Currently plugin version.
 * Start at version 1.0.0 and use SemVer - https://semver.org
 * Rename this for your plugin and update it as you release new versions.
 */
define('WPLP_NOTIFICATION_VERSION', '1.0.0');

/**
 * The code that runs during plugin activation.
 * This action is documented in includes/class-wplp_notification-activator.php
 */
function activate_wplp_notification()
{
	require_once plugin_dir_path(__FILE__) . 'includes/class-wplp_notification-activator.php';
	Wplp_notification_Activator::activate();
}

/**
 * The code that runs during plugin deactivation.
 * This action is documented in includes/class-wplp_notification-deactivator.php
 */
function deactivate_wplp_notification()
{
	require_once plugin_dir_path(__FILE__) . 'includes/class-wplp_notification-deactivator.php';
	Wplp_notification_Deactivator::deactivate();
}

register_activation_hook(__FILE__, 'activate_wplp_notification');
register_deactivation_hook(__FILE__, 'deactivate_wplp_notification');
register_activation_hook(__FILE__, 'create_custom_table');

// Include the Activator class file
require_once plugin_dir_path(__FILE__) . 'includes/class-wplp_notification-activator.php';

// Include the custom post type class file
require_once plugin_dir_path(__FILE__) . 'includes/class-wplp_notification-cpt.php';

// Hook the post type registration to 'init' action
add_action('init', array('Wplp_notification_Activator', 'register_tickets_post_type'));

// Hook the custom post type class
add_action('plugins_loaded', 'initialize_wplp_notification_cpt');
function initialize_wplp_notification_cpt()
{
	new Wplp_notification_CPT();
}

// Load the main plugin class
require plugin_dir_path(__FILE__) . 'includes/class-wplp_notification.php';


// Run the plugin
function run_wplp_notification()
{
	$plugin = new Wplp_notification();
	$plugin->run();
}
run_wplp_notification();