<?php
/**
 * Lyli Shop — design tokens (loaded by functions.php).
 *
 * Source of truth: docs/THEME-DECISION.md §11 (accepted 2026-08-04, round 2).
 * These constants drive:
 *   1. CSS custom properties in style.css (:root) — manually mirrored there
 *      for runtime performance; keep both in sync.
 *   2. Editor color presets (functions.php → add_theme_support).
 *   3. Block pattern editorial colors.
 *
 * Unresolved final background/cream palette is intentionally NOT represented:
 * neutral white / default surfaces are used (docs/THEME-DECISION.md §11).
 */

namespace ShopChild;

if (! defined('ABSPATH')) {
    exit;
}

const COLOR_TOKENS = [
    // Primary: headings, primary CTA, key brand accents, selected nav state.
    'brand-primary' => '#7A3B17',
    // Secondary/soft: hover states, secondary accents, softer decorative use.
    'brand-secondary' => '#8A4A23',
    // Neutral surface defaults — not final brand palette (cream still open).
    'surface' => '#FFFFFF',
    'surface-soft' => '#F8F5F2',
    'text' => '#2D2A26',
    'text-muted' => '#6B6560',
    'border' => '#E4DED8',
];

const TYPOGRAPHY_TOKENS = [
    'heading' => 'Fraunces',
    'body' => 'Be Vietnam Pro',
];

/**
 * Google Fonts URL (runtime delivery; no binary font files committed).
 * Verified families exist on Google Fonts (open-licensed).
 */
function google_fonts_url(): string
{
    return 'https://fonts.googleapis.com/css2?family=Fraunces:wght@400;600;700&family=Be+Vietnam+Pro:wght@400;500;600;700&display=swap';
}