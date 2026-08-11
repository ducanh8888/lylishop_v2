<?php

namespace Lyli\GHN\Application;

use Lyli\GHN\Api_Client;
use Lyli\GHN\Infrastructure\GHN\Status_Mapper;
use Lyli\GHN\Infrastructure\WooCommerce\Settings_Repository;
use Lyli\GHN\Order_Mapper;
use Lyli\GHN\WooCommerce\Shipment_Repository;

/** The single Create/Sync/Cancel/Print workflow used by every admin integration. */
final class Shipment_Application
{
    public function __construct(
        private Api_Client $client,
        private Order_Mapper $mapper,
        private Shipment_Repository $shipments,
        private Settings_Repository $settings
    ) {
    }

    public function preview_payload($order)
    {
        return $this->mapper->build_payload($order, $this->settings->get());
    }

    public function create($order)
    {
        $authorized = $this->authorize($order);
        if (is_wp_error($authorized)) {
            return $authorized;
        }
        $local = $this->shipments->read($order);
        if ('ghn' === ($local['provider'] ?? '') && ! empty($local['tracking_code'])) {
            return $local;
        }

        $payload = $this->preview_payload($order);
        if (is_wp_error($payload)) {
            return $payload;
        }
        $existing = $this->client->order_info_by_client_code($payload['client_order_code']);
        if (! is_wp_error($existing)) {
            return $this->persist($order, $this->shipment_data($existing, $payload));
        }
        if (! $this->client->is_not_found_error($existing)) {
            return $existing;
        }
        $preview = $this->client->preview_order($payload);
        if (is_wp_error($preview)) {
            return $preview;
        }
        $created = $this->client->create_order($payload);

        return is_wp_error($created) ? $created : $this->persist($order, $this->shipment_data($created, $payload));
    }

    public function sync($order)
    {
        $authorized = $this->authorize($order);
        if (is_wp_error($authorized)) {
            return $authorized;
        }
        $tracking_code = $this->tracking_code($order);
        if (is_wp_error($tracking_code)) {
            return $tracking_code;
        }
        $data = $this->client->order_info($tracking_code);

        return is_wp_error($data)
            ? $data
            : $this->persist($order, $this->shipment_data($data, ['client_order_code' => Order_Mapper::client_order_code($order)]));
    }

    public function cancel($order)
    {
        $authorized = $this->authorize($order);
        if (is_wp_error($authorized)) {
            return $authorized;
        }
        $tracking_code = $this->tracking_code($order);
        if (is_wp_error($tracking_code)) {
            return $tracking_code;
        }
        $data = $this->client->cancel_order($tracking_code);
        if (is_wp_error($data)) {
            return $data;
        }
        $row = $this->first_row($data);
        if (isset($row['result']) && ! $row['result']) {
            return new \WP_Error('lyli_ghn_cancel_rejected', sanitize_text_field((string) ($row['message'] ?? __('GHN từ chối hủy vận đơn.', 'lyli-ghn-connector'))));
        }

        return $this->persist($order, [
            'order_code' => $tracking_code,
            'tracking_code' => $tracking_code,
            'client_order_code' => Order_Mapper::client_order_code($order),
            'tracking_url' => 'https://donhang.ghn.vn/',
            'status_id' => 'cancel',
            'status' => Status_Mapper::label('cancel'),
        ]);
    }

    public function print($order)
    {
        $authorized = $this->authorize($order);
        if (is_wp_error($authorized)) {
            return $authorized;
        }
        $tracking_code = $this->tracking_code($order);
        if (is_wp_error($tracking_code)) {
            return $tracking_code;
        }
        $print = $this->client->print_order($tracking_code);
        if (is_wp_error($print)) {
            return $print;
        }
        $token = is_array($print) ? (string) ($print['token'] ?? '') : '';
        if ('' === $token) {
            return new \WP_Error('lyli_ghn_missing_print_token', __('GHN không trả về print token.', 'lyli-ghn-connector'));
        }
        $url = $this->client->build_print_url($token, sanitize_key((string) ($this->settings->get()['print_format'] ?? 'a5')));

        return is_wp_error($url) ? $url : ['url' => $url];
    }

    private function tracking_code($order)
    {
        $data = $this->shipments->read($order);
        $tracking_code = sanitize_text_field((string) ($data['tracking_code'] ?? ''));
        return '' !== $tracking_code ? $tracking_code : new \WP_Error('lyli_ghn_missing_tracking', __('Đơn hàng chưa có vận đơn GHN.', 'lyli-ghn-connector'));
    }

    private function authorize($order)
    {
        if (! current_user_can('manage_woocommerce')) {
            return new \WP_Error('lyli_ghn_forbidden', __('Bạn không có quyền thao tác vận đơn GHN.', 'lyli-ghn-connector'));
        }
        if (! is_object($order) || ! method_exists($order, 'get_id') || absint($order->get_id()) < 1) {
            return new \WP_Error('lyli_ghn_invalid_order', __('Đơn WooCommerce không hợp lệ.', 'lyli-ghn-connector'));
        }
        return true;
    }

    private function persist($order, $data)
    {
        return is_wp_error($data) ? $data : $this->shipments->save($order, $data);
    }

    /** @param array<string,mixed> $data @param array<string,mixed> $fallback */
    private function shipment_data(array $data, array $fallback = [])
    {
        $data = $this->first_row($data);
        $order_code = sanitize_text_field((string) ($data['order_code'] ?? ''));
        if ('' === $order_code) {
            return new \WP_Error('lyli_ghn_missing_order_code', __('GHN không trả về mã vận đơn.', 'lyli-ghn-connector'));
        }
        $status_id = sanitize_key((string) ($data['status'] ?? 'ready_to_pick'));
        $fee = $data['total_fee'] ?? $data['fee']['total'] ?? $data['fee']['main_service'] ?? 0;
        $insurance_fee = $data['fee']['insurance'] ?? $data['insurance_fee'] ?? 0;
        $service_type = (int) ($data['service_type_id'] ?? $fallback['service_type_id'] ?? 0);

        return [
            'order_code' => $order_code,
            'client_order_code' => sanitize_text_field((string) ($data['client_order_code'] ?? $fallback['client_order_code'] ?? '')),
            'service_code' => (string) $service_type,
            'service_name' => 5 === $service_type ? __('GHN Hàng nặng', 'lyli-ghn-connector') : __('GHN Hàng nhẹ', 'lyli-ghn-connector'),
            'tracking_url' => 'https://donhang.ghn.vn/',
            'status_id' => $status_id,
            'status' => Status_Mapper::label($status_id),
            'fee' => max(0, (float) $fee),
            'insurance_fee' => max(0, (float) $insurance_fee),
            'cod_amount' => max(0, (float) ($data['cod_amount'] ?? $fallback['cod_amount'] ?? 0)),
        ];
    }

    /** @param array<string,mixed> $data @return array<string,mixed> */
    private function first_row(array $data): array
    {
        return isset($data[0]) && is_array($data[0]) ? $data[0] : $data;
    }
}
