<?php

namespace Lyli\GHN\WooCommerce;

use Lyli\GHN\Contracts\Legacy_Shipment_Reader;
use Lyli\GHN\Infrastructure\WooCommerce\Shipment_Meta_Keys;

final class Shipment_Repository
{
    /** @param array<int,Legacy_Shipment_Reader> $legacy_readers */
    public function __construct(private array $legacy_readers = [])
    {
    }

    /** @param object $order @return array<string,mixed> */
    public function read($order): array
    {
        if (! is_object($order) || ! method_exists($order, 'get_meta')) {
            return [];
        }

        $order_code = sanitize_text_field((string) $order->get_meta(Shipment_Meta_Keys::CANONICAL['order_code'], true));
        if ('' === $order_code) {
            foreach ($this->legacy_readers as $reader) {
                $legacy = $reader->read($order);
                if ([] !== $legacy) {
                    return $legacy;
                }
            }
            return [];
        }

        $data = [];
        foreach (Shipment_Meta_Keys::CANONICAL as $key => $meta_key) {
            $data[$key] = $order->get_meta($meta_key, true);
        }
        $data['provider'] = 'ghn';
        $data['tracking_code'] = $order_code;
        $data['tracking_id'] = $order_code;
        $data['label_id'] = $order_code;

        return $data;
    }

    /** @param object $order @param array<string,mixed> $data @return array<string,mixed>|\WP_Error */
    public function save($order, array $data)
    {
        if (! is_object($order) || ! method_exists($order, 'update_meta_data') || ! method_exists($order, 'save_meta_data')) {
            return new \WP_Error('lyli_ghn_persistence_order', __('Không thể lưu trạng thái GHN vào đơn WooCommerce.', 'lyli-ghn-connector'));
        }
        $order_code = sanitize_text_field((string) ($data['order_code'] ?? $data['tracking_code'] ?? ''));
        if ('' === $order_code) {
            return new \WP_Error('lyli_ghn_persistence_code', __('Trạng thái GHN thiếu order_code.', 'lyli-ghn-connector'));
        }

        $existing = $this->read($order);
        $normalized = [
            'provider' => 'ghn',
            'order_code' => $order_code,
            'client_order_code' => sanitize_text_field((string) ($data['client_order_code'] ?? $existing['client_order_code'] ?? '')),
            'service_code' => sanitize_text_field((string) ($data['service_code'] ?? $existing['service_code'] ?? '')),
            'service_name' => sanitize_text_field((string) ($data['service_name'] ?? $existing['service_name'] ?? '')),
            'status_id' => sanitize_key((string) ($data['status_id'] ?? $existing['status_id'] ?? '')),
            'status' => sanitize_text_field((string) ($data['status'] ?? $existing['status'] ?? '')),
            'tracking_url' => esc_url_raw((string) ($data['tracking_url'] ?? $existing['tracking_url'] ?? 'https://donhang.ghn.vn/')),
            'fee' => max(0, (float) ($data['fee'] ?? $existing['fee'] ?? 0)),
            'insurance_fee' => max(0, (float) ($data['insurance_fee'] ?? $existing['insurance_fee'] ?? 0)),
            'cod_amount' => max(0, (float) ($data['cod_amount'] ?? $existing['cod_amount'] ?? 0)),
            'last_synced' => gmdate('c'),
        ];

        foreach (Shipment_Meta_Keys::CANONICAL as $key => $meta_key) {
            $order->update_meta_data($meta_key, $normalized[$key]);
        }
        $order->save_meta_data();

        return $this->read($order);
    }
}
