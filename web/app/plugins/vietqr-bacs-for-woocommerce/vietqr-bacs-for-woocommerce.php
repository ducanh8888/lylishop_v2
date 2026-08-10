<?php
/**
 * Plugin Name: VietQR BACS for WooCommerce
 * Description: Adds owner-configured VietQR instructions to WooCommerce Direct Bank Transfer without automatic reconciliation.
 * Version: 0.1.0
 * Requires at least: 7.0
 * Requires PHP: 8.3
 * Requires Plugins: woocommerce
 * Author: Commerce Tools
 * License: GPL-2.0-or-later
 * Text Domain: vietqr-bacs-for-woocommerce
 */

namespace VietQR\BACS;

if (! defined('ABSPATH')) {
    exit;
}

const VERSION = '0.1.0';
const SETTINGS_OPTION = 'woocommerce_vietqr_bacs_settings';

add_action('plugins_loaded', static function (): void {
    if (! class_exists('WooCommerce') || ! class_exists('WC_Integration')) {
        add_action('admin_notices', static function (): void {
            if (current_user_can('activate_plugins')) {
                echo '<div class="notice notice-error"><p>' . esc_html__('VietQR BACS requires WooCommerce.', 'vietqr-bacs-for-woocommerce') . '</p></div>';
            }
        });
        return;
    }

    require_once __DIR__ . '/includes/class-integration.php';
    require_once __DIR__ . '/includes/class-qr-builder.php';
    require_once __DIR__ . '/includes/class-renderer.php';

    add_filter('woocommerce_integrations', static function (array $integrations): array {
        $integrations[] = Integration::class;
        return $integrations;
    });

    (new Renderer(new Qr_Builder()))->init();
}, 30);
