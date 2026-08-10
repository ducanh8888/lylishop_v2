<?php
/**
 * Vietnam Store Toolkit access boundary.
 *
 * Shop Owner keeps manage_woocommerce for normal toolkit, BACS, shipping and
 * order operations. The two legacy DevVN migration tools remain developer-only
 * because they can rewrite order and customer address metadata in batches.
 */

namespace SitePolicy\VietnamToolkit;

if (! defined('ABSPATH')) {
    exit;
}

const DEVVN_SCAN_TOOL = 'yoohw_vietnam_store_tools_devvn_migration_dry_run';
const DEVVN_SYNC_TOOL = 'yoohw_vietnam_store_tools_devvn_migration';
const DEVVN_AJAX_ACTION = 'wp_ajax_yoohw_vietnam_store_tools_devvn_migration_step';

add_filter('woocommerce_debug_tools', __NAMESPACE__ . '\\hide_owner_migration_tools', PHP_INT_MAX);
add_action(DEVVN_AJAX_ACTION, __NAMESPACE__ . '\\deny_owner_migration_ajax', 0);
add_action('admin_head-toplevel_page_yoohw-vietnam-store', __NAMESPACE__ . '\\hide_owner_migration_navigation');

/** @param mixed $tools */
function hide_owner_migration_tools($tools): array
{
    $tools = is_array($tools) ? $tools : [];

    if (! is_shop_owner()) {
        return $tools;
    }

    unset($tools[DEVVN_SCAN_TOOL], $tools[DEVVN_SYNC_TOOL]);

    return $tools;
}

function deny_owner_migration_ajax(): void
{
    if (! is_shop_owner()) {
        return;
    }

    wp_send_json_error(
        ['message' => __('Công cụ chuyển đổi dữ liệu chỉ dành cho quản trị viên kỹ thuật.', 'site-policy')],
        403
    );
}

/**
 * The plugin dashboard has no filter for individual cards. Hide only its
 * DevVN card from the owner-facing navigation; the server-side denial above
 * remains the security boundary.
 */
function hide_owner_migration_navigation(): void
{
    if (! is_shop_owner()) {
        return;
    }

    echo '<style id="site-policy-vietnam-toolkit">'
        . '.yoohw-vietnam-store__section[aria-labelledby="yoohw-vietnam-store-tools"] '
        . '.yoohw-vietnam-store__card:last-child{display:none}'
        . '</style>';
}

function is_shop_owner(): bool
{
    $roles = (array) wp_get_current_user()->roles;

    return in_array(\SitePolicy\ROLE_OWNER, $roles, true)
        && ! in_array('administrator', $roles, true);
}
