<?php
/**
 * The core plugin class.
 *
 * @since      1.0.0
 * @package    OSNA_Tools
 * @subpackage OSNA_Tools/includes
 */

class OSNA_Tools
{

    /**
     * The loader that's responsible for maintaining and registering all hooks that power
     * the plugin.
     *
     * @since    1.0.0
     * @access   protected
     * @var      OSNA_Tools_Loader    $loader    Maintains and registers all hooks for the plugin.
     */
    protected $loader;

    /**
     * The unique identifier of this plugin.
     *
     * @since    1.0.0
     * @access   protected
     * @var      string    $plugin_name    The string used to uniquely identify this plugin.
     */
    protected $plugin_name;

    /**
     * The current version of the plugin.
     *
     * @since    1.0.0
     * @access   protected
     * @var      string    $version    The current version of the plugin.
     */
    protected $version;

    /**
     * Define the core functionality of the plugin.
     *
     * @since    1.0.0
     */
    public function __construct()
    {
        $this->version = OSNA_TOOLS_VERSION;
        $this->plugin_name = 'osna-wp-tools';

        $this->load_dependencies();
        $this->define_admin_hooks();
        $this->define_public_hooks();
    }

    /**
     * Load the required dependencies for this plugin.
     *
     * @since    1.0.0
     * @access   private
     */
    private function load_dependencies()
    {
        /**
         * The class responsible for orchestrating the actions and filters of the
         * core plugin.
         */
        require_once OSNA_TOOLS_PLUGIN_DIR . 'includes/class-osna-tools-loader.php';

        /**
         * The class responsible for defining all actions that occur in the admin area.
         */
        require_once OSNA_TOOLS_PLUGIN_DIR . 'admin/class-osna-tools-admin.php';

        /**
         * The class responsible for defining all actions that occur in the public-facing
         * side of the site.
         */
        require_once OSNA_TOOLS_PLUGIN_DIR . 'public/class-osna-tools-public.php';

        /**
         * Load Ultimate Sliders module
         */
        require_once OSNA_TOOLS_PLUGIN_DIR . 'includes/ultimate-sliders/class-ultimate-sliders.php';

        $this->loader = new OSNA_Tools_Loader();
    }

    /**
     * Register all of the hooks related to the admin area functionality
     * of the plugin.
     *
     * @since    1.0.0
     * @access   private
     */
    private function define_admin_hooks()
    {
        $plugin_admin = new OSNA_Tools_Admin($this->get_plugin_name(), $this->get_version());

        $this->loader->add_action('admin_enqueue_scripts', $plugin_admin, 'enqueue_styles');
        $this->loader->add_action('admin_enqueue_scripts', $plugin_admin, 'enqueue_scripts');
        $this->loader->add_action('admin_menu', $plugin_admin, 'add_plugin_admin_menu');

        // Initialize Ultimate Sliders
        $ultimate_sliders = new Ultimate_Sliders();
        $ultimate_sliders->init();

        // Initialize Payment Gateways
        if (file_exists(OSNA_TOOLS_PLUGIN_DIR . 'includes/payment-gateways/class-payment-gateways.php')) {
            require_once OSNA_TOOLS_PLUGIN_DIR . 'includes/payment-gateways/class-payment-gateways.php';
            $payment_gateways = new Payment_Gateways();
            $payment_gateways->init();
        }
    }

    /**
     * Register all of the hooks related to the public-facing functionality
     * of the plugin.
     *
     * @since    1.0.0
     * @access   private
     */
    private function define_public_hooks()
    {
        $plugin_public = new OSNA_Tools_Public($this->get_plugin_name(), $this->get_version());

        $this->loader->add_action('wp_enqueue_scripts', $plugin_public, 'enqueue_styles');
        $this->loader->add_action('wp_enqueue_scripts', $plugin_public, 'enqueue_scripts');
    }

    /**
     * Run the loader to execute all of the hooks with WordPress.
     *
     * @since    1.0.0
     */
    public function run()
    {
        $this->loader->run();
    }

    /**
     * The name of the plugin used to uniquely identify it within the context of
     * WordPress and to define internationalization functionality.
     *
     * @since     1.0.0
     * @return    string    The name of the plugin.
     */
    public function get_plugin_name()
    {
        return $this->plugin_name;
    }

    /**
     * The reference to the class that orchestrates the hooks with the plugin.
     *
     * @since     1.0.0
     * @return    OSNA_Tools_Loader    Orchestrates the hooks of the plugin.
     */
    public function get_loader()
    {
        return $this->loader;
    }

    /**
     * Retrieve the version number of the plugin.
     *
     * @since     1.0.0
     * @return    string    The version number of the plugin.
     */
    public function get_version()
    {
        return $this->version;
    }


}
