<?php

namespace Lyli\GHN;

final class Settings
{
    private const PAGE_SLUG = 'lyli-ghn';
    private const SAVE_ACTION = 'lyli_ghn_save_settings';
    private const NONCE_ACTION = 'lyli_ghn_save_settings';

    /** @return array<string,mixed> */
    public static function defaults(): array
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

    public static function init(): void
    {
        add_action('admin_menu', [self::class, 'register_menu'], 60);
        add_action('admin_post_' . self::SAVE_ACTION, [self::class, 'handle_save']);
    }

    /** @return array<string,mixed> */
    public static function get(): array
    {
        $saved = get_option(SETTINGS_OPTION, []);
        return array_merge(self::defaults(), is_array($saved) ? $saved : []);
    }

    public static function token(): string
    {
        $token = get_option(TOKEN_OPTION, '');
        return is_string($token) ? trim($token) : '';
    }

    /** @param array<string,mixed>|null $settings */
    public static function is_ready(?array $settings = null): bool
    {
        $settings = $settings ?? self::get();

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
            && '' !== self::token();
    }

    public static function authorize_save()
    {
        if (! current_user_can('manage_woocommerce')) {
            return new \WP_Error('lyli_ghn_forbidden', __('Bạn không có quyền cấu hình GHN.', 'lyli-ghn-connector'));
        }

        $nonce = isset($_POST['_lyli_ghn_nonce']) && is_scalar($_POST['_lyli_ghn_nonce'])
            ? sanitize_text_field(wp_unslash($_POST['_lyli_ghn_nonce']))
            : '';
        if ('' === $nonce || ! wp_verify_nonce($nonce, self::NONCE_ACTION)) {
            return new \WP_Error('lyli_ghn_bad_nonce', __('Phiên cấu hình đã hết hạn. Vui lòng thử lại.', 'lyli-ghn-connector'));
        }

        return true;
    }

    public static function register_menu(): void
    {
        add_submenu_page(
            'woocommerce',
            __('Kết nối GHN', 'lyli-ghn-connector'),
            __('Kết nối GHN', 'lyli-ghn-connector'),
            'manage_woocommerce',
            self::PAGE_SLUG,
            [self::class, 'render_page']
        );
    }

    public static function handle_save(): void
    {
        $authorized = self::authorize_save();
        if (is_wp_error($authorized)) {
            wp_die(esc_html($authorized->get_error_message()), '', ['response' => 403]);
        }

        $input = isset($_POST['lyli_ghn']) && is_array($_POST['lyli_ghn'])
            ? wp_unslash($_POST['lyli_ghn'])
            : [];
        $settings = self::sanitize($input);

        if (! empty($input['clear_token'])) {
            delete_option(TOKEN_OPTION);
        } elseif (isset($input['token']) && is_scalar($input['token']) && '' !== trim((string) $input['token'])) {
            self::write_private_option(TOKEN_OPTION, sanitize_text_field((string) $input['token']));
        }

        $requested_enabled = ! empty($settings['enabled']);
        if ($requested_enabled && ! self::is_ready($settings)) {
            $settings['enabled'] = false;
            $result = 'incomplete';
        } else {
            $result = 'saved';
        }

        self::write_private_option(SETTINGS_OPTION, $settings);

        wp_safe_redirect(add_query_arg('lyli_ghn_result', $result, admin_url('admin.php?page=' . self::PAGE_SLUG)));
        exit;
    }

    /** @param array<string,mixed> $input @return array<string,mixed> */
    public static function sanitize(array $input): array
    {
        $environment = self::input_key($input, 'environment', 'test');
        $service_type_id = self::input_int($input, 'service_type_id');
        $payment_type_id = self::input_int($input, 'payment_type_id');
        $required_note = self::input_key($input, 'required_note');
        $cod_policy = self::input_key($input, 'cod_policy', 'disabled');
        $insurance_policy = self::input_key($input, 'insurance_policy', 'disabled');
        $print_format = self::input_key($input, 'print_format', 'a5');

        return [
            'enabled' => ! empty($input['enabled']),
            'environment' => in_array($environment, ['test', 'production'], true) ? $environment : 'test',
            'shop_id' => self::input_int($input, 'shop_id'),
            'service_type_id' => in_array($service_type_id, [2, 5], true) ? $service_type_id : 0,
            'payment_type_id' => in_array($payment_type_id, [1, 2], true) ? $payment_type_id : 0,
            'required_note' => in_array($required_note, ['khongchoxemhang', 'choxemhangkhongthu', 'chothuhang'], true)
                ? strtoupper($required_note)
                : '',
            'print_format' => in_array($print_format, ['a5', '80x80', '52x70'], true) ? $print_format : 'a5',
            'cod_policy' => in_array($cod_policy, ['disabled', 'cod_gateway_only'], true) ? $cod_policy : 'disabled',
            'insurance_policy' => in_array($insurance_policy, ['disabled', 'remaining_total'], true) ? $insurance_policy : 'disabled',
            'package_weight_g' => self::input_int($input, 'package_weight_g'),
            'package_length_cm' => self::input_int($input, 'package_length_cm'),
            'package_width_cm' => self::input_int($input, 'package_width_cm'),
            'package_height_cm' => self::input_int($input, 'package_height_cm'),
        ];
    }

    /** @param array<string,mixed> $input */
    private static function input_key(array $input, string $key, string $default = ''): string
    {
        return isset($input[$key]) && is_scalar($input[$key]) ? sanitize_key((string) $input[$key]) : $default;
    }

    /** @param array<string,mixed> $input */
    private static function input_int(array $input, string $key): int
    {
        return isset($input[$key]) && is_scalar($input[$key]) ? absint($input[$key]) : 0;
    }

    /** @param mixed $value */
    private static function write_private_option(string $name, $value): void
    {
        if (false === get_option($name, false)) {
            add_option($name, $value, '', false);
            return;
        }

        update_option($name, $value, false);
    }

    public static function render_page(): void
    {
        if (! current_user_can('manage_woocommerce')) {
            wp_die(esc_html__('Bạn không có quyền truy cập trang này.', 'lyli-ghn-connector'));
        }

        $settings = self::get();
        $result = isset($_GET['lyli_ghn_result']) && is_scalar($_GET['lyli_ghn_result'])
            ? sanitize_key(wp_unslash($_GET['lyli_ghn_result']))
            : '';
        ?>
        <div class="wrap">
            <h1><?php esc_html_e('Kết nối GHN', 'lyli-ghn-connector'); ?></h1>
            <?php if ('saved' === $result) : ?>
                <div class="notice notice-success"><p><?php esc_html_e('Đã lưu cấu hình GHN.', 'lyli-ghn-connector'); ?></p></div>
            <?php elseif ('incomplete' === $result) : ?>
                <div class="notice notice-warning"><p><?php esc_html_e('Đã lưu nhưng connector vẫn tắt vì còn thiếu Token, ShopId, chính sách hoặc kích thước kiện hàng.', 'lyli-ghn-connector'); ?></p></div>
            <?php endif; ?>
            <p><?php esc_html_e('Connector chỉ tạo vận đơn khi chủ shop bấm rõ ràng trong đơn WooCommerce. Không có cước GHN live và không tự tạo vận đơn.', 'lyli-ghn-connector'); ?></p>
            <form method="post" action="<?php echo esc_url(admin_url('admin-post.php')); ?>">
                <input type="hidden" name="action" value="<?php echo esc_attr(self::SAVE_ACTION); ?>">
                <?php wp_nonce_field(self::NONCE_ACTION, '_lyli_ghn_nonce'); ?>
                <table class="form-table" role="presentation">
                    <tr><th scope="row"><?php esc_html_e('Bật connector', 'lyli-ghn-connector'); ?></th><td><label><input type="checkbox" name="lyli_ghn[enabled]" value="1" <?php checked(! empty($settings['enabled'])); ?>> <?php esc_html_e('Cho phép chọn GHN trong khung vận chuyển của đơn hàng', 'lyli-ghn-connector'); ?></label></td></tr>
                    <tr><th scope="row"><label for="lyli-ghn-environment"><?php esc_html_e('Môi trường', 'lyli-ghn-connector'); ?></label></th><td><select id="lyli-ghn-environment" name="lyli_ghn[environment]"><option value="test" <?php selected($settings['environment'], 'test'); ?>>Test</option><option value="production" <?php selected($settings['environment'], 'production'); ?>>Production</option></select></td></tr>
                    <tr><th scope="row"><label for="lyli-ghn-token">Token</label></th><td><input id="lyli-ghn-token" type="password" autocomplete="new-password" name="lyli_ghn[token]" value="" class="regular-text" placeholder="<?php echo esc_attr(self::token() ? __('Đã lưu — để trống để giữ nguyên', 'lyli-ghn-connector') : __('Chưa cấu hình', 'lyli-ghn-connector')); ?>"><br><label><input type="checkbox" name="lyli_ghn[clear_token]" value="1"> <?php esc_html_e('Xóa Token đã lưu', 'lyli-ghn-connector'); ?></label></td></tr>
                    <tr><th scope="row"><label for="lyli-ghn-shop-id">ShopId</label></th><td><input id="lyli-ghn-shop-id" type="number" min="1" name="lyli_ghn[shop_id]" value="<?php echo esc_attr((string) $settings['shop_id']); ?>"></td></tr>
                    <tr><th scope="row"><label for="lyli-ghn-service"><?php esc_html_e('Loại dịch vụ', 'lyli-ghn-connector'); ?></label></th><td><select id="lyli-ghn-service" name="lyli_ghn[service_type_id]"><option value="0"><?php esc_html_e('Chọn…', 'lyli-ghn-connector'); ?></option><option value="2" <?php selected((int) $settings['service_type_id'], 2); ?>>2 — Hàng nhẹ</option><option value="5" <?php selected((int) $settings['service_type_id'], 5); ?>>5 — Hàng nặng</option></select></td></tr>
                    <tr><th scope="row"><label for="lyli-ghn-payer"><?php esc_html_e('Người trả phí GHN', 'lyli-ghn-connector'); ?></label></th><td><select id="lyli-ghn-payer" name="lyli_ghn[payment_type_id]"><option value="0"><?php esc_html_e('Chọn…', 'lyli-ghn-connector'); ?></option><option value="1" <?php selected((int) $settings['payment_type_id'], 1); ?>>1 — Shop</option><option value="2" <?php selected((int) $settings['payment_type_id'], 2); ?>>2 — Người nhận</option></select></td></tr>
                    <tr><th scope="row"><label for="lyli-ghn-note"><?php esc_html_e('Chính sách xem hàng', 'lyli-ghn-connector'); ?></label></th><td><select id="lyli-ghn-note" name="lyli_ghn[required_note]"><option value=""><?php esc_html_e('Chọn…', 'lyli-ghn-connector'); ?></option><option value="KHONGCHOXEMHANG" <?php selected($settings['required_note'], 'KHONGCHOXEMHANG'); ?>>Không cho xem hàng</option><option value="CHOXEMHANGKHONGTHU" <?php selected($settings['required_note'], 'CHOXEMHANGKHONGTHU'); ?>>Cho xem, không thử</option><option value="CHOTHUHANG" <?php selected($settings['required_note'], 'CHOTHUHANG'); ?>>Cho thử hàng</option></select></td></tr>
                    <tr><th scope="row"><label for="lyli-ghn-cod"><?php esc_html_e('Thu hộ COD', 'lyli-ghn-connector'); ?></label></th><td><select id="lyli-ghn-cod" name="lyli_ghn[cod_policy]"><option value="disabled" <?php selected($settings['cod_policy'], 'disabled'); ?>>Tắt</option><option value="cod_gateway_only" <?php selected($settings['cod_policy'], 'cod_gateway_only'); ?>>Chỉ đơn dùng phương thức COD và chưa thanh toán</option></select></td></tr>
                    <tr><th scope="row"><label for="lyli-ghn-insurance"><?php esc_html_e('Khai giá', 'lyli-ghn-connector'); ?></label></th><td><select id="lyli-ghn-insurance" name="lyli_ghn[insurance_policy]"><option value="disabled" <?php selected($settings['insurance_policy'], 'disabled'); ?>>Tắt</option><option value="remaining_total" <?php selected($settings['insurance_policy'], 'remaining_total'); ?>>Giá trị đơn còn lại, tối đa 5.000.000đ</option></select></td></tr>
                    <tr><th scope="row"><?php esc_html_e('Kiện hàng mặc định', 'lyli-ghn-connector'); ?></th><td>
                        <label><?php esc_html_e('Khối lượng (g)', 'lyli-ghn-connector'); ?> <input type="number" min="1" max="50000" name="lyli_ghn[package_weight_g]" value="<?php echo esc_attr((string) $settings['package_weight_g']); ?>"></label><br>
                        <label><?php esc_html_e('Dài (cm)', 'lyli-ghn-connector'); ?> <input type="number" min="1" max="200" name="lyli_ghn[package_length_cm]" value="<?php echo esc_attr((string) $settings['package_length_cm']); ?>"></label>
                        <label><?php esc_html_e('Rộng (cm)', 'lyli-ghn-connector'); ?> <input type="number" min="1" max="200" name="lyli_ghn[package_width_cm]" value="<?php echo esc_attr((string) $settings['package_width_cm']); ?>"></label>
                        <label><?php esc_html_e('Cao (cm)', 'lyli-ghn-connector'); ?> <input type="number" min="1" max="200" name="lyli_ghn[package_height_cm]" value="<?php echo esc_attr((string) $settings['package_height_cm']); ?>"></label>
                    </td></tr>
                    <tr><th scope="row"><label for="lyli-ghn-print-format"><?php esc_html_e('Khổ nhãn in', 'lyli-ghn-connector'); ?></label></th><td><select id="lyli-ghn-print-format" name="lyli_ghn[print_format]"><option value="a5" <?php selected($settings['print_format'], 'a5'); ?>>A5</option><option value="80x80" <?php selected($settings['print_format'], '80x80'); ?>>80 × 80 mm</option><option value="52x70" <?php selected($settings['print_format'], '52x70'); ?>>52 × 70 mm</option></select></td></tr>
                </table>
                <?php submit_button(__('Lưu cấu hình GHN', 'lyli-ghn-connector')); ?>
            </form>
        </div>
        <?php
    }
}
