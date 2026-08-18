/**
 * Cart badge micro-animation + inline add-to-cart confirmation. Both only
 * fire on WooCommerce's own `added_to_cart` event (already dispatched by
 * add-to-cart.min.js after a successful AJAX add) — never on ordinary page
 * render, and never for a redirect/non-AJAX add-to-cart submit, since that
 * flow doesn't dispatch this event at all. The confirmation is anchored to
 * the product card (li.product), not the button — see the comment above
 * the `added_to_cart` handler below for why.
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
        var card = button ? button.closest('li.product') : null;
        if (!card) {
            return;
        }

        // Anchored to li.product rather than the button itself: WooCommerce
        // rewrites the button's own class list into a remove-from-cart
        // control right after a successful add, and that rewrite owns the
        // button's accessible label from that point on — this only adds a
        // supplementary visual confirmation on the card, it never touches
        // the button's own attributes.
        var existingTimer = confirmTimers.get(card);
        if (existingTimer) {
            clearTimeout(existingTimer);
        }

        card.classList.remove('lyli-added-confirm');
        void card.offsetWidth; // restart the fade if the same card fires again quickly
        card.classList.add('lyli-added-confirm');

        confirmTimers.set(card, window.setTimeout(function () {
            card.classList.remove('lyli-added-confirm');
            confirmTimers.delete(card);
        }, CONFIRM_MS));
    });
})(window.jQuery);
