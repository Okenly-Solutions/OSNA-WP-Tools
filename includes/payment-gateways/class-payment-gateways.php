<?php
/**
 * The Payment Gateways functionality.
 *
 * @since      1.0.0
 * @package    OSNA_Tools
 * @subpackage OSNA_Tools/includes/payment-gateways
 */

class Payment_Gateways
{

    /**
     * Initialize the class.
     *
     * @since    1.0.0
     */
    public function init()
    {
        // Load dependencies
        require_once OSNA_TOOLS_PLUGIN_DIR . 'includes/payment-gateways/class-payment-gateways-api.php';

        // Check if WooCommerce is active
        if ($this->is_woocommerce_active()) {
            require_once OSNA_TOOLS_PLUGIN_DIR . 'includes/payment-gateways/class-payment-gateways-woocommerce.php';

            // Initialize WooCommerce integration
            $woocommerce_integration = new Payment_Gateways_WooCommerce();
            $woocommerce_integration->init();
        }

        // Initialize API endpoints
        $api = new Payment_Gateways_API();
        $api->init();
    }

    /**
     * Check if WooCommerce is active.
     *
     * @since    1.0.0
     * @return   boolean
     */
    private function is_woocommerce_active()
    {
        return in_array('woocommerce/woocommerce.php', apply_filters('active_plugins', get_option('active_plugins')));
    }
}