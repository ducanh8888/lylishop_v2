<?php

namespace Lyli\GHN;

use Lyli\GHN\Application\Shipment_Application;
use Lyli\GHN\Infrastructure\WooCommerce\Lyli_Legacy_Shipment_Reader;
use Lyli\GHN\Infrastructure\WooCommerce\Settings_Repository;
use Lyli\GHN\Integrations\VietnamStoreToolkit\Toolkit_Address_Resolver;
use Lyli\GHN\Integrations\VietnamStoreToolkit\Toolkit_Adapter;
use Lyli\GHN\Integrations\VietnamStoreToolkit\Toolkit_Legacy_Shipment_Reader;
use Lyli\GHN\WooCommerce\Composite_Address_Resolver;
use Lyli\GHN\WooCommerce\Customer_Tracking;
use Lyli\GHN\WooCommerce\Shipment_Repository;
use Lyli\GHN\WooCommerce\Standalone_Admin;
use Lyli\GHN\WooCommerce\Woo_Address_Resolver;

final class Plugin
{
    private static ?Shipment_Application $application = null;
    private static ?Shipment_Repository $shipments = null;

    public static function init(): void
    {
        Settings::init();
        if (! class_exists('WooCommerce')) {
            add_action('admin_notices', [self::class, 'dependency_notice']);
            return;
        }

        $application = self::application();
        $toolkit_supported = Toolkit_Adapter::is_supported();
        if (null !== $application && $toolkit_supported) {
            (new Toolkit_Adapter($application))->init();
        } elseif (null !== $application) {
            (new Standalone_Admin($application, self::shipments()))->init();
        }
        if (! $toolkit_supported) {
            (new Customer_Tracking(self::shipments()))->init();
        }
    }

    public static function application(): ?Shipment_Application
    {
        $settings = new Settings_Repository();
        $values = $settings->get();
        if (! $settings->is_ready($values)) {
            return null;
        }
        if (null === self::$application) {
            $resolvers = [];
            if (Toolkit_Address_Resolver::is_supported()) {
                $resolvers[] = new Toolkit_Address_Resolver();
            }
            $resolvers[] = new Woo_Address_Resolver();
            self::$application = new Shipment_Application(
                new Api_Client($values, $settings->token()),
                new Order_Mapper(new Composite_Address_Resolver($resolvers)),
                self::shipments(),
                $settings
            );
        }

        return self::$application;
    }

    public static function shipments(): Shipment_Repository
    {
        if (null === self::$shipments) {
            self::$shipments = new Shipment_Repository([
                new Lyli_Legacy_Shipment_Reader(),
                new Toolkit_Legacy_Shipment_Reader(),
            ]);
        }
        return self::$shipments;
    }

    public static function dependency_notice(): void
    {
        if (current_user_can('activate_plugins')) {
            echo '<div class="notice notice-error"><p>' . esc_html__('GHN Connector requires WooCommerce.', 'lyli-ghn-connector') . '</p></div>';
        }
    }
}
