<?php
/**
 * Production overrides. Loaded only when WP_ENV=production.
 *
 * Per PLAN.md section 13 and TECH_STACK.md section 13.1 — production locks file
 * mods and disables the in-admin file editor unconditionally.
 *
 * Debug-log decision (2026-08-06, Bedrock bootstrap repair): WP_DEBUG_LOG is
 * FALSE in production. Once WP_CONTENT_DIR points at web/app, a boolean true
 * would create web/app/debug.log inside the public document root (LiteSpeed
 * serves web/ directly). No portable non-public log path exists for this
 * shared-host model, so production debug logging is disabled rather than made
 * publicly reachable. See config/application.php "Debugging Settings".
 */

use Roots\WPConfig\Config;

Config::define('WP_DEBUG', false);
Config::define('WP_DEBUG_LOG', false);
Config::define('WP_DEBUG_DISPLAY', false);
Config::define('DISALLOW_FILE_MODS', true);
Config::define('DISALLOW_FILE_EDIT', true);
Config::define('AUTOMATIC_UPDATER_DISABLED', true);
Config::define('WP_AUTO_UPDATE_CORE', false);