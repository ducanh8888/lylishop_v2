<?php
/**
 * Lyli Shop — design asset configuration (loaded by functions.php).
 *
 * theme.json is the canonical source for color and typography tokens. Frontend
 * and editor CSS reference the WordPress preset variables generated from it;
 * PHP intentionally does not mirror the palette.
 */

namespace ShopChild;

if (! defined('ABSPATH')) {
    exit;
}

/**
 * Botiga 2.4.7 replaces the complete theme.json palette at priority 10 with
 * its Customizer palette. Remove only that filter after both themes load so
 * the child theme.json remains the canonical editor/frontend token source.
 */
add_action('after_setup_theme', __NAMESPACE__ . '\\preserve_child_theme_json_palette', 0);
function preserve_child_theme_json_palette(): void
{
    remove_filter('wp_theme_json_data_theme', 'botiga_filter_theme_json_data_theme');
}

/**
 * Google Fonts URL (runtime delivery; no binary font files committed).
 * Verified families exist on Google Fonts (open-licensed).
 */
function google_fonts_url(): string
{
    return 'https://fonts.googleapis.com/css2?family=Fraunces:wght@400;600;700&family=Be+Vietnam+Pro:wght@400;500;600;700&display=swap';
}
