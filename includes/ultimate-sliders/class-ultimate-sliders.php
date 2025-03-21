<?php
/**
 * The Ultimate Sliders functionality.
 *
 * @since      1.0.0
 * @package    OSNA_Tools
 * @subpackage OSNA_Tools/includes/ultimate-sliders
 */

class Ultimate_Sliders
{

    /**
     * The post type instance.
     *
     * @since    1.0.0
     * @access   private
     * @var      Ultimate_Sliders_Post_Type    $post_type    The post type instance.
     */
    private $post_type;

    /**
     * Initialize the class.
     *
     * @since    1.0.0
     */
    public function init()
    {
        // Load dependencies
        require_once OSNA_TOOLS_PLUGIN_DIR . 'includes/ultimate-sliders/class-ultimate-sliders-post-type.php';
        require_once OSNA_TOOLS_PLUGIN_DIR . 'includes/ultimate-sliders/class-ultimate-sliders-graphql.php';

        // Initialize post type
        $this->post_type = new Ultimate_Sliders_Post_Type();

        // Register hooks
        add_action('init', array($this->post_type, 'register_post_type'));
        add_action('add_meta_boxes', array($this->post_type, 'register_meta_boxes'));
        add_action('save_post_ultimate_slider', array($this->post_type, 'save_meta_box_data'), 10, 2);
        add_action('admin_notices', array($this->post_type, 'display_admin_notices'));

        // Register REST API endpoints
        add_action('rest_api_init', array($this, 'register_rest_routes'));

        // Initialize GraphQL support if WPGraphQL is active
        if (class_exists('WPGraphQL')) {
            $graphql = new Ultimate_Sliders_GraphQL();
            $graphql->init();
        }
    }

    /**
     * Register REST API routes.
     *
     * @since    1.0.0
     */
    public function register_rest_routes()
    {
        register_rest_route('osna/v1', '/sliders', array(
            'methods' => 'GET',
            'callback' => array($this, 'get_sliders'),
            'permission_callback' => '__return_true',
        ));

        register_rest_route('osna/v1', '/sliders/(?P<id>\d+)', array(
            'methods' => 'GET',
            'callback' => array($this, 'get_slider'),
            'permission_callback' => '__return_true',
            'args' => array(
                'id' => array(
                    'validate_callback' => function ($param) {
                        return is_numeric($param);
                    }
                ),
            ),
        ));
    }

    /**
     * Get all sliders.
     *
     * @since    1.0.0
     * @param    WP_REST_Request    $request    The request.
     * @return   WP_REST_Response               The response.
     */
    public function get_sliders($request)
    {
        $args = array(
            'post_type' => 'ultimate_slider',
            'posts_per_page' => -1,
            'post_status' => 'publish',
        );

        $sliders = get_posts($args);
        $data = array();

        foreach ($sliders as $slider) {
            $data[] = $this->prepare_slider_for_response($slider);
        }

        return rest_ensure_response($data);
    }

    /**
     * Get a single slider.
     *
     * @since    1.0.0
     * @param    WP_REST_Request    $request    The request.
     * @return   WP_REST_Response               The response.
     */
    public function get_slider($request)
    {
        $slider_id = $request->get_param('id');
        $slider = get_post($slider_id);

        if (!$slider || $slider->post_type !== 'ultimate_slider') {
            return new WP_Error('not_found', __('Slider not found', 'osna-wp-tools'), array('status' => 404));
        }

        $data = $this->prepare_slider_for_response($slider);
        return rest_ensure_response($data);
    }

    /**
     * Prepare a slider for the REST response.
     *
     * @since    1.0.0
     * @param    WP_Post    $slider    The slider post.
     * @return   array                 The slider data.
     */
    private function prepare_slider_for_response($slider)
    {
        $autoplay = get_post_meta($slider->ID, '_ultimate_slider_autoplay', true);
        $autoplay_speed = get_post_meta($slider->ID, '_ultimate_slider_autoplay_speed', true);
        $transition_effect = get_post_meta($slider->ID, '_ultimate_slider_transition_effect', true);
        $show_dots = get_post_meta($slider->ID, '_ultimate_slider_show_dots', true);
        $show_arrows = get_post_meta($slider->ID, '_ultimate_slider_show_arrows', true);
        $continue_button_text = get_post_meta($slider->ID, '_ultimate_slider_continue_button_text', true);
        $slides = get_post_meta($slider->ID, '_ultimate_slider_slides', true);

        if (!is_array($slides)) {
            $slides = array();
        }

        // Process slides to include full image URLs
        foreach ($slides as &$slide) {
            if ($slide['media_type'] === 'image' && !empty($slide['image_id'])) {
                $image_url = wp_get_attachment_image_url($slide['image_id'], 'full');
                if ($image_url) {
                    $slide['image_url'] = $image_url;
                }
            }
        }

        return array(
            'id' => $slider->ID,
            'title' => $slider->post_title,
            'content' => apply_filters('the_content', $slider->post_content),
            'settings' => array(
                'autoplay' => $autoplay === '1',
                'autoplay_speed' => (int) $autoplay_speed,
                'transition_effect' => $transition_effect,
                'show_dots' => $show_dots === '1',
                'show_arrows' => $show_arrows === '1',
                'continue_button_text' => $continue_button_text,
            ),
            'slides' => $slides,
        );
    }
}
