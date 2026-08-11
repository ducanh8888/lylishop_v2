<?php

namespace Lyli\VietQRBACS;

final class Order_Presentation
{
    /** @var array<int,bool> */
    private array $rendered = [];

    public function __construct(
        private Integration $integration,
        private Payload_Factory $payload_factory,
        private QR_Renderer $renderer
    ) {
    }

    public function init(): void
    {
        add_action('woocommerce_thankyou_bacs', [$this, 'render_by_id'], 20);
        add_action('woocommerce_receipt_bacs', [$this, 'render_by_id'], 20);
        add_action('woocommerce_order_details_after_order_table', [$this, 'render_order'], 20);
    }

    public function render_by_id(int $order_id): void
    {
        $order = wc_get_order($order_id);
        if ($order) {
            $this->render_order($order);
        }
    }

    public function render_order($order): void
    {
        if (! $this->integration->enabled()
            || ! is_object($order)
            || ! method_exists($order, 'get_id')
            || 'bacs' !== (string) $order->get_payment_method()
            || isset($this->rendered[(int) $order->get_id()])
        ) {
            return;
        }

        if (! current_user_can('manage_woocommerce') && method_exists($order, 'get_user_id')) {
            $order_user_id = (int) $order->get_user_id();
            if ($order_user_id > 0 && (! is_user_logged_in() || $order_user_id !== get_current_user_id())) {
                return;
            }
            if (0 === $order_user_id && method_exists($order, 'get_order_key')) {
                $request_key = isset($_GET['key']) ? wc_clean(wp_unslash($_GET['key'])) : '';
                if ('' === $request_key || ! hash_equals((string) $order->get_order_key(), (string) $request_key)) {
                    return;
                }
            }
        }

        $merchant = $this->integration->merchant();
        try {
            $amount = $this->payload_factory->amount($order);
            $reference = $this->payload_factory->reference($merchant['reference_prefix'], (string) $order->get_order_number());
            $payload = $this->payload_factory->build($merchant['bank_bin'], $merchant['account_number'], $amount, $reference);
            $qr_uri = $this->renderer->data_uri($payload);
        } catch (\Throwable $exception) {
            if (current_user_can('manage_woocommerce')) {
                echo '<p class="woocommerce-info">' . esc_html__('VietQR chưa thể hiển thị. Hãy kiểm tra cấu hình BIN, số tài khoản và số tiền.', 'lyli-vietqr-bacs') . '</p>';
            }
            return;
        }

        $this->rendered[(int) $order->get_id()] = true;
        echo '<section class="woocommerce-order-details lyli-vietqr-bacs">';
        echo '<h2>' . esc_html__('Quét mã để chuyển khoản', 'lyli-vietqr-bacs') . '</h2>';
        echo '<p>' . esc_html__('Đơn hàng chỉ được xác nhận thanh toán sau khi chủ shop kiểm tra giao dịch.', 'lyli-vietqr-bacs') . '</p>';
        echo '<img width="280" height="280" loading="lazy" alt="' . esc_attr__('Mã VietQR cho đơn hàng', 'lyli-vietqr-bacs') . '" src="' . esc_attr($qr_uri) . '">';
        echo '<dl>';
        echo '<dt>' . esc_html__('Chủ tài khoản', 'lyli-vietqr-bacs') . '</dt><dd>' . esc_html($merchant['account_holder']) . '</dd>';
        echo '<dt>' . esc_html__('Số tài khoản', 'lyli-vietqr-bacs') . '</dt><dd>' . esc_html($merchant['account_number']) . '</dd>';
        echo '<dt>' . esc_html__('Số tiền', 'lyli-vietqr-bacs') . '</dt><dd>' . wp_kses_post(wc_price($amount, ['currency' => 'VND'])) . '</dd>';
        echo '<dt>' . esc_html__('Nội dung', 'lyli-vietqr-bacs') . '</dt><dd><code>' . esc_html($reference) . '</code></dd>';
        echo '</dl></section>';
    }
}
