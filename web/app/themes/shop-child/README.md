# shop-child — Lyli Shop V1 storefront

Child theme for `lylishop.online` (parent: **Botiga Free 2.4.7**). Presentation only — no order, payment, voucher or business logic (PLAN.md §6.1 / THEME-DECISION.md §8).

## Contents

| Path | Purpose |
|---|---|
| `style.css` | Theme metadata + full V1 storefront CSS (tokens, typography, homepage patterns, product archive/single, Classic Cart/Checkout, responsive, a11y). Auto-loaded by Botiga (`botiga-style` handle) — never enqueue manually. |
| `theme.json` | Editor presets (colors/typography/spacing/content widths) for Gutenberg. Not FSE templates. |
| `functions.php` | Loads all `inc/` modules; editor font URL. |
| `inc/design-tokens.php` | Accepted brand tokens (`#7A3B17` / `#8A4A23`) + neutral surfaces + Google Fonts URL (runtime; no binaries). |
| `inc/enqueue.php` | Google Fonts runtime delivery + preconnect. |
| `inc/announcement.php` | Optional announcement bar from Lyli Site Settings. |
| `inc/footer.php` | Footer intro/contact/socials/copyright (Botiga hooks, values from Lyli Site Settings). |
| `inc/accessibility.php` | Notes Botiga's existing skip-link/search labels; no duplicates added. |
| `inc/block-patterns.php` | 8 controlled Gutenberg patterns (Hero, Categories, Brand story, USP, Custom-order CTA, Featured products, Final CTA, Empty shop). |
| `inc/woocommerce.php` | Narrow presentation hooks: one-image/missing-image classes, custom-order hint on single product (only when configured). |

## Decisions

- **No FSE primary architecture** — classic/hybrid per THEME-DECISION.md §3.
- **No page builder** — core Gutenberg blocks + patterns (PLAN.md §6.1).
- **No Botiga Pro** — prohibited (THEME-DECISION.md §7).
- **Classic Cart/Checkout** — styled presentation only (TECH_STACK.md §4.1).
- **Cream/background palette** — unresolved, NOT used; neutral white surfaces (`#FFFFFF`/`#F8F5F2`) per THEME-DECISION.md §9/§11.
- **Fonts** — Fraunces + Be Vietnam Pro via Google Fonts runtime; no font binaries committed; Aristotelica Pro only via external approved logo asset.
- **Products lacking real images** remain **draft** (WEBSITE-REQUIREMENTS.md policy).

## Owner editing surface

All page text/images/sections/menu/logo/products/prices are WordPress-admin controlled. Global small values (footer intro, contact, socials, announcement, CTA defaults, copyright) come from **WP Admin → Lyli Shop → Cài đặt giao diện** (the `lyli-site-settings` MU plugin) — see `docs/OWNER-ADMIN-GUIDE.md`.