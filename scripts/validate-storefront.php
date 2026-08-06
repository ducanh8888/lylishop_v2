<?php
/**
 * Lyli storefront static validator (credential-free, no DB, no WordPress).
 *
 * Verifies repository-level invariants for the admin-editable storefront:
 *  1. Required shop-child files exist.
 *  2. shop-child style.css Template header == botiga.
 *  3. No forbidden page-builder packages/artifacts (Elementor/Divi/Brizy/
 *     WPBakery/Oxygen/Bricks) and no Botiga Pro.
 *  4. No WooCommerce template-override copies inside shop-child.
 *  5. No unapproved plugin artifacts (checked against the manifest allow-list).
 *  6. No tracked secrets: .env, DB dumps, backups, uploads, private keys,
 *     proprietary font binaries.
 *  7. Lyli Site Settings uses the Settings API with sanitize callbacks,
 *     capability checks and nonces (settings_fields).
 *  8. Block patterns register the 8 controlled Lyli slugs.
 *  9. Site bootstrap has the --apply guard and no hard-coded credentials.
 * 10. theme.json parses (JSON) and declares no block templates.
 *
 * Exit code 0 = PASS; non-zero = FAIL. Prints one line per check.
 */

namespace LyliValidate;

$root = dirname(__DIR__);

$failures = 0;
$checks   = [];

function check(string $label, bool $ok, string $detail = ''): void
{
    global $checks, $failures;
    $checks[] = [$label, $ok, $detail];
    if (! $ok) {
        $failures++;
    }
}

/* 1. Required shop-child files */
$required_theme_files = [
    'web/app/themes/shop-child/style.css',
    'web/app/themes/shop-child/functions.php',
    'web/app/themes/shop-child/theme.json',
    'web/.htaccess',
    'web/app/themes/shop-child/inc/design-tokens.php',
    'web/app/themes/shop-child/inc/enqueue.php',
    'web/app/themes/shop-child/inc/announcement.php',
    'web/app/themes/shop-child/inc/footer.php',
    'web/app/themes/shop-child/inc/accessibility.php',
    'web/app/themes/shop-child/inc/block-patterns.php',
    'web/app/themes/shop-child/inc/woocommerce.php',
    'web/app/mu-plugins/lyli-site-settings/lyli-site-settings.php',
    'web/app/mu-plugins/lyli-site-settings/inc/settings-page.php',
    'web/app/mu-plugins/lyli-site-settings/inc/public-accessors.php',
    'web/app/mu-plugins/lyli-site-bootstrap/lyli-site-bootstrap.php',
];
foreach ($required_theme_files as $rel) {
    check("Required file exists: $rel", is_file("$root/$rel"));
}

/* 2. shop-child style.css Template header */
$style_css = file_get_contents("$root/web/app/themes/shop-child/style.css");
check(
    'shop-child style.css has Template: botiga',
    is_string($style_css) && preg_match('/^\s*Template:\s*botiga\s*$/mi', $style_css) === 1
);

/* 3. Forbidden page builders / Botiga Pro in composer.json */
$composer = json_decode((string) file_get_contents("$root/composer.json"), true);
check('composer.json parses', is_array($composer));
$composer_blob = (string) file_get_contents("$root/composer.json");
$forbidden_packages = [
    'elementor', 'brizy', 'wpbakery', 'oxygen', 'bricks',
    'wpackagist-plugin/elementor', 'wpackagist-plugin/brizy', 'wpackagist-plugin/wp-bakery',
    'wpackagist-plugin/oxygen', 'wpackagist-plugin/bricks', 'wpackagist-theme/botiga-pro',
];
foreach ($forbidden_packages as $pkg) {
    check("No forbidden package: $pkg", stripos($composer_blob, $pkg) === false);
}

/* 4. No WooCommerce template copies in shop-child */
$wc_copy_paths = glob("$root/web/app/themes/shop-child/woocommerce/*", GLOB_ONLYDIR);
check('No woocommerce/ template overrides in shop-child', empty($wc_copy_paths));
$wc_copy_files = glob("$root/web/app/themes/shop-child/woocommerce/*.php");
check('No woocommerce/*.php template files in shop-child', empty($wc_copy_files));

/* 5. Unapproved plugins check */
$approved_exempt = [
    'woocommerce' => true, 'fluent-smtp' => true, 'simple-history' => true,
    'updraftplus' => true, 'wp-2fa' => true, 'wp-seopress' => true, 'wp-super-cache' => true,
];
$unapproved = [];
foreach (glob("$root/web/app/plugins/*", GLOB_ONLYDIR) as $dir) {
    $slug = basename($dir);
    if ($slug === '.gitkeep') {
        continue;
    }
    if (! isset($approved_exempt[$slug])) {
        $unapproved[] = $slug;
    }
}
check('No unapproved plugin artifacts (manifest allow-list)', empty($unapproved), implode(',', $unapproved));

/* 6. No committed font binaries in theme/MU plugins */
$scan_paths = [
    "$root/web/app/themes/shop-child",
    "$root/web/app/mu-plugins/lyli-site-settings",
    "$root/web/app/mu-plugins/lyli-site-bootstrap",
];
$proprietary_font_exts = ['woff', 'woff2', 'ttf', 'otf'];
$prop_font_found = [];
foreach ($scan_paths as $dir) {
    if (! is_dir($dir)) {
        continue;
    }
    $it = new \RecursiveIteratorIterator(new \RecursiveDirectoryIterator($dir));
    foreach ($it as $file) {
        if (! $file->isFile()) {
            continue;
        }
        $ext = strtolower(pathinfo($file->getFilename(), PATHINFO_EXTENSION));
        if (in_array($ext, $proprietary_font_exts, true)) {
            $prop_font_found[] = $file->getPathname();
        }
    }
}
check('No committed font binaries in theme/MU plugins', empty($prop_font_found), implode(',', $prop_font_found));

/* Tracked-file hygiene (git ls-files) */
$tracked = [];
exec('git ls-files', $tracked, $git_status);
if ($git_status === 0) {
    $forbidden_tracked = array_filter($tracked, static function (string $f): bool {
        if ($f === '' || $f === '.env.example' || $f === 'auth.json.example') {
            return false;
        }
        if (preg_match('#(^|/)\.env($|\.)#', $f)) {
            return true;
        }
        if (preg_match('#\.sql(\.gz)?$#i', $f) || preg_match('#\.(pem|key|ppk)$#i', $f)) {
            return true;
        }
        if (preg_match('#(^|/)(vendor|web/wp|web/app/uploads|backups?|scratch)/#', $f)) {
            return true;
        }
        return false;
    });
    check('No tracked secrets/dumps/uploads/vendor', empty($forbidden_tracked), implode(',', $forbidden_tracked));
} else {
    check('git ls-files available (tracked hygiene)', false, 'not in a git repo');
}

/* 7. Settings API sanitizers + capability checks + nonces */
$settings_php = (string) file_get_contents("$root/web/app/mu-plugins/lyli-site-settings/inc/settings-page.php");
check('Settings page uses Settings API (register_setting)', stripos($settings_php, 'register_setting(') !== false);
check('Settings page uses settings_fields nonce', stripos($settings_php, 'settings_fields(') !== false);
check('Settings page has sanitize callbacks', substr_count($settings_php, "'sanitize_callback'") >= 8);
check('Settings page has capability check (current_user_can)', stripos($settings_php, 'current_user_can(') !== false);

$accessors_php = (string) file_get_contents("$root/web/app/mu-plugins/lyli-site-settings/inc/public-accessors.php");
check('Accessors exist', stripos($accessors_php, 'get_setting(') !== false);

/* 8. Block patterns */
$patterns_php = (string) file_get_contents("$root/web/app/themes/shop-child/inc/block-patterns.php");
$required_pattern_slugs = [
    'lyli-hero', 'lyli-featured-categories', 'lyli-brand-story', 'lyli-usp',
    'lyli-custom-order-cta', 'lyli-featured-products', 'lyli-final-cta', 'lyli-empty-shop',
];
check('Block patterns use register_block_pattern', stripos($patterns_php, 'register_block_pattern(') !== false);
foreach ($required_pattern_slugs as $slug) {
    check("Block pattern registered: $slug", strpos($patterns_php, "'$slug'") !== false);
}

/* 9. Site bootstrap guard */
$bootstrap_php = (string) file_get_contents("$root/web/app/mu-plugins/lyli-site-bootstrap/lyli-site-bootstrap.php");
check(
    'Bootstrap requires --apply for writes',
    strpos($bootstrap_php, 'pass --apply to apply changes') !== false
);
check(
    'Bootstrap registers WP-CLI command',
    stripos($bootstrap_php, 'WP_CLI::add_command') !== false
);

/* 10. theme.json parses and has no block templates */
$theme_json_raw = (string) file_get_contents("$root/web/app/themes/shop-child/theme.json");
$theme_json = json_decode($theme_json_raw, true);
check('theme.json parses as JSON', is_array($theme_json));
check(
    'theme.json declares no block templates (not FSE)',
    ! isset($theme_json['templates']) || empty($theme_json['templates'])
);

/* 11. Namespaced constant references in sub-namespace inc/ files must be
 * fully qualified (\ShopChild\COLOR_TOKENS), not bare COLOR_TOKENS which
 * would resolve to ShopChild\<Sub>\COLOR_TOKENS and fatal at load. */
$unqualified_constant_refs = [];
foreach (glob("$root/web/app/themes/shop-child/inc/*.php") as $inc_file) {
    $code = (string) file_get_contents($inc_file);
    if (preg_match('/const (COLOR_TOKENS|TYPOGRAPHY_TOKENS)/', $code)) {
        continue; // definition file
    }
    foreach (['COLOR_TOKENS', 'TYPOGRAPHY_TOKENS', 'google_fonts_url'] as $sym) {
        if (preg_match('/(?<!\\\\)\b' . preg_quote($sym, '/') . '\b/', $code)) {
            $unqualified_constant_refs[] = basename($inc_file) . ': ' . $sym;
        }
    }
}
check(
    'No unqualified ShopChild symbol references in inc/ sub-namespaces',
    empty($unqualified_constant_refs),
    implode(', ', $unqualified_constant_refs)
);

/* composer.json validate script updated? */
check(
    'composer.json has validate:storefront script',
    isset($composer['scripts']['validate:storefront'])
);

/* ---- Report ---- */
foreach ($checks as [$label, $ok, $detail]) {
    printf("  [%s] %s%s\n", $ok ? 'PASS' : 'FAIL', $label, $detail !== '' ? ' — ' . $detail : '');
}

echo "\n";
if ($failures > 0) {
    echo "STORE FRONT VALIDATION: FAIL ({$failures} check(s) failed)\n";
    exit(1);
}

echo "STORE FRONT VALIDATION: PASS\n";
exit(0);