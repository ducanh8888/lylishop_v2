<?php
/**
 * Production overrides. Loaded only when WP_ENV=production.
 *
 * The intended administrator account has full wp-admin access, including
 * manual plugin/theme installation, updates, and the built-in file editors.
 * Automatic updates stay disabled so production never mutates unattended.
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
Config::define('DISALLOW_FILE_MODS', false);
Config::define('DISALLOW_FILE_EDIT', false);
Config::define('AUTOMATIC_UPDATER_DISABLED', true);
Config::define('WP_AUTO_UPDATE_CORE', false);
