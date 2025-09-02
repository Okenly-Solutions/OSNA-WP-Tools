<?php
/**
 * The Referral System functionality of the plugin.
 *
 * @since      1.0.0
 * @package    OSNA_Tools
 * @subpackage OSNA_Tools/includes/referral-system
 */

class Referral_System
{
    /**
     * Initialize the referral system.
     *
     * @since    1.0.0
     */
    public function init()
    {
        // Create database tables on activation
        add_action('wp_loaded', array($this, 'create_tables'));
        
        // Initialize API endpoints
        $this->init_api();
        
        // Initialize admin features
        if (is_admin()) {
            $this->init_admin();
        }
        
        // Initialize public features
        $this->init_public();
        
        // WooCommerce hooks
        add_action('plugins_loaded', array($this, 'init_woocommerce_hooks'));
    }

    /**
     * Create referral system database tables.
     *
     * @since    1.0.0
     */
    public function create_tables()
    {
        global $wpdb;

        $charset_collate = $wpdb->get_charset_collate();

        // Check if tables already exist and are properly structured
        $referral_codes_table = $wpdb->prefix . 'osna_referral_codes';
        $table_exists = $wpdb->get_var("SHOW TABLES LIKE '$referral_codes_table'") === $referral_codes_table;

        if (!$table_exists) {
            // Referral codes table
            $sql1 = "CREATE TABLE $referral_codes_table (
                id bigint(20) NOT NULL AUTO_INCREMENT,
                code varchar(20) NOT NULL,
                user_id bigint(20) NOT NULL,
                discount_type varchar(20) NOT NULL DEFAULT 'percentage',
                discount_value decimal(10,2) NOT NULL DEFAULT 0.00,
                reward_type varchar(20) NOT NULL DEFAULT 'fixed',
                reward_value decimal(10,2) NOT NULL DEFAULT 0.00,
                usage_limit int(11) DEFAULT NULL,
                usage_count int(11) NOT NULL DEFAULT 0,
                status varchar(20) NOT NULL DEFAULT 'active',
                created_at datetime NOT NULL DEFAULT CURRENT_TIMESTAMP,
                updated_at datetime NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
                PRIMARY KEY (id),
                UNIQUE KEY code_unique (code),
                KEY user_id (user_id),
                KEY status (status)
            ) $charset_collate;";

            require_once(ABSPATH . 'wp-admin/includes/upgrade.php');
            dbDelta($sql1);
        }

        // Referral usage table
        $referral_usage_table = $wpdb->prefix . 'osna_referral_usage';
        $usage_table_exists = $wpdb->get_var("SHOW TABLES LIKE '$referral_usage_table'") === $referral_usage_table;

        if (!$usage_table_exists) {
            $sql2 = "CREATE TABLE $referral_usage_table (
                id bigint(20) NOT NULL AUTO_INCREMENT,
                referral_code_id bigint(20) NOT NULL,
                referrer_id bigint(20) NOT NULL,
                referee_id bigint(20) NOT NULL,
                order_id bigint(20) NOT NULL,
                discount_amount decimal(10,2) NOT NULL DEFAULT 0.00,
                reward_amount decimal(10,2) NOT NULL DEFAULT 0.00,
                reward_status varchar(20) NOT NULL DEFAULT 'pending',
                created_at datetime NOT NULL DEFAULT CURRENT_TIMESTAMP,
                PRIMARY KEY (id),
                KEY referral_code_id (referral_code_id),
                KEY referrer_id (referrer_id),
                KEY referee_id (referee_id),
                KEY order_id (order_id),
                KEY reward_status (reward_status)
            ) $charset_collate;";

            require_once(ABSPATH . 'wp-admin/includes/upgrade.php');
            dbDelta($sql2);
        }

        // Referral rewards table
        $referral_rewards_table = $wpdb->prefix . 'osna_referral_rewards';
        $rewards_table_exists = $wpdb->get_var("SHOW TABLES LIKE '$referral_rewards_table'") === $referral_rewards_table;

        if (!$rewards_table_exists) {
            $sql3 = "CREATE TABLE $referral_rewards_table (
                id bigint(20) NOT NULL AUTO_INCREMENT,
                user_id bigint(20) NOT NULL,
                referral_usage_id bigint(20) NOT NULL,
                reward_type varchar(20) NOT NULL,
                reward_value decimal(10,2) NOT NULL,
                status varchar(20) NOT NULL DEFAULT 'pending',
                processed_at datetime DEFAULT NULL,
                created_at datetime NOT NULL DEFAULT CURRENT_TIMESTAMP,
                PRIMARY KEY (id),
                KEY user_id (user_id),
                KEY referral_usage_id (referral_usage_id),
                KEY status (status)
            ) $charset_collate;";

            require_once(ABSPATH . 'wp-admin/includes/upgrade.php');
            dbDelta($sql3);
        }
    }

    /**
     * Initialize API endpoints.
     *
     * @since    1.0.0
     */
    private function init_api()
    {
        require_once OSNA_TOOLS_PLUGIN_DIR . 'includes/referral-system/class-referral-system-api.php';
        $api = new Referral_System_API();
        $api->init();
    }

    /**
     * Initialize admin features.
     *
     * @since    1.0.0
     */
    private function init_admin()
    {
        require_once OSNA_TOOLS_PLUGIN_DIR . 'includes/referral-system/class-referral-system-admin.php';
        $admin = new Referral_System_Admin();
        $admin->init();
    }

    /**
     * Initialize public features.
     *
     * @since    1.0.0
     */
    private function init_public()
    {
        require_once OSNA_TOOLS_PLUGIN_DIR . 'includes/referral-system/class-referral-system-public.php';
        $public = new Referral_System_Public();
        $public->init();
    }

    /**
     * Initialize WooCommerce hooks.
     *
     * @since    1.0.0
     */
    public function init_woocommerce_hooks()
    {
        if (class_exists('WooCommerce')) {
            require_once OSNA_TOOLS_PLUGIN_DIR . 'includes/referral-system/class-referral-system-woocommerce.php';
            $woocommerce = new Referral_System_WooCommerce();
            $woocommerce->init();
        }
    }

    /**
     * Create a new referral code.
     *
     * @since    1.0.0
     * @param    array    $data    Referral code data
     * @return   int|WP_Error      Referral code ID on success, WP_Error on failure
     */
    public static function create_referral_code($data)
    {
        global $wpdb;

        // Validate required fields
        if (empty($data['code']) || empty($data['user_id'])) {
            return new WP_Error('missing_data', 'Code and User ID are required');
        }

        // Validate code format (uppercase letters and numbers only)
        $code = strtoupper(sanitize_text_field($data['code']));
        if (!preg_match('/^[A-Z0-9]+$/', $code)) {
            return new WP_Error('invalid_code', 'Code must contain only uppercase letters and numbers');
        }

        // Check if code already exists
        $existing = $wpdb->get_var($wpdb->prepare(
            "SELECT id FROM {$wpdb->prefix}osna_referral_codes WHERE code = %s",
            $code
        ));

        if ($existing) {
            return new WP_Error('code_exists', 'Referral code already exists');
        }

        // Prepare data for insertion
        $insert_data = array(
            'code' => $code,
            'user_id' => absint($data['user_id']),
            'discount_type' => sanitize_text_field($data['discount_type'] ?? 'percentage'),
            'discount_value' => floatval($data['discount_value'] ?? 0),
            'reward_type' => sanitize_text_field($data['reward_type'] ?? 'fixed'),
            'reward_value' => floatval($data['reward_value'] ?? 0),
            'usage_limit' => isset($data['usage_limit']) ? absint($data['usage_limit']) : null,
            'status' => sanitize_text_field($data['status'] ?? 'active')
        );

        $result = $wpdb->insert(
            $wpdb->prefix . 'osna_referral_codes',
            $insert_data,
            array('%s', '%d', '%s', '%f', '%s', '%f', '%d', '%s')
        );

        if ($result === false) {
            return new WP_Error('db_error', 'Failed to create referral code');
        }

        return $wpdb->insert_id;
    }

    /**
     * Get referral code by code string.
     *
     * @since    1.0.0
     * @param    string    $code    Referral code
     * @return   object|null       Referral code data or null
     */
    public static function get_referral_code($code)
    {
        global $wpdb;

        return $wpdb->get_row($wpdb->prepare(
            "SELECT * FROM {$wpdb->prefix}osna_referral_codes WHERE code = %s AND status = 'active'",
            strtoupper(sanitize_text_field($code))
        ));
    }

    /**
     * Apply referral code to order.
     *
     * @since    1.0.0
     * @param    string    $code      Referral code
     * @param    int       $order_id  WooCommerce order ID
     * @param    int       $user_id   User ID who used the code
     * @return   bool|WP_Error       True on success, WP_Error on failure
     */
    public static function apply_referral_code($code, $order_id, $user_id)
    {
        global $wpdb;

        $referral_code = self::get_referral_code($code);
        if (!$referral_code) {
            return new WP_Error('invalid_code', 'Invalid or inactive referral code');
        }

        // Check usage limit
        if ($referral_code->usage_limit && $referral_code->usage_count >= $referral_code->usage_limit) {
            return new WP_Error('usage_limit', 'Referral code usage limit exceeded');
        }

        // Check if user is trying to use their own code
        if ($referral_code->user_id == $user_id) {
            return new WP_Error('self_referral', 'Cannot use your own referral code');
        }

        // Get order
        $order = wc_get_order($order_id);
        if (!$order) {
            return new WP_Error('invalid_order', 'Invalid order');
        }

        // Calculate discount
        $order_total = $order->get_total();
        $discount_amount = 0;

        if ($referral_code->discount_type === 'percentage') {
            $discount_amount = ($order_total * $referral_code->discount_value) / 100;
        } else {
            $discount_amount = min($referral_code->discount_value, $order_total);
        }

        // Record referral usage
        $usage_data = array(
            'referral_code_id' => $referral_code->id,
            'referrer_id' => $referral_code->user_id,
            'referee_id' => $user_id,
            'order_id' => $order_id,
            'discount_amount' => $discount_amount,
            'reward_amount' => $referral_code->reward_value
        );

        $result = $wpdb->insert(
            $wpdb->prefix . 'osna_referral_usage',
            $usage_data,
            array('%d', '%d', '%d', '%d', '%f', '%f')
        );

        if ($result === false) {
            return new WP_Error('db_error', 'Failed to record referral usage');
        }

        // Update usage count
        $wpdb->update(
            $wpdb->prefix . 'osna_referral_codes',
            array('usage_count' => $referral_code->usage_count + 1),
            array('id' => $referral_code->id),
            array('%d'),
            array('%d')
        );

        // Create pending reward for referrer
        $wpdb->insert(
            $wpdb->prefix . 'osna_referral_rewards',
            array(
                'user_id' => $referral_code->user_id,
                'referral_usage_id' => $wpdb->insert_id,
                'reward_type' => $referral_code->reward_type,
                'reward_value' => $referral_code->reward_value,
                'status' => 'pending'
            ),
            array('%d', '%d', '%s', '%f', '%s')
        );

        return true;
    }

    /**
     * Get referral statistics for a user.
     *
     * @since    1.0.0
     * @param    int    $user_id    User ID
     * @return   array             Referral statistics
     */
    public static function get_user_referral_stats($user_id)
    {
        global $wpdb;

        $stats = array(
            'total_referrals' => 0,
            'total_rewards' => 0,
            'pending_rewards' => 0,
            'processed_rewards' => 0,
            'recent_referrals' => array()
        );

        // Get total referrals
        $stats['total_referrals'] = $wpdb->get_var($wpdb->prepare(
            "SELECT COUNT(*) FROM {$wpdb->prefix}osna_referral_usage WHERE referrer_id = %d",
            $user_id
        ));

        // Get reward totals
        $reward_stats = $wpdb->get_row($wpdb->prepare(
            "SELECT 
                SUM(reward_value) as total_rewards,
                SUM(CASE WHEN status = 'pending' THEN reward_value ELSE 0 END) as pending_rewards,
                SUM(CASE WHEN status = 'processed' THEN reward_value ELSE 0 END) as processed_rewards
             FROM {$wpdb->prefix}osna_referral_rewards WHERE user_id = %d",
            $user_id
        ));

        if ($reward_stats) {
            $stats['total_rewards'] = floatval($reward_stats->total_rewards);
            $stats['pending_rewards'] = floatval($reward_stats->pending_rewards);
            $stats['processed_rewards'] = floatval($reward_stats->processed_rewards);
        }

        // Get recent referrals
        $stats['recent_referrals'] = $wpdb->get_results($wpdb->prepare(
            "SELECT ru.*, u.display_name as referee_name, rc.code 
             FROM {$wpdb->prefix}osna_referral_usage ru
             LEFT JOIN {$wpdb->prefix}users u ON ru.referee_id = u.ID
             LEFT JOIN {$wpdb->prefix}osna_referral_codes rc ON ru.referral_code_id = rc.id
             WHERE ru.referrer_id = %d 
             ORDER BY ru.created_at DESC 
             LIMIT 10",
            $user_id
        ));

        return $stats;
    }
}