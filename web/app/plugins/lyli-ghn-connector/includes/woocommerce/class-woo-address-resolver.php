<?php

namespace Lyli\GHN\WooCommerce;

use Lyli\GHN\Contracts\Address_Resolver;
use Lyli\GHN\Domain\Address;

final class Woo_Address_Resolver implements Address_Resolver
{
    public function resolve($order)
    {
        $fields = self::order_fields($order);
        if (is_wp_error($fields)) {
            return $fields;
        }

        $province = $this->province_name($fields['province']);
        $ward = trim($fields['ward']);
        if ('' === $province || '' === $ward || ctype_digit($ward) || strlen($ward) < 3) {
            return new \WP_Error(
                'lyli_ghn_native_address_unresolved',
                __('Địa chỉ WooCommerce phải lưu tên Tỉnh/Thành và Phường/Xã, hoặc cần một address adapter tương thích.', 'lyli-ghn-connector')
            );
        }

        return self::address($fields, $province, $ward);
    }

    private function province_name(string $value): string
    {
        $value = trim($value);
        if ('' === $value || ctype_digit($value)) {
            return '';
        }

        if (function_exists('WC') && WC() && isset(WC()->countries)) {
            $states = WC()->countries->get_states('VN');
            if (is_array($states) && isset($states[$value])) {
                return sanitize_text_field((string) $states[$value]);
            }
        }

        return strlen($value) >= 3 ? sanitize_text_field($value) : '';
    }

    /** @param object $order @return array<string,string>|\WP_Error */
    public static function order_fields($order)
    {
        if (! is_object($order)) {
            return new \WP_Error('lyli_ghn_invalid_order', __('Đơn WooCommerce không hợp lệ.', 'lyli-ghn-connector'));
        }

        $has_shipping = method_exists($order, 'get_shipping_address_1')
            && ('' !== trim((string) $order->get_shipping_address_1()) || '' !== trim((string) $order->get_shipping_first_name()));
        $prefix = $has_shipping ? 'shipping' : 'billing';
        $get = static function (string $field) use ($order, $prefix): string {
            $method = 'get_' . $prefix . '_' . $field;
            return method_exists($order, $method) ? trim((string) $order->{$method}()) : '';
        };

        $country = strtoupper($get('country'));
        if ('' !== $country && 'VN' !== $country) {
            return new \WP_Error('lyli_ghn_country', __('GHN connector V1 chỉ hỗ trợ địa chỉ Việt Nam.', 'lyli-ghn-connector'));
        }

        $phone = $get('phone');
        if ('' === $phone && 'shipping' === $prefix && method_exists($order, 'get_billing_phone')) {
            $phone = trim((string) $order->get_billing_phone());
        }

        return [
            'recipient' => trim($get('first_name') . ' ' . $get('last_name')),
            'phone' => $phone,
            'province' => $get('state'),
            'ward' => $get('city'),
            'street' => trim(implode(', ', array_filter([$get('address_1'), $get('address_2')]))),
        ];
    }

    /** @param array<string,string> $fields @return Address|\WP_Error */
    public static function address(array $fields, string $province_name, string $ward_name)
    {
        $phone = preg_replace('/[^0-9+]/', '', $fields['phone']) ?: '';
        if ('' === $fields['recipient'] || '' === $phone || '' === $fields['street']) {
            return new \WP_Error('lyli_ghn_address_incomplete', __('Đơn hàng thiếu tên, số điện thoại hoặc địa chỉ đường.', 'lyli-ghn-connector'));
        }

        return new Address(
            sanitize_text_field($fields['recipient']),
            $phone,
            sanitize_text_field($province_name),
            sanitize_text_field($ward_name),
            sanitize_text_field($fields['street']),
            sanitize_text_field($fields['province']),
            sanitize_text_field($fields['ward'])
        );
    }
}
