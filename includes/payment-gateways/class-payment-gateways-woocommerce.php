<?php
/**
 * WooCommerce integration for Payment Gateways.
 *
 * @since      1.0.0
 * @package    OSNA_Tools
 * @subpackage OSNA_Tools/includes/payment-gateways
 */

// Make sure WooCommerce is active
if (!class_exists('WC_Payment_Gateway')) {
    return;
}

/**
 * OSNA WooCommerce Payment Gateway class.
 * 
 * @since      1.0.0
 */
class OSNA_WooCommerce_Payment_Gateway extends WC_Payment_Gateway
{
    /**
     * Constructor for the gateway.
     */
    public function __construct()
    {
        $this->id = 'osna_payment_gateway';
        $this->icon = ''; // URL to icon
        $this->has_fields = false;
        $this->method_title = __('OSNA Payment Gateway', 'osna-wp-tools');
        $this->method_description = __('Process payments through various payment providers including Lygos and Stripe.', 'osna-wp-tools');

        // Load the settings
        $this->init_form_fields();
        $this->init_settings();

        // Define user set variables
        $this->title = $this->get_option('title');
        $this->description = $this->get_option('description');
        $this->enabled = $this->get_option('enabled');
        $this->provider = $this->get_option('provider');

        // Actions
        add_action('woocommerce_update_options_payment_gateways_' . $this->id, array($this, 'process_admin_options'));
        add_action('woocommerce_api_osna_payment_gateway', array($this, 'handle_webhook'));
    }

    /**
     * Initialize Gateway Settings Form Fields.
     */
    public function init_form_fields()
    {
        $this->form_fields = array(
            'enabled' => array(
                'title' => __('Enable/Disable', 'osna-wp-tools'),
                'type' => 'checkbox',
                'label' => __('Enable OSNA Payment Gateway', 'osna-wp-tools'),
                'default' => 'no'
            ),
            'title' => array(
                'title' => __('Title', 'osna-wp-tools'),
                'type' => 'text',
                'description' => __('This controls the title which the user sees during checkout.', 'osna-wp-tools'),
                'default' => __('OSNA Payment', 'osna-wp-tools'),
                'desc_tip' => true,
            ),
            'description' => array(
                'title' => __('Description', 'osna-wp-tools'),
                'type' => 'textarea',
                'description' => __('This controls the description which the user sees during checkout.', 'osna-wp-tools'),
                'default' => __('Pay securely using our payment gateway.', 'osna-wp-tools')
            ),
            'provider' => array(
                'title' => __('Payment Provider', 'osna-wp-tools'),
                'type' => 'select',
                'description' => __('Select which payment provider to use.', 'osna-wp-tools'),
                'options' => array(
                    'lygos' => __('Lygos', 'osna-wp-tools'),
                    'stripe' => __('Stripe', 'osna-wp-tools'),
                ),
                'default' => 'lygos'
            ),
            'lygos_api_key' => array(
                'title' => __('Lygos API Key', 'osna-wp-tools'),
                'type' => 'text',
                'description' => __('Enter your Lygos API key.', 'osna-wp-tools'),
                'default' => '',
            ),
            'stripe_api_key' => array(
                'title' => __('Stripe API Key', 'osna-wp-tools'),
                'type' => 'text',
                'description' => __('Enter your Stripe API key.', 'osna-wp-tools'),
                'default' => '',
            )
        );
    }

    /**
     * Process Payment.
     *
     * @param int $order_id
     * @return array
     */
    public function process_payment($order_id)
    {
        $order = wc_get_order($order_id);

        // Save provider settings to options
        update_option('osna_payment_gateway_provider', $this->provider);

        // Save API keys to options
        if ($this->provider === 'lygos') {
            update_option('osna_lygos_api_key', $this->get_option('lygos_api_key'));
            update_option('osna_lygos_enabled', $this->enabled === 'yes');
        } elseif ($this->provider === 'stripe') {
            update_option('osna_stripe_api_key', $this->get_option('stripe_api_key'));
            update_option('osna_stripe_enabled', $this->enabled === 'yes');
        }

        // Mark as on-hold (we're awaiting the payment)
        $order->update_status('on-hold', __('Awaiting payment confirmation.', 'osna-wp-tools'));

        // Prepare parameters for the API call
        $parameters = array(
            'amount' => $order->get_total(),
            'shop_name' => get_bloginfo('name'),
            'message' => sprintf(__('Payment for order #%s', 'osna-wp-tools'), $order->get_order_number()),
            'success_url' => $this->get_return_url($order),
            'failure_url' => $order->get_cancel_order_url(),
            'order_id' => $order_id
        );

        // Make the API request to our internal endpoint
        $api_url = rest_url('osna/v1/process-payment');
        $response = wp_remote_post($api_url, array(
            'method' => 'POST',
            'headers' => array(
                'Content-Type' => 'application/json',
                'X-API-Key' => get_option('osna_payment_gateway_api_key')
            ),
            'body' => json_encode($parameters),
        ));

        if (is_wp_error($response)) {
            wc_add_notice(__('Payment error:', 'osna-wp-tools') . ' ' . $response->get_error_message(), 'error');
            return array(
                'result' => 'failure'
            );
        }

        $response_data = json_decode(wp_remote_retrieve_body($response), true);

        if (isset($response_data['payment_url'])) {
            // Redirect to the payment URL
            return array(
                'result' => 'success',
                'redirect' => $response_data['payment_url']
            );
        } else {
            // Handle error
            wc_add_notice(__('Payment error:', 'osna-wp-tools') . ' ' . ($response_data['error'] ?? __('Unknown error', 'osna-wp-tools')), 'error');
            return array(
                'result' => 'failure'
            );
        }
    }

    /**
     * Handle webhook requests.
     */
    public function handle_webhook()
    {
        $payload = file_get_contents('php://input');
        $data = json_decode($payload, true);

        if (!$data || !isset($data['order_id']) || !isset($data['status'])) {
            wp_die('Invalid webhook data', 'Invalid Data', array('response' => 400));
        }

        $order_id = $data['order_id'];
        $order = wc_get_order($order_id);

        if (!$order) {
            wp_die('Order not found', 'Order Not Found', array('response' => 404));
        }

        // Process the payment status
        if ($data['status'] === 'completed') {
            // Payment is successful
            $order->payment_complete();
            $order->add_order_note(__('Payment completed via OSNA Payment Gateway.', 'osna-wp-tools'));
        } elseif ($data['status'] === 'failed') {
            // Payment failed
            $order->update_status('failed', __('Payment failed.', 'osna-wp-tools'));
        }

        wp_die('Webhook processed', 'Success', array('response' => 200));
    }
}

/**
 * WooCommerce integration class for Payment Gateways.
 */
class Payment_Gateways_WooCommerce
{
    /**
     * Initialize the class.
     *
     * @since    1.0.0
     */
    public function init()
    {
        // Add the payment gateway to WooCommerce
        add_filter('woocommerce_payment_gateways', array($this, 'add_payment_gateway'));
    }

    /**
     * Add the payment gateway to WooCommerce.
     *
     * @since    1.0.0
     * @param    array    $gateways    The array of gateway classes.
     * @return   array                 The updated array of gateway classes.
     */
    public function add_payment_gateway($gateways)
    {
        $gateways[] = 'OSNA_WooCommerce_Payment_Gateway';
        return $gateways;
    }
}