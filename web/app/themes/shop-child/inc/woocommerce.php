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
 * Storefront V2 Batch A.2 UX correction — main-shop eyebrow only.
 *
 * A.1 also hook-inserted a one-line intro paragraph here and used CSS flex
 * `order` to visually place it after the H1 (this hook only fires before
 * the title). Live audit for A.2 flagged that whole mechanism as a warning
 * sign — the flex-order rules already needed a follow-up fix once
 * (category archives nest their sub-category container differently than
 * the main shop nests its top-level one) and were pure structural
 * overhead for one paragraph. WooCommerce already has a native, hook-free
 * slot for exactly this: the "Shop" page's own post content, rendered
 * automatically after the H1 by Botiga's own
 * botiga_woocommerce_product_archive_description() — unconditionally, no
 * flex-order needed, and it's the same mechanism category archives already
 * use for their own product_cat term description (contract §4.2 keeps that
 * separation). The intro copy itself now lives as WooCommerce Shop page
 * (post ID from wc_get_page_id('shop')) content, a content change, not a
 * template/hook one — see docs/STOREFRONT-V2-IMPLEMENTATION.md's A.2
 * amendment.
 *
 * The eyebrow alone has no such native slot, so it stays hooked to
 * botiga_before_shop_archive_title (inc/plugins/woocommerce/features/
 * wc-page-header.php) — its natural position, immediately before the H1,
 * needs no reordering at all.
 */
add_action('botiga_before_shop_archive_title', __NAMESPACE__ . '\\render_shop_archive_eyebrow');
function render_shop_archive_eyebrow(): void
{
    if (! is_shop()) {
        return;
    }

    printf(
        '<p class="lyli-shop-intro-eyebrow">%s</p>',
        esc_html__('Quà tặng handmade', 'shop-child')
    );
}

/**
 * Storefront V2 Batch A.2 — suppress category-navigation chips wherever
 * they wouldn't give the shopper an actual choice. Botiga's own
 * shop_archive_header_style_show_categories / _show_sub_categories theme
 * mods are boolean on/off switches with no count-awareness; filtering the
 * stored value through WordPress's own `theme_mod_{name}` core filter
 * (fired by every get_theme_mod() call, including Botiga's) lets a chip
 * row still render whenever it has 2+ real choices, and disappear
 * whenever it wouldn't — without touching Botiga's rendering code, and
 * without hardcoding any category name. Both counts intentionally reuse
 * the same hide_empty:true semantics Botiga's own query args use, so an
 * empty category never counts as a choice.
 */
add_filter('theme_mod_shop_archive_header_style_show_categories', __NAMESPACE__ . '\\suppress_single_category_nav');
function suppress_single_category_nav($value)
{
    if (! $value || ! is_shop()) {
        return $value;
    }

    $count = wp_count_terms('product_cat', ['parent' => 0, 'hide_empty' => true]);

    return (! is_wp_error($count) && $count >= 2) ? $value : false;
}

add_filter('theme_mod_shop_archive_header_style_show_sub_categories', __NAMESPACE__ . '\\suppress_single_subcategory_nav');
function suppress_single_subcategory_nav($value)
{
    if (! $value || ! (is_product_category() || is_product_tag() || is_product_taxonomy())) {
        return $value;
    }

    $term = get_queried_object();
    if (! $term instanceof \WP_Term) {
        return $value;
    }

    $count = wp_count_terms('product_cat', ['parent' => $term->term_id, 'hide_empty' => true]);

    return (! is_wp_error($count) && $count >= 2) ? $value : false;
}

/**
 * Storefront V2 Batch A.2 — concise Vietnamese result-count copy.
 * WooCommerce's loop/result-count.php template (a core template, not
 * overridden here) has no apply_filters() around its final string, only
 * _e()/_n()/_nx() calls — so the smallest scoped mechanism is a gettext
 * filter matched on exact domain + source string(s), the same technique
 * already used and documented for the cart "Shipment" string (contract
 * §12.1). Each filter below only ever matches its own exact source
 * string in the 'woocommerce' domain; nothing else can match.
 */
add_filter('gettext', __NAMESPACE__ . '\\simplify_single_result_count_text', 10, 3);
function simplify_single_result_count_text($translated, $text, $domain)
{
    if ($domain === 'woocommerce' && $text === 'Showing the single result') {
        return __('1 sản phẩm', 'shop-child');
    }

    return $translated;
}

add_filter('ngettext', __NAMESPACE__ . '\\simplify_all_results_count_text', 10, 5);
function simplify_all_results_count_text($translated, $single, $plural, $number, $domain)
{
    if ($domain === 'woocommerce' && $single === 'Showing all %1$d result' && $plural === 'Showing all %1$d results') {
        return __('%1$d sản phẩm', 'shop-child');
    }

    return $translated;
}

add_filter('ngettext_with_context', __NAMESPACE__ . '\\simplify_paginated_result_count_text', 10, 6);
function simplify_paginated_result_count_text($translated, $single, $plural, $number, $context, $domain)
{
    if (
        $domain === 'woocommerce'
        && $context === 'with first and last result'
        && $single === 'Showing %1$d&ndash;%2$d of %3$d result'
        && $plural === 'Showing %1$d&ndash;%2$d of %3$d results'
    ) {
        return __('%1$d&ndash;%2$d trong %3$d sản phẩm', 'shop-child');
    }

    return $translated;
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
