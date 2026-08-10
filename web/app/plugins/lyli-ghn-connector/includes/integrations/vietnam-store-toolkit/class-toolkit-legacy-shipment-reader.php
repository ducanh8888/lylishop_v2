<?php

namespace Lyli\GHN\Integrations\VietnamStoreToolkit;

use Lyli\GHN\Contracts\Legacy_Shipment_Reader;

final class Toolkit_Legacy_Shipment_Reader implements Legacy_Shipment_Reader
{
    private const META = [
        'provider' => '_vck_shipping_provider',
        'service_code' => '_vck_shipping_service_code',
        'service_name' => '_vck_shipping_service_name',
        'label_id' => '_vck_shipping_label_id',
        'tracking_code' => '_vck_shipping_tracking_code',
        'tracking_url' => '_vck_shipping_tracking_url',
        'status_id' => '_vck_shipping_status_id',
        'status' => '_vck_shipping_status',
        'fee' => '_vck_shipping_fee',
        'insurance_fee' => '_vck_shipping_insurance_fee',
        'cod_amount' => '_vck_shipping_cod_amount',
        'last_synced' => '_vck_shipping_last_synced_at',
    ];

    public function read($order): array
    {
        if (! is_object($order) || ! method_exists($order, 'get_meta')) {
            return [];
        }

        $provider = sanitize_key((string) $order->get_meta(self::META['provider'], true));
        if (! in_array($provider, ['lyli_ghn', 'ghn'], true)) {
            return [];
        }

        $data = ['provider' => 'ghn', 'legacy_source' => 'vietnam-store-toolkit'];
        foreach (self::META as $key => $meta_key) {
            if ('provider' !== $key) {
                $data[$key] = $order->get_meta($meta_key, true);
            }
        }
        $data['tracking_id'] = $data['tracking_code'];

        return '' !== trim((string) $data['tracking_code']) ? $data : [];
    }
}
