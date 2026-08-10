<?php

namespace VietQR\BACS;

final class Integration extends \WC_Integration
{
    public function __construct()
    {
        $this->id = 'vietqr_bacs';
        $this->method_title = __('VietQR cho chuyển khoản ngân hàng', 'vietqr-bacs-for-woocommerce');
        $this->method_description = __('Hiển thị VietQR cho đơn dùng WooCommerce BACS. Không tự đối soát hoặc đánh dấu đã thanh toán.', 'vietqr-bacs-for-woocommerce');
        $this->init_form_fields();
        $this->init_settings();
        add_action('woocommerce_update_options_integration_' . $this->id, [$this, 'process_admin_options']);
    }

    public function init_form_fields(): void
    {
        $this->form_fields = [
            'enabled' => [
                'title' => __('Bật VietQR cho BACS', 'vietqr-bacs-for-woocommerce'),
                'type' => 'checkbox',
                'label' => __('Hiển thị QR trên các trang thanh toán đơn BACS phù hợp', 'vietqr-bacs-for-woocommerce'),
                'default' => 'no',
            ],
            'bank_bin' => [
                'title' => __('Mã ngân hàng / BIN', 'vietqr-bacs-for-woocommerce'),
                'type' => 'text',
                'description' => __('Nhập mã BIN hoặc mã ngân hàng được VietQR hỗ trợ.', 'vietqr-bacs-for-woocommerce'),
                'desc_tip' => true,
            ],
            'account_number' => [
                'title' => __('Số tài khoản', 'vietqr-bacs-for-woocommerce'),
                'type' => 'text',
            ],
            'account_holder' => [
                'title' => __('Chủ tài khoản', 'vietqr-bacs-for-woocommerce'),
                'type' => 'text',
            ],
            'qr_template' => [
                'title' => __('Mẫu VietQR', 'vietqr-bacs-for-woocommerce'),
                'type' => 'select',
                'default' => 'compact2',
                'options' => [
                    'compact2' => 'Compact 2',
                    'compact' => 'Compact',
                    'qr_only' => 'QR only',
                    'print' => 'Print',
                ],
            ],
            'description_template' => [
                'title' => __('Mẫu nội dung chuyển khoản', 'vietqr-bacs-for-woocommerce'),
                'type' => 'text',
                'default' => 'ORDER-{order_number}',
                'description' => __('Biến hỗ trợ: {order_number}, {order_id}. Nội dung cuối được chuẩn hóa an toàn.', 'vietqr-bacs-for-woocommerce'),
                'desc_tip' => true,
            ],
        ];
    }
}
