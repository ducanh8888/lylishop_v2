<?php
/**
 * Lyli Shop — controlled Gutenberg block patterns.
 *
 * Registered via the same mechanism Botiga uses (register_block_pattern).
 * Patterns are editable in Gutenberg, responsive, use approved tokens, and
 * contain NO fake claims, NO fake contact details, NO fake testimonials,
 * NO proprietary assets, and no fabricated delivery/return/refund promises.
 *
 * Homepage section ordering/visibility is controlled by normal block editing
 * in the page, not by a second options screen.
 */

namespace ShopChild\Patterns;

if (! defined('ABSPATH')) {
    exit;
}

/**
 * Register the Lyli block pattern category.
 */
add_action('init', __NAMESPACE__ . '\\register_category', 9);
function register_category(): void
{
    if (! function_exists('register_block_pattern_category')) {
        return;
    }

    register_block_pattern_category(
        'lyli-shop',
        [
            'label' => __('Lyli Shop', 'shop-child'),
        ]
    );
}

/**
 * Register all controlled patterns.
 */
add_action('init', __NAMESPACE__ . '\\register_patterns', 20);
function register_patterns(): void
{
    if (! function_exists('register_block_pattern')) {
        return;
    }

    $primary = COLOR_TOKENS['brand-primary'];

    $patterns = [
        'lyli-hero' => [
            'title'      => __('Lyli Hero', 'shop-child'),
            'categories' => ['lyli-shop'],
            'content'    => '<!-- wp:cover {"dimRatio":60,"overlayColor":"lyli-primary","isUserOverlayColor":true,"minHeight":480,"contentPosition":"center center"} -->
<div class="wp-block-cover"><span aria-hidden="true" class="wp-block-cover__background has-lyli-primary-background-color has-background-dim-60 has-background-dim"></span><div class="wp-block-cover__inner-container"><!-- wp:group {"align":"wide","layout":{"type":"constrained"}} -->
<div class="wp-block-group alignwide"><!-- wp:heading {"textAlign":"center","textColor":"white","fontSize":"huge"} -->
<h2 class="wp-block-heading has-text-align-center has-text-color" style="color:#ffffff">' . esc_html__('Sản phẩm móc len thủ công', 'shop-child') . '</h2>
<!-- /wp:heading -->

<!-- wp:paragraph {"align":"center","textColor":"white"} -->
<p class="has-text-align-center has-text-color" style="color:#ffffff">' . esc_html__('Giới thiệu ngắn về Ly li shop — bạn có thể thay nội dung này trong WordPress.', 'shop-child') . '</p>
<!-- /wp:paragraph -->

<!-- wp:buttons {"layout":{"type":"flex","justifyContent":"center"}} -->
<div class="wp-block-buttons"><!-- wp:button {"backgroundColor":"white","textColor":"lyli-primary","fontSize":"medium"} -->
<div class="wp-block-button"><a class="wp-block-button__link has-text-color has-background" style="color:' . $primary . ';background-color:#ffffff">' . esc_html__('Xem cửa hàng', 'shop-child') . '</a></div>
<!-- /wp:button --></div>
<!-- /wp:buttons --></div>
<!-- /wp:group --></div></div>
<!-- /wp:cover -->',
        ],

        'lyli-featured-categories' => [
            'title'      => __('Lyli Danh mục nổi bật', 'shop-child'),
            'categories' => ['lyli-shop'],
            'content'    => '<!-- wp:group {"className":"lyli-featured-categories"} -->
<div class="wp-block-group lyli-featured-categories"><!-- wp:heading {"textAlign":"center","level":2} -->
<h2 class="wp-block-heading has-text-align-center">' . esc_html__('Danh mục sản phẩm', 'shop-child') . '</h2>
<!-- /wp:heading -->

<!-- wp:paragraph {"align":"center"} -->
<p class="has-text-align-center">' . esc_html__('Chọn danh mục để xem sản phẩm tương ứng.', 'shop-child') . '</p>
<!-- /wp:paragraph -->

<!-- wp:columns -->
<div class="wp-block-columns"><!-- wp:column {"verticalAlignment":"center"} -->
<div class="wp-block-column is-vertically-aligned-center"><!-- wp:heading {"level":4} -->
<h4 class="wp-block-heading">' . esc_html__('Móc khóa len', 'shop-child') . '</h4>
<!-- /wp:heading --></div>
<!-- /wp:column -->

<!-- wp:column -->
<div class="wp-block-column"><!-- wp:heading {"level":4} -->
<h4 class="wp-block-heading">' . esc_html__('Gấu bông len', 'shop-child') . '</h4>
<!-- /wp:heading --></div>
<!-- /wp:column -->

<!-- wp:column -->
<div class="wp-block-column"><!-- wp:heading {"level":4} -->
<h4 class="wp-block-heading">' . esc_html__('Hoa len', 'shop-child') . '</h4>
<!-- /wp:heading --></div>
<!-- /wp:column -->

<!-- wp:column -->
<div class="wp-block-column"><!-- wp:heading {"level":4} -->
<h4 class="wp-block-heading">' . esc_html__('Hộp quà', 'shop-child') . '</h4>
<!-- /wp:heading --></div>
<!-- /wp:column --></div>
<!-- /wp:columns --></div>
<!-- /wp:group -->',
        ],

        'lyli-brand-story' => [
            'title'      => __('Lyli Câu chuyện thương hiệu', 'shop-child'),
            'categories' => ['lyli-shop'],
            'content'    => '<!-- wp:group -->
<div class="wp-block-group"><!-- wp:heading {"level":2} -->
<h2 class="wp-block-heading">' . esc_html__('Câu chuyện Ly li shop', 'shop-child') . '</h2>
<!-- /wp:heading -->

<!-- wp:paragraph -->
<p>' . esc_html__('Ly li shop là cửa hàng sản phẩm móc len thủ công. Nội dung giới thiệu chi tiết có thể được chỉnh sửa tại đây qua WordPress.', 'shop-child') . '</p>
<!-- /wp:paragraph -->

<!-- wp:image {"sizeSlug":"large","className":"is-style-rounded"} -->
<figure class="wp-block-image size-large is-style-rounded"><img src="" alt="' . esc_attr__('Hình ảnh sản phẩm thủ công', 'shop-child') . '" /></figure>
<!-- /wp:image --></div>
<!-- /wp:group -->',
        ],

        'lyli-usp' => [
            'title'      => __('Lyli Điểm nổi bật', 'shop-child'),
            'categories' => ['lyli-shop'],
            'content'    => '<!-- wp:group {"className":"lyli-pattern"} -->
<div class="wp-block-group lyli-pattern"><!-- wp:heading {"textAlign":"center","level":2} -->
<h2 class="wp-block-heading has-text-align-center">' . esc_html__('Vì sao chọn Ly li shop?', 'shop-child') . '</h2>
<!-- /wp:heading -->

<!-- wp:columns -->
<div class="wp-block-columns"><!-- wp:column -->
<div class="wp-block-column"><!-- wp:heading {"level":3} --><h3 class="wp-block-heading">' . esc_html__('Thủ công', 'shop-child') . '</h3><!-- /wp:heading --><!-- wp:paragraph --><p>' . esc_html__('Sản phẩm được làm thủ công tỉ mỉ.', 'shop-child') . '</p><!-- /wp:paragraph --></div>
<!-- /wp:column -->

<!-- wp:column -->
<div class="wp-block-column"><!-- wp:heading {"level":3} --><h3 class="wp-block-heading">' . esc_html__('Chất liệu', 'shop-child') . '</h3><!-- /wp:heading --><!-- wp:paragraph --><p>' . esc_html__('Chất liệu len được lựa chọn cẩn thận.', 'shop-child') . '</p><!-- /wp:paragraph --></div>
<!-- /wp:column -->

<!-- wp:column -->
<div class="wp-block-column"><!-- wp:heading {"level":3} --><h3 class="wp-block-heading">' . esc_html__('Quà tặng', 'shop-child') . '</h3><!-- /wp:heading --><!-- wp:paragraph --><p>' . esc_html__('Phù hợp làm quà tặng ý nghĩa.', 'shop-child') . '</p><!-- /wp:paragraph --></div>
<!-- /wp:column --></div>
<!-- /wp:columns --></div>
<!-- /wp:group -->',
        ],

        'lyli-custom-order-cta' => [
            'title'      => __('Lyli CTA đặt mẫu', 'shop-child'),
            'categories' => ['lyli-shop'],
            'content'    => '<!-- wp:group {"backgroundColor":"lyli-primary","textColor":"white","layout":{"type":"constrained"}} -->
<div class="wp-block-group has-text-color has-background" style="background-color:' . $primary . ';color:#ffffff"><!-- wp:heading {"textAlign":"center","level":2} -->
<h2 class="wp-block-heading has-text-align-center">' . esc_html__('Đặt mẫu theo yêu cầu', 'shop-child') . '</h2>
<!-- /wp:heading -->

<!-- wp:paragraph {"align":"center"} -->
<p class="has-text-align-center">' . esc_html__('Bạn muốn một sản phẩm móc len riêng? Liên hệ để đặt mẫu theo ý tưởng của bạn.', 'shop-child') . '</p>
<!-- /wp:paragraph -->

<!-- wp:buttons {"layout":{"type":"flex","justifyContent":"center"}} -->
<div class="wp-block-buttons"><!-- wp:button {"backgroundColor":"white","textColor":"lyli-primary","fontSize":"medium"} -->
<div class="wp-block-button"><a class="wp-block-button__link has-text-color has-background" style="color:' . $primary . ';background-color:#ffffff">' . esc_html__('Liên hệ đặt mẫu', 'shop-child') . '</a></div>
<!-- /wp:button --></div>
<!-- /wp:buttons --></div>
<!-- /wp:group -->',
        ],

        'lyli-featured-products' => [
            'title'      => __('Lyli Sản phẩm nổi bật', 'shop-child'),
            'categories' => ['lyli-shop'],
            'content'    => '<!-- wp:group -->
<div class="wp-block-group"><!-- wp:heading {"level":2} -->
<h2 class="wp-block-heading">' . esc_html__('Sản phẩm nổi bật', 'shop-child') . '</h2>
<!-- /wp:heading -->

<!-- wp:paragraph -->
<p>' . esc_html__('Khu vực này hiển thị sản phẩm khi cửa hàng có sản phẩm công khai.', 'shop-child') . '</p>
<!-- /wp:paragraph -->

<!-- wp:woocommerce/product-new {"columns":4,"rows":1} /--></div>
<!-- /wp:group -->',
        ],

        'lyli-final-cta' => [
            'title'      => __('Lyli CTA cuối trang', 'shop-child'),
            'categories' => ['lyli-shop'],
            'content'    => '<!-- wp:group {"className":"lyli-final-cta","layout":{"type":"constrained"}} -->
<div class="wp-block-group lyli-final-cta"><!-- wp:heading {"textAlign":"center","level":2} -->
<h2 class="wp-block-heading has-text-align-center">' . esc_html__('Ghé thăm cửa hàng của Ly li shop', 'shop-child') . '</h2>
<!-- /wp:heading -->

<!-- wp:paragraph {"align":"center"} -->
<p class="has-text-align-center">' . esc_html__('Khám phá các sản phẩm móc len thủ công mới nhất.', 'shop-child') . '</p>
<!-- /wp:paragraph -->

<!-- wp:buttons {"layout":{"type":"flex","justifyContent":"center"}} -->
<div class="wp-block-buttons"><!-- wp:button {"backgroundColor":"lyli-primary","fontSize":"medium"} -->
<div class="wp-block-button"><a class="wp-block-button__link has-background" style="background-color:' . $primary . '">' . esc_html__('Xem cửa hàng', 'shop-child') . '</a></div>
<!-- /wp:button --></div>
<!-- /wp:buttons --></div>
<!-- /wp:group -->',
        ],

        'lyli-empty-shop' => [
            'title'      => __('Lyli Cửa hàng trống', 'shop-child'),
            'categories' => ['lyli-shop'],
            'content'    => '<!-- wp:group {"className":"lyli-empty-shop","layout":{"type":"constrained"}} -->
<div class="wp-block-group lyli-empty-shop"><!-- wp:heading {"textAlign":"center","level":2} -->
<h2 class="wp-block-heading has-text-align-center">' . esc_html__('Cửa hàng đang được chuẩn bị', 'shop-child') . '</h2>
<!-- /wp:heading -->

<!-- wp:paragraph {"align":"center"} -->
<p class="has-text-align-center">' . esc_html__('Các sản phẩm sẽ sớm được giới thiệu. Vui lòng quay lại sau.', 'shop-child') . '</p>
<!-- /wp:paragraph --></div>
<!-- /wp:group -->',
        ],
    ];

    foreach ($patterns as $slug => $pattern) {
        register_block_pattern(
            'lyli/' . $slug,
            $pattern
        );
    }
}