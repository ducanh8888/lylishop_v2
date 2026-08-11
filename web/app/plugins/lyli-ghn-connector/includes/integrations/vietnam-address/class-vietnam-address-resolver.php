<?php

namespace Lyli\GHN\Integrations\VietnamAddress;

use Lyli\GHN\Contracts\Address_Resolver;
use Lyli\GHN\WooCommerce\Woo_Address_Resolver;
use Lyli\VietnamAddress\Repository;

final class Vietnam_Address_Resolver implements Address_Resolver
{
    public static function is_supported(): bool
    {
        return class_exists(Repository::class);
    }

    public function resolve($order)
    {
        if (! self::is_supported()) {
            return new \WP_Error('lyli_ghn_vietnam_address_unavailable', __('Lyli Vietnam Address adapter không khả dụng.', 'lyli-ghn-connector'));
        }

        $fields = Woo_Address_Resolver::order_fields($order);
        if (is_wp_error($fields)) {
            return $fields;
        }

        $address = Repository::instance()->resolve($fields['province'], $fields['ward'], $fields['street']);
        if (null === $address) {
            return new \WP_Error('lyli_ghn_vietnam_address_unknown', __('Mã Tỉnh/Thành hoặc Phường/Xã không thuộc dữ liệu địa chỉ hai cấp.', 'lyli-ghn-connector'));
        }

        return Woo_Address_Resolver::address($fields, $address->province_name, $address->ward_name);
    }
}
