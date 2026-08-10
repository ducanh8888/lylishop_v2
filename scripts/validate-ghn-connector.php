<?php

/** Focused, network-free validation for the repo-controlled GHN connector. */

define('ABSPATH', __DIR__ . '/../');
define('Lyli\\GHN\\SETTINGS_OPTION', 'lyli_ghn_settings');
define('Lyli\\GHN\\TOKEN_OPTION', 'lyli_ghn_token');

$GLOBALS['lyli_test_options'] = [];
$GLOBALS['lyli_test_can_manage'] = true;
$GLOBALS['lyli_test_nonce_valid'] = true;
$GLOBALS['lyli_test_shipping_data'] = [];

final class WP_Error
{
    private string $code;
    private string $message;
    private $data;

    public function __construct(string $code = '', string $message = '', $data = null)
    {
        $this->code = $code;
        $this->message = $message;
        $this->data = $data;
    }

    public function get_error_code(): string { return $this->code; }
    public function get_error_message(): string { return $this->message; }
    public function get_error_data() { return $this->data; }
}

function is_wp_error($value): bool { return $value instanceof WP_Error; }
function __(string $text, string $domain = ''): string { return $text; }
function sanitize_text_field($value): string { return trim(strip_tags((string) $value)); }
function sanitize_textarea_field($value): string { return trim(strip_tags((string) $value)); }
function sanitize_key($value): string { return preg_replace('/[^a-z0-9_\-]/', '', strtolower((string) $value)) ?: ''; }
function sanitize_file_name($value): string { return preg_replace('/[^A-Za-z0-9._-]/', '-', (string) $value) ?: ''; }
function esc_url_raw($value): string { return filter_var((string) $value, FILTER_SANITIZE_URL) ?: ''; }
function absint($value): int { return abs((int) $value); }
function wp_unslash($value) { return $value; }
function wp_json_encode($value, int $flags = 0): string { return json_encode($value, $flags | JSON_THROW_ON_ERROR); }
function wp_remote_retrieve_response_code(array $response): int { return (int) ($response['response']['code'] ?? 0); }
function wp_remote_retrieve_body(array $response): string { return (string) ($response['body'] ?? ''); }
function wp_remote_retrieve_header(array $response, string $name): string { return (string) ($response['headers'][strtolower($name)] ?? ''); }
function get_option(string $name, $default = false) { return $GLOBALS['lyli_test_options'][$name] ?? $default; }
function current_user_can(string $capability): bool { return $capability === 'manage_woocommerce' && $GLOBALS['lyli_test_can_manage']; }
function wp_verify_nonce(string $nonce, string $action): bool { return $GLOBALS['lyli_test_nonce_valid'] && ('lyli_ghn_save_settings' === $action || str_starts_with($action, 'yoohw_vietnam_store_tools_shipping_action_') || str_starts_with($action, 'lyli_ghn_shipment_action_')); }
function wp_parse_url(string $url, int $component = -1) { return -1 === $component ? parse_url($url) : parse_url($url, $component); }
function remove_accents(string $text): string { return $text; }
function wc_get_weight($value, string $unit): float { return (float) $value * 1000; }
function wc_get_dimension($value, string $unit): float { return (float) $value; }
function wc_price($value): string { return (string) $value; }
function wp_strip_all_tags($value): string { return strip_tags((string) $value); }
function WC() { return (object) ['countries' => new class { public function get_states(string $country): array { return ['SG' => 'Thành phố Hồ Chí Minh']; } }]; }

final class Yoohw_Vietnam_Store_Tools_Vietnam_Address_Data
{
    public static function province_exists($code): bool { return '79' === (string) $code; }
    public static function ward_exists($ward, $province = ''): bool { return '26734' === (string) $ward && '79' === (string) $province; }
    public static function get_province_name($code): string { return 'Thành phố Hồ Chí Minh'; }
    public static function get_ward_name($ward, $province = ''): string { return 'Phường Tân Định'; }
}

final class Yoohw_Vietnam_Store_Tools_Shipping
{
    public static function get_order_shipping_data($order, bool $force = false): array
    {
        return $GLOBALS['lyli_test_shipping_data'];
    }
}

final class Test_Product
{
    public function __construct(private string $sku = '', private string $weight = '', private string $length = '', private string $width = '', private string $height = '') {}
    public function get_sku(): string { return $this->sku; }
    public function get_weight(): string { return $this->weight; }
    public function get_length(): string { return $this->length; }
    public function get_width(): string { return $this->width; }
    public function get_height(): string { return $this->height; }
}

final class Test_Item
{
    public function __construct(private ?Test_Product $product = null) {}
    public function get_quantity(): int { return 2; }
    public function get_name(): string { return 'Móc khóa len'; }
    public function get_total(): float { return 200000; }
    public function get_product(): ?Test_Product { return $this->product; }
}

class Test_Order
{
    private array $meta = [];
    public function __construct(private string $payment = 'cod', private bool $paid = false, private ?Test_Product $product = null) {}
    public function get_id(): int { return 120; }
    public function get_shipping_address_1(): string { return '12 Nguyễn Huệ'; }
    public function get_shipping_address_2(): string { return ''; }
    public function get_shipping_first_name(): string { return 'Ly'; }
    public function get_shipping_last_name(): string { return 'Nguyễn'; }
    public function get_shipping_country(): string { return 'VN'; }
    public function get_shipping_state(): string { return '79'; }
    public function get_shipping_city(): string { return '26734'; }
    public function get_shipping_phone(): string { return '0901 234 567'; }
    public function get_billing_phone(): string { return '0901 234 567'; }
    public function get_payment_method(): string { return $this->payment; }
    public function is_paid(): bool { return $this->paid; }
    public function get_total(): float { return 220000; }
    public function get_total_refunded(): float { return 20000; }
    public function get_customer_note(): string { return 'Gọi trước khi giao'; }
    public function get_items(): array { return [new Test_Item($this->product)]; }
    public function get_meta(string $key, bool $single = true) { return $this->meta[$key] ?? ''; }
    public function update_meta_data(string $key, $value): void { $this->meta[$key] = $value; }
    public function save_meta_data(): void {}
}

final class Test_Native_Order extends Test_Order
{
    public function get_shipping_state(): string { return 'SG'; }
    public function get_shipping_city(): string { return 'Phường Sài Gòn'; }
}

$plugin_dir = __DIR__ . '/../web/app/plugins/lyli-ghn-connector';
require_once $plugin_dir . '/includes/class-settings.php';
require_once $plugin_dir . '/includes/class-api-client.php';
require_once $plugin_dir . '/includes/contracts/interface-address-resolver.php';
require_once $plugin_dir . '/includes/contracts/interface-legacy-shipment-reader.php';
require_once $plugin_dir . '/includes/domain/class-address.php';
require_once $plugin_dir . '/includes/woocommerce/class-woo-address-resolver.php';
require_once $plugin_dir . '/includes/woocommerce/class-composite-address-resolver.php';
require_once $plugin_dir . '/includes/integrations/vietnam-store-toolkit/class-toolkit-address-resolver.php';
require_once $plugin_dir . '/includes/integrations/vietnam-store-toolkit/class-toolkit-legacy-shipment-reader.php';
require_once $plugin_dir . '/includes/woocommerce/class-shipment-repository.php';
require_once $plugin_dir . '/includes/class-order-mapper.php';
require_once $plugin_dir . '/includes/class-provider.php';
require_once $plugin_dir . '/includes/class-print-controller.php';

$failures = 0;
function check_ghn(string $label, bool $ok, string $detail = ''): void
{
    global $failures;
    echo ($ok ? 'PASS' : 'FAIL') . ' ' . $label . ($detail ? ' — ' . $detail : '') . PHP_EOL;
    if (! $ok) { ++$failures; }
}

$settings = [
    'enabled' => true,
    'environment' => 'test',
    'shop_id' => 12345,
    'service_type_id' => 2,
    'payment_type_id' => 1,
    'required_note' => 'KHONGCHOXEMHANG',
    'cod_policy' => 'cod_gateway_only',
    'insurance_policy' => 'remaining_total',
    'package_weight_g' => 500,
    'package_length_cm' => 20,
    'package_width_cm' => 15,
    'package_height_cm' => 10,
];
$GLOBALS['lyli_test_options']['lyli_ghn_settings'] = $settings;
$GLOBALS['lyli_test_options']['lyli_ghn_token'] = 'secret-token';

$address_resolver = new Lyli\GHN\WooCommerce\Composite_Address_Resolver([
    new Lyli\GHN\Integrations\VietnamStoreToolkit\Toolkit_Address_Resolver(),
    new Lyli\GHN\WooCommerce\Woo_Address_Resolver(),
]);
$mapper = new Lyli\GHN\Order_Mapper($address_resolver);
$repository = new Lyli\GHN\WooCommerce\Shipment_Repository(new Lyli\GHN\Integrations\VietnamStoreToolkit\Toolkit_Legacy_Shipment_Reader());
$payload = $mapper->build_payload(new Test_Order(), $settings);
check_ghn('Two-level address maps to GHN name mode', is_array($payload)
    && true === $payload['is_new_to_address']
    && 'Phường Tân Định' === $payload['to_ward_name']
    && ! isset($payload['to_district_name']));
$native_mapper = new Lyli\GHN\Order_Mapper(new Lyli\GHN\WooCommerce\Woo_Address_Resolver());
$native_payload = $native_mapper->build_payload(new Test_Native_Order(), $settings);
check_ghn('WooCommerce-native address fallback resolves names', is_array($native_payload)
    && 'Thành phố Hồ Chí Minh' === $native_payload['to_province_name']
    && 'Phường Sài Gòn' === $native_payload['to_ward_name']);
check_ghn('WooCommerce-native fallback rejects unresolved numeric ward', is_wp_error($native_mapper->build_payload(new Test_Order(), $settings)));
check_ghn('COD is conservative and refund-aware', 200000 === $payload['cod_amount']);
check_ghn('Insurance value is capped from remaining total policy', 200000 === $payload['insurance_value']);
check_ghn('Non-COD order never collects COD', 0 === Lyli\GHN\Order_Mapper::cod_amount(new Test_Order('bacs'), $settings));

$missing_package = $settings;
$missing_package['package_weight_g'] = 0;
check_ghn('Missing package dimensions block creation', is_wp_error($mapper->build_payload(new Test_Order(), $missing_package)));
$heavy = $settings;
$heavy['service_type_id'] = 5;
check_ghn('Heavy service blocks missing product dimensions', is_wp_error($mapper->build_payload(new Test_Order(), $heavy)));

$calls = [];
$transport = static function (string $url, array $args) use (&$calls): array {
    $calls[] = [$url, $args];
    return ['response' => ['code' => 200], 'headers' => [], 'body' => json_encode(['code' => 200, 'data' => ['order_code' => 'GHN123']])];
};
$client = new Lyli\GHN\Api_Client($settings, 'secret-token', $transport);
$created = $client->create_order($payload);
check_ghn('Request serialization uses test allowlist endpoint', str_ends_with($calls[0][0], '/v2/shipping-order/create'));
$request_body = json_decode($calls[0][1]['body'], true);
check_ghn('Request carries JSON and required headers', $request_body['client_order_code'] === 'LYLI-WC-120'
    && $calls[0][1]['headers']['Token'] === 'secret-token'
    && $calls[0][1]['headers']['ShopId'] === '12345');

$timeout_client = new Lyli\GHN\Api_Client($settings, 'secret-token', static fn () => new WP_Error('timeout', 'Timed out with secret-token'));
$timeout = $timeout_client->order_info('GHN123');
check_ghn('Transport errors redact Token', is_wp_error($timeout) && ! str_contains($timeout->get_error_message(), 'secret-token'));
$error_client = new Lyli\GHN\Api_Client($settings, 'secret-token', static fn () => ['response' => ['code' => 400], 'headers' => [], 'body' => json_encode(['code' => 400, 'message' => 'Bad secret-token'])]);
$api_error = $error_client->order_info('GHN123');
check_ghn('GHN errors redact Token', is_wp_error($api_error) && ! str_contains($api_error->get_error_message(), 'secret-token'));

$idempotent_calls = [];
$idempotent_client = new Lyli\GHN\Api_Client($settings, 'secret-token', static function (string $url, array $args) use (&$idempotent_calls): array {
    $idempotent_calls[] = $url;
    return ['response' => ['code' => 200], 'headers' => [], 'body' => json_encode(['code' => 200, 'data' => ['order_code' => 'EXISTING', 'status' => 'ready_to_pick', 'service_type_id' => 2]])];
});
$provider = new Lyli\GHN\Provider($idempotent_client, $mapper, $repository);
$shipment = $provider->create_shipment(new Test_Order());
check_ghn('Existing client_order_code prevents duplicate create', 1 === count($idempotent_calls)
    && str_ends_with($idempotent_calls[0], '/detail-by-client-code')
    && 'EXISTING' === $shipment['tracking_code']);
check_ghn('Canonical shipment state is connector-owned', 'ghn' === $shipment['provider'] && 'EXISTING' === $shipment['order_code']);
$legacy_order = new Test_Order();
$legacy_order->update_meta_data('_vck_shipping_provider', 'lyli_ghn');
$legacy_order->update_meta_data('_vck_shipping_tracking_code', 'LEGACY123');
$legacy_order->update_meta_data('_vck_shipping_status_id', 'picked');
$legacy = $repository->read($legacy_order);
check_ghn('Legacy Toolkit shipment metadata remains readable', 'LEGACY123' === ($legacy['tracking_code'] ?? '') && 'vietnam-store-toolkit' === ($legacy['legacy_source'] ?? ''));
check_ghn('GHN status mapping stays separate from Woo status', 'Đã giao hàng' === Lyli\GHN\Provider::status_label('delivered'));

$test_print_client = new Lyli\GHN\Api_Client($settings, 'secret-token');
$production_settings = $settings;
$production_settings['environment'] = 'production';
$production_print_client = new Lyli\GHN\Api_Client($production_settings, 'secret-token');
$test_a5_url = $test_print_client->build_print_url('test-token-123', 'a5');
$production_a5_url = $production_print_client->build_print_url('prod-token-123', 'a5');
check_ghn('Print test A5 URL', 'https://dev-online-gateway.ghn.vn/a5/public-api/printA5?token=test-token-123' === $test_a5_url);
check_ghn('Print production A5 URL', 'https://online-gateway.ghn.vn/a5/public-api/printA5?token=prod-token-123' === $production_a5_url);
check_ghn('Print 80x80 URL', str_contains((string) $test_print_client->build_print_url('test-token-123', '80x80'), '/a5/public-api/print80x80?token='));
check_ghn('Print 52x70 URL', str_contains((string) $test_print_client->build_print_url('test-token-123', '52x70'), '/a5/public-api/print52x70?token='));
check_ghn('Print rejects wrong scheme', ! $test_print_client->validate_print_url('http://dev-online-gateway.ghn.vn/a5/public-api/printA5?token=x', 'a5'));
check_ghn('Print rejects arbitrary host', ! $test_print_client->validate_print_url('https://example.com/a5/public-api/printA5?token=x', 'a5'));
check_ghn('Print rejects lookalike, subdomain and userinfo hosts',
    ! $test_print_client->validate_print_url('https://dev-online-gateway.ghn.vn.evil.test/a5/public-api/printA5?token=x', 'a5')
    && ! $test_print_client->validate_print_url('https://dev-online-gateway.ghn.vn@evil.test/a5/public-api/printA5?token=x', 'a5')
    && ! $test_print_client->validate_print_url('https://sub.dev-online-gateway.ghn.vn/a5/public-api/printA5?token=x', 'a5'));
check_ghn('Print rejects arbitrary path', ! $test_print_client->validate_print_url('https://dev-online-gateway.ghn.vn/a5/public-api/other?token=x', 'a5'));
$encoded_url = $test_print_client->build_print_url('token +/value', 'a5');
check_ghn('Print token is URL encoded', is_string($encoded_url) && str_ends_with($encoded_url, '?token=token%20%2B%2Fvalue'));

$print_calls = [];
$print_transport = static function (string $url, array $args) use (&$print_calls): array {
    $print_calls[] = [$url, $args];
    return ['response' => ['code' => 200], 'headers' => [], 'body' => json_encode(['code' => 200, 'data' => ['token' => 'temporary-print-token']])];
};
$print_provider = new Lyli\GHN\Provider(new Lyli\GHN\Api_Client($settings, 'secret-token', $print_transport), $mapper, $repository);
$print_order = new Test_Order();
$repository->save($print_order, ['order_code' => 'GHN123', 'tracking_code' => 'GHN123']);
$options_before_print = $GLOBALS['lyli_test_options'];
$print_result = $print_provider->print_shipment($print_order);
check_ghn('Print token is not persisted', $options_before_print === $GLOBALS['lyli_test_options']);
check_ghn('Print document is not fetched server-side', 1 === count($print_calls)
    && str_ends_with($print_calls[0][0], '/v2/a5/gen-token')
    && is_array($print_result)
    && isset($print_result['url'])
    && ! isset($print_result['content']));

$_POST = ['_lyli_ghn_nonce' => 'test'];
$GLOBALS['lyli_test_can_manage'] = false;
check_ghn('Settings deny missing manage_woocommerce', is_wp_error(Lyli\GHN\Settings::authorize_save()));
check_ghn('Shipment mutation denies missing manage_woocommerce', is_wp_error($provider->create_shipment(new Test_Order())));
check_ghn('Print denies missing manage_woocommerce', is_wp_error(Lyli\GHN\Print_Controller::authorize(120, 'valid')));
$GLOBALS['lyli_test_can_manage'] = true;
$GLOBALS['lyli_test_nonce_valid'] = false;
check_ghn('Settings reject invalid nonce', is_wp_error(Lyli\GHN\Settings::authorize_save()));
check_ghn('Print rejects invalid nonce', is_wp_error(Lyli\GHN\Print_Controller::authorize(120, 'invalid')));

$owned_source = '';
$iterator = new RecursiveIteratorIterator(new RecursiveDirectoryIterator($plugin_dir));
foreach ($iterator as $file) { if ($file->isFile() && 'php' === $file->getExtension()) { $owned_source .= file_get_contents($file->getPathname()); } }
$owned_source .= file_get_contents($plugin_dir . '/lyli-ghn-connector.php');
check_ghn('No unauthenticated AJAX or webhook surface', ! str_contains($owned_source, 'wp_ajax_nopriv') && ! str_contains($owned_source, 'register_rest_route'));
check_ghn('No live checkout rate method in V1', ! str_contains($owned_source, 'WC_Shipping_Method'));
check_ghn('Token is never rendered back into input value', str_contains($owned_source, 'name="lyli_ghn[token]" value=""'));
check_ghn('Print opens external page without opener or referrer', str_contains($owned_source, "setAttribute('rel', 'noopener noreferrer')"));
$core_source = file_get_contents($plugin_dir . '/includes/class-api-client.php')
    . file_get_contents($plugin_dir . '/includes/class-order-mapper.php')
    . file_get_contents($plugin_dir . '/includes/class-provider.php')
    . file_get_contents($plugin_dir . '/includes/woocommerce/class-shipment-repository.php')
    . file_get_contents($plugin_dir . '/includes/domain/class-address.php');
check_ghn('GHN domain and persistence contain no Toolkit import', false === stripos($core_source, 'yoohw'));
check_ghn('Standalone Woo admin uses capability and nonce authorization', str_contains($owned_source, 'lyli_ghn_shipment_action_') && str_contains($owned_source, "current_user_can('manage_woocommerce')"));

exit($failures > 0 ? 1 : 0);
