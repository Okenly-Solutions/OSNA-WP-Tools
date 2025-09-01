<?php
/**
 * The Product Custom Fields functionality.
 *
 * @since      1.0.0
 * @package    OSNA_Tools
 * @subpackage OSNA_Tools/includes/product-custom-fields
 */

class Product_Custom_Fields
{

    /**
     * The table name for storing field definitions.
     *
     * @since    1.0.0
     * @access   private
     * @var      string    $table_name    The table name for field definitions.
     */
    private $table_name;

    /**
     * Initialize the class.
     *
     * @since    1.0.0
     */
    public function __construct() {
        global $wpdb;
        $this->table_name = $wpdb->prefix . 'osna_product_custom_fields';
    }

    /**
     * Initialize the class.
     *
     * @since    1.0.0
     */
    public function init()
    {
        // Load dependencies
        require_once OSNA_TOOLS_PLUGIN_DIR . 'includes/product-custom-fields/class-product-custom-fields-admin.php';
        require_once OSNA_TOOLS_PLUGIN_DIR . 'includes/product-custom-fields/class-product-custom-fields-woocommerce.php';
        require_once OSNA_TOOLS_PLUGIN_DIR . 'includes/product-custom-fields/class-product-custom-fields-api.php';

        // Check if WooCommerce is active
        if ($this->is_woocommerce_active()) {
            // Initialize WooCommerce integration
            $woocommerce_integration = new Product_Custom_Fields_WooCommerce();
            $woocommerce_integration->init();
        }

        // Initialize admin interface
        $admin = new Product_Custom_Fields_Admin();
        $admin->init();

        // Initialize API endpoints
        $api = new Product_Custom_Fields_API();
        $api->init();

        // Initialize GraphQL integration if WPGraphQL is active
        if (function_exists('graphql') || class_exists('\WPGraphQL')) {
            require_once OSNA_TOOLS_PLUGIN_DIR . 'includes/product-custom-fields/class-product-custom-fields-graphql.php';
            $graphql_integration = new Product_Custom_Fields_GraphQL();
            $graphql_integration->init();
        }
    }

    /**
     * Create the database table during plugin activation.
     *
     * @since    1.0.0
     */
    public static function create_tables()
    {
        global $wpdb;
        $table_name = $wpdb->prefix . 'osna_product_custom_fields';

        $charset_collate = $wpdb->get_charset_collate();

        $sql = "CREATE TABLE $table_name (
            field_id bigint(20) unsigned NOT NULL AUTO_INCREMENT,
            field_name varchar(191) NOT NULL,
            field_label varchar(191) NOT NULL,
            field_description text,
            field_type varchar(50) NOT NULL,
            field_options text,
            field_default text,
            product_types text,
            field_required tinyint(1) DEFAULT 0,
            display_in_admin tinyint(1) DEFAULT 1,
            display_on_frontend tinyint(1) DEFAULT 1,
            display_order int(11) DEFAULT 0,
            created_at datetime NOT NULL,
            updated_at datetime NOT NULL,
            PRIMARY KEY (field_id),
            UNIQUE KEY field_name (field_name)
        ) $charset_collate;";

        require_once(ABSPATH . 'wp-admin/includes/upgrade.php');
        dbDelta($sql);
    }

    /**
     * Get all custom field definitions.
     *
     * @since    1.0.0
     * @return   array    The custom field definitions.
     */
    public function get_fields()
    {
        global $wpdb;
        // Make sure table name is properly set and used in the query
        $fields = $wpdb->get_results("SELECT * FROM {$this->table_name} ORDER BY display_order, field_label", ARRAY_A);

        if (empty($fields)) {
            return array();
        }

        // Process field data
        foreach ($fields as &$field) {
            $field['field_options'] = maybe_unserialize($field['field_options']);
            $field['product_types'] = maybe_unserialize($field['product_types']);
        }

        return $fields;
    }

    /**
     * Get a specific field definition by ID.
     *
     * @since    1.0.0
     * @param    int       $field_id    The field ID.
     * @return   array|null             The field definition or null if not found.
     */
    public function get_field($field_id)
    {
        global $wpdb;
        $field = $wpdb->get_row(
            $wpdb->prepare("SELECT * FROM {$this->table_name} WHERE field_id = %d", $field_id),
            ARRAY_A
        );

        if (!$field) {
            return null;
        }

        $field['field_options'] = maybe_unserialize($field['field_options']);
        $field['product_types'] = maybe_unserialize($field['product_types']);

        return $field;
    }

    /**
     * Add a new custom field definition.
     *
     * @since    1.0.0
     * @param    array     $field_data    The field data.
     * @return   int|false                The new field ID or false on failure.
     */
    public function add_field($field_data)
    {
        global $wpdb;

        $defaults = array(
            'field_name' => '',
            'field_label' => '',
            'field_description' => '',
            'field_type' => 'text',
            'field_options' => array(),
            'field_default' => '',
            'product_types' => array('simple', 'variable'),
            'field_required' => 0,
            'display_in_admin' => 1,
            'display_on_frontend' => 1,
            'display_order' => 0,
            'created_at' => current_time('mysql'),
            'updated_at' => current_time('mysql')
        );

        $field_data = wp_parse_args($field_data, $defaults);

        // Sanitize data
        $field_data['field_name'] = sanitize_key($field_data['field_name']);
        $field_data['field_label'] = sanitize_text_field($field_data['field_label']);
        $field_data['field_description'] = sanitize_textarea_field($field_data['field_description']);
        $field_data['field_type'] = sanitize_key($field_data['field_type']);

        // Serialize arrays
        $field_data['field_options'] = maybe_serialize($field_data['field_options']);
        $field_data['product_types'] = maybe_serialize($field_data['product_types']);

        $result = $wpdb->insert($this->table_name, $field_data);

        if ($result === false) {
            return false;
        }

        return $wpdb->insert_id;
    }

    /**
     * Update an existing custom field definition.
     *
     * @since    1.0.0
     * @param    int       $field_id      The field ID.
     * @param    array     $field_data    The field data.
     * @return   bool                     Whether the update was successful.
     */
    public function update_field($field_id, $field_data)
    {
        global $wpdb;

        // Remove certain fields that shouldn't be updated directly
        unset($field_data['field_id']);
        unset($field_data['created_at']);

        // Set update time
        $field_data['updated_at'] = current_time('mysql');

        // Sanitize data
        if (isset($field_data['field_name'])) {
            $field_data['field_name'] = sanitize_key($field_data['field_name']);
        }
        if (isset($field_data['field_label'])) {
            $field_data['field_label'] = sanitize_text_field($field_data['field_label']);
        }
        if (isset($field_data['field_description'])) {
            $field_data['field_description'] = sanitize_textarea_field($field_data['field_description']);
        }
        if (isset($field_data['field_type'])) {
            $field_data['field_type'] = sanitize_key($field_data['field_type']);
        }

        // Serialize arrays
        if (isset($field_data['field_options']) && is_array($field_data['field_options'])) {
            $field_data['field_options'] = maybe_serialize($field_data['field_options']);
        }
        if (isset($field_data['product_types']) && is_array($field_data['product_types'])) {
            $field_data['product_types'] = maybe_serialize($field_data['product_types']);
        }

        $result = $wpdb->update(
            $this->table_name,
            $field_data,
            array('field_id' => $field_id)
        );

        return $result !== false;
    }

    /**
     * Delete a custom field definition.
     *
     * @since    1.0.0
     * @param    int       $field_id    The field ID.
     * @return   bool                   Whether the deletion was successful.
     */
    public function delete_field($field_id)
    {
        global $wpdb;

        // Get the field to find its name
        $field = $this->get_field($field_id);
        if (!$field) {
            return false;
        }

        // Delete the field definition
        $result = $wpdb->delete(
            $this->table_name,
            array('field_id' => $field_id)
        );

        if ($result === false) {
            return false;
        }

        // Optionally, clean up post meta data associated with this field
        // This is resource-intensive for large sites, so make it optional or scheduled
        // delete_post_meta_by_key('_osna_product_field_' . $field['field_name']);

        return true;
    }

    /**
     * Get custom field values for a product.
     *
     * @since    1.0.0
     * @param    int       $product_id    The product ID.
     * @return   array                    The custom field values.
     */
    public function get_product_field_values($product_id)
    {
        $fields = $this->get_fields();
        $values = array();

        foreach ($fields as $field) {
            $meta_key = '_osna_product_field_' . $field['field_name'];
            $value = get_post_meta($product_id, $meta_key, true);

            // Use default value if not set
            if ($value === '' && $field['field_default'] !== '') {
                $value = $field['field_default'];
            }

            $values[$field['field_name']] = array(
                'field_id' => $field['field_id'],
                'label' => $field['field_label'],
                'type' => $field['field_type'],
                'value' => $value
            );
        }

        return $values;
    }

    /**
     * Check if WooCommerce is active.
     *
     * @since    1.0.0
     * @return   boolean
     */
    private function is_woocommerce_active()
    {
        return in_array('woocommerce/woocommerce.php', apply_filters('active_plugins', get_option('active_plugins')));
    }
}