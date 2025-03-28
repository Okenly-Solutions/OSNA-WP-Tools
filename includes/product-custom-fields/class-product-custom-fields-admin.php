<?php
/**
 * The admin-specific functionality for Product Custom Fields.
 *
 * @since      1.0.0
 * @package    OSNA_Tools
 * @subpackage OSNA_Tools/includes/product-custom-fields
 */

class Product_Custom_Fields_Admin
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

        // Add admin submenu page
        add_action('admin_menu', array($this, 'add_admin_menu'), 99);

        // Register AJAX handlers for field operations
        add_action('wp_ajax_osna_add_product_field', array($this, 'ajax_add_field'));
        add_action('wp_ajax_osna_update_product_field', array($this, 'ajax_update_field'));
        add_action('wp_ajax_osna_delete_product_field', array($this, 'ajax_delete_field'));

        // Add admin scripts and styles
        add_action('admin_enqueue_scripts', array($this, 'enqueue_scripts'));
    }

    /**
     * Add the admin submenu page.
     *
     * @since    1.0.0
     */
    public function add_admin_menu()
    {
        add_submenu_page(
            'osna-wp-tools',
            __('Product Custom Fields', 'osna-wp-tools'),
            __('Product Fields', 'osna-wp-tools'),
            'manage_options',
            'osna-product-custom-fields',
            array($this, 'render_admin_page')
        );
    }

    /**
     * Render the admin page.
     *
     * @since    1.0.0
     */
    public function render_admin_page()
    {
        // Check if WooCommerce is active
        if (!class_exists('WC_Product')) {
            echo '<div class="notice notice-error"><p>' . __('WooCommerce is not active. Product Custom Fields requires WooCommerce to function properly.', 'osna-wp-tools') . '</p></div>';
            return;
        }

        // Display success/error messages
        if (isset($_GET['message'])) {
            $message = sanitize_text_field($_GET['message']);
            if ($message === 'field_added') {
                echo '<div class="notice notice-success is-dismissible"><p>' . __('Field added successfully.', 'osna-wp-tools') . '</p></div>';
            } elseif ($message === 'field_updated') {
                echo '<div class="notice notice-success is-dismissible"><p>' . __('Field updated successfully.', 'osna-wp-tools') . '</p></div>';
            } elseif ($message === 'field_deleted') {
                echo '<div class="notice notice-success is-dismissible"><p>' . __('Field deleted successfully.', 'osna-wp-tools') . '</p></div>';
            } elseif ($message === 'error') {
                echo '<div class="notice notice-error is-dismissible"><p>' . __('An error occurred. Please try again.', 'osna-wp-tools') . '</p></div>';
            }
        }

        // Get field to edit if specified
        $edit_field_id = isset($_GET['edit_field']) ? intval($_GET['edit_field']) : 0;
        $edit_field = $edit_field_id ? $this->parent->get_field($edit_field_id) : null;

        // Get all fields
        $fields = $this->parent->get_fields();
        ?>
        <div class="wrap">
            <h1 class="wp-heading-inline"><?php _e('Product Custom Fields', 'osna-wp-tools'); ?></h1>

            <div class="bg-white rounded-2xl shadow-xl overflow-hidden max-w-7xl mx-auto my-8">
                <!-- Header -->
                <div class="bg-gradient-to-r from-blue-500 to-indigo-600 px-8 py-6">
                    <div class="flex items-center justify-between">
                        <h2 class="text-3xl font-semibold text-white"><?php _e('Manage Product Fields', 'osna-wp-tools'); ?>
                        </h2>
                    </div>
                    <p class="text-blue-100 mt-2"><?php _e('Add custom fields to WooCommerce products', 'osna-wp-tools'); ?></p>
                </div>

                <!-- Content -->
                <div class="p-8">
                    <div class="grid grid-cols-1 md:grid-cols-2 gap-8">
                        <!-- Field Form -->
                        <div class="bg-gray-50 rounded-xl overflow-hidden shadow-md">
                            <div class="p-6">
                                <h3 class="text-xl font-medium text-gray-800 mb-4">
                                    <?php echo $edit_field ? __('Edit Field', 'osna-wp-tools') : __('Add New Field', 'osna-wp-tools'); ?>
                                </h3>

                                <form id="osna-product-field-form" method="post"
                                    action="<?php echo admin_url('admin-ajax.php'); ?>">
                                    <?php wp_nonce_field('osna_product_field_nonce', 'osna_product_field_nonce'); ?>

                                    <input type="hidden" name="action"
                                        value="<?php echo $edit_field ? 'osna_update_product_field' : 'osna_add_product_field'; ?>">
                                    <?php if ($edit_field): ?>
                                        <input type="hidden" name="field_id"
                                            value="<?php echo esc_attr($edit_field['field_id']); ?>">
                                    <?php endif; ?>

                                    <div class="osna-field-group mb-4">
                                        <label for="field_name" class="block text-sm font-medium text-gray-700 mb-1">
                                            <?php _e('Field Name', 'osna-wp-tools'); ?>
                                            <span class="required">*</span>
                                        </label>
                                        <input type="text" id="field_name" name="field_name"
                                            value="<?php echo $edit_field ? esc_attr($edit_field['field_name']) : ''; ?>" <?php echo $edit_field ? 'readonly' : ''; ?>
                                            class="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-blue-500 focus:ring focus:ring-blue-500 focus:ring-opacity-50"
                                            required>
                                        <p class="text-sm text-gray-500 mt-1">
                                            <?php _e('Internal name for the field. Use lowercase letters, numbers, and underscores. No spaces.', 'osna-wp-tools'); ?>
                                        </p>
                                    </div>

                                    <div class="osna-field-group mb-4">
                                        <label for="field_label" class="block text-sm font-medium text-gray-700 mb-1">
                                            <?php _e('Field Label', 'osna-wp-tools'); ?>
                                            <span class="required">*</span>
                                        </label>
                                        <input type="text" id="field_label" name="field_label"
                                            value="<?php echo $edit_field ? esc_attr($edit_field['field_label']) : ''; ?>"
                                            class="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-blue-500 focus:ring focus:ring-blue-500 focus:ring-opacity-50"
                                            required>
                                        <p class="text-sm text-gray-500 mt-1">
                                            <?php _e('Label displayed to users.', 'osna-wp-tools'); ?>
                                        </p>
                                    </div>

                                    <div class="osna-field-group mb-4">
                                        <label for="field_type" class="block text-sm font-medium text-gray-700 mb-1">
                                            <?php _e('Field Type', 'osna-wp-tools'); ?>
                                            <span class="required">*</span>
                                        </label>
                                        <select id="field_type" name="field_type"
                                            class="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-blue-500 focus:ring focus:ring-blue-500 focus:ring-opacity-50"
                                            required>
                                            <option value="text" <?php selected($edit_field ? $edit_field['field_type'] : '', 'text'); ?>>
                                                <?php _e('Text', 'osna-wp-tools'); ?>
                                            </option>
                                            <option value="textarea" <?php selected($edit_field ? $edit_field['field_type'] : '', 'textarea'); ?>>
                                                <?php _e('Textarea', 'osna-wp-tools'); ?>
                                            </option>
                                            <option value="select" <?php selected($edit_field ? $edit_field['field_type'] : '', 'select'); ?>>
                                                <?php _e('Select', 'osna-wp-tools'); ?>
                                            </option>
                                            <option value="checkbox" <?php selected($edit_field ? $edit_field['field_type'] : '', 'checkbox'); ?>>
                                                <?php _e('Checkbox', 'osna-wp-tools'); ?>
                                            </option>
                                            <option value="date" <?php selected($edit_field ? $edit_field['field_type'] : '', 'date'); ?>>
                                                <?php _e('Date', 'osna-wp-tools'); ?>
                                            </option>
                                            <option value="number" <?php selected($edit_field ? $edit_field['field_type'] : '', 'number'); ?>>
                                                <?php _e('Number', 'osna-wp-tools'); ?>
                                            </option>
                                        </select>
                                    </div>

                                    <div id="field_options_container" class="osna-field-group mb-4"
                                        style="<?php echo ($edit_field && $edit_field['field_type'] === 'select') ? '' : 'display: none;'; ?>">
                                        <label for="field_options" class="block text-sm font-medium text-gray-700 mb-1">
                                            <?php _e('Field Options', 'osna-wp-tools'); ?>
                                        </label>
                                        <textarea id="field_options" name="field_options" rows="4"
                                            class="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-blue-500 focus:ring focus:ring-blue-500 focus:ring-opacity-50"><?php
                                            if ($edit_field && !empty($edit_field['field_options'])) {
                                                $options = array();
                                                foreach ($edit_field['field_options'] as $value => $label) {
                                                    $options[] = $value . '|' . $label;
                                                }
                                                echo esc_textarea(implode("\n", $options));
                                            }
                                            ?></textarea>
                                        <p class="text-sm text-gray-500 mt-1">
                                            <?php _e('Enter one option per line in the format: value|label', 'osna-wp-tools'); ?>
                                        </p>
                                    </div>

                                    <div class="osna-field-group mb-4">
                                        <label for="field_description" class="block text-sm font-medium text-gray-700 mb-1">
                                            <?php _e('Field Description', 'osna-wp-tools'); ?>
                                        </label>
                                        <textarea id="field_description" name="field_description" rows="3"
                                            class="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-blue-500 focus:ring focus:ring-blue-500 focus:ring-opacity-50"><?php echo $edit_field ? esc_textarea($edit_field['field_description']) : ''; ?></textarea>
                                        <p class="text-sm text-gray-500 mt-1">
                                            <?php _e('Help text displayed to users.', 'osna-wp-tools'); ?>
                                        </p>
                                    </div>

                                    <div class="osna-field-group mb-4">
                                        <label for="field_default" class="block text-sm font-medium text-gray-700 mb-1">
                                            <?php _e('Default Value', 'osna-wp-tools'); ?>
                                        </label>
                                        <input type="text" id="field_default" name="field_default"
                                            value="<?php echo $edit_field ? esc_attr($edit_field['field_default']) : ''; ?>"
                                            class="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-blue-500 focus:ring focus:ring-blue-500 focus:ring-opacity-50">
                                    </div>

                                    <div class="osna-field-group mb-4">
                                        <label class="block text-sm font-medium text-gray-700 mb-1">
                                            <?php _e('Product Types', 'osna-wp-tools'); ?>
                                        </label>
                                        <?php
                                        $product_types = array(
                                            'simple' => __('Simple Product', 'osna-wp-tools'),
                                            'variable' => __('Variable Product', 'osna-wp-tools'),
                                            'variation' => __('Product Variation', 'osna-wp-tools'),
                                            'grouped' => __('Grouped Product', 'osna-wp-tools'),
                                            'external' => __('External/Affiliate Product', 'osna-wp-tools')
                                        );

                                        $selected_types = $edit_field ? $edit_field['product_types'] : array('simple', 'variable');

                                        foreach ($product_types as $type => $label) {
                                            echo '<div class="flex items-center">';
                                            echo '<input type="checkbox" id="product_type_' . esc_attr($type) . '" name="product_types[]" value="' . esc_attr($type) . '" ' .
                                                (in_array($type, $selected_types) ? 'checked' : '') . ' class="rounded border-gray-300 text-blue-600 shadow-sm focus:border-blue-500 focus:ring focus:ring-blue-500 focus:ring-opacity-50 mr-2">';
                                            echo '<label for="product_type_' . esc_attr($type) . '">' . esc_html($label) . '</label>';
                                            echo '</div>';
                                        }
                                        ?>
                                    </div>

                                    <div class="osna-field-group mb-4">
                                        <div class="flex items-center">
                                            <input type="checkbox" id="field_required" name="field_required" value="1" <?php checked($edit_field ? $edit_field['field_required'] : 0, 1); ?>
                                                class="rounded border-gray-300 text-blue-600 shadow-sm focus:border-blue-500 focus:ring focus:ring-blue-500 focus:ring-opacity-50 mr-2">
                                            <label for="field_required">
                                                <?php _e('Required Field', 'osna-wp-tools'); ?>
                                            </label>
                                        </div>
                                    </div>

                                    <div class="osna-field-group mb-4">
                                        <div class="flex items-center">
                                            <input type="checkbox" id="display_in_admin" name="display_in_admin" value="1" <?php checked($edit_field ? $edit_field['display_in_admin'] : 1, 1); ?>
                                                class="rounded border-gray-300 text-blue-600 shadow-sm focus:border-blue-500 focus:ring focus:ring-blue-500 focus:ring-opacity-50 mr-2">
                                            <label for="display_in_admin">
                                                <?php _e('Show in Admin', 'osna-wp-tools'); ?>
                                            </label>
                                        </div>
                                    </div>

                                    <div class="osna-field-group mb-4">
                                        <div class="flex items-center">
                                            <input type="checkbox" id="display_on_frontend" name="display_on_frontend" value="1"
                                                <?php checked($edit_field ? $edit_field['display_on_frontend'] : 1, 1); ?>
                                                class="rounded border-gray-300 text-blue-600 shadow-sm focus:border-blue-500 focus:ring focus:ring-blue-500 focus:ring-opacity-50 mr-2">
                                            <label for="display_on_frontend">
                                                <?php _e('Show on Frontend', 'osna-wp-tools'); ?>
                                            </label>
                                        </div>
                                    </div>

                                    <div class="osna-field-group mb-4">
                                        <label for="display_order" class="block text-sm font-medium text-gray-700 mb-1">
                                            <?php _e('Display Order', 'osna-wp-tools'); ?>
                                        </label>
                                        <input type="number" id="display_order" name="display_order"
                                            value="<?php echo $edit_field ? esc_attr($edit_field['display_order']) : '0'; ?>"
                                            class="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-blue-500 focus:ring focus:ring-blue-500 focus:ring-opacity-50">
                                        <p class="text-sm text-gray-500 mt-1">
                                            <?php _e('Order in which fields appear. Lower numbers appear first.', 'osna-wp-tools'); ?>
                                        </p>
                                    </div>

                                    <div class="mt-6">
                                        <button type="submit"
                                            class="inline-flex items-center px-4 py-2 border border-transparent text-sm font-medium rounded-md text-white bg-blue-600 hover:bg-blue-700 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-blue-500">
                                            <?php echo $edit_field ? __('Update Field', 'osna-wp-tools') : __('Add Field', 'osna-wp-tools'); ?>
                                        </button>

                                        <?php if ($edit_field): ?>
                                            <a href="<?php echo admin_url('admin.php?page=osna-product-custom-fields'); ?>"
                                                class="ml-4 inline-flex items-center px-4 py-2 border border-gray-300 shadow-sm text-sm font-medium rounded-md text-gray-700 bg-white hover:bg-gray-50 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-blue-500">
                                                <?php _e('Cancel', 'osna-wp-tools'); ?>
                                            </a>
                                        <?php endif; ?>
                                    </div>
                                </form>
                            </div>
                        </div>

                        <!-- Field List -->
                        <div class="bg-gray-50 rounded-xl overflow-hidden shadow-md">
                            <div class="p-6">
                                <h3 class="text-xl font-medium text-gray-800 mb-4">
                                    <?php _e('Existing Fields', 'osna-wp-tools'); ?>
                                </h3>

                                <?php if (empty($fields)): ?>
                                    <p><?php _e('No custom fields defined yet.', 'osna-wp-tools'); ?></p>
                                <?php else: ?>
                                    <div class="overflow-auto">
                                        <table class="min-w-full divide-y divide-gray-200">
                                            <thead class="bg-gray-100">
                                                <tr>
                                                    <th scope="col"
                                                        class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">
                                                        <?php _e('Name', 'osna-wp-tools'); ?>
                                                    </th>
                                                    <th scope="col"
                                                        class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">
                                                        <?php _e('Label', 'osna-wp-tools'); ?>
                                                    </th>
                                                    <th scope="col"
                                                        class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">
                                                        <?php _e('Type', 'osna-wp-tools'); ?>
                                                    </th>
                                                    <th scope="col"
                                                        class="px-6 py-3 text-right text-xs font-medium text-gray-500 uppercase tracking-wider">
                                                        <?php _e('Actions', 'osna-wp-tools'); ?>
                                                    </th>
                                                </tr>
                                            </thead>
                                            <tbody class="bg-white divide-y divide-gray-200">
                                                <?php foreach ($fields as $field): ?>
                                                    <tr>
                                                        <td class="px-6 py-4 whitespace-nowrap text-sm font-medium text-gray-900">
                                                            <?php echo esc_html($field['field_name']); ?>
                                                        </td>
                                                        <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-500">
                                                            <?php echo esc_html($field['field_label']); ?>
                                                        </td>
                                                        <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-500">
                                                            <?php
                                                            $type_labels = array(
                                                                'text' => __('Text', 'osna-wp-tools'),
                                                                'textarea' => __('Textarea', 'osna-wp-tools'),
                                                                'select' => __('Select', 'osna-wp-tools'),
                                                                'checkbox' => __('Checkbox', 'osna-wp-tools'),
                                                                'date' => __('Date', 'osna-wp-tools'),
                                                                'number' => __('Number', 'osna-wp-tools')
                                                            );
                                                            echo isset($type_labels[$field['field_type']]) ? esc_html($type_labels[$field['field_type']]) : esc_html($field['field_type']);
                                                            ?>
                                                        </td>
                                                        <td class="px-6 py-4 whitespace-nowrap text-right text-sm font-medium">
                                                            <a href="<?php echo esc_url(admin_url('admin.php?page=osna-product-custom-fields&edit_field=' . $field['field_id'])); ?>"
                                                                class="text-blue-600 hover:text-blue-900">
                                                                <?php _e('Edit', 'osna-wp-tools'); ?>
                                                            </a>
                                                            <span class="mx-2">|</span>
                                                            <a href="#" class="text-red-600 hover:text-red-900 delete-field"
                                                                data-id="<?php echo esc_attr($field['field_id']); ?>"
                                                                data-name="<?php echo esc_attr($field['field_name']); ?>">
                                                                <?php _e('Delete', 'osna-wp-tools'); ?>
                                                            </a>
                                                        </td>
                                                    </tr>
                                                <?php endforeach; ?>
                                            </tbody>
                                        </table>
                                    </div>
                                <?php endif; ?>
                            </div>
                        </div>
                    </div>

                    <!-- Additional Information -->
                    <div class="mt-8 p-6 bg-gray-50 rounded-xl">
                        <h3 class="text-xl font-medium text-gray-800 mb-4">
                            <?php _e('How to Use Product Custom Fields', 'osna-wp-tools'); ?>
                        </h3>

                        <ol class="list-decimal pl-5 text-gray-600 mb-4 space-y-2">
                            <li><?php _e('Create your custom fields using the form on the left.', 'osna-wp-tools'); ?></li>
                            <li><?php _e('Edit any WooCommerce product to see your custom fields in the "Product Data" section.', 'osna-wp-tools'); ?>
                            </li>
                            <li><?php _e('Custom fields will be displayed on the product page if "Show on Frontend" is enabled.', 'osna-wp-tools'); ?>
                            </li>
                            <li><?php _e('Custom field data is available via the WooCommerce REST API and GraphQL if WPGraphQL is installed.', 'osna-wp-tools'); ?>
                            </li>
                        </ol>

                        <div class="bg-blue-50 border-l-4 border-blue-400 p-4 mb-4">
                            <div class="flex">
                                <div class="flex-shrink-0">
                                    <svg class="h-5 w-5 text-blue-400" xmlns="http://www.w3.org/2000/svg" viewBox="0 0 20 20"
                                        fill="currentColor">
                                        <path fill-rule="evenodd"
                                            d="M18 10a8 8 0 11-16 0 8 8 0 0116 0zm-7-4a1 1 0 11-2 0 1 1 0 012 0zM9 9a1 1 0 000 2v3a1 1 0 001 1h1a1 1 0 100-2v-3a1 1 0 00-1-1H9z"
                                            clip-rule="evenodd" />
                                    </svg>
                                </div>
                                <div class="ml-3">
                                    <p class="text-sm text-blue-700">
                                        <?php _e('Need help? Check out the documentation or contact support.', 'osna-wp-tools'); ?>
                                    </p>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Footer -->
                <div class="bg-gray-50 px-8 py-4 border-t border-gray-200">
                    <div class="flex items-center justify-between">
                        <p class="text-sm text-gray-500">© <?php echo date('Y'); ?> OSNA WP Tools</p>
                        <a href="https://osna.com" target="_blank" class="text-sm text-blue-600 hover:text-blue-800">
                            <?php _e('Visit OSNA', 'osna-wp-tools'); ?>
                        </a>
                    </div>
                </div>
            </div>

            <!-- Delete Confirmation Modal -->
            <div id="delete-modal" class="hidden fixed z-10 inset-0 overflow-y-auto" aria-labelledby="modal-title" role="dialog"
                aria-modal="true">
                <div class="flex items-end justify-center min-h-screen pt-4 px-4 pb-20 text-center sm:block sm:p-0">
                    <div class="fixed inset-0 bg-gray-500 bg-opacity-75 transition-opacity" aria-hidden="true"></div>

                    <span class="hidden sm:inline-block sm:align-middle sm:h-screen" aria-hidden="true">&#8203;</span>

                    <div
                        class="inline-block align-bottom bg-white rounded-lg text-left overflow-hidden shadow-xl transform transition-all sm:my-8 sm:align-middle sm:max-w-lg sm:w-full">
                        <div class="bg-white px-4 pt-5 pb-4 sm:p-6 sm:pb-4">
                            <div class="sm:flex sm:items-start">
                                <div
                                    class="mx-auto flex-shrink-0 flex items-center justify-center h-12 w-12 rounded-full bg-red-100 sm:mx-0 sm:h-10 sm:w-10">
                                    <svg class="h-6 w-6 text-red-600" xmlns="http://www.w3.org/2000/svg" fill="none"
                                        viewBox="0 0 24 24" stroke="currentColor" aria-hidden="true">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                            d="M12 9v2m0 4h.01m-6.938 4h13.856c1.54 0 2.502-1.667 1.732-3L13.732 4c-.77-1.333-2.694-1.333-3.464 0L3.34 16c-.77 1.333.192 3 1.732 3z" />
                                    </svg>
                                </div>
                                <div class="mt-3 text-center sm:mt-0 sm:ml-4 sm:text-left">
                                    <h3 class="text-lg leading-6 font-medium text-gray-900" id="modal-title">
                                        <?php _e('Delete Custom Field', 'osna-wp-tools'); ?>
                                    </h3>
                                    <div class="mt-2">
                                        <p class="text-sm text-gray-500" id="delete-message">
                                            <?php _e('Are you sure you want to delete this field? All data associated with this field will be lost. This action cannot be undone.', 'osna-wp-tools'); ?>
                                        </p>
                                    </div>
                                </div>
                            </div>
                        </div>
                        <div class="bg-gray-50 px-4 py-3 sm:px-6 sm:flex sm:flex-row-reverse">
                            <form id="delete-field-form" method="post" action="<?php echo admin_url('admin-ajax.php'); ?>">
                                <?php wp_nonce_field('osna_product_field_nonce', 'osna_product_field_nonce'); ?>
                                <input type="hidden" name="action" value="osna_delete_product_field">
                                <input type="hidden" name="field_id" id="delete-field-id" value="">

                                <button type="submit"
                                    class="w-full inline-flex justify-center rounded-md border border-transparent shadow-sm px-4 py-2 bg-red-600 text-base font-medium text-white hover:bg-red-700 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-red-500 sm:ml-3 sm:w-auto sm:text-sm">
                                    <?php _e('Delete', 'osna-wp-tools'); ?>
                                </button>
                            </form>
                            <button type="button" id="cancel-delete"
                                class="mt-3 w-full inline-flex justify-center rounded-md border border-gray-300 shadow-sm px-4 py-2 bg-white text-base font-medium text-gray-700 hover:bg-gray-50 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-blue-500 sm:mt-0 sm:ml-3 sm:w-auto sm:text-sm">
                                <?php _e('Cancel', 'osna-wp-tools'); ?>
                            </button>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <script>
            jQuery(document).ready(function ($) {
                // Show/hide field options based on field type
                $('#field_type').on('change', function () {
                    if ($(this).val() === 'select') {
                        $('#field_options_container').show();
                    } else {
                        $('#field_options_container').hide();
                    }
                });

                // Delete field confirmation
                $('.delete-field').on('click', function (e) {
                    e.preventDefault();
                    var fieldId = $(this).data('id');
                    var fieldName = $(this).data('name');

                    $('#delete-field-id').val(fieldId);
                    $('#delete-message').html('<?php _e('Are you sure you want to delete the field', 'osna-wp-tools'); ?> <strong>' + fieldName + '</strong>? <?php _e('All data associated with this field will be lost. This action cannot be undone.', 'osna-wp-tools'); ?>');
                    $('#delete-modal').removeClass('hidden');
                });

                // Cancel delete
                $('#cancel-delete').on('click', function () {
                    $('#delete-modal').addClass('hidden');
                });

                // Handle form submission with AJAX
                $('#osna-product-field-form').on('submit', function (e) {
                    e.preventDefault();

                    $.ajax({
                        url: $(this).attr('action'),
                        type: 'POST',
                        data: $(this).serialize(),
                        success: function (response) {
                            if (response.success) {
                                window.location.href = '<?php echo admin_url('admin.php?page=osna-product-custom-fields'); ?>&message=' + response.data.message;
                            } else {
                                alert(response.data.message);
                            }
                        }
                    });
                });

                // Handle delete form submission with AJAX
                $('#delete-field-form').on('submit', function (e) {
                    e.preventDefault();

                    $.ajax({
                        url: $(this).attr('action'),
                        type: 'POST',
                        data: $(this).serialize(),
                        success: function (response) {
                            if (response.success) {
                                window.location.href = '<?php echo admin_url('admin.php?page=osna-product-custom-fields'); ?>&message=field_deleted';
                            } else {
                                alert(response.data.message);
                                $('#delete-modal').addClass('hidden');
                            }
                        }
                    });
                });
            });
        </script>
        <?php
    }

    /**
     * Enqueue scripts and styles for the admin page.
     *
     * @since    1.0.0
     * @param    string    $hook    The current admin page.
     */
    public function enqueue_scripts($hook)
    {
        // Only enqueue on our admin page
        if ($hook !== 'osna-tools_page_osna-product-custom-fields') {
            return;
        }
    }

    /**
     * AJAX handler for adding a new field.
     *
     * @since    1.0.0
     */
    public function ajax_add_field()
    {
        // Check nonce
        if (!isset($_POST['osna_product_field_nonce']) || !wp_verify_nonce($_POST['osna_product_field_nonce'], 'osna_product_field_nonce')) {
            wp_send_json_error(array('message' => __('Security check failed.', 'osna-wp-tools')));
        }

        // Check user permissions
        if (!current_user_can('manage_options')) {
            wp_send_json_error(array('message' => __('You do not have permission to perform this action.', 'osna-wp-tools')));
        }

        // Validate required fields
        if (empty($_POST['field_name']) || empty($_POST['field_label']) || empty($_POST['field_type'])) {
            wp_send_json_error(array('message' => __('Required fields are missing.', 'osna-wp-tools')));
        }

        // Format field data
        $field_data = array(
            'field_name' => sanitize_key($_POST['field_name']),
            'field_label' => sanitize_text_field($_POST['field_label']),
            'field_description' => isset($_POST['field_description']) ? sanitize_textarea_field($_POST['field_description']) : '',
            'field_type' => sanitize_key($_POST['field_type']),
            'field_default' => isset($_POST['field_default']) ? sanitize_text_field($_POST['field_default']) : '',
            'field_required' => isset($_POST['field_required']) ? 1 : 0,
            'display_in_admin' => isset($_POST['display_in_admin']) ? 1 : 0,
            'display_on_frontend' => isset($_POST['display_on_frontend']) ? 1 : 0,
            'display_order' => isset($_POST['display_order']) ? intval($_POST['display_order']) : 0,
            'product_types' => isset($_POST['product_types']) ? $_POST['product_types'] : array('simple', 'variable')
        );

        // Process field options for select fields
        if ($field_data['field_type'] === 'select' && !empty($_POST['field_options'])) {
            $options_text = sanitize_textarea_field($_POST['field_options']);
            $lines = explode("\n", $options_text);
            $options = array();

            foreach ($lines as $line) {
                $parts = explode('|', $line, 2);
                if (count($parts) === 2) {
                    $key = trim($parts[0]);
                    $value = trim($parts[1]);
                    if (!empty($key)) {
                        $options[$key] = $value;
                    }
                }
            }

            $field_data['field_options'] = $options;
        } else {
            $field_data['field_options'] = array();
        }

        // Add the field
        $result = $this->parent->add_field($field_data);

        if ($result) {
            wp_send_json_success(array('message' => 'field_added'));
        } else {
            wp_send_json_error(array('message' => __('Failed to add field. Please try again.', 'osna-wp-tools')));
        }
    }

    /**
     * AJAX handler for updating a field.
     *
     * @since    1.0.0
     */
    public function ajax_update_field()
    {
        // Check nonce
        if (!isset($_POST['osna_product_field_nonce']) || !wp_verify_nonce($_POST['osna_product_field_nonce'], 'osna_product_field_nonce')) {
            wp_send_json_error(array('message' => __('Security check failed.', 'osna-wp-tools')));
        }

        // Check user permissions
        if (!current_user_can('manage_options')) {
            wp_send_json_error(array('message' => __('You do not have permission to perform this action.', 'osna-wp-tools')));
        }

        // Validate required fields
        if (empty($_POST['field_id']) || empty($_POST['field_label']) || empty($_POST['field_type'])) {
            wp_send_json_error(array('message' => __('Required fields are missing.', 'osna-wp-tools')));
        }

        $field_id = intval($_POST['field_id']);

        // Format field data
        $field_data = array(
            'field_label' => sanitize_text_field($_POST['field_label']),
            'field_description' => isset($_POST['field_description']) ? sanitize_textarea_field($_POST['field_description']) : '',
            'field_type' => sanitize_key($_POST['field_type']),
            'field_default' => isset($_POST['field_default']) ? sanitize_text_field($_POST['field_default']) : '',
            'field_required' => isset($_POST['field_required']) ? 1 : 0,
            'display_in_admin' => isset($_POST['display_in_admin']) ? 1 : 0,
            'display_on_frontend' => isset($_POST['display_on_frontend']) ? 1 : 0,
            'display_order' => isset($_POST['display_order']) ? intval($_POST['display_order']) : 0,
            'product_types' => isset($_POST['product_types']) ? $_POST['product_types'] : array('simple', 'variable')
        );

        // Process field options for select fields
        if ($field_data['field_type'] === 'select' && !empty($_POST['field_options'])) {
            $options_text = sanitize_textarea_field($_POST['field_options']);
            $lines = explode("\n", $options_text);
            $options = array();

            foreach ($lines as $line) {
                $parts = explode('|', $line, 2);
                if (count($parts) === 2) {
                    $key = trim($parts[0]);
                    $value = trim($parts[1]);
                    if (!empty($key)) {
                        $options[$key] = $value;
                    }
                }
            }

            $field_data['field_options'] = $options;
        } else {
            $field_data['field_options'] = array();
        }

        // Update the field
        $result = $this->parent->update_field($field_id, $field_data);

        if ($result) {
            wp_send_json_success(array('message' => 'field_updated'));
        } else {
            wp_send_json_error(array('message' => __('Failed to update field. Please try again.', 'osna-wp-tools')));
        }
    }

    /**
     * AJAX handler for deleting a field.
     *
     * @since    1.0.0
     */
    public function ajax_delete_field()
    {
        // Check nonce
        if (!isset($_POST['osna_product_field_nonce']) || !wp_verify_nonce($_POST['osna_product_field_nonce'], 'osna_product_field_nonce')) {
            wp_send_json_error(array('message' => __('Security check failed.', 'osna-wp-tools')));
        }

        // Check user permissions
        if (!current_user_can('manage_options')) {
            wp_send_json_error(array('message' => __('You do not have permission to perform this action.', 'osna-wp-tools')));
        }

        // Validate field_id
        if (empty($_POST['field_id'])) {
            wp_send_json_error(array('message' => __('Field ID is required.', 'osna-wp-tools')));
        }

        $field_id = intval($_POST['field_id']);

        // Delete the field
        $result = $this->parent->delete_field($field_id);

        if ($result) {
            wp_send_json_success(array('message' => 'field_deleted'));
        } else {
            wp_send_json_error(array('message' => __('Failed to delete field. Please try again.', 'osna-wp-tools')));
        }
    }
}