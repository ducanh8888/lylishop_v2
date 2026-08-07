<?php
/**
 * Lyli Shop — footer integration.
 * Renders footer intro/contact/social from Lyli Site Settings inside Botiga's
 * own Header/Footer Builder footer. The child theme does not create a second
 * <footer> element and does not replace Botiga's outer footer architecture.
 * Missing values are hidden cleanly (no invented contact data).
 */

namespace ShopChild\Footer;

if (! defined('ABSPATH')) {
    exit;
}

/**
 * Register against the documented inner hook emitted by Botiga 2.4.7's
 * footer_front_output(). If the builder is disabled, Botiga's native legacy
 * footer remains intact instead of being replaced by child-theme markup.
 */
add_action('after_setup_theme', __NAMESPACE__ . '\\register_footer', 30);
function register_footer(): void
{
    add_action('botiga_bhfb_footer_inner_before', __NAMESPACE__ . '\\render_footer_info', 5);
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

    echo '<div class="lyli-footer-content"><div class="lyli-footer-inner">';
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
    echo '</div>';
}
