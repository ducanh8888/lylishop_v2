<?php
/**
 * Plugin Name: Lyli GHN Connector
 * Description: Repo-controlled GHN shipment connector for Vietnam Store Toolkit.
 * Version: 0.1.0
 * Requires at least: 7.0
 * Requires PHP: 8.3
 * Requires Plugins: woocommerce, yoohw-vietnam-store-tools
 * Author: Lyli Shop
 * License: Proprietary
 * Text Domain: lyli-ghn-connector
 */

namespace Lyli\GHN;

if (! defined('ABSPATH')) {
    exit;
}

const VERSION = '0.1.0';
const SETTINGS_OPTION = 'lyli_ghn_settings';
const TOKEN_OPTION = 'lyli_ghn_token';

require_once __DIR__ . '/includes/class-settings.php';
require_once __DIR__ . '/includes/class-api-client.php';
require_once __DIR__ . '/includes/class-order-mapper.php';
require_once __DIR__ . '/includes/class-provider.php';
require_once __DIR__ . '/includes/class-plugin.php';

add_action('plugins_loaded', [Plugin::class, 'init'], 30);
