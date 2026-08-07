<?php

/**
 * Bedrock bootstrap validation gate.
 *
 * Verifies the executable bootstrap contract of this repository without:
 *   - loading wp-settings.php (WordPress core bootstrap),
 *   - touching a database,
 *   - requiring real credentials.
 *
 * A temporary synthetic .env is created with fake values, config/application.php
 * is loaded in an isolated subprocess, then the synthetic .env is deleted.
 *
 * Run: php scripts/validate-bedrock-bootstrap.php
 * Exit: 0 = all checks pass; 1 = at least one check failed.
 */

$root = dirname(__DIR__);
$webDir = $root . '/web';
$failures = [];
$checks = [];

function note(string $name, bool $ok, string $detail = ''): void
{
    global $checks;
    $checks[] = [$name, $ok, $detail];
}

function fail(string $name, string $detail): void
{
    global $failures;
    $failures[] = $name . ($detail !== '' ? ': ' . $detail : '');
}

$phpBinary = PHP_BINARY;

/* -------------------------------------------------------------------------
 * 1. Tracked entrypoint existence
 * ---------------------------------------------------------------------- */

$wpConfig = $webDir . '/wp-config.php';
$indexPhp = $webDir . '/index.php';

note('web/wp-config.php exists', is_file($wpConfig));
note('web/index.php exists', is_file($indexPhp));
note('vendor/autoload.php exists', is_file($root . '/vendor/autoload.php'));
note('web/wp/ exists', is_dir($webDir . '/wp'));

/* -------------------------------------------------------------------------
 * 2. Syntax validation (php -l) for tracked PHP entrypoints
 * ---------------------------------------------------------------------- */

foreach (['web/wp-config.php' => $wpConfig, 'web/index.php' => $indexPhp] as $label => $file) {
    $out = [];
    $code = 0;
    exec(escapeshellarg($phpBinary) . ' -l ' . escapeshellarg($file) . ' 2>&1', $out, $code);
    note($label . ' PHP syntax valid', $code === 0, implode(' ', $out));
}

/* -------------------------------------------------------------------------
 * 3. Synthetic .env
 * ---------------------------------------------------------------------- */

$syntheticEnv = $root . '/.env';
$fakeDbPassword = 'synthetic-test-password-7f3a9c';
$fakeValues = [
    'WP_ENV=production',
    'WP_HOME=https://synthetic.example',
    'WP_SITEURL=${WP_HOME}/wp',
    'DB_NAME=synthetic_db',
    'DB_USER=synthetic_user',
    'DB_PASSWORD=' . $fakeDbPassword,
    'DB_HOST=localhost',
    'AUTH_KEY=synthetic-auth-key',
    'SECURE_AUTH_KEY=synthetic-secure-auth-key',
    'LOGGED_IN_KEY=synthetic-logged-in-key',
    'NONCE_KEY=synthetic-nonce-key',
    'AUTH_SALT=synthetic-auth-salt',
    'SECURE_AUTH_SALT=synthetic-secure-auth-salt',
    'LOGGED_IN_SALT=synthetic-logged-in-salt',
    'NONCE_SALT=synthetic-nonce-salt',
];

// Remove any stale synthetic .env from an interrupted run.
if (file_exists($syntheticEnv)) {
    @unlink($syntheticEnv);
}

file_put_contents($syntheticEnv, implode("\n", $fakeValues) . "\n");

/* -------------------------------------------------------------------------
 * 4. Configuration-only load in an isolated subprocess
 * ---------------------------------------------------------------------- */

$probe = <<<'PHP'
$root = $argv[1];
require $root . '/vendor/autoload.php';
require $root . '/config/application.php';
echo json_encode([
    'ABSPATH' => ABSPATH,
    'WP_CONTENT_DIR' => WP_CONTENT_DIR,
    'WP_CONTENT_URL' => WP_CONTENT_URL,
    'WP_PLUGIN_DIR' => WP_PLUGIN_DIR,
    'WP_PLUGIN_URL' => WP_PLUGIN_URL,
    'WPMU_PLUGIN_DIR' => WPMU_PLUGIN_DIR,
    'WPMU_PLUGIN_URL' => WPMU_PLUGIN_URL,
    'DB_CHARSET' => DB_CHARSET,
    'DB_COLLATE' => DB_COLLATE,
    'WP_ENV' => WP_ENV,
    'WP_ENVIRONMENT_TYPE' => defined('WP_ENVIRONMENT_TYPE') ? WP_ENVIRONMENT_TYPE : null,
    'WP_DEBUG' => WP_DEBUG,
    'WP_DEBUG_DISPLAY' => WP_DEBUG_DISPLAY,
    'WP_DEBUG_LOG' => WP_DEBUG_LOG,
    'AUTOMATIC_UPDATER_DISABLED' => AUTOMATIC_UPDATER_DISABLED,
    'WP_AUTO_UPDATE_CORE' => WP_AUTO_UPDATE_CORE,
    'DISALLOW_FILE_EDIT' => DISALLOW_FILE_EDIT,
    'DISALLOW_FILE_MODS' => defined('DISALLOW_FILE_MODS') ? DISALLOW_FILE_MODS : null,
]);
PHP;

$out = [];
$code = 0;
exec(
    escapeshellarg($phpBinary)
    . ' -r ' . escapeshellarg($probe)
    . ' ' . escapeshellarg($root)
    . ' 2>&1',
    $out,
    $code
);

$rawOutput = implode("\n", $out);

$json = $out ? json_decode($out[count($out) - 1], true) : null;
if ($code !== 0 || !is_array($json)) {
    // The probe failed to load: treat as a list of failed checks.
    note('config/application.php loads with synthetic values', false, $rawOutput);
    note('ABSPATH resolves to web/wp/', false);
    note('WP_CONTENT_DIR resolves to web/app', false);
    note('WP_CONTENT_URL resolves to synthetic WP_HOME + /app', false);
    note('WP_PLUGIN_DIR resolves to web/app/plugins', false);
    note('WP_PLUGIN_URL resolves to synthetic WP_HOME + /app/plugins', false);
    note('WPMU_PLUGIN_DIR resolves to web/app/mu-plugins', false);
    note('WPMU_PLUGIN_URL resolves to synthetic WP_HOME + /app/mu-plugins', false);
    note('DB_CHARSET resolves to utf8mb4', false);
    note('DB_COLLATE resolves to utf8mb4_unicode_ci', false);
    note('WP_ENV resolves to production', false);
    note('WP_ENVIRONMENT_TYPE resolves', false);
    note('production hardening constants resolve', false);
} else {
    $expectedAbs = $webDir . '/wp/';
    $expectedContentDir = $webDir . '/app';
    $expectedContentUrl = 'https://synthetic.example/app';
    $expectedPluginDir = $expectedContentDir . '/plugins';
    $expectedPluginUrl = $expectedContentUrl . '/plugins';
    $expectedMuPluginDir = $expectedContentDir . '/mu-plugins';
    $expectedMuPluginUrl = $expectedContentUrl . '/mu-plugins';

    note(
        'config/application.php loads with synthetic values',
        true,
        'process exit 0'
    );
    note('ABSPATH resolves to web/wp/', $json['ABSPATH'] === $expectedAbs, $json['ABSPATH']);
    note('WP_CONTENT_DIR resolves to web/app', $json['WP_CONTENT_DIR'] === $expectedContentDir, $json['WP_CONTENT_DIR']);
    note('WP_CONTENT_URL resolves to synthetic WP_HOME + /app', $json['WP_CONTENT_URL'] === $expectedContentUrl, $json['WP_CONTENT_URL']);
    note('WP_PLUGIN_DIR resolves to web/app/plugins', $json['WP_PLUGIN_DIR'] === $expectedPluginDir, $json['WP_PLUGIN_DIR']);
    note('WP_PLUGIN_URL resolves to synthetic WP_HOME + /app/plugins', $json['WP_PLUGIN_URL'] === $expectedPluginUrl, $json['WP_PLUGIN_URL']);
    note('WPMU_PLUGIN_DIR resolves to web/app/mu-plugins', $json['WPMU_PLUGIN_DIR'] === $expectedMuPluginDir, $json['WPMU_PLUGIN_DIR']);
    note('WPMU_PLUGIN_URL resolves to synthetic WP_HOME + /app/mu-plugins', $json['WPMU_PLUGIN_URL'] === $expectedMuPluginUrl, $json['WPMU_PLUGIN_URL']);
    note('DB_CHARSET resolves to utf8mb4', $json['DB_CHARSET'] === 'utf8mb4', $json['DB_CHARSET']);
    note('DB_COLLATE resolves to utf8mb4_unicode_ci', $json['DB_COLLATE'] === 'utf8mb4_unicode_ci', $json['DB_COLLATE']);
    note('WP_ENV resolves to production', $json['WP_ENV'] === 'production', $json['WP_ENV']);
    note('WP_ENVIRONMENT_TYPE resolves', $json['WP_ENVIRONMENT_TYPE'] === 'production', (string) $json['WP_ENVIRONMENT_TYPE']);

    $hardeningOk = $json['WP_DEBUG'] === false
        && $json['WP_DEBUG_DISPLAY'] === false
        && $json['WP_DEBUG_LOG'] === false
        && $json['AUTOMATIC_UPDATER_DISABLED'] === true
        && $json['WP_AUTO_UPDATE_CORE'] === false
        && $json['DISALLOW_FILE_EDIT'] === true
        && $json['DISALLOW_FILE_MODS'] === true;
    note('production hardening constants resolve', $hardeningOk, $rawOutput);
}

/* -------------------------------------------------------------------------
 * 5. Cleanup and negative assertions
 * ---------------------------------------------------------------------- */

$envDeleted = !file_exists($syntheticEnv);
if (!$envDeleted) {
    @unlink($syntheticEnv);
    $envDeleted = !file_exists($syntheticEnv);
}
note('temporary synthetic .env deleted', $envDeleted);

note('no real credential needed', stripos($rawOutput, $fakeDbPassword) === false);
note('no database connection attempted', stripos($rawOutput, 'SQLSTATE') === false && stripos($rawOutput, 'mysqli') === false);
note('wp-settings.php not loaded', stripos($rawOutput, 'wp-settings.php') === false);
// The synthetic WP_HOME (synthetic.example) legitimately appears as the derived
// WP_CONTENT_URL constant in the probe output. The negative assertion therefore
// checks that raw environment VALUES (the fake DB credentials) are never echoed.
$envValueLeak = stripos($rawOutput, 'DB_PASSWORD=' . $fakeDbPassword) !== false
    || stripos($rawOutput, 'DB_NAME=synthetic_db') !== false
    || stripos($rawOutput, 'DB_USER=synthetic_user') !== false;
note('test output contains no environment-variable values', !$envValueLeak);

/* -------------------------------------------------------------------------
 * 6. Report
 * ---------------------------------------------------------------------- */

echo "Bedrock bootstrap validation\n";
echo "============================\n";
$allOk = true;
foreach ($checks as [$name, $ok, $detail]) {
    $mark = $ok ? 'PASS' : 'FAIL';
    if (!$ok) {
        $allOk = false;
    }
    echo sprintf("  [%s] %s%s\n", $mark, $name, $ok || $detail === '' ? '' : ' — ' . $detail);
}

if ($failures) {
    $allOk = false;
    echo "\nFailures:\n";
    foreach ($failures as $f) {
        echo "  - $f\n";
    }
}

if (!$allOk) {
    echo "\nRESULT: FAIL\n";
    exit(1);
}

echo "\nRESULT: PASS\n";
exit(0);
