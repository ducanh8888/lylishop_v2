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

/**
 * Storefront V2 Batch A.1 corrective pass — main-shop editorial eyebrow +
 * one-line intro. Main shop only (is_shop()); category/tag archives use
 * their own product_cat term description via WooCommerce's existing
 * woocommerce_archive_description hook instead, per the frozen contract
 * (docs/STOREFRONT-V2-IMPLEMENTATION.md §4.2) — no duplicate intro there.
 *
 * Originally hooked to woocommerce_before_shop_loop (priority 15), which
 * live-verified to render in a completely different DOM branch than the
 * archive header (.woocommerce-page-header is a child of #page.site; the
 * shop loop lives inside .row.main-row > .site-main, a structural sibling
 * several levels over) — producing a disconnected ~150px gap between the
 * header and the intro on production. Botiga exposes exactly one
 * do_action inside the header itself, right before the H1:
 * botiga_before_shop_archive_title (inc/plugins/woocommerce/features/
 * wc-page-header.php). Hooking there instead keeps eyebrow+intro+H1+chips
 * inside the same header block. Visual order (eyebrow, H1, intro) is
 * achieved with CSS flex `order` in style.css, since the hook only fires
 * before the H1 — see the matching comment there.
 */
add_action('botiga_before_shop_archive_title', __NAMESPACE__ . '\\render_shop_archive_intro');
function render_shop_archive_intro(): void
{
    if (! is_shop()) {
        return;
    }

    printf(
        '<p class="lyli-shop-intro-eyebrow">%1$s</p><p class="lyli-shop-intro-copy">%2$s</p>',
        esc_html__('Quà tặng handmade', 'shop-child'),
        esc_html__('Móc khóa len thủ công, làm theo yêu cầu, giao trong 1–3 ngày.', 'shop-child')
    );
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
