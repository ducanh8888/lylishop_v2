<?php
/**
 * Lyli Shop — Cart / Checkout / My Account presentation.
 * Split out per the same file-ownership convention as inc/woocommerce/
 * {archive,product-card,single-product}.php (docs/STOREFRONT-V2-
 * IMPLEMENTATION.md §16) — a distinct concern (commerce-flow presentation
 * + scoped translation fixes) from archive/card/PDP. Presentation and
 * narrow scoped-string-translation only — no order, payment, shipping or
 * business logic.
 */

namespace ShopChild\Woo;

if (! defined('ABSPATH')) {
    exit;
}

/**
 * Storefront V2 Batch C — fix the untranslated "Shipment" cart-totals /
 * checkout order-review row label.
 *
 * Root cause, verified live from WooCommerce core source
 * (includes/class-wc-cart.php): `_x('Shipment', 'shipping packages',
 * 'woocommerce')` — a real core string with a context, missing from the
 * active Vietnamese language-pack coverage for that specific context
 * (docs/WOOCOMMERCE-VIETNAMESE-2026-08-08.md already documents this class
 * of gap and explicitly advises against editing installed .mo files
 * directly, since a language-pack update would silently revert that
 * edit). Uses `gettext_with_context`, not `gettext`, because the source
 * calls `_x()`, which routes through the context-aware filter.
 *
 * Scoped simultaneously on domain + exact source string + exact context —
 * cannot match or alter any other string, in WooCommerce or any other
 * textdomain. Confirmed live this fires identically on cart-page render,
 * checkout-page render, and checkout/cart AJAX fragment re-renders (the
 * fragments are re-rendered server-side through the same translation
 * pipeline, so no separate AJAX-specific handling is needed).
 */
add_filter('gettext_with_context', __NAMESPACE__ . '\\translate_shipment_label', 10, 4);
function translate_shipment_label(string $translated, string $text, string $context, string $domain): string
{
    if ($domain === 'woocommerce' && $text === 'Shipment' && $context === 'shipping packages') {
        return __('Vận chuyển', 'shop-child');
    }

    return $translated;
}
