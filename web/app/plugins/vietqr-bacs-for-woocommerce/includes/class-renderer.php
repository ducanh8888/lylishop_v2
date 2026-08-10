<?php

namespace VietQR\BACS;

final class Renderer
{
    public function __construct(private Qr_Builder $builder)
    {
    }

    public function init(): void
    {
        add_action('woocommerce_thankyou_bacs', [$this, 'render'], 20);
        add_action('woocommerce_receipt_bacs', [$this, 'render'], 20);
        add_action('woocommerce_view_order', [$this, 'render'], 20);
    }

    public function render($order_id): void
    {
        $settings = get_option(SETTINGS_OPTION, []);
        if (! is_array($settings) || 'yes' !== ($settings['enabled'] ?? 'no')) {
            return;
        }
        $order = wc_get_order($order_id);
        if (! $order) {
            return;
        }
        $data = $this->builder->build($order, $settings);
        if (is_wp_error($data)) {
            return;
        }

        echo '<section class="woocommerce-order-details vietqr-bacs-payment"><h2 class="woocommerce-order-details__title">' . esc_html__('Chuyển khoản ngân hàng qua VietQR', 'vietqr-bacs-for-woocommerce') . '</h2>';
        echo '<p>' . esc_html__('Quét mã bằng ứng dụng ngân hàng và giữ nguyên số tiền cùng nội dung chuyển khoản.', 'vietqr-bacs-for-woocommerce') . '</p>';
        echo '<p><img src="' . esc_url($data['url']) . '" alt="' . esc_attr__('Mã VietQR cho đơn hàng', 'vietqr-bacs-for-woocommerce') . '" loading="lazy" decoding="async" width="360" height="360"></p>';
        echo '<dl><dt>' . esc_html__('Ngân hàng', 'vietqr-bacs-for-woocommerce') . '</dt><dd>' . esc_html($data['bank']) . '</dd>';
        echo '<dt>' . esc_html__('Số tài khoản', 'vietqr-bacs-for-woocommerce') . '</dt><dd>' . esc_html($data['account']) . '</dd>';
        echo '<dt>' . esc_html__('Chủ tài khoản', 'vietqr-bacs-for-woocommerce') . '</dt><dd>' . esc_html($data['holder']) . '</dd>';
        echo '<dt>' . esc_html__('Số tiền', 'vietqr-bacs-for-woocommerce') . '</dt><dd>' . wp_kses_post(wc_price($data['amount'], ['currency' => $order->get_currency()])) . '</dd>';
        echo '<dt>' . esc_html__('Nội dung', 'vietqr-bacs-for-woocommerce') . '</dt><dd><code>' . esc_html($data['description']) . '</code></dd></dl>';
        echo '<p><small>' . esc_html__('Thanh toán vẫn được shop xác nhận thủ công; VietQR không tự đánh dấu đơn đã thanh toán.', 'vietqr-bacs-for-woocommerce') . '</small></p></section>';
    }
}
