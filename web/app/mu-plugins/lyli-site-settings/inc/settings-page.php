<?php
/**
 * Lyli Site Settings — settings page.
 *
 * WP Admin → Lyli Shop → Cài đặt giao diện.
 * Uses the WordPress Settings API with capability checks, nonces,
 * strict sanitize callbacks and escaped output.
 */

namespace LyliSiteSettings\SettingsPage;

if (! defined('ABSPATH')) {
    exit;
}

/**
 * Register the admin menu page ("Lyli Shop" → "Cài đặt giao diện").
 */
function register_menu(): void
{
    if (! current_user_can('manage_options')) {
        return;
    }

    add_menu_page(
        __('Lyli Shop', 'lyli-site-settings'),
        __('Lyli Shop', 'lyli-site-settings'),
        'manage_options',
        \LyliSiteSettings\MENU_SLUG,
        __NAMESPACE__ . '\\render_page',
        'dashicons-superhero',
        3
    );

    add_submenu_page(
        \LyliSiteSettings\MENU_SLUG,
        __('Cài đặt giao diện', 'lyli-site-settings'),
        __('Cài đặt giao diện', 'lyli-site-settings'),
        'manage_options',
        \LyliSiteSettings\SETTINGS_PAGE,
        __NAMESPACE__ . '\\render_page'
    );
}

/**
 * Register each setting with the Settings API.
 */
function register_settings(): void
{
    register_setting(\LyliSiteSettings\SETTINGS_GROUP, 'lyli_footer_intro', [
        'type'              => 'string',
        'sanitize_callback' => __NAMESPACE__ . '\\sanitize_textarea',
        'default'           => '',
    ]);
    register_setting(\LyliSiteSettings\SETTINGS_GROUP, 'lyli_contact_email', [
        'type'              => 'string',
        'sanitize_callback' => 'sanitize_email',
        'default'           => '',
    ]);
    register_setting(\LyliSiteSettings\SETTINGS_GROUP, 'lyli_contact_phone', [
        'type'              => 'string',
        'sanitize_callback' => __NAMESPACE__ . '\\sanitize_phone',
        'default'           => '',
    ]);
    register_setting(\LyliSiteSettings\SETTINGS_GROUP, 'lyli_facebook_url', [
        'type'              => 'string',
        'sanitize_callback' => 'esc_url_raw',
        'default'           => '',
    ]);
    register_setting(\LyliSiteSettings\SETTINGS_GROUP, 'lyli_instagram_url', [
        'type'              => 'string',
        'sanitize_callback' => 'esc_url_raw',
        'default'           => '',
    ]);
    register_setting(\LyliSiteSettings\SETTINGS_GROUP, 'lyli_tiktok_url', [
        'type'              => 'string',
        'sanitize_callback' => 'esc_url_raw',
        'default'           => '',
    ]);
    register_setting(\LyliSiteSettings\SETTINGS_GROUP, 'lyli_zalo_url', [
        'type'              => 'string',
        'sanitize_callback' => 'esc_url_raw',
        'default'           => '',
    ]);
    register_setting(\LyliSiteSettings\SETTINGS_GROUP, 'lyli_announcement', [
        'type'              => 'string',
        'sanitize_callback' => 'sanitize_text_field',
        'default'           => '',
    ]);
    register_setting(\LyliSiteSettings\SETTINGS_GROUP, 'lyli_announcement_enabled', [
        'type'              => 'boolean',
        'sanitize_callback' => __NAMESPACE__ . '\\sanitize_checkbox',
        'default'           => false,
    ]);
    register_setting(\LyliSiteSettings\SETTINGS_GROUP, 'lyli_custom_order_label', [
        'type'              => 'string',
        'sanitize_callback' => 'sanitize_text_field',
        'default'           => '',
    ]);
    register_setting(\LyliSiteSettings\SETTINGS_GROUP, 'lyli_custom_order_url', [
        'type'              => 'string',
        'sanitize_callback' => 'esc_url_raw',
        'default'           => '',
    ]);
    register_setting(\LyliSiteSettings\SETTINGS_GROUP, 'lyli_footer_copyright', [
        'type'              => 'string',
        'sanitize_callback' => 'sanitize_text_field',
        'default'           => '',
    ]);
}

/**
 * Render the settings page.
 */
function render_page(): void
{
    if (! current_user_can('manage_options')) {
        wp_die(esc_html__('Bạn không có quyền truy cập trang này.', 'lyli-site-settings'));
    }
    ?>
    <div class="wrap">
        <h1><?php echo esc_html__('Lyli Shop — Cài đặt giao diện', 'lyli-site-settings'); ?></h1>
        <p><?php echo esc_html__('Các giá trị hiển thị toàn trang (footer, liên hệ, mạng xã hội, thông báo, CTA đặt mẫu). Nội dung trang/bài thuộc Gutenberg trong WordPress.', 'lyli-site-settings'); ?></p>
        <form action="options.php" method="post">
            <?php settings_fields(\LyliSiteSettings\SETTINGS_GROUP); ?>
            <table class="form-table" role="presentation">
                <tr>
                    <th scope="row"><label for="lyli_footer_intro"><?php echo esc_html__('Giới thiệu ngắn (footer)', 'lyli-site-settings'); ?></label></th>
                    <td>
                        <textarea name="lyli_footer_intro" id="lyli_footer_intro" class="large-text" rows="3"><?php echo esc_textarea(get_option('lyli_footer_intro', '')); ?></textarea>
                        <p class="description"><?php echo esc_html__('1–2 câu giới thiệu; để trống nếu chưa muốn hiển thị.', 'lyli-site-settings'); ?></p>
                    </td>
                </tr>
                <tr>
                    <th scope="row"><label for="lyli_contact_email"><?php echo esc_html__('Email liên hệ', 'lyli-site-settings'); ?></label></th>
                    <td>
                        <input type="email" name="lyli_contact_email" id="lyli_contact_email" class="regular-text" value="<?php echo esc_attr(get_option('lyli_contact_email', '')); ?>" />
                    </td>
                </tr>
                <tr>
                    <th scope="row"><label for="lyli_contact_phone"><?php echo esc_html__('Số điện thoại liên hệ', 'lyli-site-settings'); ?></label></th>
                    <td>
                        <input type="tel" name="lyli_contact_phone" id="lyli_contact_phone" class="regular-text" value="<?php echo esc_attr(get_option('lyli_contact_phone', '')); ?>" />
                        <p class="description"><?php echo esc_html__('Chỉ chấp nhận số điện thoại hợp lệ; để trống nếu chưa rõ.', 'lyli-site-settings'); ?></p>
                    </td>
                </tr>
                <tr>
                    <th scope="row"><label for="lyli_facebook_url">Facebook URL</label></th>
                    <td><input type="url" name="lyli_facebook_url" id="lyli_facebook_url" class="regular-text" value="<?php echo esc_attr(get_option('lyli_facebook_url', '')); ?>" /></td>
                </tr>
                <tr>
                    <th scope="row"><label for="lyli_instagram_url">Instagram URL</label></th>
                    <td><input type="url" name="lyli_instagram_url" id="lyli_instagram_url" class="regular-text" value="<?php echo esc_attr(get_option('lyli_instagram_url', '')); ?>" /></td>
                </tr>
                <tr>
                    <th scope="row"><label for="lyli_tiktok_url">TikTok URL</label></th>
                    <td><input type="url" name="lyli_tiktok_url" id="lyli_tiktok_url" class="regular-text" value="<?php echo esc_attr(get_option('lyli_tiktok_url', '')); ?>" /></td>
                </tr>
                <tr>
                    <th scope="row"><label for="lyli_zalo_url">Zalo URL</label></th>
                    <td><input type="url" name="lyli_zalo_url" id="lyli_zalo_url" class="regular-text" value="<?php echo esc_attr(get_option('lyli_zalo_url', '')); ?>" /></td>
                </tr>
                <tr>
                    <th scope="row"><label for="lyli_announcement"><?php echo esc_html__('Dòng thông báo (announcement bar)', 'lyli-site-settings'); ?></label></th>
                    <td>
                        <input type="text" name="lyli_announcement" id="lyli_announcement" class="large-text" value="<?php echo esc_attr(get_option('lyli_announcement', '')); ?>" />
                        <p class="description"><?php echo esc_html__('Ví dụ: "Ly li shop — sản phẩm móc len thủ công". Để trống là ẩn.', 'lyli-site-settings'); ?></p>
                    </td>
                </tr>
                <tr>
                    <th scope="row"><?php echo esc_html__('Hiển thị dòng thông báo', 'lyli-site-settings'); ?></th>
                    <td>
                        <label>
                            <input type="checkbox" name="lyli_announcement_enabled" id="lyli_announcement_enabled" value="1" <?php checked(get_option('lyli_announcement_enabled', false)); ?> />
                            <?php echo esc_html__('Bật hiển thị announcement bar trên toàn trang', 'lyli-site-settings'); ?>
                        </label>
                    </td>
                </tr>
                <tr>
                    <th scope="row"><label for="lyli_custom_order_label"><?php echo esc_html__('Nhãn CTA đặt mẫu mặc định', 'lyli-site-settings'); ?></label></th>
                    <td>
                        <input type="text" name="lyli_custom_order_label" id="lyli_custom_order_label" class="regular-text" value="<?php echo esc_attr(get_option('lyli_custom_order_label', '')); ?>" />
                        <p class="description"><?php echo esc_html__('Ví dụ: "Đặt mẫu theo yêu cầu". Mặc định ẩn nếu rỗng.', 'lyli-site-settings'); ?></p>
                    </td>
                </tr>
                <tr>
                    <th scope="row"><label for="lyli_custom_order_url"><?php echo esc_html__('URL CTA đặt mẫu mặc định', 'lyli-site-settings'); ?></label></th>
                    <td>
                        <input type="url" name="lyli_custom_order_url" id="lyli_custom_order_url" class="regular-text" value="<?php echo esc_attr(get_option('lyli_custom_order_url', '')); ?>" />
                        <p class="description"><?php echo esc_html__('Nên trỏ tới trang "Đặt mẫu theo yêu cầu"; để trống nếu chưa rõ.', 'lyli-site-settings'); ?></p>
                    </td>
                </tr>
                <tr>
                    <th scope="row"><label for="lyli_footer_copyright"><?php echo esc_html__('Dòng copyright footer', 'lyli-site-settings'); ?></label></th>
                    <td>
                        <input type="text" name="lyli_footer_copyright" id="lyli_footer_copyright" class="large-text" value="<?php echo esc_attr(get_option('lyli_footer_copyright', '')); ?>" />
                        <p class="description"><?php echo esc_html__('Ví dụ: "© 2026 Ly li shop". Để trống để dùng mặc định.', 'lyli-site-settings'); ?></p>
                    </td>
                </tr>
            </table>
            <?php submit_button(); ?>
        </form>
    </div>
    <?php
}

/**
 * Sanitize callback: textarea (allow line breaks, strip tags, trim).
 */
function sanitize_textarea($value)
{
    if (! is_string($value)) {
        return '';
    }
    return sanitize_textarea_field(wp_unslash($value));
}

/**
 * Sanitize callback: phone — digits, +, -, spaces, parentheses, max 20 chars.
 */
function sanitize_phone($value)
{
    if (! is_string($value)) {
        return '';
    }
    $phone = preg_replace('/[^0-9+()\-\s]/', '', wp_unslash($value));
    return mb_substr(trim($phone), 0, 20);
}

/**
 * Sanitize callback: checkbox boolean.
 */
function sanitize_checkbox($value)
{
    return $value ? 1 : 0;
}