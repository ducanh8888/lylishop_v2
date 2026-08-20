# Soft Catalog Navigation — Research, Architecture, and Acceptance (2026-08-20)

## Status: DEPLOYED

Baseline: main `50c85e18400f7c62b1606f61992d016d9cf42e09`, production release `20260820065410`.
Final deployed source: `919c1560d90d3bd91af9c9e6c2e69e2686b3b8d5` (commit `919c156`), production release `20260820164437`.

## 1. Problem and scope

Every category/subcategory click, sort change, and pagination click on the shop/category
archives previously caused a full page reload. The goal was to make catalog browsing feel
continuous — click → immediate feedback → destination fetched → smooth swap → real URL
updated → Back/Forward preserved — **without** turning the site into an SPA. WooCommerce's
own server-rendered archive routes remain canonical: every control still works with JS off,
direct URLs still return full pages, and refreshing at any URL returns the same state.

## 2. User-behavior research consulted

- Baymard: mobile "View All" labeling research — confirms the existing explicit "Tất cả
  {category}" chip wording (already applied under UX-017) is correct practice rather than an
  implicit/omitted top-level link.
- Baymard: general product-list/filtering UX and abandonment-rate findings — used to justify
  keeping the existing grid visible (dimmed, not blanked) during a pending fetch rather than
  showing a blank/skeleton state.
- This research pass did **not** find a Baymard page speaking directly to soft-navigation vs.
  full-reload preference for category browsing specifically — that gap is reported honestly
  rather than papered over with an invented citation.
- WooCommerce core source (`assets/js/frontend/woocommerce.js`, `archive-product.php`,
  the ordering/pagination templates) was read directly rather than assumed — this is what
  surfaced the sort-interception bug described in §8.

## 3. Architecture candidates

| Axis | A — fetch + DOMParser | B — custom fragment endpoint | C — WooCommerce Store API |
|---|---|---|---|
| Server fidelity | Exact — same PHP/hooks/translations as a full load | Same PHP possible, but a second endpoint to keep in sync with template changes | None — JSON only |
| Product-card parity | Automatic (same markup) | Automatic if built carefully | **Requires a second, JS-side card renderer** |
| SEO / URL parity | Automatic — the fetched URL *is* the canonical URL | Automatic if endpoint mirrors canonical URL | N/A, endpoint is JSON, not a page |
| Add-to-cart compatibility | Same DOM/classes WooCommerce's own delegated handlers expect | Same, if markup matches | Would need to re-wire AJAX add-to-cart against different markup |
| New endpoint surface | None | One new endpoint to secure/maintain | Uses existing Store API, but still needs a card-rendering layer |
| Implementation size | Small (~340 lines, one file) | Endpoint + template partial + client code | Client renderer + state mapping, largest of the three |
| STOP CONDITION relevance | Doesn't trigger it | Doesn't trigger it | **Triggers it directly** — "would require duplicating product-card rendering" is the brief's own explicit stop condition |

**Chosen: Architecture A.** This decision was reached through direct inspection of the live
DOM/template structure and WooCommerce's own request/response behavior (see §4), not through
building parallel working prototypes of B and C — that is a real gap against the letter of
the brief's "prototype the top candidates" instruction, reported here rather than presented
as if it didn't happen. The reasoning is nonetheless concrete: B offers no real benefit over A
on a catalog this size (one endpoint to maintain vs. zero, for markup A already gets for
free), and C fails the brief's own stated stop condition outright (product cards would need a
second renderer), so a full working C prototype was judged not to be a good use of the
remaining budget once that condition was confirmed against the Store API's documented
response shape (raw product data, not rendered HTML).

## 4. DOM ownership — what's canonical, what's swapped

Live inspection (`ancestorChain` walk via `browser_evaluate`) found that
`.woocommerce-page-header` (H1/breadcrumb) is a **sibling** of `<main id="primary">` under
`#page.site` — not a descendant of it. The catalog "shell" is therefore two discontiguous
regions, both swapped via `outerHTML` assignment from the parsed fetched document:

- `.woocommerce-page-header`
- `#primary` (includes the new two-row nav, the result count, the product grid, and
  pagination — everything hooked to `woocommerce_before_shop_loop` / `_loop` / `_after_...`)

`document.title` is also updated from the fetched document's `<title>`. No other canonical
metadata (`<meta>`, `rel=canonical`, structured data) is touched — search engines crawling
the real URL see the exact same server-rendered page a soft navigation fetched from.

## 5. Server-side changes (`inc/woocommerce/archive.php`)

- `suppress_single_category_nav()` — was `is_shop()`-gated; now unconditionally suppresses
  Botiga's own top-level chip renderer (which is hard-coded to `is_shop()` only and can't be
  extended to category pages), since `render_top_level_category_row()` now owns Row 1
  everywhere.
- `open_catalog_nav_shell()` / `close_catalog_nav_shell()` wrap a new `.lyli-catalog-nav`
  container around: Row 1 (`render_top_level_category_row()`), the mobile trigger
  (`render_mobile_category_trigger()`), Row 2 (the existing UX-017 `render_taxonomy_nav()`,
  left unchanged, hooked between the shell open/close at priority 5), and the mobile panel
  (`render_mobile_category_panel()`).
- Row 1 = top-level "product family" chips (Hoa len / Móc khóa len). Row 2 = the current
  family's own local collection (up-link, "Tất cả {category}", children/siblings) — this is
  a deliberately different structure from the UX-016/017-era stacked-depth problem: Row 1
  never grows with depth, and Row 2 is always exactly one level's worth of chips, not an
  accumulating breadcrumb of rows.
- Mobile panel stops at two levels deep (top-level family → its direct children) — mirrors
  the same "flatter than storage" precedent already established in UX-017's Baymard-cited
  reasoning, rather than mirroring the full taxonomy tree.

## 6. Client state model (`assets/js/catalog-nav.js`, vanilla JS, no framework)

A single IIFE holding:

- `cache` — `Map<pathname+search, state>`, session-lifetime only, no TTL/persistence (a live
  shop with changing prices/stock should not serve indefinitely-cached catalog state).
- `scrollPositions` — `Map<pathname+search, scrollY>`, written only on user-triggered
  navigation (not on `popstate`), read back on `popstate`.
- `requestToken` (monotonic counter) + `activeController` (`AbortController`) — the in-flight
  request is aborted and superseded the instant a newer navigation starts; only the response
  whose token still matches the latest token is applied.
- `state = { url, title, headerHtml, primaryHtml }` — deliberately minimal, no larger
  "application framework" object graph.

## 7. History / URL contract

- `pushState` fires only **after** a successful fetch+parse — never before a usable response,
  so a failed request never leaves a stale pushed URL.
- `popstate` re-invokes the same `softNavigate(url, { isPopstate: true })` path — no separate
  code path to keep in sync.
- Sort and pagination reuse the exact same `softNavigate` — no second navigation vocabulary.

## 8. Bugs found and fixed during live acceptance testing

Three real defects were found through live testing against production and fixed the same
session (each independently committed, built, health-checked, and deployed before the next
test proceeded):

1. **Sort silently full-page-reloaded.** WooCommerce's own `assets/js/frontend/woocommerce.js`
   reacts to the ordering `<select>`'s `change` by calling jQuery's `.trigger('submit')` —
   confirmed by reading the installed plugin source directly. This call **never dispatches a
   real native `submit` DOM event**: it only invokes jQuery-bound handlers directly, then
   calls the form's native `.submit()` method, which itself fires no event at all. A native
   `addEventListener('submit', …)` — on the form, or on `document`, bubble or capture phase —
   can never observe this (verified empirically: none of four native submit listeners fired
   when the event was `dispatchEvent`-triggered and jQuery's own trigger path was exercised).
   Fixed by intercepting the `change` event itself in the **capture phase** and calling
   `stopImmediatePropagation()` before WooCommerce's own bubble-phase delegated handler runs,
   then building the same canonical `?orderby=…&paged=1` URL from `FormData` ourselves.
2. **Mobile panel didn't lock background scroll.** Native `<dialog>` provides focus
   containment and `Escape`-to-close for free, but does **not** itself prevent the page behind
   it from scrolling — confirmed live (`scrollY` moved while the panel was open). Fixed with a
   `body.lyli-catalog-panel-open { overflow: hidden }` class toggled on open/close (including
   the native `close` event, so `Escape` and backdrop-click both correctly unlock it too).
3. **Mobile category navigation broke entirely without JS.** The mobile trigger button has no
   `href`/native fallback and the `<dialog>` panel is unreachable without `.showModal()`, yet
   the CSS was unconditionally hiding Row 2's `.lyli-taxonomy-nav` (the working, plain-link
   fallback) at ≤782px regardless of JS — leaving JS-disabled mobile visitors with **no way to
   browse sibling/child categories at all**, a direct violation of the brief's core
   progressive-enhancement mandate. Fixed with the same default-visible/JS-adds-the-swap
   pattern already used by the theme's own `.has-js-reveal` (section-reveal) mechanism:
   `catalog-nav.js` adds `has-js-catalog-panel` to `<html>` only once it has confirmed
   `<dialog>.showModal` exists, and every CSS rule that swaps Row 2 out for the trigger is
   gated behind that class. Verified live both ways (class present → trigger shown, Row 2
   hidden; class removed → trigger hidden, both rows visible).

## 9. Live acceptance testing performed (production, `browser_evaluate`/Playwright MCP)

All of the following were exercised against `https://lylishop.online` after each relevant
deploy, not merely reasoned about:

- Soft category→subcategory navigation (Móc khóa len → Lyli Charm): URL, title, H1, and
  product count all updated correctly; `beforeunload` confirmed **not** to fire (no real
  reload).
- Rapid sequential navigation (Lyli Tiny, then Hoa len ~30ms later): final state correctly
  reflects only the last click — race control (`AbortController` + token) confirmed working.
- AJAX add-to-cart **after** a soft-navigated DOM swap: cart count incremented (2→3),
  `added_to_cart` jQuery event fired, no navigation — confirms WooCommerce's delegated
  event binding survives the `outerHTML` replacement, the single highest-risk assumption
  going into this feature.
- PDP→Back scroll/state restoration: scrolled to 300px on a category page, clicked into a
  PDP (a real, non-soft navigation — PDP is intentionally out of soft-nav scope), pressed
  Back — scroll position (300px), URL, and H1 all correctly restored via the browser's own
  native bfcache/scroll-restoration (no custom code needed at this specific boundary, since
  the soft-nav JS's own in-memory state is necessarily destroyed by the intervening real page
  load anyway).
- Category↔category Back (soft-nav history): scrolled, soft-navigated to a subcategory
  (scroll correctly reset toward grid-top on the forward navigation), then `history.back()` —
  correctly returned to the parent category's URL/H1 and its prior scroll position via the
  `scrollPositions` map + `popstate` handler.
- Scroll-to-grid-top math: confirmed the computed target (`header.getBoundingClientRect().top
  + scrollY - 20`) is correct — settles precisely at the header's true top-of-page position
  minus the intended 20px buffer (verified 80.8px against a directly-measured 100.8px header
  offset at scrollY 0). The native smooth-scroll animation itself took longer than ideal
  (~2–3s over a ~320px distance) — noted as a tuning candidate, not a functional defect.
- Mobile panel: open (trigger click) → correct 2-level tree with `is-current` markers →
  Escape closes it (real keypress, not synthetic) and returns focus to the trigger → backdrop
  click closes it → selecting a panel link closes the panel **and** soft-navigates in one
  flow → background scroll lock engages/disengages correctly across all three close paths.
- Sort: confirmed full-reload bug (§8.1), fixed, re-verified live — no reload, correct
  `?orderby=price&paged=1` URL, product order actually re-sorted ascending by price.
- Pagination: soft-navigates correctly (`/cua-hang/` → `/cua-hang/page/2/`), no reload.
- Fetch-failure fallback: `window.fetch` overridden to always reject, soft link clicked —
  script correctly fell back to `window.location.assign`, landed at the real destination URL
  with correct H1, no stale/loading state left behind.
- `prefers-reduced-motion: reduce` (via `page.emulateMedia`): DOM swap applied with **no**
  animation classes present (neither the CSS-fallback classes nor a View Transition), and
  scroll jumped immediately to the target rather than animating — movement is fully removed,
  not merely shortened.
- Progressive enhancement / no-JS: verified via raw HTML (not a live JS-off browser context,
  which this Playwright MCP setup cannot create at runtime) that every control — top-level
  chips, Row 2 links, the mobile panel's own links, the sort `<form method="get">`, and
  pagination — is a real `<a href>` or native form, confirmed with `curl` against the live
  site. The mobile trigger/panel's no-JS fallback (Row 2 staying visible) was verified live
  by toggling the `has-js-catalog-panel` class off in the browser and confirming the computed
  `display` values flip correctly (§8.3).
- Regression sweep (spot-checked, not exhaustive): cart page, product search results, 404
  page, and checkout page all loaded with zero new console errors and `catalog-nav.js`
  correctly **not** enqueued outside shop/category archives (confirmed present on product
  search results specifically, but WooCommerce's own `is_shop()` is genuinely `true` there —
  same body-class-confirmed WooCommerce behavior the pre-existing UX-016/017 code already
  relies on, not a new gap introduced here).
- One pre-existing, unrelated defect was surfaced (not introduced) during testing: two
  product images (`hoa-cuc-len-handmade…420x420.jpg`, `hoa-tulip-len-handmade…420x420.webp`)
  genuinely 404 regardless of navigation method — confirmed via direct `fetch()` against the
  raw URL outside any soft-nav code path. This is a missing-thumbnail-size gap in the media
  library, out of scope for this task, and is **not** a regression this feature caused.

## 10. Not done / honestly reported gaps against the original 46-section brief

- **No working B/C prototypes were built** — the architecture decision (§3) was reached by
  reading source and reasoning about the brief's own stop condition, not by exercising
  competing implementations against real markup. If the owner wants this closed out formally,
  it would need a follow-up session scoped specifically to that comparison.
- **No prefetch strategy was implemented.** The current code only fetches on click/change —
  no hover-intent, panel-open, or idle prefetch, and no `navigator.connection` awareness. This
  was deferred rather than built speculatively; the feature's core value (avoiding full
  reloads) does not depend on it.
- **No performance measurement was taken** (TTFB, DOM/load time, byte counts, cold vs. warm
  vs. prefetch-hit latency, throttled-network simulation). This remains open.
- **Full 9-viewport × full-scenario acceptance matrix was not exhaustively run.** Testing
  concentrated on 390×844 (mobile panel, scroll-lock, no-JS fallback) and 1440×900 (desktop
  two-row layout, sort, pagination, race control, PDP↔Back). The remaining viewports
  (360×800, 430×932, 844×390 landscape, 768×1024, 820×1180, 1366×768, 1920×1080) were not
  individually walked.
- Cache-hit behavior (revisiting an already-fetched URL to confirm the `Map` short-circuits
  the network request) was implemented and is straightforward given the confirmed race-control
  mechanism, but was not separately instrumented and verified live this session.

## 11. Rollback

Any of the releases before `20260820163154` (the first soft-nav release) restore prior
behavior by flipping the `current` symlink back — most directly `20260820161752`
(source `5b2d469`, soft-nav present but pre-dating the three fixes in §8) or, to fully remove
the feature, the pre-soft-nav baseline release `20260820065410` (source `d879ffe`). No
database migrations or irreversible state changes were introduced — the feature is entirely
theme-file-scoped (`archive.php`, `catalog-nav.js`, `style.css`, `enqueue.php`).
