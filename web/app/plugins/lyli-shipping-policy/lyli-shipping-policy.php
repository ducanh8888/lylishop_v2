<?php
/**
 * Plugin Name: Lyli Shipping Policy
 * Description: Keeps the Lyli native WooCommerce shipping rate within its legacy amount and weight eligibility limits.
 * Version: 0.1.0
 * Requires at least: 7.0
 * Requires PHP: 8.3
 * Requires Plugins: woocommerce
 * Author: Lyli Shop
 * License: Proprietary
 * Text Domain: lyli-shipping-policy
 */

namespace Lyli\ShippingPolicy;

if (! defined('ABSPATH')) {
    exit;
}

require_once __DIR__ . '/includes/class-shipping-guard.php';

add_action('plugins_loaded', static function (): void {
    if (! class_exists('WooCommerce')) {
        return;
    }

    (new Shipping_Guard())->init();
}, 30);
