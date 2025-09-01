<?php
/**
 * Fired during plugin activation.
 *
 * @since      1.0.0
 * @package    OSNA_Tools
 * @subpackage OSNA_Tools/includes
 */

class OSNA_Tools_Activator
{

    /**
     * Activate the plugin.
     *
     * Create necessary database tables and register custom post types.
     *
     * @since    1.0.0
     */
    public static function activate()
    {
        // Register custom post types
        require_once OSNA_TOOLS_PLUGIN_DIR . 'includes/ultimate-sliders/class-ultimate-sliders-post-type.php';
        $post_type = new Ultimate_Sliders_Post_Type();
        $post_type->register_post_type();

        // Create database tables for Product Custom Fields
        require_once OSNA_TOOLS_PLUGIN_DIR . 'includes/product-custom-fields/class-product-custom-fields.php';
        Product_Custom_Fields::create_tables();

        // Flush rewrite rules to ensure our custom post types work
        flush_rewrite_rules();
    }
}
