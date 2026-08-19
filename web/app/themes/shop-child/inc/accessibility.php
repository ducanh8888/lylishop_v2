<?php
/**
 * Lyli Shop — accessibility behavior.
 *
 * Verified against Botiga 2.4.7 source (release inspection 2026-08-06):
 *   - Botiga header.php:37 already outputs a skip link
 *     (`<a class="skip-link screen-reader-text" href="#primary">`).
 *   - Botiga searchform.php already includes `screen-reader-text` labels for
 *     both the default and WooCommerce product search forms.
 *
 * We therefore deliberately add NO duplicate skip link and NO extra search
 * label. This file is a lightweight container for presentation-level
 * accessibility niceties that are provably non-duplicative.
 */

namespace ShopChild\Accessibility;

if (! defined('ABSPATH')) {
    exit;
}

/**
 * Post-Storefront-V2 UX audit UX-004/UX-003 — Botiga theme chrome strings
 * that leak English on an otherwise fully-Vietnamese site. These are the
 * FIRST thing a screen-reader/keyboard user encounters on every page (skip
 * link) and the entirety of the 404 page's own content, so the fix lives
 * here alongside the file's existing skip-link documentation rather than
 * in a WooCommerce-specific module — none of these strings are Woo's.
 *
 * Exact source strings and line numbers, verified against the deployed
 * Botiga 2.4.7 source (not assumed):
 *   - header.php:37                — 'Skip to content'
 *   - searchform.php:54             — 'Search products&hellip;' (used by
 *                                      both the header search flyout and
 *                                      the 404 page's own search form —
 *                                      one fix covers both)
 *   - inc/template-tags.php:632     — 'Oops! That page can&rsquo;t be
 *                                      found.' (404 heading)
 *   - inc/template-tags.php:636     — 'It looks like nothing was found at
 *                                      this location. Maybe try one of the
 *                                      links below or a search?' (404 body)
 *   - inc/template-tags.php:646     — 'Most Popular' (404 page's
 *                                      best-sellers section heading)
 *
 * Each filter matches domain + exact source string simultaneously — cannot
 * match or alter any other string, in Botiga or any other textdomain. Not
 * a literal/robotic translation: rephrased as natural, concise Vietnamese
 * rather than a word-for-word mirror of the English original.
 */
add_filter('gettext', __NAMESPACE__ . '\\translate_botiga_chrome_strings', 10, 3);
function translate_botiga_chrome_strings(string $translated, string $text, string $domain): string
{
    if ($domain !== 'botiga') {
        return $translated;
    }

    // Source strings are matched as literally written in Botiga's PHP
    // (esc_html_e()/esc_attr__() receive the raw HTML-entity text, e.g.
    // "&rsquo;"/"&hellip;", not a decoded Unicode character — gettext
    // filters see that same raw text, before the browser's HTML parser
    // ever decodes it).
    $map = [
        'Skip to content' => __('Bỏ qua đến nội dung', 'shop-child'),
        'Search products&hellip;' => __('Tìm sản phẩm…', 'shop-child'),
        'Oops! That page can&rsquo;t be found.' => __('Trang này không tồn tại', 'shop-child'),
        'It looks like nothing was found at this location. Maybe try one of the links below or a search?' =>
            __('Có thể trang đã bị xoá hoặc đường dẫn không đúng. Hãy thử tìm kiếm hoặc chọn một liên kết bên dưới.', 'shop-child'),
        'Most Popular' => __('Sản phẩm nổi bật', 'shop-child'),
    ];

    return $map[$text] ?? $translated;
}

/**
 * Add an accessible "Đặt mẫu theo yêu cầu" context label to the cart icon
 * for screen readers — only when WooCommerce icons are rendered by Botiga's
 * header builder and the site has Custom Order CTA enabled.
 *
 * (Placeholder hook — kept narrow and guarded; nothing synthetic is emitted
 * unless real site settings are present.)
 */