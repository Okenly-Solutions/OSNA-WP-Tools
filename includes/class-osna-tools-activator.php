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

        // Standard post capabilities will be used - no custom capabilities needed

        // Create database tables for Product Custom Fields
        require_once OSNA_TOOLS_PLUGIN_DIR . 'includes/product-custom-fields/class-product-custom-fields.php';
        Product_Custom_Fields::create_tables();

        // Flush rewrite rules to ensure our custom post types work
        flush_rewrite_rules();
    }
    
    /**
     * Add custom capabilities to WordPress roles.
     *
     * @since    1.0.0
     */
    private static function add_capabilities() {
        $capabilities = array(
            'edit_slider',
            'edit_sliders',
            'edit_others_sliders',
            'publish_sliders',
            'read_slider',
            'read_private_sliders',
            'delete_slider',
            'delete_sliders',
            'delete_others_sliders',
            'delete_private_sliders',
            'delete_published_sliders',
            'edit_private_sliders',
            'edit_published_sliders',
        );

        // Add to administrator
        $admin_role = get_role('administrator');
        if ($admin_role) {
            foreach ($capabilities as $capability) {
                $admin_role->add_cap($capability);
            }
        }

        // Add to editor
        $editor_role = get_role('editor');
        if ($editor_role) {
            foreach ($capabilities as $capability) {
                $editor_role->add_cap($capability);
            }
        }
    }
}
