<?php
/**
 * Lyli Shop — WooCommerce single-product (PDP) presentation.
 * Split out of inc/woocommerce.php per the Storefront V2 contract's file
 * ownership rule (docs/STOREFRONT-V2-IMPLEMENTATION.md §16/§13a) once
 * that file grew past its size/concern-mixing trigger. Presentation
 * only — no variation engine, no add-to-cart logic, no pricing/stock
 * changes.
 */

namespace ShopChild\Woo;

if (! defined('ABSPATH')) {
    exit;
}

/**
 * Add a small "Custom order available" hint on the single product page only
 * when the owner has configured the Custom-Order CTA site settings.
 * Pure presentation; no business logic.
 */
add_action('woocommerce_single_product_summary', __NAMESPACE__ . '\\maybe_render_custom_order_hint', 40);
function maybe_render_custom_order_hint(): void
{
    if (! function_exists('LyliSiteSettings\\get_custom_order_label')) {
        return;
    }

    $label = \LyliSiteSettings\get_custom_order_label();
    $url   = \LyliSiteSettings\get_custom_order_url();
    if ($label === '' || $url === '') {
        return;
    }

    printf(
        '<p class="lyli-custom-order-hint"><a href="%1$s">%2$s</a></p>',
        esc_url($url),
        esc_html($label)
    );
}

/**
 * Batch B — curated related-products heading.
 *
 * Contract §10.5 freezes "native grid, not carousel" for the current
 * catalog size (3 related items exactly fills one row; nothing to
 * carousel through). Because the carousel is NOT enabled
 * (shop_single_related_products_slider stays off), the render path is
 * WooCommerce's own default template (templates/single-product/
 * related.php), which reads its heading from the *native* WooCommerce
 * filter `woocommerce_product_related_products_heading` — not
 * `botiga_woocommerce_product_related_products_heading`, which only
 * fires inside Botiga's own carousel-rendering function
 * (botiga_woocommerce_output_related_products_slider(), confirmed by
 * reading inc/plugins/woocommerce/features/related-products.php) and
 * would never fire on the grid path we're actually using. Verified by
 * reading both templates directly rather than assuming the Botiga-
 * prefixed hook name from the original contract draft still applied.
 */
add_filter('woocommerce_product_related_products_heading', __NAMESPACE__ . '\\related_products_heading');
function related_products_heading(): string
{
    return __('Có thể bạn cũng thích', 'shop-child');
}

/**
 * Batch B — PDP description recomposition.
 *
 * Evidence (full catalog census, all 12 published products, live-queried
 * via WP-CLI): every single product's description follows
 * [intro paragraph] → <h2>Thông tin sản phẩm</h2> → ... → <h2>Lưu ý sản
 * phẩm handmade</h2> → [closing paragraph], with a variable middle
 * section (1-2 headings, inconsistently worded: "Cá nhân hóa và thời
 * gian chuẩn bị" / "Chọn mẫu" / "Chọn màu" / "Lựa chọn sản phẩm").
 * "Thông tin sản phẩm" and "Lưu ý sản phẩm handmade" are the ONLY two
 * headings stable across 12/12 products, always first/last, always
 * <h2>, always at the top level (never nested) — confirmed by reading
 * the raw post_content HTML directly, not assumed.
 *
 * Mechanism: DOMDocument (PHP built-in, no dependency) walks the
 * top-level child nodes of the *already-filtered* content (same content
 * woocommerce_product_description_tab() would have echoed via
 * the_content()) and buckets them into at most 3 groups by matching only
 * those two exact heading strings, case-insensitively, on <h2> nodes:
 *   - "before"  — content before the first recognized heading (intro
 *                 paragraph on every product sampled)
 *   - "info"    — content following "Thông tin sản phẩm" up to the next
 *                 heading of any kind
 *   - "details" — everything else in original order (the variable middle
 *                 heading(s) + their content, PLUS the "Lưu ý sản phẩm
 *                 handmade" heading + its content + any closing text) —
 *                 deliberately NOT split further, since the middle
 *                 heading text is not stable enough to key on safely
 *
 * This never reorders content and never drops a node — every top-level
 * child of the original content lands in exactly one bucket, in its
 * original relative order. A product with neither recognized heading
 * (partial/minimal content) puts everything in "before", which renders
 * identically to today's plain tab — a safe degrade, not a broken one.
 *
 * Implementation layer: replaces the 'description' tab's callback via
 * the documented `woocommerce_product_tabs` filter (contract-approved
 * mechanism already used elsewhere in this project) rather than hooking
 * the global `the_content` filter, which would be too broad (would also
 * touch any other the_content() call on the same request). No template
 * override — templates/single-product/tabs/description.php is untouched;
 * this only swaps which function the tabs array calls for that one tab.
 */
add_filter('woocommerce_product_tabs', __NAMESPACE__ . '\\recompose_description_tab');
function recompose_description_tab(array $tabs): array
{
    if (isset($tabs['description'])) {
        $tabs['description']['callback'] = __NAMESPACE__ . '\\render_recomposed_description_tab';
    }

    return $tabs;
}

function render_recomposed_description_tab(): void
{
    global $post;

    $heading = apply_filters('woocommerce_product_description_heading', __('Description', 'woocommerce'));
    if ($heading) {
        printf('<h2>%s</h2>', esc_html($heading));
    }

    $content = apply_filters('the_content', $post->post_content);

    $sections = split_description_sections($content);

    if ($sections === null) {
        // Parsing failed (malformed HTML or nothing recognizable) — fail
        // safe to the plain, unmodified content rather than risk losing
        // or garbling owner-written text.
        echo $content; // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped -- already filtered by the_content
        return;
    }

    if ($sections['before'] !== '') {
        printf('<div class="lyli-pdp-description-intro">%s</div>', $sections['before']); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped -- built from the same filtered content
    }
    if ($sections['info'] !== '') {
        printf(
            '<div class="lyli-pdp-section"><h3 class="lyli-pdp-section-title">%s</h3>%s</div>',
            esc_html__('Thông tin sản phẩm', 'shop-child'),
            $sections['info'] // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped -- built from the same filtered content
        );
    }
    if ($sections['details'] !== '') {
        printf('<div class="lyli-pdp-section lyli-pdp-section-details">%s</div>', $sections['details']); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped -- built from the same filtered content
    }
}

/**
 * Splits already-filtered description HTML into 'before' / 'info' /
 * 'details' buckets by matching only the two proven-stable <h2> headings
 * (see render_recomposed_description_tab() docblock for the evidence and
 * design). Returns null if the content can't be parsed as a fragment at
 * all (caller falls back to printing it unmodified).
 */
function split_description_sections(string $html): ?array
{
    $html = trim($html);
    if ($html === '') {
        return ['before' => '', 'info' => '', 'details' => ''];
    }

    $recognized = [
        'info' => mb_strtolower(trim(__('Thông tin sản phẩm', 'shop-child'))),
    ];
    // "Lưu ý sản phẩm handmade" only needs to be recognized as a
    // section-boundary (it starts the "details" bucket along with
    // whatever precedes it there); its own text isn't separately
    // re-printed as a heading label since it already exists as a real
    // <h2> node within the original content.
    $details_boundary = mb_strtolower(trim(__('Lưu ý sản phẩm handmade', 'shop-child')));

    $doc = new \DOMDocument();
    libxml_use_internal_errors(true);
    $loaded = $doc->loadHTML(
        '<?xml encoding="utf-8"?><div id="lyli-root">' . $html . '</div>',
        LIBXML_NOERROR | LIBXML_NOWARNING
    );
    libxml_clear_errors();

    if (! $loaded) {
        return null;
    }

    // getElementById() is not reliably wired to the `id` attribute for a
    // parsed HTML fragment without a DTD declaring it as an ID type —
    // fall back to the one <div> we know we injected as the sole wrapper.
    $root = $doc->getElementById('lyli-root') ?: $doc->getElementsByTagName('div')->item(0);
    if (! $root) {
        return null;
    }

    $buckets = ['before' => '', 'info' => '', 'details' => ''];
    $current = 'before';

    foreach (iterator_to_array($root->childNodes) as $node) {
        if ($node->nodeType === XML_ELEMENT_NODE && strtolower($node->nodeName) === 'h2') {
            $text = mb_strtolower(trim($node->textContent));
            if ($text === $recognized['info']) {
                $current = 'info';
                continue; // heading label is re-printed by the caller, not the original node
            }
            if ($text === $details_boundary) {
                $current = 'details';
                // fall through: this heading itself belongs in "details"
                // (unlike "info", it has no separate caller-printed label)
            } elseif ($current === 'info') {
                // an unrecognized heading after "info" starts the
                // catch-all "details" bucket, per the frozen design
                $current = 'details';
            }
        }

        $buckets[$current] .= $doc->saveHTML($node);
    }

    return $buckets;
}
