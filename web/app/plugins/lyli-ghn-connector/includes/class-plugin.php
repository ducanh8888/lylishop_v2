<?php

namespace Lyli\GHN;

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
    private static ?Provider $provider = null;
    private static ?Shipment_Repository $shipments = null;

    public static function init(): void
    {
        Settings::init();
        if (! class_exists('WooCommerce')) {
            add_action('admin_notices', [self::class, 'dependency_notice']);
            return;
        }

        $provider = self::provider();
        $toolkit_supported = Toolkit_Adapter::is_supported();
        if (null !== $provider && $toolkit_supported) {
            (new Toolkit_Adapter($provider))->init();
        } elseif (null !== $provider) {
            (new Standalone_Admin($provider, self::shipments()))->init();
        }
        if (! $toolkit_supported) {
            (new Customer_Tracking(self::shipments()))->init();
        }
    }

    public static function provider(): ?Provider
    {
        $settings = Settings::get();
        if (! Settings::is_ready($settings)) {
            return null;
        }
        if (null === self::$provider) {
            $resolvers = [];
            if (Toolkit_Address_Resolver::is_supported()) {
                $resolvers[] = new Toolkit_Address_Resolver();
            }
            $resolvers[] = new Woo_Address_Resolver();
            self::$provider = new Provider(
                new Api_Client($settings, Settings::token()),
                new Order_Mapper(new Composite_Address_Resolver($resolvers)),
                self::shipments()
            );
        }

        return self::$provider;
    }

    public static function shipments(): Shipment_Repository
    {
        if (null === self::$shipments) {
            self::$shipments = new Shipment_Repository(new Toolkit_Legacy_Shipment_Reader());
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
