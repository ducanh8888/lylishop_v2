<?php
/**
 * Lyli Shop — asset enqueue.
 * Only loads what Botiga does not already provide:
 *   1. Google Fonts (runtime, no committed binaries).
 *   The child's own style.css is auto-loaded by Botiga's `botiga-style`
 *   handle (verified against Botiga 2.4.7 source — do not double-enqueue).
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