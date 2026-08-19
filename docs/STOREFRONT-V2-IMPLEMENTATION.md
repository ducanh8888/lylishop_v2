# Storefront Composition V2 — Implementation Contract

Status: **FROZEN**. This document is the canonical source of truth for Storefront V2 implementation. It supersedes any composition decision that exists only in chat history or a commit message. Implementation work (Batch A/B/C) must follow this contract exactly; if implementation discovers a need to exceed the intervention ceiling set here, **stop and get founder review** rather than self-authorizing a higher layer.

## Status at a glance (updated after Batch C implementation + production acceptance, source `891593b`)

| Batch | Status |
|---|---|
| A — Archive + Product Card | **IMPLEMENTED, CLOSED.** Passed a full-review final verdict (see §6a) after two corrective sub-passes (A.1 hierarchy, A.2 catalog-first UX). One latent correctness bug found and fixed in the review pass (§6a). |
| B — PDP + Related Products | **IMPLEMENTED, CLOSED.** Deployed and production-accepted — see §10a for the final review verdict, evidence corrections, and the one post-deploy fix (sticky-CTA premature-visibility bug). |
| C — Cart / Checkout / Account | **IMPLEMENTED / CONTENT PENDING.** Deployed and production-accepted for everything technical — see §12a. The checkout privacy consent paragraph remains WooCommerce's English default; no owner-approved Vietnamese wording exists yet for that specific sentence, and inventing/machine-translating legal copy is out of scope. Not marked fully CLOSED until that content is supplied. |

Sections §4 and §6–§9 document Batch A's implementation history (root causes, amendments, hook choices) and remain as historical/evidentiary record — superseded in *emphasis* by §6a's final verdict where the two disagree, but not rewritten, since the root-cause detail in them is still the reference for *why* the code looks the way it does.

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

### 4.5 Batch A.2 amendment — archive is catalog-first, not hero-first

A.1 shipped a *technically* correct composition (right hook, right DOM position, right spacing math) that was still wrong: live-measured, the header/eyebrow/H1/intro/chip/toolbar stack consumed **68% of a 1440×900 first viewport** before any product row appeared — a shopper who clicked "Sản phẩm" landed on what read as a campaign hero, not a catalog. Corrected in Batch A.2 with durable rules for any future archive/composition work in this project:

- **The archive is a product-browsing interface first, an editorial/branded surface second.** A meaningful part of the first product row must be visible in a normal desktop first viewport without scrolling. This is an explicit acceptance constraint, not a nice-to-have.
- **Category/sub-category navigation renders only when it gives the shopper an actual choice** — 2 or more real (non-empty) sibling terms. A single chip is not navigation, it's a dead-end that repeats what the intro copy already said. Gated generically by `wp_count_terms()` through WordPress's own `theme_mod_{name}` filter (see `inc/woocommerce.php`), never by hardcoding a category name — if the catalog grows past one real category, the chips reappear on their own.
- **Prefer a native, hook-free composition over reordering CSS.** A.1's flex `order` trick (needed because a hook only fires *before* the H1, and the intro copy was wanted *after* it) required a follow-up fix once already, because Botiga's two chip-rendering functions nest their markup differently between the main shop and category archives. A.2 removed the need for `order` entirely by moving the intro copy into WooCommerce's own native shop-page-description slot (`wc_get_page_id('shop')` post content, rendered automatically by Botiga's existing `botiga_woocommerce_product_archive_description()` — the exact mechanism category archives already used for their own term description). When a DOM-order workaround needs a second workaround, that's the signal to find the native slot instead of patching the hack.
- **Visual acceptance is reviewed as whole-screen composition, not element-by-element hook correctness.** A.1 passed every functional/DOM check and was still a UX regression. The standing acceptance question for any future archive change: *if this were a shopper's first visit, would this page help them start browsing immediately?*
- **Centered "hero" alignment was reversed to left-aligned**, matching the product grid's own reading axis — a centered block directly above a left/right commerce toolbar (result count / sort) created a jarring realignment shoppers had to visually re-parse.

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

## 6a. Batch A final review verdict (full-review pass)

**PASS WITH FIXES.** Reviewed the actual deployed production state from scratch (not prior PASS reports) across desktop/tablet/mobile, as a shopper, on `/cua-hang/`, `/product-category/moc-khoa-len/`, a tag archive, and every card variant. One correctness bug and one small cross-flow defect were found and fixed in this pass (both below); everything else confirmed working as designed.

**Shop archive (A.2's catalog-first redesign):** confirmed still holding — left-aligned reading axis, compact header, full first product row visible above the fold at 1440×900 and immediately visible at 390px, sort control reads as a control, result count concise, single dead-end category chip still correctly absent. No remaining awkwardness found.

**Category archives:** hierarchy and hierarchy-to-grid transition read as one page, matching the main shop's visual language. The `Lyli Charm` / `Lyli Tiny` sub-category chips on `moc-khoa-len` **do** help — they're real, non-overlapping product groupings (confirmed live: both have their own published products), not an arbitrary taxonomy artifact, so `count >= 2` was the right threshold here, not a shortcut. Empty term description renders no intro, correctly.

**Product cards:** image, title, price, stock metadata, and CTA all read clearly at both card densities (4-col desktop, 2-col mobile). Variable-product price and CTA ("Chọn") vs. simple-product CTA ("Thêm vào giỏ hàng") are visually identical in weight but functionally distinct — acceptable, since both are equally "the one thing to click" from a card, and the PDP is where the distinction actually matters. No card redesign warranted.

**Correctness bug found and fixed (FIX NOW):** `suppress_single_subcategory_nav()` queried `wp_count_terms('product_cat', ['parent' => $term->term_id])` for *any* of `is_product_category() || is_product_tag() || is_product_taxonomy()`, but term IDs are shared across taxonomies in WordPress — a tag's `term_id` has no relationship to `product_cat`'s parent/child structure. Live-verified on a real, reachable, non-empty tag archive (`/product-tag/capybara-handmade/`, term_id 59): the query returned 0 only because no category happens to have `parent=59` — coincidence, not correctness. As the catalog grows and term IDs increase across taxonomies sharing the same ID space, that coincidence could flip and show unrelated categories as if they were "children" of the current tag. Fixed to only ever evaluate the count when the queried term's own taxonomy is actually `product_cat`; every other taxonomy context now suppresses outright, since "sub-category chips" isn't a coherent concept there regardless of count.

**Cross-flow defect found and fixed (FIX NOW):** cart/checkout order-review tables (`table.shop_table`) let price amounts wrap mid-number at 390px ("55.0" / "00 đ" on two lines) — a narrow price column plus default `white-space: normal`. A price splitting across a line break is a defect, not a design choice; fixed with `white-space: nowrap` on `.amount` inside `shop_table`, covering both cart and checkout order-review (same class, same template family).

**Content reproducibility (§4 of the review brief):** A.2 moved the shop-archive intro into the WooCommerce Shop page's own post content (`wc_get_page_id('shop')`, post ID 5). `docs/PRODUCTION-INSTALL-RUNBOOK.md` step 10 already documents that the standard WooCommerce setup wizard creates this page on a fresh install, but did not document that its *content* carries this intro. Documented in `shop-child/README.md`'s "Owner editing surface" section (new bullet), which is the living reference for "what content lives where" — the historical, already-executed install runbook was left untouched per the project's own convention of not rewriting completed historical logs.

---

## 10. Batch B — PDP + Related Products (CURRENT / FROZEN, detailed by the full-review pass)

This section supersedes the original brief version. It is grounded in real catalog data pulled live during the review, not Botiga documentation screenshots.

### 10.1 PDP information hierarchy (frozen)

Reviewed the actual rendered DOM/content for a simple product (Gà Mắt Lồi) and a variable product (Capybara). Both already put decision-critical content above the fold at 1440px: gallery, title, price, short description, variation form (variable) or quantity+CTA (simple). Classification, to guide Batch B recomposition — **do not reshuffle what's already correct**:

| Element | Class | Notes |
|---|---|---|
| Breadcrumb | supporting | keep, unchanged |
| Gallery | decision-critical | see §10.2 |
| Title | decision-critical | keep, unchanged |
| Price | decision-critical | keep full prominence — never shrink to "editorial footnote" |
| Short description | decision-critical | already present, already good, keep in the summary column |
| Variation selectors (variable only) | decision-critical | native WooCommerce form, untouched |
| Quantity + primary add-to-cart | decision-critical | must stay unmistakably primary — see §10.3 |
| Custom-order hint (`lyli-custom-order-hint`) | supporting | already small/quiet below the CTA — confirmed live it does not compete, keep as-is |
| SKU / category / tags meta | supporting | keep, unchanged |
| Full description tabs ("Mô tả" / "Thông tin bổ sung" / "Đánh giá") | post-decision detail | recompose per §10.4 |
| Related products | post-decision detail | see §10.5 |

**Verdict: no purchase-block reordering needed.** The existing hook order (native WooCommerce `woocommerce_single_product_summary` priorities) already produces the right hierarchy. Batch B's PDP work is about the *content tabs* and *gallery choice*, not about moving the purchase block.

### 10.2 Gallery — frozen, based on real catalog data

**Evidence correction (pre-implementation review):** the original freeze below stated "11 products" while its own bucket counts summed to 12 and its percentages summed past 100% — an internal inconsistency, not just stale data. Re-ran the catalog query live before implementing anything. The catalog had also genuinely grown by one product since the prior pass ("Hoa hướng dương kép len handmade" — product 261, a `simple` product). Corrected, invariant-checked evidence (12 unique published products; bucket counts sum to 12; percentages sum to 100%):

```
1 image:  3 products (25.0%)
2 images: 4 products (33.3%)
3 images: 3 products (25.0%)
4 images: 1 product  (8.3%)
6 images: 1 product  (8.3%)
                      ------
Total:   12 products  100.0% (sum verified)
```

**Frozen: keep `gallery-default` (Botiga's current setting). Do not switch to `gallery-grid`/`gallery-scrolling`/`gallery-showcase`.** The corrected distribution reinforces the original conclusion rather than changing it: 58.3% of the catalog has only 2–3 images, and a quarter has exactly 1. A layout built around a richer gallery (grid/showcase, generally designed for 4+ images) would look sparse or produce empty affordances on the majority of the catalog — exactly the "empty carousel arrows" failure mode the review brief warned against.

**Mobile gallery acceptance (browser-verified at 390×844, not merely inherited-and-assumed):** tested the 1-image product (Gà Mắt Lồi), a 2–3-image product, and the highest-image-count product (6 images). All three: no horizontal overflow, no broken/tiny thumbnail rail, first image fully visible on load, no confusing prev/next arrows on the 1-image product (native WooCommerce template omits the thumbnail rail entirely when there's nothing to navigate — confirmed, not assumed), content after the gallery flows naturally with no dead space. One `scrollWidth` overflow flag investigated and ruled a false positive — an off-canvas mini-cart dropdown widget (`position:absolute; visibility:hidden; offsetParent:null`) unrelated to the gallery, confirmed via a real `window.scrollTo` + `scrollX` check showing no actual scrollable overflow.

- **One-image fallback:** confirmed correct (native WooCommerce behavior, no thumbnail rail rendered).
- **Multi-image behavior:** confirmed correct (native thumbnail rail + zoom, Botiga default) at desktop and mobile.
- **Mobile behavior:** now directly browser-verified (not merely presumed inherited) across the three representative image-count buckets — no regressions found, no gallery code changed.

### 10.3 Purchase block — frozen

- Title scale, price prominence, quantity control: **keep as-is**, already correct.
- Variation form: native, untouched. Live-verified the disabled/dimmed state (`opacity: 0.7`, `.wc-variation-selection-needed`) already communicates "select a variation first" correctly — no restyling needed.
- Add-to-cart CTA: already the visually dominant element on both simple and variable PDPs (full brand-color fill vs. the custom-order hint's small text link) — **confirmed it does not need defending against `"Đặt mẫu theo yêu cầu"` or the custom-order hint; live review found no competition.**
- Success/error feedback: reuses the existing add-to-cart confirmation system (contract §14) — no new mechanism.

### 10.4 PDP description content — implemented, full-catalog census

**Evidence upgrade (pre-implementation review):** the original freeze sampled 5/11 products. Before writing any parsing logic, re-ran the census across the **full current catalog (12/12 published products)**, reading each product's raw `post_content` HTML directly rather than assuming the 5-product sample generalized:

```
Every one of 12/12 products follows:
  [intro paragraph] → <h2>Thông tin sản phẩm</h2> → [1–2 variable middle
  heading(s), inconsistently worded across products: "Cá nhân hóa và
  thời gian chuẩn bị" / "Chọn mẫu" / "Chọn màu" / "Lựa chọn sản phẩm"]
  → <h2>Lưu ý sản phẩm handmade</h2> → [closing paragraph]
```

**Finding, confirmed 12/12 (not 5/11):** "Thông tin sản phẩm" and "Lưu ý sản phẩm handmade" are the *only* two headings stable across the entire catalog — always `<h2>`, always top-level (never nested inside another element), always first/last among the recognized headings. The middle heading(s) remain genuinely unstable in wording, confirming the original freeze's caution against keying on them.

**Implemented mechanism — PHP `DOMDocument` (built-in, no dependency), not regex:** `web/app/themes/shop-child/inc/woocommerce/single-product.php`'s `split_description_sections()` parses the already-`the_content()`-filtered HTML, walks only the **top-level** child nodes, and buckets them by matching `<h2>` text against the two stable headings (case-insensitive, exact match):
- **`before`** — content preceding the first recognized heading (the intro paragraph, on every product sampled).
- **`info`** — content following "Thông tin sản phẩm" up to the next heading of any kind.
- **`details`** — everything else, in original order: the variable middle heading(s) + their content, the "Lưu ý sản phẩm handmade" heading + its content, and any closing text — deliberately *not* split further, since the middle heading text is not stable enough to key on safely (confirmed by the 12-product census, not merely presumed).

This never reorders content and never drops a node — every top-level child of the original content lands in exactly one bucket, in its original relative order. A product with neither recognized heading (partial/minimal content) puts everything in `before`, rendering identically to the original plain tab — a safe degrade, not a broken one. `loadHTML()` failure (malformed input) falls back to printing the unmodified content rather than risking data loss.

**Validated before wiring into WordPress:** isolation-tested against two real, structurally-different product descriptions (products 236 and 172) with a standalone PHP harness (stubbed `__()`/`esc_html__()`, no WordPress bootstrap) — zero content loss or reordering in either case. Re-validated a second time inside the actual WordPress runtime via `wp eval-file` against the live release, before the release was flipped to `current`.

**Implementation layer:** replaces the `description` tab's callback via the `woocommerce_product_tabs` filter (no template override — `templates/single-product/tabs/description.php` is untouched, only which function the tabs array calls for that one tab changes).

This remains explicitly **not** a structured-content schema. A genuine structured schema (separate fields for materials/dimensions/care) remains **DEFERRED**, per §4.4 — the full-catalog census reinforces, not weakens, that conclusion: the middle-heading wording variance is exactly the signal that a rigid schema would break.

### 10.5 Related products — implemented

Re-verified against the current 12-product catalog (not the 11-product figure the original freeze used): 3 related products still render in a plain grid, using the same card markup Batch A already restyled (confirmed: stock metadata and single clean anchor present on related-product cards too, no separate component needed).

**Frozen and implemented: native grid, not carousel.** At a 12-product catalog with only 3 related items shown, a carousel still has nothing to carousel *through* — 3 items still fit a 3-column desktop row exactly, with mobile already falling back to the existing 2-column card grid. Enabling `shop_single_related_products_slider` here would add interaction affordance (arrows/dots) with no actual additional content behind it. Revisit this specific freeze if the catalog grows enough that "related products" routinely exceeds one visible row.

**Filter-name correction (pre-implementation review):** the original freeze cited `botiga_woocommerce_product_related_products_heading`. Reading the actual template source before implementing revealed this hook only fires inside Botiga's **carousel**-rendering function (`botiga_woocommerce_output_related_products_slider()`, `inc/plugins/woocommerce/features/related-products.php`) — which this design deliberately does not use (native grid stays). The render path actually taken (`templates/single-product/related.php`, WooCommerce's own default template) reads its heading from WooCommerce's **native**, non-prefixed filter `woocommerce_product_related_products_heading` (confirmed at `related.php:40`). Implemented against the correct filter; the wrong citation in the original freeze would have silently no-op'd if implemented as originally written.

- Heading: "Có thể bạn cũng thích", via `woocommerce_product_related_products_heading` (`inc/woocommerce/single-product.php`).
- Count: unchanged (3).
- No new JS library, no carousel enablement.

### 10.6 Mobile PDP and the sticky-CTA — implemented

**Decision: APPROVED FOR BATCH B, and implemented.**

Evidence, live-measured on the Capybara (variable) PDP at 390×844: the primary add-to-cart button sits well below the first viewport, meaning a large share of the page's scroll depth has zero purchase action available once a shopper scrolls past the purchase block to read the (real, substantial, owner-written) description content. A shopper who reads the description and decides to buy would otherwise have to scroll back up to find the button again.

Reviewed against the contract's original concerns before implementing — none blocked it:
- **Variation ambiguity:** solved by design, not by duplicating state — the sticky bar is a *proxy* to the real form, never a second form. If a variation is already selected, tapping it submits via the same native add-to-cart call the real button uses; if not, tapping it scrolls to and focuses the real variation selector. No variation state, price, or stock logic is ever read or written twice.
- **WooCommerce validation conflict:** none — the sticky bar never intercepts or duplicates WooCommerce's own validation/AJAX; it only ever defers to the real button.
- **Obscuring content:** appears only after the real purchase block scrolls out of view, and hides again when the real block or the footer comes back into view — never permanently docked.

**Observation-target correction (pre-implementation review):** the original freeze specified watching `.summary`. Before implementing, the actual DOM was measured live on a real variable-product PDP: `.summary` extends roughly 250px past the real purchase form — it also contains `product_meta`, the Botiga brand-wrapper, and `lyli-custom-order-hint`, all rendered *after* the form closes. Watching `.summary` would have delayed the sticky bar's appearance well past the point the real purchase action actually left the viewport, directly contradicting the "once the real purchase action is no longer reasonably available" requirement. **`form.cart` is the implemented observation target** — present with the same class on both simple (`class="cart"`) and variable (`class="variations_form cart"`) products, and always wraps the real add-to-cart button exactly, on both product types.

**Implemented mechanism** (`web/app/themes/shop-child/assets/js/pdp-sticky-cta.js`):
- `IntersectionObserver` on `form.cart` (visibility) and on the footer (never overlaps the footer); `MutationObserver` on the real button and price node (class/text sync — no polling).
- Shows: product title, current visible price, and a single button whose label/enabled-state/aria-label are read live from the real button's DOM state every observer tick — never cached.
- Tap behavior: if the real button carries `disabled`/`wc-variation-selection-needed`, smooth-scrolls to `.variations` and focuses the first unresolved `<select>` — no fake add-to-cart attempt; if the real button is enabled, calls `.click()` on it directly (exactly one native click, proxied, not duplicated).
- Quantity: not represented in the sticky bar at all — the real form's quantity input remains the only quantity control; a sticky-button click submits through the real button, which reads quantity from the real form naturally.
- Respects `prefers-reduced-motion` via the theme's existing blanket duration-collapse rule (same mechanism `sticky-header.js` already relies on) — no separate reduced-motion CSS needed.
- Mobile-only via a CSS `max-width: 782px` gate — reusing the exact breakpoint `sticky-header.js` already uses for its own sticky/relative header switch (same "sticky/pinned UI" semantic category), not a new breakpoint invented for this feature.
- `env(safe-area-inset-bottom, 0px)` bottom padding for notch/home-indicator safety.

**Post-deploy fix (found during production browser acceptance, before Batch B was marked closed):** the initial implementation showed the bar immediately on page load, before any scroll — live-verified on the simple-product mobile PDP: `form.cart` starts at `y≈1014px`, below the 844px viewport, so the very first `IntersectionObserver` callback reports non-intersection from frame one, at `scrollY: 0`. This contradicted the acceptance requirement ("real button visible → sticky hidden; scroll down → sticky appears") — "scrolled beyond the real purchase block" implies an actual scroll happened, not merely non-intersection at initial load. **Fixed** by gating visibility on a real `scroll` event having fired at least once (`{ once: true, passive: true }` listener), in addition to the existing form/footer intersection state. Re-verified live post-fix: bar stays hidden at `scrollY: 0` even though the form is below the fold; correctly appears once the shopper scrolls the form out of view; correctly hides again once the form is scrolled back into view; correctly stays `display: none` (not merely un-shown) at desktop width regardless of internal state. Deployed as commit `78a0f5e`, release `20260819134713`.

### 10.7 Intervention ceiling (Batch B)

L0 (gallery setting confirmed unnecessary — kept at current default) → L1 (CSS for description recomposition, related-products heading via L3 filter, sticky-CTA styling) → L2/L3 (heading-detection presentation logic, `botiga_woocommerce_product_related_products_heading` filter) → **one small new JS file** for the sticky-CTA visibility/proxy logic (IntersectionObserver + click-proxy, same pattern as `reveal.js`/`sticky-header.js`, not a new dependency). **No L4/L5.** No template overrides.

### 10.8 Batch B acceptance criteria (whole-screen, not element-by-element)

**Desktop:**
- Gallery and purchase block together establish product + price + decision path within the first viewport, for both simple and variable products independently
- Primary CTA is the unambiguous, unmistakable primary action
- Variation path is understandable without trial-and-error (native form, native disabled-state)
- No decorative/editorial section pushes the purchase block down or delays the decision

**Mobile:**
- Required variation/CTA flow is obvious
- No horizontal overflow
- No competing CTAs (custom-order hint stays visually subordinate)
- Product information remains readable at 390px
- Sticky CTA behaves exactly per §10.6's frozen spec — proxy only, never a duplicate form, never permanently docked, respects reduced motion

---

## 10a. Batch B final review verdict (production acceptance)

**IMPLEMENTED, CLOSED.** Deployed as source commit `991e3d9` (PDP description, related heading, sticky CTA) plus follow-up commit `78a0f5e` (sticky-CTA post-deploy visibility fix), production release `20260819134713`, verified live on `https://lylishop.online`.

**Pre-implementation evidence corrections (before any code was written):** the gallery catalog count (11 → 12 products, corrected distribution, §10.2), the related-products heading filter name (`botiga_`-prefixed → native `woocommerce_product_related_products_heading`, §10.5), and the description census sample size (5/11 → 12/12 full catalog, §10.4) were all corrected against live-queried/live-read evidence before implementation began, per the mandate to verify the frozen design rather than implement it as originally written.

**PHP file split:** `inc/woocommerce.php` was split into `inc/woocommerce/{archive,product-card,single-product}.php` per §16's existing trigger, once Batch B's related-heading and description-recomposition hooks would have pushed the single file into mixed archive/card/PDP concerns. `functions.php` requires each explicitly. No aesthetic-only refactor — done at the point the split trigger's own criteria were actually met.

**Simple-product PDP (Gà Mắt Lồi, mobile 390×844 and desktop 1440×900):** gallery (1-image, no thumbnail rail, no confusing arrows) confirmed correct; description recomposition renders "Thông tin sản phẩm" as its own labeled block with all original bullet content intact, followed by the variable middle heading and "Lưu ý sản phẩm handmade" inside the details block, in original order, nothing dropped or duplicated; sticky CTA hidden on load, appears only after a real scroll event once the form leaves the viewport, hides again when the form or footer re-enters view, `display: none` entirely above 782px.

**Variable-product PDP (Capybara, mobile 390×844 and desktop 1440×900) — full sticky-CTA state matrix, browser-verified:**
- Unselected: real button carries `disabled wc-variation-selection-needed`; sticky bar reflects `lyli-sticky-cta-unavailable`; tapping it scrolls to `.variations` and moves focus to the first unresolved `<select>` — confirmed via `document.activeElement` — with no add-to-cart attempt.
- Variation selected: real button loses the disabled classes; sticky bar's `MutationObserver` syncs within one tick (`lyli-sticky-cta-unavailable` clears, label updates, price matches the real `.summary .price` text exactly — both read `55.000 ₫` live).
- Click-to-purchase: instrumented the real button's click listener before tapping the sticky button — exactly one native click fired (`clickCount: 1`), and the real form's own native submission behavior executed (this product configuration performs a redirect-style add-to-cart, unmodified by the sticky script) — confirming the proxy never duplicates or intercepts WooCommerce's own handler.
- Desktop: sticky bar is `display: none` regardless of scroll position or internal visibility-class state — never appears on tablet/desktop.

**Regression sweep (production, post-deploy):** `/`, `/cua-hang/`, cart, checkout — zero console errors on every page beyond the pre-existing jQuery Migrate log line; a test cart item added during the click-proxy test was removed via the native cart-page remove control (functions correctly); no PHP fatals/warnings surfaced in any of the above.

**Template overrides:** 0. **New frontend libraries:** 0. **New JS files:** 1 (`assets/js/pdp-sticky-cta.js`, vanilla, no dependency, enqueued only on `is_product()`). **Business-logic changes:** 0 — WooCommerce's variation engine, add-to-cart handlers, pricing, stock, and cart/session logic are untouched; the sticky CTA reads and proxies only.

**Rollback:** previous release `20260819133359` (source `991e3d9`) remains on disk, undisturbed, one `ln -sfn` away if a regression surfaces later.

---

## 12. Batch C — Cart / Checkout / Account (CURRENT / FROZEN, detailed by the full-review pass)

Freeze principle: **reliability > originality.** No composition rewrite of either page. This section supersedes the original brief version.

**Approved:** visual hierarchy, spacing, typography, payment-method card treatment, language cleanup, My Account light polish.

### 12.0 Cart hierarchy — frozen

Live-reviewed at 1440px and 390px with a real item in cart. Desktop table (product / price / quantity / subtotal columns, coupon field, order-summary panel with warm cream background) already reads clearly — primary action ("Tiến hành thanh toán") is the one full-brand-color button, correctly dominant over "Cập nhật giỏ hàng" (muted/secondary, correctly dimmed until quantity actually changes — native WooCommerce disabled-state behavior, not a bug). **Frozen: keep the native table composition, no rewrite.** One real defect was found and already fixed in this same review pass (§6a): price amounts wrapped mid-number at 390px (`white-space: nowrap` added to `table.shop_table .amount`). No further cart layout change is scoped for Batch C beyond what's already shipped.

- Primary action: "Tiến hành thanh toán" (full brand color)
- Secondary action: "Cập nhật giỏ hàng" (muted, native disabled-until-changed state)
- Information that matters before checkout: product identity/image, quantity, subtotal, order total — all present, all legible
- Noise to visually reduce: none found — the table is already lean (no shipping estimator, no extra upsell blocks)

### 12.1 Fix untranslated "Shipment" — implemented

**Root cause, re-verified live from WooCommerce core source** (`includes/class-wc-cart.php`): `_x('Shipment', 'shipping packages', 'woocommerce')`. Confirmed unchanged since the original freeze — still a real core string, translated with a **context** (`shipping packages`), missing from the active Vietnamese translation coverage for that specific context (see `docs/WOOCOMMERCE-VIETNAMESE-2026-08-08.md`, which documents this class of gap and explicitly advises **against** editing installed translation files directly).

**Implemented** in `web/app/themes/shop-child/inc/woocommerce/commerce-ui.php` as a named, namespaced function (`ShopChild\Woo\translate_shipment_label`), following the same module convention as `archive.php`/`single-product.php` rather than a global anonymous filter:

```php
add_filter('gettext_with_context', __NAMESPACE__ . '\\translate_shipment_label', 10, 4);
function translate_shipment_label(string $translated, string $text, string $context, string $domain): string
{
    if ($domain === 'woocommerce' && $text === 'Shipment' && $context === 'shipping packages') {
        return __('Vận chuyển', 'shop-child');
    }
    return $translated;
}
```

Scoped on **domain + exact source string + exact context** simultaneously — cannot match or alter any other string, in WooCommerce or any other textdomain.

**Acceptance — verified live on production:**
```
Cart initial render: "Vận chuyển" (was "Shipment")
Checkout initial render: "Vận chuyển" (was "Shipment")
Checkout AJAX fragment refresh (triggered via the real `update_checkout` event,
  the same event WooCommerce's own address-change handler fires): "Vận chuyển"
  survives the refresh — fragments re-render server-side through the same
  translation pipeline, confirmed live, not merely assumed
```

### 12.2 Checkout privacy paragraph — CONTENT PENDING

**Root cause, re-verified from WooCommerce core source** (`includes/wc-template-functions.php`, `wc_get_privacy_policy_text()`): unchanged — the English paragraph is WooCommerce's own hard-coded default, returned via `get_option('woocommerce_checkout_privacy_policy_text', <English default>)` because that option has never been set. Live-confirmed on production: `wp option get woocommerce_checkout_privacy_policy_text` returns the English default verbatim.

**Searched for an already-approved Vietnamese wording before concluding this is blocked**, per the task's own requirement — found:
- `wp_page_for_privacy_policy` **is set** (post ID 20, "Chính sách bảo mật", `publish`, last updated 15/08/2026) — a full, real, owner-approved Vietnamese Privacy Policy **page** already exists and is live at `/chinh-sach-bao-mat/`. The checkout paragraph's `[privacy_policy]` shortcode already correctly links to it (confirmed live: the rendered link text "chính sách riêng tư" — itself a core-translated string, unrelated to this gap — points at `https://lylishop.online/chinh-sach-bao-mat/`).
- **No approved wording exists specifically for the short checkout consent sentence itself** ("Your personal data will be used to process your order…") — this is a distinct, shorter piece of copy from the full policy page, and nothing in `docs/`, WP options, or the admin guide supplies an approved Vietnamese version of it.

**Decision, per the task's explicit constraint:** do not invent or machine-translate this sentence. Left as an open content dependency — implement everything else in Batch C, and do not report Batch C as fully CLOSED while this remains.

```
PRIVACY CONTENT DEPENDENCY:
Owner-approved Vietnamese wording required for the short checkout
consent paragraph (WooCommerce → Settings → Accounts & Privacy →
"Checkout privacy policy"). The full Privacy Policy PAGE is already
approved, live, and correctly linked — only this one shorter sentence
is outstanding.
```

Fix mechanism (unchanged, ready to apply the moment wording is supplied): `WooCommerce → Settings → Accounts & Privacy → "Checkout privacy policy"` — an L0 admin setting, no deploy required.

### 12.2a Checkout language audit — full sweep, re-verified live

Re-reviewed the entire checkout page for English leakage against the current catalog/cart state, classified by mechanism:

| String | Classification | Mechanism | Status |
|---|---|---|---|
| "Shipment" (cart totals + checkout order review) | core translation gap | `gettext_with_context` filter, §12.1 | **Fixed, deployed, verified** |
| Checkout privacy paragraph | owner/legal content, delivered via a core translation-default fallback | WooCommerce admin setting, §12.2 | **Open — content dependency** |
| `[privacy_policy]` shortcode link text ("chính sách riêng tư") | core translation, confirmed already Vietnamese via the vi language pack | no action | Already correct |
| Payment method descriptions (VietQR/BACS/COD text) | site-config copy | already custom-written and already Vietnamese — confirmed live, no gap found | Already correct |
| Field labels (Tên, Họ, Địa chỉ, Quốc gia/Khu vực, Tỉnh/Thành phố, Phường/Xã, etc.) | core translation, confirmed already Vietnamese | no action | Already correct |

No new English-leakage strings were found beyond the one already documented in §12.2 — this was a verification pass, not a new-defect pass. **Did not** edit installed language-pack files, and **did not** invent the privacy wording.

### 12.3 Payment methods — implemented

CSS-only visual card treatment around the existing `.wc_payment_methods` / `.wc_payment_method` / `.payment_box` markup (all confirmed live on the deployed checkout page). Live-verified enabled gateways: `bacs` ("Chuyển khoản / VietQR") and `cod` ("Thanh toán khi nhận hàng") — matches the contract's assumption exactly (`WC()->payment_gateways()->get_available_payment_gateways()`).

**Implemented mechanism** — CSS `:has(input:checked)`, not a JS-added state class:

```css
.woocommerce-checkout ul.wc_payment_methods li.wc_payment_method:has(input:checked) {
    border-color: var(--lyli-color-primary);
    background: var(--lyli-color-cream);
}
```

This reads the real native radio's checked state directly, so the visual selected-state can never drift from the actual selection — including across a checkout AJAX fragment refresh, which re-renders the identical native markup server-side (verified live: switching to COD then triggering `update_checkout` left the COD card correctly highlighted after the refresh). Browsers without `:has()` simply render every method in its quiet/unselected treatment — a plainer page, not a broken one; no JS fallback needed since nothing functional depends on the visual state.

- **Selected state:** confirmed live — primary-color border + cream background, description sits inside with a divider.
- **Unselected state:** confirmed live — quiet border, warm-white background.
- **Radio visibility:** native input untouched, no `appearance:none` on the radio itself.
- **Description hierarchy:** VietQR/COD descriptions unchanged, unmoved — only spacing/border added around the existing `.payment_box`.
- Verified: no gateway IDs, payment-selection JS, or gateway business logic touched; radios click-switch instantly and the CSS state follows the real checked radio on every click, live-confirmed via `getComputedStyle`.

### 12.4 Progress indicator — decision: REJECTED

**Not deferred, not optional — rejected outright by this review.** Classic Cart and Classic Checkout (contract §2, architecture freeze) are two separate WordPress pages joined by a normal navigation, not steps in a client-side wizard. WooCommerce does not have navigable "Cart → Information → Shipping → Payment" states within checkout — it's a single-page form with visual sections, submitted once. A progress indicator implies a multi-step flow that does not actually exist; showing one would be presenting fake progression the underlying system can't back up. If the actual checkout flow ever changes to a genuine multi-step one (out of scope for Storefront V2 entirely — that would be a business-logic change), this decision should be revisited then, not worked around now with decorative steps.

### 12.5 Form language — implemented (applies across Batch C, and is the baseline Batch B's PDP already follows)

Derived from what's already working across the site, not invented for checkout specifically.

**selectWoo/select2 finding (pre-implementation review, corrects an unstated assumption):** live DOM inspection found billing/shipping **country and state/province** are enhanced by WooCommerce's own bundled selectWoo (confirmed via the `select2-hidden-accessible` class on the real `<select>`, plus a live `.select2-container` in the DOM). The **ward/"Phường-Xã"** select (`billing_city`/`shipping_city`, the Vietnam-address connector's own field) is native and unenhanced — no select2 class, confirmed live. Styling the native `<select>` for country/state would have touched an invisible element and changed nothing on screen, so:

- **Country/state (selectWoo-enhanced):** styled the actual rendered `.select2-selection` UI directly — border, radius, and background matched to the site's input tokens; focus/open state gets the primary-color border. selectWoo itself was left fully enabled, untouched.
- **Ward select (native):** reuses the exact bordered + chevron treatment Batch A shipped for the shop-archive sort control (§7) — same mechanism, not a second select style.
- **One real bug found and fixed during production acceptance** (not present in the original freeze): a pre-existing `.form-row select { background: var(--lyli-color-warm-white) }` rule used the `background` *shorthand*, which resets `background-image` too — at an equal-specificity tie with the new chevron rule, this silently dropped the ward select's chevron. Split `background-color` out from the shorthand so the two rules stop colliding (deployed as a same-day follow-up commit, verified live before Batch C was reported closed).
- **Text inputs:** existing bordered style (`--lyli-color-border`, `var(--lyli-radius-sm)`) — unchanged, already correct.
- **Quantity:** unchanged, already correct on cart/PDP.
- **Radios/checkboxes:** kept native throughout, including the new payment-method cards (§12.3) — no custom-drawn replacements anywhere.
- **Focus:** confirmed live — the existing global `:focus-visible` rule still covers payment radios and every checkout control unmodified; no second focus treatment was added.
- **Error/helper text:** untouched — native WooCommerce validation only.
- **Disabled state:** untouched — the existing `opacity: 0.7` pattern remains the site's one disabled-state language.

---

## 12a. Batch C final review verdict (production acceptance)

**IMPLEMENTED / CONTENT PENDING.** Deployed as source commit `42acdf8` (translation + payment/select CSS) plus same-day follow-up commit `891593b` (select-chevron cascade fix found during acceptance), production release `20260819143928`, verified live on `https://lylishop.online`.

**Pre-implementation review, cart:** re-tested with a real simple product + a real variable product (variation attribute correctly appears baked into the cart-item title per WooCommerce's own `WC_Product_Variation::get_name()` default format — confirmed this is native behavior, not a missing `dl.variation` bug). Native table composition, price-wrap fix, and quantity/coupon/subtotal all still correct against the current (12-product) catalog. No new cart defect found — left untouched per the frozen contract.

**Pre-implementation review, stale assumptions corrected:**
1. §12.1's example code was inline/anonymous — implemented instead as a named, namespaced function in a new module (§14 below), consistent with how `archive.php`/`single-product.php` already work.
2. §12.5 did not record that country/state are selectWoo-enhanced while the ward select is native — both now handled by their actually-correct mechanism (§12.5).
3. The checkout privacy paragraph search (§12.2) found a previously-undocumented fact: a full, approved, live Vietnamese Privacy Policy **page** already exists (`wp_page_for_privacy_policy`, post 20) — narrowing the real open dependency to just the short consent sentence, not the whole legal topic.

**Implemented:**
- "Shipment" → "Vận chuyển": scoped `gettext_with_context` filter, verified on cart, checkout, and after a real checkout AJAX fragment refresh (§12.1).
- Payment-method card treatment: `:has(input:checked)`-driven, native radios untouched, verified surviving an AJAX fragment refresh (§12.3).
- Checkout address selects: selectWoo UI styled for country/state, native chevron treatment for the ward select — including a same-day fix for a background-shorthand cascade collision found in production acceptance (§12.5).
- Checkout hierarchy, cart structure, and the progress-indicator rejection: all re-verified against the current catalog/cart and left exactly as frozen — no defect found that would justify touching them (§12.0, §12.4).
- My Account: reviewed logged-out at both viewports (§13a) — already-shipped light polish (bordered nav/content, shared table/form language) holds up; no new defect found, so nothing further was added, per the explicit instruction not to add scope merely because this surface is less glamorous than the PDP.

**Not implemented (by design, not oversight):** the checkout privacy consent sentence — no owner-approved Vietnamese wording exists yet; inventing or machine-translating it was explicitly out of scope (§12.2).

**AJAX acceptance (live-verified):** dispatched a real `update_checkout` event (the same event WooCommerce's own address-change handler fires) — "Vận chuyển" persisted, the previously-selected COD payment card's highlighted state persisted, selectWoo remained enhanced, zero console errors before or after.

**Regression sweep (production, post-deploy):** `/`, `/cua-hang/` (zero empty product-loop anchors, sort chevron intact, no overflow), simple PDP (sticky CTA hidden-on-load → appears after real incremental scroll past `form.cart` → hides on scroll-back, confirmed unaffected by Batch C's CSS-only changes; description recomposition sections intact), variable PDP (related-products heading intact) — zero console errors beyond the pre-existing jQuery Migrate log and two unrelated image-preload warnings (native browser preload-timing noise, not caused by this batch). Keyboard focus on payment radios confirmed via the existing global `:focus-visible` rule.

**Template overrides:** 0. **New JS files:** 0. **New JS libraries:** 0. **Business-logic changes:** 0 — gateway IDs, payment selection JS, checkout AJAX, and WooCommerce validation are all untouched; every new rule is presentation (CSS) or scoped string translation (one `gettext_with_context` filter).

**Rollback:** previous release `20260819134713` (source `78a0f5e`, the last Batch B state) remains on disk, undisturbed, one `ln -sfn` away if a regression surfaces later.

---

## 13. My Account (CURRENT / FROZEN, detailed by the full-review pass)

**Frozen: LIGHT POLISH ONLY.** Spacing, typography, optional card/border treatment on existing elements. No redesign effort beyond that. Lowest priority of all three batches — confirmed by this review, not just asserted: the logged-out login/register form is plain but functionally clear, and low-traffic relative to guest checkout on a small handmade shop (established finding, unchanged since the original preflight).

- **Navigation treatment:** WooCommerce's native My Account tab navigation — restyle only (typography/spacing to match site tokens), no new nav component.
- **Content width:** match the existing `.woocommerce-cart .entry-content` / `.woocommerce-checkout .entry-content` max-width (`1140px`, already declared in `style.css`) for consistency with cart/checkout.
- **Spacing/forms:** reuse §12.5's form language exactly — no separate account-specific form treatment.
- **Tables (orders list):** restyle only via the same `table.shop_table` treatment cart/checkout already use (including this pass's price-wrap fix, which applies here too since it's the same class).
- **Mobile:** no separate mobile design needed — native WooCommerce account markup already stacks reasonably; verify no overflow, do not redesign.
- **Logged-in dashboard, orders, addresses, account details, logout:** not independently re-designed — all inherit the same restyle-only treatment as the navigation/forms/tables above. Do not turn this into a bespoke "dashboard product."

### 13a. Batch C intervention ceiling

L0 (checkout privacy setting — mechanism ready, wording pending) → L1 (payment-method card CSS, selectWoo/select form-language CSS, My Account restyle — all shipped) → L2 (one `gettext_with_context` filter for "Shipment", shipped, same pattern already used for the archive result-count in Batch A). **No L2 business-logic hooks, no L3, no L4/L5.** Zero new JS shipped — Batch C stayed presentation-only by nature (reliability > originality) end to end, confirmed at implementation time, not just planned.

### 13b. Batch C acceptance criteria (whole-screen, not element-by-element)

**Cart:** shopper can verify items/quantities/total at a glance; checkout CTA visually dominates; mobile cart is not a crushed desktop table (already confirmed true — native table already reflows reasonably at 390px, and the one real defect found this pass — price wrap — is fixed).

**Checkout:** form-completion path is obvious (field order/labels already clear, confirmed live); payment selection is visually obvious with the shipped card treatment; "Đặt hàng" remains the dominant final action; no ambiguity after AJAX refresh (verified live via a real `update_checkout` event — "Vận chuyển" and the selected-payment card state both survive); zero untranslated *operational* strings (§12.1 shipped) — the one remaining English string is owner/legal content (§12.2, open dependency, not an operational string); validation stays native and visible.

**Account:** feels part of the same storefront (shared tokens/forms/tables), without having been turned into a separate design effort.

---

## Design system (derived from what's already working, full-review pass)

This is not a new token system — every value below already exists in `style.css`. This section exists so Batch B/C reuse it explicitly instead of re-deriving spacing/color/type decisions per page, which is exactly the mistake that made the pre-A.2 archive feel like disconnected Botiga sections.

**Typography**
- Display/heading: Fraunces, 600 weight — used for H1 (page titles), product titles, section headings. Archive/PDP H1 uses a *scoped* smaller clamp (`clamp(1.6rem, calc(1.25rem + 1.4vw), 2.5rem)`, contract §6) than the global `.page-title` default (`clamp(2rem, calc(1.45rem + 2.4vw), 4.3rem)`) — the scoped version is the one to reuse for any future compact-header context; the global one is for a page carrying a title alone (cart/checkout currently use the unscoped, larger version — acceptable there since neither sits above a toolbar the way the archive did).
- Body: Be Vietnam Pro, 400 weight — all copy, descriptions, table content.
- Eyebrow: Be Vietnam Pro, 600 weight, `0.78–0.8rem`, uppercase, `letter-spacing: 0.08em`, `var(--lyli-color-primary)` — one established pattern (shop archive eyebrow), reuse verbatim for any future eyebrow, don't invent a second treatment.
- Price: bespoke — `1.55rem` on PDP (`.summary .price`), smaller on cards, always full-weight/full-color, never muted. Muted-copy token (`--lyli-color-muted`) is reserved for secondary information (stock metadata, descriptions) and must never touch price text.

**Spacing tiers actually in use** (do not invent a second scale):
- Micro: `4–8px` (eyebrow-to-title, icon gaps)
- Component: `12–18px` (card internal padding, form field gaps)
- Section: `20–36px` (clamp-based, e.g. `clamp(20px, 3vw, 32px)`) — between related blocks within one page section
- Block: `clamp(28px, 4–6vw, 68px)` — between major page sections (`.content-wrapper` margin-top, PDP `.images`/`.summary` margin-bottom)

**Surfaces**
- `--lyli-color-warm-white`: primary page/card background
- `--lyli-color-cream`: secondary surface (order-summary panels on cart/checkout, hover fills)
- Borders: `1px solid var(--lyli-color-border)`, `var(--lyli-radius-sm/md)` — used on inputs, chips, cards
- Cards: `border-radius: 16px`, `box-shadow: var(--lyli-shadow-card)` (product cards, order-summary panels) — the one card treatment, reuse for any future card-like surface (payment-method cards, Batch C)

**CTA language**
- Primary: full `var(--lyli-color-primary)` fill, white text — add-to-cart, "Tiến hành thanh toán", "Đặt hàng". Exactly one per view.
- Secondary: muted/lighter fill or outline — "Cập nhật giỏ hàng" (already correctly dimmed until actionable — reuse this *disabled-until-actionable* pattern, not a separate "secondary button" style, wherever the same semantics apply)
- Text/navigation action: plain link color (`var(--lyli-color-primary)`, underline on hover) — breadcrumb, custom-order hint, "Xem chi tiết"-type links
- Destructive: none currently styled distinctly (cart remove is an icon "×", not a labeled destructive button) — if Batch C introduces a labeled destructive action, use `--lyli-color-primary`'s existing warm palette, not a foreign red, consistent with the brand constraint against generic ecommerce visual language
- Disabled: `opacity: 0.7` on the same base button style — never a separate greyed-out button component

**Form language:** see §12.5 (written for Batch C, applies identically to any future PDP/archive form control).

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

**Split executed during Batch B implementation.** Batch B's related-heading and description-recomposition hooks would have pushed the single `inc/woocommerce.php` into mixed archive/card/PDP concerns, meeting the trigger below. Split into:

```
inc/woocommerce/archive.php        (Batch A — shop/category archive hooks)
inc/woocommerce/product-card.php   (Batch A — loop/card hooks)
inc/woocommerce/single-product.php (Batch B — PDP hooks: custom-order hint,
                                     related-products heading, description
                                     recomposition)
inc/woocommerce/commerce-ui.php    (Batch C — cart/checkout/account
                                     presentation: the "Shipment" scoped
                                     translation fix)
```

`functions.php` requires each explicitly (matching the existing `inc/*.php` require pattern). Each file keeps the original pattern: namespaced functions under `ShopChild\Woo`, each hook registration documented with a comment explaining what it does and why it's presentation-only.

**Split trigger (for reference, now satisfied twice):** the single file would have exceeded roughly 200–250 lines or mixed clearly distinct concerns once Batch B's hooks were added; Batch C's cart/checkout translation fix is a fourth, equally distinct concern from archive/card/PDP, so it got its own narrow module (`commerce-ui.php`) rather than being folded into `single-product.php` — matching the task's explicit instruction not to dump checkout translations into the PDP file, and not to build a directory taxonomy more complex than the shop itself.

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
Mini cart drawer
Checkout flow redesign
WooCommerce template overrides
New animation library
New ecommerce JS library
Custom filtering engine
Custom AJAX product grid
Headless/frontend rewrite
```

Sticky mobile add-to-cart is **no longer on this list** — the full-review pass made the decision (approved, §10.6) with a frozen behavior spec, superseding its earlier "deferred pending review" status.

---

## 20. Decision log

### APPROVED
- Moderate recomposition (not a redesign, not "do nothing")
- Hook-first intervention policy, L0→L3
- Zero WooCommerce template overrides
- Archive composition (eyebrow on main shop via `botiga_before_shop_archive_title`; intro copy via the native Shop-page-description slot, not a hook — §6a/A.2; term description on category archives)
- Botiga native category chips, count-gated (`theme_mod_shop_archive_header_style_show_categories` / `_show_sub_categories` filters, ≥2 real choices required) — confirmed by full review to give real navigational value where they do appear (`Lyli Charm`/`Lyli Tiny`), not just a mechanical threshold
- Product card hierarchy: image → title/price → stock-derived metadata → always-visible CTA
- `shop_product_add_to_cart_layout` → `layout2` (fixes nested-anchor bug at the source, relocates CTA to native position)
- Deterministic metadata only (stock status); "Handmade" label deferred pending tag-policy confirmation
- PDP editorial composition using existing content only (no schema invention) — refined by full review to key specifically on the two proven-stable headings (§10.4), not a general heading-matcher
- Related-products: curated heading, **native grid** (not carousel — full review reversed the original "carousel permitted" framing based on real catalog size, §10.5) — **implemented and deployed**, using the corrected native `woocommerce_product_related_products_heading` filter (§10.5)
- **Sticky mobile PDP add-to-cart** — approved by full review with a frozen proxy-only behavior spec (§10.6), no duplicated purchase state — **implemented and deployed**, observing `form.cart` (corrected from `.summary`) with a post-deploy scroll-gating fix (§10.6, §10a)
- **Batch B PHP file split** (`inc/woocommerce/{archive,product-card,single-product}.php`) — executed once Batch B's hooks met the existing split trigger (§16)
- Checkout/cart visual polish + the two verified string fixes + the full-review language audit (§12.2a) confirming no further English leakage
- Cart/checkout/account table price `white-space: nowrap` fix (FIX NOW, shipped this pass)
- `suppress_single_subcategory_nav()` correctness fix — only evaluate on real `product_cat` terms (FIX NOW, shipped this pass)
- **"Shipment" → "Vận chuyển"** — scoped `gettext_with_context` filter, named/namespaced, verified surviving checkout AJAX fragment refresh — **implemented and deployed** (§12.1)
- **Payment-method card treatment** — `:has(input:checked)`-driven, native radios/JS untouched — **implemented and deployed**, verified surviving AJAX refresh (§12.3)
- **Checkout selectWoo/native-select form language** — selectWoo UI styled directly for country/state, Batch A chevron treatment reused for the native ward select, including a same-day cascade-collision fix found in production acceptance — **implemented and deployed** (§12.5)
- **Batch C PHP module** (`inc/woocommerce/commerce-ui.php`) — a fourth narrow module, not folded into `single-product.php` (§16)

### DEFERRED
- Product image swap (needs per-product gallery-data audit)
- "Handmade" metadata label (needs founder confirmation of permanent tag policy)
- Product-content schema migration (separate task; full review found the catalog's heading variance is itself evidence against attempting this yet, §10.4)

### CONTENT PENDING (not deferred — technical mechanism is ready and unblocked)
- **Checkout privacy consent paragraph** — no owner-approved Vietnamese wording exists for this specific short sentence (distinct from the full Privacy Policy page, which is already approved and live at `/chinh-sach-bao-mat/`, post ID 20). Apply via `WooCommerce → Settings → Accounts & Privacy → "Checkout privacy policy"` the moment wording is supplied — no further deploy needed (§12.2).

### REJECTED
- Custom commerce frontend / headless / React
- Template-copy-heavy architecture
- New JS animation library
- Checkout engine changes
- Page builder (Elementor or otherwise)
- **Checkout progress indicator** — rejected outright by full review, not merely deferred: Classic Cart/Checkout have no real multi-step state to represent, so any progress UI would be fake progression (§12.4)
- **Related-products carousel** — rejected for the current catalog size (3 items exactly fills one row; nothing to carousel through, §10.5). Revisit only if the catalog grows enough to routinely exceed one visible row.

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
[x] Batch A — IMPLEMENTED, CLOSED (§6a final verdict)
    [x] shop_product_add_to_cart_layout → layout2
    [x] Category chips, count-gated (theme_mod_{name} filters)
    [x] Archive eyebrow (hook) + intro (native Shop-page-description, not a hook)
    [x] Product card CSS + stock-status metadata
    [x] Sort-dropdown 390px fix, chevron affordance
    [x] WP-CLI hook-dump verified (empty anchors gone)
    [x] suppress_single_subcategory_nav() taxonomy correctness fix (full-review pass)
    [x] Cart/checkout table price-wrap fix (full-review pass)
    [x] Deployed, production-verified

[x] Batch B — IMPLEMENTED, CLOSED (§10a final verdict)
    [x] Keep gallery-default (catalog data re-verified live: 12 products, corrected distribution, §10.2)
    [x] Recompose PDP description: only the 2 proven-stable headings, full 12/12 catalog census (§10.4)
    [x] woocommerce_product_related_products_heading (native filter — corrected from the original botiga_-prefixed citation, §10.5) → curated heading
    [x] Related products stay native grid (shop_single_related_products_slider not enabled — §10.5)
    [x] Sticky mobile add-to-cart: assets/js/pdp-sticky-cta.js, proxy-only, observes form.cart (corrected from .summary, §10.6), MutationObserver state sync, no duplicated form state
    [x] Post-deploy fix: sticky-CTA premature-visibility-on-load bug found in production acceptance and fixed (§10.6)
    [x] PHP file split: inc/woocommerce/{archive,product-card,single-product}.php (§16 trigger met)
    [x] Acceptance pass (§10.8/§10a, whole-screen) at mobile/desktop, deployed, smoke-tested, production-verified

[x] Batch C — IMPLEMENTED / CONTENT PENDING (§12a final verdict)
    [x] gettext_with_context filter for "Shipment" (§12.1) — verified incl. AJAX fragment refresh
    [ ] Checkout privacy policy text (content from owner) → WooCommerce setting (§12.2) — OPEN, mechanism ready, wording not yet supplied
    [x] Payment method CSS card treatment (§12.3) — :has(input:checked), verified incl. AJAX fragment refresh
    [x] Form-language pass: selectWoo UI styled for country/state, native chevron for the ward select, cascade-collision fix (§12.5)
    [x] My Account reviewed — already-shipped light polish confirmed sufficient, nothing further added (§13)
    [x] Progress indicator: confirmed NOT implemented — rejected, re-verified still correct (§12.4)
    [x] Acceptance pass (§13b, whole-screen) at mobile/desktop, deployed, smoke-tested, production-verified — not fully CLOSED pending privacy content
```

---

*This document is the canonical contract for Storefront Composition V2. Implementation must not exceed the intervention ceiling in §3/§10.7/§13a, must not implement anything listed in §19, and must stop for founder review if a documented fix proves insufficient in practice.*
