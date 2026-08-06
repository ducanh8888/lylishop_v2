<?php

/**
 * Shared Bedrock-style application configuration.
 *
 * Loaded by web/wp-config.php. Environment-specific overrides live in
 * config/environments/{WP_ENV}.php, loaded before the final Config::apply()
 * below.
 *
 * Adapted from the official Roots Bedrock config/application.php to this
 * repository's pinned packages and accepted policies.
 *
 * Intentional deviations from official Bedrock:
 *   - Only ".env" is loaded (no .env.local): this project is one-production-
 *     only (docs/DEPLOYMENT.md Amendment 2026-08-03). A local override file
 *     must never silently alter a production release.
 *   - No DATABASE_URL support: not required by project policy.
 *   - No WP_DEVELOPMENT_MODE / DISABLE_WP_CRON / WP_POST_REVISIONS /
 *     CONCATENATE_SCRIPTS / reverse-proxy HTTPS-detection blocks: none are
 *     part of the accepted stack or the hosting model (LiteSpeed vhost serves
 *     HTTPS directly).
 *   - DISALLOW_FILE_MODS remains defined in config/environments/production.php
 *     (existing repo policy, TECH_STACK.md §13.1) rather than here.
 *   - $table_prefix is fixed to 'wp_' per project decision; no DB_PREFIX
 *     environment variable is read.
 *   - Explicit missing-required-variable checks run even when no .env file is
 *     present, so a broken bootstrap fails clearly instead of silently using
 *     empty credentials.
 */

use Roots\WPConfig\Config;

use function Env\env;

// Convert booleans, nulls and integers; strip surrounding quotes; prefer real
// environment values over loaded values (official Bedrock convention).
Env\Env::$options
    = Env\Env::CONVERT_BOOL
    | Env\Env::CONVERT_NULL
    | Env\Env::CONVERT_INT
    | Env\Env::STRIP_QUOTES
    | Env\Env::LOCAL_FIRST;

/**
 * Directory containing all of the site's files (project/release root).
 */
$root_dir = dirname(__DIR__);

/**
 * Document Root (Bedrock web/ directory containing index.php / wp-config.php).
 */
$webroot_dir = $root_dir . '/web';

/**
 * Load the root .env with vlucas/phpdotenv.
 *
 * file_exists($root_dir . '/.env') follows the production release symlink
 * (.env -> shared/.env), so a safe symlink is supported. Values are never
 * logged here. An immutable repository prevents later accidental overrides.
 */
if (file_exists($root_dir . '/.env')) {
    $repository = Dotenv\Repository\RepositoryBuilder::createWithNoAdapters()
        ->addAdapter(Dotenv\Repository\Adapter\EnvConstAdapter::class)
        ->addAdapter(Dotenv\Repository\Adapter\PutenvAdapter::class)
        ->immutable()
        ->make();

    $dotenv = Dotenv\Dotenv::create($repository, $root_dir, ['.env'], false);
    $dotenv->load();

    $dotenv->required(['WP_HOME', 'WP_SITEURL', 'DB_NAME', 'DB_USER', 'DB_PASSWORD']);
}

/**
 * Fail clearly when required runtime configuration is missing, regardless of
 * whether a .env file is present. These are runtime credentials/URLs; no
 * insecure default is substituted.
 */
foreach (['WP_HOME', 'WP_SITEURL', 'DB_NAME', 'DB_USER', 'DB_PASSWORD'] as $required_key) {
    if (empty(env($required_key))) {
        throw new \RuntimeException(
            "Required environment variable {$required_key} is missing or empty. "
            . 'Check the release .env (kept outside the web root).'
        );
    }
}

/**
 * Set up our global environment constant.
 * Default: production (one-production-only architecture — never staging).
 */
define('WP_ENV', env('WP_ENV') ?: 'production');

/**
 * Set WP_ENVIRONMENT_TYPE from WP_ENV unless explicitly provided.
 */
if (!defined('WP_ENVIRONMENT_TYPE')) {
    $wp_environment_type = env('WP_ENVIRONMENT_TYPE');

    if ($wp_environment_type) {
        Config::define('WP_ENVIRONMENT_TYPE', $wp_environment_type);
    } elseif (in_array(WP_ENV, ['production', 'staging', 'development', 'local'], true)) {
        Config::define('WP_ENVIRONMENT_TYPE', WP_ENV);
    }
}

/**
 * URLs — supplied by the .env; never hard-coded in PHP.
 */
Config::define('WP_HOME', env('WP_HOME'));
Config::define('WP_SITEURL', env('WP_SITEURL'));

/**
 * Custom Content Directory — Composer installs plugins/themes/mu-plugins under
 * web/app, so WordPress must use web/app instead of web/wp/wp-content.
 */
Config::define('CONTENT_DIR', '/app');
Config::define('WP_CONTENT_DIR', $webroot_dir . Config::get('CONTENT_DIR'));
Config::define('WP_CONTENT_URL', Config::get('WP_HOME') . Config::get('CONTENT_DIR'));

/**
 * DB settings
 */
Config::define('DB_NAME', env('DB_NAME'));
Config::define('DB_USER', env('DB_USER'));
Config::define('DB_PASSWORD', env('DB_PASSWORD'));
Config::define('DB_HOST', env('DB_HOST') ?: 'localhost');
Config::define('DB_CHARSET', 'utf8mb4');
Config::define('DB_COLLATE', 'utf8mb4_unicode_ci');
$table_prefix = 'wp_';

/**
 * Authentication Unique Keys and Salts — from the .env; never committed.
 */
Config::define('AUTH_KEY', env('AUTH_KEY'));
Config::define('SECURE_AUTH_KEY', env('SECURE_AUTH_KEY'));
Config::define('LOGGED_IN_KEY', env('LOGGED_IN_KEY'));
Config::define('NONCE_KEY', env('NONCE_KEY'));
Config::define('AUTH_SALT', env('AUTH_SALT'));
Config::define('SECURE_AUTH_SALT', env('SECURE_AUTH_SALT'));
Config::define('LOGGED_IN_SALT', env('LOGGED_IN_SALT'));
Config::define('NONCE_SALT', env('NONCE_SALT'));

/**
 * Custom Settings — accepted production policies (same values as before this
 * repair; AUTOMATIC_UPDATER_DISABLED / WP_AUTO_UPDATE_CORE / DISALLOW_FILE_EDIT
 * preserved from the original repository).
 */
Config::define('AUTOMATIC_UPDATER_DISABLED', true);
Config::define('WP_AUTO_UPDATE_CORE', false);
Config::define('DISALLOW_FILE_EDIT', true);

/**
 * Debugging Settings — safe defaults.
 *
 * WP_DEBUG_LOG=false is deliberate: once WP_CONTENT_DIR points at web/app, a
 * true value would write web/app/debug.log inside the public document root.
 * No portable non-public log path is defined, so production debug logging is
 * disabled (config/environments/production.php repeats the same choice).
 */
Config::define('WP_DEBUG', false);
Config::define('WP_DEBUG_DISPLAY', false);
Config::define('WP_DEBUG_LOG', false);
ini_set('display_errors', '0');

/**
 * Environment-specific configuration (production, development).
 */
$env_config = __DIR__ . '/environments/' . WP_ENV . '.php';

if (file_exists($env_config)) {
    require_once $env_config;
}

Config::apply();

/**
 * WordPress root directory.
 */
if (!defined('ABSPATH')) {
    define('ABSPATH', $webroot_dir . '/wp/');
}