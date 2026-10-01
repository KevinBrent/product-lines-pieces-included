# Product Lines Pieces Included

Displays the SKU numbers for component products included with a WooCommerce product.

## Requirements

- WordPress
- WooCommerce

## Usage

Add product IDs to the parent product's `_pieces_included` post meta as an array. For example:

```php
update_post_meta( $product_id, '_pieces_included', [ 632983, 632984, 632984 ] );
```

On the single-product page, the plugin resolves those product IDs to their SKUs and displays the result below the product title:

```
SKU(s): 4234272, 4214218 (qty2)
```

Missing products and products without an SKU are not displayed. Repeated SKUs are consolidated and shown with their quantity.

## Installation

1. Install and activate WooCommerce.
2. Copy this plugin directory into `wp-content/plugins/`.
3. Activate **Product Lines Pieces Included** from the WordPress Plugins screen.
