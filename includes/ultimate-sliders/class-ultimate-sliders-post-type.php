<?php
/**
 * The Ultimate Sliders post type functionality.
 *
 * @since      1.0.0
 * @package    OSNA_Tools
 * @subpackage OSNA_Tools/includes/ultimate-sliders
 */

class Ultimate_Sliders_Post_Type
{

    /**
     * Register the custom post type for Ultimate Sliders.
     *
     * @since    1.0.0
     */
    public function register_post_type()
    {
        $labels = array(
            'name' => _x('Ultimate Sliders', 'Post Type General Name', 'osna-wp-tools'),
            'singular_name' => _x('Slider', 'Post Type Singular Name', 'osna-wp-tools'),
            'menu_name' => __('Ultimate Sliders', 'osna-wp-tools'),
            'name_admin_bar' => __('Slider', 'osna-wp-tools'),
            'archives' => __('Slider Archives', 'osna-wp-tools'),
            'attributes' => __('Slider Attributes', 'osna-wp-tools'),
            'parent_item_colon' => __('Parent Slider:', 'osna-wp-tools'),
            'all_items' => __('All Sliders', 'osna-wp-tools'),
            'add_new_item' => __('Add New Slider', 'osna-wp-tools'),
            'add_new' => __('Add New', 'osna-wp-tools'),
            'new_item' => __('New Slider', 'osna-wp-tools'),
            'edit_item' => __('Edit Slider', 'osna-wp-tools'),
            'update_item' => __('Update Slider', 'osna-wp-tools'),
            'view_item' => __('View Slider', 'osna-wp-tools'),
            'view_items' => __('View Sliders', 'osna-wp-tools'),
            'search_items' => __('Search Slider', 'osna-wp-tools'),
            'not_found' => __('Not found', 'osna-wp-tools'),
            'not_found_in_trash' => __('Not found in Trash', 'osna-wp-tools'),
            'featured_image' => __('Featured Image', 'osna-wp-tools'),
            'set_featured_image' => __('Set featured image', 'osna-wp-tools'),
            'remove_featured_image' => __('Remove featured image', 'osna-wp-tools'),
            'use_featured_image' => __('Use as featured image', 'osna-wp-tools'),
            'insert_into_item' => __('Insert into slider', 'osna-wp-tools'),
            'uploaded_to_this_item' => __('Uploaded to this slider', 'osna-wp-tools'),
            'items_list' => __('Sliders list', 'osna-wp-tools'),
            'items_list_navigation' => __('Sliders list navigation', 'osna-wp-tools'),
            'filter_items_list' => __('Filter sliders list', 'osna-wp-tools'),
        );

        $args = array(
            'label' => __('Slider', 'osna-wp-tools'),
            'description' => __('Ultimate Sliders for advertisements and promotions', 'osna-wp-tools'),
            'labels' => $labels,
            'supports' => array('title', 'editor', 'thumbnail', 'custom-fields'),
            'hierarchical' => false,
            'public' => true,
            'show_ui' => true,
            'show_in_menu' => false, // Show as submenu under our custom menu
            'menu_position' => 5,
            'menu_icon' => 'dashicons-slides',
            'show_in_admin_bar' => true,
            'show_in_nav_menus' => true,
            'can_export' => true,
            'has_archive' => false,
            'exclude_from_search' => true,
            'publicly_queryable' => true,
            'capability_type' => 'post',
            'map_meta_cap' => true,
            'show_in_rest' => true,
            'rest_base' => 'ultimate-sliders', 
            'rest_controller_class' => 'Ultimate_Sliders_REST_Controller',
            'show_in_graphql' => true,
            'graphql_single_name' => 'ultimateSlider',
            'graphql_plural_name' => 'ultimateSliders',
        );

        register_post_type('ultimate_slider', $args);

        // Add REST API permission filters
        add_filter('rest_pre_dispatch', array($this, 'fix_rest_api_permissions'), 10, 3);
    }


    /**
     * Register meta boxes for the Ultimate Sliders post type.
     *
     * @since    1.0.0
     */
    public function register_meta_boxes()
    {
        add_meta_box(
            'ultimate_slider_settings',
            __('Slider Settings', 'osna-wp-tools'),
            array($this, 'render_slider_settings_meta_box'),
            'ultimate_slider',
            'normal',
            'high'
        );

        add_meta_box(
            'ultimate_slider_slides',
            __('Slider Slides', 'osna-wp-tools'),
            array($this, 'render_slider_slides_meta_box'),
            'ultimate_slider',
            'normal',
            'high'
        );
    }

    /**
     * Render the slider settings meta box.
     *
     * @since    1.0.0
     * @param    WP_Post    $post    The post object.
     */
    public function render_slider_settings_meta_box($post)
    {
        // Add nonce for security
        wp_nonce_field('ultimate_slider_settings_nonce', 'ultimate_slider_settings_nonce');

        // Get saved values
        $autoplay = get_post_meta($post->ID, '_ultimate_slider_autoplay', true);
        $autoplay_speed = get_post_meta($post->ID, '_ultimate_slider_autoplay_speed', true) ?: 5000;
        $transition_effect = get_post_meta($post->ID, '_ultimate_slider_transition_effect', true) ?: 'fade';
        $show_dots = get_post_meta($post->ID, '_ultimate_slider_show_dots', true);
        $show_arrows = get_post_meta($post->ID, '_ultimate_slider_show_arrows', true);
        $continue_button_text = get_post_meta($post->ID, '_ultimate_slider_continue_button_text', true) ?: 'Continue to Website';

        // Output the fields
        ?>
        <div class="osna-admin-panel">
            <div class="osna-field-group">
                <label for="ultimate_slider_autoplay">
                    <input type="checkbox" id="ultimate_slider_autoplay" name="ultimate_slider_autoplay" value="1" <?php checked($autoplay, '1'); ?>>
                    <?php _e('Enable Autoplay', 'osna-wp-tools'); ?>
                </label>
            </div>

            <div class="osna-field-group">
                <label for="ultimate_slider_autoplay_speed"><?php _e('Autoplay Speed (ms)', 'osna-wp-tools'); ?></label>
                <input type="number" id="ultimate_slider_autoplay_speed" name="ultimate_slider_autoplay_speed"
                    value="<?php echo esc_attr($autoplay_speed); ?>" min="1000" step="500">
            </div>

            <div class="osna-field-group">
                <label for="ultimate_slider_transition_effect"><?php _e('Transition Effect', 'osna-wp-tools'); ?></label>
                <select id="ultimate_slider_transition_effect" name="ultimate_slider_transition_effect">
                    <option value="fade" <?php selected($transition_effect, 'fade'); ?>><?php _e('Fade', 'osna-wp-tools'); ?>
                    </option>
                    <option value="slide" <?php selected($transition_effect, 'slide'); ?>><?php _e('Slide', 'osna-wp-tools'); ?>
                    </option>
                </select>
            </div>

            <div class="osna-field-group">
                <label for="ultimate_slider_show_dots">
                    <input type="checkbox" id="ultimate_slider_show_dots" name="ultimate_slider_show_dots" value="1" <?php checked($show_dots, '1'); ?>>
                    <?php _e('Show Navigation Dots', 'osna-wp-tools'); ?>
                </label>
            </div>

            <div class="osna-field-group">
                <label for="ultimate_slider_show_arrows">
                    <input type="checkbox" id="ultimate_slider_show_arrows" name="ultimate_slider_show_arrows" value="1" <?php checked($show_arrows, '1'); ?>>
                    <?php _e('Show Navigation Arrows', 'osna-wp-tools'); ?>
                </label>
            </div>

            <div class="osna-field-group">
                <label for="ultimate_slider_continue_button_text"><?php _e('Continue Button Text', 'osna-wp-tools'); ?></label>
                <input type="text" id="ultimate_slider_continue_button_text" name="ultimate_slider_continue_button_text"
                    value="<?php echo esc_attr($continue_button_text); ?>">
            </div>
        </div>
        <?php
    }

    /**
     * Render the slider slides meta box.
     *
     * @since    1.0.0
     * @param    WP_Post    $post    The post object.
     */
    public function render_slider_slides_meta_box($post)
    {
        // Add nonce for security
        wp_nonce_field('ultimate_slider_slides_nonce', 'ultimate_slider_slides_nonce');

        // Get saved slides
        $slides = get_post_meta($post->ID, '_ultimate_slider_slides', true);
        if (!is_array($slides)) {
            $slides = array();
        }

        // Output the fields
        ?>
        <div class="osna-admin-panel">
            <div id="ultimate-slider-slides">
                <?php
                if (!empty($slides)) {
                    foreach ($slides as $index => $slide) {
                        $this->render_slide_fields($index, $slide);
                    }
                } else {
                    // Add one empty slide by default
                    $this->render_slide_fields(0);
                }
                ?>
            </div>

            <button type="button" class="button button-primary"
                id="add-slide"><?php _e('Add Slide', 'osna-wp-tools'); ?></button>
        </div>

        <script type="text/template" id="slide-template">
                                            <?php $this->render_slide_fields('{{index}}'); ?>
                                        </script>
        <?php
    }

    /**
     * Render the fields for a single slide.
     *
     * @since    1.0.0
     * @param    int      $index    The slide index.
     * @param    array    $slide    The slide data.
     */
    private function render_slide_fields($index, $slide = array())
    {
        $media_type = isset($slide['media_type']) ? $slide['media_type'] : 'image';
        $image_id = isset($slide['image_id']) ? $slide['image_id'] : '';
        $video_url = isset($slide['video_url']) ? $slide['video_url'] : '';
        $title = isset($slide['title']) ? $slide['title'] : '';
        $description = isset($slide['description']) ? $slide['description'] : '';
        $cta_text = isset($slide['cta_text']) ? $slide['cta_text'] : '';
        $cta_url = isset($slide['cta_url']) ? $slide['cta_url'] : '';
        $background_color = isset($slide['background_color']) ? $slide['background_color'] : '#000000';
        $text_color = isset($slide['text_color']) ? $slide['text_color'] : '#ffffff';

        ?>
        <div class="slide-container" data-index="<?php echo esc_attr($index); ?>">
            <div class="slide-header">
                <h3><?php printf(__('Slide %s', 'osna-wp-tools'), intval($index) + 1); ?></h3>
                <button type="button" class="button remove-slide"><?php _e('Remove', 'osna-wp-tools'); ?></button>
            </div>

            <div class="slide-content">
                <div class="osna-field-group">
                    <label for="slides[<?php echo $index; ?>][media_type]"><?php _e('Media Type', 'osna-wp-tools'); ?></label>
                    <select id="slides[<?php echo $index; ?>][media_type]" name="slides[<?php echo $index; ?>][media_type]"
                        class="media-type-select">
                        <option value="image" <?php selected($media_type, 'image'); ?>><?php _e('Image', 'osna-wp-tools'); ?>
                        </option>
                        <option value="video" <?php selected($media_type, 'video'); ?>><?php _e('Video', 'osna-wp-tools'); ?>
                        </option>
                    </select>
                </div>

                <div class="osna-field-group media-field image-field" <?php echo $media_type !== 'image' ? 'style="display:none;"' : ''; ?>>
                    <label for="slides[<?php echo $index; ?>][image_id]"><?php _e('Image', 'osna-wp-tools'); ?></label>
                    <div class="image-preview-container">
                        <div class="image-preview">
                            <?php if ($image_id): ?>
                                <?php echo wp_get_attachment_image($image_id, 'thumbnail'); ?>
                            <?php endif; ?>
                        </div>
                        <input type="hidden" id="slides[<?php echo $index; ?>][image_id]"
                            name="slides[<?php echo $index; ?>][image_id]" value="<?php echo esc_attr($image_id); ?>">
                        <button type="button" class="button upload-image"><?php _e('Upload Image', 'osna-wp-tools'); ?></button>
                        <button type="button" class="button remove-image" <?php echo !$image_id ? 'style="display:none;"' : ''; ?>><?php _e('Remove Image', 'osna-wp-tools'); ?></button>
                    </div>
                </div>

                <div class="osna-field-group media-field video-field" <?php echo $media_type !== 'video' ? 'style="display:none;"' : ''; ?>>
                    <label for="slides[<?php echo $index; ?>][video_url]"><?php _e('Video URL', 'osna-wp-tools'); ?></label>
                    <input type="url" id="slides[<?php echo $index; ?>][video_url]"
                        name="slides[<?php echo $index; ?>][video_url]" value="<?php echo esc_attr($video_url); ?>"
                        placeholder="https://example.com/video.mp4">
                    <p class="description">
                        <?php _e('Enter the URL of the video file (MP4, WebM, etc.) or YouTube/Vimeo embed URL.', 'osna-wp-tools'); ?>
                    </p>
                </div>

                <div class="osna-field-group">
                    <label for="slides[<?php echo $index; ?>][title]"><?php _e('Title', 'osna-wp-tools'); ?></label>
                    <input type="text" id="slides[<?php echo $index; ?>][title]" name="slides[<?php echo $index; ?>][title]"
                        value="<?php echo esc_attr($title); ?>">
                </div>

                <div class="osna-field-group">
                    <label for="slides[<?php echo $index; ?>][description]"><?php _e('Description', 'osna-wp-tools'); ?></label>
                    <textarea id="slides[<?php echo $index; ?>][description]" name="slides[<?php echo $index; ?>][description]"
                        rows="3"><?php echo esc_textarea($description); ?></textarea>
                </div>

                <div class="osna-field-group">
                    <label
                        for="slides[<?php echo $index; ?>][cta_text]"><?php _e('Call to Action Text', 'osna-wp-tools'); ?></label>
                    <input type="text" id="slides[<?php echo $index; ?>][cta_text]"
                        name="slides[<?php echo $index; ?>][cta_text]" value="<?php echo esc_attr($cta_text); ?>">
                </div>

                <div class="osna-field-group">
                    <label
                        for="slides[<?php echo $index; ?>][cta_url]"><?php _e('Call to Action URL', 'osna-wp-tools'); ?></label>
                    <input type="url" id="slides[<?php echo $index; ?>][cta_url]" name="slides[<?php echo $index; ?>][cta_url]"
                        value="<?php echo esc_attr($cta_url); ?>">
                </div>

                <div class="osna-field-group">
                    <label
                        for="slides[<?php echo $index; ?>][background_color]"><?php _e('Background Color', 'osna-wp-tools'); ?></label>
                    <input type="color" id="slides[<?php echo $index; ?>][background_color]"
                        name="slides[<?php echo $index; ?>][background_color]"
                        value="<?php echo esc_attr($background_color); ?>">
                </div>

                <div class="osna-field-group">
                    <label for="slides[<?php echo $index; ?>][text_color]"><?php _e('Text Color', 'osna-wp-tools'); ?></label>
                    <input type="color" id="slides[<?php echo $index; ?>][text_color]"
                        name="slides[<?php echo $index; ?>][text_color]" value="<?php echo esc_attr($text_color); ?>">
                </div>
            </div>
        </div>
        <?php
    }

    /**
     * Save the meta box data.
     *
     * @since    1.0.0
     * @param    int       $post_id    The post ID.
     * @param    WP_Post   $post       The post object.
     */
    public function save_meta_box_data($post_id, $post)
    {
        // If this is an autosave, our form has not been submitted, so we don't want to do anything
        if (defined('DOING_AUTOSAVE') && DOING_AUTOSAVE) {
            return;
        }

        // Check the user's permissions
        if (!current_user_can('edit_post', $post_id)) {
            return;
        }

        // Check if at least one of our nonces is set and valid
        $settings_nonce_valid = isset($_POST['ultimate_slider_settings_nonce']) &&
            wp_verify_nonce($_POST['ultimate_slider_settings_nonce'], 'ultimate_slider_settings_nonce');

        $slides_nonce_valid = isset($_POST['ultimate_slider_slides_nonce']) &&
            wp_verify_nonce($_POST['ultimate_slider_slides_nonce'], 'ultimate_slider_slides_nonce');

        // At least one nonce must be valid to proceed
        if (!$settings_nonce_valid && !$slides_nonce_valid) {
            return;
        }

        // Process settings if settings nonce is valid
        if ($settings_nonce_valid) {
            // Save slider settings
            $autoplay = isset($_POST['ultimate_slider_autoplay']) ? '1' : '0';
            $autoplay_speed = isset($_POST['ultimate_slider_autoplay_speed']) ? absint($_POST['ultimate_slider_autoplay_speed']) : 5000;
            $transition_effect = isset($_POST['ultimate_slider_transition_effect']) ? sanitize_text_field($_POST['ultimate_slider_transition_effect']) : 'fade';
            $show_dots = isset($_POST['ultimate_slider_show_dots']) ? '1' : '0';
            $show_arrows = isset($_POST['ultimate_slider_show_arrows']) ? '1' : '0';
            $continue_button_text = isset($_POST['ultimate_slider_continue_button_text']) ? sanitize_text_field($_POST['ultimate_slider_continue_button_text']) : 'Continue to Website';

            update_post_meta($post_id, '_ultimate_slider_autoplay', $autoplay);
            update_post_meta($post_id, '_ultimate_slider_autoplay_speed', $autoplay_speed);
            update_post_meta($post_id, '_ultimate_slider_transition_effect', $transition_effect);
            update_post_meta($post_id, '_ultimate_slider_show_dots', $show_dots);
            update_post_meta($post_id, '_ultimate_slider_show_arrows', $show_arrows);
            update_post_meta($post_id, '_ultimate_slider_continue_button_text', $continue_button_text);
        }

        // Process slides if slides nonce is valid
        if ($slides_nonce_valid) {
            // Save slides
            if (isset($_POST['slides']) && is_array($_POST['slides'])) {
                $slides = array();

                foreach ($_POST['slides'] as $slide) {
                    if (is_array($slide)) {
                        $slides[] = array(
                            'media_type' => isset($slide['media_type']) ? sanitize_text_field($slide['media_type']) : 'image',
                            'image_id' => isset($slide['image_id']) ? absint($slide['image_id']) : '',
                            'video_url' => isset($slide['video_url']) ? esc_url_raw($slide['video_url']) : '',
                            'title' => isset($slide['title']) ? sanitize_text_field($slide['title']) : '',
                            'description' => isset($slide['description']) ? wp_kses_post($slide['description']) : '',
                            'cta_text' => isset($slide['cta_text']) ? sanitize_text_field($slide['cta_text']) : '',
                            'cta_url' => isset($slide['cta_url']) ? esc_url_raw($slide['cta_url']) : '',
                            'background_color' => isset($slide['background_color']) ? sanitize_hex_color($slide['background_color']) : '#000000',
                            'text_color' => isset($slide['text_color']) ? sanitize_hex_color($slide['text_color']) : '#ffffff',
                        );
                    }
                }

                $result = update_post_meta($post_id, '_ultimate_slider_slides', $slides);
                
                // Add success notification
                if ($result !== false) {
                    $this->log_error('Slider saved successfully', 'success');
                }
            } else {
                // Clear slides if no slides data provided
                update_post_meta($post_id, '_ultimate_slider_slides', array());
            }
        }
    }

    /**
     * Log errors and provide admin feedback.
     *
     * @since    1.0.0
     * @param    string    $message    The error message.
     * @param    string    $level      The error level.
     */
    private function log_error($message, $level = 'error')
    {
        // Log to WordPress error log
        error_log('OSNA Ultimate Slider: ' . $message);

        // Store the error as a transient to display to the admin
        $errors = get_transient('ultimate_slider_errors') ?: array();
        $errors[] = array(
            'message' => $message,
            'level' => $level,
            'time' => current_time('mysql')
        );
        set_transient('ultimate_slider_errors', $errors, HOUR_IN_SECONDS);
    }


    // Add this function to the class:

    /**
     * Display admin notices.
     *
     * @since    1.0.0
     */
    public function display_admin_notices()
    {
        $screen = get_current_screen();
        if (!$screen || $screen->post_type !== 'ultimate_slider') {
            return;
        }

        $errors = get_transient('ultimate_slider_errors');
        if (!empty($errors) && is_array($errors)) {
            foreach ($errors as $error) {
                if (isset($error['message']) && isset($error['level'])) {
                    $class = 'notice notice-' . esc_attr($error['level']) . ' is-dismissible';
                    $message = esc_html($error['message']);
                    ?>
                    <div class="<?php echo esc_attr($class); ?>">
                        <p><?php echo $message; ?></p>
                    </div>
                    <?php
                }
            }
            // Clear errors after displaying
            delete_transient('ultimate_slider_errors');
        }
    }

    /**
     * Fix REST API permissions for ultimate_slider post type.
     *
     * @since    1.0.0
     * @param    mixed    $result
     * @param    object   $server
     * @param    object   $request
     * @return   mixed
     */
    public function fix_rest_api_permissions($result, $server, $request)
    {
        $route = $request->get_route();
        
        // Only handle ultimate-sliders routes
        if (strpos($route, '/wp/v2/ultimate-sliders') === false) {
            return $result;
        }

        // If user is logged in and has edit_posts capability, allow access
        if (is_user_logged_in() && current_user_can('edit_posts')) {
            // Return null to let the request continue
            return null;
        }

        return $result;
    }

}
