<?php
/**
 * Lyli Shop — announcement bar.
 * Renders the optional announcement bar from Lyli Site Settings.
 * Empty/disabled settings hide the bar entirely.
 */

namespace ShopChild\Announcement;

if (! defined('ABSPATH')) {
    exit;
}

/**
 * Render announcement bar right after the opening body tag.
 * Botiga header.php calls wp_body_open().
 */
add_action('wp_body_open', __NAMESPACE__ . '\\render', 5);
function render(): void
{
    if (! function_exists('LyliSiteSettings\\is_announcement_enabled')) {
        return;
    }

    if (! \LyliSiteSettings\is_announcement_enabled()) {
        return;
    }

    $text = \LyliSiteSettings\get_announcement();
    if ($text === '') {
        return;
    }

    printf(
        '<div class="lyli-announcement" role="region" aria-label="%1$s"><p>%2$s</p></div>',
        esc_attr__('Thông báo', 'shop-child'),
        esc_html($text)
    );
}