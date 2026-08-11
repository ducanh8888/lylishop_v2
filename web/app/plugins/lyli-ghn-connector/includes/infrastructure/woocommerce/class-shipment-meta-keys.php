<?php

namespace Lyli\GHN\Infrastructure\WooCommerce;

final class Shipment_Meta_Keys
{
    public const CANONICAL = [
        'provider' => '_openship_ghn_provider',
        'order_code' => '_openship_ghn_order_code',
        'client_order_code' => '_openship_ghn_client_order_code',
        'service_code' => '_openship_ghn_service_code',
        'service_name' => '_openship_ghn_service_name',
        'status_id' => '_openship_ghn_status',
        'status' => '_openship_ghn_status_label',
        'tracking_url' => '_openship_ghn_tracking_url',
        'fee' => '_openship_ghn_fee',
        'insurance_fee' => '_openship_ghn_insurance_fee',
        'cod_amount' => '_openship_ghn_cod_amount',
        'last_synced' => '_openship_ghn_last_synced_at',
    ];

    private function __construct()
    {
    }
}
