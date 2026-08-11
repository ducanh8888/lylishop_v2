<?php

namespace Lyli\GHN\Infrastructure\GHN;

final class Status_Mapper
{
    public static function label(string $status): string
    {
        $labels = [
            'ready_to_pick' => __('Mới tạo vận đơn', 'lyli-ghn-connector'),
            'picking' => __('Đang lấy hàng', 'lyli-ghn-connector'),
            'cancel' => __('Đã hủy', 'lyli-ghn-connector'),
            'picked' => __('Đã lấy hàng', 'lyli-ghn-connector'),
            'storing' => __('Đang lưu kho', 'lyli-ghn-connector'),
            'transporting' => __('Đang luân chuyển', 'lyli-ghn-connector'),
            'sorting' => __('Đang phân loại', 'lyli-ghn-connector'),
            'delivering' => __('Đang giao hàng', 'lyli-ghn-connector'),
            'delivered' => __('Đã giao hàng', 'lyli-ghn-connector'),
            'delivery_fail' => __('Giao hàng thất bại', 'lyli-ghn-connector'),
            'waiting_to_return' => __('Đang chờ trả hàng', 'lyli-ghn-connector'),
            'return' => __('Đang trả hàng', 'lyli-ghn-connector'),
            'returning' => __('Đang trả cho shop', 'lyli-ghn-connector'),
            'returned' => __('Đã trả cho shop', 'lyli-ghn-connector'),
            'exception' => __('Vận đơn ngoại lệ', 'lyli-ghn-connector'),
            'damage' => __('Hàng bị hư hỏng', 'lyli-ghn-connector'),
            'lost' => __('Hàng bị thất lạc', 'lyli-ghn-connector'),
        ];

        return $labels[$status] ?? sanitize_text_field($status);
    }
}
