<?php
/**
 * Lyli Shop — WooCommerce shop/category archive presentation.
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

/**
 * Storefront V2 full-review pass — narrow correctness fix. The original
 * guard covered is_product_category() || is_product_tag() ||
 * is_product_taxonomy() (mirroring Botiga's own sub-category query, which
 * has the same quirk), but term IDs are shared across taxonomies in
 * WordPress — a product_tag or attribute-taxonomy term's term_id has no
 * relationship to product_cat's parent/child structure. Live-verified:
 * wp_count_terms('product_cat', ['parent' => $tag_term_id]) on a real,
 * reachable, non-empty tag archive (/product-tag/capybara-handmade/,
 * term_id 59) returned 0 only because no category happens to have
 * parent=59 — coincidence, not correctness. As the catalog grows and
 * term IDs increase across taxonomies sharing the same ID space, that
 * coincidence could flip and show unrelated categories as if they were
 * "children" of the current tag. Sub-category chips are only a coherent
 * concept on an actual product_cat term, so this now only ever evaluates
 * the count there — every other taxonomy context suppresses outright.
 */
add_filter('theme_mod_shop_archive_header_style_show_sub_categories', __NAMESPACE__ . '\\suppress_single_subcategory_nav');
function suppress_single_subcategory_nav($value)
{
    if (! $value || ! is_product_category()) {
        return false;
    }

    $term = get_queried_object();
    if (! $term instanceof \WP_Term || $term->taxonomy !== 'product_cat') {
        return false;
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
 * Post-Storefront-V2 soft catalog navigation pass (owner-reported: category
 * drill-down felt rigid, no deterministic way back up/sideways beyond the
 * breadcrumb; docs/UX-AUDIT-POST-STOREFRONT-V2-2026-08-19.md addendum).
 *
 * Root cause: Botiga's own sub-category chips
 * (botiga_shop_page_header_sub_category_links(), wc-page-header.php) only
 * ever list the DIRECT CHILDREN of the current term, and only render at all
 * when suppress_single_subcategory_nav() above allows it (≥2 populated
 * children). A leaf category — or any category with fewer than 2 populated
 * children — therefore shows zero taxonomy navigation beyond the
 * breadcrumb: a real dead end, live-confirmed on `Lyli Charm`, `Lyli Tiny`,
 * `Hoa hướng dương`, `Hoa tulip`, `Hoa giỏ` (every current leaf).
 *
 * This adds a second, independent navigation concern Botiga's own gate
 * cannot express: moving UP to the parent (or shop root) and SIDEWAYS to
 * populated siblings — not DOWN to children, which Botiga's existing,
 * unmodified mechanism already owns. Deliberately hooked to WooCommerce's
 * own `woocommerce_before_shop_loop` (not Botiga's custom header function)
 * at an early priority so it always fires on every category archive
 * regardless of Botiga's show_sub_categories gating — the two systems are
 * independent by design (task brief §8/§19), not a replacement for one
 * another. "Up" is shown unconditionally (ancestor/exit navigation is not
 * subject to the "does this give a real choice" gate a lone child chip
 * would fail); siblings render only when ≥2 populated terms share the same
 * parent (current term + at least one real alternative), reusing the same
 * count-gating philosophy already established for child chips. Fully
 * derived from the live taxonomy graph via get_queried_object()/get_terms()
 * — no hardcoded category name, so it keeps working as the catalog grows.
 *
 * Post-remediation-pass fix (UX-014 re-test): `woocommerce_before_shop_loop`
 * turned out to sit inside WooCommerce core's `if (woocommerce_product_loop())`
 * branch — it never fires when the current archive has zero products,
 * confirmed live via `do_action` on `/product-category/lyli-signature/`
 * (0 products): the function produced correct output when called directly,
 * but never actually ran through the real page request. That is exactly
 * the single worst dead-end case this was built for, so it was silently
 * not helping there at all. Also hooked to WooCommerce's own
 * `woocommerce_no_products_found` (fires in the sibling `else` branch,
 * before the native "no products found" notice) so the same nav renders
 * on an empty category too — the function is identical either way, only
 * one of the two hooks ever fires for a given request, so there is no
 * duplicate-render risk.
 */
add_action('woocommerce_before_shop_loop', __NAMESPACE__ . '\\render_taxonomy_nav', 5);
add_action('woocommerce_no_products_found', __NAMESPACE__ . '\\render_taxonomy_nav', 5);
function render_taxonomy_nav(): void
{
    if (! is_product_category()) {
        return;
    }

    $term = get_queried_object();
    if (! $term instanceof \WP_Term || $term->taxonomy !== 'product_cat') {
        return;
    }

    if ($term->parent) {
        $parent = get_term($term->parent, 'product_cat');
        if (! $parent instanceof \WP_Term) {
            return;
        }
        $up_url = get_term_link($parent);
        $up_label = $parent->name;
    } else {
        $shop_page_id = wc_get_page_id('shop');
        $up_url = $shop_page_id > 0 ? get_permalink($shop_page_id) : home_url('/');
        $up_label = __('Cửa hàng', 'shop-child');
    }

    if (is_wp_error($up_url) || ! $up_url) {
        return;
    }

    $siblings = get_terms([
        'taxonomy' => 'product_cat',
        'parent' => $term->parent,
        'hide_empty' => true,
    ]);
    $siblings = is_wp_error($siblings) ? [] : $siblings;

    printf(
        '<nav class="lyli-taxonomy-nav" aria-label="%s">',
        esc_attr__('Điều hướng danh mục', 'shop-child')
    );
    printf(
        '<a class="lyli-taxonomy-nav-up" href="%s"><span aria-hidden="true">&larr;</span> %s</a>',
        esc_url($up_url),
        esc_html($up_label)
    );

    if (count($siblings) >= 2) {
        echo '<span class="lyli-taxonomy-nav-siblings">';
        foreach ($siblings as $sibling) {
            $sibling_url = get_term_link($sibling);
            if (is_wp_error($sibling_url)) {
                continue;
            }
            $is_current = $sibling->term_id === $term->term_id;
            printf(
                '<a class="category-button lyli-taxonomy-nav-chip%s" href="%s"%s>%s</a>',
                $is_current ? ' is-current' : '',
                esc_url($sibling_url),
                $is_current ? ' aria-current="page"' : '',
                esc_html($sibling->name)
            );
        }
        echo '</span>';
    }

    echo '</nav>';
}

/**
 * Post-Storefront-V2 UX audit UX-015 — a no-result product search showed
 * only WooCommerce's generic native notice ("Không tìm thấy sản phẩm nào
 * khớp với lựa chọn của bạn.") with no explicit next step. Scoped to
 * is_search() specifically — a no-result *category* archive is already
 * covered by render_taxonomy_nav()'s up/sibling navigation (UX-014), so
 * this doesn't duplicate that. Hooked after WooCommerce's own notice
 * (default priority 10) so this reads as a follow-on suggestion, not a
 * competing message.
 */
add_action('woocommerce_no_products_found', __NAMESPACE__ . '\\render_search_no_results_guidance', 15);
function render_search_no_results_guidance(): void
{
    if (! is_search()) {
        return;
    }

    $shop_url = wc_get_page_id('shop') > 0 ? get_permalink(wc_get_page_id('shop')) : home_url('/');

    printf(
        '<p class="lyli-search-no-results-hint">%s <a href="%s">%s</a>.</p>',
        esc_html__('Thử từ khóa khác hoặc', 'shop-child'),
        esc_url($shop_url),
        esc_html__('xem tất cả sản phẩm', 'shop-child')
    );
}
