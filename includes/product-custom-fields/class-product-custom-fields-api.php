<?php
/**
 * The API functionality for Product Custom Fields.
 *
 * @since      1.0.0
 * @package    OSNA_Tools
 * @subpackage OSNA_Tools/includes/product-custom-fields
 */

class Product_Custom_Fields_API
{

    /**
     * The parent Product_Custom_Fields instance.
     *
     * @since    1.0.0
     * @access   private
     * @var      Product_Custom_Fields    $parent    The parent instance.
     */
    private $parent;

    /**
     * Initialize the class.
     *
     * @since    1.0.0
     */
    public function init()
    {
        $this->parent = new Product_Custom_Fields();

        // Register REST API routes
        add_action('rest_api_init', array($this, 'register_rest_routes'));

        // Add custom fields to WooCommerce API
        add_filter('woocommerce_rest_prepare_product_object', array($this, 'add_custom_fields_to_api_response'), 10, 3);
        add_filter('woocommerce_rest_prepare_product_variation_object', array($this, 'add_custom_fields_to_api_response'), 10, 3);

        // Handle custom fields in WooCommerce API create/update
        add_action('woocommerce_rest_insert_product_object', array($this, 'handle_custom_fields_in_api_request'), 10, 3);
        add_action('woocommerce_rest_insert_product_variation_object', array($this, 'handle_custom_fields_in_api_request'), 10, 3);
    }

    /**
     * Register REST API routes.
     *
     * @since    1.0.0
     */
    public function register_rest_routes()
    {
        register_rest_route('osna/v1', '/product-custom-fields', array(
            'methods' => 'GET',
            'callback' => array($this, 'get_fields'),
            'permission_callback' => array($this, 'get_fields_permissions_check'),
        ));

        register_rest_route('osna/v1', '/product-custom-fields/(?P<id>\d+)', array(
            'methods' => 'GET',
            'callback' => array($this, 'get_field'),
            'permission_callback' => array($this, 'get_fields_permissions_check'),
            'args' => array(
                'id' => array(
                    'validate_callback' => function ($param) {
                        return is_numeric($param);
                    }
                ),
            ),
        ));

        register_rest_route('osna/v1', '/products/(?P<product_id>\d+)/custom-fields', array(
            'methods' => 'GET',
            'callback' => array($this, 'get_product_field_values'),
            'permission_callback' => array($this, 'get_fields_permissions_check'),
            'args' => array(
                'product_id' => array(
                    'validate_callback' => function ($param) {
                        return is_numeric($param);
                    }
                ),
            ),
        ));
    }

    /**
     * Check if user has permission to access custom fields.
     *
     * @since    1.0.0
     * @param    WP_REST_Request    $request    The request.
     * @return   bool
     */
    public function get_fields_permissions_check($request)
    {
        // Public read access
        if ($request->get_method() === 'GET') {
            return true;
        }

        // For write operations, check capabilities
        return current_user_can('manage_options');
    }

    /**
     * Get all custom field definitions.
     *
     * @since    1.0.0
     * @param    WP_REST_Request    $request    The request.
     * @return   WP_REST_Response
     */
    public function get_fields($request)
    {
        $fields = $this->parent->get_fields();

        if (empty($fields)) {
            return rest_ensure_response(array());
        }

        return rest_ensure_response($fields);
    }

    /**
     * Get a specific custom field definition.
     *
     * @since    1.0.0
     * @param    WP_REST_Request    $request    The request.
     * @return   WP_REST_Response
     */
    public function get_field($request)
    {
        $field_id = $request->get_param('id');
        $field = $this->parent->get_field($field_id);

        if (!$field) {
            return new WP_Error('not_found', __('Field not found', 'osna-wp-tools'), array('status' => 404));
        }

        return rest_ensure_response($field);
    }

    /**
     * Get custom field values for a product.
     *
     * @since    1.0.0
     * @param    WP_REST_Request    $request    The request.
     * @return   WP_REST_Response
     */
    public function get_product_field_values($request)
    {
        $product_id = $request->get_param('product_id');

        // Check if product exists
        $product = wc_get_product($product_id);
        if (!$product) {
            return new WP_Error('not_found', __('Product not found', 'osna-wp-tools'), array('status' => 404));
        }

        $values = $this->parent->get_product_field_values($product_id);

        return rest_ensure_response($values);
    }

    /**
     * Add custom fields to WooCommerce API response.
     *
     * @since    1.0.0
     * @param    WP_REST_Response    $response    The response object.
     * @param    WC_Product          $product     The product object.
     * @param    WP_REST_Request     $request     The request object.
     * @return   WP_REST_Response
     */
    public function add_custom_fields_to_api_response($response, $product, $request)
    {
        $data = $response->get_data();
        $product_id = $product->get_id();

        // Get custom field values
        $values = $this->parent->get_product_field_values($product_id);

        if (!empty($values)) {
            // Add custom fields to the meta_data array
            if (!isset($data['meta_data'])) {
                $data['meta_data'] = array();
            }

            foreach ($values as $field_name => $field_data) {
                // Only include fields that have values
                if ($field_data['value'] !== '') {
                    $data['meta_data'][] = array(
                        'key' => '_osna_product_field_' . $field_name,
                        'value' => $field_data['value'],
                        'display_key' => $field_data['label'],
                        'display_value' => $this->format_field_value_for_display($field_data)
                    );
                }
            }

            // Add a dedicated osna_custom_fields property
            $data['osna_custom_fields'] = $values;

            $response->set_data($data);
        }

        return $response;
    }

    /**
     * Handle custom fields in WooCommerce API create/update requests.
     *
     * @since    1.0.0
     * @param    WC_Product         $product     The product object.
     * @param    WP_REST_Request    $request     The request object.
     * @param    bool               $creating    Whether this is a creation request.
     */
    public function handle_custom_fields_in_api_request($product, $request, $creating)
    {
        $product_id = $product->get_id();
        $request_data = $request->get_params();

        // Check if osna_custom_fields is in the request
        if (isset($request_data['osna_custom_fields']) && is_array($request_data['osna_custom_fields'])) {
            $custom_fields = $request_data['osna_custom_fields'];

            // Get all field definitions
            $field_definitions = $this->parent->get_fields();
            $field_map = array();
            foreach ($field_definitions as $field) {
                $field_map[$field['field_name']] = $field;
            }

            // Save each field value
            foreach ($custom_fields as $field_name => $value) {
                // Skip if field doesn't exist
                if (!isset($field_map[$field_name])) {
                    continue;
                }

                $meta_key = '_osna_product_field_' . $field_name;

                // Validate and sanitize based on field type
                switch ($field_map[$field_name]['field_type']) {
                    case 'checkbox':
                        $value = ($value === true || $value === 'yes' || $value === '1') ? 'yes' : 'no';
                        break;

                    case 'number':
                        $value = is_numeric($value) ? floatval($value) : '';
                        break;

                    case 'select':
                        // Ensure value is one of the allowed options
                        if (!empty($field_map[$field_name]['field_options'])) {
                            if (!array_key_exists($value, $field_map[$field_name]['field_options'])) {
                                $value = '';
                            }
                        }
                        break;

                    case 'textarea':
                        $value = sanitize_textarea_field($value);
                        break;

                    default:
                        $value = sanitize_text_field($value);
                        break;
                }

                update_post_meta($product_id, $meta_key, $value);
            }

        }
    }

}