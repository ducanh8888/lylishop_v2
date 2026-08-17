# shop-child — Lyli Shop V1 storefront

Child theme for `lylishop.online` (parent: **Botiga Free 2.4.7**). Presentation only — no order, payment, voucher or business logic (PLAN.md §6.1 / THEME-DECISION.md §8).

## Contents

| Path | Purpose |
|---|---|
| `style.css` | Theme metadata + full V1 storefront CSS (tokens, typography, homepage patterns, product archive/single, Classic Cart/Checkout, responsive, a11y). Auto-loaded by Botiga (`botiga-style` handle) — never enqueue manually. |
| `functions.php` | Loads all `inc/` modules; editor font URL. |
| `theme.json` | Canonical brand color, typography, spacing and content-width tokens for frontend and editor. |
| `inc/design-tokens.php` | Protects the child `theme.json` palette from Botiga's Customizer override and provides the Google Fonts URL (runtime; no binaries). |
| `inc/enqueue.php` | Google Fonts runtime delivery, preconnect and independent child stylesheet cache version. |
| `inc/announcement.php` | Optional announcement bar from Lyli Site Settings. |
| `inc/footer.php` | Footer intro/contact/socials/copyright inside Botiga's single semantic footer (values from Lyli Site Settings). |
| `inc/accessibility.php` | Notes Botiga's existing skip-link/search labels; no duplicates added. |
| `inc/block-patterns.php` | 8 controlled Gutenberg patterns (Hero, Categories, Brand story, USP, Custom-order CTA, Featured products, Final CTA, Empty shop). |
| `inc/woocommerce.php` | Narrow presentation hooks: one-image/missing-image classes, custom-order hint on single product (only when configured). |
| `inc/botiga-admin.php` | Keeps Botiga Dashboard usable when production file modifications are locked and the optional Starter Sites importer is unavailable. |

## Decisions

- **No FSE primary architecture** — classic/hybrid per THEME-DECISION.md §3.
- **No page builder** — core Gutenberg blocks + patterns (PLAN.md §6.1).
- **No Botiga Pro** — prohibited (THEME-DECISION.md §7).
- **Classic Cart/Checkout** — styled presentation only (TECH_STACK.md §4.1).
- **Support surfaces** — restrained neutral canvas/surface colors live in `theme.json`; primary/secondary brand colors remain the founder-approved tokens.
- **Fonts** — Gutenberg exposes `Fraunces — Tiêu đề` (default `600`) and `Be Vietnam Pro — Nội dung & CTA` (body `400`, button `500`). They load through Google Fonts runtime; no font binaries are committed. Aristotelica Pro is only allowed through an approved external logo asset. Production evidence: `docs/TYPOGRAPHY-IMPLEMENTATION-2026-08-08.md`.
- **Products lacking real images** remain **draft** (WEBSITE-REQUIREMENTS.md policy).

## Motion & interaction

- **Motion tokens** (`style.css` `:root`) — `--lyli-motion-fast` (120ms), `--lyli-motion-base` (200ms), `--lyli-motion-slow` (320ms), paired with `--lyli-ease-standard` and `--lyli-ease-out`. `--lyli-transition` is kept as a `motion-base` + `ease-standard` alias for older call sites; new rules should reach for the layered tokens directly.
- **Hover-capability policy** — every decorative transform/shadow hover (button lift, category-card lift, product-image scale, header-icon fill) is scoped inside `@media (hover: hover) and (pointer: fine)`, so a tap on a touch device can't leave a stuck hover state behind. Color-only hovers and anything that carries state (`:focus-visible`, `:active`, the current-page nav indicator) stay outside that query — they must work the same regardless of pointer type.
- **Reduced-motion policy** — the blanket `prefers-reduced-motion: reduce` duration override is required because Botiga's own CSS has none; it only compresses duration, so a second, selector-specific block neutralizes the actual transform end-state for each decorative hover so nothing jumps to its final position instantly. The FAQ chevron's rotation is deliberately excluded from that neutralization — it carries open/closed state, not decoration, so it keeps its (now near-instant) transition instead of losing the information.
- **CSS-only interaction** — no JS and no animation library (GSAP/Framer/Motion One) anywhere in the interaction system. The FAQ disclosure indicator is a custom chevron built with a CSS border and `[open]` state, riding entirely on native `<details>/<summary>` semantics; panel height is never animated.
- **Component notes** — the product-card image scales, not the whole card. The product title responds to hover only through a sibling selector keyed off the real product-link anchor (`li.product > a:first-child:hover ~ .woocommerce-loop-product__title`), because Botiga's grid markup closes that anchor before the title renders — the title itself is plain text, not a link, so it must not visually promise the whole card is clickable. A stretched-link treatment for the category card was evaluated and deliberately not implemented: the block patterns are owner-editable content outside code review, and a full-card `::after` overlay would silently swallow clicks on any interactive element an owner adds inside a card later, with no visible cause.

## Owner editing surface

All page text/images/sections/menu/logo/products/prices are WordPress-admin controlled. Global small values (footer intro, contact, socials, announcement, CTA defaults, copyright) come from **WP Admin → Lyli Shop → Cài đặt giao diện** (the `lyli-site-settings` MU plugin) — see `docs/OWNER-ADMIN-GUIDE.md`.
