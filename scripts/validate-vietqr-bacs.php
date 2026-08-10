<?php

define('ABSPATH', __DIR__ . '/../');

final class WP_Error
{
    public function __construct(private string $code = '', private string $message = '') {}
    public function get_error_code(): string { return $this->code; }
    public function get_error_message(): string { return $this->message; }
}
function is_wp_error($value): bool { return $value instanceof WP_Error; }
function __(string $text, string $domain = ''): string { return $text; }
function sanitize_text_field($value): string { return trim(strip_tags((string) $value)); }
function sanitize_key($value): string { return preg_replace('/[^a-z0-9_\-]/', '', strtolower((string) $value)) ?: ''; }
function remove_accents(string $value): string { return strtr($value, ['Đ' => 'D', 'đ' => 'd']); }
function wp_parse_url(string $url) { return parse_url($url); }

final class Test_VietQR_Order
{
    public function __construct(private string $gateway = 'bacs', private bool $paid = false) {}
    public function get_payment_method(): string { return $this->gateway; }
    public function is_paid(): bool { return $this->paid; }
    public function get_total(): float { return 250000; }
    public function get_total_refunded(): float { return 50000; }
    public function get_order_number(): string { return 'WC-120'; }
    public function get_id(): int { return 120; }
}

require_once __DIR__ . '/../web/app/plugins/vietqr-bacs-for-woocommerce/includes/class-qr-builder.php';

$failures = 0;
function check_vietqr(string $label, bool $ok): void
{
    global $failures;
    echo ($ok ? 'PASS' : 'FAIL') . ' ' . $label . PHP_EOL;
    if (! $ok) { ++$failures; }
}

$settings = [
    'bank_bin' => '970436',
    'account_number' => '000123456789',
    'account_holder' => 'EXAMPLE OWNER',
    'qr_template' => 'compact2',
    'description_template' => 'ORDER-{order_number}-{order_id}',
];
$builder = new \VietQR\BACS\Qr_Builder();
$result = $builder->build(new Test_VietQR_Order(), $settings);
check_vietqr('BACS order builds QR data', is_array($result));
check_vietqr('VietQR URL uses exact HTTPS host', is_array($result) && str_starts_with($result['url'], 'https://img.vietqr.io/image/'));
check_vietqr('Amount due is refund-aware', is_array($result) && 200000 === $result['amount']);
check_vietqr('Description is deterministic and sanitized', is_array($result) && 'ORDER-WC-120-120' === $result['description']);
check_vietqr('Merchant fields are URL encoded', is_array($result) && str_contains($result['url'], 'accountName=EXAMPLE%20OWNER'));
check_vietqr('Non-BACS order is rejected', is_wp_error($builder->build(new Test_VietQR_Order('cod'), $settings)));
check_vietqr('Paid order is rejected', is_wp_error($builder->build(new Test_VietQR_Order('bacs', true), $settings)));
check_vietqr('Incomplete merchant settings are rejected', is_wp_error($builder->build(new Test_VietQR_Order(), [])));

$source = '';
$plugin_dir = __DIR__ . '/../web/app/plugins/vietqr-bacs-for-woocommerce';
foreach (glob($plugin_dir . '/includes/*.php') ?: [] as $file) { $source .= file_get_contents($file); }
$source .= file_get_contents($plugin_dir . '/vietqr-bacs-for-woocommerce.php');
check_vietqr('No webhook or public mutation endpoint', ! str_contains($source, 'register_rest_route') && ! str_contains($source, 'wp_ajax_nopriv'));
check_vietqr('No automatic payment settlement', ! str_contains($source, 'payment_complete') && ! str_contains($source, 'set_status'));
check_vietqr('Plugin extends native BACS instead of adding a gateway', ! str_contains($source, 'woocommerce_payment_gateways'));
check_vietqr('No merchant account fixture is present in plugin source', ! str_contains($source, '000123456789'));

exit($failures > 0 ? 1 : 0);
