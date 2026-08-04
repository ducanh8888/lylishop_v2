# shop-child

Child theme of **Botiga Free** (parent theme decision: `docs/THEME-DECISION.md`, accepted 2026-08-04, verified against wpackagist 2026-08-04). Presentation only — see the "must not contain" list in `docs/THEME-DECISION.md` section 8.

## Current state

Metadata skeleton + design-token foundation only (`style.css`, `functions.php`, `inc/design-tokens.php`). **No visual styling, component CSS, or template overrides have been implemented yet.** The color and navigation decisions that used to block this work are now closed (`docs/THEME-DECISION.md` section 9) — what remains open is only the background/cream palette and the final placeholder-image design (`docs/THEME-DECISION-BRIEF.md` section 5).

## Enqueue behavior (verified against real Botiga 2.4.7, 2026-08-04)

`functions.php` intentionally does **not** manually enqueue `style.css`. Botiga's own `botiga_style_css()` (`wp_enqueue_scripts`, priority 12) already enqueues `get_stylesheet_uri()` under the handle `botiga-style`, which WordPress resolves to *this* theme's `style.css` automatically once shop-child is active — that's the standard parent/child mechanism. An earlier version of this file duplicated that enqueue under a second handle (`shop-child`), loading the same file twice, and separately enqueued Botiga's root `style.css` as a fake dependency even though that file is only a 23-line header block with no CSS rules. Botiga's real parent stylesheet is `assets/css/styles.min.css`, enqueued by Botiga itself under the handle `botiga-style-min`. See `docs/INSTALLATION-PREPARATION.md` Phase 3 for the full evidence. Botiga ships no `woocommerce/` template-override directory — it integrates purely through action/filter hooks around WooCommerce's own default templates.

## Design tokens (accepted, not yet wired into CSS)

Source of truth: `docs/THEME-DECISION.md` section 11. Machine-readable copy: `inc/design-tokens.php` (not required from `functions.php` yet — it produces no output today).

| Token | Value | Role |
|---|---|---|
| `brand-primary` | `#7A3B17` | Headings, primary CTA, key brand accents, selected nav state |
| `brand-secondary` | `#8A4A23` | Hover states, secondary accents, softer decorative treatment |
| heading font | Fraunces | Name only — no font file committed |
| body/CTA font | Be Vietnam Pro | Name only — no font file committed |

Aristotelica Pro is logo-asset use only and its font files must never be committed to this repository. Background/cream tokens are still open candidates — see `docs/THEME-DECISION.md` section 11 — do not assume either candidate set here.

## File structure

```text
shop-child/
├── style.css                  # theme header (Template: botiga) — no component CSS yet
├── functions.php              # bootstrap only — no manual style enqueue (Botiga does it)
├── README.md                  # this file
├── inc/
│   ├── design-tokens.php      # color/typography token constants (present, inert)
│   ├── enqueue.php            # planned — split out of functions.php once it grows
│   └── template-overrides.php # planned — narrowly justified WooCommerce overrides only
├── patterns/                  # planned — controlled Gutenberg block patterns
└── templates/                 # planned — WooCommerce template overrides, only when justified
```

Planned entries above are created when the corresponding step in `docs/THEME-IMPLEMENTATION-PLAN.md` is actually implemented, not in advance.
