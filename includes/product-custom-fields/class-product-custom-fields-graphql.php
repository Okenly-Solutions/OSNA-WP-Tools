<?php
/**
 * The GraphQL integration for Product Custom Fields.
 *
 * @since      1.0.0
 * @package    OSNA_Tools
 * @subpackage OSNA_Tools/includes/product-custom-fields
 */

class Product_Custom_Fields_GraphQL
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
        // Return if WPGraphQL is not active
        if (!function_exists('graphql') || !class_exists('\WPGraphQL')) {
            return;
        }

        // Include WPGraphQL functions if they exist
        if (file_exists(WP_PLUGIN_DIR . '/wp-graphql/src/AppContext.php')) {
            require_once WP_PLUGIN_DIR . '/wp-graphql/src/AppContext.php';
        }

        $this->parent = new Product_Custom_Fields();

        // Register GraphQL types first
        add_action('graphql_register_types', [$this, 'register_graphql_types'], 1);
        
        // Register custom fields on product types with a very late priority
        add_action('graphql_register_types', [$this, 'register_custom_fields_on_product_types'], 99);
    }

    /**
     * Register GraphQL types and fields.
     *
     * @return void
     */
    public function register_graphql_types()
    {
        // Register ProductCustomFieldValue type
        register_graphql_object_type('ProductCustomFieldValue', [
            'description' => __('Value of a custom field for a product', 'osna-wp-tools'),
                'fieldId' => [
                'fieldName' => [
                    'type' => 'String',
                    'description' => __('Field name/key', 'osna-wp-tools'),
                ],
                'label' => [
                    'type' => 'String',
                    'description' => __('Field label', 'osna-wp-tools'),
                ],
                'type' => [
                    'type' => 'String',
                    'description' => __('Field type', 'osna-wp-tools'),
                ],
                'value' => [
                    'type' => 'String',
                    'description' => __('Field value', 'osna-wp-tools'),
                ],
                'displayValue' => [
                    'type' => 'String',
                    'description' => __('Formatted display value', 'osna-wp-tools'),
                ],
            ],
        ]);

        // Register ProductCustomFieldDefinition type
        register_graphql_object_type('ProductCustomFieldDefinition', [
            'description' => __('Definition of a custom field for products', 'osna-wp-tools'),
            'fields' => [
                'fieldId' => [
                    'type' => 'Int',
                    'description' => __('Field ID', 'osna-wp-tools'),
                ],
                'fieldName' => [
                    'type' => 'String',
                    'description' => __('Field name/key', 'osna-wp-tools'),
                ],
                'fieldLabel' => [
                    'type' => 'String',
                    'description' => __('Field label', 'osna-wp-tools'),
                ],
                'fieldDescription' => [
                    'type' => 'String',
                    'description' => __('Field description', 'osna-wp-tools'),
                ],
                'fieldType' => [
                    'type' => 'String',
                    'description' => __('Field type', 'osna-wp-tools'),
                ],
                'fieldOptions' => [
                    'type' => ['list_of' => 'ProductCustomFieldOption'],
                    'description' => __('Field options (for select fields)', 'osna-wp-tools'),
                ],
                'fieldDefault' => [
                    'type' => 'String',
                    'description' => __('Default value', 'osna-wp-tools'),
                ],
                'productTypes' => [
                    'type' => ['list_of' => 'String'],
                    'description' => __('Product types this field applies to', 'osna-wp-tools'),
                ],
                'fieldRequired' => [
                    'type' => 'Boolean',
                    'description' => __('Whether the field is required', 'osna-wp-tools'),
                ],
                'displayInAdmin' => [
                    'type' => 'Boolean',
                    'description' => __('Whether to display in admin', 'osna-wp-tools'),
                ],
                'displayOnFrontend' => [
                    'type' => 'Boolean',
                    'description' => __('Whether to display on frontend', 'osna-wp-tools'),
                ],
                'displayOrder' => [
                    'type' => 'Int',
                    'description' => __('Display order', 'osna-wp-tools'),
                ],
            ],
        ]);

        // Register ProductCustomFieldOption type
        register_graphql_object_type('ProductCustomFieldOption', [
            'description' => __('Option for a select-type custom field', 'osna-wp-tools'),
            'fields' => [
                'value' => [
                    'type' => 'String',
                    'description' => __('Option value', 'osna-wp-tools'),
                ],
                'label' => [
                    'type' => 'String',
                    'description' => __('Option label', 'osna-wp-tools'),
                ],
            ],
        ]);

        // Register root query to get all field definitions
        register_graphql_field('RootQuery', 'productCustomFields', [
            'type' => ['list_of' => 'ProductCustomFieldDefinition'],
            'description' => __('Get all product custom field definitions', 'osna-wp-tools'),
            'resolve' => function() {
                $fields = $this->parent->get_fields();
                $result = [];

                foreach ($fields as $field) {
                    $options = [];
                    if (!empty($field['options'])) {
                        foreach ($field['options'] as $value => $label) {
                            $options[] = [
                                'value' => $value,
                                'label' => $label,
                            ];
                        }
                    }

                    $result[] = [
                        'fieldId' => $field['id'],
                        'fieldName' => $field['name'],
                        'fieldLabel' => $field['label'],
                        'fieldDescription' => $field['description'],
                        'fieldType' => $field['type'],
                        'fieldOptions' => $options,
                        'fieldDefault' => $field['default'],
                        'productTypes' => $field['product_types'],
                        'fieldRequired' => $field['required'],
                        'displayInAdmin' => $field['display_in_admin'],
                        'displayOnFrontend' => $field['display_on_frontend'],
                        'displayOrder' => $field['display_order'],
                    ];
                }

                return $result;
            },
        ]);

        // Register root query to get a specific field definition by ID
        register_graphql_field('RootQuery', 'productCustomField', [
            'type' => 'ProductCustomFieldDefinition',
            'description' => __('Get a product custom field definition by ID', 'osna-wp-tools'),
            'args' => [
                'id' => [
                    'type' => ['non_null' => 'Int'],
                    'description' => __('Field ID', 'osna-wp-tools'),
                ],
            ],
            'resolve' => function($source, $args) {
                $field = $this->parent->get_field($args['id']);

                if (!$field) {
                    return null;
                }

                $options = [];
                if (!empty($field['options'])) {
                    foreach ($field['options'] as $value => $label) {
                        $options[] = [
                            'value' => $value,
                            'label' => $label,
                        ];
                    }
                }

                return [
                    'fieldId' => $field['id'],
                    'fieldName' => $field['name'],
                    'fieldLabel' => $field['label'],
                    'fieldDescription' => $field['description'],
                    'fieldType' => $field['type'],
                    'fieldOptions' => $options,
                    'fieldDefault' => $field['default'],
                    'productTypes' => $field['product_types'],
                    'fieldRequired' => $field['required'],
                    'displayInAdmin' => $field['display_in_admin'],
                    'displayOnFrontend' => $field['display_on_frontend'],
                    'displayOrder' => $field['display_order'],
                ];
            },
        ]);
    }

    /**
     * Register custom fields on product types
     */
    public function register_custom_fields_on_product_types()
    {
        // Create a resolver function for custom fields
        $resolve_custom_fields = function($product) {
            $product_id = $product->ID;
            $field_values = $this->parent->get_product_field_values($product_id);

            if (empty($field_values)) {
                return [];
            }

            $result = [];
            foreach ($field_values as $field_name => $field_data) {
                // Skip empty values
                if ($field_data['value'] === '') {
                    continue;
                }

                $display_value = $field_data['value'];

                // Format display value based on field type
                switch ($field_data['type']) {
                    case 'select':
                        if (isset($field_data['options'][$display_value])) {
                            $display_value = $field_data['options'][$display_value];
                        }
                        break;

                    case 'checkbox':
                        $display_value = $display_value === 'yes' ? __('Yes', 'osna-wp-tools') : __('No', 'osna-wp-tools');
                        break;
                }

                $result[] = [
                    'fieldId' => $field_data['field_id'],
                    'fieldName' => $field_name,
                    'label' => $field_data['label'],
                    'type' => $field_data['type'],
                    'value' => $field_data['value'],
                    'displayValue' => $display_value,
                ];
            }

            return $result;
        };

        // Define the custom fields configuration
        $field_config = [
            'type' => ['list_of' => 'ProductCustomFieldValue'],
            'description' => __('Custom fields defined for the product', 'osna-wp-tools'),
            'resolve' => $resolve_custom_fields,
        ];
        
        // List of product types to register the field on
        $product_types = [
            'SimpleProduct',
            'VariableProduct', 
            'GroupProduct', 
            'ExternalProduct',
            'ProductVariation'
        ];
        
        // Register on each product type
        foreach ($product_types as $type) {
            register_graphql_field($type, 'customFields', $field_config);
        }
        
        // Also register on the Product interface
        register_graphql_field('Product', 'customFields', $field_config);
        
        // And on the ProductUnion interface
        register_graphql_field('ProductUnion', 'customFields', $field_config);
        
        // Register on the Node interface as well
        register_graphql_field('Node', 'customFields', [
            'type' => ['list_of' => 'ProductCustomFieldValue'],
            'description' => __('Custom fields defined for the product', 'osna-wp-tools'),
            'resolve' => function($node) use ($resolve_custom_fields) {
                // Only resolve for product types
                if (isset($node->ID) && $node->post_type === 'product') {
                    return $resolve_custom_fields($node);
                }
                return null;
            },
        ]);
    }
}