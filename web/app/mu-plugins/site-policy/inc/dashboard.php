<?php
/**
 * Compact work dashboard widget per PLAN.md section 7.3/7.4 — replaces the
 * default WordPress "At a Glance" clutter for shop_owner/shop_staff with
 * order counts they actually act on. Read-only; no order mutation here.
 */

namespace SitePolicy\Dashboard;

function register_widget(): void
{
    $user = wp_get_current_user();
    $is_shop_role = array_intersect(
        [\SitePolicy\ROLE_OWNER, \SitePolicy\ROLE_STAFF],
        (array) $user->roles
    );

    if (empty($is_shop_role) || in_array('administrator', (array) $user->roles, true)) {
        return;
    }

    remove_meta_box('dashboard_primary', 'dashboard', 'side');
    remove_meta_box('dashboard_quick_press', 'dashboard', 'side');
    remove_meta_box('dashboard_activity', 'dashboard', 'normal');

    wp_add_dashboard_widget(
        'site_policy_work_overview',
        __('Công việc hôm nay', 'site-policy'),
        __NAMESPACE__ . '\\render_widget'
    );
}

function render_widget(): void
{
    if (! function_exists('wc_get_orders')) {
        echo '<p>' . esc_html__('WooCommerce chưa sẵn sàng.', 'site-policy') . '</p>';
        return;
    }

    $pending = wc_get_orders(['status' => 'pending', 'return' => 'ids', 'limit' => -1]);
    $processing = wc_get_orders(['status' => 'processing', 'return' => 'ids', 'limit' => -1]);
    $on_hold = wc_get_orders(['status' => 'on-hold', 'return' => 'ids', 'limit' => -1]);

    printf(
        '<ul><li>%s: %d</li><li>%s: %d</li><li>%s: %d</li></ul>',
        esc_html__('Chờ thanh toán', 'site-policy'), count($pending),
        esc_html__('Đang xử lý', 'site-policy'), count($processing),
        esc_html__('Tạm giữ', 'site-policy'), count($on_hold)
    );
}
