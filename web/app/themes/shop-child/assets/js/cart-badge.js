/**
 * Cart badge micro-animation. Only fires on WooCommerce's own
 * `added_to_cart` event (already dispatched by add-to-cart.min.js after a
 * successful AJAX add) — never on ordinary page render, so the badge only
 * pulses when the cart count actually changes.
 */
(function ($) {
    'use strict';

    if (!$) {
        return;
    }

    $(document.body).on('added_to_cart', function () {
        var badge = document.querySelector('.cart-count');
        if (!badge) {
            return;
        }
        badge.classList.remove('lyli-cart-pulse');
        void badge.offsetWidth; // restart the animation if it fires again quickly
        badge.classList.add('lyli-cart-pulse');
    });
})(window.jQuery);
