<?php
/**
 * Plugin Name: Lyli Site Settings
 * Description: Global site settings for the Lyli Shop storefront — WP Admin → Lyli Shop → Cài đặt giao diện. Settings API, capability checks, nonces, strict sanitize callbacks, escaped output, Vietnamese labels. No credentials, no commerce logic, no business-sensitive data.
 * Version: 1.0.0
 * Author: lylishop developer
 * Text Domain: lyli-site-settings
 *
 * This MU plugin keeps presentation-level site settings available
 * independently of the active theme. Page/section content stays in
 * Gutenberg pages; this plugin only stores small global values
 * (footer intro, contact details, social URLs, announcement bar,
 * custom-order CTA defaults, copyright).
 */

namespace LyliSiteSettings;

if (! defined('ABSPATH')) {
    exit;
}

const OPTION_PREFIX     = 'lyli_';
const MENU_SLUG         = 'lyli-site-settings';
const SETTINGS_PAGE     = 'lyli-site-settings';
const SETTINGS_GROUP    = 'lyli_site_settings_group';
const OPTION_KEYS       = [
    'footer_intro',
    'contact_email',
    'contact_phone',
    'facebook_url',
    'instagram_url',
    'tiktok_url',
    'zalo_url',
    'announcement',
    'announcement_enabled',
    'custom_order_label',
    'custom_order_url',
    'footer_copyright',
];

require_once __DIR__ . '/inc/settings-page.php';
require_once __DIR__ . '/inc/public-accessors.php';

add_action('admin_menu', __NAMESPACE__ . '\\SettingsPage\\register_menu', 20);
add_action('admin_init', __NAMESPACE__ . '\\SettingsPage\\register_settings');