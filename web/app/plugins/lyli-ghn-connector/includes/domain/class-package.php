<?php

namespace Lyli\GHN\Domain;

final class Package
{
    /** @param array<string,mixed> $settings */
    public static function from_settings(array $settings)
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

        return new self($values['weight'], $values['length'], $values['width'], $values['height']);
    }

    public function __construct(
        public readonly int $weight,
        public readonly int $length,
        public readonly int $width,
        public readonly int $height
    ) {
    }

    /** @return array{weight:int,length:int,width:int,height:int} */
    public function to_array(): array
    {
        return ['weight' => $this->weight, 'length' => $this->length, 'width' => $this->width, 'height' => $this->height];
    }
}
