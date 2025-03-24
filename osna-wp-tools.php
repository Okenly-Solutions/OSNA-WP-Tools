<?php
/**
 * Plugin Name: OSNA WP Tools
 * Plugin URI: https://osna.com/wp-tools
 * Description: A collection of tools for WordPress including Ultimate Sliders
 * Version: 1.0.0
 * Author: OSNA Team
 * Author URI: https://osna.com
 * Text Domain: osna-wp-tools
 * Domain Path: /languages
 */

// If this file is called directly, abort.
if (!defined('WPINC')) {
    die;
}

// Define plugin constants
define('OSNA_TOOLS_VERSION', '1.0.0');
define('OSNA_TOOLS_PLUGIN_DIR', plugin_dir_path(__FILE__));
define('OSNA_TOOLS_PLUGIN_URL', plugin_dir_url(__FILE__));
define('OSNA_TOOLS_PLUGIN_BASENAME', plugin_basename(__FILE__));

/**
 * The code that runs during plugin activation.
 */
function activate_osna_tools() {
    require_once OSNA_TOOLS_PLUGIN_DIR . 'includes/class-osna-tools-activator.php';
    OSNA_Tools_Activator::activate();
}

/**
 * The code that runs during plugin deactivation.
 */
function deactivate_osna_tools() {
    require_once OSNA_TOOLS_PLUGIN_DIR . 'includes/class-osna-tools-deactivator.php';
    OSNA_Tools_Deactivator::deactivate();
}

register_activation_hook(__FILE__, 'activate_osna_tools');
register_deactivation_hook(__FILE__, 'deactivate_osna_tools');

/**
 * The core plugin class that is used to define internationalization,
 * admin-specific hooks, and public-facing site hooks.
 */
require_once OSNA_TOOLS_PLUGIN_DIR . 'includes/class-osna-tools.php';

/**
 * Begins execution of the plugin.
 */
function run_osna_tools() {
    $plugin = new OSNA_Tools();
    $plugin->run();
}
run_osna_tools();
