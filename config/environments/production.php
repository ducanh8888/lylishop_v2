<?php
/**
 * Production overrides. Loaded only when WP_ENV=production.
 * Per PLAN.md section 13 and TECH_STACK.md section 13.1 — production locks file mods
 * and disables the in-admin file editor unconditionally.
 */

use Roots\WPConfig\Config;

Config::define('WP_DEBUG', false);
Config::define('WP_DEBUG_LOG', true);
Config::define('WP_DEBUG_DISPLAY', false);
Config::define('DISALLOW_FILE_MODS', true);
Config::define('DISALLOW_FILE_EDIT', true);
Config::define('AUTOMATIC_UPDATER_DISABLED', true);
Config::define('WP_AUTO_UPDATE_CORE', false);
