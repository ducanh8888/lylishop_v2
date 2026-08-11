<?php

declare(strict_types=1);

define('ABSPATH', dirname(__DIR__) . '/web/wp/');

$failures = [];
$passes = 0;

function shipping_check(bool $condition, string $message): void
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

function add_action(...$args): void {}
function add_filter(...$args): void {}

class WooCommerce {}

class WC_Product
{
    public function __construct(private string $weight = '', private bool $ships = true) {}
    public function get_weight(): string { return $this->weight; }
    public function needs_shipping(): bool { return $this->ships; }
}

final class Shipping_Test_Rate
{
    public function __construct(private string $method, private string $label) {}
    public function get_method_id(): string { return $this->method; }
    public function get_label(): string { return $this->label; }
}

require_once dirname(__DIR__) . '/web/app/plugins/lyli-shipping-policy/includes/class-shipping-guard.php';

use Lyli\ShippingPolicy\Shipping_Guard;

$guard = new Shipping_Guard();
$target = new Shipping_Test_Rate('flat_rate', 'Vận chuyển');
$other_flat = new Shipping_Test_Rate('flat_rate', 'Giao hàng khác');
$pickup = new Shipping_Test_Rate('local_pickup', 'Nhận tại cửa hàng');
$ghn = new Shipping_Test_Rate('lyli_ghn', 'GHN');

/** @return array<string,mixed> */
function shipping_package(float $amount, array $items = []): array
{
    return ['contents_cost' => $amount, 'contents' => $items, 'destination' => ['country' => 'VN']];
}

/** @return array<string,mixed> */
function shipping_item(string $weight, float $quantity = 1.0, bool $ships = true): array
{
    return ['data' => new WC_Product($weight, $ships), 'quantity' => $quantity];
}

$matrix = [
    ['normal below free threshold', 499999.0, [shipping_item('0.999')], true],
    ['free threshold is inclusive', 500000.0, [shipping_item('0.5')], true],
    ['above free threshold remains eligible', 500001.0, [shipping_item('0.5')], true],
    ['weight boundary is inclusive', 100000.0, [shipping_item('1.000')], true],
    ['weight above boundary is rejected', 100000.0, [shipping_item('1.001')], false],
    ['amount boundary is inclusive', 100000000.0, [shipping_item('0.5')], true],
    ['amount above boundary is rejected', 100000001.0, [shipping_item('0.5')], false],
    ['both ceilings exceeded is rejected', 100000001.0, [shipping_item('1.001')], false],
];

foreach ($matrix as [$label, $amount, $items, $expected]) {
    shipping_check($expected === $guard->is_eligible(shipping_package($amount, $items)), $label);
}

shipping_check(true === $guard->is_eligible(shipping_package(0.0, [shipping_item('')])), 'missing product weight contributes zero');
shipping_check(true === $guard->is_eligible(shipping_package(100000.0, [shipping_item('0.499', 2.0)])), 'quantity weight aggregation below ceiling');
shipping_check(true === $guard->is_eligible(shipping_package(100000.0, [shipping_item('0.5', 2.0)])), 'quantity weight aggregation equals ceiling');
shipping_check(false === $guard->is_eligible(shipping_package(100000.0, [shipping_item('0.4', 3.0)])), 'quantity weight aggregation exceeds ceiling');
shipping_check(true === $guard->is_eligible(shipping_package(100000.0, [shipping_item('0.3'), shipping_item('0.7')])), 'mixed products sum exactly to ceiling');
shipping_check(true === $guard->is_eligible(shipping_package(450000.0, [shipping_item('0.5')])), 'discounted contents_cost is the amount basis');
shipping_check(true === $guard->is_eligible(shipping_package(100000.0, [shipping_item('20', 1.0, false)])), 'non-shipping products do not add weight');
shipping_check([] === $guard->filter_rates([], shipping_package(100000001.0)), 'empty rate set remains empty');

$rates = ['target' => $target, 'other' => $other_flat, 'pickup' => $pickup, 'ghn' => $ghn];
$filtered = $guard->filter_rates($rates, shipping_package(100000001.0));
shipping_check(! isset($filtered['target']), 'ineligible package removes Lyli native flat rate');
shipping_check(isset($filtered['other']), 'unrelated flat rate remains');
shipping_check(isset($filtered['pickup']), 'local pickup remains');
shipping_check(isset($filtered['ghn']), 'future GHN rate remains');
shipping_check($rates === $guard->filter_rates($rates, shipping_package(100000000.0, [shipping_item('1')])), 'eligible package leaves every rate untouched');

$plugin_source = file_get_contents(dirname(__DIR__) . '/web/app/plugins/lyli-shipping-policy/lyli-shipping-policy.php') ?: '';
$guard_source = file_get_contents(dirname(__DIR__) . '/web/app/plugins/lyli-shipping-policy/includes/class-shipping-guard.php') ?: '';
shipping_check(str_contains($guard_source, "'contents_cost'"), 'amount basis is the Woo package contents cost');
shipping_check(! preg_match('/WC_Shipping_Method|add_rate\s*\(|\$wpdb|register_rest_route|wp_ajax_/i', $plugin_source . $guard_source), 'plugin creates no shipping method, rate, SQL, REST or AJAX surface');

if ([] !== $failures) {
    fwrite(STDERR, sprintf("\n%d shipping policy validation(s) failed.\n", count($failures)));
    exit(1);
}

printf("\nShipping policy validation passed: %d assertions.\n", $passes);
