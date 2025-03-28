# OSNA WP Tools

A collection of powerful tools for WordPress, featuring an Apple-like design with Tailwind CSS.

## Features

### Ultimate Sliders

Create beautiful, responsive sliders for your splash pages with support for:

- Images and videos
- Custom titles and descriptions
- Call-to-action buttons
- Custom background and text colors
- Autoplay and transition effects
- Navigation controls (dots and arrows)

### Product Custom Fields

Add custom fields to WooCommerce products with support for:

- Multiple field types (text, textarea, select, checkbox, date, number)
- Field validation and default values
- Product type-specific fields (Simple, Variable, Variations, etc.)
- Display on product pages and in cart/checkout
- REST API and GraphQL integration

### Payment Gateways

- Process payments through multiple providers including Lygos and Stripe
- Custom API endpoint for payment processing
- WooCommerce integration

## Installation

1. Upload the `osna-wp-tools` folder to the `/wp-content/plugins/` directory
2. Activate the plugin through the 'Plugins' menu in WordPress
3. Access the plugin via the 'OSNA Tools' menu in the WordPress admin

## Requirements

- WordPress 5.8 or higher
- PHP 7.4 or higher
- WooCommerce 5.0 or higher (for Product Custom Fields and Payment Gateways)

## GraphQL Integration

Ultimate Sliders and Product Custom Fields are available through GraphQL if you have the WPGraphQL plugin installed. This allows you to easily fetch data for your Next.js applications.

Example GraphQL query for Product Custom Fields:

```graphql
query GetProductWithCustomFields {
  product(id: "product-slug", idType: SLUG) {
    id
    name
    customFields {
      fieldName
      label
      value
      displayValue
    }
  }
}
```

## REST API Endpoints

- `/wp-json/osna/v1/sliders` - Get all sliders
- `/wp-json/osna/v1/sliders/{id}` - Get a specific slider by ID
- `/wp-json/osna/v1/process-payment` - Process a payment
- `/wp-json/osna/v1/product-custom-fields` - Get all product custom field definitions
- `/wp-json/osna/v1/product-custom-fields/{id}` - Get a specific field definition
- `/wp-json/osna/v1/products/{product_id}/custom-fields` - Get custom field values for a product

## License

This plugin is licensed under the GPL v2 or later.

## Credits

Developed by OSNA Team