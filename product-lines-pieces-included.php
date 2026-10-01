<?php
/**
 * Plugin Name: Product Lines Pieces Included
 * Description: Displays the SKUs listed in a product's Pieces Included metadata.
 * Version:     1.2.0
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

define( 'PLPI_VERSION', '1.2.0' );
define( 'PLPI_FILE', __FILE__ );
define( 'PLPI_PATH', plugin_dir_path( __FILE__ ) );

/**
 * Get the SKUs and quantities included with a product.
 *
 * The _pieces_included meta value contains SKU values. A SKU may refer to a
 * product that has not yet been added to WooCommerce.
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

    foreach ( $pieces_included as $piece_sku ) {

        if ( ! is_scalar( $piece_sku ) ) {
            continue;
        }

        $sku = trim( (string) $piece_sku );

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
 * Display the Pieces Included SKU setting.
 *
 * @return void
 */
function display_pieces_included_field(): void {

    global $post;

    $pieces_included = $post ? get_post_meta( $post->ID, '_pieces_included', true ) : [];
    $pieces_included = is_array( $pieces_included ) ? $pieces_included : [];
    $pieces_included = array_filter( $pieces_included, 'is_scalar' );

    ?>
    <p class="form-field _pieces_included_field">
        <label for="_pieces_included"><?php esc_html_e( 'Pieces Included', 'product-lines-pieces-included' ); ?></label>
        <textarea
            id="_pieces_included"
            name="_pieces_included"
            rows="5"
            style="width: 50%;"
        ><?php echo esc_textarea( implode( "\n", $pieces_included ) ); ?></textarea>
        <?php echo wc_help_tip( __( 'Enter one SKU per line. SKUs for products not yet in WooCommerce are retained.', 'product-lines-pieces-included' ) ); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped ?>
    </p>
    <?php
}

/**
 * Save the Pieces Included SKU setting.
 *
 * @param int $product_id Product ID being saved.
 *
 * @return void
 */
function save_pieces_included_field( int $product_id ): void {

    if ( ! current_user_can( 'edit_post', $product_id ) ) {
        return;
    }

    $pieces_included = isset( $_POST['_pieces_included'] ) && is_string( $_POST['_pieces_included'] )
        ? wp_unslash( $_POST['_pieces_included'] )
        : '';

    $pieces_included = preg_split( '/[\r\n,]+/', $pieces_included );
    $pieces_included = is_array( $pieces_included ) ? $pieces_included : [];
    $pieces_included = array_map( 'sanitize_text_field', $pieces_included );
    $pieces_included = array_filter( $pieces_included, static function( string $piece_sku ): bool {
        return '' !== trim( $piece_sku );
    } );
    $pieces_included = array_map( 'trim', $pieces_included );

    $product = wc_get_product( $product_id );

    if ( ! $product instanceof WC_Product ) {
        return;
    }

    $product->update_meta_data( '_pieces_included', array_values( $pieces_included ) );
    $product->save();
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
    add_action( 'woocommerce_product_options_general_product_data', __NAMESPACE__ . '\\display_pieces_included_field' );
    add_action( 'woocommerce_process_product_meta', __NAMESPACE__ . '\\save_pieces_included_field' );
}

add_action( 'plugins_loaded', __NAMESPACE__ . '\\register_hooks' );
