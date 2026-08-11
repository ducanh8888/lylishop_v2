<?php

namespace Lyli\GHN\Infrastructure\WooCommerce;

final class Settings_Repository
{
    /** These deployed option names stay stable so existing owner configuration survives 0.2.x. */
    public const SETTINGS_OPTION = 'lyli_ghn_settings';
    public const TOKEN_OPTION = 'lyli_ghn_token';

    /** @return array<string,mixed> */
    public function defaults(): array
    {
        return [
            'enabled' => false,
            'environment' => 'test',
            'shop_id' => 0,
            'service_type_id' => 0,
            'payment_type_id' => 0,
            'required_note' => '',
            'print_format' => 'a5',
            'cod_policy' => 'disabled',
            'insurance_policy' => 'disabled',
            'package_weight_g' => 0,
            'package_length_cm' => 0,
            'package_width_cm' => 0,
            'package_height_cm' => 0,
        ];
    }

    /** @return array<string,mixed> */
    public function get(): array
    {
        $saved = get_option(self::SETTINGS_OPTION, []);
        return array_merge($this->defaults(), is_array($saved) ? $saved : []);
    }

    public function token(): string
    {
        $token = get_option(self::TOKEN_OPTION, '');
        return is_string($token) ? trim($token) : '';
    }

    /** @param array<string,mixed>|null $settings */
    public function is_ready(?array $settings = null): bool
    {
        $settings = $settings ?? $this->get();

        return ! empty($settings['enabled'])
            && in_array($settings['environment'], ['test', 'production'], true)
            && (int) $settings['shop_id'] > 0
            && in_array((int) $settings['service_type_id'], [2, 5], true)
            && in_array((int) $settings['payment_type_id'], [1, 2], true)
            && in_array($settings['required_note'], ['KHONGCHOXEMHANG', 'CHOXEMHANGKHONGTHU', 'CHOTHUHANG'], true)
            && (int) $settings['package_weight_g'] > 0
            && (int) $settings['package_length_cm'] > 0
            && (int) $settings['package_width_cm'] > 0
            && (int) $settings['package_height_cm'] > 0
            && '' !== $this->token();
    }

    /** @param mixed $value */
    public function save_settings($value): void
    {
        $this->write_private_option(self::SETTINGS_OPTION, $value);
    }

    public function save_token(string $token): void
    {
        $this->write_private_option(self::TOKEN_OPTION, sanitize_text_field($token));
    }

    public function delete_token(): void
    {
        delete_option(self::TOKEN_OPTION);
    }

    /** @param mixed $value */
    private function write_private_option(string $name, $value): void
    {
        if (false === get_option($name, false)) {
            add_option($name, $value, '', false);
            return;
        }

        update_option($name, $value, false);
    }
}
