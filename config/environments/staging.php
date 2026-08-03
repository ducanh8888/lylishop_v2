<?php
/** Staging overrides. Loaded only when WP_ENV=staging. See docs/DEPLOYMENT.md. */

use Roots\WPConfig\Config;

Config::define('WP_DEBUG', true);
Config::define('WP_DEBUG_LOG', true);
Config::define('WP_DEBUG_DISPLAY', false);
Config::define('DISALLOW_FILE_MODS', true);

// Keep staging out of search engines until go-live.
add_filter('option_blog_public', '__return_zero');
