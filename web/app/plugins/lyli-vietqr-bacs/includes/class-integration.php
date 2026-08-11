<?php

namespace Lyli\VietQRBACS;

final class Integration extends \WC_Integration
{
    public function __construct()
    {
        $this->id = 'lyli_vietqr_bacs';
        $this->method_title = __('VietQR cho Chuyển khoản ngân hàng', 'lyli-vietqr-bacs');
        $this->method_description = __('Hiển thị VietQR được tạo nội bộ cho đơn dùng phương thức BACS. Không tự xác nhận thanh toán.', 'lyli-vietqr-bacs');
        $this->init_form_fields();
        $this->init_settings();

        add_action('woocommerce_update_options_integration_' . $this->id, [$this, 'process_admin_options']);
    }

    public function init_form_fields(): void
    {
        $this->form_fields = [
            'enabled' => [
                'title' => __('Bật VietQR cho BACS', 'lyli-vietqr-bacs'),
                'type' => 'checkbox',
                'label' => __('Hiển thị QR sau khi khách chọn Chuyển khoản ngân hàng', 'lyli-vietqr-bacs'),
                'default' => 'no',
            ],
            'bank_bin' => [
                'title' => __('Mã BIN ngân hàng', 'lyli-vietqr-bacs'),
                'type' => 'text',
                'description' => __('Mã BIN gồm đúng 6 chữ số.', 'lyli-vietqr-bacs'),
                'desc_tip' => true,
                'default' => '',
            ],
            'account_number' => [
                'title' => __('Số tài khoản', 'lyli-vietqr-bacs'),
                'type' => 'text',
                'default' => '',
            ],
            'account_holder' => [
                'title' => __('Tên chủ tài khoản', 'lyli-vietqr-bacs'),
                'type' => 'text',
                'default' => '',
            ],
            'reference_prefix' => [
                'title' => __('Tiền tố nội dung chuyển khoản', 'lyli-vietqr-bacs'),
                'type' => 'text',
                'description' => __('Chỉ chữ và số; mã đơn sẽ được nối phía sau. Tối đa toàn bộ 25 ký tự.', 'lyli-vietqr-bacs'),
                'desc_tip' => true,
                'default' => 'LYLI',
            ],
        ];
    }

    public function enabled(): bool
    {
        return 'yes' === $this->get_option('enabled', 'no');
    }

    public function validate_text_field($key, $value): string
    {
        $value = sanitize_text_field((string) $value);
        return match ($key) {
            'bank_bin' => substr(preg_replace('/\D/', '', $value) ?: '', 0, 6),
            'account_number' => substr(preg_replace('/[^A-Za-z0-9]/', '', $value) ?: '', 0, 19),
            'reference_prefix' => substr(strtoupper(preg_replace('/[^A-Za-z0-9]/', '', $value) ?: ''), 0, 15),
            default => $value,
        };
    }

    /** @return array<string,string> */
    public function merchant(): array
    {
        return [
            'bank_bin' => preg_replace('/\D/', '', (string) $this->get_option('bank_bin', '')) ?: '',
            'account_number' => preg_replace('/[^A-Za-z0-9]/', '', (string) $this->get_option('account_number', '')) ?: '',
            'account_holder' => sanitize_text_field((string) $this->get_option('account_holder', '')),
            'reference_prefix' => strtoupper(preg_replace('/[^A-Za-z0-9]/', '', (string) $this->get_option('reference_prefix', 'LYLI')) ?: 'LYLI'),
        ];
    }
}
