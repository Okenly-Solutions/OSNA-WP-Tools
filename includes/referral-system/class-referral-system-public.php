<?php
/**
 * The public-facing functionality for the Referral System.
 *
 * @since      1.0.0
 * @package    OSNA_Tools
 * @subpackage OSNA_Tools/includes/referral-system
 */

class Referral_System_Public
{
    /**
     * Initialize public functionality.
     *
     * @since    1.0.0
     */
    public function init()
    {
        // Add referral dashboard to user account
        add_action('init', array($this, 'add_account_endpoints'));
        add_filter('woocommerce_account_menu_items', array($this, 'add_account_menu_items'));
        add_action('woocommerce_account_referrals_endpoint', array($this, 'referrals_endpoint_content'));
        
        // Handle referral links
        add_action('init', array($this, 'handle_referral_links'));
        
        // Enqueue public scripts
        add_action('wp_enqueue_scripts', array($this, 'enqueue_scripts'));
        
        // Shortcodes
        add_shortcode('referral_dashboard', array($this, 'referral_dashboard_shortcode'));
        add_shortcode('referral_link', array($this, 'referral_link_shortcode'));
    }

    /**
     * Add WooCommerce account endpoints.
     *
     * @since    1.0.0
     */
    public function add_account_endpoints()
    {
        add_rewrite_endpoint('referrals', EP_ROOT | EP_PAGES);
    }

    /**
     * Add menu items to WooCommerce account.
     *
     * @since    1.0.0
     * @param    array    $items    Existing menu items
     * @return   array             Modified menu items
     */
    public function add_account_menu_items($items)
    {
        $new_items = array();
        
        foreach ($items as $key => $item) {
            $new_items[$key] = $item;
            
            if ($key === 'orders') {
                $new_items['referrals'] = __('Referrals', 'osna-wp-tools');
            }
        }
        
        return $new_items;
    }

    /**
     * Content for referrals endpoint.
     *
     * @since    1.0.0
     */
    public function referrals_endpoint_content()
    {
        $user_id = get_current_user_id();
        $stats = Referral_System::get_user_referral_stats($user_id);
        
        // Get user's referral codes
        global $wpdb;
        $user_codes = $wpdb->get_results($wpdb->prepare(
            "SELECT * FROM {$wpdb->prefix}osna_referral_codes WHERE user_id = %d AND status = 'active'",
            $user_id
        ));
        
        ?>
        <div class="osna-referrals-dashboard">
            <h2><?php _e('My Referrals', 'osna-wp-tools'); ?></h2>
            
            <!-- Statistics Overview -->
            <div class="referral-stats">
                <div class="stat-box">
                    <h3><?php echo esc_html($stats['total_referrals']); ?></h3>
                    <p><?php _e('Total Referrals', 'osna-wp-tools'); ?></p>
                </div>
                <div class="stat-box">
                    <h3>$<?php echo esc_html(number_format($stats['total_rewards'], 2)); ?></h3>
                    <p><?php _e('Total Rewards', 'osna-wp-tools'); ?></p>
                </div>
                <div class="stat-box">
                    <h3>$<?php echo esc_html(number_format($stats['pending_rewards'], 2)); ?></h3>
                    <p><?php _e('Pending Rewards', 'osna-wp-tools'); ?></p>
                </div>
                <div class="stat-box">
                    <h3>$<?php echo esc_html(number_format($stats['processed_rewards'], 2)); ?></h3>
                    <p><?php _e('Processed Rewards', 'osna-wp-tools'); ?></p>
                </div>
            </div>

            <!-- Referral Codes -->
            <?php if (!empty($user_codes)): ?>
                <div class="referral-codes-section">
                    <h3><?php _e('Your Referral Codes', 'osna-wp-tools'); ?></h3>
                    
                    <?php foreach ($user_codes as $code): ?>
                        <div class="referral-code-card">
                            <div class="code-info">
                                <h4><?php echo esc_html($code->code); ?></h4>
                                <p>
                                    <?php if ($code->discount_type === 'percentage'): ?>
                                        <?php printf(__('%s%% discount', 'osna-wp-tools'), $code->discount_value); ?>
                                    <?php else: ?>
                                        <?php printf(__('$%s discount', 'osna-wp-tools'), $code->discount_value); ?>
                                    <?php endif; ?>
                                </p>
                                <p class="usage-info">
                                    <?php printf(__('Used %d times', 'osna-wp-tools'), $code->usage_count); ?>
                                    <?php if ($code->usage_limit): ?>
                                        <?php printf(__(' of %d', 'osna-wp-tools'), $code->usage_limit); ?>
                                    <?php endif; ?>
                                </p>
                            </div>
                            
                            <div class="code-actions">
                                <div class="referral-link">
                                    <label><?php _e('Referral Link:', 'osna-wp-tools'); ?></label>
                                    <input type="text" 
                                           value="<?php echo esc_attr($this->get_referral_link($code->code)); ?>" 
                                           readonly 
                                           class="referral-link-input" />
                                    <button type="button" 
                                            class="copy-link-btn button" 
                                            data-link="<?php echo esc_attr($this->get_referral_link($code->code)); ?>">
                                        <?php _e('Copy', 'osna-wp-tools'); ?>
                                    </button>
                                </div>
                                
                                <div class="social-share">
                                    <p><?php _e('Share on:', 'osna-wp-tools'); ?></p>
                                    <a href="<?php echo esc_url($this->get_facebook_share_url($code->code)); ?>" 
                                       target="_blank" 
                                       class="share-btn facebook">
                                        <?php _e('Facebook', 'osna-wp-tools'); ?>
                                    </a>
                                    <a href="<?php echo esc_url($this->get_twitter_share_url($code->code)); ?>" 
                                       target="_blank" 
                                       class="share-btn twitter">
                                        <?php _e('Twitter', 'osna-wp-tools'); ?>
                                    </a>
                                    <a href="<?php echo esc_url($this->get_whatsapp_share_url($code->code)); ?>" 
                                       target="_blank" 
                                       class="share-btn whatsapp">
                                        <?php _e('WhatsApp', 'osna-wp-tools'); ?>
                                    </a>
                                </div>
                            </div>
                        </div>
                    <?php endforeach; ?>
                </div>
            <?php endif; ?>

            <!-- Recent Referrals -->
            <?php if (!empty($stats['recent_referrals'])): ?>
                <div class="recent-referrals">
                    <h3><?php _e('Recent Referrals', 'osna-wp-tools'); ?></h3>
                    <table class="woocommerce-table woocommerce-table--order-details shop_table">
                        <thead>
                            <tr>
                                <th><?php _e('Code', 'osna-wp-tools'); ?></th>
                                <th><?php _e('Customer', 'osna-wp-tools'); ?></th>
                                <th><?php _e('Order', 'osna-wp-tools'); ?></th>
                                <th><?php _e('Discount', 'osna-wp-tools'); ?></th>
                                <th><?php _e('Reward', 'osna-wp-tools'); ?></th>
                                <th><?php _e('Date', 'osna-wp-tools'); ?></th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php foreach ($stats['recent_referrals'] as $referral): ?>
                                <tr>
                                    <td><?php echo esc_html($referral->code); ?></td>
                                    <td><?php echo esc_html($referral->referee_name ?: 'Guest'); ?></td>
                                    <td>
                                        <a href="<?php echo esc_url(wc_get_endpoint_url('view-order', $referral->order_id, wc_get_page_permalink('myaccount'))); ?>">
                                            #<?php echo esc_html($referral->order_id); ?>
                                        </a>
                                    </td>
                                    <td>$<?php echo esc_html(number_format($referral->discount_amount, 2)); ?></td>
                                    <td>$<?php echo esc_html(number_format($referral->reward_amount, 2)); ?></td>
                                    <td><?php echo esc_html(date_i18n(get_option('date_format'), strtotime($referral->created_at))); ?></td>
                                </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                </div>
            <?php endif; ?>

            <?php if (empty($user_codes)): ?>
                <div class="no-referral-codes">
                    <p><?php _e('You don\'t have any active referral codes yet. Contact us to get your referral code!', 'osna-wp-tools'); ?></p>
                </div>
            <?php endif; ?>
        </div>
        <?php
    }

    /**
     * Handle referral links when users visit the site.
     *
     * @since    1.0.0
     */
    public function handle_referral_links()
    {
        if (isset($_GET['ref']) && !empty($_GET['ref'])) {
            $referral_code = sanitize_text_field($_GET['ref']);
            
            // Validate the referral code
            $code_data = Referral_System::get_referral_code($referral_code);
            
            if ($code_data) {
                // Store in session/cookie for later use
                if (!headers_sent()) {
                    setcookie('referral_code', $referral_code, time() + (30 * 24 * 60 * 60), '/'); // 30 days
                }
                
                // Store in session if WooCommerce is available
                if (function_exists('WC') && WC()->session) {
                    WC()->session->set('pending_referral_code', $referral_code);
                }
            }
        }
    }

    /**
     * Enqueue public scripts and styles.
     *
     * @since    1.0.0
     */
    public function enqueue_scripts()
    {
        if (is_account_page()) {
            wp_enqueue_script(
                'osna-referral-public',
                OSNA_TOOLS_PLUGIN_URL . 'public/js/referral-public.js',
                array('jquery'),
                OSNA_TOOLS_VERSION,
                true
            );

            wp_localize_script('osna-referral-public', 'osnaReferralPublic', array(
                'messages' => array(
                    'copied' => __('Link copied to clipboard!', 'osna-wp-tools'),
                    'copy_failed' => __('Failed to copy link', 'osna-wp-tools')
                )
            ));

            wp_enqueue_style(
                'osna-referral-public',
                OSNA_TOOLS_PLUGIN_URL . 'public/css/referral-public.css',
                array(),
                OSNA_TOOLS_VERSION
            );
        }
    }

    /**
     * Referral dashboard shortcode.
     *
     * @since    1.0.0
     * @param    array    $atts    Shortcode attributes
     * @return   string           Shortcode output
     */
    public function referral_dashboard_shortcode($atts)
    {
        if (!is_user_logged_in()) {
            return '<p>' . __('Please log in to view your referral dashboard.', 'osna-wp-tools') . '</p>';
        }

        ob_start();
        $this->referrals_endpoint_content();
        return ob_get_clean();
    }

    /**
     * Referral link shortcode.
     *
     * @since    1.0.0
     * @param    array    $atts    Shortcode attributes
     * @return   string           Shortcode output
     */
    public function referral_link_shortcode($atts)
    {
        $atts = shortcode_atts(array(
            'code' => '',
            'text' => __('Refer a Friend', 'osna-wp-tools')
        ), $atts);

        if (empty($atts['code'])) {
            return '';
        }

        $link = $this->get_referral_link($atts['code']);
        
        return sprintf(
            '<a href="%s" class="referral-link-button">%s</a>',
            esc_url($link),
            esc_html($atts['text'])
        );
    }

    /**
     * Get referral link for a code.
     *
     * @since    1.0.0
     * @param    string    $code    Referral code
     * @return   string            Referral link
     */
    private function get_referral_link($code)
    {
        return add_query_arg('ref', $code, home_url());
    }

    /**
     * Get Facebook share URL.
     *
     * @since    1.0.0
     * @param    string    $code    Referral code
     * @return   string            Facebook share URL
     */
    private function get_facebook_share_url($code)
    {
        $link = $this->get_referral_link($code);
        $text = urlencode(sprintf(__('Check out this amazing store! Use my referral code %s for a discount.', 'osna-wp-tools'), $code));
        
        return "https://www.facebook.com/sharer/sharer.php?u=" . urlencode($link) . "&quote=" . $text;
    }

    /**
     * Get Twitter share URL.
     *
     * @since    1.0.0
     * @param    string    $code    Referral code
     * @return   string            Twitter share URL
     */
    private function get_twitter_share_url($code)
    {
        $link = $this->get_referral_link($code);
        $text = urlencode(sprintf(__('Check out this amazing store! Use my referral code %s for a discount. %s', 'osna-wp-tools'), $code, $link));
        
        return "https://twitter.com/intent/tweet?text=" . $text;
    }

    /**
     * Get WhatsApp share URL.
     *
     * @since    1.0.0
     * @param    string    $code    Referral code
     * @return   string            WhatsApp share URL
     */
    private function get_whatsapp_share_url($code)
    {
        $link = $this->get_referral_link($code);
        $text = urlencode(sprintf(__('Check out this amazing store! Use my referral code %s for a discount: %s', 'osna-wp-tools'), $code, $link));
        
        return "https://wa.me/?text=" . $text;
    }
}