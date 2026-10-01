<?php
/**
 * Plugin Name: Product Lines Pieces Included
 * Description: Displays the SKUs for products listed in a product's Pieces Included metadata.
 * Version:     1.0.0
 * Author:      Kevin Brent
 * License:     GPL-2.0-or-later
 * Text Domain: product-lines-pieces-included
 *
 * @author Kevin Brent
 *
 * @package Product_Lines_Pieces_Included
 */

namespace Product_Lines_Pieces_Included;

use WC_Product;

if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

define( 'PLPI_VERSION', '1.0.0' );
define( 'PLPI_FILE', __FILE__ );
define( 'PLPI_PATH', plugin_dir_path( __FILE__ ) );

/**
 * Get the SKUs and quantities for products included with a product.
 *
 * The _pieces_included meta value is expected to contain WooCommerce product IDs.
 *
 * @param WC_Product $product Product being displayed.
 *
 * @return array<string, int>
 */
function get_pieces_included_skus( WC_Product $product ): array {

    $pieces_included = $product->get_meta( '_pieces_included', true );

    if ( ! is_array( $pieces_included ) ) {
        return [];
    }

    $skus = [];

    foreach ( $pieces_included as $piece_id ) {

        $piece = wc_get_product( absint( $piece_id ) );

        if ( ! $piece instanceof WC_Product ) {
            continue;
        }

        $sku = $piece->get_sku();

        if ( '' === $sku ) {
            continue;
        }

        if ( ! isset( $skus[ $sku ] ) ) {
            $skus[ $sku ] = 0;
        }

        $skus[ $sku ]++;

    }

    return $skus;
}

/**
 * Display the included-product SKUs below the current product title.
 *
 * @return void
 */
function display_pieces_included_skus(): void {

    global $product;

    if ( ! $product instanceof WC_Product ) {
        return;
    }

    $skus = get_pieces_included_skus( $product );

    if ( empty( $skus ) ) {
        return;
    }

    $display_skus = [];

    foreach ( $skus as $sku => $quantity ) {
        $display_skus[] = 1 < $quantity
            ? sprintf( '%s (qty%d)', $sku, $quantity )
            : $sku;
    }

    printf(
        '<div class="product-lines-pieces-included">%s %s</div>',
        esc_html__( 'SKU(s):', 'product-lines-pieces-included' ),
        esc_html( implode( ', ', $display_skus ) )
    );
}

/**
 * Register WooCommerce hooks after plugins load.
 *
 * @return void
 */
function register_hooks(): void {

    if ( ! class_exists( 'WooCommerce' ) ) {
        return;
    }

    add_action( 'woocommerce_single_product_summary', __NAMESPACE__ . '\\display_pieces_included_skus', 6 );
}

add_action( 'plugins_loaded', __NAMESPACE__ . '\\register_hooks' );
