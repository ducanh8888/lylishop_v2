<?php
/**
 * Lyli Shop — design asset configuration (loaded by functions.php).
 *
 * theme.json is the canonical source for color and typography tokens. Frontend
 * and editor CSS reference the WordPress preset variables generated from it;
 * PHP intentionally does not mirror the palette.
 */

namespace ShopChild;

if (! defined('ABSPATH')) {
    exit;
}

/**
 * Google Fonts URL (runtime delivery; no binary font files committed).
 * Verified families exist on Google Fonts (open-licensed).
 */
function google_fonts_url(): string
{
    return 'https://fonts.googleapis.com/css2?family=Fraunces:wght@400;600;700&family=Be+Vietnam+Pro:wght@400;500;600;700&display=swap';
}
