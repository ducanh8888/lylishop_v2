<?php
/**
 * Plugin Name: Lyli VietQR for BACS
 * Description: Local VietQR presentation for native WooCommerce bank transfer orders.
 * Version: 0.1.0
 * Requires at least: 7.0
 * Requires PHP: 8.3
 * Requires Plugins: woocommerce
 * Author: Lyli Shop
 * License: Proprietary adapter; reusable libraries retain their own licenses.
 * Text Domain: lyli-vietqr-bacs
 */

namespace Lyli\VietQRBACS;

if (! defined('ABSPATH')) {
    exit;
}

const VERSION = '0.1.0';

require_once __DIR__ . '/includes/class-payload-factory.php';
require_once __DIR__ . '/includes/class-qr-renderer.php';

add_action('plugins_loaded', static function (): void {
    if (! class_exists('WooCommerce') || ! class_exists('WC_Integration')) {
        return;
    }

    require_once __DIR__ . '/includes/class-integration.php';
    require_once __DIR__ . '/includes/class-order-presentation.php';

    add_filter('woocommerce_integrations', static function (array $integrations): array {
        $integrations[] = Integration::class;
        return $integrations;
    });

    add_action('woocommerce_init', static function (): void {
        $integration = \WC()->integrations?->get_integration('lyli_vietqr_bacs');
        if ($integration instanceof Integration) {
            (new Order_Presentation($integration, new Payload_Factory(), new QR_Renderer()))->init();
        }
    }, 20);
}, 30);
