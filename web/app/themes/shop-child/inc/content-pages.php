<?php
/**
 * Lyli Shop — long-form page content presentation.
 * A narrow, distinct concern from WooCommerce archive/product/cart
 * presentation (inc/woocommerce/*) or footer/accessibility chrome — static
 * WP `page` content (policies, brand story) that reads as prose rather
 * than a structured block layout.
 */

namespace ShopChild\ContentPages;

if (! defined('ABSPATH')) {
    exit;
}

/**
 * Post-Storefront-V2 UX audit UX-009 — generic WordPress page content
 * (`.entry-content`) rendered at the full 1110px container width at
 * 1440px (~133 characters/line), measurably wider than the blog single-
 * post template's own 730px (~88 characters/line) for equivalent
 * long-form reading content. Live-verified this affects every page using
 * Botiga's default page template — not just the 4 legal/policy pages, but
 * every plain WP page.
 *
 * Deliberately NOT a blanket fix for every WordPress page (task brief:
 * "do NOT globally constrain every normal WordPress page blindly") —
 * scoped to the pages that are genuinely dense prose, confirmed by
 * reading each candidate live rather than assuming from its slug:
 *   - the 4 policy pages (privacy, returns, shipping, terms) — dense
 *     paragraph-after-paragraph legal text
 *   - "Giới thiệu" (About) — the brand-story page, same prose density
 * "Liên hệ" (short contact info, not dense prose) and "Đặt mẫu theo yêu
 * cầu" (a structured, multi-column card layout, not a wall of text) were
 * checked live and excluded — narrowing their already-short or
 * already-columned content would add a constraint without a real problem
 * to solve.
 *
 * Looked up by slug via is_page(), not hardcoded post IDs — resilient to
 * the pages being recreated. These 4+1 slugs are fixed, real legal/brand
 * pages (not an open-ended, owner-growing taxonomy like product
 * categories), so referencing them by slug here is a reasonable, narrow
 * exception to the "no hardcoding" convention used for taxonomy work.
 */
add_filter('body_class', __NAMESPACE__ . '\\add_readable_content_class');
function add_readable_content_class(array $classes): array
{
    $slugs = [
        'chinh-sach-van-chuyen',
        'chinh-sach-doi-tra',
        'chinh-sach-bao-mat',
        'dieu-khoan',
        'gioi-thieu',
    ];

    if (is_page($slugs)) {
        $classes[] = 'lyli-readable-content';
    }

    return $classes;
}
