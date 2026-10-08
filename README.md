# Product Lines Pieces Included

Displays the SKU values for components included with a WooCommerce product.

## Requirements

- WordPress
- WooCommerce

## Usage

On the **General** tab of the WooCommerce product editor, use the **Pieces Included** field to enter one SKU per line. The values are stored in the parent product's `_pieces_included` post meta as an array of SKU strings.

An SKU does not need to correspond to an existing WooCommerce product. This allows the relationship to be entered before that product is imported or added to the site.

The same value can also be maintained programmatically. For example:

```php
update_post_meta( $product_id, '_pieces_included', [ '632983', '632984', '632984' ] );
```

On the single-product page, the plugin displays the stored piece SKUs below the product title:

```
SKU(s): 4234272, 4214218 (qty2)
```

Every non-empty SKU is displayed, including SKUs for products that do not yet exist in WooCommerce. Repeated SKUs are consolidated and shown with their quantity.

For a custom Elementor single-product template, add a **Shortcode** widget in the desired SKU location and enter:

```
[product_lines_sku]
```

The shortcode displays the product's SKU followed by its included-piece SKUs. It does not modify the stored WooCommerce SKU or affect SKU output elsewhere.

## Installation

1. Install and activate WooCommerce.
2. Copy this plugin directory into `wp-content/plugins/`.
3. Activate **Product Lines Pieces Included** from the WordPress Plugins screen.
