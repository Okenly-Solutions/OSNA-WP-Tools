<?php
/**
 * The Payment Gateways API functionality.
 *
 * @since      1.0.0
 * @package    OSNA_Tools
 * @subpackage OSNA_Tools/includes/payment-gateways
 */

class Payment_Gateways_API
{

    /**
     * Initialize the class.
     *
     * @since    1.0.0
     */
    public function init()
    {
        // Register REST API endpoints
        add_action('rest_api_init', array($this, 'register_rest_routes'));

        // Add CORS headers
        add_action('rest_api_init', function () {
            remove_filter('rest_pre_serve_request', 'rest_send_cors_headers');
            add_filter('rest_pre_serve_request', array($this, 'add_cors_headers'));
        }, 15);
    }

    /**
     * Add CORS headers to REST API responses.
     *
     * @since    1.0.0
     * @param    bool    $served    Whether the request has already been served.
     * @return   bool              Whether the request has already been served.
     */
    public function add_cors_headers($served)
    {
        // Allow requests from any origin
        header('Access-Control-Allow-Origin: *');
        header('Access-Control-Allow-Methods: GET, POST, OPTIONS');
        header('Access-Control-Allow-Headers: X-API-Key, x-api-key, Content-Type, Authorization, X-Requested-With');

        // Handle preflight OPTIONS requests
        if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') {
            header('Access-Control-Max-Age: 86400'); // Cache preflight for 24 hours
            status_header(200);
            exit();
        }

        return $served;
    }

    /**
     * Register REST API routes.
     *
     * @since    1.0.0
     */
    public function register_rest_routes()
    {
        register_rest_route('osna/v1', '/process-payment', array(
            'methods' => 'POST, OPTIONS',  // Add OPTIONS method
            'callback' => array($this, 'process_payment'),
            'permission_callback' => array($this, 'check_permissions'),
        ));

        register_rest_route('osna/v1', '/payment-gateways', array(
            'methods' => 'GET, OPTIONS',  // Add OPTIONS method
            'callback' => array($this, 'get_payment_gateways'),
            'permission_callback' => '__return_true',
        ));
    }

    /**
     * Check permissions for the API endpoint.
     *
     * @since    1.0.0
     * @param    WP_REST_Request    $request    The request.
     * @return   boolean
     */
    public function check_permissions($request)
    {
        // Always allow OPTIONS requests
        if ($request->get_method() === 'OPTIONS') {
            return true;
        }

        // Check for API key
        $headers = $request->get_headers();

        if (isset($headers['x_api_key']) && $headers['x_api_key'][0] === get_option('osna_payment_gateway_api_key')) {
            return true;
        }

        return false;
    }

    /**
     * Process a payment request.
     *
     * @since    1.0.0
     * @param    WP_REST_Request    $request    The request.
     * @return   WP_REST_Response               The response.
     */
    public function process_payment($request)
    {
        $parameters = $request->get_json_params();

        // Validate required parameters
        $required_params = array('amount', 'shop_name', 'message', 'success_url', 'failure_url', 'order_id');
        foreach ($required_params as $param) {
            if (!isset($parameters[$param])) {
                return new WP_REST_Response(array(
                    'success' => false,
                    'error' => sprintf(__('Missing required parameter: %s', 'osna-wp-tools'), $param)
                ), 400);
            }
        }

        // Get the selected gateway from options
        $gateway = get_option('osna_payment_gateway_provider', 'lygos');

        // Process based on selected gateway
        switch ($gateway) {
            case 'lygos':
                return $this->process_lygos_payment($parameters);
            case 'stripe':
                return $this->process_stripe_payment($parameters);
            default:
                return new WP_REST_Response(array(
                    'success' => false,
                    'error' => __('Invalid payment gateway provider', 'osna-wp-tools')
                ), 400);
        }
    }

    /**
     * Process a payment through Lygos.
     *
     * @since    1.0.0
     * @param    array    $parameters    The payment parameters.
     * @return   WP_REST_Response        The response.
     */
    private function process_lygos_payment($parameters)
    {
        $api_key = get_option('osna_lygos_api_key');

        // Prepare data for Lygos API
        $body = json_encode(array(
            'amount' => $parameters['amount'],
            'shop_name' => $parameters['shop_name'],
            'message' => $parameters['message'],
            'success_url' => $parameters['success_url'],
            'failure_url' => $parameters['failure_url'],
            'order_id' => $parameters['order_id']
        ));

        // Make the API request to Lygos
        $response = wp_remote_post('https://api.lygosapp.com/v1/gateway', array(
            'method' => 'POST',
            'headers' => array(
                'Content-Type' => 'application/json',
                'api-key' => $api_key
            ),
            'body' => $body,
        ));

        if (is_wp_error($response)) {
            return new WP_REST_Response(array(
                'success' => false,
                'error' => $response->get_error_message()
            ), 500);
        }

        $data = json_decode(wp_remote_retrieve_body($response), true);
        return new WP_REST_Response($data, 200);
    }

    /**
     * Process a payment through Stripe.
     *
     * @since    1.0.0
     * @param    array    $parameters    The payment parameters.
     * @return   WP_REST_Response        The response.
     */
    private function process_stripe_payment($parameters)
    {
        // Implementation for Stripe payment processing
        // Similar to Lygos but with Stripe-specific API calls

        // For now, return a placeholder response
        return new WP_REST_Response(array(
            'success' => false,
            'error' => __('Stripe integration is coming soon', 'osna-wp-tools')
        ), 501);
    }

    /**
     * Get available payment gateways.
     *
     * @since    1.0.0
     * @param    WP_REST_Request    $request    The request.
     * @return   WP_REST_Response               The response.
     */
    public function get_payment_gateways($request)
    {
        $gateways = array(
            'lygos' => array(
                'name' => 'Lygos',
                'description' => __('Process payments through Lygos payment gateway', 'osna-wp-tools'),
                'enabled' => get_option('osna_lygos_enabled', false),
            ),
            'stripe' => array(
                'name' => 'Stripe',
                'description' => __('Process payments through Stripe payment gateway', 'osna-wp-tools'),
                'enabled' => get_option('osna_stripe_enabled', false),
            ),
        );

        return rest_ensure_response($gateways);
    }
}