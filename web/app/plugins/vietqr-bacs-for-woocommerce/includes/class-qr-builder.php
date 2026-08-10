<?php

namespace VietQR\BACS;

final class Qr_Builder
{
    private const HOST = 'img.vietqr.io';
    private const TEMPLATES = ['compact2', 'compact', 'qr_only', 'print'];

    /** @param array<string,mixed> $settings @return array<string,mixed>|\WP_Error */
    public function build($order, array $settings)
    {
        if (! is_object($order) || ! method_exists($order, 'get_payment_method') || 'bacs' !== $order->get_payment_method()) {
            return new \WP_Error('vietqr_bacs_wrong_gateway', __('Đơn hàng không dùng BACS.', 'vietqr-bacs-for-woocommerce'));
        }
        if (method_exists($order, 'is_paid') && $order->is_paid()) {
            return new \WP_Error('vietqr_bacs_paid', __('Đơn hàng đã được thanh toán.', 'vietqr-bacs-for-woocommerce'));
        }

        $bank = strtoupper(preg_replace('/[^A-Za-z0-9]/', '', (string) ($settings['bank_bin'] ?? '')) ?: '');
        $account = preg_replace('/[^A-Za-z0-9]/', '', (string) ($settings['account_number'] ?? '')) ?: '';
        $holder = sanitize_text_field((string) ($settings['account_holder'] ?? ''));
        $template = sanitize_key((string) ($settings['qr_template'] ?? 'compact2'));
        $template = in_array($template, self::TEMPLATES, true) ? $template : 'compact2';
        if ('' === $bank || '' === $account || '' === $holder) {
            return new \WP_Error('vietqr_bacs_incomplete', __('Cấu hình VietQR chưa đầy đủ.', 'vietqr-bacs-for-woocommerce'));
        }

        $amount = max(0, (int) round((float) $order->get_total() - (float) $order->get_total_refunded()));
        if ($amount < 1) {
            return new \WP_Error('vietqr_bacs_no_amount', __('Đơn hàng không còn số tiền cần chuyển.', 'vietqr-bacs-for-woocommerce'));
        }
        $description = self::description(
            (string) ($settings['description_template'] ?? 'ORDER-{order_number}'),
            (string) $order->get_order_number(),
            (int) $order->get_id()
        );

        $url = 'https://' . self::HOST . '/image/' . rawurlencode($bank) . '-' . rawurlencode($account) . '-' . rawurlencode($template) . '.png?'
            . http_build_query(['amount' => $amount, 'addInfo' => $description, 'accountName' => $holder], '', '&', PHP_QUERY_RFC3986);
        $parts = wp_parse_url($url);
        if (! is_array($parts) || 'https' !== ($parts['scheme'] ?? '') || self::HOST !== ($parts['host'] ?? '') || ! str_starts_with((string) ($parts['path'] ?? ''), '/image/')) {
            return new \WP_Error('vietqr_bacs_url', __('Không thể tạo URL VietQR an toàn.', 'vietqr-bacs-for-woocommerce'));
        }

        return ['url' => $url, 'amount' => $amount, 'description' => $description, 'bank' => $bank, 'account' => $account, 'holder' => $holder];
    }

    public static function description(string $template, string $order_number, int $order_id): string
    {
        $value = strtr($template, ['{order_number}' => $order_number, '{order_id}' => (string) $order_id]);
        $value = strtoupper(remove_accents($value));
        $value = preg_replace('/[^A-Z0-9._-]+/', '-', $value) ?: '';

        return mb_substr(trim($value, '-'), 0, 50);
    }
}
