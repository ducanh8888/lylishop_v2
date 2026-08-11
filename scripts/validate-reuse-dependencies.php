<?php

declare(strict_types=1);

$root = dirname(__DIR__);
$failures = [];
$passes = 0;

function check_reuse(bool $condition, string $message): void
{
    global $failures, $passes;
    if ($condition) {
        $passes++;
        echo "PASS: {$message}\n";
        return;
    }
    $failures[] = $message;
    echo "FAIL: {$message}\n";
}

if (! defined('ABSPATH')) {
    define('ABSPATH', $root . '/web/wp/');
}
if (! function_exists('add_action')) {
    function add_action(...$args): void {}
}
if (! function_exists('add_filter')) {
    function add_filter(...$args): void {}
}
if (! function_exists('sanitize_text_field')) {
    function sanitize_text_field($value): string { return trim(strip_tags((string) $value)); }
}
if (! function_exists('__')) {
    function __($value): string { return (string) $value; }
}
if (! function_exists('is_wp_error')) {
    function is_wp_error($value): bool { return $value instanceof WP_Error; }
}
if (! class_exists('WP_Error')) {
    class WP_Error
    {
        public function __construct(public string $code = '', public string $message = '') {}
    }
}

require_once $root . '/vendor/autoload.php';
require_once $root . '/web/app/plugins/lyli-vietnam-address/lyli-vietnam-address.php';

$repository = \Lyli\VietnamAddress\Repository::instance();
$provinces = $repository->provinces();
$ward_count = array_sum(array_map(static fn ($code): int => count($repository->wards((string) $code)), array_keys($provinces)));
check_reuse(34 === count($provinces), 'address dataset contains 34 province-level units');
check_reuse(3321 === $ward_count, 'address dataset contains 3,321 ward-level units');
check_reuse(count($provinces) === count(array_unique(array_keys($provinces))), 'province codes are unique');

$all_ward_codes = [];
foreach (array_keys($provinces) as $province_code) {
    foreach (array_keys($repository->wards((string) $province_code)) as $ward_code) {
        $all_ward_codes[] = $ward_code;
    }
}
check_reuse(count($all_ward_codes) === count(array_unique($all_ward_codes)), 'ward codes are globally unique');

$known = $repository->resolve('79', '25747', '12 Test Street');
check_reuse($known instanceof \Lyli\VietnamAddress\Address, 'known province and ward resolve');
check_reuse('Thành phố Hồ Chí Minh' === ($known?->province_name ?? ''), 'known province name is current');
check_reuse('Phường Thủ Dầu Một' === ($known?->ward_name ?? ''), 'known ward relation resolves without district');
check_reuse(null === $repository->resolve('79', '00004', '12 Test Street'), 'ward from another province is rejected');
check_reuse(null === $repository->resolve('99', '99999', '12 Test Street'), 'unknown codes are rejected');
check_reuse(['province_code', 'province_name', 'ward_code', 'ward_name', 'street'] === array_keys($known?->to_array() ?? []), 'canonical address DTO serializes stable fields');

require_once $root . '/web/app/plugins/lyli-ghn-connector/includes/contracts/interface-address-resolver.php';
require_once $root . '/web/app/plugins/lyli-ghn-connector/includes/domain/class-address.php';
require_once $root . '/web/app/plugins/lyli-ghn-connector/includes/woocommerce/class-woo-address-resolver.php';
require_once $root . '/web/app/plugins/lyli-ghn-connector/includes/integrations/vietnam-address/class-vietnam-address-resolver.php';

$order = new class {
    public function get_shipping_address_1(): string { return '12 Test Street'; }
    public function get_shipping_first_name(): string { return 'Test'; }
    public function get_shipping_last_name(): string { return 'Customer'; }
    public function get_shipping_phone(): string { return ''; }
    public function get_billing_phone(): string { return '0900000000'; }
    public function get_shipping_country(): string { return 'VN'; }
    public function get_shipping_state(): string { return '79'; }
    public function get_shipping_city(): string { return '25747'; }
    public function get_shipping_address_2(): string { return ''; }
};
$ghn_address = (new \Lyli\GHN\Integrations\VietnamAddress\Vietnam_Address_Resolver())->resolve($order);
check_reuse($ghn_address instanceof \Lyli\GHN\Domain\Address, 'selected address source converts into GHN Address DTO');
check_reuse(true === ($ghn_address?->to_ghn_payload()['is_new_to_address'] ?? false), 'GHN payload uses two-level address flag');
check_reuse(! array_key_exists('to_district_name', $ghn_address?->to_ghn_payload() ?? []), 'GHN DTO does not fabricate district');

require_once $root . '/web/app/plugins/lyli-vietqr-bacs/includes/class-payload-factory.php';
require_once $root . '/web/app/plugins/lyli-vietqr-bacs/includes/class-qr-renderer.php';

$factory = new \Lyli\VietQRBACS\Payload_Factory();
$payload_a = $factory->build('970436', '0000000001', 125000, 'LYLI1001');
$payload_b = $factory->build('970436', '0000000001', 125000, 'LYLI1001');
check_reuse($payload_a === $payload_b, 'VietQR payload is deterministic');
$parsed = (new \Liopay\VietQR\Parser\QRParser())->parse($payload_a);
check_reuse('125000' === $parsed->getAmount(), 'Woo order amount maps into VietQR payload');
check_reuse('LYLI1001' === $parsed->getAdditionalData()->getReferenceLabel(), 'deterministic order reference maps into VietQR payload');
check_reuse('QRIBFTTA' === $parsed->getServiceCode(), 'payload uses account-transfer service');
$uri = (new \Lyli\VietQRBACS\QR_Renderer())->data_uri($payload_a);
check_reuse(str_starts_with($uri, 'data:image/svg+xml;base64,'), 'QR image renders locally as an SVG data URI');

$invalid = 0;
foreach ([
    ['123', '0000000001', 125000, 'LYLI1001'],
    ['970436', '', 125000, 'LYLI1001'],
    ['970436', '0000000001', 0, 'LYLI1001'],
] as $arguments) {
    try {
        $factory->build(...$arguments);
    } catch (\InvalidArgumentException) {
        $invalid++;
    }
}
check_reuse(3 === $invalid, 'invalid merchant settings and non-positive amount are rejected');

$test_order = new class {
    public function get_total(): string { return '150000'; }
    public function get_total_refunded(): string { return '25000'; }
};
check_reuse(125000 === $factory->amount($test_order), 'refunded amount is conservatively excluded');
check_reuse('LYLIABC123' === $factory->reference('Lyli-', 'ABC-123'), 'reference mapping is normalized and deterministic');

$presentation = file_get_contents($root . '/web/app/plugins/lyli-vietqr-bacs/includes/class-order-presentation.php') ?: '';
$plugin_source = file_get_contents($root . '/web/app/plugins/lyli-vietqr-bacs/lyli-vietqr-bacs.php') ?: '';
$integration_source = file_get_contents($root . '/web/app/plugins/lyli-vietqr-bacs/includes/class-integration.php') ?: '';
$owned_vietqr = $presentation . $plugin_source . (file_get_contents($root . '/web/app/plugins/lyli-vietqr-bacs/includes/class-payload-factory.php') ?: '');
check_reuse(str_contains($presentation, "'bacs' !=="), 'presentation is restricted to native BACS orders');
check_reuse(str_contains($presentation, 'hash_equals') && str_contains($presentation, 'get_order_key'), 'guest order QR requires the matching Woo order key');
check_reuse(str_contains($integration_source, 'extends \\WC_Integration') && str_contains($integration_source, 'validate_text_field'), 'merchant configuration reuses sanitized Woo Integration settings');
check_reuse(! preg_match('/payment_complete|update_status|set_status/i', $owned_vietqr), 'adapter cannot auto-mark an order paid');
check_reuse(! preg_match('/webhook|rest_api_init|wp_remote_|curl_/i', $owned_vietqr), 'adapter registers no webhook and requires no payment network');
check_reuse(str_contains($presentation, 'esc_attr($qr_uri)') && str_contains($presentation, 'esc_html($reference)'), 'dynamic presentation values are escaped');

$address_adapter_source = file_get_contents($root . '/web/app/plugins/lyli-vietnam-address/includes/class-woo-adapter.php') ?: '';
check_reuse(str_contains($address_adapter_source, "check_ajax_referer('lyli_vn_wards'") && str_contains($address_adapter_source, 'woocommerce_after_checkout_validation'), 'address UI has nonce and server-side relationship validation');
$address_script_source = file_get_contents($root . '/web/app/plugins/lyli-vietnam-address/assets/checkout.js') ?: '';
check_reuse(str_contains($address_script_source, 'requestSequence') && str_contains($address_script_source, "state !== $('#"), 'late ward responses cannot overwrite the currently selected province');

if ([] !== $failures) {
    fwrite(STDERR, sprintf("\n%d reuse validation(s) failed.\n", count($failures)));
    exit(1);
}

printf("\nReuse dependency validation passed: %d assertions.\n", $passes);
