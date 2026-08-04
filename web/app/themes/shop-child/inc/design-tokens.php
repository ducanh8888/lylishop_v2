<?php
/**
 * Design token foundation — data only, not wired into any enqueue or output yet.
 * Not required from functions.php. This is a documented reference for the
 * "design token" step of docs/THEME-IMPLEMENTATION-PLAN.md, not an
 * implementation. No CSS is generated from this file today.
 *
 * Source of truth for the values below: docs/THEME-DECISION.md section 11.
 * Founder decision date: 2026-08-04 (round 2).
 */

namespace ShopChild;

if (! defined('ABSPATH')) {
    exit;
}

/**
 * Accepted color tokens. Background/cream/accent tokens are intentionally
 * absent — those remain open candidates (see docs/THEME-DECISION.md section 11)
 * and must not be guessed here.
 */
const COLOR_TOKENS = [
    // Primary: headings, primary CTA, key brand accents, selected nav state.
    'brand-primary' => '#7A3B17',
    // Secondary/soft: hover states, secondary accents, softer decorative use.
    'brand-secondary' => '#8A4A23',
];

/**
 * Accepted typography families (names only — no font files are committed to
 * this repository; self-hosted vs. Google Fonts delivery is not decided).
 * Aristotelica Pro is intentionally excluded here: logo-asset use only, and
 * its font files must never be committed to this repository.
 */
const TYPOGRAPHY_TOKENS = [
    'heading' => 'Fraunces',
    'body' => 'Be Vietnam Pro',
];
