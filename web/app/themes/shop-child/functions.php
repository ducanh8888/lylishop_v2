<?php
/**
 * shop-child functions — presentation only.
 * No cart/checkout/voucher business logic here (PLAN.md section 6.1).
 */

namespace ShopChild;

if (! defined('ABSPATH')) {
    exit;
}

add_action('wp_enqueue_scripts', __NAMESPACE__ . '\\enqueue_styles', 20);

function enqueue_styles(): void
{
    wp_enqueue_style(
        'storefront-parent',
        get_template_directory_uri() . '/style.css',
        [],
        wp_get_theme('storefront')->get('Version')
    );

    wp_enqueue_style(
        'shop-child',
        get_stylesheet_directory_uri() . '/style.css',
        ['storefront-parent'],
        wp_get_theme()->get('Version')
    );
}
