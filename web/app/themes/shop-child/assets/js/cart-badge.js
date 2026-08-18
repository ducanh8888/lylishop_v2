/**
 * Cart badge micro-animation + inline add-to-cart confirmation. Both only
 * fire on WooCommerce's own `added_to_cart` event (already dispatched by
 * add-to-cart.min.js after a successful AJAX add) — never on ordinary page
 * render, and never for a redirect/non-AJAX add-to-cart submit, since that
 * flow doesn't dispatch this event at all.
 */
(function ($) {
    'use strict';

    if (!$) {
        return;
    }

    var CONFIRM_MS = 1800;
    var confirmTimers = new WeakMap();

    $(document.body).on('added_to_cart', function () {
        var badge = document.querySelector('.cart-count');
        if (!badge) {
            return;
        }
        badge.classList.remove('lyli-cart-pulse');
        void badge.offsetWidth; // restart the animation if it fires again quickly
        badge.classList.add('lyli-cart-pulse');
    });

    $(document.body).on('added_to_cart', function (event, fragments, cartHash, $button) {
        var button = $button && $button.get ? $button.get(0) : null;
        if (!button) {
            return;
        }

        var existingTimer = confirmTimers.get(button);
        if (existingTimer) {
            clearTimeout(existingTimer);
        }

        if (button.dataset.lyliOriginalLabel === undefined) {
            button.dataset.lyliOriginalLabel = button.getAttribute('aria-label') || '';
        }

        button.classList.remove('lyli-added-confirm');
        void button.offsetWidth; // restart the fade if the same button fires again quickly
        button.classList.add('lyli-added-confirm');
        button.setAttribute('aria-label', 'Đã thêm vào giỏ hàng');

        confirmTimers.set(button, window.setTimeout(function () {
            button.classList.remove('lyli-added-confirm');
            var original = button.dataset.lyliOriginalLabel;
            if (original) {
                button.setAttribute('aria-label', original);
            } else {
                button.removeAttribute('aria-label');
            }
            confirmTimers.delete(button);
        }, CONFIRM_MS));
    });
})(window.jQuery);
