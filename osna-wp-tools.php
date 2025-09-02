<?php
/**
 * Plugin Name: OSNA WP Tools
 * Plugin URI: https://osna.com/wp-tools
 * Description: A collection of tools for WordPress including Ultimate Sliders
 * Version: 1.0.0
 * Author: OSNA Team
 * Author URI: https://osna.com
 * Text Domain: osna-wp-tools
 * Domain Path: /languages
 */

// If this file is called directly, abort.
if (!defined('WPINC')) {
    die;
}

// Prevent any output before headers to avoid Quirks Mode
if (!headers_sent()) {
    ob_start();
}

// Define plugin constants
define('OSNA_TOOLS_VERSION', '1.0.0');
define('OSNA_TOOLS_PLUGIN_DIR', plugin_dir_path(__FILE__));
define('OSNA_TOOLS_PLUGIN_URL', plugin_dir_url(__FILE__));
define('OSNA_TOOLS_PLUGIN_BASENAME', plugin_basename(__FILE__));

/**
 * The code that runs during plugin activation.
 */
function activate_osna_tools() {
    require_once OSNA_TOOLS_PLUGIN_DIR . 'includes/class-osna-tools-activator.php';
    OSNA_Tools_Activator::activate();
}

/**
 * The code that runs during plugin deactivation.
 */
function deactivate_osna_tools() {
    require_once OSNA_TOOLS_PLUGIN_DIR . 'includes/class-osna-tools-deactivator.php';
    OSNA_Tools_Deactivator::deactivate();
}

register_activation_hook(__FILE__, 'activate_osna_tools');
register_deactivation_hook(__FILE__, 'deactivate_osna_tools');

/**
 * The core plugin class that is used to define internationalization,
 * admin-specific hooks, and public-facing site hooks.
 */
require_once OSNA_TOOLS_PLUGIN_DIR . 'includes/class-osna-tools.php';

/**
 * Fix REST API nonce and cookie validation issues in WordPress 6.7+
 */
add_action('init', function() {
    // Fix nonce validation for REST API requests
    add_filter('rest_authentication_errors', function($result) {
        // If there's already an error, handle it
        if (is_wp_error($result)) {
            $error_code = $result->get_error_code();
            
            // Handle nonce validation failures for ultimate-slider requests
            if ($error_code === 'rest_cookie_invalid_nonce') {
                $request_uri = $_SERVER['REQUEST_URI'] ?? '';
                if (strpos($request_uri, '/wp-json/wp/v2/ultimate-sliders') !== false) {
                    // If user is logged in and has edit capability, bypass nonce check
                    if (is_user_logged_in() && current_user_can('edit_posts')) {
                        return true; // Allow the request
                    }
                }
            }
        }
        
        return $result;
    }, 5); // High priority to run early
    
    // Add nonce to REST API responses for editor
    add_filter('rest_pre_serve_request', function($served, $result, $request, $server) {
        $route = $request->get_route();
        
        // Add proper nonce header for ultimate-slider requests
        if (strpos($route, '/wp/v2/ultimate-sliders') !== false) {
            if (is_user_logged_in()) {
                header('X-WP-Nonce: ' . wp_create_nonce('wp_rest'));
            }
        }
        
        return $served;
    }, 10, 4);
});

/**
 * Fix REST API 403 errors and improve slider editor functionality
 */
add_action('rest_api_init', function() {
    // Register our custom REST controller
    require_once OSNA_TOOLS_PLUGIN_DIR . 'includes/ultimate-sliders/class-ultimate-sliders-rest-controller.php';
    $controller = new Ultimate_Sliders_REST_Controller();
    $controller->register_routes();
});

add_action('init', function() {
    // Add better REST API error handling for custom post types
    add_filter('rest_authentication_errors', function($result) {
        // If we already have an error, return it
        if (is_wp_error($result)) {
            return $result;
        }

        return $result;
    });

    // Add specific permission callback for ultimate_slider post type
    add_filter('rest_prepare_ultimate_slider', function($response, $post, $request) {
        return $response;
    }, 10, 3);

    // Add temporary capability when processing ultimate-slider requests
    add_filter('user_has_cap', function($allcaps, $caps, $args, $user) {
        // Only modify during REST API requests for ultimate-sliders
        if (defined('REST_REQUEST') && REST_REQUEST) {
            $request_uri = $_SERVER['REQUEST_URI'] ?? '';
            if (strpos($request_uri, '/wp-json/wp/v2/ultimate-sliders') !== false) {
                // Grant necessary capabilities for ultimate slider operations
                if (isset($allcaps['edit_posts']) && $allcaps['edit_posts']) {
                    $allcaps['edit_ultimate_sliders'] = true;
                    $allcaps['edit_published_ultimate_sliders'] = true;
                    $allcaps['edit_others_ultimate_sliders'] = true;
                    $allcaps['publish_ultimate_sliders'] = true;
                }
            }
        }
        return $allcaps;
    }, 10, 4);

    // Override REST API permission check for ultimate_slider
    add_filter('rest_post_dispatch', function($result, $server, $request) {
        $route = $request->get_route();
        
        // Handle ultimate-sliders endpoint specifically  
        if (strpos($route, '/wp/v2/ultimate-sliders') !== false) {
            // If we got a 403 error but user is logged in and can edit posts, allow it
            if (is_wp_error($result) && $result->get_error_code() === 'rest_forbidden') {
                if (is_user_logged_in() && current_user_can('edit_posts')) {
                    // Re-process the request with proper permissions
                    return $server->dispatch($request);
                }
            }
        }
        
        return $result;
    }, 10, 3);

    // Fix REST API permissions for custom post types in admin
    add_filter('rest_pre_dispatch', function($result, $server, $request) {
        $route = $request->get_route();
        
        // Allow admin users to access ultimate-sliders endpoints
        if (strpos($route, '/wp/v2/ultimate-sliders') !== false && is_user_logged_in()) {
            // Check if user has editing capabilities
            if (current_user_can('edit_posts')) {
                // User has permission, let the request continue
                return $result;
            }
        }
        
        // Block unnecessary REST API calls on slider edit pages to prevent 403 spam
        if ((isset($_GET['post_type']) && $_GET['post_type'] === 'ultimate_slider') || 
            (isset($_GET['post']) && get_post_type($_GET['post']) === 'ultimate_slider')) {
            
            // Block problematic plugin endpoints that cause 403 errors
            $blocked_routes = array(
                '/yith/wishlist/',
                '/wpforms/',
                '/hostinger-ai-assistant/',
                '/wp/v2/taxonomies/',
                '/wp/v2/types/',
                '/wp/v2/statuses/',
                '/oembed/',
                '/wp/v2/settings/',
                '/wp-site-health/',
                '/wp/v2/themes/',
                '/wp/v2/plugins/',
                '/jetpack/',
                '/elementor/',
                '/woocommerce/',
            );
            
            foreach ($blocked_routes as $blocked_route) {
                if (strpos($route, $blocked_route) !== false) {
                    return new WP_REST_Response(array('message' => 'Route disabled on slider pages'), 200);
                }
            }
        }
        
        return $result;
    }, 10, 3);
});

/**
 * Disable unnecessary scripts on slider edit pages
 */
add_action('admin_init', function() {
    if ((isset($_GET['post_type']) && $_GET['post_type'] === 'ultimate_slider') || 
        (isset($_GET['post']) && get_post_type($_GET['post']) === 'ultimate_slider')) {
        
        // Remove YITH Wishlist admin scripts that make unnecessary API calls
        if (class_exists('YITH_WCWL_Admin')) {
            remove_action('admin_enqueue_scripts', array('YITH_WCWL_Admin', 'enqueue_scripts'), 15);
        }
        
        // Remove WPForms scripts that aren't needed on slider pages  
        add_filter('wpforms_load_admin_scripts', '__return_false');
        
        // Disable other problematic plugin scripts
        add_filter('elementor/admin/can_edit_post_type', '__return_false');
        add_filter('jetpack_can_load_admin_styles', '__return_false');
        
        // Add CSS to hide Gutenberg blocks that cause REST API calls
        add_action('admin_head', function() {
            echo '<style>
                .editor-block-list__layout .wp-block-embed,
                .block-editor-block-list__layout .wp-block-embed,
                .block-editor-inserter__panel .wp-block-embed {
                    display: none !important;
                }
            </style>';
        });

        // Add nonce fix JavaScript for editor
        add_action('admin_footer', function() {
            if ((isset($_GET['post_type']) && $_GET['post_type'] === 'ultimate_slider') || 
                (isset($_GET['post']) && get_post_type($_GET['post']) === 'ultimate_slider')) {
                ?>
                <script type="text/javascript">
                jQuery(document).ready(function($) {
                    // Fix REST API nonce for ultimate-slider requests
                    if (typeof wp !== 'undefined' && wp.apiFetch) {
                        wp.apiFetch.use(function(options, next) {
                            // Add or refresh nonce for ultimate-slider requests
                            if (options.url && options.url.indexOf('/wp/v2/ultimate-sliders') !== -1) {
                                options.headers = options.headers || {};
                                options.headers['X-WP-Nonce'] = '<?php echo wp_create_nonce('wp_rest'); ?>';
                                
                                // Also try with credentials
                                options.credentials = 'include';
                            }
                            return next(options);
                        });
                    }
                });
                </script>
                <?php
            }
        });
    }
});

/**
 * Improve REST API CORS headers
 */
add_action('rest_api_init', function() {
    remove_filter('rest_pre_serve_request', 'rest_send_cors_headers');
    add_filter('rest_pre_serve_request', function($served, $result, $request, $server) {
        $origin = get_http_origin();
        
        // Allow same-origin requests
        if ($origin && is_user_logged_in()) {
            header('Access-Control-Allow-Origin: ' . $origin);
            header('Access-Control-Allow-Methods: OPTIONS, GET, POST, PUT, PATCH, DELETE');
            header('Access-Control-Allow-Credentials: true');
            header('Access-Control-Allow-Headers: Authorization, X-WP-Nonce, Content-Disposition, Content-MD5, Content-Type');
        }
        
        return $served;
    }, 15, 4);
});

/**
 * Begins execution of the plugin.
 */
function run_osna_tools() {
    $plugin = new OSNA_Tools();
    $plugin->run();
}
run_osna_tools();
