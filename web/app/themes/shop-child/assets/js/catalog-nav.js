/**
 * Soft catalog navigation — progressive enhancement over WooCommerce's own
 * server-rendered shop/category archive routes
 * (docs/SOFT-CATALOG-NAVIGATION-RESEARCH-2026-08-20.md).
 *
 * Architecture: fetch the REAL destination URL (the exact same page a
 * full navigation or a search engine would get), parse it with
 * DOMParser, and swap two stable, already-existing regions
 * (`.woocommerce-page-header` and `#primary`) from the parsed response
 * into the current document. Every hook, translation, and product-card
 * markup choice this produces is identical to a full page load, because
 * it IS the same server-rendered HTML — there is no second, JS-side
 * catalog renderer to keep in sync with PHP.
 *
 * If anything is missing, unsupported, or fails, this script falls back
 * to a normal browser navigation. Every link and control it touches is a
 * real `<a href>` or native `<select>`/pagination link that already works
 * without JavaScript — this file only intercepts the click/change and
 * decides whether to handle it itself or let it proceed as a real
 * navigation.
 */
(function () {
    'use strict';

    var primary = document.querySelector('#primary');
    if (
        !primary ||
        typeof window.fetch !== 'function' ||
        typeof window.DOMParser !== 'function' ||
        !window.history ||
        typeof window.history.pushState !== 'function'
    ) {
        return; // no soft navigation — every link/control still works natively
    }

    var PRIMARY_SELECTOR = '#primary';
    var HEADER_SELECTOR = '.woocommerce-page-header';
    var cache = new Map();
    var scrollPositions = new Map();
    var requestToken = 0;
    var activeController = null;
    var statusRegion = null;

    function normalizeUrl(url) {
        var u = new URL(url, window.location.href);
        return u.pathname + u.search;
    }

    function isSoftNavigableUrl(url) {
        try {
            var u = new URL(url, window.location.href);
            return u.origin === window.location.origin;
        } catch (e) {
            return false;
        }
    }

    function ensureStatusRegion() {
        if (statusRegion) {
            return statusRegion;
        }
        statusRegion = document.createElement('div');
        statusRegion.className = 'lyli-catalog-status';
        statusRegion.setAttribute('aria-live', 'polite');
        statusRegion.setAttribute('role', 'status');
        document.body.appendChild(statusRegion);
        return statusRegion;
    }

    function announce(text) {
        var region = ensureStatusRegion();
        region.textContent = '';
        // Force a reflow so a repeated identical string still announces.
        void region.offsetWidth;
        region.textContent = text;
    }

    function prefersReducedMotion() {
        return window.matchMedia('(prefers-reduced-motion: reduce)').matches;
    }

    function getResultCountText(root) {
        var el = root.querySelector('.woocommerce-result-count');
        return el ? el.textContent.trim() : '';
    }

    /**
     * Extract the two swappable regions plus <title> from a fetched,
     * parsed document. Returns null if the response doesn't look like a
     * real archive page (missing #primary) — callers fall back to a
     * normal navigation rather than swapping in something wrong.
     */
    function extractState(doc, url) {
        var newPrimary = doc.querySelector(PRIMARY_SELECTOR);
        if (!newPrimary) {
            return null;
        }
        var newHeader = doc.querySelector(HEADER_SELECTOR);
        var titleEl = doc.querySelector('title');
        return {
            url: url,
            title: titleEl ? titleEl.textContent : document.title,
            headerHtml: newHeader ? newHeader.outerHTML : null,
            primaryHtml: newPrimary.outerHTML,
        };
    }

    function applyState(state, options) {
        options = options || {};

        var header = document.querySelector(HEADER_SELECTOR);
        if (state.headerHtml && header) {
            header.outerHTML = state.headerHtml;
        }

        var currentPrimary = document.querySelector(PRIMARY_SELECTOR);
        if (currentPrimary) {
            currentPrimary.outerHTML = state.primaryHtml;
        }

        document.title = state.title;

        var newPrimary = document.querySelector(PRIMARY_SELECTOR);
        if (newPrimary) {
            newPrimary.removeAttribute('aria-busy');
        }

        if (!options.skipScroll) {
            scrollToCatalogTop();
        } else if (typeof options.restoreScrollY === 'number') {
            window.scrollTo(0, options.restoreScrollY);
        }

        var countText = newPrimary ? getResultCountText(newPrimary) : '';
        if (countText) {
            announce(countText);
        }
    }

    function scrollToCatalogTop() {
        var target = document.querySelector(HEADER_SELECTOR) || document.querySelector(PRIMARY_SELECTOR);
        if (!target) {
            return;
        }
        var top = target.getBoundingClientRect().top + window.scrollY - 20;
        window.scrollTo({ top: Math.max(top, 0), behavior: prefersReducedMotion() ? 'auto' : 'smooth' });
    }

    function swapWithTransition(state, options) {
        if (document.startViewTransition && !prefersReducedMotion()) {
            document.startViewTransition(function () {
                applyState(state, options);
            });
            return;
        }

        // CSS-fallback path for browsers without the View Transition API.
        var currentPrimary = document.querySelector(PRIMARY_SELECTOR);
        if (currentPrimary && !prefersReducedMotion()) {
            currentPrimary.classList.add('lyli-catalog-swap-out');
            window.setTimeout(function () {
                applyState(state, options);
                var freshPrimary = document.querySelector(PRIMARY_SELECTOR);
                if (freshPrimary) {
                    freshPrimary.classList.add('lyli-catalog-swap-in');
                    window.setTimeout(function () {
                        freshPrimary.classList.remove('lyli-catalog-swap-in');
                    }, 260);
                }
            }, 160);
        } else {
            applyState(state, options);
        }
    }

    function fetchState(url) {
        var key = normalizeUrl(url);
        if (cache.has(key)) {
            return Promise.resolve(cache.get(key));
        }

        if (activeController) {
            activeController.abort();
        }
        var controller = new AbortController();
        activeController = controller;
        var myToken = ++requestToken;

        return fetch(url, { credentials: 'same-origin', signal: controller.signal })
            .then(function (response) {
                if (!response.ok) {
                    throw new Error('bad status ' + response.status);
                }
                return response.text();
            })
            .then(function (html) {
                var doc = new DOMParser().parseFromString(html, 'text/html');
                var state = extractState(doc, url);
                if (!state) {
                    throw new Error('no #primary in response');
                }
                if (myToken === requestToken) {
                    cache.set(key, state);
                }
                return { state: state, token: myToken };
            });
    }

    function softNavigate(url, options) {
        options = options || {};
        var target = document.querySelector(PRIMARY_SELECTOR);
        if (target) {
            target.setAttribute('aria-busy', 'true');
        }

        if (!options.isPopstate) {
            scrollPositions.set(normalizeUrl(window.location.href), window.scrollY);
        }

        fetchState(url)
            .then(function (result) {
                if (result.token !== requestToken && !options.isPopstate) {
                    return; // a newer navigation already won; drop this one
                }
                if (!options.isPopstate) {
                    window.history.pushState({ lyliCatalogNav: true }, '', url);
                }
                swapWithTransition(result.state, {
                    skipScroll: !!options.isPopstate,
                    restoreScrollY: options.isPopstate ? scrollPositions.get(normalizeUrl(url)) : undefined,
                });
            })
            .catch(function (err) {
                if (err && err.name === 'AbortError') {
                    return; // superseded by a newer click — not a real failure
                }
                // Fail safe: a real navigation always still works.
                window.location.assign(url);
            });
    }

    function closestSoftLink(el) {
        var a = el.closest(
            '.lyli-catalog-row-family a, .lyli-taxonomy-nav a, .lyli-catalog-panel-link, .woocommerce-pagination a'
        );
        if (!a || !a.href) {
            return null;
        }
        if (!isSoftNavigableUrl(a.href)) {
            return null;
        }
        return a;
    }

    document.addEventListener('click', function (event) {
        if (event.defaultPrevented || event.button !== 0) {
            return;
        }
        if (event.metaKey || event.ctrlKey || event.shiftKey || event.altKey) {
            return; // respect "open in new tab"/etc.
        }
        var link = closestSoftLink(event.target);
        if (!link) {
            return;
        }
        event.preventDefault();
        closeCategoryPanel();
        softNavigate(link.href);
    });

    document.addEventListener('change', function (event) {
        var select = event.target.closest('.woocommerce-ordering select');
        if (!select) {
            return;
        }
        var form = select.closest('form');
        if (!form) {
            return;
        }
        // The select's own form action + field values already encode the
        // canonical Woo sort URL — build it the same way a real submit
        // would, rather than inventing a second sort vocabulary.
        var params = new URLSearchParams(new FormData(form));
        var url = window.location.pathname + '?' + params.toString();
        event.preventDefault();
        softNavigate(url);
    });

    window.addEventListener('popstate', function () {
        if (!isSoftNavigableUrl(window.location.href)) {
            return;
        }
        softNavigate(window.location.href, { isPopstate: true });
    });

    // --- Mobile category panel -------------------------------------------

    var panel = document.getElementById('lyli-catalog-panel');
    var trigger = document.querySelector('[data-lyli-catalog-trigger]');

    function openCategoryPanel() {
        if (!panel || typeof panel.showModal !== 'function') {
            return;
        }
        panel.showModal();
    }

    function closeCategoryPanel() {
        if (panel && panel.open) {
            panel.close();
        }
    }

    if (panel && trigger && typeof panel.showModal === 'function') {
        trigger.addEventListener('click', openCategoryPanel);

        panel.addEventListener('click', function (event) {
            var closeBtn = event.target.closest('[data-lyli-catalog-close]');
            if (closeBtn) {
                closeCategoryPanel();
                return;
            }
            // Native <dialog> closes on a backdrop click only if the click
            // target is the dialog element itself (not its content).
            if (event.target === panel) {
                closeCategoryPanel();
            }
        });

        panel.addEventListener('close', function () {
            trigger.focus();
        });
    } else if (trigger) {
        // No <dialog> support: leave the trigger visually present but
        // inert rather than partially working — the shop-root/category
        // link it summarizes is still reachable via the desktop rows at
        // wider widths and via a normal page load either way.
        trigger.setAttribute('aria-disabled', 'true');
    }
})();
