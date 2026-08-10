<?php

namespace Lyli\GHN;

final class Print_Controller
{
    private const ACTION = 'yoohw_vietnam_store_tools_print_shipment';
    private const PROVIDER = 'lyli_ghn';
    private const NONCE_FIELD = 'yoohw_vietnam_store_tools_shipping_nonce';

    public static function init(): void
    {
        add_action('admin_post_' . self::ACTION, [self::class, 'handle'], 1);
        add_action('admin_footer', [self::class, 'render_new_tab_guard'], 1);
    }

    public static function authorize(int $order_id, string $nonce)
    {
        if (! current_user_can('manage_woocommerce')) {
            return new \WP_Error('lyli_ghn_forbidden', __('Bạn không có quyền in nhãn GHN.', 'lyli-ghn-connector'));
        }

        if ($order_id < 1 || '' === $nonce || ! wp_verify_nonce($nonce, 'yoohw_vietnam_store_tools_shipping_action_' . $order_id)) {
            return new \WP_Error('lyli_ghn_bad_print_nonce', __('Phiên in nhãn GHN đã hết hạn. Vui lòng thử lại.', 'lyli-ghn-connector'));
        }

        return true;
    }

    public static function handle(): void
    {
        $provider_id = isset($_POST['provider_id']) && is_scalar($_POST['provider_id'])
            ? sanitize_key(wp_unslash($_POST['provider_id']))
            : '';
        if (self::PROVIDER !== $provider_id) {
            return;
        }

        $order_id = isset($_POST['order_id']) ? absint($_POST['order_id']) : 0;
        $nonce = isset($_POST[self::NONCE_FIELD]) && is_scalar($_POST[self::NONCE_FIELD])
            ? sanitize_text_field(wp_unslash($_POST[self::NONCE_FIELD]))
            : '';
        $authorized = self::authorize($order_id, $nonce);
        if (is_wp_error($authorized)) {
            wp_die(esc_html($authorized->get_error_message()), '', ['response' => 403]);
        }

        $order = wc_get_order($order_id);
        $provider = Plugin::provider();
        if (! $order || null === $provider) {
            wp_die(esc_html__('Không thể chuẩn bị nhãn GHN cho đơn hàng này.', 'lyli-ghn-connector'), '', ['response' => 400]);
        }

        $result = $provider->print_shipment($order, ['request' => $_POST]);
        if (is_wp_error($result) || ! is_array($result) || empty($result['url'])) {
            $message = is_wp_error($result) ? $result->get_error_message() : __('GHN không trả về URL in hợp lệ.', 'lyli-ghn-connector');
            wp_die(esc_html($message), '', ['response' => 400]);
        }

        $url = (string) $result['url'];
        $host = (string) wp_parse_url($url, PHP_URL_HOST);
        $allow_host = static function (array $hosts) use ($host): array {
            $hosts[] = $host;
            return array_values(array_unique($hosts));
        };
        add_filter('allowed_redirect_hosts', $allow_host);
        nocache_headers();
        header('Referrer-Policy: no-referrer');
        $redirected = wp_safe_redirect($url, 302, 'Lyli GHN Connector');
        remove_filter('allowed_redirect_hosts', $allow_host);
        if (! $redirected) {
            wp_die(esc_html__('URL in GHN đã bị chặn vì không an toàn.', 'lyli-ghn-connector'), '', ['response' => 400]);
        }
        exit;
    }

    public static function render_new_tab_guard(): void
    {
        if (! current_user_can('manage_woocommerce')) {
            return;
        }
        ?>
        <script>
        document.addEventListener('click', function (event) {
            var button = event.target.closest('[data-vck-shipping-action="<?php echo esc_js(self::ACTION); ?>"][data-vck-shipping-provider-id="<?php echo esc_js(self::PROVIDER); ?>"]');
            if (!button) return;
            event.preventDefault();
            event.stopImmediatePropagation();
            var form = document.createElement('form');
            form.method = 'post';
            form.action = <?php echo wp_json_encode(admin_url('admin-post.php')); ?>;
            form.target = '_blank';
            form.setAttribute('rel', 'noopener noreferrer');
            var fields = {
                action: <?php echo wp_json_encode(self::ACTION); ?>,
                order_id: button.getAttribute('data-vck-shipping-order-id') || '',
                provider_id: <?php echo wp_json_encode(self::PROVIDER); ?>,
                <?php echo wp_json_encode(self::NONCE_FIELD); ?>: button.getAttribute('data-vck-shipping-nonce') || ''
            };
            Object.keys(fields).forEach(function (name) {
                var input = document.createElement('input');
                input.type = 'hidden';
                input.name = name;
                input.value = fields[name];
                form.appendChild(input);
            });
            document.body.appendChild(form);
            form.submit();
            form.remove();
        }, true);
        </script>
        <?php
    }
}
