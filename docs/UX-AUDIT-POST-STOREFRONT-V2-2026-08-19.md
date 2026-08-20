# Post-Storefront V2 UX/UI Audit — 2026-08-19

**Status: REMEDIATED. Findings UX-001 through UX-016 have final dispositions — see §9. The original audit pass (below, §0–§8) is preserved as the historical record of what was found; §9 documents what was actually done about it, deployed 2026-08-20.**

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

**Runtime/browser used:** Chromium via Playwright MCP (`mcp__playwright__*` tools). **No Safari or Firefox testing was performed** — this is a real methodology gap, not an oversight to hide (see §Not Tested). **Still true as of the remediation pass** — only Chromium was available in this environment on both dates; see §9 for the explicit re-statement.

### Real production inventory (WP-CLI, live-queried — not assumed from old docs)

**Correction (remediation pass, 2026-08-20):** this table as originally written was internally inconsistent — it named 13 pages while stating "12", and stated "12 total" categories while separately listing and self-correcting to 5 empty ones. Both were arithmetic slips in the original write-up, not a sign the underlying live query was wrong. Corrected counts for the original audit date, plus the catalog had already grown again by the time of the remediation pass one day later (a real, expected, live-shop growth pattern, not a further inconsistency) — both snapshots are kept below rather than silently overwritten:

| | 2026-08-19 (original audit) | 2026-08-20 (remediation pass) |
|---|---|---|
| Published pages | 13 | 14 (new: `Hoa len` landing page, `hoa-len-handmade`) |
| Published products | 14 (9 simple, 5 variable) | 16 |
| Product categories (total) | 12 | 13 (new: `Hoa giỏ`, populated) |
| Product categories (empty) | 5: `Lyli Signature`, `Gấu bông len`, `Hộp quà`, `Đặt mẫu theo yêu cầu`, `Bó hoa len có sẵn` | 5 (same 5 — still empty) |
| Product categories (populated) | 7 | 8 |
| Blog posts | 5, all published the same day | 5 (unchanged) |
| Payment methods enabled | `bacs` (Chuyển khoản / VietQR), `cod` | unchanged |
| Main nav items | 6: Trang chủ, Danh mục, Sản phẩm, Blog, Giới thiệu, Liên hệ | unchanged |
| Registration | Disabled (`woocommerce_enable_myaccount_registration` = `no`) | unchanged |

`Đặt mẫu theo yêu cầu` (the custom-order routing category, term 21) was omitted from the original audit's empty-category list by mistake — it is real, empty, and top-level, exactly like the other 4. Corrected here.

This inventory materially changed the audit route list from what an old contract would suggest: the catalog crossed the 12-product pagination threshold for the first time (new: pagination UI, untested before), and several categories exist with zero products (new dead-end-page surface, since remediated for navigability — see UX-014 in §9).

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

## 8. Addendum — soft catalog navigation (owner-reported, implemented and closed)

**Added 2026-08-19, same day as the original audit, after a direct owner report** (not one of the 15 numbered findings above — a new, distinct issue). Unlike the rest of this document, this addendum covers a pass that was **implemented and deployed**, not merely audited.

### UX-016 — Category drill-down had no deterministic way back up or sideways

**SEVERITY:** P1 (owner-reported) **TYPE:** hierarchy, affordance, mobile
**PAGE/FLOW:** any `product_cat` category archive
**STATE:** leaf category, or any category with <2 populated children

**OWNER REPORT (verbatim intent):** category navigation felt rigid; drilling into a category left no obvious way to return to Cửa hàng, go up to a parent, or switch to a sibling, without relying on browser Back; existing chips read as "heavy outlined pills, disconnected from the softer handmade character of Lyli Shop."

**REPRODUCED, root cause confirmed:** Botiga's own sub-category chips (`botiga_shop_page_header_sub_category_links()`) only ever list a term's *direct children*, and only render at all when `suppress_single_subcategory_nav()` (existing count-gating, Batch A.2) allows ≥2 of them. Live taxonomy query (WP-CLI, not assumed) found every current leaf category — `Lyli Charm`, `Lyli Tiny`, `Hoa hướng dương`, `Hoa tulip`, `Hoa giỏ` — rendered **zero** taxonomy navigation beyond the small breadcrumb text. Confirmed live on `/product-category/hoa-huong-duong/`: breadcrumb only, no chip row at all.

**Live taxonomy graph** (`get_terms()`, hide_empty:false, live-queried — not the stale snapshot in the task brief):

```
Móc khóa len (11)              Gấu bông len (0, empty)
├─ Lyli Signature (0, empty)   Hộp quà (0, empty)
├─ Lyli Charm (4)               Đặt mẫu theo yêu cầu (0, empty)
└─ Lyli Tiny (7)

Hoa len (3)
├─ Bó hoa len có sẵn (0, empty)
├─ Hoa giỏ (1)
└─ Hoa len lẻ (2)
   ├─ Hoa hướng dương (1)
   └─ Hoa tulip (1)
```

Max depth 2. 5 top-level categories, 3 empty (correctly excluded from all navigation, per contract).

### Candidates reviewed

**A — Breadcrumb + compact up-link + child/sibling pills.** Clear separation of concerns; scales to any depth. *Chosen*, in a refined single-row form (see below).

**B — Improved breadcrumb hierarchy + one unified current/sibling chip row, no separate up-link.** Rejected: breadcrumb links are small, low-contrast text — explicitly disqualified by the brief as "the only way back up." Folding "exit to parent" and "switch to sibling" into visually identical chips would blur a real semantic difference (leaving the current branch entirely vs. staying within it).

**C — Compact taxonomy trail + "Xem tất cả …" contextual control + child choices.** Rejected: "view all" framing doesn't naturally express *both* "go up" and "switch sideways" — would still need a separate sibling row, arriving at something functionally identical to A with an extra conceptual layer and no real benefit.

### Chosen model

One compact row, rendered only on category archives (`is_product_category()`), hooked to WooCommerce's native `woocommerce_before_shop_loop` (priority 5) — deliberately **not** Botiga's own gated header function, so it always fires regardless of Botiga's ≥2-children gate:

```
[← Parent-or-Cửa-hàng]  [Sibling 1]  [Sibling 2 (current, filled)]  [Sibling 3]
```

- **Up target:** always shown (ancestor/exit navigation is not subject to the "does this give a real choice" gate — a lone leaf still needs a deterministic way back). Parent term if `$term->parent`, else the shop archive URL, labeled "Cửa hàng".
- **Siblings:** other populated terms sharing the current term's parent (`get_terms(['parent' => $term->parent, 'hide_empty' => true])`), current one marked `aria-current="page"`. Rendered only when ≥2 total (current + ≥1 real alternative) — an empty sibling never appears, a lone sibling group (just the current term, no alternative) hides the row entirely rather than showing a pointless single chip.
- Fully derived from `get_queried_object()`/`get_terms()` at request time — **zero hardcoded category names**, verified by testing against `Lyli Signature`'s and `Hoa giỏ`'s live behavior without any special-casing in the code.
- Botiga's own child-chip row (existing, unmodified, still count-gated ≥2) renders independently, below or absent as before — the two mechanisms solve different problems and don't replace each other.

**Chip visual treatment** (owner's "heavy outlined pills" complaint): root-caused via computed style, not guessed — `.category-button`'s fill (`--lyli-color-warm-white`) was identical to the page canvas behind it, so only the 1px border was ever visible, reading as a hollow wireframe outline rather than a filled pill. Fixed by giving chips a real, visibly-different fill (`--lyli-color-cream`) and removing the now-redundant border — applies to Botiga's own native chips too (shop-root and child chips), not just the new component, so the softening is sitewide and consistent. Current-state chips reuse the exact filled-primary-circle language the site already established for pagination's current-page number, rather than inventing a second "selected" pattern.

**Two real defects found and fixed during production acceptance, before this was reported closed:**
1. Botiga's own chips (inside `.woocommerce-page-header`) are styled by a two-class selector (`.woocommerce-page-header .category-button`) that beat this theme's original bare one-class rule outright — live-verified the new taxonomy-nav chips got the soft cream fill correctly while Botiga's own shop-root/child chips stayed on the old white-fill/near-black-2px-border look. Fixed by matching Botiga's ancestor-qualified specificity.
2. Botiga's global `a:focus { outline: thin dotted }` (higher specificity than this theme's bare `:focus-visible` rule) silently won on any plain-anchor chip — live-verified a focused chip fell back to a thin dotted ring, nearly invisible on a dark-filled current chip since its color inherits `currentColor` (white-on-dark). Fixed with a scoped, matching-specificity override for the two link types this pass touches (not a sitewide fix — that pre-existing gap likely affects other plain links too, out of scope for this pass, worth a future audit finding).

### Implementation

- **PHP:** `render_taxonomy_nav()`, `inc/woocommerce/archive.php` (existing file, same "archive presentation" concern already owned there — not a new module, per the task's own "don't build a directory taxonomy more complex than the shop" instruction).
- **CSS:** `style.css` — `.lyli-taxonomy-nav`/`.lyli-taxonomy-nav-up`/`.lyli-taxonomy-nav-siblings` (new, `lyli-` prefixed), `.category-button` fill/height/focus fixes (shared with Botiga's native chips).
- **JS:** 0 (none needed or added).
- **Template overrides:** 0.

### Measurements — `firstProductTop` before/after (no regression to UX-007)

| State | Viewport | Before | After | Δ |
|---|---|---|---|---|
| Shop root (untouched by this pass) | 1366×768 | 548.7px (71.4%) | 553.8px (72.1%) | +5.1 (measurement noise, not a real change — shop root's own render path was never touched) |
| Móc khóa len (parent) | 1366×768 | 487.7px (63.5%) | 530.9px (69.1%) | +43.2 |
| Móc khóa len (parent) | 390×844 | 421.9px (50.0%) | 465.0px (55.1%) | +43.1 |
| Lyli Charm (leaf, was a dead end) | 1366×768 | 420.9px (54.8%) | 468.9px (61.1%) | +48.0 |
| Lyli Charm (leaf) | 1440×900 | 421.9px (46.9%) | 469.9px (52.2%) | +48.0 |
| Lyli Charm (leaf) | 390×844 | 355.0px (42.1%) | 403.0px (47.8%) | +48.0 |
| Hoa hướng dương (deep leaf, was a dead end) | 390×844 | 486.8px (57.7%) | 550.0px (65.1%) | +63.2 |

Every increase is genuinely new, previously-absent navigation content (a leaf category had *zero* chip content before), not padding inflation — each state's post-change percentage still sits at or below the shop root's own pre-existing 71–72% baseline (a state this pass deliberately left untouched), so no new worst-case was created relative to what the site already shipped.

### Acceptance — verified live on production

- **Deep-link entry** (fresh navigation, no history): `Lyli Charm`, `Hoa tulip`, `Hoa hướng dương` all render correct up/sibling context with zero prior state.
- **Sibling switching:** clicking a sibling chip on `Hoa tulip` correctly navigates to `Hoa hướng dương` and back.
- **Up navigation:** clicking "← Hoa len lẻ" from `Hoa hướng dương` correctly lands on the parent, which itself shows a correct further "← Hoa len" + sibling `Hoa giỏ` — verified the model composes correctly at every depth, not just depth 1.
- **Mobile 360×800, 390×844, 430×932, landscape 844×390, tablet 768×1024:** zero horizontal overflow at any width; single-row layout holds; touch targets measured at 44px minimum (chips and up-link both).
- **Keyboard/accessibility:** every control is a real `<a href>` (no `div onclick`); `aria-current="page"` on the current chip; `<nav aria-label="Điều hướng danh mục">` landmark; visible `:focus-visible` ring confirmed after the specificity fix above.
- **Regression:** zero empty product-loop anchors (Batch A), sticky CTA + description recomposition + related grid intact (Batch B), Vận chuyển translation + privacy copy + payment cards intact (Batch C), zero console errors on every page checked (home, shop, all 4 taxonomy depths, PDP, cart, checkout, account, blog).

### Production

Deployed across three same-day commits as issues were found during acceptance (not one blind deploy): `70dc9ee` (feature), `0238056` (chip-fill specificity fix), `d6c871f` (focus-visible fix). Final production release `20260819201428`, source `d6c871f676211e74bb93f3394eb24ffac895fdb6`.

---

## 9. Remediation pass — final dispositions (2026-08-20)

Every finding below was re-reproduced live on current production before any change — none was implemented from the original write-up alone. Baseline before this pass: main `bfbd7bc7add837db63fe9de3b6e6caa7e52d87ec`, production release `20260819201428`, source `d6c871f676211e74bb93f3394eb24ffac895fdb6`.

**UX-001 — Homepage Hoa len routing**
**STATUS: FIXED** (found already partially fixed independently; completed the remainder) **IMPLEMENTED BY:** content only, no commit (WP page content, applied before this pass's own code changes) **PRODUCTION VERIFIED:** yes
Re-reproduced first: the owner/editor had already replaced the homepage's "Hoa len đặt riêng" card with "Hoa len," linking to a new, real, dedicated landing page (`/hoa-len-handmade/`, WP page 279) that correctly routes to the populated `Hoa len lẻ` category and embeds live product grids for `Hoa hướng dương`/`Hoa tulip` — found independent of this session, between the original audit and this remediation pass. The core defect (routing an in-stock category through the custom-order-only funnel) is resolved. Residual, minor, **not fixed in this pass**: the new landing page itself also links to `Bó hoa len có sẵn`, which is still genuinely empty (0 products) — a shopper clicking it lands on an empty archive, though now with UX-016's up/sibling recovery navigation rather than a hard dead end (UX-014 fix directly helps here too). Left as owner-content, out of this pass's authority to edit further.

**UX-002 — Policy discoverability**
**STATUS: FIXED** **IMPLEMENTED BY:** `c59980a` **PRODUCTION VERIFIED:** yes
Re-confirmed still true (zero policy links anywhere). Added all 4 published policy pages to the existing slim copyright bar (paired with "Tài khoản," not a new 4th footer column). Verified live: all 4 hrefs correct, no mobile overlap with the back-to-top button (see UX-013).

**UX-003 — Vietnamese 404**
**STATUS: FIXED** **IMPLEMENTED BY:** `da23979` (initial 3 strings) + `cbd3889` (found a 4th, "Most Popular," during production acceptance) **PRODUCTION VERIFIED:** yes
Exact source strings verified against the deployed Botiga 2.4.7 source (`inc/template-tags.php`, `header.php`) before writing any filter. Scoped `gettext` filter, domain=`botiga` + exact source string. All 4 confirmed Vietnamese live: heading, body, search placeholder, and the best-sellers section heading.

**UX-004 — Skip link / theme chrome localization**
**STATUS: FIXED** **IMPLEMENTED BY:** `da23979` (same filter/commit as UX-003 — one mechanism covers both) **PRODUCTION VERIFIED:** yes
"Skip to content" → "Bỏ qua đến nội dung," confirmed live via `:focus-visible` inspection (the element most keyboard/screen-reader users encounter first on every page). Did not expand into a theme-wide translation sweep — only confirmed leaks were touched, exactly as scoped.

**UX-005 — Brand message too keychain-specific**
**STATUS: FIXED** **IMPLEMENTED BY:** content only, no commit (WP post content, `wp eval-file` with verified single-occurrence `str_replace`, backed up before changing — see §10) **PRODUCTION VERIFIED:** yes
Re-queried the live catalog first: `Gấu bông len` (plush) is still genuinely empty — confirmed the task's own warning applied, so plush was deliberately **not** named as available. Broadened the homepage eyebrow, H1, intro paragraph, and brand-story H2 from "Móc khóa len" to "Đồ len" (generic "yarn items"), and the intro paragraph now explicitly names both `móc khóa` and `hoa len` as concrete, actually-in-stock examples. Custom-order page H1 broadened from "…móc khóa len handmade…" to "…mẫu len handmade…"; its body paragraph already correctly named all three families (keychains/flowers/plush) as custom-order capability, which is honest framing there (not implying ready stock) and was left unchanged. Verified live at 390px — no wall-of-text regression, still reads as 4 short lines.

**UX-006 — Homepage final CTA color**
**STATUS: FIXED** **IMPLEMENTED BY:** `da23979` **PRODUCTION VERIFIED:** yes
Re-measured: still `rgb(194, 195, 210)`. Root-caused precisely this time (the original audit's "accidental default swatch" theory was wrong — the homepage's own block JSON carries no color choice at all; it was a deliberate CSS rule using the real, defined `--lyli-color-lavender` token). Swapped to `--lyli-color-blush`, the same token the page's other CTA block already uses, for one consistent CTA family instead of two unrelated hues. Checked 360/390/430/1366/1440/1920 — no layout impact, color-only change.

**UX-007 — Short-desktop shop density**
**STATUS: FIXED (partial, by design)** **IMPLEMENTED BY:** `da23979` **PRODUCTION VERIFIED:** yes
**Before:** 1366×768 `firstProductTop` = 548.7px (71.4% of viewport). **After:** 515.8px (67.2%) — a 32.9px / 4.2-point improvement. Trimmed the header's own padding ceiling and the breadcrumb's 30px margin (the single largest, clearly-attributable gap in the header stack); deliberately left the post-header margin-collapse system untouched (Batch A.2 history shows it already needed one fix, and it's shared with 1440/1920, which had to stay balanced). 1440×900 and 1920×1080 re-verified balanced (not cramped), 390×844 re-verified not cramped. This is a **measured, real, but modest** improvement, not a full resolution of the underlying "how much chrome does an archive need" question — a larger structural change (e.g., collapsing the eyebrow or moving the intro) was judged out of proportion for this pass and was not attempted.

**UX-008 — Pagination touch targets**
**STATUS: FIXED** **IMPLEMENTED BY:** `da23979` **PRODUCTION VERIFIED:** yes
**Before:** 36×36px. **After:** 44×44px, confirmed live on both page 1 and page 2 of the current (now 16-product) catalog. Current-state color/spacing/softness untouched — Botiga's own customizer button-color setting already resolved to the Lyli primary brown here, confirmed live before touching anything.

**UX-009 — Policy reading width**
**STATUS: FIXED (scoped)** **IMPLEMENTED BY:** `da23979` (new `inc/content-pages.php`) **PRODUCTION VERIFIED:** yes
**Before:** 1110px (~133 chars/line) on every plain WP page. **After:** 760px (~88 chars/line, matching the blog's own established pattern) on the 4 policy pages + "Giới thiệu" only. Explicitly checked "Liên hệ" (short, not dense prose) and "Đặt mẫu theo yêu cầu" (structured card columns, not a wall of text) live before excluding them — confirmed both still render at the original 1110px, unaffected, verified live post-deploy.

**UX-010 — Variable product disabled CTA explanation**
**STATUS: REJECTED** **IMPLEMENTED BY:** n/a **PRODUCTION VERIFIED:** n/a (no change made)
Re-tested live on a variable PDP at 390×844 before deciding. Found: bold "Chọn mẫu" label directly above the native select (which itself reads "Chọn một tùy chọn"), immediately followed by a visibly dimmed (`opacity: 0.7`) button — the same disabled-state language used everywhere else on the site. Judged this sequence self-explanatory for a reasonable first-time shopper; the requested hint text would restate what the label already says one line above. Per the task's own explicit instruction for this exact scenario ("if the flow is already sufficiently obvious: REJECT the additional hint as redundant"), rejected.

**UX-011 — Visual variant picker**
**STATUS: DEFER** — unchanged, separate founder-approved future scope. Production architecture has not changed; native Woo variation mechanics remain untouched.

**UX-012 — Sticky mobile CTA visual polish**
**STATUS: FIXED** **IMPLEMENTED BY:** `da23979` **PRODUCTION VERIFIED:** yes
Added `var(--lyli-radius-lg)` to the sticky bar's top corners only — the one existing brand token already used elsewhere for this radius tier, not a new value. Confirmed live: bar height, padding, shadow, and — critically — `pdp-sticky-cta.js`'s visibility/proxy/sync logic are byte-for-byte untouched; only a `border-radius` line was added to the CSS rule. Checked 360/390/430/844×390 and safe-area behavior (`env(safe-area-inset-bottom)` already existing, untouched).

**UX-013 — Back-to-top / footer collision**
**STATUS: FIXED** **IMPLEMENTED BY:** `da23979` **PRODUCTION VERIFIED:** yes
Re-reproduced the original footer overlap, and **found a second, previously-undocumented overlap** during this pass: Botiga's back-to-top button also collided with the sticky PDP CTA bar itself (live-measured, near-total bounding-box overlap on any PDP once both are visible). Fixed both with a single mobile-only offset (`bottom: calc(88px + safe-area)`), clearing both the footer link row and the sticky bar. Verified live at 360/390/430 on the footer (all pages) and specifically on a PDP with the sticky bar visible; also spot-checked cart/checkout/account (no fixed-bottom UI there to collide with, confirmed unaffected).

**UX-014 — Empty product category recovery**
**STATUS: SUPERSEDED, then FIXED after a real gap was found in the superseding mechanism** **IMPLEMENTED BY:** `da23979` **PRODUCTION VERIFIED:** yes
Re-tested per the task's own instruction to check whether UX-016 already covers this. Found it did **not**: `render_taxonomy_nav()` was hooked only to `woocommerce_before_shop_loop`, which WooCommerce core never fires when an archive has zero products (confirmed via direct `do_action` testing on the actual empty-archive request — the function produced correct output when called manually, but the real page request never reached it). This is exactly the single worst dead-end case the feature was built for, silently not helped by it. Also hooked the same function to `woocommerce_no_products_found`. Verified live on `/product-category/lyli-signature/` (0 products): up-link to "Móc khóa len" + sibling chips "Lyli Charm"/"Lyli Tiny" now render correctly above the native "no products" notice.

**UX-015 — Search no-result recovery**
**STATUS: FIXED** **IMPLEMENTED BY:** `da23979` **PRODUCTION VERIFIED:** yes
Added "Thử từ khóa khác hoặc xem tất cả sản phẩm." (with a real link to the shop archive) after WooCommerce's own native notice, scoped to `is_search()` specifically so it never duplicates UX-014's category-specific up/sibling navigation. Verified live on a nonsense-term search.

**UX-016 — Soft catalog navigation regression check**
**STATUS: KEEP (no regression found, one real gap found and fixed under UX-014)**
Specifically tested the "two consecutive taxonomy rows" concern (Botiga's child chips + the new up/sibling row) at 360/390/430 on `Móc khóa len` (a parent category that shows both). Judged the composition clear, not confusing: the two rows read as functionally distinct (go deeper vs. go up/sideways), visually distinguished (Botiga's chips vs. the up-link's ghost-link treatment + filled current-state chip), with adequate spacing between them. No architecture change made. The one real regression-adjacent finding from this pass — the empty-category hook gap — is filed under UX-014 above since that's the finding it directly addresses, not a defect in UX-016's own design.

---

## 10. Content/DB changes (reproducibility record)

All WordPress content mutations made during this pass, for a clean-rebuild/restore process to account for:

| Object | Change | Old value | New value | Mechanism | Rollback |
|---|---|---|---|---|---|
| Homepage (post 14) | Eyebrow text | `Móc khóa len handmade, mua trực tiếp tại LyliShop` | `Quà len handmade, mua trực tiếp tại LyliShop` | `wp eval-file`, verified single-occurrence `str_replace` | Restore from `shared/backups/ux005-content-2026-08-20/post-14-before.txt` (full pre-change `post_content`) via `wp post update 14 --post_content=<file>` |
| Homepage (post 14) | H1 | `Móc khóa len handmade cute cho những món quà nhỏ có cảm xúc.` | `Đồ len handmade cute cho những món quà nhỏ có cảm xúc.` | same | same |
| Homepage (post 14) | Hero intro paragraph (opening clause) | `Móc khóa len handmade cute từ LyliShop: phụ kiện len nhỏ xinh, đóng gói làm quà,` | `Đồ len handmade cute từ LyliShop: móc khóa, hoa len và phụ kiện nhỏ xinh, đóng gói làm quà,` | same | same |
| Homepage (post 14) | Brand-story H2 | `Móc khóa len handmade được chuẩn bị theo từng món quà` | `Đồ len handmade được chuẩn bị theo từng món quà` | same | same |
| Custom-order page (post 17) | H1 | `Liên hệ đặt móc khóa len handmade theo yêu cầu` | `Liên hệ đặt mẫu len handmade theo yêu cầu` | same | Restore from `shared/backups/ux005-content-2026-08-20/post-17-before.txt` via `wp post update 17 --post_content=<file>` |

No taxonomy, product, menu, or WooCommerce-setting changes were made in this pass. The `Hoa len` landing page (post 279) and the homepage's card-03 link/label change (UX-001) were **not** made by this pass — found already in place, authored independently between the original audit and this remediation pass; not this session's own change to roll back.

---

## 11. UX-017 — Category navigation felt like a taxonomy tree, not a shop (owner-reported, 2026-08-20)

**Owner observation:** hover/current colors on category controls felt inconsistent; a hovered chip could look too close to the current one; parent category pages exposed multiple navigation rows that were "logically correct but visually cumbersome"; the overall feel was "navigating a taxonomy tree rather than browsing a small handmade shop."

### Research applied

- **Baymard — Overcategorization** (`baymard.com/blog/ecommerce-over-categorization`): categories under roughly 10–30 products are filter/collection candidates, not true categories; splitting products that share attributes into separate categories forces "pogosticking" to compare alternatives.
- **Baymard — Mobile "View All"** (`baymard.com/blog/mobile-main-nav-view-all`): a bare category-name header is frequently not understood as a tappable "view everything" control; explicit "View All {category}" wording performs far better (only 24% of sites get this right).
- **W3C menu interaction-state guidance**: distinct, non-ambiguous visual states for available/hover/focus/current controls.

### Root causes (measured, not assumed)

1. **Two independent navigation systems stacked visually.** UX-016 deliberately ran Botiga's own native child-chip row (down only) alongside a separate up/sibling row (up + sideways) on the same parent-category page — architecturally sound, visually two rows.
2. **Hover/current collision — found via matched-CSSRule inspection.** Botiga generates a Customizer stylesheet (`custom-styles.css`, from the theme's own "Button" color setting) containing `.woocommerce-page-header .category-button:hover { background-color: #212121 !important; color: #fff !important; border-color: #212121 !important; }` — a near-black fill with `!important`, silently overriding this theme's own (non-`!important`) hover rule regardless of specificity or source order, and visually close in weight/darkness to the primary-brown current-state fill.

### Live taxonomy (product IDs, not just counts)

```
Móc khóa len (17, 11 products: 249,251,246,241,236,231,169,170,172,120,107)
├─ Lyli Signature (41, 0)
├─ Lyli Charm (40, 4: 249,251,169,170)
└─ Lyli Tiny (39, 7: 246,241,236,231,172,120,107)

Hoa len (19, 5 products: 320,308,299,268,259)
├─ Bó hoa len có sẵn (67, 0)
├─ Hoa giỏ (75, 1: 299)
└─ Hoa len lẻ (68, 4: 320,308,268,259)
   ├─ Hoa hướng dương (71, 1: 259)
   └─ Hoa tulip (73, 1: 268)
```

**Key fact:** every child term's product set is an exact subset of its parent's own — every product carries both the parent and child term. `Lyli Charm ∪ Lyli Tiny = Móc khóa len` exactly; `Hoa giỏ ∪ Hoa len lẻ = Hoa len` exactly. The parent category page already *is* the "view all" scope for its children, natively, with no query change needed.

### Overcategorization assessment

**Likely:** yes, by Baymard's own quantitative marker — `Lyli Charm` (4), `Lyli Tiny` (7), `Hoa giỏ` (1), `Hoa hướng dương` (1), `Hoa tulip` (1) are all well under the 10–30 product threshold, and each reads as a style/series distinction (a charm collection, a size/scale line, a flower variety) rather than a fundamentally different product type.
**Structural migration recommended:** **no, not in this pass.** A migration (converting these into attributes/tags, or flattening the hierarchy) would touch URLs, SEO, breadcrumbs, product assignments, and the newly-created `hoa-len-handmade` landing page's own links — real, cross-cutting risk explicitly out of this task's authority. This section is the flagged structural recommendation the task asked for; **presentation-only** changes were implemented instead, valid under either taxonomy.

### Candidates considered

**A — current scope + collection strip (chosen):** one row — up-link, then a "Tất cả {current category}" chip (current-marked) followed by children (or siblings on a leaf). Root-level sibling switching (e.g. Móc khóa len ↔ Hoa len) moves one tap away via the up-link to Cửa hàng, where Botiga's own native top-level chips already list every populated top-level category — traded deliberately after live mobile testing showed the extra row cost more than the one extra tap.
**B — one flat taxonomy strip (`[Cửa hàng][Hoa len][Móc khóa len][Lyli Charm][Lyli Tiny]`):** rejected — flattening two hierarchy levels into one row loses the parent/child semantic distinction the breadcrumb still needs to carry; risks ambiguity about what "Hoa len" vs "Lyli Charm" actually means side-by-side without a clear grouping cue.
**C — broad scope + subtype strip only (identical in spirit to A, without the explicit up-link separator):** effectively converges with A once "up" is proven necessary (UX-016's original, still-valid requirement) — A is C with the ancestor-exit control made explicit and visually distinct from the local browse chips, rather than folded into the same row.

### Chosen model — implemented

One row via `render_taxonomy_nav()` (`inc/woocommerce/archive.php`), Botiga's native child-chip mechanism now fully suppressed (`suppress_single_subcategory_nav()` always returns `false`):
- **Parent** (has populated children): `← {grandparent or Cửa hàng}` then `Tất cả {category}` (current) then child chips.
- **Leaf** (no children): `← {parent}` then sibling chips (current included, marked).
- **Empty category**: same function, still hooked to both `woocommerce_before_shop_loop` and `woocommerce_no_products_found` (UX-014's fix, re-verified still firing).

### Interaction state matrix (computed style, live)

| | Default | Hover (desktop) | Focus-visible | Current | Current+hover |
|---|---|---|---|---|---|
| background | `--lyli-color-cream` `rgb(251,239,229)` | `--lyli-color-blush` `rgb(246,228,227)` `!important` | same as default/current | `--lyli-color-primary` `rgb(122,59,23)` | `--lyli-color-primary` `!important` |
| text | `--lyli-color-primary` `rgb(122,59,23)` | `--lyli-color-primary` | unchanged | `--lyli-color-warm-white` `rgb(255,252,247)` | `--lyli-color-warm-white` `!important` |
| border | transparent | `--lyli-color-primary` `!important` | n/a | transparent | `--lyli-color-primary` `!important` |
| outline | none | none | `3px solid var(--lyli-color-primary)`, 2px offset | none | `3px solid var(--lyli-color-primary)` |

Live-verified via `browser_hover` + `getComputedStyle` (not inferred from source): hovering a non-current chip → `rgb(246,228,227)` bg / `rgb(122,59,23)` border-text; the current chip stays `rgb(122,59,23)` bg / `rgb(255,252,247)` text at all times. No shared color between hover and current states.

### Measurements

| State | Viewport | Before (two rows) | After (one row) | Δ |
|---|---|---|---|---|
| Parent (Móc khóa len) | 390×844 | 465.0px | 394.2px | −70.8 |
| Parent (Móc khóa len) | 1366×768 | 530.9px | 440.9px | −90.0 |
| Parent (Móc khóa len) | 1440×900 | 469.9px | 441.9px | −28.0 |

Shop root, leaf categories, and deep-leaf categories were single-row already and are unchanged (not re-measured — no code path affecting them changed).

### Acceptance

Verified live: 360/390/430/768/820(via 768 pattern)/844×390/1366/1440/1920 — zero horizontal overflow at any width. Direct entry (fresh navigation, no history) to a parent, leaf, deep leaf, and the empty `Lyli Signature` category all render correct, non-dead-end navigation. `aria-current="page"` present only on real `<a href>` elements representing the true current URL. Regression: shop root untouched, zero empty product-loop anchors, PDP description/related/sticky-CTA intact, cart/checkout Vận chuyển translation intact, console clean on every page checked.

---

*This document is a new, independent audit phase. It does not modify, supersede, or reopen `docs/STOREFRONT-V2-IMPLEMENTATION.md`, which remains the closed, historical record of Storefront V2.*
