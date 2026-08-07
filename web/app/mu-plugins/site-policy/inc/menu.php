<?php
/**
 * Admin menu whitelist per PLAN.md sections 7.3/7.4.
 * developer_admin (Administrator) sees everything unchanged.
 */

namespace SitePolicy\Menu;

function filter_admin_menu(): void
{
    $user = wp_get_current_user();

    if (in_array('administrator', (array) $user->roles, true)) {
        return;
    }

    if (in_array(\SitePolicy\ROLE_OWNER, (array) $user->roles, true)) {
        add_owner_appearance_menu();
        hide_menu_pages(owner_hidden_menus());
        return;
    }

    if (in_array(\SitePolicy\ROLE_STAFF, (array) $user->roles, true)) {
        hide_menu_pages(staff_hidden_menus());
    }
}

/** @return string[] */
function owner_hidden_menus(): array
{
    return [
        'tools.php',
        'options-general.php',
        'plugins.php',
        'users.php',
        'site-health.php',
    ];
}

function add_owner_appearance_menu(): void
{
    add_menu_page(
        __('Giao diện cửa hàng', 'site-policy'),
        __('Giao diện', 'site-policy'),
        'edit_theme_options',
        'lyli-appearance',
        __NAMESPACE__ . '\\render_appearance_page',
        'dashicons-admin-customizer',
        58
    );

    add_submenu_page(
        'lyli-appearance',
        __('Logo & giao diện', 'site-policy'),
        __('Logo & giao diện', 'site-policy'),
        'edit_theme_options',
        'customize.php'
    );
    add_submenu_page(
        'lyli-appearance',
        __('Menu điều hướng', 'site-policy'),
        __('Menu điều hướng', 'site-policy'),
        'edit_theme_options',
        'nav-menus.php'
    );
}

function render_appearance_page(): void
{
    if (! current_user_can('edit_theme_options')) {
        wp_die(esc_html__('Bạn không có quyền truy cập trang này.', 'site-policy'));
    }

    $customize_url = add_query_arg('return', rawurlencode(admin_url('admin.php?page=lyli-appearance')), admin_url('customize.php'));
    ?>
    <div class="wrap">
        <h1><?php esc_html_e('Giao diện cửa hàng', 'site-policy'); ?></h1>
        <p><?php esc_html_e('Chọn đúng công cụ để sửa logo, màu sắc, bố cục đầu trang hoặc menu.', 'site-policy'); ?></p>
        <p>
            <a class="button button-primary" href="<?php echo esc_url($customize_url); ?>"><?php esc_html_e('Mở Logo & giao diện', 'site-policy'); ?></a>
            <a class="button" href="<?php echo esc_url(admin_url('nav-menus.php')); ?>"><?php esc_html_e('Sửa menu điều hướng', 'site-policy'); ?></a>
        </p>
        <p class="description"><?php esc_html_e('Cài đặt, đổi hoặc sửa mã giao diện bị khóa và cần nhà phát triển.', 'site-policy'); ?></p>
    </div>
    <?php
}

/** @return string[] */
function staff_hidden_menus(): array
{
    return array_merge(owner_hidden_menus(), [
        'edit.php', // Posts — staff only touches orders/products, not blog content
        'upload.php',
        'woocommerce_page_wc-reports',
        'woocommerce_page_wc-settings',
    ]);
}

/** @param string[] $slugs */
function hide_menu_pages(array $slugs): void
{
    foreach ($slugs as $slug) {
        remove_menu_page($slug);
    }

    // "Users" for shop_owner is reduced to "profile only", never full user admin.
    remove_submenu_page('users.php', 'user-new.php');
}
