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
add_action('botiga_before_footer_copyright', __NAMESPACE__ . '\\render_footer_info', 5);
function render_footer_info(): void
{
    if (! function_exists('LyliSiteSettings\\get_footer_intro')) {
        return;
    }

    $intro      = \LyliSiteSettings\get_footer_intro();
    $email      = \LyliSiteSettings\get_contact_email();
    $phone      = \LyliSiteSettings\get_contact_phone();
    $facebook   = \LyliSiteSettings\get_facebook_url();
    $instagram  = \LyliSiteSettings\get_instagram_url();
    $tiktok     = \LyliSiteSettings\get_tiktok_url();
    $zalo       = \LyliSiteSettings\get_zalo_url();

    if ($intro === '' && $email === '' && $phone === '' && $facebook === '' && $instagram === '' && $tiktok === '' && $zalo === '') {
        return;
    }

    echo '<div class="lyli-footer-info">';

    if ($intro !== '') {
        printf('<p class="lyli-footer-intro">%s</p>', esc_html($intro));
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

    echo '</div>';
}

/**
 * Inject the Lyli copyright line into the Botiga copyright bar, keeping the
 * default Botiga credits as a fallback when the setting is empty.
 */
add_action('botiga_footer_copyright_content_start', __NAMESPACE__ . '\\render_copyright', 15);
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