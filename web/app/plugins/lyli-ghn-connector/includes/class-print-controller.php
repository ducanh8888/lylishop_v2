<?php

namespace Lyli\GHN;

final class Print_Controller
{
    public static function authorize(int $order_id, string $nonce, string $nonce_action = '')
    {
        if (! current_user_can('manage_woocommerce')) {
            return new \WP_Error('lyli_ghn_forbidden', __('Bạn không có quyền in nhãn GHN.', 'lyli-ghn-connector'));
        }
        $nonce_action = '' !== $nonce_action ? $nonce_action : 'lyli_ghn_shipment_action_' . $order_id;
        if ($order_id < 1 || '' === $nonce || ! wp_verify_nonce($nonce, $nonce_action)) {
            return new \WP_Error('lyli_ghn_bad_print_nonce', __('Phiên in nhãn GHN đã hết hạn. Vui lòng thử lại.', 'lyli-ghn-connector'));
        }

        return true;
    }

    public static function handle_authorized_request(int $order_id, string $nonce, string $nonce_action, Provider $provider): void
    {
        $authorized = self::authorize($order_id, $nonce, $nonce_action);
        if (is_wp_error($authorized)) {
            wp_die(esc_html($authorized->get_error_message()), '', ['response' => 403]);
        }
        $order = wc_get_order($order_id);
        if (! $order) {
            wp_die(esc_html__('Không thể chuẩn bị nhãn GHN cho đơn hàng này.', 'lyli-ghn-connector'), '', ['response' => 400]);
        }
        $result = $provider->print_shipment($order);
        if (is_wp_error($result) || ! is_array($result) || empty($result['url'])) {
            $message = is_wp_error($result) ? $result->get_error_message() : __('GHN không trả về URL in hợp lệ.', 'lyli-ghn-connector');
            wp_die(esc_html($message), '', ['response' => 400]);
        }
        self::redirect((string) $result['url']);
    }

    public static function redirect(string $url): void
    {
        $host = (string) wp_parse_url($url, PHP_URL_HOST);
        if (! in_array($host, ['dev-online-gateway.ghn.vn', 'online-gateway.ghn.vn'], true)) {
            wp_die(esc_html__('URL in GHN đã bị chặn vì không an toàn.', 'lyli-ghn-connector'), '', ['response' => 400]);
        }
        $allow_host = static function (array $hosts) use ($host): array {
            $hosts[] = $host;
            return array_values(array_unique($hosts));
        };
        add_filter('allowed_redirect_hosts', $allow_host);
        nocache_headers();
        header('Referrer-Policy: no-referrer');
        $redirected = wp_safe_redirect($url, 302, 'GHN Connector');
        remove_filter('allowed_redirect_hosts', $allow_host);
        if (! $redirected) {
            wp_die(esc_html__('URL in GHN đã bị chặn vì không an toàn.', 'lyli-ghn-connector'), '', ['response' => 400]);
        }
        exit;
    }
}
