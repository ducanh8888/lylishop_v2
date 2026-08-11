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
 * 10. theme.json parses, declares no block templates, and exposes the approved
 *     brand typography to Gutenberg with the required semantic weights.
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
    'web/app/themes/shop-child/editor-style.css',
    'web/.htaccess',
    'web/app/themes/shop-child/inc/design-tokens.php',
    'web/app/themes/shop-child/inc/theme-runtime.php',
    'web/app/themes/shop-child/inc/enqueue.php',
    'web/app/themes/shop-child/inc/announcement.php',
    'web/app/themes/shop-child/inc/footer.php',
    'web/app/themes/shop-child/inc/accessibility.php',
    'web/app/themes/shop-child/inc/block-patterns.php',
    'web/app/themes/shop-child/inc/mobile-header.php',
    'web/app/themes/shop-child/inc/woocommerce.php',
    'web/app/mu-plugins/lyli-site-settings/lyli-site-settings.php',
    'web/app/mu-plugins/lyli-site-settings/inc/settings-page.php',
    'web/app/mu-plugins/lyli-site-settings/inc/public-accessors.php',
    'web/app/mu-plugins/lyli-site-bootstrap/lyli-site-bootstrap.php',
    'web/app/mu-plugins/lyli-editorial-import/lyli-editorial-import.php',
    'web/app/mu-plugins/lyli-editorial-import/inc/command.php',
    'web/app/mu-plugins/lyli-editorial-import/data/editorial-content.json',
    'web/app/mu-plugins/site-policy/inc/vietnam-toolkit.php',
    'web/app/mu-plugins/bedrock-autoloader.php',
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
    'ai-engine' => true,
    'woocommerce' => true, 'fluent-smtp' => true, 'simple-history' => true,
    'updraftplus' => true, 'wp-2fa' => true, 'wp-seopress' => true, 'wp-super-cache' => true,
    'yoohw-vietnam-store-tools' => true,
    'lyli-ghn-connector' => true,
    'lyli-vietnam-address' => true,
    'lyli-vietqr-bacs' => true,
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
    "$root/web/app/mu-plugins/lyli-editorial-import",
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
$font_families = $theme_json['settings']['typography']['fontFamilies'] ?? [];
$fonts_by_slug = [];
foreach ($font_families as $font_family) {
    if (isset($font_family['slug'])) {
        $fonts_by_slug[$font_family['slug']] = $font_family;
    }
}
check(
    'Gutenberg exposes Fraunces heading font',
    ($fonts_by_slug['lyli-heading']['name'] ?? '') === 'Fraunces — Tiêu đề'
        && str_contains($fonts_by_slug['lyli-heading']['fontFamily'] ?? '', 'Fraunces')
);
check(
    'Gutenberg exposes Be Vietnam Pro body font',
    ($fonts_by_slug['lyli-body']['name'] ?? '') === 'Be Vietnam Pro — Nội dung & CTA'
        && str_contains($fonts_by_slug['lyli-body']['fontFamily'] ?? '', 'Be Vietnam Pro')
);
check(
    'Brand typography weights match guideline',
    ($theme_json['settings']['typography']['fontWeight'] ?? false) === true
        && ($theme_json['styles']['typography']['fontWeight'] ?? '') === '400'
        && ($theme_json['styles']['elements']['heading']['typography']['fontWeight'] ?? '') === '600'
        && ($theme_json['styles']['elements']['button']['typography']['fontWeight'] ?? '') === '500'
);
$palette = $theme_json['settings']['color']['palette'] ?? [];
$palette_by_slug = [];
foreach ($palette as $color) {
    if (isset($color['slug'])) {
        $palette_by_slug[$color['slug']] = strtoupper((string) ($color['color'] ?? ''));
    }
}
$official_palette = [
    'lyli-primary' => '#7A3B17',
    'lyli-warm-white' => '#FFFCF7',
    'lyli-cream' => '#FBEFE5',
    'lyli-blush' => '#F6E4E3',
    'lyli-sage' => '#E9F1EA',
    'lyli-lavender' => '#C2C3D2',
];
foreach ($official_palette as $slug => $color) {
    check("Official Gutenberg color: $slug", ($palette_by_slug[$slug] ?? '') === $color);
}
check(
    'Gutenberg disables the unrelated default palette',
    ($theme_json['settings']['color']['defaultPalette'] ?? null) === false
);
check(
    'Official palette excludes retired brand secondary',
    ! isset($palette_by_slug['lyli-secondary']) && ! in_array('#8A4A23', $palette_by_slug, true)
);
check(
    'Functional colors are explicitly separate from brand colors',
    isset($palette_by_slug['lyli-text'], $palette_by_slug['lyli-text-muted'], $palette_by_slug['lyli-border'])
);
check(
    'Functional button hover shade is canonical in theme.json',
    ($theme_json['settings']['custom']['lyli']['color']['actionHover'] ?? '') === '#5B2B12'
);

/* 11. Namespaced root helpers referenced from inc/ sub-namespaces must be
 * fully qualified so PHP cannot resolve them inside the child namespace. */
$unqualified_constant_refs = [];
foreach (glob("$root/web/app/themes/shop-child/inc/*.php") as $inc_file) {
    $code = (string) file_get_contents($inc_file);
    if (basename($inc_file) === 'design-tokens.php') {
        continue; // root helper definitions
    }
    foreach (['google_fonts_url'] as $sym) {
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

/* Footer integration extends Botiga's semantic footer instead of replacing it. */
$footer_php = (string) file_get_contents("$root/web/app/themes/shop-child/inc/footer.php");
$theme_css = (string) file_get_contents("$root/web/app/themes/shop-child/style.css");
check('Lyli footer uses Botiga builder inner hook', str_contains($footer_php, "add_action('botiga_bhfb_footer_inner_before'"));
check('Lyli footer does not render a second footer element', ! str_contains($footer_php, "echo '<footer"));
check('Botiga footer container is not hidden', preg_match('/\\.bhfb-footer\\s*\\{[^}]*display\\s*:\\s*none/si', $theme_css) !== 1);
check('Theme CSS tokens reference theme.json presets', str_contains($theme_css, '--lyli-color-primary: var(--wp--preset--color--lyli-primary)'));
check('Legacy duplicate CSS block is removed', preg_match_all('/^\\.lyli-pattern\\s*\\{/m', $theme_css) === 1 && substr_count($theme_css, 'Product-ready V1 visual system') === 1);
check('Theme fixes overflow sources without a page-wide concealment rule', preg_match('/(?:html|body)[^{]*\\{[^}]*overflow-x\\s*:\\s*hidden/si', $theme_css) !== 1);
check('Theme provides compact two-column mobile category and product grids', substr_count($theme_css, 'grid-template-columns: repeat(2, minmax(0, 1fr))') >= 2);
check('Theme has fluid responsive typography', substr_count($theme_css, 'clamp(') >= 8);
$editor_css = (string) file_get_contents("$root/web/app/themes/shop-child/editor-style.css");
$brand_sources = $theme_json_raw . $theme_css . $editor_css . $patterns_php;
check('Retired #8A4A23 is absent from owner-facing theme sources', stripos($brand_sources, '#8A4A23') === false);
check('Patterns avoid retired desktop-only 56/44 column ratios', ! str_contains($patterns_php, '"width":"56%"') && ! str_contains($patterns_php, '"width":"44%"'));
check('Patterns avoid fixed 520px hero height', ! str_contains($patterns_php, '"minHeight":520'));
$design_tokens_php = (string) file_get_contents("$root/web/app/themes/shop-child/inc/design-tokens.php");
check('Botiga cannot overwrite child theme.json palette', str_contains($design_tokens_php, "remove_filter('wp_theme_json_data_theme', 'botiga_filter_theme_json_data_theme')"));
check('Botiga runtime palette reads canonical theme.json values', str_contains($design_tokens_php, "add_filter('botiga_color_palettes'") && str_contains($design_tokens_php, "wp_json_file_decode("));
$theme_runtime_php = (string) file_get_contents("$root/web/app/themes/shop-child/inc/theme-runtime.php");
check('Botiga runtime reconciliation is one-time and versioned', str_contains($theme_runtime_php, "lyli_theme_runtime_version") && str_contains($theme_runtime_php, "get_option(VERSION_OPTION"));
check('Botiga CSS uses its supported public regeneration method', str_contains($theme_runtime_php, "Botiga_Custom_CSS::get_instance()->update_custom_css_file()"));
$button_section = '';
preg_match('/\/\* Buttons \*\/(.*?)\/\* Hero \*\//s', $theme_css, $button_section_match);
$button_section = $button_section_match[1] ?? '';
check('Button fixes add no important override', $button_section !== '' && ! str_contains($button_section, '!important'));
check(
    'Google Fonts runtime includes approved brand weights',
    str_contains($design_tokens_php, 'Fraunces:wght@400;600;700')
        && str_contains($design_tokens_php, 'Be+Vietnam+Pro:wght@400;500;600;700')
);
$enqueue_php = (string) file_get_contents("$root/web/app/themes/shop-child/inc/enqueue.php");
check('Child stylesheet keeps one Botiga handle', ! str_contains($enqueue_php, "wp_enqueue_style(\n        'botiga-style'"));
check('Child stylesheet has independent cache version', str_contains($enqueue_php, "registered['botiga-style']->ver"));

/* Production workflow must preserve runtime language packs and use the
 * host-proven WP-CLI path without parsing .env as shell syntax. */
$deploy_sh = (string) file_get_contents("$root/scripts/production-deploy.sh");
$backup_sh = (string) file_get_contents("$root/scripts/production-backup.sh");
$health_sh = (string) file_get_contents("$root/scripts/production-health-check.sh");
check('Deploy links shared language packs', str_contains($deploy_sh, 'shared/languages') && str_contains($deploy_sh, 'web/app/languages'));
check('Production scripts use host-proven WP-CLI path', ! str_contains($deploy_sh . $health_sh, 'shared/wp-cli.phar') && str_contains($deploy_sh, '/web/wp'));
check('Backup loads Bedrock without sourcing .env', str_contains($backup_sh, 'db export') && ! str_contains($backup_sh, 'source "$ENV_FILE"'));

/* Botiga Dashboard must not redirect into an unavailable demo importer when
 * production deliberately disables plugin installation. */
$child_functions_php = (string) file_get_contents("$root/web/app/themes/shop-child/functions.php");
$botiga_admin_php = (string) file_get_contents("$root/web/app/themes/shop-child/inc/botiga-admin.php");
check('Child theme loads Botiga admin compatibility', str_contains($child_functions_php, "inc/botiga-admin.php"));
check('Unavailable Starter Sites tab is removed', str_contains($botiga_admin_php, "unset(\$settings['tabs']['starter-sites'])"));
check('Starter Sites availability uses its runtime hook', str_contains($botiga_admin_php, "has_action('atss_starter_sites')"));
check('Botiga compatibility does not weaken file-mod locks', ! str_contains($botiga_admin_php, "DISALLOW_FILE_MODS', false"));

/* Mobile header is composed once, then left owner-editable in Botiga. */
$mobile_header_php = (string) file_get_contents("$root/web/app/themes/shop-child/inc/mobile-header.php");
check('Child theme loads mobile header composition', str_contains($child_functions_php, "inc/mobile-header.php"));
check('Mobile header composition is one-time and versioned', str_contains($mobile_header_php, 'lyli_mobile_header_composition_version'));
check('Mobile header keeps only cart beside logo and hamburger', str_contains($mobile_header_php, "['mobile_woocommerce_icons']"));
check('Mobile drawer contains search and account controls', str_contains($mobile_header_php, "'search', 'mobile_offcanvas_woocommerce_icons'"));

/* Vietnam toolkit dependency and least-privilege migration guard. */
check(
    'Composer pins Vietnam Store Toolkit exactly',
    ($composer['require']['wpackagist-plugin/yoohw-vietnam-store-tools'] ?? '') === '1.1.2'
);
$toolkit_policy_php = (string) file_get_contents("$root/web/app/mu-plugins/site-policy/inc/vietnam-toolkit.php");
check('Toolkit migration guard names both DevVN tools', str_contains($toolkit_policy_php, 'yoohw_vietnam_store_tools_devvn_migration_dry_run') && str_contains($toolkit_policy_php, 'yoohw_vietnam_store_tools_devvn_migration'));
check('Toolkit migration guard removes owner tool entries', str_contains($toolkit_policy_php, "add_filter('woocommerce_debug_tools'"));
check('Toolkit migration AJAX is denied server-side for shop_owner', str_contains($toolkit_policy_php, "const DEVVN_AJAX_ACTION = 'wp_ajax_yoohw_vietnam_store_tools_devvn_migration_step'") && str_contains($toolkit_policy_php, 'add_action(DEVVN_AJAX_ACTION') && str_contains($toolkit_policy_php, 'wp_send_json_error'));
check('Toolkit migration card is hidden from normal owner navigation', str_contains($toolkit_policy_php, "admin_head-toplevel_page_yoohw-vietnam-store") && str_contains($toolkit_policy_php, 'yoohw-vietnam-store__card:last-child'));
check('Toolkit policy does not grant manage_options', ! str_contains($toolkit_policy_php, 'manage_options'));

/* Repo-controlled GHN connector: manual shipment lifecycle only, disabled until owner configures it. */
$ghn_dir = "$root/web/app/plugins/lyli-ghn-connector";
$ghn_main_php = (string) file_get_contents("$ghn_dir/lyli-ghn-connector.php");
$ghn_plugin_php = (string) file_get_contents("$ghn_dir/includes/class-plugin.php");
$ghn_settings_php = (string) file_get_contents("$ghn_dir/includes/class-settings.php");
$ghn_client_php = (string) file_get_contents("$ghn_dir/includes/class-api-client.php");
$ghn_mapper_php = (string) file_get_contents("$ghn_dir/includes/class-order-mapper.php");
$ghn_address_php = (string) file_get_contents("$ghn_dir/includes/domain/class-address.php");
$ghn_application_php = (string) file_get_contents("$ghn_dir/includes/application/class-shipment-application.php");
$ghn_meta_keys_php = (string) file_get_contents("$ghn_dir/includes/infrastructure/woocommerce/class-shipment-meta-keys.php");
$ghn_toolkit_adapter_php = (string) file_get_contents("$ghn_dir/includes/integrations/vietnam-store-toolkit/class-toolkit-adapter.php");
$ghn_repository_php = (string) file_get_contents("$ghn_dir/includes/woocommerce/class-shipment-repository.php");
$ghn_standalone_admin_php = (string) file_get_contents("$ghn_dir/includes/woocommerce/class-standalone-admin.php");
$ghn_owned_php = $ghn_main_php . $ghn_plugin_php . $ghn_settings_php . $ghn_client_php . $ghn_mapper_php . $ghn_application_php . $ghn_toolkit_adapter_php . $ghn_repository_php . $ghn_standalone_admin_php;
check('Lyli GHN connector is repo-controlled', str_contains($ghn_main_php, 'Plugin Name: Lyli GHN Connector'));
check('GHN requires WooCommerce but not Toolkit', str_contains($ghn_main_php, 'Requires Plugins: woocommerce') && ! str_contains($ghn_main_php, 'Requires Plugins: woocommerce, yoohw'));
check('GHN Toolkit provider framework is isolated in adapter', str_contains($ghn_toolkit_adapter_php, 'yoohw_vietnam_store_tools_shipping_providers') && ! str_contains($ghn_mapper_php . $ghn_application_php . $ghn_client_php, 'Yoohw_'));
check('GHN owner settings use manage_woocommerce', str_contains($ghn_settings_php, "current_user_can('manage_woocommerce')") && ! str_contains($ghn_settings_php, 'manage_options'));
check('GHN token is never rendered back', str_contains($ghn_settings_php, 'name="lyli_ghn[token]" value=""'));
check('GHN client allowlists official gateways', str_contains($ghn_client_php, 'dev-online-gateway.ghn.vn') && str_contains($ghn_client_php, 'online-gateway.ghn.vn'));
check('GHN connector uses two-level name-mode address', str_contains($ghn_address_php, "'is_new_to_address' => true") && str_contains($ghn_address_php, "'to_ward_name'"));
check('GHN mutations have defense-in-depth capability check', str_contains($ghn_application_php, "current_user_can('manage_woocommerce')"));
check('GHN owns HPOS-compatible shipment persistence', str_contains($ghn_repository_php, 'update_meta_data') && str_contains($ghn_repository_php, 'save_meta_data') && ! str_contains($ghn_repository_php, '$wpdb'));
check('GHN canonical shipment keys are neutral and centralized', str_contains($ghn_meta_keys_php, '_openship_ghn_order_code') && ! str_contains($ghn_repository_php, "'_openship_ghn_"));
check('GHN panels share one application lifecycle', str_contains($ghn_toolkit_adapter_php, '$this->application->create(') && str_contains($ghn_standalone_admin_php, '$this->application->{$operation}('));
check('GHN has standalone Woo order admin fallback', str_contains($ghn_standalone_admin_php, 'add_meta_box') && str_contains($ghn_standalone_admin_php, 'admin_post_'));
check('GHN V1 exposes no unauthenticated webhook/AJAX', ! str_contains($ghn_owned_php, 'register_rest_route') && ! str_contains($ghn_owned_php, 'wp_ajax_nopriv'));
check('GHN V1 does not inject live checkout rates', ! str_contains($ghn_owned_php, 'WC_Shipping_Method'));
check('GHN connector focused validator exists', is_file("$root/scripts/validate-ghn-connector.php"));

check('Custom VietQR prototype is absent', ! is_file("$root/web/app/plugins/vietqr-bacs-for-woocommerce/vietqr-bacs-for-woocommerce.php") && ! is_file("$root/scripts/validate-vietqr-bacs.php"));

/* composer.json validate script updated? */
check(
    'composer.json has validate:storefront script',
    isset($composer['scripts']['validate:storefront'])
);

/* 12. Owner editing contract */
$roles_php = (string) file_get_contents("$root/web/app/mu-plugins/site-policy/inc/roles.php");
$settings_main_php = (string) file_get_contents("$root/web/app/mu-plugins/lyli-site-settings/lyli-site-settings.php");
$settings_page_php = (string) file_get_contents("$root/web/app/mu-plugins/lyli-site-settings/inc/settings-page.php");
check('shop_owner has dedicated Lyli capability', str_contains($roles_php, 'MANAGE_LYLI_SITE'));
check('shop_owner has native visual-control capability', str_contains($roles_php, "'edit_theme_options' => true"));
check('Lyli Settings save uses dedicated capability', str_contains($settings_main_php, 'option_page_capability_'));
check('Lyli Settings does not require manage_options', ! str_contains($settings_page_php, "current_user_can('manage_options')"));
check('Owner guide exists', is_file("$root/docs/OWNER-ADMIN-GUIDE.md"));

/* 13. Editorial import is content-only and owner-editable. */
$editorial_data = json_decode((string) file_get_contents("$root/web/app/mu-plugins/lyli-editorial-import/data/editorial-content.json"), true);
$editorial_command = (string) file_get_contents("$root/web/app/mu-plugins/lyli-editorial-import/inc/command.php");
check('Editorial content package parses', is_array($editorial_data));
check('Editorial package has 5 blog posts', count($editorial_data['blogPosts'] ?? []) === 5);
check('Editorial package has 25 checksummed assets', count($editorial_data['assets'] ?? []) === 25);
check('Editorial package excludes product records', ! isset($editorial_data['products']));
check('Editorial package excludes promotion', ! isset($editorial_data['promotion']));
check('Editorial import requires explicit --apply', str_contains($editorial_command, "isset(\$assocArgs['apply'])"));
check('Editorial import creates no WooCommerce products', ! str_contains($editorial_command, 'WC_Product'));
check('Editorial homepage requires existing Gutenberg structure', str_contains($editorial_command, 'editorial import will not recreate it'));
check('Editorial policies use approved source sections', str_contains($editorial_command, "policySectionContent(\$data, 'Giao hàng'"));

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
