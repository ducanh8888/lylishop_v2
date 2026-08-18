/**
 * Sticky header hide-on-scroll-down / reveal-on-scroll-up. Runs on every
 * viewport but only has a visible effect at >=783px, where style.css makes
 * the header position: sticky (below that it is position: relative and the
 * transform this toggles has nothing to detach from).
 *
 * Botiga renders two separate header elements (.bhfb-desktop and
 * .bhfb-mobile) and toggles which one is display:none via its own
 * breakpoint (~1024px) — not the same breakpoint this theme uses for
 * sticky/relative. Only one is ever visible at a time, so every tick finds
 * the currently-visible one instead of caching a single element reference.
 */
(function () {
    'use strict';

    var headers = document.querySelectorAll('.bhfb-header');
    if (!headers.length) {
        return;
    }

    var REVEAL_NEAR_TOP = 80;
    var lastScrollY = window.scrollY;
    var ticking = false;
    var focusInsideHeader = false;

    function getVisibleHeader() {
        for (var i = 0; i < headers.length; i++) {
            if (headers[i].offsetParent !== null) {
                return headers[i];
            }
        }
        return null;
    }

    document.addEventListener('focusin', function (event) {
        if (event.target.closest('.bhfb-header')) {
            focusInsideHeader = true;
            var visible = getVisibleHeader();
            if (visible) {
                visible.classList.remove('lyli-header-hidden');
            }
        }
    });
    document.addEventListener('focusout', function (event) {
        if (event.target.closest('.bhfb-header')) {
            focusInsideHeader = false;
        }
    });

    function update() {
        ticking = false;
        var currentScrollY = window.scrollY;
        var visible = getVisibleHeader();

        headers.forEach(function (header) {
            if (header !== visible) {
                header.classList.remove('lyli-header-hidden');
            }
        });

        if (visible) {
            if (focusInsideHeader || currentScrollY <= REVEAL_NEAR_TOP) {
                visible.classList.remove('lyli-header-hidden');
            } else if (currentScrollY > lastScrollY) {
                visible.classList.add('lyli-header-hidden');
            } else if (currentScrollY < lastScrollY) {
                visible.classList.remove('lyli-header-hidden');
            }
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
