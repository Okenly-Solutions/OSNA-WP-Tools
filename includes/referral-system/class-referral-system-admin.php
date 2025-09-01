<?php
/**
 * The admin functionality for the Referral System.
 *
 * @since      1.0.0
 * @package    OSNA_Tools
 * @subpackage OSNA_Tools/includes/referral-system
 */

class Referral_System_Admin
{
    /**
     * Initialize the admin functionality.
     *
     * @since    1.0.0
     */
    public function init()
    {
        add_action('admin_menu', array($this, 'add_admin_menu'));
        add_action('admin_enqueue_scripts', array($this, 'enqueue_scripts'));
    }

    /**
     * Add admin menu pages.
     *
     * @since    1.0.0
     */
    public function add_admin_menu()
    {
        add_submenu_page(
            'osna-tools',
            'Referral System',
            'Referral System',
            'manage_options',
            'osna-referral-system',
            array($this, 'display_dashboard')
        );
    }

    /**
     * Enqueue admin scripts and styles.
     *
     * @since    1.0.0
     * @param    string    $hook    Current admin page hook
     */
    public function enqueue_scripts($hook)
    {
        if ('osna-tools_page_osna-referral-system' !== $hook) {
            return;
        }

        wp_enqueue_script('chart-js', 'https://cdn.jsdelivr.net/npm/chart.js', array(), '3.9.1', true);
        wp_enqueue_script(
            'osna-referral-admin',
            OSNA_TOOLS_PLUGIN_URL . 'admin/js/referral-system-admin.js',
            array('jquery', 'chart-js'),
            OSNA_TOOLS_VERSION,
            true
        );

        wp_localize_script('osna-referral-admin', 'osnaReferralAdmin', array(
            'ajaxUrl' => admin_url('admin-ajax.php'),
            'restUrl' => rest_url('osna/v1/'),
            'nonce' => wp_create_nonce('wp_rest'),
            'currentUserId' => get_current_user_id()
        ));

        wp_enqueue_style(
            'osna-referral-admin',
            OSNA_TOOLS_PLUGIN_URL . 'admin/css/referral-system-admin.css',
            array(),
            OSNA_TOOLS_VERSION
        );
    }

    /**
     * Display the referral system dashboard.
     *
     * @since    1.0.0
     */
    public function display_dashboard()
    {
        global $wpdb;

        // Get overview statistics
        $stats = $this->get_overview_stats();
        
        ?>
        <div class="wrap">
            <h1>Referral System Dashboard</h1>
            
            <!-- Overview Stats -->
            <div class="osna-referral-overview">
                <div class="osna-stats-grid">
                    <div class="osna-stat-card">
                        <h3>Total Referral Codes</h3>
                        <div class="osna-stat-number"><?php echo esc_html($stats->total_codes); ?></div>
                    </div>
                    <div class="osna-stat-card">
                        <h3>Active Codes</h3>
                        <div class="osna-stat-number"><?php echo esc_html($stats->active_codes); ?></div>
                    </div>
                    <div class="osna-stat-card">
                        <h3>Total Referrals</h3>
                        <div class="osna-stat-number"><?php echo esc_html($stats->total_referrals); ?></div>
                    </div>
                    <div class="osna-stat-card">
                        <h3>Total Rewards</h3>
                        <div class="osna-stat-number">$<?php echo esc_html(number_format($stats->total_rewards, 2)); ?></div>
                    </div>
                </div>
            </div>

            <!-- Chart Section -->
            <div class="osna-referral-charts">
                <div class="osna-chart-container">
                    <h3>Referrals Over Time (Last 30 Days)</h3>
                    <canvas id="referralsChart" width="400" height="200"></canvas>
                </div>
            </div>

            <!-- Tabs Navigation -->
            <div class="osna-referral-tabs">
                <nav class="nav-tab-wrapper">
                    <a href="#codes" class="nav-tab nav-tab-active" data-tab="codes">Referral Codes</a>
                    <a href="#usage" class="nav-tab" data-tab="usage">Usage Statistics</a>
                    <a href="#rewards" class="nav-tab" data-tab="rewards">Rewards</a>
                    <a href="#settings" class="nav-tab" data-tab="settings">Settings</a>
                </nav>

                <!-- Referral Codes Tab -->
                <div id="tab-codes" class="osna-tab-content active">
                    <div class="osna-table-controls">
                        <button id="create-code-btn" class="button button-primary">Create New Referral Code</button>
                        <div class="osna-search-box">
                            <input type="text" id="codes-search" placeholder="Search codes..." />
                            <button id="codes-search-btn" class="button">Search</button>
                        </div>
                    </div>

                    <div class="osna-table-container">
                        <table class="wp-list-table widefat fixed striped" id="referral-codes-table">
                            <thead>
                                <tr>
                                    <th>Code</th>
                                    <th>User</th>
                                    <th>Discount</th>
                                    <th>Reward</th>
                                    <th>Usage</th>
                                    <th>Status</th>
                                    <th>Created</th>
                                    <th>Actions</th>
                                </tr>
                            </thead>
                            <tbody id="codes-table-body">
                                <!-- Data loaded via JavaScript -->
                            </tbody>
                        </table>
                    </div>

                    <div class="osna-pagination" id="codes-pagination">
                        <!-- Pagination loaded via JavaScript -->
                    </div>
                </div>

                <!-- Usage Statistics Tab -->
                <div id="tab-usage" class="osna-tab-content">
                    <div class="osna-table-container">
                        <table class="wp-list-table widefat fixed striped" id="referral-usage-table">
                            <thead>
                                <tr>
                                    <th>Code</th>
                                    <th>Referrer</th>
                                    <th>Referee</th>
                                    <th>Order ID</th>
                                    <th>Discount Amount</th>
                                    <th>Reward Amount</th>
                                    <th>Date</th>
                                </tr>
                            </thead>
                            <tbody id="usage-table-body">
                                <!-- Data loaded via JavaScript -->
                            </tbody>
                        </table>
                    </div>

                    <div class="osna-pagination" id="usage-pagination">
                        <!-- Pagination loaded via JavaScript -->
                    </div>
                </div>

                <!-- Rewards Tab -->
                <div id="tab-rewards" class="osna-tab-content">
                    <div class="osna-table-container">
                        <table class="wp-list-table widefat fixed striped" id="referral-rewards-table">
                            <thead>
                                <tr>
                                    <th>User</th>
                                    <th>Reward Type</th>
                                    <th>Reward Value</th>
                                    <th>Status</th>
                                    <th>Created</th>
                                    <th>Processed</th>
                                    <th>Actions</th>
                                </tr>
                            </thead>
                            <tbody id="rewards-table-body">
                                <!-- Data loaded via JavaScript -->
                            </tbody>
                        </table>
                    </div>
                </div>

                <!-- Settings Tab -->
                <div id="tab-settings" class="osna-tab-content">
                    <form id="referral-settings-form">
                        <table class="form-table">
                            <tr>
                                <th scope="row">Default Discount Type</th>
                                <td>
                                    <select name="default_discount_type">
                                        <option value="percentage">Percentage</option>
                                        <option value="fixed">Fixed Amount</option>
                                    </select>
                                </td>
                            </tr>
                            <tr>
                                <th scope="row">Default Discount Value</th>
                                <td>
                                    <input type="number" name="default_discount_value" step="0.01" min="0" />
                                    <p class="description">Default discount value for new referral codes</p>
                                </td>
                            </tr>
                            <tr>
                                <th scope="row">Default Reward Type</th>
                                <td>
                                    <select name="default_reward_type">
                                        <option value="fixed">Fixed Amount</option>
                                        <option value="percentage">Percentage</option>
                                        <option value="commission">Commission</option>
                                    </select>
                                </td>
                            </tr>
                            <tr>
                                <th scope="row">Default Reward Value</th>
                                <td>
                                    <input type="number" name="default_reward_value" step="0.01" min="0" />
                                    <p class="description">Default reward value for new referral codes</p>
                                </td>
                            </tr>
                            <tr>
                                <th scope="row">Auto-approve Rewards</th>
                                <td>
                                    <label>
                                        <input type="checkbox" name="auto_approve_rewards" />
                                        Automatically approve rewards when orders are completed
                                    </label>
                                </td>
                            </tr>
                        </table>

                        <p class="submit">
                            <input type="submit" name="submit" id="submit" class="button button-primary" value="Save Settings">
                        </p>
                    </form>
                </div>
            </div>
        </div>

        <!-- Create/Edit Code Modal -->
        <div id="code-modal" class="osna-modal" style="display: none;">
            <div class="osna-modal-content">
                <span class="osna-modal-close">&times;</span>
                <h2 id="modal-title">Create Referral Code</h2>
                <form id="code-form">
                    <input type="hidden" id="code-id" name="id" />
                    
                    <table class="form-table">
                        <tr>
                            <th scope="row"><label for="code">Referral Code</label></th>
                            <td>
                                <input type="text" id="code" name="code" required maxlength="20" style="text-transform: uppercase;" />
                                <p class="description">Must be uppercase letters and numbers only</p>
                            </td>
                        </tr>
                        <tr>
                            <th scope="row"><label for="user_id">User</label></th>
                            <td>
                                <select id="user_id" name="user_id" required>
                                    <option value="">Select User...</option>
                                    <?php
                                    $users = get_users(array('orderby' => 'display_name'));
                                    foreach ($users as $user) {
                                        echo '<option value="' . esc_attr($user->ID) . '">' . esc_html($user->display_name) . ' (' . esc_html($user->user_email) . ')</option>';
                                    }
                                    ?>
                                </select>
                            </td>
                        </tr>
                        <tr>
                            <th scope="row"><label for="discount_type">Discount Type</label></th>
                            <td>
                                <select id="discount_type" name="discount_type">
                                    <option value="percentage">Percentage</option>
                                    <option value="fixed">Fixed Amount</option>
                                </select>
                            </td>
                        </tr>
                        <tr>
                            <th scope="row"><label for="discount_value">Discount Value</label></th>
                            <td>
                                <input type="number" id="discount_value" name="discount_value" step="0.01" min="0" required />
                            </td>
                        </tr>
                        <tr>
                            <th scope="row"><label for="reward_type">Reward Type</label></th>
                            <td>
                                <select id="reward_type" name="reward_type">
                                    <option value="fixed">Fixed Amount</option>
                                    <option value="percentage">Percentage</option>
                                    <option value="commission">Commission</option>
                                </select>
                            </td>
                        </tr>
                        <tr>
                            <th scope="row"><label for="reward_value">Reward Value</label></th>
                            <td>
                                <input type="number" id="reward_value" name="reward_value" step="0.01" min="0" required />
                            </td>
                        </tr>
                        <tr>
                            <th scope="row"><label for="usage_limit">Usage Limit</label></th>
                            <td>
                                <input type="number" id="usage_limit" name="usage_limit" min="1" />
                                <p class="description">Leave empty for unlimited usage</p>
                            </td>
                        </tr>
                        <tr>
                            <th scope="row"><label for="status">Status</label></th>
                            <td>
                                <select id="status" name="status">
                                    <option value="active">Active</option>
                                    <option value="inactive">Inactive</option>
                                </select>
                            </td>
                        </tr>
                    </table>

                    <p class="submit">
                        <input type="submit" class="button button-primary" value="Save Code">
                        <button type="button" class="button" onclick="closeCodeModal()">Cancel</button>
                    </p>
                </form>
            </div>
        </div>

        <script>
        // Chart data for referrals over time
        var referralsChartData = <?php echo json_encode($this->get_referrals_chart_data()); ?>;
        </script>
        <?php
    }

    /**
     * Get overview statistics.
     *
     * @since    1.0.0
     * @return   object    Statistics object
     */
    private function get_overview_stats()
    {
        global $wpdb;

        $stats = new stdClass();

        // Total codes
        $stats->total_codes = $wpdb->get_var("SELECT COUNT(*) FROM {$wpdb->prefix}osna_referral_codes");

        // Active codes
        $stats->active_codes = $wpdb->get_var("SELECT COUNT(*) FROM {$wpdb->prefix}osna_referral_codes WHERE status = 'active'");

        // Total referrals
        $stats->total_referrals = $wpdb->get_var("SELECT COUNT(*) FROM {$wpdb->prefix}osna_referral_usage");

        // Total rewards
        $stats->total_rewards = $wpdb->get_var("SELECT SUM(reward_value) FROM {$wpdb->prefix}osna_referral_rewards") ?: 0;

        return $stats;
    }

    /**
     * Get chart data for referrals over time.
     *
     * @since    1.0.0
     * @return   array    Chart data
     */
    private function get_referrals_chart_data()
    {
        global $wpdb;

        $data = array();
        $labels = array();

        // Get data for last 30 days
        for ($i = 29; $i >= 0; $i--) {
            $date = date('Y-m-d', strtotime("-$i days"));
            $labels[] = date('M j', strtotime($date));

            $count = $wpdb->get_var($wpdb->prepare(
                "SELECT COUNT(*) FROM {$wpdb->prefix}osna_referral_usage WHERE DATE(created_at) = %s",
                $date
            ));

            $data[] = intval($count);
        }

        return array(
            'labels' => $labels,
            'data' => $data
        );
    }
}