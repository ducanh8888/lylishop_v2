<?php

namespace Lyli\GHN;

final class Plugin
{
    private static ?Provider $provider = null;

    public static function init(): void
    {
        Settings::init();

        if (! class_exists('WooCommerce') || ! class_exists('Yoohw_Vietnam_Store_Tools_Shipping')) {
            add_action('admin_notices', [self::class, 'dependency_notice']);
            return;
        }

        add_filter('yoohw_vietnam_store_tools_shipping_providers', [self::class, 'register_provider']);
    }

    /** @param array<string,array<string,mixed>> $providers */
    public static function register_provider(array $providers): array
    {
        $settings = Settings::get();
        if (! Settings::is_ready($settings)) {
            return $providers;
        }

        if (null === self::$provider) {
            self::$provider = new Provider(
                new Api_Client($settings, Settings::token()),
                new Order_Mapper()
            );
        }

        $providers['lyli_ghn'] = [
            'id' => 'lyli_ghn',
            'name' => __('GHN (Lyli)', 'lyli-ghn-connector'),
            'supports' => ['create', 'sync', 'cancel', 'print'],
            'render_create_fields' => [self::$provider, 'render_create_fields'],
            'create_shipment' => [self::$provider, 'create_shipment'],
            'sync_shipment' => [self::$provider, 'sync_shipment'],
            'cancel_shipment' => [self::$provider, 'cancel_shipment'],
            'print_shipment' => [self::$provider, 'print_shipment'],
        ];

        return $providers;
    }

    public static function dependency_notice(): void
    {
        if (! current_user_can('activate_plugins')) {
            return;
        }

        echo '<div class="notice notice-error"><p>';
        echo esc_html__('Lyli GHN Connector requires WooCommerce and Vietnam Store Toolkit.', 'lyli-ghn-connector');
        echo '</p></div>';
    }
}
