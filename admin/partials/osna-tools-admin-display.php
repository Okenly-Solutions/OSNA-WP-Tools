<?php
/**
 * Provide a admin area view for the plugin
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
                <h1 class="text-3xl font-semibold text-white">OSNA WP Tools</h1>
                <span
                    class="px-3 py-1 text-sm bg-white bg-opacity-20 rounded-full text-white">v<?php echo OSNA_TOOLS_VERSION; ?></span>
            </div>
            <p class="text-blue-100 mt-2">A collection of powerful tools for WordPress</p>
        </div>

        <!-- Content -->
        <div class="p-8">
            <div class="grid grid-cols-1 md:grid-cols-2 gap-8">
                <!-- Ultimate Sliders Card -->
                <div
                    class="bg-gray-50 rounded-xl overflow-hidden shadow-md transition-all duration-300 hover:shadow-lg">
                    <div class="p-6">
                        <div class="flex items-center mb-4">
                            <span class="w-10 h-10 flex items-center justify-center bg-blue-500 text-white rounded-lg">
                                <svg xmlns="http://www.w3.org/2000/svg" class="h-6 w-6" fill="none" viewBox="0 0 24 24"
                                    stroke="currentColor">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                        d="M4 6h16M4 12h16m-7 6h7" />
                                </svg>
                            </span>
                            <h2 class="text-xl font-medium text-gray-800 ml-3">Ultimate Sliders</h2>
                        </div>

                        <p class="text-gray-600 mb-6">Create beautiful, responsive sliders for your splash pages with
                            support for images, videos, and call-to-action buttons.</p>

                        <div class="flex items-center justify-between">
                            <a href="<?php echo admin_url('edit.php?post_type=ultimate_slider'); ?>"
                                class="inline-flex items-center px-4 py-2 border border-transparent text-sm font-medium rounded-md text-white bg-blue-600 hover:bg-blue-700 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-blue-500">
                                Manage Sliders
                                <svg xmlns="http://www.w3.org/2000/svg" class="ml-2 h-4 w-4" fill="none"
                                    viewBox="0 0 24 24" stroke="currentColor">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                        d="M9 5l7 7-7 7" />
                                </svg>
                            </a>

                            <a href="<?php echo admin_url('post-new.php?post_type=ultimate_slider'); ?>"
                                class="text-sm text-blue-600 hover:text-blue-800">
                                Create New
                            </a>
                        </div>
                    </div>
                </div>
                <!-- Payment Gateways Card -->
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
                            <h2 class="text-xl font-medium text-gray-800 ml-3">Payment Gateways</h2>
                        </div>

                        <p class="text-gray-600 mb-6">Integrate and manage multiple payment gateways for your
                            WooCommerce store, including Lygos and Stripe integration.</p>

                        <div class="flex items-center justify-between">
                            <a href="<?php echo admin_url('admin.php?page=osna-payment-gateways'); ?>"
                                class="inline-flex items-center px-4 py-2 border border-transparent text-sm font-medium rounded-md text-white bg-blue-600 hover:bg-blue-700 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-blue-500">
                                Manage Gateways
                                <svg xmlns="http://www.w3.org/2000/svg" class="ml-2 h-4 w-4" fill="none"
                                    viewBox="0 0 24 24" stroke="currentColor">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                        d="M9 5l7 7-7 7" />
                                </svg>
                            </a>

                            <a href="<?php echo admin_url('admin.php?page=osna-payment-gateways-settings'); ?>"
                                class="text-sm text-blue-600 hover:text-blue-800">
                                Settings
                            </a>
                        </div>
                    </div>
                </div>

                <!-- Coming Soon Card -->
                <div
                    class="bg-gray-50 rounded-xl overflow-hidden shadow-md transition-all duration-300 hover:shadow-lg opacity-75">
                    <div class="p-6">
                        <div class="flex items-center mb-4">
                            <span class="w-10 h-10 flex items-center justify-center bg-gray-400 text-white rounded-lg">
                                <svg xmlns="http://www.w3.org/2000/svg" class="h-6 w-6" fill="none" viewBox="0 0 24 24"
                                    stroke="currentColor">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                        d="M12 6v6m0 0v6m0-6h6m-6 0H6" />
                                </svg>
                            </span>
                            <h2 class="text-xl font-medium text-gray-800 ml-3">More Tools Coming Soon</h2>
                        </div>

                        <p class="text-gray-600 mb-6">We're working on more tools to enhance your WordPress site. Stay
                            tuned for updates!</p>

                        <div
                            class="inline-flex items-center px-4 py-2 border border-transparent text-sm font-medium rounded-md text-gray-600 bg-gray-200 cursor-not-allowed">
                            Coming Soon
                        </div>
                    </div>
                </div>
            </div>

            <!-- GraphQL Information -->
            <div class="mt-8 p-6 bg-gray-50 rounded-xl">
                <h2 class="text-xl font-medium text-gray-800 mb-4">GraphQL Integration</h2>

                <p class="text-gray-600 mb-4">Ultimate Sliders are available through GraphQL if you have the WPGraphQL
                    plugin installed. This allows you to easily fetch slider data for your Next.js applications.</p>

                <div class="bg-gray-800 rounded-lg p-4 overflow-auto">
                    <pre class="text-green-400 text-sm"><code>query GetSliders {
                            ultimateSliders {
                            nodes {
                            id
                            title
                            settings {
                            autoplay
                            autoplaySpeed
                            transitionEffect
                            showDots
                            showArrows
                            continueButtonText
                            }
                            slides {
                            mediaType
                            imageUrl
                            videoUrl
                            title
                            description
                            ctaText
                            ctaUrl
                            backgroundColor
                            textColor
                            }
                            }
                            }
                            }</code></pre>
                </div>
            </div>

            <!-- REST API Information -->
            <div class="mt-8 p-6 bg-gray-50 rounded-xl">
                <h2 class="text-xl font-medium text-gray-800 mb-4">REST API Endpoints</h2>

                <p class="text-gray-600 mb-4">Ultimate Sliders are also available through the WordPress REST API:</p>

                <ul class="list-disc pl-5 text-gray-600 mb-4">
                    <li><code class="bg-gray-100 px-2 py-1 rounded text-sm">/wp-json/osna/v1/sliders</code> - Get all
                        sliders</li>
                    <li><code class="bg-gray-100 px-2 py-1 rounded text-sm">/wp-json/osna/v1/sliders/{id}</code> - Get a
                        specific slider by ID</li>
                </ul>
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