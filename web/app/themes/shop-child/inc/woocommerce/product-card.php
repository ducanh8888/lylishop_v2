<?php
/**
 * Lyli Shop — WooCommerce product-card (loop) presentation.
 * Split out of inc/woocommerce.php per the Storefront V2 contract's file
 * ownership rule (docs/STOREFRONT-V2-IMPLEMENTATION.md §16/§13a) once
 * that file grew past its size/concern-mixing trigger. Presentation
 * only — no query, order, or business logic.
 */

namespace ShopChild\Woo;

if (! defined('ABSPATH')) {
    exit;
}

/**
 * One-image support: add `lyli-single-image-product` class to the product
 * wrapper when a product has exactly one image, and `lyli-missing-image-product`
 * when it has none (kept draft per policy, but the class helps CSS stability).
 */
add_filter('post_class', __NAMESPACE__ . '\\single_image_product_class', 10, 3);
function single_image_product_class(array $classes, mixed $class, int $post_id): array
{
    if (! function_exists('wc_get_product')) {
        return $classes;
    }

    $product = wc_get_product($post_id);
    if (! $product) {
        return $classes;
    }

    $image   = $product->get_image_id();
    $gallery = $product->get_gallery_image_ids();

    if ($image && empty($gallery)) {
        $classes[] = 'lyli-single-image-product';
    } elseif (! $image) {
        $classes[] = 'lyli-missing-image-product';
    }

    return $classes;
}

/**
 * Storefront V2 Batch A — stock-derived product-card metadata line.
 * Uses Botiga's own documented `botiga_loop_product_elements` filter
 * (inc/plugins/woocommerce/features/product-card.php) rather than a raw
 * hook, so it is appended to the same element list Botiga already builds
 * the card from (title, price, ...), printing after price and before the
 * add-to-cart button in DOM order.
 *
 * Source is $product->get_stock_status() only — a native WooCommerce
 * field every product has — never inferred from title/content/tags.
 * "Handmade" is intentionally not included here: it remains DEFERRED
 * pending a founder decision on a permanent per-product tagging policy
 * (contract §4.3).
 */
add_filter('botiga_loop_product_elements', __NAMESPACE__ . '\\add_card_metadata_element');
function add_card_metadata_element(array $elements): array
{
    $elements[] = __NAMESPACE__ . '\\render_card_stock_metadata';
    return $elements;
}

function render_card_stock_metadata(): void
{
    global $product;

    if (! $product instanceof \WC_Product) {
        return;
    }

    $labels = [
        'instock'     => __('Có sẵn', 'shop-child'),
        'outofstock'  => __('Hết hàng', 'shop-child'),
        'onbackorder' => __('Đặt trước', 'shop-child'),
    ];

    $status = $product->get_stock_status();
    if (! isset($labels[$status])) {
        return;
    }

    printf('<p class="lyli-card-metadata">%s</p>', esc_html($labels[$status]));
}
