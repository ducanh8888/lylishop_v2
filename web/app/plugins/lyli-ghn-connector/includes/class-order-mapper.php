<?php

namespace Lyli\GHN;

final class Order_Mapper
{
    /** @param object $order @param array<string,mixed> $settings */
    public function build_payload($order, array $settings)
    {
        $package = $this->package($settings);
        if (is_wp_error($package)) {
            return $package;
        }

        $destination = $this->destination($order);
        if (is_wp_error($destination)) {
            return $destination;
        }

        $items = $this->items($order, (int) $settings['service_type_id']);
        if (is_wp_error($items)) {
            return $items;
        }

        $payload = array_merge($destination, $package, [
            'client_order_code' => self::client_order_code($order),
            'service_type_id' => (int) $settings['service_type_id'],
            'payment_type_id' => (int) $settings['payment_type_id'],
            'required_note' => (string) $settings['required_note'],
            'cod_amount' => self::cod_amount($order, $settings),
            'insurance_value' => self::insurance_value($order, $settings),
            'content' => $this->content($items),
            'items' => $items,
        ]);

        $note = sanitize_textarea_field((string) $order->get_customer_note());
        if ('' !== $note) {
            $payload['note'] = mb_substr($note, 0, 5000);
        }

        return $payload;
    }

    /** @param object $order */
    public static function client_order_code($order): string
    {
        return 'LYLI-WC-' . absint($order->get_id());
    }

    /** @param object $order @param array<string,mixed> $settings */
    public static function cod_amount($order, array $settings): int
    {
        if ('cod_gateway_only' !== ($settings['cod_policy'] ?? 'disabled')) {
            return 0;
        }

        if ('cod' !== (string) $order->get_payment_method() || $order->is_paid()) {
            return 0;
        }

        return min(50000000, self::remaining_total($order));
    }

    /** @param object $order @param array<string,mixed> $settings */
    public static function insurance_value($order, array $settings): int
    {
        if ('remaining_total' !== ($settings['insurance_policy'] ?? 'disabled')) {
            return 0;
        }

        return min(5000000, self::remaining_total($order));
    }

    /** @param object $order */
    private static function remaining_total($order): int
    {
        $remaining = (float) $order->get_total() - (float) $order->get_total_refunded();
        return max(0, (int) round($remaining));
    }

    /** @param array<string,mixed> $settings */
    private function package(array $settings)
    {
        $values = [
            'weight' => (int) ($settings['package_weight_g'] ?? 0),
            'length' => (int) ($settings['package_length_cm'] ?? 0),
            'width' => (int) ($settings['package_width_cm'] ?? 0),
            'height' => (int) ($settings['package_height_cm'] ?? 0),
        ];

        if (min($values) < 1) {
            return new \WP_Error('lyli_ghn_missing_package', __('Thiếu khối lượng hoặc kích thước kiện hàng GHN.', 'lyli-ghn-connector'));
        }

        if ($values['weight'] > 50000 || max($values['length'], $values['width'], $values['height']) > 200) {
            return new \WP_Error('lyli_ghn_package_limit', __('Kiện hàng vượt giới hạn GHN đã cấu hình.', 'lyli-ghn-connector'));
        }

        return $values;
    }

    /** @param object $order */
    private function destination($order)
    {
        $has_shipping = '' !== trim((string) $order->get_shipping_address_1())
            || '' !== trim((string) $order->get_shipping_first_name());
        $prefix = $has_shipping ? 'shipping' : 'billing';
        $get = static function (string $field) use ($order, $prefix): string {
            $method = 'get_' . $prefix . '_' . $field;
            return method_exists($order, $method) ? trim((string) $order->{$method}()) : '';
        };

        $country = strtoupper($get('country'));
        if ('' !== $country && 'VN' !== $country) {
            return new \WP_Error('lyli_ghn_country', __('GHN connector V1 chỉ hỗ trợ địa chỉ Việt Nam.', 'lyli-ghn-connector'));
        }

        $province_code = $get('state');
        $ward_code = $get('city');
        if ('' === $province_code || '' === $ward_code || ! class_exists('Yoohw_Vietnam_Store_Tools_Vietnam_Address_Data')) {
            return new \WP_Error('lyli_ghn_address_codes', __('Đơn hàng thiếu Tỉnh/Thành hoặc Phường/Xã theo Vietnam Store Toolkit.', 'lyli-ghn-connector'));
        }

        if (! \Yoohw_Vietnam_Store_Tools_Vietnam_Address_Data::province_exists($province_code)
            || ! \Yoohw_Vietnam_Store_Tools_Vietnam_Address_Data::ward_exists($ward_code, $province_code)) {
            return new \WP_Error('lyli_ghn_address_unknown', __('Mã Tỉnh/Thành hoặc Phường/Xã không thuộc dữ liệu hai cấp hiện tại.', 'lyli-ghn-connector'));
        }

        $province_name = \Yoohw_Vietnam_Store_Tools_Vietnam_Address_Data::get_province_name($province_code);
        $ward_name = \Yoohw_Vietnam_Store_Tools_Vietnam_Address_Data::get_ward_name($ward_code, $province_code);
        $street = trim(implode(', ', array_filter([$get('address_1'), $get('address_2')])));
        $name = trim($get('first_name') . ' ' . $get('last_name'));
        $phone = $get('phone');
        if ('' === $phone && 'shipping' === $prefix && method_exists($order, 'get_billing_phone')) {
            $phone = trim((string) $order->get_billing_phone());
        }

        $normalized_phone = preg_replace('/[^0-9+]/', '', $phone) ?: '';
        if ('' === $name || '' === $normalized_phone || '' === $street) {
            return new \WP_Error('lyli_ghn_address_incomplete', __('Đơn hàng thiếu tên, số điện thoại hoặc địa chỉ đường.', 'lyli-ghn-connector'));
        }

        return [
            'to_name' => mb_substr(sanitize_text_field($name), 0, 1024),
            'to_phone' => mb_substr($normalized_phone, 0, 20),
            'to_address' => mb_substr(sanitize_text_field($street . ', ' . $ward_name . ', ' . $province_name), 0, 1024),
            'to_ward_name' => sanitize_text_field($ward_name),
            'to_province_name' => sanitize_text_field($province_name),
            'is_new_to_address' => true,
        ];
    }

    /** @param object $order */
    private function items($order, int $service_type_id)
    {
        $mapped = [];
        foreach ($order->get_items() as $item) {
            $quantity = max(1, (int) $item->get_quantity());
            $product = method_exists($item, 'get_product') ? $item->get_product() : null;
            $entry = [
                'name' => mb_substr(sanitize_text_field((string) $item->get_name()), 0, 255),
                'quantity' => $quantity,
                'price' => max(0, (int) round(((float) $item->get_total()) / $quantity)),
            ];
            if ($product && '' !== (string) $product->get_sku()) {
                $entry['code'] = mb_substr(sanitize_text_field((string) $product->get_sku()), 0, 100);
            }

            if (5 === $service_type_id) {
                $dimensions = $this->product_dimensions($product);
                if (is_wp_error($dimensions)) {
                    return $dimensions;
                }
                $entry = array_merge($entry, $dimensions);
            }

            $mapped[] = $entry;
        }

        if ([] === $mapped) {
            return new \WP_Error('lyli_ghn_no_items', __('Đơn hàng không có sản phẩm để tạo vận đơn GHN.', 'lyli-ghn-connector'));
        }

        return $mapped;
    }

    private function product_dimensions($product)
    {
        if (! $product) {
            return new \WP_Error('lyli_ghn_missing_item_dimensions', __('Hàng nặng GHN cần đủ khối lượng và kích thước cho từng sản phẩm.', 'lyli-ghn-connector'));
        }

        $values = [
            'weight' => (int) round((float) wc_get_weight($product->get_weight(), 'g')),
            'length' => (int) round((float) wc_get_dimension($product->get_length(), 'cm')),
            'width' => (int) round((float) wc_get_dimension($product->get_width(), 'cm')),
            'height' => (int) round((float) wc_get_dimension($product->get_height(), 'cm')),
        ];
        if (min($values) < 1) {
            return new \WP_Error('lyli_ghn_missing_item_dimensions', __('Hàng nặng GHN cần đủ khối lượng và kích thước cho từng sản phẩm.', 'lyli-ghn-connector'));
        }

        return $values;
    }

    /** @param array<int,array<string,mixed>> $items */
    private function content(array $items): string
    {
        $names = array_map(static fn (array $item): string => (string) $item['name'], $items);
        return mb_substr(implode(', ', $names), 0, 2000);
    }
}
