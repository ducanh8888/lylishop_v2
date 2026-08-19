/**
 * Storefront V2 Batch B — sticky mobile add-to-cart. A proxy only: never a
 * second purchase form. Owns no variation/quantity/stock/price state of
 * its own — every value it shows and every click it performs reads from
 * or defers to the real WooCommerce form (form.cart), which stays the one
 * source of truth. If this script fails to run for any reason, nothing
 * breaks — the real purchase block was already fully functional without
 * it (same fail-safe philosophy as reveal.js/sticky-header.js).
 *
 * Only enqueued on single-product pages (inc/enqueue.php) and only ever
 * visible at <=782px (style.css) — the same "mobile" boundary this theme
 * already uses for the header's own sticky/relative switch
 * (assets/js/sticky-header.js), not a new one invented for this feature.
 *
 * Observed element: form.cart, not .summary. Live-measured on a real
 * variable-product PDP: .summary extends ~250px past the actual
 * form/button (it also contains product meta, brand info, and the
 * custom-order hint below the form) — watching .summary would delay the
 * sticky bar's appearance well past the point the real purchase action
 * actually left the viewport. form.cart is present with the same class
 * on both simple and variable products and always wraps the real
 * add-to-cart button, so it's the one correct, stable target for both.
 */
(function () {
    'use strict';

    var form = document.querySelector('form.cart');
    if (!form || !('IntersectionObserver' in window) || !('MutationObserver' in window)) {
        return;
    }

    var realButton = form.querySelector('.single_add_to_cart_button');
    if (!realButton) {
        return;
    }

    var titleNode = document.querySelector('.product_title');
    var priceNode = document.querySelector('.summary .price');

    var bar = document.createElement('div');
    bar.className = 'lyli-sticky-cta';
    bar.innerHTML =
        '<span class="lyli-sticky-cta-info">' +
            '<span class="lyli-sticky-cta-title"></span>' +
            '<span class="lyli-sticky-cta-price"></span>' +
        '</span>' +
        '<button type="button" class="lyli-sticky-cta-button"></button>';
    document.body.appendChild(bar);

    var titleEl = bar.querySelector('.lyli-sticky-cta-title');
    var priceEl = bar.querySelector('.lyli-sticky-cta-price');
    var buttonEl = bar.querySelector('.lyli-sticky-cta-button');

    function isRealButtonUnavailable() {
        return realButton.classList.contains('disabled') || realButton.classList.contains('wc-variation-selection-needed');
    }

    /**
     * Reflects the real DOM — never a cached/initial-load value. Called on
     * first render and on every MutationObserver tick below, so label,
     * price, and enabled/disabled state can never go stale after a
     * variation change, an AJAX add, or any other native WooCommerce DOM
     * update.
     */
    function syncFromRealDom() {
        if (titleNode) {
            titleEl.textContent = titleNode.textContent.trim();
        }
        if (priceNode) {
            priceEl.textContent = priceNode.textContent.trim();
        }

        var unavailable = isRealButtonUnavailable();
        buttonEl.textContent = realButton.textContent.trim();
        buttonEl.setAttribute('aria-label', realButton.textContent.trim());
        bar.classList.toggle('lyli-sticky-cta-unavailable', unavailable);
    }

    syncFromRealDom();

    var observerTargets = [realButton];
    if (priceNode) {
        observerTargets.push(priceNode);
    }
    var observer = new MutationObserver(syncFromRealDom);
    observerTargets.forEach(function (target) {
        observer.observe(target, { attributes: true, attributeFilter: ['class'], childList: true, characterData: true, subtree: true });
    });

    function prefersReducedMotion() {
        return window.matchMedia('(prefers-reduced-motion: reduce)').matches;
    }

    buttonEl.addEventListener('click', function () {
        if (isRealButtonUnavailable()) {
            // Not purchasable yet (no variation selected) — return the
            // shopper to the real control instead of faking an add-to-cart.
            // No add-to-cart attempt is made; WooCommerce's own validation
            // remains solely responsible for what "unavailable" means.
            var unresolved = Array.prototype.slice.call(form.querySelectorAll('.variations select'))
                .filter(function (select) { return select.value === ''; })[0];
            var scrollTarget = form.querySelector('.variations') || form;

            scrollTarget.scrollIntoView({
                behavior: prefersReducedMotion() ? 'auto' : 'smooth',
                block: 'center',
            });

            if (unresolved) {
                unresolved.focus();
            }
            return;
        }

        // Proxy: trigger the exact same button the shopper would have
        // tapped directly, so WooCommerce's own click handler (quantity
        // read from the real form, AJAX vs. redirect flow, validation)
        // runs exactly once, completely unmodified.
        realButton.click();
    });

    var formVisible = true;
    var footerVisible = false;
    var hasScrolled = false;
    var footer = document.querySelector('.site-footer') || document.querySelector('footer');

    // "Scrolled beyond the real purchase block" implies scrolling actually
    // happened — on a tall mobile gallery, form.cart can start below the
    // fold before the shopper has scrolled at all (live-verified: a
    // simple-product PDP at 390px had form.cart at y=1014 with scrollY
    // still 0 on load). Gating on an actual scroll event, not just
    // non-intersection, avoids showing the bar before the shopper has
    // seen anything.
    window.addEventListener('scroll', function () {
        hasScrolled = true;
    }, { passive: true, once: true });

    function updateBarVisibility() {
        bar.classList.toggle('lyli-sticky-cta-visible', hasScrolled && !formVisible && !footerVisible);
    }

    var formObserver = new IntersectionObserver(function (entries) {
        formVisible = entries[0].isIntersecting;
        updateBarVisibility();
    }, { threshold: 0 });
    formObserver.observe(form);

    if (footer) {
        var footerObserver = new IntersectionObserver(function (entries) {
            footerVisible = entries[0].isIntersecting;
            updateBarVisibility();
        }, { threshold: 0 });
        footerObserver.observe(footer);
    }
})();
