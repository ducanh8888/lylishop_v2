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
 * Read the canonical design values from the child theme's theme.json.
 *
 * Botiga needs an eight-slot runtime palette for its generated CSS. Keeping the
 * mapping here, while reading every value from theme.json, avoids maintaining a
 * second set of literal brand colors in PHP.
 *
 * @return array<string, string>
 */
function design_values(): array
{
    static $values = null;

    if (is_array($values)) {
        return $values;
    }

    $values = [];
    $theme_json = wp_json_file_decode(
        get_stylesheet_directory() . '/theme.json',
        ['associative' => true]
    );

    if (! is_array($theme_json)) {
        return $values;
    }

    $palette = $theme_json['settings']['color']['palette'] ?? [];
    foreach ($palette as $color) {
        if (isset($color['slug'], $color['color'])) {
            $values[(string) $color['slug']] = (string) $color['color'];
        }
    }

    $action_hover = $theme_json['settings']['custom']['lyli']['color']['actionHover'] ?? null;
    if (is_string($action_hover)) {
        $values['lyli-action-hover'] = $action_hover;
    }

    return $values;
}

/**
 * Replace Botiga's stock palette-six runtime slots with Lyli semantic values.
 *
 * Botiga always emits backward-compatibility utilities for every stock palette.
 * Its original palette six contains the retired red/orange values. This public
 * parent-theme filter is the supported way to keep those utilities and Botiga's
 * generated CSS aligned with the canonical child theme palette.
 *
 * @param array<string, array<int, string>> $palettes Botiga palettes.
 * @return array<string, array<int, string>>
 */
add_filter('botiga_color_palettes', __NAMESPACE__ . '\\map_botiga_runtime_palette');
function map_botiga_runtime_palette(array $palettes): array
{
    $values = design_values();
    $required = [
        'lyli-primary',
        'lyli-action-hover',
        'lyli-text',
        'lyli-text-muted',
        'lyli-cream',
        'lyli-warm-white',
    ];

    foreach ($required as $key) {
        if (! isset($values[$key])) {
            return $palettes;
        }
    }

    $palettes['palette6'] = [
        $values['lyli-primary'],
        $values['lyli-action-hover'],
        $values['lyli-primary'],
        $values['lyli-text'],
        $values['lyli-text-muted'],
        $values['lyli-cream'],
        $values['lyli-warm-white'],
        $values['lyli-warm-white'],
    ];

    return $palettes;
}

/**
 * Google Fonts URL (runtime delivery; no binary font files committed).
 * Verified families exist on Google Fonts (open-licensed).
 */
function google_fonts_url(): string
{
    return 'https://fonts.googleapis.com/css2?family=Fraunces:wght@400;600;700&family=Be+Vietnam+Pro:wght@400;500;600;700&display=swap';
}
