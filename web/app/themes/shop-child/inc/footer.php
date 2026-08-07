<?php
/**
 * Lyli Shop — footer integration.
 * Renders footer intro/contact/social from Lyli Site Settings inside the
 * Botiga footer via verified Botiga 2.4.7 hooks:
 *   - botiga_before_footer_copyright   (footer content area)
 *   - botiga_footer_copyright_content_start/end (copyright bar)
 * Missing values are hidden cleanly (no invented contact data).
 */

namespace ShopChild\Footer;

if (! defined('ABSPATH')) {
    exit;
}

/**
 * Render the Lyli footer info block (intro + contact + socials) just above
 * the Botiga copyright bar.
 */
add_action('after_setup_theme', __NAMESPACE__ . '\\register_footer', 30);
function register_footer(): void
{
    add_action('botiga_footer', __NAMESPACE__ . '\\render_footer_info', 5);
}

function render_footer_info(): void
{
    $has_settings = function_exists('LyliSiteSettings\\get_footer_intro');
    $intro = $has_settings ? \LyliSiteSettings\get_footer_intro() : '';
    $email = $has_settings ? \LyliSiteSettings\get_contact_email() : '';
    $phone = $has_settings ? \LyliSiteSettings\get_contact_phone() : '';
    $facebook = $has_settings ? \LyliSiteSettings\get_facebook_url() : '';
    $instagram = $has_settings ? \LyliSiteSettings\get_instagram_url() : '';
    $tiktok = $has_settings ? \LyliSiteSettings\get_tiktok_url() : '';
    $zalo = $has_settings ? \LyliSiteSettings\get_zalo_url() : '';

    echo '<footer class="lyli-site-footer"><div class="lyli-footer-inner">';
    echo '<div class="lyli-footer-brand">';
    printf('<a class="lyli-footer-logo" href="%1$s">%2$s</a>', esc_url(home_url('/')), esc_html(get_bloginfo('name')));

    if ($intro !== '') {
        printf('<p class="lyli-footer-intro">%s</p>', esc_html($intro));
    } else {
        echo '<p class="lyli-footer-intro">' . esc_html__('Quà len thủ công với một nhịp điệu nhẹ nhàng, ấm áp.', 'shop-child') . '</p>';
    }
    echo '</div>';

    echo '<div class="lyli-footer-links"><h2>' . esc_html__('Khám phá', 'shop-child') . '</h2>';
    wp_nav_menu([
        'theme_location' => 'secondary',
        'container' => false,
        'menu_class' => 'lyli-footer-menu',
        'fallback_cb' => false,
        'depth' => 1,
    ]);
    echo '</div>';

    $has_connect = $email !== '' || $phone !== '' || $facebook !== '' || $instagram !== '' || $tiktok !== '' || $zalo !== '';
    if ($has_connect) {
        echo '<div class="lyli-footer-connect"><h2>' . esc_html__('Kết nối', 'shop-child') . '</h2>';
    }

    if ($email !== '' || $phone !== '') {
        echo '<ul class="lyli-footer-contact">';
        if ($phone !== '') {
            printf(
                '<li><a href="tel:%1$s">%2$s</a></li>',
                esc_attr(preg_replace('/[^0-9+]/', '', $phone)),
                esc_html($phone)
            );
        }
        if ($email !== '') {
            printf(
                '<li><a href="mailto:%1$s">%2$s</a></li>',
                esc_attr($email),
                esc_html($email)
            );
        }
        echo '</ul>';
    }

    $socials = [];
    if ($facebook !== '') {
        $socials[] = ['href' => $facebook, 'label' => 'Facebook'];
    }
    if ($instagram !== '') {
        $socials[] = ['href' => $instagram, 'label' => 'Instagram'];
    }
    if ($tiktok !== '') {
        $socials[] = ['href' => $tiktok, 'label' => 'TikTok'];
    }
    if ($zalo !== '') {
        $socials[] = ['href' => $zalo, 'label' => 'Zalo'];
    }

    if (! empty($socials)) {
        echo '<ul class="lyli-footer-social">';
        foreach ($socials as $social) {
            printf(
                '<li><a href="%1$s" target="_blank" rel="noopener noreferrer">%2$s</a></li>',
                esc_url($social['href']),
                esc_html($social['label'])
            );
        }
        echo '</ul>';
    }

    if ($has_connect) {
        echo '</div>';
    }
    echo '</div>';

    $copyright = $has_settings ? \LyliSiteSettings\get_footer_copyright() : '';
    if ($copyright === '') {
        $copyright = sprintf('© %s %s', wp_date('Y'), get_bloginfo('name'));
    }
    printf('<div class="lyli-footer-bottom"><span>%s</span><a href="%s">%s</a></div>', esc_html($copyright), esc_url(home_url('/tai-khoan/')), esc_html__('Tài khoản', 'shop-child'));
    echo '</footer>';
}

/**
 * Inject the Lyli copyright line into the Botiga copyright bar, keeping the
 * default Botiga credits as a fallback when the setting is empty.
 */
function render_copyright(): void
{
    if (! function_exists('LyliSiteSettings\\get_footer_copyright')) {
        return;
    }

    $copyright = \LyliSiteSettings\get_footer_copyright();
    if ($copyright === '') {
        return;
    }

    printf('<span class="lyli-footer-copyright">%s</span>', esc_html($copyright));
}
