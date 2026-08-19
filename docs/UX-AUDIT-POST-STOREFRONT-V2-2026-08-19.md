# Post-Storefront V2 UX/UI Audit — 2026-08-19

**Status: AUDIT ONLY. No runtime code, settings, or content changes were made in this pass.**

This is a new, independent audit phase — not a continuation or reopening of Storefront V2 (`docs/STOREFRONT-V2-IMPLEMENTATION.md`, CLOSED, historical, not modified by this document). It evaluates the live production site as a customer would experience it, not against the frozen V2 contract's own acceptance criteria.

---

## 0. Preflight

| Check | Result |
|---|---|
| Local `main` | `b40ab3c29f1ba6715755a7679ea54bd1e925f9b6` |
| `origin/main` | matches |
| Production `current` | `releases/20260819143928` |
| Production `SOURCE_COMMIT` | `891593b78160ef24883d7d62ce5cd62447cccff9` — matches expected |

Confirmed the live site corresponds to the closed Storefront V2 state before auditing began.

**Runtime/browser used:** Chromium via Playwright MCP (`mcp__playwright__*` tools). **No Safari or Firefox testing was performed** — this is a real methodology gap, not an oversight to hide (see §Not Tested).

### Real production inventory (WP-CLI, live-queried — not assumed from old docs)

| | |
|---|---|
| Published pages | 12 (`Trang chủ`, `Cửa hàng`, `Giỏ hàng`, `Thanh toán`, `Tài khoản`, `Giới thiệu`, `Liên hệ`, `Đặt mẫu theo yêu cầu`, `Chính sách vận chuyển`, `Chính sách đổi trả`, `Chính sách bảo mật`, `Điều khoản`, `Blog`) |
| Published products | **14** (grown from the 12 last reviewed in Batch B) — 9 simple, 5 variable |
| Product categories | 12 total, including **4 with zero products**: `Lyli Signature`, `Gấu bông len`, `Hộp quà`, `Hoa tulip`, `Bó hoa len có sẵn` (5, not 4 — corrected count) |
| Categories with real stock | `Móc khóa len` (11, children `Lyli Charm` 4 / `Lyli Tiny` 7), `Hoa len` (3, child `Hoa len lẻ` 3, grandchild `Hoa hướng dương` 3) |
| Blog posts | 5, all published the same day |
| Payment methods enabled | `bacs` (Chuyển khoản / VietQR), `cod` — confirmed live via `WC()->payment_gateways()` |
| Main nav items | 6: Trang chủ, Danh mục, Sản phẩm, Blog, Giới thiệu, Liên hệ |
| Registration | **Disabled** (`woocommerce_enable_myaccount_registration` = `no`) — guest checkout is the only customer path |

This inventory materially changed the audit route list from what an old contract would suggest: the catalog crossed the 12-product pagination threshold for the first time (new: pagination UI, untested before), and several categories exist with zero products (new dead-end-page surface).

---

## 1. Methodology

Evaluated as a customer performing real tasks (personas A–H from the brief), not as element-by-element component review. Primary desktop viewport: **1366×768** (per the task's explicit callout that this is where vertical-density problems hide that 1440×900 doesn't show). Also tested 1440×900 and 390×844 in depth, with spot checks elsewhere. Findings are backed by live DOM measurement (`getBoundingClientRect`, `getComputedStyle`), not visual impression alone, wherever a number is cited.

**Scenarios actually exercised:** homepage full-scroll (desktop + mobile), shop archive + pagination, 2 category archives (populated + empty), 3 product cards types, 2 simple PDPs, 1 variable PDP under a 9-option stress case (unselected/selected/synced states), sticky mobile CTA full state matrix on that same stress-case product, description recomposition on a 3-heading product, related products, empty cart, non-empty cart (add via genuine trusted click, not synthetic), checkout page (field markup + native HTML5 validation, no submission), My Account logged-out (blank submit blocked by design, wrong-credentials login), custom-order flow (homepage → chooser → destination → Zalo), 404, site search (real term, nonsense term), skip link, footer link inventory, back-to-top overlap check.

**Total distinct scenarios tested: 34.**

---

## 2. Findings

Severity: **P0** blocks purchase · **P1** significant confusion/friction/barrier · **P2** noticeable degradation, not blocking · **P3** polish/aesthetic debt.

---

### UX-001 — Homepage routes an in-stock category through the custom-order-only funnel

**SEVERITY:** P1 **TYPE:** content, hierarchy
**PAGE/FLOW:** Homepage → "Chọn sản phẩm có sẵn hoặc đặt mẫu riêng" 5-card chooser
**STATE:** default **VIEWPORT:** 1440×900 (confirmed present at 390×844 too)

**REPRODUCTION:** Load homepage, scroll to the 5-card chooser section, inspect card 03 "Hoa len đặt riêng."

**OBSERVED:** The card is labeled "Hoa len đặt riêng" ("custom-order-only yarn flowers") and links to `/dat-mau-theo-yeu-cau/` (the generic custom-order contact page). Two sections further down the *same homepage* ("Mẫu mới trong cửa hàng"), three real, purchasable sunflower products render with prices and working "Thêm vào giỏ hàng" buttons, drawn from the `Hoa hướng dương` category (3 products, confirmed via WP-CLI). The category the card claims is custom-order-only now has real stock.

**EVIDENCE:** `href` for the "Hoa len đặt riêng" link = `https://lylishop.online/dat-mau-theo-yeu-cau/` (captured via DOM query). Inventory: `hoa-huong-duong` term count = 3, all `simple`/`instock`.

**EXPECTED USER EXPERIENCE:** A customer who wants a sunflower and clicks the labeled-correct-sounding "Hoa len" card should land where they can buy one, not be routed into a generic "contact us to discuss custom work" page and an external Zalo hop for something already sitting in the catalog.

**USER IMPACT:** Real friction and drop-off risk for exactly the customers this card is supposed to serve — it actively steers them away from a one-click purchase into a multi-step, app-switching custom-order conversation for a product that doesn't need one.

**ROOT CAUSE:** Content authored when `Hoa len` had no in-stock products; the catalog grew and the homepage block wasn't revisited.

**RECOMMENDED DIRECTION:** Point the card at `/product-category/hoa-huong-duong/` (or the parent `hoa-len-le`/`hoa-len`) and relabel it to reflect that ready-made flowers now exist, reserving "đặt riêng" language for genuinely custom flower requests (colors/arrangements not in the catalog).

**LIKELY INTERVENTION:** content (edit the block/pattern's link + copy in the editor). **RISK:** low. **CONFIDENCE:** high. **DISPOSITION:** FIX NEXT.

---

### UX-002 — Policy pages are unreachable through navigation

**SEVERITY:** P1 **TYPE:** consistency, content, IA
**PAGE/FLOW:** sitewide (footer, header nav)
**STATE:** default **VIEWPORT:** all

**REPRODUCTION:** Inspect every `<a>` on the homepage and footer for links to `chinh-sach-doi-tra`, `chinh-sach-van-chuyen`, `dieu-khoan`, `chinh-sach-bao-mat`.

**OBSERVED:** Zero matches. `Chính sách đổi trả` (Return Policy), `Chính sách vận chuyển` (Shipping Policy), and `Điều khoản` (Terms) are published, real, well-written pages (confirmed by direct visit) with **no link anywhere on the site** — not in the footer's "KHÁM PHÁ" column, not in main nav, not on PDP, not on checkout. `Chính sách bảo mật` (Privacy) is reachable, but *only* via the `[privacy_policy]` shortcode link on the checkout page itself.

**EVIDENCE:** `document.querySelectorAll('a')` on homepage → 0 of 15 footer links point at any policy slug. Same footer markup is shared across pages (Botiga single footer), so this is sitewide, not homepage-specific.

**EXPECTED USER EXPERIENCE:** A buyer considering a custom/handmade purchase commonly wants to check the return or shipping policy *before* adding to cart — that's a normal, low-friction pre-purchase trust check, not an edge case.

**USER IMPACT:** These pages might as well not exist for a normal browsing customer. Trust-building content that was clearly written and approved (dated, formatted, on-brand) is invisible.

**RECOMMENDED DIRECTION:** Add the three pages to the footer's "KHÁM PHÁ" (or a new "Chính sách" column), matching the pattern that already surfaces Privacy on checkout.

**LIKELY INTERVENTION:** content (footer menu item addition — no code). **RISK:** low. **CONFIDENCE:** high. **DISPOSITION:** FIX NOW.

---

### UX-003 — 404 page is entirely in English

**SEVERITY:** P1 **TYPE:** content, consistency
**PAGE/FLOW:** any invalid URL
**STATE:** 404 **VIEWPORT:** 1440×900

**REPRODUCTION:** Navigate to any non-existent URL, e.g. `/san-pham-khong-ton-tai-xyz/`.

**OBSERVED:** "Oops! That page can't be found." / "It looks like nothing was found at this location. Maybe try one of the links below or a search?" / "Search products…" — Botiga's default 404 template strings, rendered verbatim in English, on an otherwise fully-Vietnamese site (confirmed: nav, footer, product pages, checkout, account, blog are all Vietnamese).

**EVIDENCE:** Screenshot captured; page `<title>` was correctly Vietnamese ("Không tìm thấy trang này"), but the *body content* is not — confirming the gap is specifically in Botiga's un-audited 404 template strings, not a site-wide locale problem.

**EXPECTED USER EXPERIENCE:** A shopper landing here (broken external link, mistyped URL, expired product page) should see the same Vietnamese-first experience as everywhere else.

**USER IMPACT:** Jarring language switch at exactly the moment a customer is already slightly frustrated (they just hit a dead link) — undermines the otherwise carefully localized feel of the site.

**RECOMMENDED DIRECTION:** Same mechanism already proven for "Shipment" (Batch C): a scoped `gettext`/`gettext_with_context` filter matched on Botiga's exact 404-template source strings.

**LIKELY INTERVENTION:** L2 (named, scoped gettext filter — same pattern as `commerce-ui.php`). **RISK:** low. **CONFIDENCE:** high. **DISPOSITION:** FIX NOW.

---

### UX-004 — Skip-link and other Botiga chrome strings remain English outside the audited commerce flows

**SEVERITY:** P2 **TYPE:** content, accessibility
**PAGE/FLOW:** sitewide
**STATE:** default **VIEWPORT:** all

**REPRODUCTION:** Inspect `.skip-link` text on any page.

**OBSERVED:** "Skip to content" — English. This is the very first thing a screen-reader or keyboard user encounters on every single page.

**EXPECTED USER EXPERIENCE:** Consistent Vietnamese, especially for an accessibility-first element encountered before any other content.

**USER IMPACT:** Assistive-technology users get an English string before anything else, every page load — a small but first-impression-forming gap, and disproportionately affects the audience least able to route around it.

**RECOMMENDED DIRECTION:** Same `gettext` mechanism as UX-003; audit for other un-translated Botiga chrome strings (search placeholder, "Add to cart" fallback text if any theme default ever surfaces, WooCommerce core admin-facing strings are out of scope) while implementing this.

**LIKELY INTERVENTION:** L2. **RISK:** low. **CONFIDENCE:** medium (this specific string confirmed; "other Botiga chrome strings" is a hypothesis, not exhaustively verified). **DISPOSITION:** FIX NOW (bundle with UX-003 — same mechanism, same audit pass).

---

### UX-005 — Brand messaging skews keychain-only despite a 3-family catalog

**SEVERITY:** P2 **TYPE:** content, hierarchy
**PAGE/FLOW:** Homepage hero, "VỀ LYLI SHOP" brand story, custom-order page H2
**STATE:** default **VIEWPORT:** 1440×900

**OBSERVED:** Homepage H1 ("Móc khóa len handmade cute cho những món quà nhỏ có cảm xúc"), the brand-story section heading, and the custom-order page's H2 ("Liên hệ đặt móc khóa len handmade theo yêu cầu") are all keychain-specific, even though: the catalog has three real product families (keychains, flowers, plush is category-only today), the footer/brand blurb correctly says "móc khóa len handmade, hoa len và thú bông len," and the custom-order page's own body paragraph correctly broadens to all three. The *headings* narrow the message; the *body copy* doesn't.

**EXPECTED USER EXPERIENCE:** Persona A ("what does Lyli sell?") should get a headline answer that matches the actual breadth of what's for sale, especially since flowers are now real, in-stock, and merchandised on the same page.

**USER IMPACT:** Understates the catalog to a first-time visitor; mild, not blocking — a visitor who only reads headlines could believe this is a keychain-only shop.

**RECOMMENDED DIRECTION:** Broaden the H1/H2 language to match the footer blurb's framing ("len handmade" generally, or explicitly "móc khóa, hoa len và thú bông len").

**LIKELY INTERVENTION:** content only. **RISK:** low. **CONFIDENCE:** high. **DISPOSITION:** FIX NEXT.

---

### UX-006 — Final homepage CTA block uses an off-brand background color

**SEVERITY:** P2 **TYPE:** visual polish, consistency
**PAGE/FLOW:** Homepage, final CTA before footer ("Tìm một món quà nhỏ cho người bạn thương")
**STATE:** default **VIEWPORT:** 1440×900 and 390×844 (confirmed both)

**OBSERVED:** Computed `background-color: rgb(194, 195, 210)` — a cool blue-gray, not a token from the brand's warm cream/terracotta palette used everywhere else on the page (`--lyli-color-cream`, `--lyli-color-warm-white`, `--lyli-color-primary`). This sits at the single most conversion-relevant position on the homepage — the last CTA before a visitor either converts or leaves via the footer.

**EVIDENCE:** `getComputedStyle` on `.lyli-final-cta` → `rgb(194, 195, 210)` (#C2C3D2). For comparison, every other section on the page uses `--lyli-color-cream` (`#FBEFE5`-family) or `--lyli-color-warm-white`.

**ROOT CAUSE (moderate confidence):** Almost certainly an accidental default WordPress block-editor color swatch applied instead of a theme.json brand token when this block's background was set in the editor — not a deliberate design choice. No other section on the site uses this hue anywhere.

**RECOMMENDED DIRECTION:** Replace with `--lyli-color-cream` or `--lyli-color-primary`-tinted background, matching the CTA block pattern used earlier on the same page ("Có một ý tưởng chưa thấy trong cửa hàng?" uses a correct dusty-rose/cream tone).

**LIKELY INTERVENTION:** content (block background-color setting in the editor) — no code. **RISK:** low. **CONFIDENCE:** high. **DISPOSITION:** FIX NOW.

---

### UX-007 — Shop archive shows minimal product content at 1366×768

**SEVERITY:** P2 **TYPE:** responsive, hierarchy
**PAGE/FLOW:** `/cua-hang/`
**STATE:** default **VIEWPORT:** 1366×768

**OBSERVED:** First product row begins at `y = 548.7px` of a 768px-tall viewport — only the top ~28% of the viewport shows any product, and not a full row. Batch A.2's own acceptance bar ("a meaningful part of the first product row must be visible... without scrolling") was verified at 1440×900 but does not hold at this shorter, very common laptop viewport height.

**EVIDENCE:** `document.querySelector('ul.products li.product').getBoundingClientRect().top` = 548.7 at 1366×768.

**EXPECTED USER EXPERIENCE:** The archive's own "catalog-first" design intent (§6a of the V2 contract) should hold across common laptop screens, not just 900px-tall viewports.

**RECOMMENDED DIRECTION:** Investigate whether breadcrumb/header/eyebrow/H1/intro/chip/toolbar vertical spacing can compress specifically at `max-height`-aware or shorter-viewport breakpoints — needs live measurement-driven iteration, not a guess from this audit alone.

**LIKELY INTERVENTION:** L1 CSS. **RISK:** medium (touches the already-tuned A.2 spacing; needs care not to regress the 1440/1920 cases that were correctly tuned). **CONFIDENCE:** high (measurement) but the *fix approach* is medium-confidence. **DISPOSITION:** FIX NEXT.

---

### UX-008 — Pagination touch targets are below the site's own 44px convention

**SEVERITY:** P2 **TYPE:** accessibility, consistency
**PAGE/FLOW:** `/cua-hang/`, page 2+ (newly relevant — catalog just crossed 12 products)
**STATE:** default **VIEWPORT:** 1366×768 (applies at all sizes — no responsive rule found)

**OBSERVED:** `.woocommerce-pagination a`/`span.page-numbers` measure **36×36px**. The theme's own established convention elsewhere (`.woocommerce ul.products li.product .button { min-height: 44px; }`) is not applied here.

**EVIDENCE:** Live `getBoundingClientRect()` on all 3 pagination controls → each 36×36px.

**USER IMPACT:** Low on desktop (mouse-driven), more relevant on touch devices — this is the *first time* pagination has ever appeared on this catalog (previously ≤12 products, single page), so it was never in scope for prior touch-target audits.

**RECOMMENDED DIRECTION:** Raise to 44×44px minimum, consistent with the rest of the site.

**LIKELY INTERVENTION:** L1 CSS. **RISK:** low. **CONFIDENCE:** high. **DISPOSITION:** FIX NEXT.

---

### UX-009 — Policy/legal page body text is not width-constrained for reading

**SEVERITY:** P2 **TYPE:** accessibility, consistency
**PAGE/FLOW:** `/chinh-sach-doi-tra/` (representative of all WP `page`-type long-form content)
**STATE:** default **VIEWPORT:** 1440×900

**OBSERVED:** Body paragraph width = **1110px** (~133 characters per line at 16px). For comparison, the blog single-post template — the site's other long-form reading surface — constrains body text to **730px** (~88 characters per line).

**EVIDENCE:** `getBoundingClientRect().width` measured directly on both templates in the same session (1110px vs. 730px).

**EXPECTED USER EXPERIENCE:** Comfortable reading width is typically cited around 50–75 characters/line (general typographic guidance, consistent with this project's own documented design convention of "~65 characters" for body text). The policy page is roughly 1.5× that.

**USER IMPACT:** Doesn't block reading, but makes already-legal-flavored content measurably harder to scan — ironic given how well-written and formatted the copy itself is (§UX-002's underlying pages).

**RECOMMENDED DIRECTION:** Apply the same reading-width constraint the blog template already uses to generic WP page content (or at minimum to the 4 policy pages specifically).

**LIKELY INTERVENTION:** L1 CSS. **RISK:** low. **CONFIDENCE:** high. **DISPOSITION:** FIX NEXT.

---

### UX-010 — No inline explanation for a disabled variable-product Add-to-cart button

**SEVERITY:** P2 **TYPE:** affordance, friction
**PAGE/FLOW:** any variable PDP, unselected state
**STATE:** no variation selected **VIEWPORT:** 1366×768 and 390×844

**OBSERVED:** The only signal that "Thêm vào giỏ hàng" is inactive is CSS dimming (`opacity: 0.7` + `disabled`/`wc-variation-selection-needed` classes) — no `.woocommerce-error`/notice/inline text tells the shopper *why*, beyond the "Chọn mẫu" label sitting above the (also unlabeled-as-required) select.

**EVIDENCE:** DOM check on `moc-khoa-nam-mini-len-handmade-9-mau...` confirmed no notice element exists in this state.

**USER IMPACT:** Moderate — the select is directly above the button and reasonably discoverable, so most shoppers will self-correct quickly, but a less experienced or distracted shopper gets a "why won't this work" moment with no explicit text answer.

**RECOMMENDED DIRECTION:** Consider a small, quiet inline hint ("Vui lòng chọn mẫu trước khi thêm vào giỏ hàng") near the button when disabled — but validate this is actually needed before building; it risks feeling redundant given the label is already adjacent.

**LIKELY INTERVENTION:** L2 (small conditional notice, presentation-only). **RISK:** low. **CONFIDENCE:** medium (real gap, but user-testing would strengthen the case before committing effort). **DISPOSITION:** FIX NEXT / POLISH — recommend validating with the owner or a couple of real users first.

---

### UX-011 — Variant selection is text-only for visually-distinct options

**SEVERITY:** P3 **TYPE:** affordance
**PAGE/FLOW:** variable PDPs with visually distinct variants (e.g., 9-color/character keychain)
**STATE:** default

**OBSERVED:** A native `<select>` with plain-text option labels ("Mũ vịt", "Mũ hổ", "Mũ mèo"...) is the only way to choose among 9 visually distinct character variants. Option names are descriptive (not just color codes), which mitigates this somewhat.

**EXTERNAL UX PRINCIPLE:** Baymard Institute's product-page research consistently finds visual/swatch variant selectors outperform text dropdowns specifically when variants differ *visually* (color, character, pattern) rather than by a non-visual spec (size, material). **OBSERVED ON LYLI:** applies directly here — every variable product in the catalog varies by appearance, not spec.

**RECOMMENDED DIRECTION:** A thumbnail/swatch variation picker would likely help, but this is explicitly an **L4/L5-level custom component** (a real variation-UI build, not a CSS restyle of the native select) — outside any current intervention ceiling and outside this audit's authority to greenlight.

**LIKELY INTERVENTION:** L4/L5 (custom component). **RISK:** high if attempted casually (variation-selection correctness is commerce-critical). **CONFIDENCE:** medium. **DISPOSITION:** DEFER — flag for a separate, founder-approved, properly-scoped pass only. Do not fold into a routine CSS/content pass.

---

### UX-012 — Sticky mobile PDP CTA reads as a utility bar, not a Lyli surface

**SEVERITY:** P3 **TYPE:** visual polish
**PAGE/FLOW:** any PDP, mobile, scrolled state
**STATE:** sticky bar visible **VIEWPORT:** 390×844

**OBSERVED:** The sticky bar is a flat, full-width rectangle with a hard top border and no corner radius. Every other interactive surface on the site — buttons (pill radius), cards (16px radius), payment-method cards (Batch C, `var(--lyli-radius-md)`), inputs (`--lyli-radius-sm`) — uses the brand's rounded-corner language. The sticky bar is the one exception.

**USER IMPACT:** Low individually, but this is exactly the "does it look bolted-on" risk the original Batch B brief explicitly asked to guard against, and on inspection, it mildly does.

**RECOMMENDED DIRECTION:** Add a top-edge radius (e.g., `border-radius: var(--lyli-radius-md) var(--lyli-radius-md) 0 0`) consistent with the rest of the surface language.

**LIKELY INTERVENTION:** L1 CSS, one rule. **RISK:** low. **CONFIDENCE:** high. **DISPOSITION:** POLISH.

---

### UX-013 — Botiga's back-to-top button overlaps the footer "Tài khoản" link on mobile

**SEVERITY:** P2 **TYPE:** functional, affordance
**PAGE/FLOW:** any page, scrolled to footer
**STATE:** default **VIEWPORT:** 390×844

**OBSERVED:** The fixed-position back-to-top button (Botiga native, `.back-to-top`) sits at `x:297–345, y:766–814`. The footer's "Tài khoản" link's clickable row spans `x:16–360, y:801–824`. The two rectangles overlap — the button visually and functionally covers roughly the right 48px of the account link's tap target.

**EVIDENCE:** Live `getBoundingClientRect()` intersection check confirmed `overlap: true`.

**USER IMPACT:** The account link is still reachable via the header icon, so this isn't a dead end — but it's a real, measurable tap-target collision on a component (back-to-top) that exists on every single page.

**RECOMMENDED DIRECTION:** Increase the back-to-top button's bottom offset on mobile, or add right-padding to the footer's copyright row so the two never occupy the same space.

**LIKELY INTERVENTION:** L1 CSS. **RISK:** low. **CONFIDENCE:** high. **DISPOSITION:** FIX NEXT.

---

### UX-014 — Empty product categories are live, dead-end pages with no recovery path

**SEVERITY:** P3 **TYPE:** content, edge-state
**PAGE/FLOW:** `/product-category/lyli-signature/` (and 4 other zero-product categories)
**STATE:** empty category **VIEWPORT:** 1366×768

**OBSERVED:** Correctly excluded from chip navigation (count-gating works as designed), but the URL is still live, returns 200, and shows only the generic "Không tìm thấy sản phẩm nào khớp với lựa chọn của bạn" message with breadcrumb but no link back to a populated category or the main shop.

**USER IMPACT:** Low under normal browsing (chip-gating prevents most customers from ever landing here), but a real risk if search engines index and rank the page, or a customer follows a stale external link.

**RECOMMENDED DIRECTION:** Either add a "Xem tất cả sản phẩm" fallback link on any empty archive, or set genuinely-inactive categories to `noindex` until populated.

**LIKELY INTERVENTION:** L2 (fallback link) or content (SEO setting). **RISK:** low. **CONFIDENCE:** high. **DISPOSITION:** POLISH.

---

### UX-015 — No-result search offers no explicit retry guidance

**SEVERITY:** P3 **TYPE:** friction, content
**PAGE/FLOW:** `/?s=<nonsense>`
**STATE:** zero results **VIEWPORT:** 1440×900

**OBSERVED:** Shows the generic "no products match" message plus category chips (an implicit recovery path), but no explicit "try a different term" copy or a direct "browse all products" link.

**RECOMMENDED DIRECTION:** Minor copy addition to the empty-state message.

**LIKELY INTERVENTION:** content. **RISK:** low. **CONFIDENCE:** medium. **DISPOSITION:** POLISH.

---

## 3. DO NOT FIX — investigated and confirmed correct

- **Empty cart state** — clear Vietnamese message + primary-styled "Quay trở lại cửa hàng" CTA. No gap found.
- **Wrong-credentials login error** — specific, explains the mistake, suggests trying email instead of username. Textbook of what the brief asked for ("explain mistakes rather than merely reject them"). No gap found.
- **Custom-order destination (Zalo hand-off)** — appropriate, low-friction mechanism for a small handmade shop; not a broken or fake funnel. The *routing into it* (UX-001) is the defect, not the destination itself.
- **Category chip count-gating (≥2 real children)** — correctly hides `Lyli Signature` (0 products), correctly shows `Lyli Charm`/`Lyli Tiny` and `Hoa len`/`Móc khóa len`. Working exactly as designed.
- **PDP description recomposition** — held up well on a complex, 3-heading, list-heavy, 9-variant product; correct order, no lost content, no duplication.
- **Related products relevance** — on-topic across every PDP sampled (sunflower → sunflower/tulip family; keychain → keychain family).
- **Sticky CTA functional logic** (visibility timing, state sync via MutationObserver, unavailable→focus routing) — fully correct even under a 9-option stress test. Only the *visual* treatment is flagged (UX-012), not the logic.
- **AJAX add-to-cart** — works correctly for genuine user interaction (verified via a real trusted click, not a synthetic one).
- **Search relevance and result-page localization** — Vietnamese heading, relevant results, functions correctly.
- **Native checkout field order / progress-indicator rejection** — re-confirmed still correct; no new evidence surfaced that would reopen either decision.

---

## 4. Not Tested

| Item | Reason |
|---|---|
| Live "Đặt hàng" submission / server-rendered checkout validation errors | Blocked by the session's own safety classifier (correctly — risks placing a real order). Assessed indirectly via required-field markup and native HTML5 constraint validation instead. |
| My Account logged-in (Dashboard/Orders/Addresses) | No safe, legitimate authenticated session available; owner credentials were not requested. |
| Safari, Firefox | Only Chromium (Playwright MCP) was available in this environment. |
| 200% browser zoom | Not exercised — scope/time; flagged as a follow-up. |
| Full 9-viewport matrix (360×800, 430×932, 768×1024, 820×1180, 1920×1080, 844×390 landscape) | Sampled at 1366×768, 1440×900, 390×844 in depth with the rest spot-checked or skipped under time budget — a real methodology gap, noted rather than hidden. |
| Valid-coupon checkout flow | No safe real test coupon existed; per instructions, one was not fabricated for convenience. |

---

## 5. Prioritization

**TOP 5 USER-IMPACT ISSUES**
1. UX-001 — homepage misroutes a now-in-stock category into the custom-order funnel
2. UX-002 — policy pages unreachable through navigation
3. UX-003 — 404 page entirely in English
4. UX-007 — shop archive shows almost no product content at 1366×768
5. UX-010 — disabled variable-product CTA has no inline explanation

**TOP 5 VISUAL/POLISH ISSUES**
1. UX-006 — off-brand gray-blue background on the homepage's final CTA
2. UX-012 — sticky CTA's missing border-radius reads as bolted-on
3. UX-009 — policy-page reading column too wide vs. the blog's own pattern
4. UX-005 — keychain-only headline language undersells the catalog
5. UX-015 — no-result search state is visually fine but under-explained

**TOP 5 MOBILE ISSUES**
1. UX-013 — back-to-top button overlaps the footer account link at 390px
2. UX-008 — pagination touch targets below the 44px convention (also desktop)
3. UX-001 — the stale routing card is equally present on mobile
4. UX-012 — sticky CTA radius gap most visible on the device it's built for
5. Hero vertical space: ~88% of the 390×844 first viewport is text before any product image appears (`imgTop: 742.8px` of 844px) — noted as supporting evidence for UX-005/homepage messaging rather than a separate numbered finding, since no *functional* defect exists, only a density observation worth the design team's attention.

**TOP 5 EASY WINS**
1. UX-006 — swap one background-color token (content-only change)
2. UX-002 — add 3 footer links (content-only change)
3. UX-012 — one CSS border-radius rule
4. UX-013 — one CSS offset adjustment
5. UX-008 — one CSS min-width/min-height rule on existing pagination selectors

**THINGS THAT LOOK ODD BUT SHOULD NOT BE CHANGED**
- The 3-level breadcrumb depth for flowers (`Hoa len / Hoa len lẻ / Hoa hướng dương`) vs. 2-level for keychains (`Móc khóa len / Lyli Tiny`) — looks inconsistent at a glance, but reflects genuinely different catalog structures (flowers have a "loose stems" sub-family concept keychains don't); not a defect to normalize away.
- Sunflower product descriptions repeating the price inline ("giá 90.000đ/bông") right below the price block — looks redundant, but it's owner-authored content describing per-stem pricing logic for a multi-unit product, not a code duplication bug.

---

## 6. Recommended next passes

**PASS 1 — correctness/content (no code, fastest to ship):** UX-001, UX-002, UX-005, UX-006. All are content-editor changes.

**PASS 2 — small scoped code (low risk, high confidence):** UX-003, UX-004 (gettext filters, same proven mechanism as "Shipment"), UX-008, UX-012, UX-013 (single-rule CSS fixes).

**PASS 3 — measurement-driven responsive work (needs iteration, moderate risk):** UX-007 (1366×768 archive density), UX-009 (policy reading width).

**PASS 4 — validate-before-building:** UX-010 (confirm the inline-hint need with real users/owner before adding UI), UX-014/UX-015 (low-urgency polish).

**DEFER, separate founder-approved scope:** UX-011 (visual variant picker — real L4/L5 component work, not a quick pass).

---

## 7. Final verdict

1. **Does Lyli Shop currently feel production-ready to a normal customer?** Yes, for the core buy flow — cart, checkout, payment, and the primary keychain catalog are solid, well-localized, and functionally correct. It is *not* yet fully polished at the edges: policy discoverability, the 404/skip-link language gap, and one stale homepage routing card are real, fixable rough edges a first-time visitor could plausibly hit.
2. **3 weakest UX areas:** (1) policy-page discoverability, (2) homepage category-routing staleness as the catalog has grown past what the copy assumed, (3) shorter-desktop-viewport archive density (1366×768).
3. **3 ugliest/least-polished visual areas:** (1) the off-brand gray-blue final CTA block, (2) the sticky mobile CTA's missing radius, (3) the fully-English 404 page sitting inside an otherwise carefully localized brand.
4. **What is currently over-designed?** Nothing found — no section reads as needlessly elaborate for a small handmade shop; the site is, if anything, restrained.
5. **What is currently under-designed?** Edge/dead-end states (empty categories, no-result search, 404) — all functionally fine but visibly less cared-for than the core commerce flow, and the homepage's final conversion moment (UX-006).
6. **What should absolutely not be touched?** The cart, checkout, payment-method, and sticky-CTA *logic* (all correct); category chip gating; the description recomposition mechanism; native checkout field order; the rejected progress indicator.
7. **If only one more UX pass were allowed, what should it contain?** Pass 1 above — the four content-only fixes (stale routing card, policy footer links, headline breadth, off-brand CTA color) — because they're the highest-impact, lowest-risk, zero-code changes available, directly addressing the top-3 user-impact issues without touching a single line of PHP/CSS/JS.

---

*This document is a new, independent audit phase. It does not modify, supersede, or reopen `docs/STOREFRONT-V2-IMPLEMENTATION.md`, which remains the closed, historical record of Storefront V2.*
