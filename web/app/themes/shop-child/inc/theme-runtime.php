<?php
/**
 * One-time reconciliation of Botiga runtime settings with Lyli theme.json.
 *
 * Direct option writes do not fire Botiga's `customize_save_after` hook, so its
 * uploads/botiga/custom-styles.css file can remain stale. This migration only
 * replaces missing values and known former/default values, preserves later
 * owner choices, then invokes Botiga's public CSS regeneration method once.
 */

namespace ShopChild\ThemeRuntime;

use Botiga_Custom_CSS;

if (! defined('ABSPATH')) {
    exit;
}

const VERSION = 1;
const VERSION_OPTION = 'lyli_theme_runtime_version';

add_action('init', __NAMESPACE__ . '\\migrate', 20);

/**
 * Normalize values for exact legacy comparisons without changing storage type.
 *
 * @param mixed $value Value to normalize.
 */
function normalized($value): string
{
    if (is_bool($value)) {
        return $value ? '1' : '0';
    }

    if (is_scalar($value)) {
        return strtolower(trim((string) $value));
    }

    return strtolower((string) wp_json_encode($value));
}

/**
 * Set a theme mod only when absent or equal to a known legacy value.
 *
 * @param mixed        $target Target value.
 * @param array<mixed> $legacy Known legacy values.
 */
function migrate_mod(string $key, $target, array $legacy = []): bool
{
    $missing = '__lyli_missing_theme_mod__';
    $current = get_theme_mod($key, $missing);

    if ($current !== $missing) {
        $legacy = array_map(__NAMESPACE__ . '\\normalized', $legacy);
        if (! in_array(normalized($current), $legacy, true)) {
            return false;
        }
    }

    set_theme_mod($key, $target);
    return true;
}

/**
 * Return semantic targets sourced from the canonical child theme.json.
 *
 * @return array<string, string>
 */
function colors(): array
{
    $values = \ShopChild\design_values();

    return [
        'primary' => $values['lyli-primary'] ?? '',
        'hover' => $values['lyli-action-hover'] ?? '',
        'warm-white' => $values['lyli-warm-white'] ?? '',
        'cream' => $values['lyli-cream'] ?? '',
        'lavender' => $values['lyli-lavender'] ?? '',
        'text' => $values['lyli-text'] ?? '',
        'muted' => $values['lyli-text-muted'] ?? '',
    ];
}

/**
 * Reconcile known runtime values and regenerate Botiga's generated stylesheet.
 */
function migrate(): void
{
    if ((int) get_option(VERSION_OPTION, 0) >= VERSION) {
        return;
    }

    $color = colors();
    if (in_array('', $color, true)) {
        return;
    }

    // These literals are migration matchers only; they are never emitted.
    $retired_colors = [
        '#FF524D',
        '#E80600',
        '#8A4A23',
        '#40140F',
        '#5B3F3E',
        '#ACA2A1',
        '#F4E3E0',
        '#FFFFFF',
        'FFFFFF',
        '#FFF',
        '#212121',
        '#757575',
    ];

    $targets = [
        'background_color' => ltrim($color['warm-white'], '#'),
        'button_background_color' => $color['primary'],
        'button_background_color_hover' => $color['hover'],
        'button_color' => $color['warm-white'],
        'button_color_hover' => $color['warm-white'],
        'button_border_color' => $color['primary'],
        'button_border_color_hover' => $color['hover'],
        'scrolltop_color' => $color['warm-white'],
        'scrolltop_color_hover' => $color['warm-white'],
        'scrolltop_bg_color' => $color['primary'],
        'scrolltop_bg_color_hover' => $color['hover'],
        'loop_post_title_color' => $color['primary'],
        'loop_post_meta_color' => $color['muted'],
        'loop_post_text_color' => $color['text'],
        'single_post_title_color' => $color['primary'],
        'site_title_color' => $color['primary'],
        'site_description_color' => $color['muted'],
        'main_header_color' => $color['text'],
        'main_header_color_hover' => $color['primary'],
        'main_header_submenu_background' => $color['warm-white'],
        'main_header_submenu_color' => $color['text'],
        'main_header_submenu_color_hover' => $color['primary'],
        'main_header_sticky_active_color' => $color['text'],
        'main_header_sticky_active_color_hover' => $color['primary'],
        'main_header_sticky_active_submenu_background_color' => $color['warm-white'],
        'main_header_sticky_active_submenu_color' => $color['text'],
        'main_header_sticky_active_submenu_color_hover' => $color['primary'],
        'offcanvas_menu_background' => $color['warm-white'],
        'color_body_text' => $color['text'],
        'content_cards_background' => $color['cream'],
        'color_link_default' => $color['primary'],
        'color_link_hover' => $color['hover'],
        'color_heading_1' => $color['primary'],
        'color_heading_2' => $color['primary'],
        'color_heading_3' => $color['primary'],
        'color_heading_4' => $color['primary'],
        'color_heading_5' => $color['primary'],
        'color_heading_6' => $color['primary'],
        'color_forms_text' => $color['text'],
        'color_forms_background' => $color['warm-white'],
        'color_forms_borders' => $color['lavender'],
        'color_forms_placeholder' => $color['muted'],
        'shop_product_product_title' => $color['primary'],
        'single_product_title_color' => $color['primary'],
        'bhfb_contact_info_icon_color' => $color['primary'],
        'bhfb_search_icon_color' => $color['primary'],
        'bhfb_woo_icons_color' => $color['primary'],
        'botiga_header_row__above_header_row_background_color' => $color['warm-white'],
        'botiga_header_row__above_header_row_border_bottom_color' => $color['cream'],
        'botiga_header_row__main_header_row_background_color' => $color['warm-white'],
        'botiga_header_row__main_header_row_border_bottom_color' => $color['cream'],
        'botiga_header_row__below_header_row_background_color' => $color['warm-white'],
        'botiga_header_row__below_header_row_border_bottom_color' => $color['cream'],
        'botiga_footer_row__above_footer_row_background_color' => $color['cream'],
        'botiga_footer_row__above_footer_row_border_top_color' => $color['lavender'],
        'botiga_footer_row__main_footer_row_background_color' => $color['cream'],
        'botiga_footer_row__main_footer_row_border_top_color' => $color['lavender'],
        'botiga_footer_row__below_footer_row_background_color' => $color['cream'],
        'botiga_footer_row__below_footer_row_border_top_color' => $color['lavender'],
    ];

    foreach ($targets as $key => $target) {
        migrate_mod($key, $target, $retired_colors);
    }

    $palette_slots = [
        $color['primary'],
        $color['hover'],
        $color['primary'],
        $color['text'],
        $color['muted'],
        $color['cream'],
        $color['warm-white'],
        $color['warm-white'],
    ];
    migrate_mod('color_palettes', 'palette6', ['palette6']);
    migrate_mod('custom_palette_toggle', 1, [1, '1']);
    foreach ($palette_slots as $index => $target) {
        migrate_mod('custom_color' . ($index + 1), $target, $retired_colors);
    }

    $typography = [
        'botiga_body_font' => ['{"font":"Be Vietnam Pro","regularweight":"400","category":"sans-serif"}', ['{"font":"Fraunces","regularweight":"500","category":"serif"}']],
        'body_font_style' => ['normal', []],
        'body_font_size_desktop' => [16, [19, '19']],
        'body_line_height' => ['1.65', ['1.84']],
        'body_letter_spacing' => ['0', ['0.5']],
        'body_text_transform' => ['none', []],
        'botiga_headings_font' => ['{"font":"Fraunces","regularweight":"600","category":"serif"}', ['{"font":"Fraunces","regularweight":"500","category":"serif"}']],
        'headings_font_style' => ['normal', []],
        'headings_line_height' => ['1.15', []],
        'headings_letter_spacing' => ['0', ['0.5']],
        'h1_font_size_desktop' => [52, [66, '66']],
        'h2_font_size_desktop' => [40, [49, '49']],
        'h3_font_size_desktop' => [30, [33, '33']],
        'h4_font_size_desktop' => [24, []],
        'h5_font_size_desktop' => [19, []],
        'h6_font_size_desktop' => [16, []],
        'botiga_header_menu_font' => ['{"font":"Be Vietnam Pro","regularweight":"600","category":"sans-serif"}', []],
        'header_menu_font_size_desktop' => [14, [10, '10']],
        'header_menu_line_height' => ['1.4', []],
        'header_menu_text_transform' => ['none', []],
        'header_menu_font_style' => ['normal', []],
    ];
    foreach ($typography as $key => [$target, $legacy]) {
        migrate_mod($key, $target, $legacy);
    }

    $layout = [
        'botiga_header_row__main_header_row' => '{ "desktop": [["search"], ["logo"], ["woo_icons"]], "mobile": [[], ["logo"], ["woo_icons", "mobile_hamburger"]] }',
        'botiga_header_row__below_header_row' => '{"desktop":[["menu"]],"mobile":[[],[],[]],"mobile_offcanvas":[[]]}',
        'botiga_header_row__below_header_row_column1_horizontal_alignment' => 'center',
        'botiga_header_row__below_header_row_height_desktop' => 52,
        'botiga_header_row__below_header_row_columns_desktop' => '1',
        'botiga_header_row__below_header_row_border_bottom_desktop' => 1,
        'mobile_breakpoint' => 1089,
    ];
    foreach ($layout as $key => $target) {
        migrate_mod($key, $target);
    }

    migrate_front_page();

    if (! regenerate_botiga_css()) {
        set_transient('botiga_update_custom_css_flag', true, 0);
        return;
    }

    update_option(VERSION_OPTION, VERSION, false);
}

/**
 * Reproduce the two narrow homepage corrections without replacing page blocks.
 */
function migrate_front_page(): void
{
    $front_page_id = (int) get_option('page_on_front');
    if ($front_page_id <= 0) {
        return;
    }

    if (! metadata_exists('post', $front_page_id, '_botiga_hide_page_title')) {
        update_post_meta($front_page_id, '_botiga_hide_page_title', '1');
    }

    $post = get_post($front_page_id);
    if (! $post instanceof \WP_Post) {
        return;
    }

    $content = preg_replace(
        '/(<h1\\b[^>]*?)\\sstyle="font-size:\\s*51px;?"([^>]*>)/i',
        '$1$2',
        $post->post_content,
        1
    );
    if (is_string($content) && $content !== $post->post_content) {
        wp_update_post([
            'ID' => $front_page_id,
            'post_content' => $content,
        ]);
    }
}

/**
 * Use Botiga's supported public generator and verify the stale palette is gone.
 */
function regenerate_botiga_css(): bool
{
    if (! class_exists(Botiga_Custom_CSS::class)) {
        return false;
    }

    Botiga_Custom_CSS::get_instance()->update_custom_css_file();

    $uploads = wp_upload_dir();
    $file = trailingslashit($uploads['basedir']) . 'botiga/custom-styles.css';
    if (! is_readable($file)) {
        return false;
    }

    $css = file_get_contents($file);
    if (! is_string($css)) {
        return false;
    }

    return stripos($css, '#FF524D') === false
        && stripos($css, '#E80600') === false;
}
