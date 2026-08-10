<?php

namespace Lyli\GHN;

final class Provider
{
    private Api_Client $client;
    private Order_Mapper $mapper;

    public function __construct(Api_Client $client, Order_Mapper $mapper)
    {
        $this->client = $client;
        $this->mapper = $mapper;
    }

    /** @param object $order @param array<string,mixed> $context */
    public function render_create_fields($order, array $context = []): void
    {
        $payload = $this->mapper->build_payload($order, Settings::get());
        if (is_wp_error($payload)) {
            echo '<p class="description" style="color:#b32d2e">' . esc_html($payload->get_error_message()) . '</p>';
            return;
        }

        echo '<p class="description">';
        echo esc_html(sprintf(
            /* translators: 1: package dimensions, 2: package weight, 3: COD amount */
            __('Kiện %1$s cm, %2$s g. Thu hộ dự kiến: %3$s. GHN dùng địa chỉ Việt Nam hai cấp của đơn hàng.', 'lyli-ghn-connector'),
            $payload['length'] . '×' . $payload['width'] . '×' . $payload['height'],
            $payload['weight'],
            wp_strip_all_tags(wc_price($payload['cod_amount']))
        ));
        echo '</p>';
    }

    /** @param object $order @param array<string,mixed> $context */
    public function create_shipment($order, array $context = [])
    {
        $authorized = $this->authorize_action($order);
        if (is_wp_error($authorized)) {
            return $authorized;
        }

        $local = $this->local_data($order);
        if ('lyli_ghn' === ($local['provider'] ?? '') && ! empty($local['tracking_code'])) {
            return $local;
        }

        $settings = Settings::get();
        $payload = $this->mapper->build_payload($order, $settings);
        if (is_wp_error($payload)) {
            return $payload;
        }

        $existing = $this->client->order_info_by_client_code($payload['client_order_code']);
        if (! is_wp_error($existing)) {
            return $this->shipment_data($existing, $payload);
        }
        if (! $this->client->is_not_found_error($existing)) {
            return $existing;
        }

        $preview = $this->client->preview_order($payload);
        if (is_wp_error($preview)) {
            return $preview;
        }

        $created = $this->client->create_order($payload);
        if (is_wp_error($created)) {
            return $created;
        }

        return $this->shipment_data($created, $payload);
    }

    /** @param object $order @param array<string,mixed> $context */
    public function sync_shipment($order, array $context = [])
    {
        $authorized = $this->authorize_action($order);
        if (is_wp_error($authorized)) {
            return $authorized;
        }

        $tracking_code = $this->tracking_code($order);
        if (is_wp_error($tracking_code)) {
            return $tracking_code;
        }

        $data = $this->client->order_info($tracking_code);
        if (is_wp_error($data)) {
            return $data;
        }

        return $this->shipment_data($data);
    }

    /** @param object $order @param array<string,mixed> $context */
    public function cancel_shipment($order, array $context = [])
    {
        $authorized = $this->authorize_action($order);
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

        return [
            'tracking_code' => $tracking_code,
            'tracking_id' => $tracking_code,
            'label_id' => $tracking_code,
            'status_id' => 'cancel',
            'status' => self::status_label('cancel'),
            'raw_response' => $data,
        ];
    }

    /** @param object $order @param array<string,mixed> $context */
    public function print_shipment($order, array $context = [])
    {
        $authorized = $this->authorize_action($order);
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

        $url = $this->client->build_print_url($token, sanitize_key((string) (Settings::get()['print_format'] ?? 'a5')));
        if (is_wp_error($url)) {
            return $url;
        }

        return [
            'url' => $url,
        ];
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
            'service_code' => (string) $service_type,
            'service_name' => 5 === $service_type ? __('GHN Hàng nặng', 'lyli-ghn-connector') : __('GHN Hàng nhẹ', 'lyli-ghn-connector'),
            'label_id' => $order_code,
            'tracking_code' => $order_code,
            'tracking_id' => $order_code,
            'tracking_url' => 'https://donhang.ghn.vn/',
            'status_id' => $status_id,
            'status' => self::status_label($status_id),
            'fee' => max(0, (float) $fee),
            'insurance_fee' => max(0, (float) $insurance_fee),
            'cod_amount' => max(0, (float) ($data['cod_amount'] ?? $fallback['cod_amount'] ?? 0)),
            'raw_response' => $data,
        ];
    }

    public static function status_label(string $status): string
    {
        $labels = [
            'ready_to_pick' => __('Mới tạo vận đơn', 'lyli-ghn-connector'),
            'picking' => __('Đang lấy hàng', 'lyli-ghn-connector'),
            'cancel' => __('Đã hủy', 'lyli-ghn-connector'),
            'money_collect_picking' => __('Đang thu tiền người gửi', 'lyli-ghn-connector'),
            'picked' => __('Đã lấy hàng', 'lyli-ghn-connector'),
            'storing' => __('Đang lưu kho', 'lyli-ghn-connector'),
            'transporting' => __('Đang luân chuyển', 'lyli-ghn-connector'),
            'sorting' => __('Đang phân loại', 'lyli-ghn-connector'),
            'delivering' => __('Đang giao hàng', 'lyli-ghn-connector'),
            'money_collect_delivering' => __('Đang thu tiền người nhận', 'lyli-ghn-connector'),
            'delivered' => __('Đã giao hàng', 'lyli-ghn-connector'),
            'delivery_fail' => __('Giao hàng thất bại', 'lyli-ghn-connector'),
            'waiting_to_return' => __('Đang chờ trả hàng', 'lyli-ghn-connector'),
            'return' => __('Đang trả hàng', 'lyli-ghn-connector'),
            'return_transporting' => __('Đang luân chuyển hàng trả', 'lyli-ghn-connector'),
            'return_sorting' => __('Đang phân loại hàng trả', 'lyli-ghn-connector'),
            'returning' => __('Đang trả cho shop', 'lyli-ghn-connector'),
            'return_fail' => __('Trả hàng thất bại', 'lyli-ghn-connector'),
            'returned' => __('Đã trả cho shop', 'lyli-ghn-connector'),
            'exception' => __('Vận đơn ngoại lệ', 'lyli-ghn-connector'),
            'damage' => __('Hàng bị hư hỏng', 'lyli-ghn-connector'),
            'lost' => __('Hàng bị thất lạc', 'lyli-ghn-connector'),
        ];

        return $labels[$status] ?? sanitize_text_field($status);
    }

    /** @param object $order */
    private function local_data($order): array
    {
        if (! class_exists('Yoohw_Vietnam_Store_Tools_Shipping')) {
            return [];
        }

        $data = \Yoohw_Vietnam_Store_Tools_Shipping::get_order_shipping_data($order, true);
        return is_array($data) ? $data : [];
    }

    /** @param object $order */
    private function tracking_code($order)
    {
        $data = $this->local_data($order);
        $tracking_code = sanitize_text_field((string) ($data['tracking_code'] ?? ''));
        if ('' === $tracking_code || 'lyli_ghn' !== ($data['provider'] ?? '')) {
            return new \WP_Error('lyli_ghn_missing_tracking', __('Đơn hàng chưa có vận đơn GHN.', 'lyli-ghn-connector'));
        }

        return $tracking_code;
    }

    /** @param array<string,mixed> $data @return array<string,mixed> */
    private function first_row(array $data): array
    {
        if (isset($data[0]) && is_array($data[0])) {
            return $data[0];
        }

        return $data;
    }

    /** @param object $order */
    private function authorize_action($order)
    {
        if (! current_user_can('manage_woocommerce')) {
            return new \WP_Error('lyli_ghn_forbidden', __('Bạn không có quyền thao tác vận đơn GHN.', 'lyli-ghn-connector'));
        }

        if (! is_object($order) || ! method_exists($order, 'get_id') || absint($order->get_id()) < 1) {
            return new \WP_Error('lyli_ghn_invalid_order', __('Đơn WooCommerce không hợp lệ.', 'lyli-ghn-connector'));
        }

        return true;
    }
}
