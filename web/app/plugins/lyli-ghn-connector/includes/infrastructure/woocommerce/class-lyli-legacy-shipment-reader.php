<?php

namespace Lyli\GHN\Infrastructure\WooCommerce;

use Lyli\GHN\Contracts\Legacy_Shipment_Reader;

final class Lyli_Legacy_Shipment_Reader implements Legacy_Shipment_Reader
{
    private const LEGACY = [
        'provider' => '_lyli_ghn_provider',
        'order_code' => '_lyli_ghn_order_code',
        'client_order_code' => '_lyli_ghn_client_order_code',
        'service_code' => '_lyli_ghn_service_code',
        'service_name' => '_lyli_ghn_service_name',
        'status_id' => '_lyli_ghn_status',
        'status' => '_lyli_ghn_status_label',
        'tracking_url' => '_lyli_ghn_tracking_url',
        'fee' => '_lyli_ghn_fee',
        'insurance_fee' => '_lyli_ghn_insurance_fee',
        'cod_amount' => '_lyli_ghn_cod_amount',
        'last_synced' => '_lyli_ghn_last_synced_at',
    ];

    public function read($order): array
    {
        $order_code = sanitize_text_field((string) $order->get_meta(self::LEGACY['order_code'], true));
        if ('' === $order_code) {
            return [];
        }

        $data = ['provider' => 'ghn', 'legacy_source' => 'lyli-ghn-0.1'];
        foreach (self::LEGACY as $key => $meta_key) {
            $data[$key] = $order->get_meta($meta_key, true);
        }
        $data['tracking_code'] = $order_code;
        $data['tracking_id'] = $order_code;
        $data['label_id'] = $order_code;

        return $data;
    }
}
