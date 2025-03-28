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
        if (!class_exists('\WPGraphQL')) {
            return;
        }

        $this->parent = new Product_Custom_Fields();

        // Register GraphQL types and fields
        add_action('graphql_register_types', array($this, 'register_graphql_types'));
    }

    /**
     * Register GraphQL types and fields.
     *
     * @since    1.0.0
     */
    public function register_graphql_types()
    {
        // Register ProductCustomFieldValue type
        register_graphql_object_type('ProductCustomFieldValue', [
            'description' => __('Value of a custom field for a product', 'osna-wp-tools'),
            'fields' => [
                'fieldId' => [
                    'type' => 'Int',
                    'description' => __('Field ID', 'osna-wp-tools'),
                ],
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

        // Add fields to WooCommerce Product type
        if (graphql_type_exists('Product')) {
            register_graphql_fields('Product', [
                'customFields' => [
                    'type' => ['list_of' => 'ProductCustomFieldValue'],
                    'description' => __('Custom fields defined for the product', 'osna-wp-tools'),
                    'resolve' => function ($product) {
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
                    },
                ],
            ]);
        }

        // Add fields to ProductVariation type
        if (graphql_type_exists('ProductVariation')) {
            register_graphql_fields('ProductVariation', [
                'customFields' => [
                    'type' => ['list_of' => 'ProductCustomFieldValue'],
                    'description' => __('Custom fields defined for the product variation', 'osna-wp-tools'),
                    'resolve' => function ($variation) {
                        $variation_id = $variation->ID;
                        $field_values = $this->parent->get_product_field_values($variation_id);

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
                    },
                ],
            ]);
        }

        // Register root query to get all field definitions
        register_graphql_field('RootQuery', 'productCustomFields', [
            'type' => ['list_of' => 'ProductCustomFieldDefinition'],
            'description' => __('Get all product custom field definitions', 'osna-wp-tools'),
            'resolve' => function () {
                $fields = $this->parent->get_fields();

                if (empty($fields)) {
                    return [];
                }

                $result = [];
                foreach ($fields as $field) {
                    $options = [];
                    if (!empty($field['field_options']) && is_array($field['field_options'])) {
                        foreach ($field['field_options'] as $value => $label) {
                            $options[] = [
                                'value' => $value,
                                'label' => $label,
                            ];
                        }
                    }

                    $result[] = [
                        'fieldId' => $field['field_id'],
                        'fieldName' => $field['field_name'],
                        'fieldLabel' => $field['field_label'],
                        'fieldDescription' => $field['field_description'],
                        'fieldType' => $field['field_type'],
                        'fieldOptions' => $options,
                        'fieldDefault' => $field['field_default'],
                        'productTypes' => $field['product_types'],
                        'fieldRequired' => (bool) $field['field_required'],
                        'displayInAdmin' => (bool) $field['display_in_admin'],
                        'displayOnFrontend' => (bool) $field['display_on_frontend'],
                        'displayOrder' => (int) $field['display_order'],
                    ];
                }

                return $result;
            },
        ]);

        // Register root query to get a specific field definition
        register_graphql_field('RootQuery', 'productCustomField', [
            'type' => 'ProductCustomFieldDefinition',
            'description' => __('Get a product custom field definition by ID', 'osna-wp-tools'),
            'args' => [
                'id' => [
                    'type' => ['non_null' => 'Int'],
                    'description' => __('The field ID', 'osna-wp-tools'),
                ],
            ],
            'resolve' => function ($source, $args) {
                $field = $this->parent->get_field($args['id']);

                if (!$field) {
                    throw new \GraphQL\Error\UserError(__('Field not found', 'osna-wp-tools'));
                }

                $options = [];
                if (!empty($field['field_options']) && is_array($field['field_options'])) {
                    foreach ($field['field_options'] as $value => $label) {
                        $options[] = [
                            'value' => $value,
                            'label' => $label,
                        ];
                    }
                }

                return [
                    'fieldId' => $field['field_id'],
                    'fieldName' => $field['field_name'],
                    'fieldLabel' => $field['field_label'],
                    'fieldDescription' => $field['field_description'],
                    'fieldType' => $field['field_type'],
                    'fieldOptions' => $options,
                    'fieldDefault' => $field['field_default'],
                    'productTypes' => $field['product_types'],
                    'fieldRequired' => (bool) $field['field_required'],
                    'displayInAdmin' => (bool) $field['display_in_admin'],
                    'displayOnFrontend' => (bool) $field['display_on_frontend'],
                    'displayOrder' => (int) $field['display_order'],
                ];
            },
        ]);
    }
}