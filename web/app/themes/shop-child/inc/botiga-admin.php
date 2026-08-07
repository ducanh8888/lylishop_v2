<?php
/**
 * Botiga admin compatibility.
 *
 * Botiga renders every dashboard tab template, including inactive tabs. Its
 * Starter Sites template redirects to the importer page when its plugin-status
 * helper cannot run. WordPress intentionally makes install_plugins fail while
 * DISALLOW_FILE_MODS is enabled, so that redirect otherwise lands on an
 * unregistered page and shows an authorization error.
 */

namespace ShopChild\BotigaAdmin;

if (! defined('ABSPATH')) {
    exit;
}

/**
 * Whether the Starter Sites integration is actually available at runtime.
 */
function starter_sites_available(): bool
{
    return (bool) has_action('atss_starter_sites');
}

/**
 * Do not let an unavailable, file-mutating demo importer break the dashboard.
 *
 * @param array<string, mixed> $settings Botiga dashboard settings.
 * @return array<string, mixed>
 */
function filter_dashboard_settings(array $settings): array
{
    if (starter_sites_available()) {
        return $settings;
    }

    if (isset($settings['tabs']) && is_array($settings['tabs'])) {
        unset($settings['tabs']['starter-sites']);
    }

    return $settings;
}
add_filter('botiga_dashboard_settings', __NAMESPACE__ . '\\filter_dashboard_settings', PHP_INT_MAX);

/**
 * Remove the matching dead submenu link as well as its dashboard tab.
 */
function remove_unavailable_starter_sites_menu(): void
{
    if (starter_sites_available()) {
        return;
    }

    remove_submenu_page(
        'botiga-dashboard',
        get_admin_url() . 'admin.php?page=botiga-dashboard&tab=starter-sites'
    );
}
add_action('admin_menu', __NAMESPACE__ . '\\remove_unavailable_starter_sites_menu', PHP_INT_MAX);
