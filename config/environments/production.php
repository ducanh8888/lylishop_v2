<?php
/**
 * Production overrides. Loaded only when WP_ENV=production.
 *
 * Production code is immutable: plugin/theme/core changes are built from the
 * pinned Composer lockfile and deployed as a new release. Content and normal
 * store settings remain editable in wp-admin.
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
