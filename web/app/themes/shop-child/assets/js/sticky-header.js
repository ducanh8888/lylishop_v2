/**
 * Sticky header hide-on-scroll-down / reveal-on-scroll-up. Runs on every
 * viewport but only has a visible effect at >=783px, where style.css makes
 * the header position: sticky (below that it is position: relative and the
 * transform this toggles has nothing to detach from).
 */
(function () {
    'use strict';

    var header = document.querySelector('.bhfb-header');
    if (!header) {
        return;
    }

    var REVEAL_NEAR_TOP = 80;
    var lastScrollY = window.scrollY;
    var ticking = false;
    var focusInsideHeader = false;

    header.addEventListener('focusin', function () {
        focusInsideHeader = true;
        header.classList.remove('lyli-header-hidden');
    });
    header.addEventListener('focusout', function () {
        focusInsideHeader = false;
    });

    function update() {
        ticking = false;
        var currentScrollY = window.scrollY;

        if (focusInsideHeader || currentScrollY <= REVEAL_NEAR_TOP) {
            header.classList.remove('lyli-header-hidden');
        } else if (currentScrollY > lastScrollY) {
            header.classList.add('lyli-header-hidden');
        } else if (currentScrollY < lastScrollY) {
            header.classList.remove('lyli-header-hidden');
        }

        lastScrollY = currentScrollY;
    }

    window.addEventListener('scroll', function () {
        if (!ticking) {
            ticking = true;
            window.requestAnimationFrame(update);
        }
    }, { passive: true });
})();
