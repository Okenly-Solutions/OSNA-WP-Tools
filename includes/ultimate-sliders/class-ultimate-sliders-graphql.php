<?php
/**
 * The Ultimate Sliders GraphQL integration.
 *
 * @since      1.0.0
 * @package    OSNA_Tools
 * @subpackage OSNA_Tools/includes/ultimate-sliders
 */

class Ultimate_Sliders_GraphQL {

    /**
     * Initialize the GraphQL integration.
     *
     * @since    1.0.0
     */
    public function init() {
        // Register GraphQL types
        add_action('graphql_register_types', array($this, 'register_graphql_types'));
    }

    /**
     * Register GraphQL types.
     *
     * @since    1.0.0
     */
    public function register_graphql_types() {
        // Register SlideSettings type
        register_graphql_object_type('UltimateSlideSettings', [
            'description' => __('Settings for a slide in an Ultimate Slider', 'osna-wp-tools'),
            'fields' => [
                'mediaType' => [
                    'type' => 'String',
                    'description' => __('Type of media (image or video)', 'osna-wp-tools'),
                ],
                'imageId' => [
                    'type' => 'Int',
                    'description' => __('ID of the image attachment', 'osna-wp-tools'),
                ],
                'imageUrl' => [
                    'type' => 'String',
                    'description' => __('URL of the image', 'osna-wp-tools'),
                ],
                'videoUrl' => [
                    'type' => 'String',
                    'description' => __('URL of the video', 'osna-wp-tools'),
                ],
                'title' => [
                    'type' => 'String',
                    'description' => __('Title of the slide', 'osna-wp-tools'),
                ],
                'description' => [
                    'type' => 'String',
                    'description' => __('Description of the slide', 'osna-wp-tools'),
                ],
                'ctaText' => [
                    'type' => 'String',
                    'description' => __('Call to action button text', 'osna-wp-tools'),
                ],
                'ctaUrl' => [
                    'type' => 'String',
                    'description' => __('Call to action button URL', 'osna-wp-tools'),
                ],
                'backgroundColor' => [
                    'type' => 'String',
                    'description' => __('Background color of the slide', 'osna-wp-tools'),
                ],
                'textColor' => [
                    'type' => 'String',
                    'description' => __('Text color of the slide', 'osna-wp-tools'),
                ],
            ],
        ]);

        // Register SliderSettings type
        register_graphql_object_type('UltimateSliderSettings', [
            'description' => __('Settings for an Ultimate Slider', 'osna-wp-tools'),
            'fields' => [
                'autoplay' => [
                    'type' => 'Boolean',
                    'description' => __('Whether autoplay is enabled', 'osna-wp-tools'),
                ],
                'autoplaySpeed' => [
                    'type' => 'Int',
                    'description' => __('Autoplay speed in milliseconds', 'osna-wp-tools'),
                ],
                'transitionEffect' => [
                    'type' => 'String',
                    'description' => __('Transition effect between slides', 'osna-wp-tools'),
                ],
                'showDots' => [
                    'type' => 'Boolean',
                    'description' => __('Whether to show navigation dots', 'osna-wp-tools'),
                ],
                'showArrows' => [
                    'type' => 'Boolean',
                    'description' => __('Whether to show navigation arrows', 'osna-wp-tools'),
                ],
                'continueButtonText' => [
                    'type' => 'String',
                    'description' => __('Text for the continue button', 'osna-wp-tools'),
                ],
            ],
        ]);

        // Add fields to UltimateSlider type
        register_graphql_fields('UltimateSlider', [
            'settings' => [
                'type' => 'UltimateSliderSettings',
                'description' => __('Slider settings', 'osna-wp-tools'),
                'resolve' => function($slider) {
                    $autoplay = get_post_meta($slider->ID, '_ultimate_slider_autoplay', true);
                    $autoplay_speed = get_post_meta($slider->ID, '_ultimate_slider_autoplay_speed', true);
                    $transition_effect = get_post_meta($slider->ID, '_ultimate_slider_transition_effect', true);
                    $show_dots = get_post_meta($slider->ID, '_ultimate_slider_show_dots', true);
                    $show_arrows = get_post_meta($slider->ID, '_ultimate_slider_show_arrows', true);
                    $continue_button_text = get_post_meta($slider->ID, '_ultimate_slider_continue_button_text', true);

                    return [
                        'autoplay' => $autoplay === '1',
                        'autoplaySpeed' => (int) $autoplay_speed,
                        'transitionEffect' => $transition_effect,
                        'showDots' => $show_dots === '1',
                        'showArrows' => $show_arrows === '1',
                        'continueButtonText' => $continue_button_text,
                    ];
                },
            ],
            'slides' => [
                'type' => ['list_of' => 'UltimateSlideSettings'],
                'description' => __('Slides in the slider', 'osna-wp-tools'),
                'resolve' => function($slider) {
                    $slides = get_post_meta($slider->ID, '_ultimate_slider_slides', true);
                    
                    if (!is_array($slides)) {
                        return [];
                    }

                    foreach ($slides as &$slide) {
                        if ($slide['media_type'] === 'image' && !empty($slide['image_id'])) {
                            $image_url = wp_get_attachment_image_url($slide['image_id'], 'full');
                            if ($image_url) {
                                $slide['imageUrl'] = $image_url;
                            }
                        }

                        // Convert keys to camelCase for GraphQL
                        $slide['mediaType'] = $slide['media_type'];
                        $slide['imageId'] = (int) $slide['image_id'];
                        $slide['videoUrl'] = $slide['video_url'];
                        $slide['ctaText'] = $slide['cta_text'];
                        $slide['ctaUrl'] = $slide['cta_url'];
                        $slide['backgroundColor'] = $slide['background_color'];
                        $slide['textColor'] = $slide['text_color'];

                        // Remove snake_case keys
                        unset($slide['media_type']);
                        unset($slide['image_id']);
                        unset($slide['video_url']);
                        unset($slide['cta_text']);
                        unset($slide['cta_url']);
                        unset($slide['background_color']);
                        unset($slide['text_color']);
                    }

                    return $slides;
                },
            ],
        ]);
    }
}
