# Storefront Composition V2 — Implementation Contract

Status: **FROZEN**. This document is the canonical source of truth for Storefront V2 implementation. It supersedes any composition decision that exists only in chat history or a commit message. Implementation work (Batch A/B/C) must follow this contract exactly; if implementation discovers a need to exceed the intervention ceiling set here, **stop and get founder review** rather than self-authorizing a higher layer.

## 0. State verification (checked live, not from memory)

| Check | Result |
|---|---|
| `git log -1 main` | `f7fb41b42fe589c64700ab2c2220d82920417e81` |
| `git log -1 origin/main` | `f7fb41b42fe589c64700ab2c2220d82920417e81` (matches) |
| Production `apps/lylishop/current` → | release `20260818150511` |
| `SOURCE_COMMIT` in that release | `f7fb41b42fe589c64700ab2c2220d82920417e81` (matches HEAD) |
| Botiga (parent theme) | 2.4.7, free — `Botiga_Pro` class confirmed absent |
| WooCommerce | 10.9.4 |
| WordPress core | 7.0.2 |
| shop-child | 1.4.0 |
| `shop-child/` file structure | `functions.php`, `theme.json`, `style.css`, `editor-style.css`, `inc/{accessibility,announcement,block-patterns,botiga-admin,design-tokens,enqueue,footer,mobile-header,theme-runtime,woocommerce}.php`, `assets/js/{cart-badge,reveal,sticky-header}.js` |

**No divergence from the expected preflight baseline.** This document treats the above as the starting point for Batch A.

`inc/woocommerce.php` today is 69 lines, two hook registrations (`post_class` filter for image-count classes, one `woocommerce_single_product_summary` callback for the custom-order hint). Small enough to extend for Batch A without splitting yet — see §18.

Motion tokens confirmed present in `style.css` `:root`: `--lyli-motion-fast` (120ms), `--lyli-motion-base` (200ms), `--lyli-motion-slow` (320ms), `--lyli-motion-section` (600ms), `--lyli-ease-standard`, `--lyli-ease-out`. Existing motion features confirmed shipped: hero entrance, `reveal.js` (category/info/story/CTA/blog/latest-posts), category-card hover, blog-card hover, cart-badge pulse, inline add-to-cart confirmation, `sticky-header.js`, CTA arrow-nudge, and the two-layer `prefers-reduced-motion` policy. All of Storefront V2 reuses this system — see §16.

---

## 1. Purpose

```
Current:
  commerce pages (shop → product → cart → checkout)  ≈ 7/10 template feeling
  homepage / blog                                     ≈ 2/10 template feeling

Goal:
  Shop / category / PDP        template feeling ≤ 3/10
  Commerce clarity              does not decrease anywhere
```

The goal is **not** to make WooCommerce disappear. The goal is:

> WooCommerce remains the engine; Lyli owns the storefront composition.

---

## 2. Architecture — frozen

Storefront V2 keeps:

```
WooCommerce commerce primitives
        ↓
Lyli composition
        ↓
Lyli visual system
        ↓
Lyli motion
```

It does not revert to:

```
Woo/Botiga template → CSS skin → motion
```

And it does **not** introduce: rewritten WooCommerce, a custom commerce frontend, headless architecture, React, Elementor or any page builder, a checkout builder, a custom cart/checkout/product/order model. WooCommerce continues to own product, variation, cart, checkout, order, payment, stock, coupon, customer, and shipping calculation in full. The theme owns presentation/composition only.

---

## 3. Intervention policy — frozen

```
L0  Theme option / WP setting
L1  CSS
L2  WordPress / WooCommerce hook
L3  Botiga filter/hook
L4  WooCommerce template override
L5  Custom component/template
```

Default policy: **L0 → L1 → L2 → L3**, lowest layer that's sufficient.

**Approved ceiling for this contract:**

```
L4 template overrides:  0
L5 custom components:   0 unless separately approved
```

If implementation discovers a real need for L4/L5, **stop** — do not self-authorize. Return for a founder review before proceeding.

---

## 4. Amendments to the preflight (resolved before freezing)

The preflight (chat-delivered, not committed — see §23) raised four items that needed deeper investigation before they could be frozen into a contract. All four are resolved below with verified, not assumed, answers.

### 4.1 Empty anchor accessibility — root cause verified, not CSS-patched

**Method:** rather than trace Botiga's conditional PHP by eye, the actual registered callback list for the shop-loop hooks was dumped live via WP-CLI against a real product-query context (`wp eval`, read-only, nothing persisted). Ground truth:

```
woocommerce_before_shop_loop_item
  [10] woocommerce_template_loop_product_link_open      ← native WC, opens <a>

woocommerce_before_shop_loop_item_title
  [9]  {closure}                                        ← Botiga: opens <div class="loop-image-wrap">
  [10] woocommerce_show_product_loop_sale_flash          ← native WC
  [10] woocommerce_template_loop_product_thumbnail       ← native WC, prints <img>
  [10] {closure}                                        ← Botiga: opens .loop-button-wrap,
                                                            prints the add-to-cart <a>, closes .loop-button-wrap
  [11] {closure}                                        ← Botiga: closes .loop-image-wrap
  [12] woocommerce_template_loop_product_link_close      ← native WC, closes <a> (relocated by Botiga)

woocommerce_after_shop_loop_item
  [9]  botiga_loop_product_structure
  [9]  botiga_wrap_loop_button_start
  [11] {closure}
```

**Root cause:** with the theme's own current settings (`shop_product_add_to_cart_layout` = `layout3`, the Botiga default), Botiga's own product-card composition (`inc/plugins/woocommerce/features/product-card.php`) relocates the add-to-cart button so it prints **inside** the still-open product-link `<a>` (opened at `before_shop_loop_item` priority 10, not closed until `before_shop_loop_item_title` priority 12 — and the button prints at priority 10 of that same hook, before the close). This nests one `<a>` (the add-to-cart button) inside another `<a>` (the product link) in the server-rendered HTML — invalid per the HTML5 spec (interactive content cannot nest). Browsers silently repair this via the parser's adoption-agency algorithm, which is exactly what produces the extra empty `<a class="woocommerce-LoopProduct-link">` elements visible in the parsed DOM. **The bug is Botiga's own unmodified default composition, not something Lyli's CSS or JS caused.**

**Two Botiga callbacks in the chain that produce this are anonymous closures** (`before_shop_loop_item_title` priority 9, 10, and 11) — `remove_action()` cannot target an anonymous closure without the original closure reference, which shop-child does not have. This rules out "surgically remove just the offending closure" as an option.

**Verified fix — L0, zero custom hook code:** Botiga exposes `shop_product_add_to_cart_layout` as a Customizer setting with four values (`layout1`–`layout4`), confirmed from `inc/customizer/options/woocommerce/shop-archive/section-product-card.php`. Tracing `product-card.php`'s conditionals against each value:

| Value | Behavior |
|---|---|
| `layout1` | Button removed from the loop entirely |
| `layout2` | **Not matched by any relocation condition** — button stays hooked to its native `woocommerce_after_shop_loop_item` (WooCommerce core default), i.e. outside and after the product-link anchor entirely |
| `layout3` (current default) | Relocated into `before_shop_loop_item_title`, nested inside the still-open link — **the bug** |
| `layout4` | Also relocated (icon-only variant), same nesting bug |

Setting `shop_product_add_to_cart_layout` to `layout2` removes the nested-anchor condition **at its source**, with no shop-child hook code required. This is also compositionally correct for the redesign independent of the accessibility fix: it moves the CTA to the button's native position (after title/price), which is exactly where Batch A's card contract (§9) wants it — not overlaid on the image.

**Acceptance:** after the setting change, re-run the same WP-CLI hook dump and confirm `woocommerce_template_loop_add_to_cart` is registered on `woocommerce_after_shop_loop_item` (not `before_shop_loop_item_title`); confirm live DOM shows exactly one `<a class="woocommerce-LoopProduct-link">` per card with no orphaned empty siblings.

**If, after implementation, this alone doesn't fully resolve it** (e.g. a Botiga update changes this behavior): the documented fallback is an `aria-hidden="true" tabindex="-1"` attribute pair added via a markup-level filter — explicitly a **last resort**, not the default plan, and only after the L0 setting change is confirmed insufficient.

### 4.2 Archive intro ownership — no duplicate editorial copy

Freeze rule:

- **Main shop archive** (`/cua-hang/`) may carry a Lyli-authored editorial eyebrow + one-line intro, hook-inserted (§8.1). This page has no taxonomy term backing it, so there is no existing canonical field this would duplicate.
- **Product category archives** (`/product-category/{slug}/`) use the category's own **`product_cat` term description** as the canonical intro copy, rendered through WooCommerce's existing `woocommerce_archive_description` hook (already wired by Botiga, unconditionally). **No second hook-inserted intro is added on category pages.** If a term description is empty, the category page simply shows no intro copy below the title — it does not fall back to a generic hook-inserted line. Writing term descriptions is a content task (WP Admin → Products → Categories → Description), not a code task.

### 4.3 Product metadata — no hardcoded "Handmade"

Checked the live catalog for a genuine, existing, catalog-wide data source (not assumed):

```
wp term list product_tag --fields=name,slug,count
  Móc khóa len handmade   moc-khoa-len-handmade   count: 11
  Quà tặng handmade       qua-tang-handmade       count: 11
  Phụ kiện treo balo      phu-kien-treo-balo      count: 11
```

All 11 currently-published products carry the `qua-tang-handmade` tag. That is a real, derivable, catalog-agnostic source today — but it is a **tag convention**, not a structural guarantee: nothing enforces that a future product in a different category continues to receive it. Per the amendment's own rule ("if there is no canonical source for Handmade: do not implement that item in Batch A"), this is judged **not yet canonical enough** to hardcode a "Handmade" word into the card contract without an explicit founder confirmation that tagging every future product with `qua-tang-handmade` is a permanent content policy.

**Decision:**
- **Stock-status-derived state** (`Có sẵn` / `Hết hàng` / `Đặt trước`) — **APPROVED for Batch A**. This reads directly from `$product->get_stock_status()` / `get_availability()`, a structural WooCommerce field every product has, not a tag convention.
- **"Handmade" label** — **DEFERRED**, pending founder confirmation that `qua-tang-handmade` (or an equivalent) is a mandatory tag applied to every product going forward. If confirmed, it is a one-line addition; if not confirmed, the metadata line ships with stock-status only.

### 4.4 PDP content recomposition — no schema invention, no batch-mixing

Batch B recomposes the existing "Mô tả" tab content into visually distinct blocks (§12.1) using the **headings the owner already wrote** (Thông tin sản phẩm / Chọn mẫu / Cá nhân hóa và thời gian chuẩn bị / Lưu ý sản phẩm handmade) as the section boundaries, styled via CSS/hook composition. It does **not**:
- introduce a new content schema (custom fields, ACF-style structured sections) in the same technical batch,
- have PHP guess section boundaries from free-form prose across products that don't share the same heading pattern,
- migrate content across every product in one pass.

If a genuine structured-content schema is wanted later (e.g. distinct "materials", "dimensions", "care" fields per product), that is explicitly a **separate content migration task**, out of scope for Batch B, requiring its own preflight.

---

## 5. Scope matrix

| Area | Current | Target | Batch | Intervention |
|---|---|---|---|---|
| Shop archive | Botiga `style1` header, no chips, no intro | Eyebrow + intro + native category chips | A | L0 (chips) + L2 (intro) |
| Category archive | Same header, empty term description | Term description via existing hook | A | L0 (content) |
| Product card | Hover-only CTA, nested/empty anchors, no metadata | Always-visible CTA, clean anchors, stock-derived metadata | A | L0 (button layout) + L1 (CSS) + L2 (metadata) |
| Single product | Rich content trapped in one description tab | Same content, recomposed into labeled blocks | B | L2 + content pass |
| Related products | Generic "Related products" grid | Curated heading + native carousel | B | L3 + L0 |
| Cart | "Shipment" leaks English | Fully Vietnamese | C | L2 (scoped `gettext`) |
| Checkout | Un-translated privacy paragraph, plain payment radios | Vietnamese copy, card-treated payment methods | C | L0 (setting) + L1 (CSS) |
| My Account | Unstyled | Light spacing/typography polish | C | L1 |

---

## 6. Batch A — Archive + Product Card

### 6.1 Archive composition

Approved direction:

```
breadcrumb

eyebrow / archive context
H1
editorial intro

category navigation/chips

result count                     sort

product grid
```

**Main shop (`/cua-hang/`):**

| Element | Source | Hook / setting | Priority |
|---|---|---|---|
| Eyebrow | New Lyli copy (short, static — e.g. "Quà tặng handmade") | `woocommerce_before_shop_loop`, custom callback in `inc/woocommerce.php` | Before Botiga's result-count callback (registered at priority 20 by Botiga's `woocommerce.php`) — use priority 15 |
| Intro line | New Lyli copy, one sentence | Same callback as eyebrow | Same |
| Category chips | Live `product_cat` terms (dynamic, not hardcoded) | Customizer → Shop → Archive Header → `shop_archive_header_style_show_categories` = enabled | L0, no code |
| CSS classes Lyli owns | — | New `.lyli-shop-intro` wrapper around eyebrow+intro only; chips stay in Botiga's own `.categories-wrapper` / `.category-button` markup, restyled via CSS, not restructured | — |

**Product category archive (`/product-category/{slug}/`):**

| Element | Source | Hook / setting |
|---|---|---|
| Category description | `product_cat` term description field (WP Admin → Products → Categories) | Existing `woocommerce_archive_description` (already wired, unconditional) |
| No duplicate intro | — | Confirmed by §4.2 — nothing else is hook-inserted here |
| Sub-category navigation | Live child `product_cat` terms | Customizer → `shop_archive_header_style_show_sub_categories` = enabled (same mechanism as main-shop chips, scoped to sub-categories) |
| Current-category active state | Botiga's own current-term detection in `botiga_shop_page_header_sub_category_links()` | Native, verify visually only — no code needed unless found broken |
| Empty description behavior | — | No intro copy renders. Not treated as a bug; it's a content gap (§4.2), tracked as a content task, not blocking Batch A code |

### 6.2 Category chips

Verified source: `botiga_woocommerce_page_header()` in `inc/plugins/woocommerce/features/wc-page-header.php`, gated by theme mod `shop_archive_header_style_show_categories` (main shop) / `shop_archive_header_style_show_sub_categories` (category/tag/taxonomy archives). Output is a `<div class="categories-wrapper">` of `<a class="category-button" role="button">` elements, one per top-level `product_cat` term (main shop) or per child term of the current category (category archive), built from `get_terms()` with `hide_empty: true` — so chips never point at empty categories.

- **Mobile wrapping/scroll:** current CSS gives no explicit overflow behavior for `.categories-wrapper` — **must be verified in browser at 390px during acceptance testing (§11)** and given `overflow-x:auto` + `flex-wrap:nowrap` (or allowed to wrap) via CSS if it doesn't already behave acceptably. This is an L1 CSS task, not a markup change.
- **Active category state:** Botiga's chip markup does not appear to mark the current category distinctly (confirmed by reading the function — no `current` class conditional in the loop). If browser testing confirms this, add a CSS-only current-state indicator by matching the chip's `href` against the current queried term (small L2 PHP addition to append a class conditionally — acceptable, not a template change).
- **Fallback:** if Botiga's native chip output proves visually or structurally unsuitable after real testing (not assumed in advance), the fallback is restyling the existing `.category-button` output via CSS only — never a custom PHP re-render of the taxonomy loop. Native primitive stays first-choice per §3.

### 6.3 Result count + sort

- Keep WooCommerce's native `woocommerce_result_count()` and `woocommerce_catalog_ordering()` output and callbacks exactly as-is (Botiga's own `botiga_wrap_products_results_ordering_before/after` wrapper, `woocommerce_before_shop_loop` priorities 19/30/31). **No query or ordering logic changes.**
- Fix the 390px sort-label clipping (confirmed live: "Sắp xếp mặc c…") via CSS only — the `<select>`'s container width/overflow, not the option text.
- Visual relationship between the two controls: CSS only (flex layout, spacing, shared baseline) — the two elements already share a wrapper (`botiga_wrap_products_results_ordering_before/after`), so this is a pure styling task, not new markup.
- Breakpoint: match existing shop-child breakpoints already in use (`600px`, `700px`, `783px`, `1025px` — confirmed present in `style.css`) rather than inventing a new one.

---

## 7. Product card contract

Final composition, in order:

```
image

product title
price

optional metadata line (stock-status only — see §4.3)

CTA
```

Not a giant information card. No secondary badges/tags beyond what's specified here without a separate approval.

### 7.1 Image

Keep the existing Woo/Botiga image request (`woocommerce_template_loop_product_thumbnail`, existing `single_product_archive_thumbnail_size` filter chain). **No secondary-image request added in Batch A.** Product image swap-on-hover remains **DEFERRED** (§21) — the preflight already confirmed via live DOM inspection of three real products that Botiga's loop template exposes exactly one `<img>` with no secondary-image data for most products; re-approving it requires a per-product data audit, not a Batch A decision.

### 7.2 Title

- Semantic element unchanged: stays whatever WooCommerce/Botiga output (`<h2 class="woocommerce-loop-product__title">` on archive loops, confirmed via live DOM).
- Typography: restyled via existing shop-child CSS token system (Fraunces heading weight), CSS only.
- Wrapping: allow natural wrap, no `-webkit-line-clamp` (a clamp was tried and reverted earlier this project for a different card type after it truncated real content — do not repeat that mistake here; use `align-items:stretch` + `height:100%` grid technique already established in `style.css` if card-height evenness is needed).
- No fake truncation that would hide part of a product's real name.

### 7.3 Price

Keep full commerce prominence — same visual weight class as today, restyled (not shrunk) via CSS tokens. Variable products continue showing WooCommerce's native price-range output (`woocommerce_template_loop_price`) unmodified — no custom price-range formatting logic.

### 7.4 Metadata

Canonical contract (§4.3 resolved):

```
stock-derived (APPROVED, Batch A):
  in stock       → "Có sẵn"
  out of stock   → "Hết hàng"
  on backorder   → "Đặt trước"

"Handmade" label:
  DEFERRED — pending founder confirmation of a permanent
  per-product tagging policy (see §4.3)
```

Source: `$product->get_stock_status()`, a native WooCommerce field on every product — never inferred from title/content, never hardcoded per-product.

### 7.5 CTA

Botiga's hover-only `visibility:hidden` behavior on `.add_to_cart_button` is replaced with an **always-visible, visually restrained** button — CSS-only change (remove the existing hover-reveal rule), combined with the `shop_product_add_to_cart_layout = layout2` change from §4.1, which also relocates the button to its native, non-nested position (after title/price).

- **Simple products:** AJAX add-to-cart — unchanged WooCommerce/Botiga behavior. The existing inline add-to-cart success confirmation (`assets/js/cart-badge.js`, anchored to `li.product` per the fix already shipped this session) **must continue to work unmodified** — Batch A does not touch that script's logic, only the CSS/layout around the button it targets.
- **Variable products:** CTA continues to read "Chọn" and route to the PDP for variation selection, exactly as WooCommerce/Botiga already do. **No custom variable-add workflow, no event-semantics changes.**

---

## 8. Empty-anchor fix — final contract

See §4.1 for the full root-cause trace. Summary contract:

1. **Primary fix (L0):** set `shop_product_add_to_cart_layout` to `layout2` via Customizer/theme mod. Removes the nested-anchor condition at its source — no shop-child hook code.
2. **Verification:** live WP-CLI hook dump (method in §4.1) confirms `woocommerce_template_loop_add_to_cart` is back on `woocommerce_after_shop_loop_item`; live DOM confirms exactly one `<a class="woocommerce-LoopProduct-link">` per card.
3. **Fallback only if step 1 proves insufficient:** `aria-hidden="true" tabindex="-1"` on any remaining empty anchor node, added via a markup-level filter — must not touch the real, populated product-image/title link.

**Acceptance:** zero empty focusable product links in the shop-loop DOM at any tested viewport; the real clickable image/title link and the CTA remain independently clickable and keyboard-reachable.

---

## 9. Batch A acceptance criteria

Viewports: `390×844`, `820×1180`, `1440×900`, `1920×1080`.

Pages: `/cua-hang/`, at least 2 product categories (e.g. `moc-khoa-len`, `lyli-tiny`), a simple-product card (AJAX add-to-cart, e.g. product 236), a variable-product card (e.g. product 172).

Must pass at every viewport:

- No horizontal overflow
- Sort-dropdown label not clipped
- Product CTA visible without hover, at every viewport including touch
- No duplicate intro copy on category archives (term description only, per §4.2)
- Category chip row wraps/scrolls cleanly, no overflow
- Zero empty focusable anchors in the product loop (verified via DOM inspection, not just visual)
- Keyboard navigation reaches image link, title link, and CTA independently and in a sane order
- AJAX add-to-cart still works and cart count updates exactly once per click
- Variable product CTA still routes to the PDP correctly
- Existing motion (reveal, hover states, cart-badge pulse, add-to-cart confirmation, sticky header) still functions, unaffected by the layout change
- `prefers-reduced-motion` behavior unaffected
- No new console errors
- No new network requests (no new image sizes, no new script/style dependency)

---

## 10. Batch B — PDP + Related Products (scope frozen, detail lighter than A)

### 10.1 Single product

**Approved:**
- Keep the WooCommerce variation form and add-to-cart logic exactly as-is
- Keep the gallery system (`single-product/product-image.php` flow, unmodified)
- Trial Botiga's native alternate gallery layouts (`single_product_gallery` theme mod: `gallery-grid`, `gallery-scrolling`, `gallery-showcase` with its bundled "sticky entry summary" option) **before** any custom gallery work — this is an L0 setting, already confirmed to exist in Botiga's own `features/single-product-gallery.php`
- Editorialize the surrounding composition (recompose the existing "Mô tả" tab content into labeled blocks per §4.4) using content the owner already wrote
- Reuse existing product copy first — no new copywriting requirement to ship Batch B

**Not approved in Batch B:**
- Content schema migration (structured fields) — see §4.4, separate task
- Custom variation UI engine
- Custom gallery library (no new JS dependency)
- Custom sticky-purchase-state logic (see §13 — separately gated)

### 10.2 Related products

**Approved:**
- Curated heading via the verified Botiga filter `botiga_woocommerce_product_related_products_heading` (confirmed present in `inc/plugins/woocommerce/features/related-products.php`, applied in `botiga_woocommerce_output_related_products_slider()` and the standard related-products heading path)
- Native Botiga carousel via theme mod `shop_single_related_products_slider` (confirmed: when enabled, swaps to `botiga_woocommerce_output_related_products_slider()`, which enqueues the already-bundled `botiga-carousel` script — **zero new JS**)
- Inherits Batch A's product-card language — no separate card component
- No new JS library of any kind

Both hook/setting names above were read directly from the deployed Botiga source (`inc/plugins/woocommerce/features/related-products.php`), not invented.

---

## 11. Sticky mobile add-to-cart — gated, not automatic

**Status: OPTIONAL / REQUIRES POST-BATCH-A UX REVIEW.** Not included in Batch B by default.

Reason: it affects a high-intent commerce interaction (the final add-to-cart action) and should only be added, if at all, after the redesigned product card and archive (Batch A) have been browser-tested and the PDP redesign (Batch B core) has shipped — evaluating it in isolation, ahead of real user-facing context, risks solving a problem that may not exist once the rest of the PDP composition is in place.

---

## 12. Batch C — Cart / Checkout / Account

Freeze principle: **reliability > originality.** No composition rewrite of either page.

**Approved:** visual hierarchy, spacing, typography, payment-method card treatment, language cleanup, My Account light polish.

### 12.1 Fix untranslated "Shipment"

**Root cause, verified from WooCommerce core source** (`includes/class-wc-cart.php`): `_x('Shipment', 'shipping packages', 'woocommerce')`. This is a real core string, translated with a **context** (`shipping packages`), missing from the active Vietnamese translation coverage for that specific context — not a broken install (see `docs/WOOCOMMERCE-VIETNAMESE-2026-08-08.md`, which already documents that some community-translated strings can remain in English and explicitly advises **against** editing installed translation files directly, since a language-pack update would silently revert that edit).

**Fix strategy — smallest scoped mechanism, consistent with that existing guidance:**

```php
add_filter('gettext_with_context', function ($translated, $text, $context, $domain) {
    if ($domain === 'woocommerce' && $text === 'Shipment' && $context === 'shipping packages') {
        return 'Vận chuyển';
    }
    return $translated;
}, 10, 4);
```

Scoped on **domain + exact source string + exact context** simultaneously — cannot match or alter any other string, in WooCommerce or any other textdomain. This is a `gettext_with_context` filter (not `gettext`) because the source uses `_x()`, which routes through the context-aware filter, not the plain one.

**Acceptance:**
```
Cart: Vietnamese (incl. "Vận chuyển")
Checkout: Vietnamese (incl. "Vận chuyển")
Order-review fragments after AJAX refresh: still Vietnamese (fragments re-render server-side, so the filter applies identically — verify live after an AJAX cart update, not just on initial page load)
```

### 12.2 Checkout privacy paragraph

**Root cause, verified from WooCommerce core source** (`includes/wc-template-functions.php`, `wc_get_privacy_policy_text()`): the English paragraph is WooCommerce's own hard-coded default, returned via `get_option('woocommerce_checkout_privacy_policy_text', <English default>)` because that option has never been set.

**Preferred fix — L0, WooCommerce admin setting, not code:**

```
WooCommerce → Settings → Accounts & Privacy → "Checkout privacy policy"
```

**This task does not invent the Vietnamese legal copy.** The exact wording for that field must be supplied or approved by the owner/legal separately — recorded here as an open content dependency, not filled in speculatively. If a code-level override is ever preferred instead of the admin field, the documented mechanism is the `woocommerce_get_privacy_policy_text` filter (confirmed present in the same source function) — but the admin-setting route is preferred since it requires no deploy at all.

### 12.3 Payment methods

CSS-only visual card treatment around the existing `.wc_payment_methods` / `.payment_box` markup (both confirmed as real, current classes on the live checkout page). Explicitly must not: replace the radio inputs, hide the existing payment description copy (VietQR/BACS/COD text is already good, custom-written), change the payment-selection JS, or touch VietQR/BACS or COD behavior in any way.

### 12.4 Progress indicator

**Status: OPTIONAL / P2.** If implemented: visual only, derived purely from page context (`is_cart()` / `is_checkout()`), zero AJAX/session state coupling, no fake or inferred checkout steps beyond what the page context itself proves.

---

## 13. My Account

**Frozen: LIGHT POLISH ONLY.** Spacing, typography, optional card/border treatment on existing elements. No redesign effort beyond that. Lowest priority of all three batches.

---

## 14. Existing motion contract

Storefront V2 reuses the current motion language without modification unless a specific new UX problem requires it.

```
--lyli-motion-fast     120ms
--lyli-motion-base     200ms
--lyli-motion-slow     320ms
--lyli-motion-section  600ms

--lyli-ease-standard   cubic-bezier(0.25, 0.1, 0.25, 1)
--lyli-ease-out        cubic-bezier(0.32, 0.08, 0.24, 1)
```

Keep unmodified: hero entrance, `reveal.js` (category/info/story/CTA/blog-archive/latest-posts), category-card hover, blog-card hover, cart-badge pulse, inline add-to-cart success feedback, `sticky-header.js` hide/reveal, CTA arrow-nudge, and the two-layer `prefers-reduced-motion` policy (blanket duration collapse + targeted transform neutralization).

**No additional motion in Storefront V2 unless a specific UX problem requires it** — Batch A/B/C as scoped in this document require none beyond what already exists (the related-products carousel reuses Botiga's own bundled `botiga-carousel`, not a new motion primitive).

---

## 15. CSS ownership

New presentation classes are prefixed `lyli-` (e.g. `.lyli-shop-intro`, `.lyli-card-metadata`) — consistent with the existing convention already used throughout `style.css` (`.lyli-hero`, `.lyli-category-card`, etc.).

When a rule must target a Woo/Botiga class directly (e.g. `.woocommerce ul.products li.product`, `.wc_payment_methods`) because no Lyli wrapper class is available at that DOM point without a hook change, that's acceptable — but the CSS comment at that rule must say why a wrapper wasn't used, following the existing commenting convention already established in `style.css` (e.g. the hero-grid specificity comments, the reduced-motion rationale comments).

**Explicitly forbidden:** broad selectors like `.woocommerce * { ... }` or multi-level descendant chains like `.product div span ...` that become unmaintainable archaeology. Every new rule should be traceable to a specific, named reason.

---

## 16. PHP ownership

New presentation hooks for Batch A/B live in `web/app/themes/shop-child/inc/woocommerce.php`, following its existing pattern (namespaced functions under `ShopChild\Woo`, each hook registration documented with a one-line comment explaining what it does and why it's presentation-only).

**Split trigger:** if Batch A + Batch B combined would push `inc/woocommerce.php` past roughly 200–250 lines or mix clearly distinct concerns (archive vs. card vs. single-product), split into:

```
inc/woocommerce/archive.php
inc/woocommerce/product-card.php
inc/woocommerce/single-product.php
```

with `functions.php` requiring each explicitly (matching the existing `inc/*.php` require pattern already in `functions.php`). **Only split if it genuinely improves maintainability at that point** — do not pre-emptively create the split structure now for a single small Batch A. This decision is deferred to whoever implements Batch A/B and should be made against the actual line count and concern-mixing at that time, not against this document's hopes.

---

## 17. Testing contract

### Functional
- Simple product add-to-cart (AJAX)
- Variable product select-and-route flow
- Cart count updates
- Cart page (add/remove/update quantity)
- Checkout page loads and totals calculate correctly
- COD payment method selectable and submits
- BACS/VietQR payment method selectable, QR/instructions still render correctly
- Vietnam address dropdown (province/ward) still functions
- Shipping display still correct

### Visual
- 390px, ~820px, 1440px, 1920px — all pages touched by the active batch

### Accessibility
- Keyboard-only navigation through card, archive, PDP, cart, checkout
- `:focus-visible` states intact
- Zero empty/dead links (per §8)
- Controls remain semantic (`<button>`/`<a>` used correctly, no `<div onclick>`)
- `prefers-reduced-motion` respected
- Touch targets remain ≥44px (existing convention already used in `style.css`, e.g. `.woocommerce ul.products li.product .button { min-height: 44px; }`)

### Performance
- No new external/JS dependency
- No new JS unless explicitly approved in this document (none is, beyond what already exists)
- No extra image requests in Batch A
- No CLS regression
- No obvious scroll jank (reuse the existing rAF-throttled pattern already established in `sticky-header.js` if any new scroll listener is ever needed — none is currently scoped)

---

## 18. Rollback contract

Each batch is deployed and can be rolled back independently:

```
Batch A   Archive + product card
Batch B   PDP + related products
Batch C   Cart / checkout / account polish
```

Sequence per batch: (1) source commit → (2) deploy → (3) browser verification at all four viewports → (4) commerce smoke test (§17 functional list) → (5) founder review → (6) proceed to next batch.

**Batches are not combined into one release.** If Batch A fails visual or functional acceptance in production, roll back Batch A's release only — Batch B/C, not yet deployed, are unaffected by construction.

---

## 19. Explicit deferred list

Not part of approved implementation. Return only through a new preflight/approval, not by drifting into scope during A/B/C:

```
Product image swap
Custom PDP content schema
Content migration across every product
Sticky mobile add-to-cart
Mini cart drawer
Checkout flow redesign
WooCommerce template overrides
New animation library
New ecommerce JS library
Custom filtering engine
Custom AJAX product grid
Headless/frontend rewrite
```

---

## 20. Decision log

### APPROVED
- Moderate recomposition (not a redesign, not "do nothing")
- Hook-first intervention policy, L0→L3
- Zero WooCommerce template overrides
- Archive composition (eyebrow/intro on main shop; term description on category archives)
- Botiga native category chips (`shop_archive_header_style_show_categories` / `_show_sub_categories`)
- Product card hierarchy: image → title/price → stock-derived metadata → always-visible CTA
- `shop_product_add_to_cart_layout` → `layout2` (fixes nested-anchor bug at the source, relocates CTA to native position)
- Deterministic metadata only (stock status); "Handmade" label deferred pending tag-policy confirmation
- PDP editorial composition using existing content only (no schema invention)
- Related-products curated heading + native Botiga carousel
- Checkout/cart visual polish + the two verified string fixes

### DEFERRED
- Product image swap (needs per-product gallery-data audit)
- "Handmade" metadata label (needs founder confirmation of permanent tag policy)
- Sticky PDP add-to-cart bar (needs post-Batch-A UX review)
- Product-content schema migration (separate task)
- Checkout progress indicator (P2, only if state-decoupled at implementation time)
- Checkout privacy-policy Vietnamese copy itself (owner/legal must supply the wording; the mechanism to apply it is documented)

### REJECTED
- Custom commerce frontend / headless / React
- Template-copy-heavy architecture
- New JS animation library
- Checkout engine changes
- Page builder (Elementor or otherwise)

---

## 21. Documentation consistency audit

Checked existing docs for contradictions with this contract:

- **`docs/THEME-DECISION.md`** (§8-area language): permits "template override WooCommerce có lý do hẹp và được ghi lại" (a narrow, documented reason). **Not a contradiction** — that clause permits an override *if* narrowly justified and documented; this contract's ceiling of 0 overrides is a stricter current target within that same allowance, not a conflict with it.
- **`docs/WOOCOMMERCE-VIETNAMESE-2026-08-08.md`**: documents that some WooCommerce-core strings may remain untranslated and explicitly advises against editing translation files directly. **Consistent** with this contract's §12.1 fix strategy (a scoped `gettext_with_context` filter, not a `.mo` file edit) — the contract was designed to comply with this existing guidance, not contradict it.
- **`docs/PRODUCTION-STATUS.md`**: references `shop-child` version `1.3.1` in one status line — this is a dated historical log entry (theme is now `1.4.0`, tracked in `style.css`'s own header and in §0 of this document). Per instruction, historical status logs are not rewritten for a version bump that happened after they were written; this document is the current canonical reference for the version instead.
- No other existing doc makes a claim about shop-archive/product-card/PDP composition that this contract conflicts with.

**Contradictions found: NONE requiring a fix to an existing document.**

---

## 22. Implementation checklist (quick reference)

```
[ ] Batch A
    [ ] shop_product_add_to_cart_layout → layout2 (Customizer)
    [ ] shop_archive_header_style_show_categories → enabled (Customizer)
    [ ] shop_archive_header_style_show_sub_categories → enabled (Customizer)
    [ ] Archive eyebrow + intro hook (inc/woocommerce.php)
    [ ] Product card CSS: always-visible CTA, metadata line, title/price weight
    [ ] Stock-status metadata hook
    [ ] Sort-dropdown 390px clipping fix (CSS)
    [ ] Category chip mobile wrap/scroll verification + fix if needed (CSS)
    [ ] WP-CLI hook-dump re-verification (empty anchors gone)
    [ ] Full acceptance pass (§9) at 4 viewports
    [ ] Deploy, commerce smoke test, founder review

[ ] Batch B
    [ ] Trial Botiga native gallery layouts (Customizer)
    [ ] Recompose PDP description into labeled blocks (existing content only)
    [ ] botiga_woocommerce_product_related_products_heading filter
    [ ] shop_single_related_products_slider → enabled (Customizer)
    [ ] Acceptance pass, deploy, smoke test, founder review

[ ] Batch C
    [ ] gettext_with_context filter for "Shipment"
    [ ] Checkout privacy policy text (content from owner) → WooCommerce setting
    [ ] Payment method CSS card treatment
    [ ] My Account spacing/typography pass
    [ ] Acceptance pass, deploy, smoke test, founder review
```

---

*This document is the canonical contract for Storefront Composition V2. Implementation must not exceed the intervention ceiling in §3, must not implement anything listed in §19, and must stop for founder review if a documented fix in §4/§6/§10/§12 proves insufficient in practice.*
