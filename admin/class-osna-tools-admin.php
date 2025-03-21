<?php
/**
 * The admin-specific functionality of the plugin.
 *
 * @since      1.0.0
 * @package    OSNA_Tools
 * @subpackage OSNA_Tools/admin
 */

class OSNA_Tools_Admin {

    /**
     * The ID of this plugin.
     *
     * @since    1.0.0
     * @access   private
     * @var      string    $plugin_name    The ID of this plugin.
     */
    private $plugin_name;

    /**
     * The version of this plugin.
     *
     * @since    1.0.0
     * @access   private
     * @var      string    $version    The current version of this plugin.
     */
    private $version;

    /**
     * Initialize the class and set its properties.
     *
     * @since    1.0.0
     * @param    string    $plugin_name       The name of this plugin.
     * @param    string    $version           The version of this plugin.
     */
    public function __construct($plugin_name, $version) {
        $this->plugin_name = $plugin_name;
        $this->version = $version;
    }

    /**
     * Register the stylesheets for the admin area.
     *
     * @since    1.0.0
     */
    public function enqueue_styles() {
        // Enqueue Tailwind CSS
        wp_enqueue_style('tailwindcss', OSNA_TOOLS_PLUGIN_URL . 'admin/css/tailwind.min.css', array(), $this->version, 'all');
        
        // Enqueue plugin admin styles
        wp_enqueue_style($this->plugin_name, OSNA_TOOLS_PLUGIN_URL . 'admin/css/osna-tools-admin.css', array('tailwindcss'), $this->version, 'all');
    }

    /**
     * Register the JavaScript for the admin area.
     *
     * @since    1.0.0
     */
    public function enqueue_scripts() {
        // Enqueue media uploader
        wp_enqueue_media();
        
        // Enqueue plugin admin scripts
        wp_enqueue_script($this->plugin_name, OSNA_TOOLS_PLUGIN_URL . 'admin/js/osna-tools-admin.js', array('jquery'), $this->version, false);
        
        // Localize script
        wp_localize_script($this->plugin_name, 'osna_tools_admin', array(
            'ajax_url' => admin_url('admin-ajax.php'),
            'nonce' => wp_create_nonce('osna_tools_admin_nonce'),
            'i18n' => array(
                'select_image' => __('Select Image', 'osna-wp-tools'),
                'use_image' => __('Use This Image', 'osna-wp-tools'),
                'slide_template' => __('Slide Template', 'osna-wp-tools'),
            ),
        ));
    }

    /**
     * Add plugin admin menu.
     *
     * @since    1.0.0
     */
    public function add_plugin_admin_menu() {
        // Add top level menu
        add_menu_page(
            __('OSNA WP Tools', 'osna-wp-tools'),
            __('OSNA Tools', 'osna-wp-tools'),
            'manage_options',
            'osna-wp-tools',
            array($this, 'display_plugin_admin_dashboard'),
            'dashicons-admin-generic',
            30
        );
        
        // Add submenu for Ultimate Sliders
        add_submenu_page(
            'osna-wp-tools',
            __('Ultimate Sliders', 'osna-wp-tools'),
            __('Ultimate Sliders', 'osna-wp-tools'),
            'manage_options',
            'edit.php?post_type=ultimate_slider'
        );
    }

    /**
     * Display the plugin admin dashboard.
     *
     * @since    1.0.0
     */
    public function display_plugin_admin_dashboard() {
        include_once OSNA_TOOLS_PLUGIN_DIR . 'admin/partials/osna-tools-admin-display.php';
    }
}
