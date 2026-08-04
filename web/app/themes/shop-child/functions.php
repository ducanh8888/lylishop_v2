<?php
/**
 * shop-child functions — presentation only.
 * No cart/checkout/voucher business logic here (PLAN.md section 6.1).
 * Parent theme: Botiga Free 2.4.7 (docs/THEME-DECISION.md, accepted 2026-08-04).
 *
 * No manual style enqueue here — verified against the real Botiga 2.4.7
 * source (docs/INSTALLATION-PREPARATION.md, Phase 3): Botiga's own
 * botiga_style_css() (wp_enqueue_scripts, priority 12) already enqueues
 * get_stylesheet_uri() under the handle `botiga-style`, which resolves to
 * THIS theme's style.css automatically once shop-child is active — that is
 * the standard WordPress parent/child mechanism, not something a child
 * theme needs to repeat. An earlier version of this file manually
 * re-enqueued the same URL under a second handle (`shop-child`), producing
 * two <link> tags for one file, and separately enqueued Botiga's *root*
 * style.css (`botiga-parent`) as a dependency even though that file is only
 * the 23-line theme header block with no CSS rules — Botiga's real parent
 * stylesheet is assets/css/styles.min.css, already enqueued by Botiga
 * itself under the handle `botiga-style-min`. Both were removed.
 *
 * If shop-child later needs its own additional asset (a separate CSS/JS
 * file distinct from style.css), add a narrowly-scoped enqueue here that
 * depends on the real parent handle `botiga-style-min`, not on Botiga's
 * root style.css.
 */

namespace ShopChild;

if (! defined('ABSPATH')) {
    exit;
}
