<?php
/**
 * Provide a admin area view for the Payment Gateways settings
 *
 * @since      1.0.0
 * @package    OSNA_Tools
 * @subpackage OSNA_Tools/admin/partials
 */

// Get the current gateway from query params
$current_gateway = isset($_GET['gateway']) ? sanitize_text_field($_GET['gateway']) : 'lygos';

// Save settings if form was submitted
if (isset($_POST['osna_payment_gateway_settings_nonce']) && wp_verify_nonce($_POST['osna_payment_gateway_settings_nonce'], 'osna_payment_gateway_settings')) {
    // Common settings
    $api_key = isset($_POST['osna_payment_gateway_api_key']) ? sanitize_text_field($_POST['osna_payment_gateway_api_key']) : '';
    update_option('osna_payment_gateway_api_key', $api_key);

    // Gateway-specific settings
    if ($current_gateway === 'lygos') {
        $lygos_api_key = isset($_POST['osna_lygos_api_key']) ? sanitize_text_field($_POST['osna_lygos_api_key']) : '';
        $lygos_enabled = isset($_POST['osna_lygos_enabled']) ? true : false;

        update_option('osna_lygos_api_key', $lygos_api_key);
        update_option('osna_lygos_enabled', $lygos_enabled);
    } elseif ($current_gateway === 'stripe') {
        $stripe_api_key = isset($_POST['osna_stripe_api_key']) ? sanitize_text_field($_POST['osna_stripe_api_key']) : '';
        $stripe_enabled = isset($_POST['osna_stripe_enabled']) ? true : false;

        update_option('osna_stripe_api_key', $stripe_api_key);
        update_option('osna_stripe_enabled', $stripe_enabled);
    }

    echo '<div class="notice notice-success is-dismissible"><p>' . __('Settings saved successfully.', 'osna-wp-tools') . '</p></div>';
}

// Get saved settings
$api_key = get_option('osna_payment_gateway_api_key', '');
$lygos_api_key = get_option('osna_lygos_api_key', '');
$lygos_enabled = get_option('osna_lygos_enabled', false);
$stripe_api_key = get_option('osna_stripe_api_key', '');
$stripe_enabled = get_option('osna_stripe_enabled', false);
?>

<div class="wrap">
    <div class="bg-white rounded-2xl shadow-xl overflow-hidden max-w-7xl mx-auto my-8">
        <!-- Header -->
        <div class="bg-gradient-to-r from-blue-500 to-indigo-600 px-8 py-6">
            <div class="flex items-center justify-between">
                <h1 class="text-3xl font-semibold text-white">Payment Gateway Settings</h1>
                <span
                    class="px-3 py-1 text-sm bg-white bg-opacity-20 rounded-full text-white">v<?php echo OSNA_TOOLS_VERSION; ?></span>
            </div>
            <p class="text-blue-100 mt-2">Configure your payment gateway settings</p>
        </div>

        <!-- Tabs -->
        <div class="bg-gray-50 px-8 py-4 border-b border-gray-200">
            <div class="flex">
                <a href="<?php echo admin_url('admin.php?page=osna-payment-gateways-settings&gateway=lygos'); ?>"
                    class="px-4 py-2 text-sm font-medium <?php echo $current_gateway === 'lygos' ? 'text-blue-600 bg-white rounded-t-lg border-t border-l border-r border-gray-200' : 'text-gray-500 hover:text-gray-700'; ?>">
                    Lygos
                </a>
                <a href="<?php echo admin_url('admin.php?page=osna-payment-gateways-settings&gateway=stripe'); ?>"
                    class="px-4 py-2 text-sm font-medium <?php echo $current_gateway === 'stripe' ? 'text-blue-600 bg-white rounded-t-lg border-t border-l border-r border-gray-200' : 'text-gray-500 hover:text-gray-700'; ?>">
                    Stripe
                </a>
            </div>
        </div>

        <!-- Content -->
        <div class="p-8">
            <form method="post" action="">
                <?php wp_nonce_field('osna_payment_gateway_settings', 'osna_payment_gateway_settings_nonce'); ?>

                <!-- Common Settings -->
                <div class="p-6 bg-gray-50 rounded-xl mb-8">
                    <h2 class="text-xl font-medium text-gray-800 mb-4">Common Settings</h2>

                    <div class="osna-field-group mb-4">
                        <label for="osna_payment_gateway_api_key"
                            class="block text-sm font-medium text-gray-700 mb-1">API Key for REST API
                            Authentication</label>
                        <input type="text" id="osna_payment_gateway_api_key" name="osna_payment_gateway_api_key"
                            value="<?php echo esc_attr($api_key); ?>"
                            class="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-blue-500 focus:ring focus:ring-blue-500 focus:ring-opacity-50">
                        <p class="text-sm text-gray-500 mt-1">This API key is used to authenticate requests to your
                            payment processing endpoint.</p>
                    </div>

                    <?php if (empty($api_key)): ?>
                        <button type="button" id="generate_api_key"
                            class="inline-flex items-center px-4 py-2 border border-transparent text-sm font-medium rounded-md text-white bg-green-600 hover:bg-green-700 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-green-500">
                            Generate API Key
                        </button>
                    <?php endif; ?>
                </div>

                <?php if ($current_gateway === 'lygos'): ?>
                    <!-- Lygos Settings -->
                    <div class="p-6 bg-gray-50 rounded-xl">
                        <h2 class="text-xl font-medium text-gray-800 mb-4">Lygos Settings</h2>

                        <div class="osna-field-group mb-4">
                            <label for="osna_lygos_enabled" class="inline-flex items-center">
                                <input type="checkbox" id="osna_lygos_enabled" name="osna_lygos_enabled" <?php checked($lygos_enabled); ?>
                                    class="rounded border-gray-300 text-blue-600 shadow-sm focus:border-blue-500 focus:ring focus:ring-blue-500 focus:ring-opacity-50 mr-2">
                                Enable Lygos Payment Gateway
                            </label>
                        </div>

                        <div class="osna-field-group mb-4">
                            <label for="osna_lygos_api_key" class="block text-sm font-medium text-gray-700 mb-1">Lygos API
                                Key</label>
                            <input type="text" id="osna_lygos_api_key" name="osna_lygos_api_key"
                                value="<?php echo esc_attr($lygos_api_key); ?>"
                                class="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-blue-500 focus:ring focus:ring-blue-500 focus:ring-opacity-50">
                            <p class="text-sm text-gray-500 mt-1">Enter your Lygos API key here. You can get this from your
                                Lygos dashboard.</p>
                        </div>

                        <div class="bg-blue-50 border-l-4 border-blue-400 p-4 mb-4">
                            <div class="flex">
                                <div class="flex-shrink-0">
                                    <svg class="h-5 w-5 text-blue-400" xmlns="http://www.w3.org/2000/svg"
                                        viewBox="0 0 20 20" fill="currentColor">
                                        <path fill-rule="evenodd"
                                            d="M18 10a8 8 0 11-16 0 8 8 0 0116 0zm-7-4a1 1 0 11-2 0 1 1 0 012 0zM9 9a1 1 0 000 2v3a1 1 0 001 1h1a1 1 0 100-2v-3a1 1 0 00-1-1H9z"
                                            clip-rule="evenodd" />
                                    </svg>
                                </div>
                                <div class="ml-3">
                                    <p class="text-sm text-blue-700">
                                        To use the Lygos payment gateway, you need to create an account at <a
                                            href="https://lygosapp.com" target="_blank"
                                            class="font-medium underline text-blue-700 hover:text-blue-600">lygosapp.com</a>.
                                    </p>
                                </div>
                            </div>
                        </div>
                    </div>
                <?php elseif ($current_gateway === 'stripe'): ?>
                    <!-- Stripe Settings -->
                    <div class="p-6 bg-gray-50 rounded-xl">
                        <h2 class="text-xl font-medium text-gray-800 mb-4">Stripe Settings</h2>

                        <div class="osna-field-group mb-4">
                            <label for="osna_stripe_enabled" class="inline-flex items-center">
                                <input type="checkbox" id="osna_stripe_enabled" name="osna_stripe_enabled" <?php checked($stripe_enabled); ?>
                                    class="rounded border-gray-300 text-blue-600 shadow-sm focus:border-blue-500 focus:ring focus:ring-blue-500 focus:ring-opacity-50 mr-2">
                                Enable Stripe Payment Gateway
                            </label>
                        </div>

                        <div class="osna-field-group mb-4">
                            <label for="osna_stripe_api_key" class="block text-sm font-medium text-gray-700 mb-1">Stripe API
                                Key</label>
                            <input type="text" id="osna_stripe_api_key" name="osna_stripe_api_key"
                                value="<?php echo esc_attr($stripe_api_key); ?>"
                                class="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-blue-500 focus:ring focus:ring-blue-500 focus:ring-opacity-50">
                            <p class="text-sm text-gray-500 mt-1">Enter your Stripe secret key here. You can get this from
                                your Stripe dashboard.</p>
                        </div>

                        <div class="bg-blue-50 border-l-4 border-blue-400 p-4 mb-4">
                            <div class="flex">
                                <div class="flex-shrink-0">
                                    <svg class="h-5 w-5 text-blue-400" xmlns="http://www.w3.org/2000/svg"
                                        viewBox="0 0 20 20" fill="currentColor">
                                        <path fill-rule="evenodd"
                                            d="M18 10a8 8 0 11-16 0 8 8 0 0116 0zm-7-4a1 1 0 11-2 0 1 1 0 012 0zM9 9a1 1 0 000 2v3a1 1 0 001 1h1a1 1 0 100-2v-3a1 1 0 00-1-1H9z"
                                            clip-rule="evenodd" />
                                    </svg>
                                </div>
                                <div class="ml-3">
                                    <p class="text-sm text-blue-700">
                                        To use the Stripe payment gateway, you need to create an account at <a
                                            href="https://stripe.com" target="_blank"
                                            class="font-medium underline text-blue-700 hover:text-blue-600">stripe.com</a>.
                                    </p>
                                </div>
                            </div>
                        </div>
                    </div>
                <?php endif; ?>

                <div class="mt-6">
                    <button type="submit"
                        class="inline-flex items-center px-4 py-2 border border-transparent text-sm font-medium rounded-md text-white bg-blue-600 hover:bg-blue-700 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-blue-500">
                        Save Settings
                    </button>

                    <a href="<?php echo admin_url('admin.php?page=osna-payment-gateways'); ?>"
                        class="ml-4 inline-flex items-center px-4 py-2 border border-gray-300 shadow-sm text-sm font-medium rounded-md text-gray-700 bg-white hover:bg-gray-50 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-blue-500">
                        Back to Payment Gateways
                    </a>
                </div>
            </form>
        </div>

        <!-- Footer -->
        <div class="bg-gray-50 px-8 py-4 border-t border-gray-200">
            <div class="flex items-center justify-between">
                <p class="text-sm text-gray-500">© <?php echo date('Y'); ?> OSNA WP Tools</p>
                <a href="https://osna.com" target="_blank" class="text-sm text-blue-600 hover:text-blue-800">Visit
                    OSNA</a>
            </div>
        </div>
    </div>
</div>

<script>
    jQuery(document).ready(function ($) {
        $('#generate_api_key').on('click', function () {
            // Generate a random 32-character string
            var chars = 'abcdefghijklmnopqrstuvwxyzABCDEFGHIJKLMNOPQRSTUVWXYZ0123456789';
            var api_key = '';
            for (var i = 0; i < 32; i++) {
                api_key += chars.charAt(Math.floor(Math.random() * chars.length));
            }

            $('#osna_payment_gateway_api_key').val(api_key);
        });
    });
</script>