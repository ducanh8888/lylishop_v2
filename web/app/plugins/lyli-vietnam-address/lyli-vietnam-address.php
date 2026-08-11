<?php
/**
 * Plugin Name: Lyli Vietnam Address
 * Description: Thin WooCommerce adapter for a pinned, current two-level Vietnam administrative dataset.
 * Version: 0.1.0
 * Requires at least: 7.0
 * Requires PHP: 8.3
 * Requires Plugins: woocommerce
 * Author: Lyli Shop
 * License: Proprietary adapter; upstream data is MIT licensed.
 * Text Domain: lyli-vietnam-address
 */

namespace Lyli\VietnamAddress;

if (! defined('ABSPATH')) {
    exit;
}

const VERSION = '0.1.0';
const DATA_RELEASE = 'v4.0.0';
const DATA_SHA256 = 'f36c1b4fd6f0c61065936c365395d66cc4a1d12b4e0f313819f2930fd27293e2';
const DATA_FILE = __DIR__ . '/data/vietnam-addresses.json';

require_once __DIR__ . '/includes/class-address.php';
require_once __DIR__ . '/includes/class-repository.php';
require_once __DIR__ . '/includes/class-woo-adapter.php';

add_action('plugins_loaded', static function (): void {
    if (! class_exists('WooCommerce')) {
        return;
    }
    (new Woo_Adapter(Repository::instance(), plugin_dir_url(__FILE__) . 'assets/checkout.js'))->init();
}, 25);
