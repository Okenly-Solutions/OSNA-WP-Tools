<?php
/**
 * WooCommerce integration for Product Custom Fields.
 *
 * @since      1.0.0
 * @package    OSNA_Tools
 * @subpackage OSNA_Tools/includes/product-custom-fields
 */

// Make sure WooCommerce is active
if (!class_exists('WC_Product')) {
    return;
}

class Product_Custom_Fields_WooCommerce
{

    /**
     * The parent Product_Custom_Fields instance.
     *
     * @since    1.0.0
     * @access   private
     * @var      Product_Custom_Fields    $parent    The parent instance.
     */
    private $parent;

    /**
     * Initialize the class.
     *
     * @since    1.0.0
     */
    public function init()
    {
        $this->parent = new Product_Custom_Fields();

        // Add custom fields to product edit screen
        add_action('woocommerce_product_options_general_product_data', array($this, 'add_custom_fields_to_general_tab'));
        add_action('woocommerce_product_options_inventory_product_data', array($this, 'add_custom_fields_to_inventory_tab'));
        add_action('woocommerce_product_options_shipping', array($this, 'add_custom_fields_to_shipping_tab'));
        add_action('woocommerce_product_options_advanced', array($this, 'add_custom_fields_to_advanced_tab'));

        // Add custom fields to product variations
        add_action('woocommerce_product_after_variable_attributes', array($this, 'add_custom_fields_to_variations'), 10, 3);

        // Save custom field data
        add_action('woocommerce_process_product_meta', array($this, 'save_custom_fields'));
        add_action('woocommerce_save_product_variation', array($this, 'save_variation_custom_fields'), 10, 2);

        // Display custom fields on product page
        add_action('woocommerce_product_meta_end', array($this, 'display_custom_fields_on_product_page'));

        // Add custom fields to order items (when product is added to cart)
        add_filter('woocommerce_add_cart_item_data', array($this, 'add_custom_fields_to_cart_item'), 10, 3);

        // Display custom fields in cart and checkout
        add_filter('woocommerce_get_item_data', array($this, 'display_custom_fields_in_cart'), 10, 2);

        // Add custom fields to order
        add_action('woocommerce_checkout_create_order_line_item', array($this, 'add_custom_fields_to_order_items'), 10, 4);

        // Add custom fields to emails
        add_action('woocommerce_email_after_order_table', array($this, 'display_custom_fields_in_emails'), 10, 4);
    }

    /**
     * Add custom fields to the product's general tab.
     *
     * @since    1.0.0
     */
    public function add_custom_fields_to_general_tab()
    {
        global $post;

        echo '<div class="options_group">';
        echo '<h4 style="padding-left: 12px;">' . __('OSNA Custom Fields', 'osna-wp-tools') . '</h4>';

        $fields = $this->parent->get_fields();
        $product_id = $post->ID;
        $product = wc_get_product($product_id);
        $product_type = $product->get_type();

        foreach ($fields as $field) {
            // Skip if not meant for this product type
            if (!empty($field['product_types']) && !in_array($product_type, $field['product_types'])) {
                continue;
            }

            $meta_key = '_osna_product_field_' . $field['field_name'];
            $value = get_post_meta($product_id, $meta_key, true);

            // Use default value if not set
            if ($value === '' && $field['field_default'] !== '') {
                $value = $field['field_default'];
            }

            // Different field types require different handling
            switch ($field['field_type']) {
                case 'text':
                    woocommerce_wp_text_input(array(
                        'id' => $meta_key,
                        'label' => $field['field_label'],
                        'desc_tip' => true,
                        'description' => $field['field_description'],
                        'value' => $value
                    ));
                    break;

                case 'textarea':
                    woocommerce_wp_textarea_input(array(
                        'id' => $meta_key,
                        'label' => $field['field_label'],
                        'desc_tip' => true,
                        'description' => $field['field_description'],
                        'value' => $value
                    ));
                    break;

                case 'select':
                    $options = array();
                    if (!empty($field['field_options'])) {
                        // Check if field_options is a string (pipe-separated format)
                        if (is_string($field['field_options'])) {
                            $option_pairs = explode('|', $field['field_options']);
                            foreach ($option_pairs as $option_pair) {
                                // If the option contains a value/label separator
                                if (strpos($option_pair, ':') !== false) {
                                    list($option_value, $option_label) = explode(':', $option_pair, 2);
                                    $options[trim($option_value)] = trim($option_label);
                                } else {
                                    // Use the same value for both value and label
                                    $options[trim($option_pair)] = trim($option_pair);
                                }
                            }
                        } 
                        // If it's already an array (from database)
                        else if (is_array($field['field_options'])) {
                            foreach ($field['field_options'] as $option_value => $option_label) {
                                $options[$option_value] = $option_label;
                            }
                        }
                    }

                    woocommerce_wp_select(array(
                        'id' => $meta_key,
                        'label' => $field['field_label'],
                        'desc_tip' => true,
                        'description' => $field['field_description'],
                        'options' => $options,
                        'value' => $value
                    ));
                    break;

                case 'checkbox':
                    woocommerce_wp_checkbox(array(
                        'id' => $meta_key,
                        'label' => $field['field_label'],
                        'desc_tip' => true,
                        'description' => $field['field_description'],
                        'value' => $value === 'yes' ? 'yes' : 'no'
                    ));
                    break;

                case 'date':
                    woocommerce_wp_text_input(array(
                        'id' => $meta_key,
                        'label' => $field['field_label'],
                        'desc_tip' => true,
                        'description' => $field['field_description'],
                        'value' => $value,
                        'type' => 'date'
                    ));
                    break;

                case 'number':
                    woocommerce_wp_text_input(array(
                        'id' => $meta_key,
                        'label' => $field['field_label'],
                        'desc_tip' => true,
                        'description' => $field['field_description'],
                        'value' => $value,
                        'type' => 'number'
                    ));
                    break;

                // Add more field types as needed
            }
        }

        echo '</div>';
    }

    /**
     * Add custom fields to the product's inventory tab.
     *
     * @since    1.0.0
     */
    public function add_custom_fields_to_inventory_tab()
    {
        // Implementation similar to add_custom_fields_to_general_tab
        // but for inventory-related fields
    }

    /**
     * Add custom fields to the product's shipping tab.
     *
     * @since    1.0.0
     */
    public function add_custom_fields_to_shipping_tab()
    {
        // Implementation similar to add_custom_fields_to_general_tab
        // but for shipping-related fields
    }

    /**
     * Add custom fields to the product's advanced tab.
     *
     * @since    1.0.0
     */
    public function add_custom_fields_to_advanced_tab()
    {
        // Implementation similar to add_custom_fields_to_general_tab
        // but for advanced fields
    }

    /**
     * Add custom fields to variations.
     *
     * @since    1.0.0
     * @param    int       $loop           Position in the loop.
     * @param    array     $variation_data Variation data.
     * @param    WP_Post   $variation      Post data.
     */
    public function add_custom_fields_to_variations($loop, $variation_data, $variation)
    {
        $fields = $this->parent->get_fields();
        $variation_id = $variation->ID;

        foreach ($fields as $field) {
            // Skip if not meant for variation product type
            if (!empty($field['product_types']) && !in_array('variation', $field['product_types'])) {
                continue;
            }

            $meta_key = '_osna_product_field_' . $field['field_name'];
            $value = get_post_meta($variation_id, $meta_key, true);

            // Use default value if not set
            if ($value === '' && $field['field_default'] !== '') {
                $value = $field['field_default'];
            }

            echo '<div class="form-field form-row">';
            echo '<label for="' . esc_attr($meta_key . '_' . $loop) . '">' . esc_html($field['field_label']) . '</label>';

            switch ($field['field_type']) {
                case 'text':
                    echo '<input type="text" id="' . esc_attr($meta_key . '_' . $loop) . '" name="' . esc_attr($meta_key . '[' . $loop . ']') . '" value="' . esc_attr($value) . '" />';
                    break;

                case 'textarea':
                    echo '<textarea id="' . esc_attr($meta_key . '_' . $loop) . '" name="' . esc_attr($meta_key . '[' . $loop . ']') . '">' . esc_textarea($value) . '</textarea>';
                    break;

                case 'select':
                    echo '<select id="' . esc_attr($meta_key . '_' . $loop) . '" name="' . esc_attr($meta_key . '[' . $loop . ']') . '">';
                    if (!empty($field['field_options'])) {
                        // Check if field_options is a string (pipe-separated format)
                        if (is_string($field['field_options'])) {
                            $option_pairs = explode('|', $field['field_options']);
                            foreach ($option_pairs as $option_pair) {
                                // If the option contains a value/label separator
                                if (strpos($option_pair, ':') !== false) {
                                    list($option_value, $option_label) = explode(':', $option_pair, 2);
                                    echo '<option value="' . esc_attr(trim($option_value)) . '" ' . selected($value, trim($option_value), false) . '>' . esc_html(trim($option_label)) . '</option>';
                                } else {
                                    // Use the same value for both value and label
                                    echo '<option value="' . esc_attr(trim($option_pair)) . '" ' . selected($value, trim($option_pair), false) . '>' . esc_html(trim($option_pair)) . '</option>';
                                }
                            }
                        } 
                        // If it's already an array (from database)
                        else if (is_array($field['field_options'])) {
                            foreach ($field['field_options'] as $option_value => $option_label) {
                                echo '<option value="' . esc_attr($option_value) . '" ' . selected($value, $option_value, false) . '>' . esc_html($option_label) . '</option>';
                            }
                        }
                    }
                    echo '</select>';
                    break;

                case 'checkbox':
                    echo '<input type="checkbox" id="' . esc_attr($meta_key . '_' . $loop) . '" name="' . esc_attr($meta_key . '[' . $loop . ']') . '" value="yes" ' . checked($value, 'yes', false) . ' />';
                    break;

                // Add more field types as needed
            }

            if ($field['field_description']) {
                echo '<p class="description">' . esc_html($field['field_description']) . '</p>';
            }

            echo '</div>';
        }
    }

    /**
     * Save custom fields for a product.
     *
     * @since    1.0.0
     * @param    int       $product_id    The product ID.
     */
    public function save_custom_fields($product_id)
    {
        $fields = $this->parent->get_fields();

        foreach ($fields as $field) {
            $meta_key = '_osna_product_field_' . $field['field_name'];

            // Different handling for different field types
            switch ($field['field_type']) {
                case 'checkbox':
                    $value = isset($_POST[$meta_key]) ? 'yes' : 'no';
                    break;

                default:
                    if (!isset($_POST[$meta_key])) {
                        break;
                    }
                    $value = $_POST[$meta_key];
                    break;
            }

            // Sanitize based on field type
            switch ($field['field_type']) {
                case 'textarea':
                    $value = sanitize_textarea_field($value);
                    break;

                case 'number':
                    $value = floatval($value);
                    break;

                default:
                    $value = sanitize_text_field($value);
                    break;
            }

            update_post_meta($product_id, $meta_key, $value);
        }
    }

    /**
     * Save custom fields for a product variation.
     *
     * @since    1.0.0
     * @param    int       $variation_id    The variation ID.
     * @param    int       $i               Loop counter.
     */
    public function save_variation_custom_fields($variation_id, $i)
    {
        $fields = $this->parent->get_fields();

        foreach ($fields as $field) {
            $meta_key = '_osna_product_field_' . $field['field_name'];

            // Skip if not meant for variation product type
            if (!empty($field['product_types']) && !in_array('variation', $field['product_types'])) {
                continue;
            }

            // Different handling for different field types
            if ($field['field_type'] === 'checkbox') {
                $value = isset($_POST[$meta_key][$i]) ? 'yes' : 'no';
            } else {
                if (!isset($_POST[$meta_key][$i])) {
                    continue;
                }
                $value = $_POST[$meta_key][$i];
            }

            // Sanitize based on field type
            switch ($field['field_type']) {
                case 'textarea':
                    $value = sanitize_textarea_field($value);
                    break;

                case 'number':
                    $value = floatval($value);
                    break;

                default:
                    $value = sanitize_text_field($value);
                    break;
            }

            update_post_meta($variation_id, $meta_key, $value);
        }
    }

    /**
     * Display custom fields on the product page.
     *
     * @since    1.0.0
     */
    public function display_custom_fields_on_product_page()
    {
        global $product;

        if (!$product) {
            return;
        }

        $fields = $this->parent->get_fields();
        $product_id = $product->get_id();
        $product_type = $product->get_type();
        $displayed = false;

        echo '<div class="osna-product-custom-fields">';

        foreach ($fields as $field) {
            // Skip if not meant for this product type
            if (!empty($field['product_types']) && !in_array($product_type, $field['product_types'])) {
                continue;
            }

            // Skip if not meant to display on frontend
            if (!$field['display_on_frontend']) {
                continue;
            }

            $meta_key = '_osna_product_field_' . $field['field_name'];
            $value = get_post_meta($product_id, $meta_key, true);

            // Skip empty values
            if ($value === '') {
                continue;
            }

            // Display heading if this is the first field
            if (!$displayed) {
                echo '<h4>' . __('Product Specifications', 'osna-wp-tools') . '</h4>';
                echo '<table class="shop_attributes osna-custom-fields">';
                $displayed = true;
            }

            // Format value based on field type
            $formatted_value = $value;
            switch ($field['field_type']) {
                case 'select':
                    if (isset($field['field_options'][$value])) {
                        $formatted_value = $field['field_options'][$value];
                    }
                    break;

                case 'checkbox':
                    $formatted_value = $value === 'yes' ? __('Yes', 'osna-wp-tools') : __('No', 'osna-wp-tools');
                    break;
            }

            echo '<tr>';
            echo '<th>' . esc_html($field['field_label']) . '</th>';
            echo '<td>' . wp_kses_post($formatted_value) . '</td>';
            echo '</tr>';
        }

        if ($displayed) {
            echo '</table>';
        }

        echo '</div>';
    }

    /**
     * Add custom fields to cart item data.
     *
     * @since    1.0.0
     * @param    array     $cart_item_data    Cart item data.
     * @param    int       $product_id        Product ID.
     * @param    int       $variation_id      Variation ID.
     * @return   array                        Modified cart item data.
     */
    public function add_custom_fields_to_cart_item($cart_item_data, $product_id, $variation_id)
    {
        $fields = $this->parent->get_fields();
        $actual_id = $variation_id ?: $product_id;

        foreach ($fields as $field) {
            // Skip if not meant to display on frontend
            if (!$field['display_on_frontend']) {
                continue;
            }

            $meta_key = '_osna_product_field_' . $field['field_name'];
            $value = get_post_meta($actual_id, $meta_key, true);

            // Skip empty values
            if ($value === '') {
                continue;
            }

            // Add to cart item data
            if (!isset($cart_item_data['osna_custom_fields'])) {
                $cart_item_data['osna_custom_fields'] = array();
            }

            $cart_item_data['osna_custom_fields'][$field['field_name']] = array(
                'label' => $field['field_label'],
                'value' => $value,
                'type' => $field['field_type'],
                'options' => $field['field_options']
            );
        }

        return $cart_item_data;
    }

    /**
     * Display custom fields in cart and checkout.
     *
     * @since    1.0.0
     * @param    array     $item_data    Item data.
     * @param    array     $cart_item    Cart item.
     * @return   array                   Modified item data.
     */
    public function display_custom_fields_in_cart($item_data, $cart_item)
    {
        if (empty($cart_item['osna_custom_fields'])) {
            return $item_data;
        }

        foreach ($cart_item['osna_custom_fields'] as $field_name => $field) {
            // Format value based on field type
            $formatted_value = $field['value'];
            switch ($field['type']) {
                case 'select':
                    if (isset($field['options'][$field['value']])) {
                        $formatted_value = $field['options'][$field['value']];
                    }
                    break;

                case 'checkbox':
                    $formatted_value = $field['value'] === 'yes' ? __('Yes', 'osna-wp-tools') : __('No', 'osna-wp-tools');
                    break;
            }

            $item_data[] = array(
                'key' => $field['label'],
                'value' => $formatted_value
            );
        }

        return $item_data;
    }

    /**
     * Add custom fields to order line items.
     *
     * @since    1.0.0
     * @param    WC_Order_Item_Product    $item          Order item.
     * @param    string                   $cart_item_key Cart item key.
     * @param    array                    $values        Cart item values.
     * @param    WC_Order                 $order         Order object.
     */
    public function add_custom_fields_to_order_items($item, $cart_item_key, $values, $order)
    {
        if (empty($values['osna_custom_fields'])) {
            return;
        }

        foreach ($values['osna_custom_fields'] as $field_name => $field) {
            // Format value based on field type
            $formatted_value = $field['value'];
            switch ($field['type']) {
                case 'select':
                    if (isset($field['options'][$field['value']])) {
                        $formatted_value = $field['options'][$field['value']];
                    }
                    break;

                case 'checkbox':
                    $formatted_value = $field['value'] === 'yes' ? __('Yes', 'osna-wp-tools') : __('No', 'osna-wp-tools');
                    break;
            }

            $item->add_meta_data($field['label'], $formatted_value);
        }
    }

    /**
     * Display custom fields in emails.
     *
     * @since    1.0.0
     * @param    WC_Order    $order         Order object.
     * @param    bool        $sent_to_admin Whether the email is sent to admin.
     * @param    bool        $plain_text    Whether the email is plain text.
     * @param    WC_Email    $email         Email object.
     */
    public function display_custom_fields_in_emails($order, $sent_to_admin, $plain_text, $email)
    {
        // This method would display custom fields in order emails
        // Implementation would extract custom fields from order items and display them
    }
}