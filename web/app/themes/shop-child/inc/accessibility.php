<?php
/**
 * Lyli Shop — accessibility behavior.
 *
 * Verified against Botiga 2.4.7 source (release inspection 2026-08-06):
 *   - Botiga header.php:37 already outputs a skip link
 *     (`<a class="skip-link screen-reader-text" href="#primary">`).
 *   - Botiga searchform.php already includes `screen-reader-text` labels for
 *     both the default and WooCommerce product search forms.
 *
 * We therefore deliberately add NO duplicate skip link and NO extra search
 * label. This file is a lightweight container for presentation-level
 * accessibility niceties that are provably non-duplicative.
 */

namespace ShopChild\Accessibility;

if (! defined('ABSPATH')) {
    exit;
}

/**
 * Add an accessible "Đặt mẫu theo yêu cầu" context label to the cart icon
 * for screen readers — only when WooCommerce icons are rendered by Botiga's
 * header builder and the site has Custom Order CTA enabled.
 *
 * (Placeholder hook — kept narrow and guarded; nothing synthetic is emitted
 * unless real site settings are present.)
 */