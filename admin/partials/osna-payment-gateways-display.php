<?php
/**
 * Provide a admin area view for the Payment Gateways page
 *
 * @since      1.0.0
 * @package    OSNA_Tools
 * @subpackage OSNA_Tools/admin/partials
 */
?>

<div class="wrap">
    <div class="bg-white rounded-2xl shadow-xl overflow-hidden max-w-7xl mx-auto my-8">
        <!-- Header -->
        <div class="bg-gradient-to-r from-blue-500 to-indigo-600 px-8 py-6">
            <div class="flex items-center justify-between">
                <h1 class="text-3xl font-semibold text-white">Payment Gateways</h1>
                <span
                    class="px-3 py-1 text-sm bg-white bg-opacity-20 rounded-full text-white">v<?php echo OSNA_TOOLS_VERSION; ?></span>
            </div>
            <p class="text-blue-100 mt-2">Manage and configure payment gateways for your WordPress site</p>
        </div>

        <!-- Content -->
        <div class="p-8">
            <div class="grid grid-cols-1 md:grid-cols-2 gap-8">
                <!-- Lygos Gateway Card -->
                <div
                    class="bg-gray-50 rounded-xl overflow-hidden shadow-md transition-all duration-300 hover:shadow-lg">
                    <div class="p-6">
                        <div class="flex items-center mb-4">
                            <span class="w-10 h-10 flex items-center justify-center bg-blue-500 text-white rounded-lg">
                                <svg xmlns="http://www.w3.org/2000/svg" class="h-6 w-6" fill="none" viewBox="0 0 24 24"
                                    stroke="currentColor">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                        d="M3 10h18M7 15h1m4 0h1m-7 4h12a3 3 0 003-3V8a3 3 0 00-3-3H6a3 3 0 00-3 3v8a3 3 0 003 3z" />
                                </svg>
                            </span>
                            <h2 class="text-xl font-medium text-gray-800 ml-3">Lygos Payment Gateway</h2>
                        </div>

                        <p class="text-gray-600 mb-6">Accept payments through the Lygos payment gateway platform.</p>

                        <?php $lygos_enabled = get_option('osna_lygos_enabled', false); ?>

                        <div
                            class="bg-<?php echo $lygos_enabled ? 'green' : 'gray'; ?>-100 border border-<?php echo $lygos_enabled ? 'green' : 'gray'; ?>-200 text-<?php echo $lygos_enabled ? 'green' : 'gray'; ?>-700 px-4 py-3 rounded mb-6">
                            <div class="flex">
                                <div class="py-1">
                                    <svg class="fill-current h-6 w-6 text-<?php echo $lygos_enabled ? 'green' : 'gray'; ?>-500 mr-4"
                                        xmlns="http://www.w3.org/2000/svg" viewBox="0 0 20 20">
                                        <?php if ($lygos_enabled): ?>
                                            <path d="M0 11l2-2 5 5L18 3l2 2L7 18z" />
                                        <?php else: ?>
                                            <path
                                                d="M10 8.586L2.929 1.515 1.515 2.929 8.586 10l-7.071 7.071 1.414 1.414L10 11.414l7.071 7.071 1.414-1.414L11.414 10l7.071-7.071-1.414-1.414L10 8.586z" />
                                        <?php endif; ?>
                                    </svg>
                                </div>
                                <div>
                                    <p>Status: <strong><?php echo $lygos_enabled ? 'Enabled' : 'Disabled'; ?></strong>
                                    </p>
                                </div>
                            </div>
                        </div>

                        <div class="flex items-center justify-between">
                            <a href="<?php echo admin_url('admin.php?page=osna-payment-gateways-settings&gateway=lygos'); ?>"
                                class="inline-flex items-center px-4 py-2 border border-transparent text-sm font-medium rounded-md text-white bg-blue-600 hover:bg-blue-700 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-blue-500">
                                Configure
                                <svg xmlns="http://www.w3.org/2000/svg" class="ml-2 h-4 w-4" fill="none"
                                    viewBox="0 0 2424 24" stroke="currentColor">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                        d="M9 5l7 7-7 7" />
                                </svg>
                            </a>
                        </div>
                    </div>
                </div>

                <!-- Stripe Gateway Card -->
                <div
                    class="bg-gray-50 rounded-xl overflow-hidden shadow-md transition-all duration-300 hover:shadow-lg">
                    <div class="p-6">
                        <div class="flex items-center mb-4">
                            <span class="w-10 h-10 flex items-center justify-center bg-blue-500 text-white rounded-lg">
                                <svg xmlns="http://www.w3.org/2000/svg" class="h-6 w-6" fill="none" viewBox="0 0 24 24"
                                    stroke="currentColor">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                        d="M3 10h18M7 15h1m4 0h1m-7 4h12a3 3 0 003-3V8a3 3 0 00-3-3H6a3 3 0 00-3 3v8a3 3 0 003 3z" />
                                </svg>
                            </span>
                            <h2 class="text-xl font-medium text-gray-800 ml-3">Stripe Payment Gateway</h2>
                        </div>

                        <p class="text-gray-600 mb-6">Accept payments through the Stripe payment gateway platform.</p>

                        <?php $stripe_enabled = get_option('osna_stripe_enabled', false); ?>

                        <div
                            class="bg-<?php echo $stripe_enabled ? 'green' : 'gray'; ?>-100 border border-<?php echo $stripe_enabled ? 'green' : 'gray'; ?>-200 text-<?php echo $stripe_enabled ? 'green' : 'gray'; ?>-700 px-4 py-3 rounded mb-6">
                            <div class="flex">
                                <div class="py-1">
                                    <svg class="fill-current h-6 w-6 text-<?php echo $stripe_enabled ? 'green' : 'gray'; ?>-500 mr-4"
                                        xmlns="http://www.w3.org/2000/svg" viewBox="0 0 20 20">
                                        <?php if ($stripe_enabled): ?>
                                            <path d="M0 11l2-2 5 5L18 3l2 2L7 18z" />
                                        <?php else: ?>
                                            <path
                                                d="M10 8.586L2.929 1.515 1.515 2.929 8.586 10l-7.071 7.071 1.414 1.414L10 11.414l7.071 7.071 1.414-1.414L11.414 10l7.071-7.071-1.414-1.414L10 8.586z" />
                                        <?php endif; ?>
                                    </svg>
                                </div>
                                <div>
                                    <p>Status: <strong><?php echo $stripe_enabled ? 'Enabled' : 'Disabled'; ?></strong>
                                    </p>
                                </div>
                            </div>
                        </div>

                        <div class="flex items-center justify-between">
                            <a href="<?php echo admin_url('admin.php?page=osna-payment-gateways-settings&gateway=stripe'); ?>"
                                class="inline-flex items-center px-4 py-2 border border-transparent text-sm font-medium rounded-md text-white bg-blue-600 hover:bg-blue-700 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-blue-500">
                                Configure
                                <svg xmlns="http://www.w3.org/2000/svg" class="ml-2 h-4 w-4" fill="none"
                                    viewBox="0 0 24 24" stroke="currentColor">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                        d="M9 5l7 7-7 7" />
                                </svg>
                            </a>
                        </div>
                    </div>
                </div>
            </div>

            <!-- WooCommerce Integration -->
            <div class="mt-8 p-6 bg-gray-50 rounded-xl">
                <h2 class="text-xl font-medium text-gray-800 mb-4">WooCommerce Integration</h2>

                <p class="text-gray-600 mb-4">If you have WooCommerce installed, these payment gateways will be
                    available as payment options during checkout.</p>

                <?php if (in_array('woocommerce/woocommerce.php', apply_filters('active_plugins', get_option('active_plugins')))): ?>
                    <div class="bg-green-100 border border-green-200 text-green-700 px-4 py-3 rounded mb-4">
                        <div class="flex">
                            <div class="py-1">
                                <svg class="fill-current h-6 w-6 text-green-500 mr-4" xmlns="http://www.w3.org/2000/svg"
                                    viewBox="0 0 20 20">
                                    <path d="M0 11l2-2 5 5L18 3l2 2L7 18z" />
                                </svg>
                            </div>
                            <div>
                                <p>WooCommerce is active. Payment gateways will be available in WooCommerce checkout.</p>
                            </div>
                        </div>
                    </div>

                    <a href="<?php echo admin_url('admin.php?page=wc-settings&tab=checkout'); ?>"
                        class="inline-flex items-center px-4 py-2 border border-transparent text-sm font-medium rounded-md text-white bg-blue-600 hover:bg-blue-700 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-blue-500">
                        WooCommerce Payment Settings
                        <svg xmlns="http://www.w3.org/2000/svg" class="ml-2 h-4 w-4" fill="none" viewBox="0 0 24 24"
                            stroke="currentColor">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5l7 7-7 7" />
                        </svg>
                    </a>
                <?php else: ?>
                    <div class="bg-yellow-100 border border-yellow-200 text-yellow-700 px-4 py-3 rounded mb-4">
                        <div class="flex">
                            <div class="py-1">
                                <svg class="fill-current h-6 w-6 text-yellow-500 mr-4" xmlns="http://www.w3.org/2000/svg"
                                    viewBox="0 0 20 20">
                                    <path
                                        d="M10 8.586L2.929 1.515 1.515 2.929 8.586 10l-7.071 7.071 1.414 1.414L10 11.414l7.071 7.071 1.414-1.414L11.414 10l7.071-7.071-1.414-1.414L10 8.586z" />
                                </svg>
                            </div>
                            <div>
                                <p>WooCommerce is not active. Install and activate WooCommerce to use these payment gateways
                                    for e-commerce.</p>
                            </div>
                        </div>
                    </div>

                    <a href="<?php echo admin_url('plugin-install.php?s=woocommerce&tab=search&type=term'); ?>"
                        class="inline-flex items-center px-4 py-2 border border-transparent text-sm font-medium rounded-md text-white bg-blue-600 hover:bg-blue-700 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-blue-500">
                        Install WooCommerce
                        <svg xmlns="http://www.w3.org/2000/svg" class="ml-2 h-4 w-4" fill="none" viewBox="0 0 24 24"
                            stroke="currentColor">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5l7 7-7 7" />
                        </svg>
                    </a>
                <?php endif; ?>
            </div>

            <!-- API Documentation -->
            <div class="mt-8 p-6 bg-gray-50 rounded-xl">
                <h2 class="text-xl font-medium text-gray-800 mb-4">API Documentation</h2>

                <p class="text-gray-600 mb-4">You can also integrate these payment gateways directly into your
                    application using our API endpoints:</p>

                <div class="bg-gray-800 rounded-lg p-4 overflow-auto">
                    <pre class="text-green-400 text-sm"><code>// Process a payment
                            POST <?php echo rest_url('osna/v1/process-payment'); ?>

                            // Request body
                            {
                            "amount": 99.99,
                            "shop_name": "Your Shop Name",
                            "message": "Payment for Order #123",
                            "success_url": "https://your-site.com/success",
                            "failure_url": "https://your-site.com/failure",
                            "order_id": "123"
                            }

                            // Headers
                            X-API-Key: your_api_key</code></pre>
                </div>

                <p class="text-gray-600 mt-4 mb-4">Get information about available payment gateways:</p>

                <div class="bg-gray-800 rounded-lg p-4 overflow-auto">
                    <pre class="text-green-400 text-sm"><code>// Get available payment gateways
                            GET <?php echo rest_url('osna/v1/payment-gateways'); ?></code></pre>
                </div>
            </div>
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