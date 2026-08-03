<?php
/**
 * Plugin Name: Site Policy
 * Description: Roles, capabilities, menu visibility and technical lockdown for lylishop.online. The only custom business-independent code allowed to run as an mu-plugin per PLAN.md section 4.2 and TECH_STACK.md section 10.2. Contains no cart, checkout, order, or payment logic.
 * Version: 0.1.0
 * Author: lylishop developer
 */

namespace SitePolicy;

if (! defined('ABSPATH')) {
    exit;
}

const ROLE_OWNER = 'shop_owner';
const ROLE_STAFF = 'shop_staff';

/**
 * Capabilities owner/staff must never hold, per PLAN.md section 7.5 and
 * TECH_STACK.md section 10.1 — plugin/theme install, core update, file edit,
 * and technical configuration stay with developer_admin only.
 */
const LOCKED_CAPABILITIES = [
    'install_plugins',
    'activate_plugins',
    'delete_plugins',
    'edit_plugins',
    'update_plugins',
    'switch_themes',
    'edit_themes',
    'install_themes',
    'delete_themes',
    'update_themes',
    'update_core',
    'edit_files',
    'edit_users',
    'promote_users',
    'remove_users',
    'manage_options',
];

require_once __DIR__ . '/inc/roles.php';
require_once __DIR__ . '/inc/menu.php';
require_once __DIR__ . '/inc/lockdown.php';
require_once __DIR__ . '/inc/dashboard.php';

add_action('init', __NAMESPACE__ . '\\Roles\\register_roles');
add_action('admin_menu', __NAMESPACE__ . '\\Menu\\filter_admin_menu', PHP_INT_MAX);
add_action('admin_init', __NAMESPACE__ . '\\Lockdown\\apply');
add_action('wp_dashboard_setup', __NAMESPACE__ . '\\Dashboard\\register_widget');
