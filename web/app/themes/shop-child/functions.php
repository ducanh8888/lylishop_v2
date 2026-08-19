<?php
/**
 * Lyli Shop child theme — functions.
 * Presentation only (PLAN.md §6.1 / THEME-DECISION.md §8):
 * brand design tokens, typography, spacing, homepage presentation,
 * product card/archive/single styling, Classic Cart & Checkout styling,
 * responsive/accessibility, controlled Gutenberg block patterns.
 * Contains NO order/payment/voucher/business logic.
 *
 * Parent: Botiga Free 2.4.7 (docs/THEME-DECISION.md, accepted 2026-08-04).
 *
 * Enqueue note (verified against real Botiga 2.4.7 source):
 * Botiga's botiga_style_css() (wp_enqueue_scripts, priority 12) enqueues
 * get_stylesheet_uri() under `botiga-style` — which automatically loads THIS
 * theme's style.css once active. We therefore must NOT re-enqueue style.css
 * manually (would double-load). We DO enqueue the Google Fonts here and keep
 * them functionally separate.
 */

namespace ShopChild;

if (! defined('ABSPATH')) {
    exit;
}

require_once __DIR__ . '/inc/design-tokens.php';
require_once __DIR__ . '/inc/theme-runtime.php';
require_once __DIR__ . '/inc/enqueue.php';
require_once __DIR__ . '/inc/announcement.php';
require_once __DIR__ . '/inc/footer.php';
require_once __DIR__ . '/inc/accessibility.php';
require_once __DIR__ . '/inc/block-patterns.php';
require_once __DIR__ . '/inc/content-pages.php';
require_once __DIR__ . '/inc/woocommerce/archive.php';
require_once __DIR__ . '/inc/woocommerce/product-card.php';
require_once __DIR__ . '/inc/woocommerce/single-product.php';
require_once __DIR__ . '/inc/woocommerce/commerce-ui.php';
require_once __DIR__ . '/inc/botiga-admin.php';
require_once __DIR__ . '/inc/mobile-header.php';

/**
 * Theme setup — narrow additions only.
 * Because this is a child theme of Botiga, we do NOT re-declare
 * add_theme_support() items Botiga already provides.
 */
add_action('after_setup_theme', __NAMESPACE__ . '\\setup');
function setup(): void
{
    // Editor color presets + typography presets come from theme.json
    // (canonical source for WP 5.9+). No add_theme_support needed here.

    // Load Google Fonts into the classic editor so previews match the frontend.
    // Fonts are runtime-delivered (no committed binaries) per THEME-DECISION.md §11.
    add_editor_style([
        \ShopChild\google_fonts_url(),
        'style.css',
        'editor-style.css',
    ]);
}

/**
 * Content width — child won't override Botiga's width, but we define a safe
 * fallback for blocks that query it when the parent isn't loaded yet.
 */
add_action('after_setup_theme', __NAMESPACE__ . '\\content_width', 5);
function content_width(): void
{
    $GLOBALS['content_width'] = 1140;
}
