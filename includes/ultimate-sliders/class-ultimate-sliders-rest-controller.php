<?php
/**
 * Custom REST API controller for Ultimate Sliders.
 * 
 * This controller overrides the default WordPress REST API permissions
 * to fix the 403 Forbidden errors in WordPress 6.7+
 *
 * @since      1.0.0
 * @package    OSNA_Tools
 * @subpackage OSNA_Tools/includes/ultimate-sliders
 */

class Ultimate_Sliders_REST_Controller extends WP_REST_Posts_Controller
{
    /**
     * Constructor.
     *
     * @since 1.0.0
     */
    public function __construct() {
        parent::__construct('ultimate_slider');
        $this->namespace = 'wp/v2';
        $this->rest_base = 'ultimate-sliders';
    }

    /**
     * Registers the routes for the objects of the controller.
     *
     * @since 1.0.0
     */
    public function register_routes() {
        register_rest_route(
            $this->namespace,
            '/' . $this->rest_base,
            array(
                array(
                    'methods'             => WP_REST_Server::READABLE,
                    'callback'            => array($this, 'get_items'),
                    'permission_callback' => array($this, 'get_items_permissions_check'),
                    'args'                => $this->get_collection_params(),
                ),
                array(
                    'methods'             => WP_REST_Server::CREATABLE,
                    'callback'            => array($this, 'create_item'),
                    'permission_callback' => array($this, 'create_item_permissions_check'),
                    'args'                => $this->get_endpoint_args_for_item_schema(WP_REST_Server::CREATABLE),
                ),
                'schema' => array($this, 'get_public_item_schema'),
            )
        );

        register_rest_route(
            $this->namespace,
            '/' . $this->rest_base . '/(?P<id>[\d]+)',
            array(
                array(
                    'methods'             => WP_REST_Server::READABLE,
                    'callback'            => array($this, 'get_item'),
                    'permission_callback' => array($this, 'get_item_permissions_check'),
                    'args'                => array(
                        'context' => $this->get_context_param(array('default' => 'view')),
                    ),
                ),
                array(
                    'methods'             => WP_REST_Server::EDITABLE,
                    'callback'            => array($this, 'update_item'),
                    'permission_callback' => array($this, 'update_item_permissions_check'),
                    'args'                => $this->get_endpoint_args_for_item_schema(WP_REST_Server::EDITABLE),
                ),
                array(
                    'methods'             => WP_REST_Server::DELETABLE,
                    'callback'            => array($this, 'delete_item'),
                    'permission_callback' => array($this, 'delete_item_permissions_check'),
                    'args'                => array(
                        'force' => array(
                            'type'        => 'boolean',
                            'default'     => false,
                            'description' => __('Whether to bypass trash and force deletion.'),
                        ),
                    ),
                ),
                'schema' => array($this, 'get_public_item_schema'),
            )
        );
    }

    /**
     * Check if a given request has access to create items.
     *
     * @since 1.0.0
     * @param WP_REST_Request $request Full data about the request.
     * @return WP_Error|bool
     */
    public function create_item_permissions_check($request) {
        // Skip nonce validation - we handle this in the main plugin
        return $this->check_basic_permissions();
    }

    /**
     * Basic permission check without nonce validation.
     *
     * @since 1.0.0
     * @return WP_Error|bool
     */
    private function check_basic_permissions() {
        if (!is_user_logged_in()) {
            return new WP_Error('rest_not_logged_in', __('You are not currently logged in.'), array('status' => 401));
        }

        if (!current_user_can('edit_posts')) {
            return new WP_Error('rest_cannot_create', __('Sorry, you are not allowed to create posts as this user.'), array('status' => 403));
        }

        return true;
    }

    /**
     * Check if a given request has access to update a specific item.
     *
     * @since 1.0.0
     * @param WP_REST_Request $request Full data about the request.
     * @return WP_Error|bool
     */
    public function update_item_permissions_check($request) {
        // Use basic permissions without nonce validation
        return $this->check_basic_permissions();
    }

    /**
     * Check if a given request has access to read items.
     *
     * @since 1.0.0
     * @param WP_REST_Request $request Full data about the request.
     * @return WP_Error|bool
     */
    public function get_items_permissions_check($request) {
        // Allow reading for everyone - sliders are meant to be public
        return true;
    }

    /**
     * Check if a given request has access to read a specific item.
     *
     * @since 1.0.0
     * @param WP_REST_Request $request Full data about the request.
     * @return WP_Error|bool
     */
    public function get_item_permissions_check($request) {
        // Allow reading for everyone - sliders are meant to be public
        return true;
    }

    /**
     * Check if a given request has access to delete a specific item.
     *
     * @since 1.0.0
     * @param WP_REST_Request $request Full data about the request.
     * @return WP_Error|bool
     */
    public function delete_item_permissions_check($request) {
        if (!is_user_logged_in()) {
            return new WP_Error('rest_not_logged_in', __('You are not currently logged in.'), array('status' => 401));
        }

        $post = $this->get_post($request['id']);
        if (is_wp_error($post)) {
            return $post;
        }

        if (!current_user_can('delete_post', $post->ID)) {
            return new WP_Error('rest_cannot_delete', __('Sorry, you are not allowed to delete this post.'), array('status' => 403));
        }

        return true;
    }

    /**
     * Get the post, if the ID is valid.
     *
     * @since 1.0.0
     * @param int $id Supplied ID.
     * @return WP_Post|WP_Error Post object if ID is valid, WP_Error otherwise.
     */
    protected function get_post($id) {
        $error = new WP_Error('rest_post_invalid_id', __('Invalid post ID.'), array('status' => 404));

        if ((int) $id <= 0) {
            return $error;
        }

        $post = get_post((int) $id);
        if (empty($post) || empty($post->ID) || 'ultimate_slider' !== $post->post_type) {
            return $error;
        }

        return $post;
    }
}