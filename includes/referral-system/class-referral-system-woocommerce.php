<?php
/**
 * WooCommerce integration for the Referral System.
 *
 * @since      1.0.0
 * @package    OSNA_Tools
 * @subpackage OSNA_Tools/includes/referral-system
 */

class Referral_System_WooCommerce
{
    /**
     * Initialize WooCommerce integration.
     *
     * @since    1.0.0
     */
    public function init()
    {
        // Add referral code field to checkout
        add_action('woocommerce_review_order_before_payment', array($this, 'add_referral_code_field'));
        
        // Process referral code on order creation
        add_action('woocommerce_checkout_order_processed', array($this, 'process_referral_code'), 10, 3);
        
        // Apply discount when referral code is used
        add_action('woocommerce_cart_calculate_fees', array($this, 'apply_referral_discount'));
        
        // Add referral code info to order
        add_action('woocommerce_checkout_update_order_meta', array($this, 'save_referral_code_to_order'));
        
        // Display referral code in order details
        add_action('woocommerce_order_details_after_order_table', array($this, 'display_referral_code_in_order'));
        
        // Add referral code column to orders admin
        add_filter('manage_edit-shop_order_columns', array($this, 'add_referral_code_column'));
        add_action('manage_shop_order_posts_custom_column', array($this, 'display_referral_code_column'), 10, 2);
        
        // AJAX handlers
        add_action('wp_ajax_apply_referral_code', array($this, 'ajax_apply_referral_code'));
        add_action('wp_ajax_nopriv_apply_referral_code', array($this, 'ajax_apply_referral_code'));
        add_action('wp_ajax_remove_referral_code', array($this, 'ajax_remove_referral_code'));
        add_action('wp_ajax_nopriv_remove_referral_code', array($this, 'ajax_remove_referral_code'));
        
        // Enqueue scripts
        add_action('wp_enqueue_scripts', array($this, 'enqueue_scripts'));
        
        // Process rewards when order is completed
        add_action('woocommerce_order_status_completed', array($this, 'process_referral_rewards'));
    }

    /**
     * Add referral code field to checkout.
     *
     * @since    1.0.0
     */
    public function add_referral_code_field()
    {
        $applied_code = WC()->session->get('applied_referral_code');
        ?>
        <div id="referral-code-section" class="referral-code-section">
            <h3><?php _e('Referral Code', 'osna-wp-tools'); ?></h3>
            
            <?php if ($applied_code): ?>
                <div class="referral-code-applied">
                    <p>
                        <strong><?php _e('Applied Referral Code:', 'osna-wp-tools'); ?></strong> 
                        <?php echo esc_html($applied_code['code']); ?>
                        <button type="button" id="remove-referral-code" class="button"><?php _e('Remove', 'osna-wp-tools'); ?></button>
                    </p>
                </div>
            <?php else: ?>
                <div class="referral-code-form">
                    <p>
                        <input type="text" id="referral_code" name="referral_code" placeholder="<?php esc_attr_e('Enter referral code', 'osna-wp-tools'); ?>" maxlength="20" style="text-transform: uppercase;" />
                        <button type="button" id="apply-referral-code" class="button"><?php _e('Apply', 'osna-wp-tools'); ?></button>
                    </p>
                    <div id="referral-code-messages"></div>
                </div>
            <?php endif; ?>
        </div>
        <?php
    }

    /**
     * Apply referral discount to cart.
     *
     * @since    1.0.0
     */
    public function apply_referral_discount()
    {
        if (is_admin() && !defined('DOING_AJAX')) {
            return;
        }

        $applied_code = WC()->session->get('applied_referral_code');
        
        if (!$applied_code) {
            return;
        }

        $referral_code = Referral_System::get_referral_code($applied_code['code']);
        
        if (!$referral_code) {
            // Code is no longer valid, remove it
            WC()->session->__unset('applied_referral_code');
            return;
        }

        $cart_total = WC()->cart->get_subtotal();
        $discount_amount = 0;

        if ($referral_code->discount_type === 'percentage') {
            $discount_amount = ($cart_total * $referral_code->discount_value) / 100;
        } else {
            $discount_amount = min($referral_code->discount_value, $cart_total);
        }

        if ($discount_amount > 0) {
            WC()->cart->add_fee(
                sprintf(__('Referral Discount (%s)', 'osna-wp-tools'), $referral_code->code),
                -$discount_amount
            );
        }
    }

    /**
     * Save referral code to order meta.
     *
     * @since    1.0.0
     * @param    int    $order_id    Order ID
     */
    public function save_referral_code_to_order($order_id)
    {
        $applied_code = WC()->session->get('applied_referral_code');
        
        if ($applied_code) {
            update_post_meta($order_id, '_referral_code', $applied_code['code']);
            update_post_meta($order_id, '_referral_code_data', $applied_code);
        }
    }

    /**
     * Process referral code when order is created.
     *
     * @since    1.0.0
     * @param    int      $order_id    Order ID
     * @param    array    $posted_data Posted data
     * @param    WC_Order $order       Order object
     */
    public function process_referral_code($order_id, $posted_data, $order)
    {
        $applied_code = WC()->session->get('applied_referral_code');
        
        if ($applied_code) {
            $user_id = $order->get_user_id() ?: 0;
            
            // Apply referral code
            $result = Referral_System::apply_referral_code($applied_code['code'], $order_id, $user_id);
            
            if (is_wp_error($result)) {
                // Log error but don't prevent order creation
                error_log('Referral code application failed: ' . $result->get_error_message());
            }
            
            // Clear session
            WC()->session->__unset('applied_referral_code');
        }
    }

    /**
     * Display referral code in order details.
     *
     * @since    1.0.0
     * @param    WC_Order    $order    Order object
     */
    public function display_referral_code_in_order($order)
    {
        $referral_code = get_post_meta($order->get_id(), '_referral_code', true);
        
        if ($referral_code) {
            ?>
            <h2><?php _e('Referral Information', 'osna-wp-tools'); ?></h2>
            <table class="woocommerce-table woocommerce-table--order-details shop_table order_details">
                <tbody>
                    <tr>
                        <th><?php _e('Referral Code Used:', 'osna-wp-tools'); ?></th>
                        <td><?php echo esc_html($referral_code); ?></td>
                    </tr>
                </tbody>
            </table>
            <?php
        }
    }

    /**
     * Add referral code column to orders admin.
     *
     * @since    1.0.0
     * @param    array    $columns    Existing columns
     * @return   array               Modified columns
     */
    public function add_referral_code_column($columns)
    {
        $new_columns = array();
        
        foreach ($columns as $key => $column) {
            $new_columns[$key] = $column;
            
            if ($key === 'order_status') {
                $new_columns['referral_code'] = __('Referral Code', 'osna-wp-tools');
            }
        }
        
        return $new_columns;
    }

    /**
     * Display referral code in orders admin column.
     *
     * @since    1.0.0
     * @param    string    $column     Column name
     * @param    int       $post_id    Post ID
     */
    public function display_referral_code_column($column, $post_id)
    {
        if ($column === 'referral_code') {
            $referral_code = get_post_meta($post_id, '_referral_code', true);
            echo $referral_code ? esc_html($referral_code) : '—';
        }
    }

    /**
     * AJAX handler to apply referral code.
     *
     * @since    1.0.0
     */
    public function ajax_apply_referral_code()
    {
        check_ajax_referer('woocommerce-cart', 'security');

        $code = sanitize_text_field($_POST['referral_code'] ?? '');
        
        if (empty($code)) {
            wp_send_json_error(array('message' => __('Please enter a referral code', 'osna-wp-tools')));
        }

        $referral_code = Referral_System::get_referral_code($code);
        
        if (!$referral_code) {
            wp_send_json_error(array('message' => __('Invalid or inactive referral code', 'osna-wp-tools')));
        }

        // Check usage limit
        if ($referral_code->usage_limit && $referral_code->usage_count >= $referral_code->usage_limit) {
            wp_send_json_error(array('message' => __('Referral code usage limit exceeded', 'osna-wp-tools')));
        }

        // Check if user is trying to use their own code
        $current_user_id = get_current_user_id();
        if ($current_user_id && $referral_code->user_id == $current_user_id) {
            wp_send_json_error(array('message' => __('You cannot use your own referral code', 'osna-wp-tools')));
        }

        // Store in session
        WC()->session->set('applied_referral_code', array(
            'code' => $referral_code->code,
            'discount_type' => $referral_code->discount_type,
            'discount_value' => $referral_code->discount_value
        ));

        wp_send_json_success(array(
            'message' => sprintf(__('Referral code "%s" applied successfully!', 'osna-wp-tools'), $referral_code->code),
            'code' => $referral_code->code
        ));
    }

    /**
     * AJAX handler to remove referral code.
     *
     * @since    1.0.0
     */
    public function ajax_remove_referral_code()
    {
        check_ajax_referer('woocommerce-cart', 'security');

        WC()->session->__unset('applied_referral_code');

        wp_send_json_success(array(
            'message' => __('Referral code removed', 'osna-wp-tools')
        ));
    }

    /**
     * Enqueue scripts for referral code functionality.
     *
     * @since    1.0.0
     */
    public function enqueue_scripts()
    {
        if (is_checkout()) {
            wp_enqueue_script(
                'osna-referral-checkout',
                OSNA_TOOLS_PLUGIN_URL . 'public/js/referral-checkout.js',
                array('jquery'),
                OSNA_TOOLS_VERSION,
                true
            );

            wp_localize_script('osna-referral-checkout', 'osnaReferral', array(
                'ajaxUrl' => admin_url('admin-ajax.php'),
                'nonce' => wp_create_nonce('woocommerce-cart'),
                'messages' => array(
                    'applying' => __('Applying...', 'osna-wp-tools'),
                    'removing' => __('Removing...', 'osna-wp-tools')
                )
            ));

            wp_enqueue_style(
                'osna-referral-checkout',
                OSNA_TOOLS_PLUGIN_URL . 'public/css/referral-checkout.css',
                array(),
                OSNA_TOOLS_VERSION
            );
        }
    }

    /**
     * Process referral rewards when order is completed.
     *
     * @since    1.0.0
     * @param    int    $order_id    Order ID
     */
    public function process_referral_rewards($order_id)
    {
        global $wpdb;

        // Get pending rewards for this order
        $rewards = $wpdb->get_results($wpdb->prepare(
            "SELECT rr.* FROM {$wpdb->prefix}osna_referral_rewards rr
             INNER JOIN {$wpdb->prefix}osna_referral_usage ru ON rr.referral_usage_id = ru.id
             WHERE ru.order_id = %d AND rr.status = 'pending'",
            $order_id
        ));

        foreach ($rewards as $reward) {
            // Process reward based on type
            $this->process_reward($reward);
            
            // Update reward status
            $wpdb->update(
                $wpdb->prefix . 'osna_referral_rewards',
                array(
                    'status' => 'processed',
                    'processed_at' => current_time('mysql')
                ),
                array('id' => $reward->id),
                array('%s', '%s'),
                array('%d')
            );
        }
    }

    /**
     * Process individual reward.
     *
     * @since    1.0.0
     * @param    object    $reward    Reward object
     */
    private function process_reward($reward)
    {
        switch ($reward->reward_type) {
            case 'fixed':
            case 'commission':
                // Create WooCommerce credit/coupon for the user
                $this->create_user_credit($reward->user_id, $reward->reward_value);
                break;
                
            case 'percentage':
                // Create percentage discount coupon
                $this->create_percentage_coupon($reward->user_id, $reward->reward_value);
                break;
        }
        
        // Send notification email to user
        $this->send_reward_notification($reward);
    }

    /**
     * Create user credit as a coupon.
     *
     * @since    1.0.0
     * @param    int      $user_id    User ID
     * @param    float    $amount     Credit amount
     */
    private function create_user_credit($user_id, $amount)
    {
        $user = get_user_by('id', $user_id);
        if (!$user) {
            return;
        }

        $coupon_code = 'REFERRAL_' . $user_id . '_' . time();
        
        $coupon = array(
            'post_title' => $coupon_code,
            'post_content' => '',
            'post_status' => 'publish',
            'post_author' => 1,
            'post_type' => 'shop_coupon'
        );

        $coupon_id = wp_insert_post($coupon);

        // Set coupon meta
        update_post_meta($coupon_id, 'discount_type', 'fixed_cart');
        update_post_meta($coupon_id, 'coupon_amount', $amount);
        update_post_meta($coupon_id, 'individual_use', 'yes');
        update_post_meta($coupon_id, 'usage_limit', 1);
        update_post_meta($coupon_id, 'usage_limit_per_user', 1);
        update_post_meta($coupon_id, 'expiry_date', date('Y-m-d', strtotime('+1 year')));
        update_post_meta($coupon_id, 'customer_email', array($user->user_email));
        update_post_meta($coupon_id, '_is_referral_reward', 'yes');
    }

    /**
     * Create percentage discount coupon.
     *
     * @since    1.0.0
     * @param    int      $user_id       User ID
     * @param    float    $percentage    Percentage discount
     */
    private function create_percentage_coupon($user_id, $percentage)
    {
        $user = get_user_by('id', $user_id);
        if (!$user) {
            return;
        }

        $coupon_code = 'REFERRAL_PCT_' . $user_id . '_' . time();
        
        $coupon = array(
            'post_title' => $coupon_code,
            'post_content' => '',
            'post_status' => 'publish',
            'post_author' => 1,
            'post_type' => 'shop_coupon'
        );

        $coupon_id = wp_insert_post($coupon);

        // Set coupon meta
        update_post_meta($coupon_id, 'discount_type', 'percent');
        update_post_meta($coupon_id, 'coupon_amount', $percentage);
        update_post_meta($coupon_id, 'individual_use', 'yes');
        update_post_meta($coupon_id, 'usage_limit', 1);
        update_post_meta($coupon_id, 'usage_limit_per_user', 1);
        update_post_meta($coupon_id, 'expiry_date', date('Y-m-d', strtotime('+1 year')));
        update_post_meta($coupon_id, 'customer_email', array($user->user_email));
        update_post_meta($coupon_id, '_is_referral_reward', 'yes');
    }

    /**
     * Send reward notification email.
     *
     * @since    1.0.0
     * @param    object    $reward    Reward object
     */
    private function send_reward_notification($reward)
    {
        $user = get_user_by('id', $reward->user_id);
        if (!$user) {
            return;
        }

        $subject = __('You received a referral reward!', 'osna-wp-tools');
        $message = sprintf(
            __('Congratulations! You have received a referral reward of $%s for referring a customer. The reward has been added to your account as a coupon.', 'osna-wp-tools'),
            number_format($reward->reward_value, 2)
        );

        wp_mail($user->user_email, $subject, $message);
    }
}