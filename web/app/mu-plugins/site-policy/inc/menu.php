<?php
/**
 * Admin menu whitelist per PLAN.md sections 7.3/7.4.
 * developer_admin (Administrator) sees everything unchanged.
 */

namespace SitePolicy\Menu;

function filter_admin_menu(): void
{
    $user = wp_get_current_user();

    if (in_array('administrator', (array) $user->roles, true)) {
        return;
    }

    if (in_array(\SitePolicy\ROLE_OWNER, (array) $user->roles, true)) {
        hide_menu_pages(owner_hidden_menus());
        return;
    }

    if (in_array(\SitePolicy\ROLE_STAFF, (array) $user->roles, true)) {
        hide_menu_pages(staff_hidden_menus());
    }
}

/** @return string[] */
function owner_hidden_menus(): array
{
    return [
        'tools.php',
        'options-general.php',
        'themes.php',
        'plugins.php',
        'users.php',
        'site-health.php',
    ];
}

/** @return string[] */
function staff_hidden_menus(): array
{
    return array_merge(owner_hidden_menus(), [
        'edit.php', // Posts — staff only touches orders/products, not blog content
        'upload.php',
        'woocommerce_page_wc-reports',
        'woocommerce_page_wc-settings',
    ]);
}

/** @param string[] $slugs */
function hide_menu_pages(array $slugs): void
{
    foreach ($slugs as $slug) {
        remove_menu_page($slug);
    }

    // "Users" for shop_owner is reduced to "profile only", never full user admin.
    remove_submenu_page('users.php', 'user-new.php');
}
