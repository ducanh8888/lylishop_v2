<?php
/**
 * Plugin Name: Lyli GHN Connector
 * Description: Repo-controlled GHN shipment connector for WooCommerce with optional integration adapters.
 * Version: 0.2.1
 * Requires at least: 7.0
 * Requires PHP: 8.3
 * Requires Plugins: woocommerce
 * Author: Lyli Shop
 * License: Proprietary
 * Text Domain: lyli-ghn-connector
 */

namespace Lyli\GHN;

if (! defined('ABSPATH')) {
    exit;
}

const VERSION = '0.2.1';

require_once __DIR__ . '/includes/infrastructure/woocommerce/class-settings-repository.php';
require_once __DIR__ . '/includes/class-settings.php';
require_once __DIR__ . '/includes/class-api-client.php';
require_once __DIR__ . '/includes/contracts/interface-address-resolver.php';
require_once __DIR__ . '/includes/contracts/interface-legacy-shipment-reader.php';
require_once __DIR__ . '/includes/domain/class-address.php';
require_once __DIR__ . '/includes/domain/class-package.php';
require_once __DIR__ . '/includes/domain/class-cod-policy.php';
require_once __DIR__ . '/includes/infrastructure/ghn/class-status-mapper.php';
require_once __DIR__ . '/includes/infrastructure/woocommerce/class-shipment-meta-keys.php';
require_once __DIR__ . '/includes/infrastructure/woocommerce/class-lyli-legacy-shipment-reader.php';
require_once __DIR__ . '/includes/woocommerce/class-woo-address-resolver.php';
require_once __DIR__ . '/includes/woocommerce/class-composite-address-resolver.php';
require_once __DIR__ . '/includes/integrations/vietnam-address/class-vietnam-address-resolver.php';
require_once __DIR__ . '/includes/integrations/vietnam-store-toolkit/class-toolkit-address-resolver.php';
require_once __DIR__ . '/includes/integrations/vietnam-store-toolkit/class-toolkit-legacy-shipment-reader.php';
require_once __DIR__ . '/includes/woocommerce/class-shipment-repository.php';
require_once __DIR__ . '/includes/class-order-mapper.php';
require_once __DIR__ . '/includes/application/class-shipment-application.php';
require_once __DIR__ . '/includes/admin/class-create-summary.php';
require_once __DIR__ . '/includes/class-print-controller.php';
require_once __DIR__ . '/includes/integrations/vietnam-store-toolkit/class-toolkit-adapter.php';
require_once __DIR__ . '/includes/woocommerce/class-standalone-admin.php';
require_once __DIR__ . '/includes/woocommerce/class-customer-tracking.php';
require_once __DIR__ . '/includes/class-plugin.php';

add_action('plugins_loaded', [Plugin::class, 'init'], 30);
