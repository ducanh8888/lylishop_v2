/**
 * Section-level scroll reveal. CSS-driven; this only toggles a class.
 * No-JS / reduced-motion / IntersectionObserver-missing all fall back to
 * the page's normal (fully visible) state — see the has-js-reveal gate in
 * style.css, which only hides content once this script confirms it can
 * reveal it again.
 */
(function () {
    'use strict';

    if (
        window.matchMedia('(prefers-reduced-motion: reduce)').matches ||
        !('IntersectionObserver' in window)
    ) {
        return;
    }

    var targets = document.querySelectorAll(
        '.lyli-category-card, .lyli-info-card, .lyli-story-visual, .lyli-final-cta'
    );
    if (!targets.length) {
        return;
    }

    document.documentElement.classList.add('has-js-reveal');

    var io = new IntersectionObserver(function (entries) {
        entries.forEach(function (entry) {
            if (entry.isIntersecting) {
                entry.target.classList.add('is-revealed');
                io.unobserve(entry.target);
            }
        });
    }, { threshold: 0.15 });

    targets.forEach(function (target) {
        io.observe(target);
    });
})();
