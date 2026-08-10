<?php

namespace Lyli\GHN\Integrations\VietnamStoreToolkit;

use Lyli\GHN\Contracts\Address_Resolver;
use Lyli\GHN\WooCommerce\Woo_Address_Resolver;

final class Toolkit_Address_Resolver implements Address_Resolver
{
    public static function is_supported(): bool
    {
        return class_exists('Yoohw_Vietnam_Store_Tools_Vietnam_Address_Data')
            && method_exists('Yoohw_Vietnam_Store_Tools_Vietnam_Address_Data', 'province_exists')
            && method_exists('Yoohw_Vietnam_Store_Tools_Vietnam_Address_Data', 'ward_exists');
    }

    public function resolve($order)
    {
        if (! self::is_supported()) {
            return new \WP_Error('lyli_ghn_toolkit_address_unavailable', __('Vietnam Store Toolkit address adapter không khả dụng.', 'lyli-ghn-connector'));
        }

        $fields = Woo_Address_Resolver::order_fields($order);
        if (is_wp_error($fields)) {
            return $fields;
        }
        $province_code = $fields['province'];
        $ward_code = $fields['ward'];
        if ('' === $province_code || '' === $ward_code
            || ! \Yoohw_Vietnam_Store_Tools_Vietnam_Address_Data::province_exists($province_code)
            || ! \Yoohw_Vietnam_Store_Tools_Vietnam_Address_Data::ward_exists($ward_code, $province_code)
        ) {
            return new \WP_Error('lyli_ghn_toolkit_address_unknown', __('Mã Tỉnh/Thành hoặc Phường/Xã không thuộc dữ liệu hai cấp của Toolkit.', 'lyli-ghn-connector'));
        }

        return Woo_Address_Resolver::address(
            $fields,
            (string) \Yoohw_Vietnam_Store_Tools_Vietnam_Address_Data::get_province_name($province_code),
            (string) \Yoohw_Vietnam_Store_Tools_Vietnam_Address_Data::get_ward_name($ward_code, $province_code)
        );
    }
}
