<?php
/**
 * The API functionality for the Referral System.
 *
 * @since      1.0.0
 * @package    OSNA_Tools
 * @subpackage OSNA_Tools/includes/referral-system
 */

class Referral_System_API
{
    /**
     * Initialize the API endpoints.
     *
     * @since    1.0.0
     */
    public function init()
    {
        add_action('rest_api_init', array($this, 'register_routes'));
    }

    /**
     * Register API routes.
     *
     * @since    1.0.0
     */
    public function register_routes()
    {
        $namespace = 'osna/v1';

        // Validate referral code
        register_rest_route($namespace, '/referral/validate', array(
            'methods' => 'POST',
            'callback' => array($this, 'validate_referral_code'),
            'permission_callback' => '__return_true',
            'args' => array(
                'code' => array(
                    'required' => true,
                    'type' => 'string',
                    'sanitize_callback' => 'sanitize_text_field'
                )
            )
        ));

        // Apply referral code to order
        register_rest_route($namespace, '/referral/apply', array(
            'methods' => 'POST',
            'callback' => array($this, 'apply_referral_code'),
            'permission_callback' => array($this, 'check_user_permission'),
            'args' => array(
                'code' => array(
                    'required' => true,
                    'type' => 'string',
                    'sanitize_callback' => 'sanitize_text_field'
                ),
                'order_id' => array(
                    'required' => true,
                    'type' => 'integer',
                    'sanitize_callback' => 'absint'
                )
            )
        ));

        // Get user referral statistics
        register_rest_route($namespace, '/referral/stats/(?P<user_id>\d+)', array(
            'methods' => 'GET',
            'callback' => array($this, 'get_user_stats'),
            'permission_callback' => array($this, 'check_user_or_admin_permission'),
            'args' => array(
                'user_id' => array(
                    'required' => true,
                    'type' => 'integer',
                    'sanitize_callback' => 'absint'
                )
            )
        ));

        // Create referral code (admin only)
        register_rest_route($namespace, '/referral/create', array(
            'methods' => 'POST',
            'callback' => array($this, 'create_referral_code'),
            'permission_callback' => array($this, 'check_admin_permission'),
            'args' => array(
                'code' => array(
                    'required' => true,
                    'type' => 'string',
                    'sanitize_callback' => 'sanitize_text_field'
                ),
                'user_id' => array(
                    'required' => true,
                    'type' => 'integer',
                    'sanitize_callback' => 'absint'
                ),
                'discount_type' => array(
                    'required' => false,
                    'type' => 'string',
                    'default' => 'percentage',
                    'sanitize_callback' => 'sanitize_text_field'
                ),
                'discount_value' => array(
                    'required' => false,
                    'type' => 'number',
                    'default' => 0,
                    'sanitize_callback' => 'floatval'
                ),
                'reward_type' => array(
                    'required' => false,
                    'type' => 'string',
                    'default' => 'fixed',
                    'sanitize_callback' => 'sanitize_text_field'
                ),
                'reward_value' => array(
                    'required' => false,
                    'type' => 'number',
                    'default' => 0,
                    'sanitize_callback' => 'floatval'
                ),
                'usage_limit' => array(
                    'required' => false,
                    'type' => 'integer',
                    'sanitize_callback' => 'absint'
                )
            )
        ));

        // Get all referral codes (admin only)
        register_rest_route($namespace, '/referral/codes', array(
            'methods' => 'GET',
            'callback' => array($this, 'get_referral_codes'),
            'permission_callback' => array($this, 'check_admin_permission'),
            'args' => array(
                'page' => array(
                    'required' => false,
                    'type' => 'integer',
                    'default' => 1,
                    'sanitize_callback' => 'absint'
                ),
                'per_page' => array(
                    'required' => false,
                    'type' => 'integer',
                    'default' => 20,
                    'sanitize_callback' => 'absint'
                ),
                'search' => array(
                    'required' => false,
                    'type' => 'string',
                    'sanitize_callback' => 'sanitize_text_field'
                )
            )
        ));

        // Update referral code (admin only)
        register_rest_route($namespace, '/referral/codes/(?P<id>\d+)', array(
            'methods' => 'PUT',
            'callback' => array($this, 'update_referral_code'),
            'permission_callback' => array($this, 'check_admin_permission'),
            'args' => array(
                'id' => array(
                    'required' => true,
                    'type' => 'integer',
                    'sanitize_callback' => 'absint'
                )
            )
        ));

        // Delete referral code (admin only)
        register_rest_route($namespace, '/referral/codes/(?P<id>\d+)', array(
            'methods' => 'DELETE',
            'callback' => array($this, 'delete_referral_code'),
            'permission_callback' => array($this, 'check_admin_permission'),
            'args' => array(
                'id' => array(
                    'required' => true,
                    'type' => 'integer',
                    'sanitize_callback' => 'absint'
                )
            )
        ));

        // Get referral usage statistics (admin only)
        register_rest_route($namespace, '/referral/usage', array(
            'methods' => 'GET',
            'callback' => array($this, 'get_referral_usage'),
            'permission_callback' => array($this, 'check_admin_permission'),
            'args' => array(
                'page' => array(
                    'required' => false,
                    'type' => 'integer',
                    'default' => 1,
                    'sanitize_callback' => 'absint'
                ),
                'per_page' => array(
                    'required' => false,
                    'type' => 'integer',
                    'default' => 20,
                    'sanitize_callback' => 'absint'
                )
            )
        ));
    }

    /**
     * Validate referral code endpoint.
     *
     * @since    1.0.0
     * @param    WP_REST_Request    $request    Request object
     * @return   WP_REST_Response             Response object
     */
    public function validate_referral_code($request)
    {
        $code = $request->get_param('code');
        
        if (empty($code)) {
            return new WP_REST_Response(array(
                'success' => false,
                'message' => 'Referral code is required'
            ), 400);
        }

        $referral_code = Referral_System::get_referral_code($code);
        
        if (!$referral_code) {
            return new WP_REST_Response(array(
                'success' => false,
                'message' => 'Invalid or inactive referral code'
            ), 404);
        }

        // Check usage limit
        if ($referral_code->usage_limit && $referral_code->usage_count >= $referral_code->usage_limit) {
            return new WP_REST_Response(array(
                'success' => false,
                'message' => 'Referral code usage limit exceeded'
            ), 400);
        }

        return new WP_REST_Response(array(
            'success' => true,
            'data' => array(
                'code' => $referral_code->code,
                'discount_type' => $referral_code->discount_type,
                'discount_value' => floatval($referral_code->discount_value),
                'usage_count' => intval($referral_code->usage_count),
                'usage_limit' => $referral_code->usage_limit ? intval($referral_code->usage_limit) : null
            )
        ), 200);
    }

    /**
     * Apply referral code endpoint.
     *
     * @since    1.0.0
     * @param    WP_REST_Request    $request    Request object
     * @return   WP_REST_Response             Response object
     */
    public function apply_referral_code($request)
    {
        $code = $request->get_param('code');
        $order_id = $request->get_param('order_id');
        $user_id = get_current_user_id();

        $result = Referral_System::apply_referral_code($code, $order_id, $user_id);

        if (is_wp_error($result)) {
            return new WP_REST_Response(array(
                'success' => false,
                'message' => $result->get_error_message()
            ), 400);
        }

        return new WP_REST_Response(array(
            'success' => true,
            'message' => 'Referral code applied successfully'
        ), 200);
    }

    /**
     * Get user referral statistics endpoint.
     *
     * @since    1.0.0
     * @param    WP_REST_Request    $request    Request object
     * @return   WP_REST_Response             Response object
     */
    public function get_user_stats($request)
    {
        $user_id = $request->get_param('user_id');
        $stats = Referral_System::get_user_referral_stats($user_id);

        return new WP_REST_Response(array(
            'success' => true,
            'data' => $stats
        ), 200);
    }

    /**
     * Create referral code endpoint.
     *
     * @since    1.0.0
     * @param    WP_REST_Request    $request    Request object
     * @return   WP_REST_Response             Response object
     */
    public function create_referral_code($request)
    {
        $data = array(
            'code' => $request->get_param('code'),
            'user_id' => $request->get_param('user_id'),
            'discount_type' => $request->get_param('discount_type'),
            'discount_value' => $request->get_param('discount_value'),
            'reward_type' => $request->get_param('reward_type'),
            'reward_value' => $request->get_param('reward_value'),
            'usage_limit' => $request->get_param('usage_limit')
        );

        $result = Referral_System::create_referral_code($data);

        if (is_wp_error($result)) {
            return new WP_REST_Response(array(
                'success' => false,
                'message' => $result->get_error_message()
            ), 400);
        }

        return new WP_REST_Response(array(
            'success' => true,
            'data' => array('id' => $result),
            'message' => 'Referral code created successfully'
        ), 201);
    }

    /**
     * Get referral codes endpoint.
     *
     * @since    1.0.0
     * @param    WP_REST_Request    $request    Request object
     * @return   WP_REST_Response             Response object
     */
    public function get_referral_codes($request)
    {
        global $wpdb;

        $page = $request->get_param('page');
        $per_page = $request->get_param('per_page');
        $search = $request->get_param('search');

        $offset = ($page - 1) * $per_page;

        $where = "WHERE 1=1";
        $params = array();

        if (!empty($search)) {
            $where .= " AND (rc.code LIKE %s OR u.display_name LIKE %s OR u.user_email LIKE %s)";
            $search_term = '%' . $wpdb->esc_like($search) . '%';
            $params[] = $search_term;
            $params[] = $search_term;
            $params[] = $search_term;
        }

        $query = "SELECT rc.*, u.display_name as user_name, u.user_email 
                  FROM {$wpdb->prefix}osna_referral_codes rc 
                  LEFT JOIN {$wpdb->prefix}users u ON rc.user_id = u.ID 
                  $where 
                  ORDER BY rc.created_at DESC 
                  LIMIT %d OFFSET %d";

        $params[] = $per_page;
        $params[] = $offset;

        $codes = $wpdb->get_results($wpdb->prepare($query, $params));

        // Get total count
        $count_query = "SELECT COUNT(*) 
                        FROM {$wpdb->prefix}osna_referral_codes rc 
                        LEFT JOIN {$wpdb->prefix}users u ON rc.user_id = u.ID 
                        $where";

        $count_params = array_slice($params, 0, -2); // Remove limit and offset params
        $total = $wpdb->get_var(!empty($count_params) ? $wpdb->prepare($count_query, $count_params) : $count_query);

        return new WP_REST_Response(array(
            'success' => true,
            'data' => $codes,
            'pagination' => array(
                'total' => intval($total),
                'page' => $page,
                'per_page' => $per_page,
                'total_pages' => ceil($total / $per_page)
            )
        ), 200);
    }

    /**
     * Update referral code endpoint.
     *
     * @since    1.0.0
     * @param    WP_REST_Request    $request    Request object
     * @return   WP_REST_Response             Response object
     */
    public function update_referral_code($request)
    {
        global $wpdb;

        $id = $request->get_param('id');
        $data = array();

        // Get allowed update fields
        $allowed_fields = array('discount_type', 'discount_value', 'reward_type', 'reward_value', 'usage_limit', 'status');
        
        foreach ($allowed_fields as $field) {
            if ($request->has_param($field)) {
                $data[$field] = $request->get_param($field);
            }
        }

        if (empty($data)) {
            return new WP_REST_Response(array(
                'success' => false,
                'message' => 'No valid fields to update'
            ), 400);
        }

        $result = $wpdb->update(
            $wpdb->prefix . 'osna_referral_codes',
            $data,
            array('id' => $id)
        );

        if ($result === false) {
            return new WP_REST_Response(array(
                'success' => false,
                'message' => 'Failed to update referral code'
            ), 500);
        }

        return new WP_REST_Response(array(
            'success' => true,
            'message' => 'Referral code updated successfully'
        ), 200);
    }

    /**
     * Delete referral code endpoint.
     *
     * @since    1.0.0
     * @param    WP_REST_Request    $request    Request object
     * @return   WP_REST_Response             Response object
     */
    public function delete_referral_code($request)
    {
        global $wpdb;

        $id = $request->get_param('id');

        $result = $wpdb->delete(
            $wpdb->prefix . 'osna_referral_codes',
            array('id' => $id),
            array('%d')
        );

        if ($result === false) {
            return new WP_REST_Response(array(
                'success' => false,
                'message' => 'Failed to delete referral code'
            ), 500);
        }

        return new WP_REST_Response(array(
            'success' => true,
            'message' => 'Referral code deleted successfully'
        ), 200);
    }

    /**
     * Get referral usage statistics endpoint.
     *
     * @since    1.0.0
     * @param    WP_REST_Request    $request    Request object
     * @return   WP_REST_Response             Response object
     */
    public function get_referral_usage($request)
    {
        global $wpdb;

        $page = $request->get_param('page');
        $per_page = $request->get_param('per_page');
        $offset = ($page - 1) * $per_page;

        $usage = $wpdb->get_results($wpdb->prepare(
            "SELECT ru.*, rc.code, 
                    u1.display_name as referrer_name, u1.user_email as referrer_email,
                    u2.display_name as referee_name, u2.user_email as referee_email
             FROM {$wpdb->prefix}osna_referral_usage ru
             LEFT JOIN {$wpdb->prefix}osna_referral_codes rc ON ru.referral_code_id = rc.id
             LEFT JOIN {$wpdb->prefix}users u1 ON ru.referrer_id = u1.ID
             LEFT JOIN {$wpdb->prefix}users u2 ON ru.referee_id = u2.ID
             ORDER BY ru.created_at DESC
             LIMIT %d OFFSET %d",
            $per_page,
            $offset
        ));

        $total = $wpdb->get_var("SELECT COUNT(*) FROM {$wpdb->prefix}osna_referral_usage");

        return new WP_REST_Response(array(
            'success' => true,
            'data' => $usage,
            'pagination' => array(
                'total' => intval($total),
                'page' => $page,
                'per_page' => $per_page,
                'total_pages' => ceil($total / $per_page)
            )
        ), 200);
    }

    /**
     * Check if user has permission (logged in).
     *
     * @since    1.0.0
     * @return   bool    True if user is logged in
     */
    public function check_user_permission()
    {
        return is_user_logged_in();
    }

    /**
     * Check if user is admin or the requested user.
     *
     * @since    1.0.0
     * @param    WP_REST_Request    $request    Request object
     * @return   bool                         True if user has permission
     */
    public function check_user_or_admin_permission($request)
    {
        $current_user_id = get_current_user_id();
        $requested_user_id = $request->get_param('user_id');

        return current_user_can('manage_options') || $current_user_id == $requested_user_id;
    }

    /**
     * Check if user is admin.
     *
     * @since    1.0.0
     * @return   bool    True if user is admin
     */
    public function check_admin_permission()
    {
        return current_user_can('manage_options');
    }
}