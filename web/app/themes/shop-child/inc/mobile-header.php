<?php
/**
 * One-time Botiga mobile-header composition migration.
 *
 * The version marker prevents later requests from overwriting owner changes in
 * the Customizer. Desktop header settings are intentionally untouched.
 */

namespace ShopChild\MobileHeader;

if (! defined('ABSPATH')) {
    exit;
}

const COMPOSITION_VERSION = 1;
const VERSION_MOD = 'lyli_mobile_header_composition_version';

add_action('after_setup_theme', __NAMESPACE__ . '\\migrate_composition', 30);

function migrate_composition(): void
{
    if ((int) get_theme_mod(VERSION_MOD, 0) >= COMPOSITION_VERSION) {
        return;
    }

    // Visible narrow row: logo (owned by Botiga layout) + cart + hamburger.
    set_theme_mod('header_layout_mobile', 'header_mobile_layout_1');
    set_theme_mod('header_components_mobile', ['mobile_woocommerce_icons']);
    set_theme_mod('enable_mobile_header_cart', 1);
    set_theme_mod('enable_mobile_header_account', 0);

    // Deliberate secondary interaction: search + account inside the drawer.
    set_theme_mod('header_components_offcanvas', ['search', 'mobile_offcanvas_woocommerce_icons']);
    set_theme_mod('enable_mobile_header_offcanvas_cart', 0);
    set_theme_mod('enable_mobile_header_offcanvas_account', 1);

    set_theme_mod(VERSION_MOD, COMPOSITION_VERSION);
}
