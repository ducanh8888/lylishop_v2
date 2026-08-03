<?php
/**
 * Role registration per PLAN.md section 7 and TECH_STACK.md section 10.1.
 * developer_admin = WordPress Administrator (unchanged, developer-only).
 * shop_owner      = Shop Manager + whitelisted extras, never Administrator.
 * shop_staff      = order handling + optionally product/inventory/customer/review.
 */

namespace SitePolicy\Roles;

function register_roles(): void
{
    add_shop_owner_role();
    add_shop_staff_role();
}

function add_shop_owner_role(): void
{
    $shop_manager = get_role('shop_manager');
    $capabilities = $shop_manager ? $shop_manager->capabilities : [];

    $capabilities = array_merge($capabilities, [
        'read' => true,
        'edit_posts' => true,
        'edit_pages' => true,
        'upload_files' => true,
        'moderate_comments' => true,
        'manage_woocommerce' => true,
        'view_woocommerce_reports' => true,
    ]);

    // Explicitly denied even if inherited — enforced again in inc/lockdown.php.
    foreach (\SitePolicy\LOCKED_CAPABILITIES as $cap) {
        unset($capabilities[$cap]);
    }

    if (! get_role(\SitePolicy\ROLE_OWNER)) {
        add_role(\SitePolicy\ROLE_OWNER, __('Shop Owner', 'site-policy'), $capabilities);
    } else {
        $role = get_role(\SitePolicy\ROLE_OWNER);
        sync_capabilities($role, $capabilities);
    }
}

function add_shop_staff_role(): void
{
    $capabilities = [
        'read' => true,
        'edit_shop_orders' => true,
        'read_shop_order' => true,
        'edit_others_shop_orders' => true,
        'moderate_comments' => true,
    ];

    if (! get_role(\SitePolicy\ROLE_STAFF)) {
        add_role(\SitePolicy\ROLE_STAFF, __('Shop Staff', 'site-policy'), $capabilities);
    } else {
        $role = get_role(\SitePolicy\ROLE_STAFF);
        sync_capabilities($role, $capabilities);
    }
}

/** Grant a capability to shop_staff on demand (e.g. products, inventory) — used by the owner dashboard, never by staff themselves. */
function grant_staff_capability(string $capability): void
{
    $role = get_role(\SitePolicy\ROLE_STAFF);
    if ($role && ! in_array($capability, \SitePolicy\LOCKED_CAPABILITIES, true)) {
        $role->add_cap($capability);
    }
}

function sync_capabilities(\WP_Role $role, array $desired): void
{
    foreach ($desired as $cap => $grant) {
        if ($grant) {
            $role->add_cap($cap);
        }
    }
}
