<?php
/**
 * Lyli Shop — WooCommerce presentation hooks (narrow, markup-level only).
 *
 * Verified: Botiga 2.4.7 integrates WooCommerce via hooks only — it ships NO
 * woocommerce/ template override directory. We keep that discipline: no
 * template copies, no order/payment/calculation logic here.
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
 * Add a small "Custom order available" hint on the single product page only
 * when the owner has configured the Custom-Order CTA site settings.
 * Pure presentation; no business logic.
 */
add_action('woocommerce_single_product_summary', __NAMESPACE__ . '\\maybe_render_custom_order_hint', 40);
function maybe_render_custom_order_hint(): void
{
    if (! function_exists('LyliSiteSettings\\get_custom_order_label')) {
        return;
    }

    $label = \LyliSiteSettings\get_custom_order_label();
    $url   = \LyliSiteSettings\get_custom_order_url();
    if ($label === '' || $url === '') {
        return;
    }

    printf(
        '<p class="lyli-custom-order-hint"><a href="%1$s">%2$s</a></p>',
        esc_url($url),
        esc_html($label)
    );
}
