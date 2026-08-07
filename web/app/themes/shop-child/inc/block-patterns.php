<?php
/**
 * Editable Lyli Shop Gutenberg patterns.
 * Content remains in WordPress; these patterns provide the visual starting system.
 */

namespace ShopChild\Patterns;

if (! defined('ABSPATH')) {
    exit;
}

add_action('init', __NAMESPACE__ . '\\register_category', 9);
function register_category(): void
{
    if (function_exists('register_block_pattern_category')) {
        register_block_pattern_category('lyli-shop', ['label' => __('Lyli Shop', 'shop-child')]);
    }
}

add_action('init', __NAMESPACE__ . '\\register_patterns', 20);
function register_patterns(): void
{
    if (! function_exists('register_block_pattern')) {
        return;
    }

    $shop_url = function_exists('wc_get_page_permalink') ? wc_get_page_permalink('shop') : home_url('/cua-hang/');
    $custom_url = page_url_by_title('Đặt mẫu theo yêu cầu', home_url('/dat-mau-theo-yeu-cau/'));
    $about_url = page_url_by_title('Giới thiệu', home_url('/gioi-thieu/'));

    $category_names = ['Móc khóa len', 'Gấu bông len', 'Hoa len', 'Hộp quà', 'Đặt mẫu theo yêu cầu'];
    $category_cards = '';
    foreach ($category_names as $index => $name) {
        $term = get_term_by('name', $name, 'product_cat');
        $url = $term && ! is_wp_error($term) ? get_term_link($term) : $shop_url;
        if (is_wp_error($url)) {
            $url = $shop_url;
        }
        $category_cards .= sprintf(
            '<!-- wp:group {"className":"lyli-category-card","layout":{"type":"constrained"}} -->'
            . '<div class="wp-block-group lyli-category-card"><p class="lyli-category-index">%1$02d</p>'
            . '<!-- wp:heading {"level":3,"fontSize":"larger"} --><h3 class="wp-block-heading has-larger-font-size"><a href="%2$s">%3$s</a></h3><!-- /wp:heading -->'
            . '<!-- wp:paragraph --><p>%4$s</p><!-- /wp:paragraph --></div><!-- /wp:group -->',
            $index + 1,
            esc_url($url),
            esc_html($name),
            esc_html(category_description($index))
        );
    }

    $patterns = [
        'lyli-hero' => [
            'title' => __('Lyli — Hero cửa hàng', 'shop-child'),
            'categories' => ['lyli-shop'],
            'content' => sprintf(
                '<!-- wp:group {"align":"wide","className":"lyli-pattern lyli-hero","layout":{"type":"constrained"}} -->'
                . '<div class="wp-block-group alignwide lyli-pattern lyli-hero"><!-- wp:columns {"verticalAlignment":"center","className":"lyli-hero-grid"} -->'
                . '<div class="wp-block-columns are-vertically-aligned-center lyli-hero-grid"><!-- wp:column {"verticalAlignment":"center","width":"56%%"} -->'
                . '<div class="wp-block-column is-vertically-aligned-center" style="flex-basis:56%%"><!-- wp:paragraph {"className":"lyli-eyebrow"} --><p class="lyli-eyebrow">Lyli Shop · Handmade</p><!-- /wp:paragraph -->'
                . '<!-- wp:heading {"level":1,"fontSize":"gigantic"} --><h1 class="wp-block-heading has-gigantic-font-size">Những món quà len nhỏ, mang theo cảm xúc thật</h1><!-- /wp:heading -->'
                . '<!-- wp:paragraph {"fontSize":"large","className":"lyli-hero-copy"} --><p class="lyli-hero-copy has-large-font-size">Khám phá các mẫu móc len được giới thiệu theo cách nhẹ nhàng, ấm áp và dễ chọn cho từng dịp.</p><!-- /wp:paragraph -->'
                . '<!-- wp:buttons --><div class="wp-block-buttons"><!-- wp:button --><div class="wp-block-button"><a class="wp-block-button__link wp-element-button" href="%1$s">Xem cửa hàng</a></div><!-- /wp:button -->'
                . '<!-- wp:button {"className":"is-style-outline"} --><div class="wp-block-button is-style-outline"><a class="wp-block-button__link wp-element-button" href="%2$s">Đặt mẫu riêng</a></div><!-- /wp:button --></div><!-- /wp:buttons --></div><!-- /wp:column -->'
                . '<!-- wp:column {"verticalAlignment":"center","width":"44%%"} --><div class="wp-block-column is-vertically-aligned-center" style="flex-basis:44%%">'
                . '<!-- wp:cover {"dimRatio":0,"minHeight":520,"minHeightUnit":"px","customOverlayColor":"#F0E9E3","className":"lyli-hero-visual"} --><div class="wp-block-cover lyli-hero-visual" style="min-height:520px"><span aria-hidden="true" class="wp-block-cover__background has-background-dim-0 has-background-dim" style="background-color:#F0E9E3"></span><div class="wp-block-cover__inner-container">'
                . '<!-- wp:group {"layout":{"type":"constrained"}} --><div class="wp-block-group"><!-- wp:paragraph {"align":"center","className":"lyli-yarn-mark"} --><p class="has-text-align-center lyli-yarn-mark">LYLI</p><!-- /wp:paragraph -->'
                . '<!-- wp:paragraph {"align":"center"} --><p class="has-text-align-center">Một khoảng hình ảnh ấm áp — có thể thay bằng ảnh sản phẩm trong trình sửa trang.</p><!-- /wp:paragraph --></div><!-- /wp:group --></div></div><!-- /wp:cover --></div><!-- /wp:column --></div><!-- /wp:columns --></div><!-- /wp:group -->',
                esc_url($shop_url),
                esc_url($custom_url)
            ),
        ],
        'lyli-featured-categories' => [
            'title' => __('Lyli — Năm danh mục chính', 'shop-child'),
            'categories' => ['lyli-shop'],
            'content' => '<!-- wp:group {"align":"wide","className":"lyli-pattern lyli-featured-categories","layout":{"type":"constrained"}} --><div class="wp-block-group alignwide lyli-pattern lyli-featured-categories">'
                . section_heading('Danh mục chính', 'Chọn nhanh theo kiểu quà bạn đang tìm.')
                . '<!-- wp:group {"className":"lyli-category-grid","layout":{"type":"default"}} --><div class="wp-block-group lyli-category-grid">' . $category_cards . '</div><!-- /wp:group --></div><!-- /wp:group -->',
        ],
        'lyli-featured-products' => [
            'title' => __('Lyli — Sản phẩm mới', 'shop-child'),
            'categories' => ['lyli-shop'],
            'content' => '<!-- wp:group {"align":"wide","className":"lyli-pattern lyli-products-section","layout":{"type":"constrained"}} --><div class="wp-block-group alignwide lyli-pattern lyli-products-section">'
                . section_heading('Mẫu mới trong cửa hàng', 'Sản phẩm sẽ xuất hiện tại đây ngay khi chủ shop đăng bán.')
                . '<!-- wp:shortcode -->[products limit="4" columns="4" orderby="date" order="DESC"]<!-- /wp:shortcode -->'
                . sprintf('<!-- wp:buttons {"layout":{"type":"flex","justifyContent":"center"}} --><div class="wp-block-buttons"><!-- wp:button {"className":"is-style-outline"} --><div class="wp-block-button is-style-outline"><a class="wp-block-button__link wp-element-button" href="%s">Xem tất cả sản phẩm</a></div><!-- /wp:button --></div><!-- /wp:buttons -->', esc_url($shop_url))
                . '</div><!-- /wp:group -->',
        ],
        'lyli-usp' => [
            'title' => __('Lyli — Giá trị handmade', 'shop-child'),
            'categories' => ['lyli-shop'],
            'content' => '<!-- wp:group {"align":"wide","className":"lyli-pattern lyli-usp-section","layout":{"type":"constrained"}} --><div class="wp-block-group alignwide lyli-pattern lyli-usp-section">'
                . section_heading('Nhẹ nhàng trong từng lựa chọn', 'Một trải nghiệm mua sắm rõ ràng, gần gũi và tôn trọng chất handmade.')
                . '<!-- wp:columns {"className":"lyli-usp-grid"} --><div class="wp-block-columns lyli-usp-grid">'
                . info_card('01', 'Chọn mẫu dễ dàng', 'Danh mục được sắp xếp theo loại quà, không theo các nhãn kỹ thuật khó hiểu.')
                . info_card('02', 'Có thể đặt mẫu riêng', 'Gửi ý tưởng qua trang đặt mẫu khi bạn cần một thiết kế khác với sản phẩm có sẵn.')
                . info_card('03', 'Thông tin minh bạch', 'Giá, lựa chọn sản phẩm và trạng thái đơn hàng được hiển thị ngay trong cửa hàng.')
                . '</div><!-- /wp:columns --></div><!-- /wp:group -->',
        ],
        'lyli-custom-order-cta' => [
            'title' => __('Lyli — CTA đặt mẫu', 'shop-child'),
            'categories' => ['lyli-shop'],
            'content' => sprintf(
                '<!-- wp:group {"align":"wide","className":"lyli-pattern lyli-custom-cta","layout":{"type":"constrained"}} --><div class="wp-block-group alignwide lyli-pattern lyli-custom-cta"><!-- wp:columns {"verticalAlignment":"center"} --><div class="wp-block-columns are-vertically-aligned-center">'
                . '<!-- wp:column {"verticalAlignment":"center","width":"68%%"} --><div class="wp-block-column is-vertically-aligned-center" style="flex-basis:68%%"><!-- wp:paragraph {"className":"lyli-eyebrow"} --><p class="lyli-eyebrow">Mẫu riêng của bạn</p><!-- /wp:paragraph --><!-- wp:heading --><h2 class="wp-block-heading">Có một ý tưởng chưa thấy trong cửa hàng?</h2><!-- /wp:heading --><!-- wp:paragraph --><p>Gửi mô tả, màu sắc hoặc hình tham khảo. Lyli Shop sẽ trao đổi lại trước khi bắt đầu.</p><!-- /wp:paragraph --></div><!-- /wp:column -->'
                . '<!-- wp:column {"verticalAlignment":"center","width":"32%%"} --><div class="wp-block-column is-vertically-aligned-center" style="flex-basis:32%%"><!-- wp:buttons {"layout":{"type":"flex","justifyContent":"right"}} --><div class="wp-block-buttons"><!-- wp:button --><div class="wp-block-button"><a class="wp-block-button__link wp-element-button" href="%s">Gửi yêu cầu đặt mẫu</a></div><!-- /wp:button --></div><!-- /wp:buttons --></div><!-- /wp:column --></div><!-- /wp:columns --></div><!-- /wp:group -->',
                esc_url($custom_url)
            ),
        ],
        'lyli-brand-story' => [
            'title' => __('Lyli — Câu chuyện thương hiệu', 'shop-child'),
            'categories' => ['lyli-shop'],
            'content' => sprintf(
                '<!-- wp:group {"align":"wide","className":"lyli-pattern lyli-story","layout":{"type":"constrained"}} --><div class="wp-block-group alignwide lyli-pattern lyli-story"><!-- wp:columns {"verticalAlignment":"center"} --><div class="wp-block-columns are-vertically-aligned-center">'
                . '<!-- wp:column {"verticalAlignment":"center","width":"42%%"} --><div class="wp-block-column is-vertically-aligned-center lyli-story-visual" style="flex-basis:42%%"><!-- wp:paragraph {"align":"center","className":"lyli-story-mark"} --><p class="has-text-align-center lyli-story-mark">Mỗi món quà<br>có một câu chuyện</p><!-- /wp:paragraph --></div><!-- /wp:column -->'
                . '<!-- wp:column {"verticalAlignment":"center","width":"58%%"} --><div class="wp-block-column is-vertically-aligned-center" style="flex-basis:58%%"><!-- wp:paragraph {"className":"lyli-eyebrow"} --><p class="lyli-eyebrow">Về Lyli Shop</p><!-- /wp:paragraph --><!-- wp:heading --><h2 class="wp-block-heading">Không cần cầu kỳ để trở nên đáng nhớ</h2><!-- /wp:heading --><!-- wp:paragraph --><p>Lyli Shop hướng đến những món đồ len có cảm giác mềm mại, gần gũi và phù hợp để trao tặng. Nội dung câu chuyện này có thể được chủ shop chỉnh sửa bất cứ lúc nào.</p><!-- /wp:paragraph --><!-- wp:paragraph --><p><a href="%s">Đọc thêm về cửa hàng →</a></p><!-- /wp:paragraph --></div><!-- /wp:column --></div><!-- /wp:columns --></div><!-- /wp:group -->',
                esc_url($about_url)
            ),
        ],
        'lyli-final-cta' => [
            'title' => __('Lyli — CTA cuối trang', 'shop-child'),
            'categories' => ['lyli-shop'],
            'content' => sprintf(
                '<!-- wp:group {"align":"wide","className":"lyli-pattern lyli-final-cta","layout":{"type":"constrained"}} --><div class="wp-block-group alignwide lyli-pattern lyli-final-cta"><!-- wp:heading {"textAlign":"center"} --><h2 class="wp-block-heading has-text-align-center">Tìm một món quà nhỏ cho người bạn thương</h2><!-- /wp:heading --><!-- wp:paragraph {"align":"center"} --><p class="has-text-align-center">Bắt đầu từ năm danh mục chính hoặc gửi một ý tưởng riêng cho Lyli Shop.</p><!-- /wp:paragraph --><!-- wp:buttons {"layout":{"type":"flex","justifyContent":"center"}} --><div class="wp-block-buttons"><!-- wp:button --><div class="wp-block-button"><a class="wp-block-button__link wp-element-button" href="%1$s">Khám phá cửa hàng</a></div><!-- /wp:button --><!-- wp:button {"className":"is-style-outline"} --><div class="wp-block-button is-style-outline"><a class="wp-block-button__link wp-element-button" href="%2$s">Đặt mẫu riêng</a></div><!-- /wp:button --></div><!-- /wp:buttons --></div><!-- /wp:group -->',
                esc_url($shop_url),
                esc_url($custom_url)
            ),
        ],
        'lyli-empty-shop' => [
            'title' => __('Lyli — Cửa hàng chưa có sản phẩm', 'shop-child'),
            'categories' => ['lyli-shop'],
            'content' => sprintf('<!-- wp:group {"className":"lyli-empty-state","layout":{"type":"constrained"}} --><div class="wp-block-group lyli-empty-state"><!-- wp:heading {"textAlign":"center"} --><h2 class="wp-block-heading has-text-align-center">Các mẫu đầu tiên đang được chuẩn bị</h2><!-- /wp:heading --><!-- wp:paragraph {"align":"center"} --><p class="has-text-align-center">Trong lúc chờ sản phẩm mới, bạn có thể gửi ý tưởng đặt mẫu riêng.</p><!-- /wp:paragraph --><!-- wp:buttons {"layout":{"type":"flex","justifyContent":"center"}} --><div class="wp-block-buttons"><!-- wp:button --><div class="wp-block-button"><a class="wp-block-button__link wp-element-button" href="%s">Đặt mẫu theo yêu cầu</a></div><!-- /wp:button --></div><!-- /wp:buttons --></div><!-- /wp:group -->', esc_url($custom_url)),
        ],
    ];

    foreach ($patterns as $slug => $pattern) {
        register_block_pattern('lyli/' . $slug, $pattern);
    }
}

function page_url_by_title(string $title, string $fallback): string
{
    $posts = get_posts(['post_type' => 'page', 'post_status' => ['publish', 'draft'], 'title' => $title, 'posts_per_page' => 1]);
    return $posts ? (string) get_permalink($posts[0]) : $fallback;
}

function category_description(int $index): string
{
    return [
        'Nhỏ gọn, vui mắt và dễ mang theo mỗi ngày.',
        'Những người bạn len mềm mại cho góc riêng ấm áp.',
        'Một bó hoa bền lâu cho lời nhắn dịu dàng.',
        'Gợi ý quà được sắp xếp gọn gàng và dễ trao tặng.',
        'Bắt đầu từ màu sắc, kích thước hoặc hình tham khảo của bạn.',
    ][$index] ?? '';
}

function section_heading(string $title, string $copy): string
{
    return '<!-- wp:group {"className":"lyli-section-heading","layout":{"type":"constrained"}} --><div class="wp-block-group lyli-section-heading"><!-- wp:heading {"textAlign":"center"} --><h2 class="wp-block-heading has-text-align-center">' . esc_html($title) . '</h2><!-- /wp:heading --><!-- wp:paragraph {"align":"center"} --><p class="has-text-align-center">' . esc_html($copy) . '</p><!-- /wp:paragraph --></div><!-- /wp:group -->';
}

function info_card(string $number, string $title, string $copy): string
{
    return '<!-- wp:column {"className":"lyli-info-card"} --><div class="wp-block-column lyli-info-card"><p class="lyli-info-number">' . esc_html($number) . '</p><!-- wp:heading {"level":3} --><h3 class="wp-block-heading">' . esc_html($title) . '</h3><!-- /wp:heading --><!-- wp:paragraph --><p>' . esc_html($copy) . '</p><!-- /wp:paragraph --></div><!-- /wp:column -->';
}
