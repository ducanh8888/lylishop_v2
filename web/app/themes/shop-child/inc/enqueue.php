<?php
/**
 * Lyli Shop — asset enqueue.
 * Only loads what Botiga does not already provide:
 *   1. Google Fonts (runtime, no committed binaries).
 *   The child's own style.css is auto-loaded by Botiga's `botiga-style`
 *   handle (verified against Botiga 2.4.7 source — do not double-enqueue).
 *   Botiga assigns its parent version to that handle, so we replace only the
 *   registered version with the child file timestamp for reliable cache busting.
 */

namespace ShopChild\Assets;

if (! defined('ABSPATH')) {
    exit;
}

/**
 * Enqueue the Google Fonts stylesheet (frontend).
 * The URL builder function lives in the ShopChild root namespace.
 */
add_action('wp_enqueue_scripts', __NAMESPACE__ . '\\enqueue_google_fonts', 11);
function enqueue_google_fonts(): void
{
    wp_enqueue_style(
        'lyli-google-fonts',
        \ShopChild\google_fonts_url(),
        [],
        null
    );
}

/**
 * Version Botiga's existing child stylesheet handle without enqueueing a
 * second copy. This runs after Botiga registers the handle at priority 12.
 */
add_action('wp_enqueue_scripts', __NAMESPACE__ . '\\version_child_stylesheet', 13);
function version_child_stylesheet(): void
{
    $styles = wp_styles();
    if (! isset($styles->registered['botiga-style'])) {
        return;
    }

    $modified = filemtime(get_stylesheet_directory() . '/style.css');
    if ($modified !== false) {
        $styles->registered['botiga-style']->ver = (string) $modified;
    }
}

/**
 * Preconnect to improve font delivery.
 */
add_filter('wp_resource_hints', __NAMESPACE__ . '\\font_preconnect', 10, 2);
function font_preconnect(array $urls, string $relation_type): array
{
    if ('preconnect' === $relation_type) {
        $urls[] = ['href' => 'https://fonts.googleapis.com'];
        $urls[] = ['href' => 'https://fonts.gstatic.com', 'crossorigin' => 'anonymous'];
    }
    return $urls;
}

/**
 * Section-level scroll reveal (category/USP/story/final-CTA). Vanilla JS,
 * no dependency, deferred. The script itself is solely responsible for
 * gating content on prefers-reduced-motion / IntersectionObserver support
 * and for adding the class that hides content before revealing it — if
 * this file fails to load or execute for any reason, nothing ever gets
 * hidden, so the page stays fully usable without it.
 */
add_action('wp_enqueue_scripts', __NAMESPACE__ . '\\enqueue_reveal_script', 14);
function enqueue_reveal_script(): void
{
    $path = get_stylesheet_directory() . '/assets/js/reveal.js';
    $modified = file_exists($path) ? filemtime($path) : false;

    wp_enqueue_script(
        'lyli-reveal',
        get_stylesheet_directory_uri() . '/assets/js/reveal.js',
        [],
        $modified !== false ? (string) $modified : null,
        ['strategy' => 'defer']
    );
}

/**
 * Cart badge pulse. Depends on jQuery only because WooCommerce's own
 * add-to-cart script dispatches `added_to_cart` as a jQuery event on
 * document.body — jQuery is already loaded by WooCommerce on every page
 * with a cart icon, so this adds no new dependency.
 */
add_action('wp_enqueue_scripts', __NAMESPACE__ . '\\enqueue_cart_badge_script', 14);
function enqueue_cart_badge_script(): void
{
    $path = get_stylesheet_directory() . '/assets/js/cart-badge.js';
    $modified = file_exists($path) ? filemtime($path) : false;

    wp_enqueue_script(
        'lyli-cart-badge',
        get_stylesheet_directory_uri() . '/assets/js/cart-badge.js',
        ['jquery'],
        $modified !== false ? (string) $modified : null,
        ['strategy' => 'defer']
    );
}

/**
 * Sticky header hide-on-scroll-down / reveal-on-scroll-up. Vanilla JS, no
 * dependency, deferred. Only has a visible effect at >=783px, where the
 * header is actually position: sticky (see style.css) — see the script
 * file for the scroll-direction/focus/near-top logic.
 */
add_action('wp_enqueue_scripts', __NAMESPACE__ . '\\enqueue_sticky_header_script', 14);
function enqueue_sticky_header_script(): void
{
    $path = get_stylesheet_directory() . '/assets/js/sticky-header.js';
    $modified = file_exists($path) ? filemtime($path) : false;

    wp_enqueue_script(
        'lyli-sticky-header',
        get_stylesheet_directory_uri() . '/assets/js/sticky-header.js',
        [],
        $modified !== false ? (string) $modified : null,
        ['strategy' => 'defer']
    );
}
