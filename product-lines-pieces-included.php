<?php
/**
 * Plugin Name: Product Lines Pieces Included
 * Description: Displays the SKUs listed in a product's Pieces Included metadata.
 * Version:     1.3.0
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

define( 'PLPI_VERSION', '1.3.0' );
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
 * Format the included-product SKUs for display.
 *
 * @param WC_Product $product Product being displayed.
 *
 * @return array<int, string>
 */
function get_display_pieces_included_skus( WC_Product $product ): array {

    $display_skus = [];

    foreach ( get_pieces_included_skus( $product ) as $sku => $quantity ) {
        $display_skus[] = 1 < $quantity
            ? sprintf( '%s (qty%d)', $sku, $quantity )
            : $sku;
    }

    return $display_skus;
}

/**
 * Get the current WooCommerce product.
 *
 * @return WC_Product|null
 */
function get_current_product(): ?WC_Product {

    global $product;

    if ( $product instanceof WC_Product ) {
        return $product;
    }

    $product = wc_get_product( get_the_ID() );

    return $product instanceof WC_Product ? $product : null;
}

/**
 * Display the included-product SKUs below the current product title.
 *
 * @return void
 */
function display_pieces_included_skus(): void {

    $product = get_current_product();

    if ( null === $product ) {
        return;
    }

    $display_skus = get_display_pieces_included_skus( $product );

    if ( empty( $display_skus ) ) {
        return;
    }

    printf(
        '<div class="product-lines-pieces-included">%s %s</div>',
        esc_html__( 'SKU(s):', 'product-lines-pieces-included' ),
        esc_html( implode( ', ', $display_skus ) )
    );
}

/**
 * Display the product SKU and included-product SKUs in a shortcode location.
 *
 * Use [product_lines_sku] in an Elementor Shortcode widget or other shortcode
 * location. The product's stored SKU is not modified.
 *
 * @return string
 */
function display_combined_skus_shortcode(): string {

    $product = get_current_product();

    if ( null === $product ) {
        return '';
    }

    $display_skus = get_display_pieces_included_skus( $product );
    $product_sku  = trim( $product->get_sku( 'edit' ) );

    if ( '' !== $product_sku ) {
        array_unshift( $display_skus, $product_sku );
    }

    if ( empty( $display_skus ) ) {
        return '';
    }

    return sprintf(
        '<span class="sku_wrapper product-lines-pieces-included">%s <span class="sku">%s</span></span>',
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
    add_shortcode( 'product_lines_sku', __NAMESPACE__ . '\\display_combined_skus_shortcode' );
}

add_action( 'plugins_loaded', __NAMESPACE__ . '\\register_hooks' );
