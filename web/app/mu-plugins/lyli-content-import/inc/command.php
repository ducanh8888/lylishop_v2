<?php

namespace LyliContentImport;

use WP_CLI;

if (! defined('ABSPATH')) {
    exit;
}

final class ContentCommand
{
    /**
     * Import the approved LyliShop content package.
     *
     * ## OPTIONS
     *
     * [--apply]
     * : Persist changes. Without this flag the command validates and reports only.
     *
     * [--assets-root=<path>]
     * : Directory containing the handoff public/ tree. Required with --apply.
     */
    public function import(array $args, array $assocArgs): void
    {
        $apply = isset($assocArgs['apply']);
        $assetsRoot = isset($assocArgs['assets-root']) ? rtrim((string) $assocArgs['assets-root'], '/\\') : '';
        $data = $this->loadData();
        $this->validateData($data, $assetsRoot, $apply);

        WP_CLI::log(sprintf(
            'Validated products=%d blog=%d assets=%d mode=%s',
            count($data['products']),
            count($data['blogPosts']),
            count($data['assets']),
            $apply ? 'apply' : 'dry-run'
        ));

        if (! $apply) {
            WP_CLI::success('Lyli content import dry-run passed; no changes made.');
            return;
        }

        if (! class_exists('WooCommerce') || ! function_exists('wc_get_product')) {
            WP_CLI::error('WooCommerce must be active before importing products.');
        }

        $attachmentMap = $this->importAssets($data, $assetsRoot);
        $this->importSettings($data);
        $termMap = $this->importProductTerms($data);
        $productIds = $this->importProducts($data, $attachmentMap, $termMap);
        $postIds = $this->importBlog($data, $attachmentMap);
        $pageIds = $this->importPages($data, $attachmentMap);
        $this->importMenu($data, $pageIds);
        $this->configureHeader();
        $this->removeDefaultPost();

        update_option('lyli_content_import_manifest', [
            'schema_version' => (int) $data['schemaVersion'],
            'products' => count($productIds),
            'blog_posts' => count($postIds),
            'assets' => count($attachmentMap),
        ], false);

        WP_CLI::success(sprintf(
            'Imported and published products=%d blog=%d pages=%d assets=%d.',
            count($productIds),
            count($postIds),
            count($pageIds),
            count($attachmentMap)
        ));
    }

    private function loadData(): array
    {
        if (! is_readable(DATA_FILE)) {
            WP_CLI::error('Content package is missing: ' . DATA_FILE);
        }
        $data = json_decode((string) file_get_contents(DATA_FILE), true);
        if (! is_array($data)) {
            WP_CLI::error('Content package is not valid JSON.');
        }
        return $data;
    }

    private function validateData(array $data, string $assetsRoot, bool $apply): void
    {
        foreach (['schemaVersion', 'site', 'products', 'blogPosts', 'assets', 'homepage', 'legal'] as $key) {
            if (! array_key_exists($key, $data)) {
                WP_CLI::error('Content package missing key: ' . $key);
            }
        }
        if (count($data['products']) !== 9 || count($data['blogPosts']) !== 5 || count($data['assets']) !== 63) {
            WP_CLI::error('Content package counts do not match the approved handoff.');
        }
        $productSlugs = array_column($data['products'], 'slug');
        $blogSlugs = array_column($data['blogPosts'], 'slug');
        if (count(array_unique($productSlugs)) !== 9 || count(array_unique($blogSlugs)) !== 5) {
            WP_CLI::error('Product or blog slugs are not unique.');
        }
        if (! $apply) {
            return;
        }
        if ($assetsRoot === '' || ! is_dir($assetsRoot)) {
            WP_CLI::error('--assets-root must point to the extracted handoff public directory.');
        }
        foreach ($data['assets'] as $asset) {
            $file = $assetsRoot . DIRECTORY_SEPARATOR . ltrim(str_replace('/', DIRECTORY_SEPARATOR, $asset['sourcePath']), DIRECTORY_SEPARATOR);
            if (! is_file($file)) {
                WP_CLI::error('Missing asset: ' . $asset['sourcePath']);
            }
            if (hash_file('sha256', $file) !== $asset['sha256']) {
                WP_CLI::error('Asset checksum mismatch: ' . $asset['sourcePath']);
            }
        }
    }

    private function importAssets(array $data, string $assetsRoot): array
    {
        require_once ABSPATH . 'wp-admin/includes/image.php';
        require_once ABSPATH . 'wp-admin/includes/file.php';
        require_once ABSPATH . 'wp-admin/includes/media.php';

        $uploads = wp_upload_dir();
        if (! empty($uploads['error'])) {
            WP_CLI::error('Uploads unavailable: ' . $uploads['error']);
        }

        $map = [];
        foreach ($data['assets'] as $asset) {
            $sourcePath = (string) $asset['sourcePath'];
            $existing = get_posts([
                'post_type' => 'attachment',
                'post_status' => 'inherit',
                'posts_per_page' => 1,
                'fields' => 'ids',
                'meta_key' => SOURCE_META,
                'meta_value' => $sourcePath,
            ]);
            if ($existing) {
                $map[$sourcePath] = (int) $existing[0];
                continue;
            }

            $relative = ltrim($sourcePath, '/');
            $source = $assetsRoot . DIRECTORY_SEPARATOR . str_replace('/', DIRECTORY_SEPARATOR, $relative);
            $uploadRelative = 'lyli-source/' . $relative;
            $target = trailingslashit($uploads['basedir']) . str_replace('/', DIRECTORY_SEPARATOR, $uploadRelative);
            wp_mkdir_p(dirname($target));
            if (! copy($source, $target)) {
                WP_CLI::error('Unable to copy asset: ' . $sourcePath);
            }
            if (hash_file('sha256', $target) !== $asset['sha256']) {
                WP_CLI::error('Copied asset checksum mismatch: ' . $sourcePath);
            }

            $mime = $this->mimeType($target);
            $attachmentId = wp_insert_attachment([
                'post_mime_type' => $mime,
                'post_title' => sanitize_text_field(pathinfo($relative, PATHINFO_FILENAME)),
                'post_status' => 'inherit',
                'guid' => trailingslashit($uploads['baseurl']) . $uploadRelative,
            ], $target);
            if (is_wp_error($attachmentId)) {
                WP_CLI::error($attachmentId->get_error_message());
            }
            update_attached_file((int) $attachmentId, $target);
            update_post_meta((int) $attachmentId, SOURCE_META, $sourcePath);
            update_post_meta((int) $attachmentId, '_lyli_source_sha256', (string) $asset['sha256']);
            if (! empty($asset['alt'])) {
                update_post_meta((int) $attachmentId, '_wp_attachment_image_alt', sanitize_text_field((string) $asset['alt']));
            }
            if ($mime !== 'image/svg+xml') {
                $metadata = wp_generate_attachment_metadata((int) $attachmentId, $target);
                if (is_array($metadata)) {
                    wp_update_attachment_metadata((int) $attachmentId, $metadata);
                }
            }
            $map[$sourcePath] = (int) $attachmentId;
        }
        WP_CLI::log('Assets imported/verified: ' . count($map));
        return $map;
    }

    private function mimeType(string $file): string
    {
        $extension = strtolower(pathinfo($file, PATHINFO_EXTENSION));
        return match ($extension) {
            'svg' => 'image/svg+xml',
            'webp' => 'image/webp',
            'png' => 'image/png',
            'jpg', 'jpeg' => 'image/jpeg',
            default => 'application/octet-stream',
        };
    }

    private function importSettings(array $data): void
    {
        $site = $data['site'];
        $footer = $data['footer'];
        $promotion = $data['promotion'];
        $settings = [
            'lyli_footer_intro' => $footer['brandDescription'],
            'lyli_contact_email' => $site['email'],
            'lyli_contact_phone' => $site['phone'],
            'lyli_facebook_url' => $site['socials']['facebook'],
            'lyli_instagram_url' => $site['socials']['instagram'],
            'lyli_tiktok_url' => $site['socials']['tiktok'],
            'lyli_zalo_url' => $site['socials']['zalo'],
            'lyli_announcement' => $promotion['summary'],
            'lyli_announcement_enabled' => 1,
            'lyli_custom_order_label' => 'Nhắn Zalo đặt hàng',
            'lyli_custom_order_url' => $site['socials']['zalo'],
            'lyli_footer_copyright' => '© ' . wp_date('Y') . ' ' . $site['name'] . '. Bản quyền thuộc về LyliShop.',
        ];
        foreach ($settings as $key => $value) {
            update_option($key, $value, false);
        }
    }

    private function importProductTerms(array $data): array
    {
        $map = [];
        foreach ($data['products'] as $product) {
            $name = (string) $product['category'];
            if (isset($map[$name])) {
                continue;
            }
            $existing = term_exists($name, 'product_cat');
            if (! $existing) {
                $existing = wp_insert_term($name, 'product_cat');
            }
            if (is_wp_error($existing)) {
                WP_CLI::error($existing->get_error_message());
            }
            $map[$name] = (int) (is_array($existing) ? $existing['term_id'] : $existing);
        }
        return $map;
    }

    private function importProducts(array $data, array $attachmentMap, array $termMap): array
    {
        $ids = [];
        foreach ($data['products'] as $record) {
            $existing = get_page_by_path($record['slug'], OBJECT, 'product');
            $product = $existing ? wc_get_product($existing->ID) : new \WC_Product_Simple();
            if (! $product instanceof \WC_Product) {
                $product = new \WC_Product_Simple();
            }
            $product->set_name((string) $record['name']);
            $product->set_slug((string) $record['slug']);
            $product->set_status('publish');
            $product->set_catalog_visibility('visible');
            $product->set_regular_price((string) (int) $record['priceVnd']);
            $product->set_short_description($this->paragraph((string) $record['shortDescription']));
            $product->set_description($this->productContent($record, $attachmentMap));
            $product->set_category_ids([(int) $termMap[$record['category']]]);
            $product->set_tag_ids($this->ensureTerms((array) $record['tags'], 'product_tag'));
            $product->set_featured(! empty($record['featured']));
            $product->set_manage_stock(false);
            if (isset($attachmentMap[$record['image']['src']])) {
                $product->set_image_id($attachmentMap[$record['image']['src']]);
            }
            $gallery = [];
            foreach ((array) ($record['images'] ?? []) as $image) {
                if (isset($attachmentMap[$image['src']]) && $attachmentMap[$image['src']] !== $product->get_image_id()) {
                    $gallery[] = $attachmentMap[$image['src']];
                }
            }
            $product->set_gallery_image_ids(array_values(array_unique($gallery)));
            $product->set_attributes($this->productAttributes($record));
            $productId = $product->save();
            update_post_meta($productId, PRODUCT_META, wp_json_encode($record, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES));
            update_post_meta($productId, '_lyli_catalog_only', 'yes');
            update_post_meta($productId, '_lyli_source_slug', (string) $record['slug']);
            if (! empty($record['metaDescription'])) {
                update_post_meta($productId, '_seopress_titles_desc', (string) $record['metaDescription']);
            }
            $ids[] = (int) $productId;
        }
        WP_CLI::log('Products published: ' . count($ids));
        return $ids;
    }

    private function ensureTerms(array $names, string $taxonomy): array
    {
        $ids = [];
        foreach ($names as $name) {
            $existing = term_exists((string) $name, $taxonomy);
            if (! $existing) {
                $existing = wp_insert_term((string) $name, $taxonomy);
            }
            if (! is_wp_error($existing)) {
                $ids[] = (int) (is_array($existing) ? $existing['term_id'] : $existing);
            }
        }
        return $ids;
    }

    private function productAttributes(array $record): array
    {
        $values = [
            'Chất liệu' => $record['information']['material'],
            'Kích thước' => $record['information']['size'],
            'Màu sắc' => $record['information']['colors'],
            'Sản xuất' => $record['information']['production'],
            'Bảo quản' => $record['information']['care'],
            'Màu có thể chọn' => implode(', ', (array) $record['availableColors']),
        ];
        $attributes = [];
        $position = 0;
        foreach ($values as $name => $value) {
            $attribute = new \WC_Product_Attribute();
            $attribute->set_id(0);
            $attribute->set_name($name);
            $attribute->set_options([(string) $value]);
            $attribute->set_position($position++);
            $attribute->set_visible(true);
            $attribute->set_variation(false);
            $attributes[] = $attribute;
        }
        return $attributes;
    }

    private function productContent(array $record, array $attachmentMap): string
    {
        $content = $this->paragraph((string) $record['longDescription']);
        $content .= $this->heading('Điểm nổi bật', 2) . $this->listBlock((array) $record['highlights']);
        $content .= $this->heading('Lợi ích và cách sử dụng', 2) . $this->listBlock((array) $record['benefits']);
        $content .= $this->heading('Lựa chọn đặt hàng', 2);
        foreach ((array) $record['orderOptions'] as $option) {
            $label = (string) $option['name'];
            if (isset($option['price'])) {
                $label .= ' — ' . number_format_i18n((int) $option['price']) . 'đ';
            }
            $content .= $this->heading($label, 3);
            if (! empty($option['description'])) {
                $content .= $this->paragraph((string) $option['description']);
            }
        }
        if (! empty($record['images'])) {
            $content .= $this->heading('Hình ảnh sản phẩm', 2);
            foreach ($record['images'] as $image) {
                if (isset($attachmentMap[$image['src']])) {
                    $content .= $this->imageBlock($attachmentMap[$image['src']], (string) $image['alt']);
                }
            }
        }
        $content .= $this->heading('Câu hỏi thường gặp', 2);
        foreach ((array) $record['faqs'] as $faq) {
            $content .= $this->detailsBlock((string) $faq['question'], (string) $faq['answer']);
        }
        return $content;
    }

    private function importBlog(array $data, array $attachmentMap): array
    {
        $category = term_exists('Cẩm nang handmade', 'category');
        if (! $category) {
            $category = wp_insert_term('Cẩm nang handmade', 'category');
        }
        $categoryId = is_wp_error($category) ? 0 : (int) (is_array($category) ? $category['term_id'] : $category);
        $ids = [];
        foreach ($data['blogPosts'] as $record) {
            $postId = $this->upsertPost('post', (string) $record['slug'], [
                'post_title' => (string) $record['title'],
                'post_excerpt' => (string) $record['excerpt'],
                'post_content' => $this->blogContent($record, $attachmentMap),
                'post_status' => 'publish',
                'post_date' => (string) $record['datePublished'],
                'post_date_gmt' => get_gmt_from_date((string) $record['datePublished']),
                'post_category' => $categoryId ? [$categoryId] : [],
                'tags_input' => (array) $record['keywords'],
            ]);
            if (isset($attachmentMap[$record['image']['src']])) {
                set_post_thumbnail($postId, $attachmentMap[$record['image']['src']]);
            }
            update_post_meta($postId, '_lyli_source_slug', (string) $record['slug']);
            update_post_meta($postId, '_lyli_reading_time', (string) $record['readingTime']);
            update_post_meta($postId, '_seopress_titles_desc', (string) $record['description']);
            $ids[] = $postId;
        }
        WP_CLI::log('Blog posts published: ' . count($ids));
        return $ids;
    }

    private function blogContent(array $record, array $attachmentMap): string
    {
        $content = '';
        foreach ((array) $record['sections'] as $section) {
            $content .= $this->heading((string) $section['heading'], 2);
            foreach ((array) ($section['body'] ?? []) as $paragraph) {
                $content .= $this->paragraph((string) $paragraph);
            }
            foreach ((array) ($section['blocks'] ?? []) as $block) {
                $content .= $this->blogBlock($block, $attachmentMap);
            }
        }
        if (! empty($record['faqs'])) {
            $content .= $this->heading('Câu hỏi thường gặp', 2);
            foreach ($record['faqs'] as $faq) {
                $content .= $this->detailsBlock((string) $faq['question'], (string) $faq['answer']);
            }
        }
        return $content;
    }

    private function blogBlock(array $block, array $attachmentMap): string
    {
        return match ($block['type']) {
            'paragraph' => $this->paragraph((string) $block['text']),
            'list' => $this->listBlock((array) $block['items'], ! empty($block['ordered'])),
            'table' => $this->tableBlock($block),
            'quote' => $this->quoteBlock((string) $block['quote'], (string) ($block['cite'] ?? '')),
            'callout' => $this->calloutBlock((string) ($block['title'] ?? ''), (string) $block['body'], (string) $block['tone']),
            'image' => isset($attachmentMap[$block['src']])
                ? $this->imageBlock($attachmentMap[$block['src']], (string) $block['alt'], (string) ($block['caption'] ?? ''))
                : '',
            default => '',
        };
    }

    private function importPages(array $data, array $attachmentMap): array
    {
        $ids = [];
        $ids['home'] = $this->upsertPost('page', 'trang-chu', [
            'post_title' => 'Trang chủ',
            'post_content' => $this->homepageContent($data, $attachmentMap),
            'post_status' => 'publish',
        ]);
        $ids['about'] = $this->upsertPost('page', 'gioi-thieu', [
            'post_title' => 'Giới thiệu',
            'post_content' => $this->aboutContent($data['homepageAbout'], $attachmentMap),
            'post_status' => 'publish',
        ]);
        $ids['contact'] = $this->upsertPost('page', 'lien-he', [
            'post_title' => 'Liên hệ',
            'post_content' => $this->contactContent($data['homepageContact']),
            'post_status' => 'publish',
        ]);
        $ids['custom'] = $this->upsertPost('page', 'dat-mau-theo-yeu-cau', [
            'post_title' => 'Đặt mẫu theo yêu cầu',
            'post_content' => $this->customOrderContent($data['homepageContact'], $data['homepageAbout']),
            'post_status' => 'publish',
        ]);
        $ids['blog'] = $this->upsertPost('page', 'blog', [
            'post_title' => 'Blog',
            'post_content' => '',
            'post_status' => 'publish',
        ]);
        $ids['privacy'] = $this->upsertPageByTitle('Chính sách bảo mật', 'chinh-sach-bao-mat', $this->legalContent($data['legal']['privacy']), 'publish');
        $ids['terms'] = $this->upsertPageByTitle('Điều khoản', 'dieu-khoan', $this->legalContent($data['legal']['terms']), 'publish');

        update_option('show_on_front', 'page');
        update_option('page_on_front', $ids['home']);
        update_option('page_for_posts', $ids['blog']);
        update_option('wp_page_for_privacy_policy', $ids['privacy']);
        return $ids;
    }

    private function homepageContent(array $data, array $attachmentMap): string
    {
        $site = $data['site'];
        $home = $data['homepage'];
        $about = $data['homepageAbout'];
        $contact = $data['homepageContact'];
        $promotion = $data['promotion'];
        $heroImage = $home['images']['hero'];
        $content = '<!-- wp:group {"className":"lyli-pattern lyli-hero","layout":{"type":"constrained"}} --><div class="wp-block-group lyli-pattern lyli-hero">';
        $content .= $this->paragraph('Móc khóa len handmade, đặt qua tin nhắn', 'lyli-eyebrow');
        $content .= $this->heading('Móc khóa len handmade cute cho những món quà nhỏ có cảm xúc.', 1);
        $content .= $this->paragraph('LyliShop làm phụ kiện len nhỏ xinh, có thể trao đổi màu sắc và đóng gói gọn gàng để bạn tặng bạn thân, người yêu hoặc tự thưởng cho mình.');
        $content .= $this->buttonBlock('Liên hệ đặt hàng', (string) $site['socials']['zalo']);
        if (isset($attachmentMap[$heroImage['src']])) {
            $content .= $this->imageBlock($attachmentMap[$heroImage['src']], (string) $heroImage['alt']);
        }
        $content .= '</div><!-- /wp:group -->';

        $content .= '<!-- wp:group {"anchor":"promotion","className":"lyli-pattern lyli-custom-cta","layout":{"type":"constrained"}} --><div id="promotion" class="wp-block-group lyli-pattern lyli-custom-cta">';
        $content .= $this->paragraph((string) $promotion['eyebrow'], 'lyli-eyebrow');
        $content .= $this->heading((string) $promotion['title'], 2) . $this->paragraph((string) $promotion['summary']);
        $content .= $this->listBlock((array) $promotion['items']);
        $content .= $this->listBlock((array) $promotion['notes']);
        $content .= '</div><!-- /wp:group -->';

        $content .= '<!-- wp:group {"anchor":"categories","className":"lyli-pattern","layout":{"type":"constrained"}} --><div id="categories" class="wp-block-group lyli-pattern">';
        $content .= $this->paragraph('Danh mục sản phẩm', 'lyli-eyebrow');
        $content .= $this->heading('Chọn nhanh theo kiểu móc khóa và quà tặng', 2);
        $content .= $this->paragraph('Các nhóm sản phẩm được sắp xếp để bạn dễ chọn size, kiểu dáng và mục đích tặng.');
        foreach ($home['categories'] as $index => $category) {
            $content .= $this->heading((string) $category['title'], 3);
            if (isset($home['categoryImages'][$index], $attachmentMap[$home['categoryImages'][$index]['src']])) {
                $image = $home['categoryImages'][$index];
                $content .= $this->imageBlock($attachmentMap[$image['src']], (string) $image['alt']);
            }
            $content .= $this->paragraph((string) $category['price'] . ' — ' . (string) $category['description']);
        }
        $content .= '</div><!-- /wp:group -->';

        $content .= '<!-- wp:group {"anchor":"featured","className":"lyli-pattern","layout":{"type":"constrained"}} --><div id="featured" class="wp-block-group lyli-pattern">';
        $content .= $this->paragraph('Sản phẩm nổi bật', 'lyli-eyebrow');
        $content .= $this->heading('Một số mẫu móc khóa len đang được ưu tiên hiển thị', 2);
        $content .= '<!-- wp:shortcode -->[products limit="4" columns="4" visibility="featured"]<!-- /wp:shortcode -->';
        $content .= '</div><!-- /wp:group -->';

        $content .= '<!-- wp:group {"anchor":"about","className":"lyli-pattern","layout":{"type":"constrained"}} --><div id="about" class="wp-block-group lyli-pattern">';
        $content .= $this->aboutContent($about, $attachmentMap);
        $content .= '</div><!-- /wp:group -->';

        $content .= '<!-- wp:group {"anchor":"gallery","className":"lyli-pattern","layout":{"type":"constrained"}} --><div id="gallery" class="wp-block-group lyli-pattern">';
        $content .= $this->heading('Hình ảnh sản phẩm LyliShop', 2);
        foreach ($data['gallery'] as $image) {
            if (isset($attachmentMap[$image['src']])) {
                $content .= $this->imageBlock($attachmentMap[$image['src']], (string) $image['alt']);
            }
        }
        $content .= '</div><!-- /wp:group -->';

        $content .= '<!-- wp:group {"anchor":"reviews","className":"lyli-pattern","layout":{"type":"constrained"}} --><div id="reviews" class="wp-block-group lyli-pattern">';
        $content .= $this->paragraph('Đánh giá khách hàng', 'lyli-eyebrow');
        $content .= $this->heading('Phản hồi thật sẽ được cập nhật rõ ràng', 2);
        foreach ($home['reviews'] as $review) {
            $content .= $this->heading((string) $review['name'], 3);
            $content .= $this->paragraph((string) $review['date'] . ' — ' . (string) $review['status']);
            $content .= $this->paragraph((string) $review['content']);
        }
        $content .= '</div><!-- /wp:group -->';

        $content .= '<!-- wp:group {"anchor":"news","className":"lyli-pattern","layout":{"type":"constrained"}} --><div id="news" class="wp-block-group lyli-pattern">';
        $content .= $this->paragraph('Tin tức', 'lyli-eyebrow');
        $content .= $this->heading('Cẩm nang quà handmade nhỏ xinh', 2);
        $content .= '<!-- wp:latest-posts {"postsToShow":5,"displayPostDate":true,"displayFeaturedImage":true,"featuredImageAlign":"left"} /-->';
        $content .= '</div><!-- /wp:group -->';

        $content .= '<!-- wp:group {"anchor":"contact","className":"lyli-pattern","layout":{"type":"constrained"}} --><div id="contact" class="wp-block-group lyli-pattern">';
        $content .= $this->contactContent($contact);
        $content .= '</div><!-- /wp:group -->';
        return $content;
    }

    private function aboutContent(array $about, array $attachmentMap): string
    {
        $content = $this->paragraph((string) $about['eyebrow'], 'lyli-eyebrow');
        $content .= $this->heading((string) $about['title'], 2) . $this->paragraph((string) $about['description']);
        if (isset($attachmentMap[$about['image']['src']])) {
            $content .= $this->imageBlock($attachmentMap[$about['image']['src']], (string) $about['image']['alt'], (string) $about['image']['caption']);
        }
        foreach ($about['highlights'] as $highlight) {
            $content .= $this->heading((string) $highlight['title'], 3) . $this->paragraph((string) $highlight['description']);
        }
        $content .= $this->heading('Quy trình', 2);
        foreach ($about['trustSteps'] as $step) {
            $content .= $this->heading((string) $step['title'], 3) . $this->paragraph((string) $step['description']);
        }
        return $content;
    }

    private function contactContent(array $contact): string
    {
        $content = $this->paragraph((string) $contact['eyebrow'], 'lyli-eyebrow');
        $content .= $this->heading((string) $contact['title'], 2) . $this->paragraph((string) $contact['description']);
        foreach ($contact['trustItems'] as $item) {
            $content .= $this->heading((string) $item['title'], 3) . $this->paragraph((string) $item['description']);
        }
        $content .= $this->heading('Kênh liên hệ', 2);
        foreach ($contact['channels'] as $channel) {
            $value = (string) $channel['value'];
            if (! empty($channel['href'])) {
                $value = sprintf('<a href="%s">%s</a>', esc_url((string) $channel['href']), esc_html($value));
            } else {
                $value = esc_html($value);
            }
            $content .= '<!-- wp:paragraph --><p><strong>' . esc_html((string) $channel['label']) . ':</strong> ' . $value . '</p><!-- /wp:paragraph -->';
        }
        $content .= $this->heading('Câu hỏi thường gặp', 2);
        foreach ($contact['faqs'] as $faq) {
            $content .= $this->detailsBlock((string) $faq['question'], (string) $faq['answer']);
        }
        return $content;
    }

    private function customOrderContent(array $contact, array $about): string
    {
        $content = $this->heading((string) $contact['title'], 1) . $this->paragraph((string) $contact['description']);
        $content .= $this->heading('Cách đặt mẫu', 2);
        foreach ($about['trustSteps'] as $step) {
            $content .= $this->heading((string) $step['title'], 3) . $this->paragraph((string) $step['description']);
        }
        $content .= $this->buttonBlock('Nhắn Zalo đặt hàng', (string) $contact['ctas'][0]['href']);
        return $content;
    }

    private function legalContent(array $legal): string
    {
        $content = '';
        foreach ($legal['blocks'] as $block) {
            if ($block['type'] === 'h1') {
                continue;
            }
            $content .= match ($block['type']) {
                'h2' => $this->heading((string) $block['text'], 2),
                'p' => $this->paragraph((string) $block['text']),
                'list' => $this->listBlock((array) $block['items'], ! empty($block['ordered'])),
                default => '',
            };
        }
        return $content;
    }

    private function upsertPageByTitle(string $title, string $slug, string $content, string $status): int
    {
        $posts = get_posts([
            'post_type' => 'page',
            'post_status' => ['draft', 'publish', 'private'],
            'posts_per_page' => 1,
            'title' => $title,
        ]);
        if ($posts) {
            $result = wp_update_post([
                'ID' => $posts[0]->ID,
                'post_name' => $slug,
                'post_content' => $content,
                'post_status' => $status,
            ], true);
            if (is_wp_error($result)) {
                WP_CLI::error($result->get_error_message());
            }
            return (int) $result;
        }
        return $this->upsertPost('page', $slug, [
            'post_title' => $title,
            'post_content' => $content,
            'post_status' => $status,
        ]);
    }

    private function upsertPost(string $type, string $slug, array $fields): int
    {
        $existing = get_page_by_path($slug, OBJECT, $type);
        $payload = array_merge($fields, ['post_type' => $type, 'post_name' => $slug]);
        if ($existing) {
            $payload['ID'] = $existing->ID;
        }
        $result = wp_insert_post($payload, true);
        if (is_wp_error($result)) {
            WP_CLI::error($result->get_error_message());
        }
        return (int) $result;
    }

    private function importMenu(array $data, array $pageIds): void
    {
        $name = 'Primary Menu';
        $menu = wp_get_nav_menu_object($name);
        if (! $menu) {
            $menuId = wp_create_nav_menu($name);
            if (is_wp_error($menuId)) {
                WP_CLI::error($menuId->get_error_message());
            }
        } else {
            $menuId = (int) $menu->term_id;
            foreach (wp_get_nav_menu_items($menuId) ?: [] as $item) {
                wp_delete_post($item->ID, true);
            }
        }
        $shopId = function_exists('wc_get_page_id') ? (int) wc_get_page_id('shop') : 0;
        $items = [
            ['Trang chủ', get_permalink($pageIds['home'])],
            ['Danh mục', trailingslashit(home_url('/')) . '#categories'],
            ['Sản phẩm', $shopId > 0 ? get_permalink($shopId) : home_url('/cua-hang/')],
            ['Blog', get_permalink($pageIds['blog'])],
            ['Giới thiệu', get_permalink($pageIds['about'])],
            ['Liên hệ', get_permalink($pageIds['contact'])],
        ];
        foreach ($items as [$title, $url]) {
            wp_update_nav_menu_item($menuId, 0, [
                'menu-item-title' => $title,
                'menu-item-url' => $url,
                'menu-item-status' => 'publish',
                'menu-item-type' => 'custom',
            ]);
        }
        $locations = get_theme_mod('nav_menu_locations', []);
        $locations['primary'] = $menuId;
        $locations['secondary'] = $menuId;
        set_theme_mod('nav_menu_locations', $locations);
    }

    private function configureHeader(): void
    {
        set_theme_mod('botiga_header_row__main_header_row', '{ "desktop": [["search"], ["logo"], ["woo_icons"]], "mobile": [["search"], ["logo"], ["woo_icons", "mobile_hamburger"]] }');
        set_theme_mod('botiga_header_row__below_header_row', '{ "desktop": [["menu"]], "mobile": [[], [], []], "mobile_offcanvas": [[]] }');
        set_theme_mod('botiga_header_row__below_header_row_column1_horizontal_alignment', 'center');
    }

    private function removeDefaultPost(): void
    {
        $post = get_page_by_path('hello-world', OBJECT, 'post');
        if ($post && $post->post_title === 'Hello world!') {
            wp_delete_post($post->ID, true);
        }
    }

    private function paragraph(string $text, string $class = ''): string
    {
        $attrs = $class !== '' ? ' {"className":"' . esc_attr($class) . '"}' : '';
        $classAttr = $class !== '' ? ' class="' . esc_attr($class) . '"' : '';
        return '<!-- wp:paragraph' . $attrs . ' --><p' . $classAttr . '>' . esc_html($text) . '</p><!-- /wp:paragraph -->';
    }

    private function heading(string $text, int $level): string
    {
        return '<!-- wp:heading {"level":' . $level . '} --><h' . $level . ' class="wp-block-heading">' . esc_html($text) . '</h' . $level . '><!-- /wp:heading -->';
    }

    private function listBlock(array $items, bool $ordered = false): string
    {
        $tag = $ordered ? 'ol' : 'ul';
        $attrs = $ordered ? ' {"ordered":true}' : '';
        $html = '<!-- wp:list' . $attrs . ' --><' . $tag . '>';
        foreach ($items as $item) {
            $html .= '<!-- wp:list-item --><li>' . esc_html((string) $item) . '</li><!-- /wp:list-item -->';
        }
        return $html . '</' . $tag . '><!-- /wp:list -->';
    }

    private function detailsBlock(string $summary, string $body): string
    {
        return '<!-- wp:details --><details class="wp-block-details"><summary>' . esc_html($summary) . '</summary><!-- wp:paragraph --><p>' . esc_html($body) . '</p><!-- /wp:paragraph --></details><!-- /wp:details -->';
    }

    private function imageBlock(int $attachmentId, string $alt, string $caption = ''): string
    {
        $url = wp_get_attachment_url($attachmentId);
        if (! $url) {
            return '';
        }
        $figureClass = $caption !== '' ? 'wp-block-image size-full' : 'wp-block-image size-full';
        $html = '<!-- wp:image {"id":' . $attachmentId . ',"sizeSlug":"full","linkDestination":"none"} -->';
        $html .= '<figure class="' . $figureClass . '"><img src="' . esc_url($url) . '" alt="' . esc_attr($alt) . '" class="wp-image-' . $attachmentId . '"/>';
        if ($caption !== '') {
            $html .= '<figcaption class="wp-element-caption">' . esc_html($caption) . '</figcaption>';
        }
        return $html . '</figure><!-- /wp:image -->';
    }

    private function buttonBlock(string $label, string $url): string
    {
        return '<!-- wp:buttons --><div class="wp-block-buttons"><!-- wp:button --><div class="wp-block-button"><a class="wp-block-button__link wp-element-button" href="' . esc_url($url) . '">' . esc_html($label) . '</a></div><!-- /wp:button --></div><!-- /wp:buttons -->';
    }

    private function quoteBlock(string $quote, string $cite): string
    {
        $citation = $cite !== '' ? '<cite>' . esc_html($cite) . '</cite>' : '';
        return '<!-- wp:quote --><blockquote class="wp-block-quote"><p>' . esc_html($quote) . '</p>' . $citation . '</blockquote><!-- /wp:quote -->';
    }

    private function calloutBlock(string $title, string $body, string $tone): string
    {
        $html = '<!-- wp:group {"className":"lyli-callout lyli-callout-' . esc_attr($tone) . '","layout":{"type":"constrained"}} --><div class="wp-block-group lyli-callout lyli-callout-' . esc_attr($tone) . '">';
        if ($title !== '') {
            $html .= $this->heading($title, 3);
        }
        return $html . $this->paragraph($body) . '</div><!-- /wp:group -->';
    }

    private function tableBlock(array $block): string
    {
        $html = '<!-- wp:table --><figure class="wp-block-table"><table><thead><tr>';
        foreach ((array) $block['headers'] as $header) {
            $html .= '<th>' . esc_html((string) $header) . '</th>';
        }
        $html .= '</tr></thead><tbody>';
        foreach ((array) $block['rows'] as $row) {
            $html .= '<tr>';
            foreach ($row as $cell) {
                $html .= '<td>' . esc_html((string) $cell) . '</td>';
            }
            $html .= '</tr>';
        }
        $html .= '</tbody></table>';
        if (! empty($block['caption'])) {
            $html .= '<figcaption class="wp-element-caption">' . esc_html((string) $block['caption']) . '</figcaption>';
        }
        return $html . '</figure><!-- /wp:table -->';
    }
}
