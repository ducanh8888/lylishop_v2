<?php
/**
 * Shared Bedrock-style application config, loaded by web/wp-config.php.
 * Environment-specific overrides live in config/environments/{env}.php.
 */

use Roots\WPConfig\Config;
use function Env\env;

Config::define('DB_NAME', env('DB_NAME'));
Config::define('DB_USER', env('DB_USER'));
Config::define('DB_PASSWORD', env('DB_PASSWORD'));
Config::define('DB_HOST', env('DB_HOST') ?: 'localhost');
Config::define('DB_CHARSET', 'utf8mb4');
Config::define('DB_COLLATE', 'utf8mb4_unicode_ci');
$table_prefix = 'wp_';

Config::define('WP_ENV', env('WP_ENV') ?: 'production');
Config::define('WP_HOME', env('WP_HOME'));
Config::define('WP_SITEURL', env('WP_SITEURL'));
Config::define('WP_DEBUG', false);

Config::define('AUTH_KEY', env('AUTH_KEY'));
Config::define('SECURE_AUTH_KEY', env('SECURE_AUTH_KEY'));
Config::define('LOGGED_IN_KEY', env('LOGGED_IN_KEY'));
Config::define('NONCE_KEY', env('NONCE_KEY'));
Config::define('AUTH_SALT', env('AUTH_SALT'));
Config::define('SECURE_AUTH_SALT', env('SECURE_AUTH_SALT'));
Config::define('LOGGED_IN_SALT', env('LOGGED_IN_SALT'));
Config::define('NONCE_SALT', env('NONCE_SALT'));

Config::define('AUTOMATIC_UPDATER_DISABLED', true);
Config::define('WP_AUTO_UPDATE_CORE', false);
Config::define('DISALLOW_FILE_EDIT', true);

$env_file = __DIR__ . '/environments/' . Config::get('WP_ENV') . '.php';
if (file_exists($env_file)) {
    require_once $env_file;
}

Config::apply();

if (! defined('ABSPATH')) {
    define('ABSPATH', dirname(__DIR__) . '/web/wp/');
}
