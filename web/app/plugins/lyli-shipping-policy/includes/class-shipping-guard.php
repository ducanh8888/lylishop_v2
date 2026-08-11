<?php

namespace Lyli\ShippingPolicy;

final class Shipping_Guard
{
    public const MAX_AMOUNT = 100000000.0;
    public const MAX_WEIGHT = 1.0;
    public const TARGET_METHOD = 'flat_rate';
    public const TARGET_LABEL = 'Vận chuyển';

    public function init(): void
    {
        add_filter('woocommerce_package_rates', [$this, 'filter_rates'], 20, 2);
    }

    /**
     * Remove only the native Lyli rate when the legacy Toolkit eligibility
     * ceiling is exceeded. WooCommerce remains responsible for zones, fees,
     * taxes and all other shipping methods.
     *
     * @param array<string,\WC_Shipping_Rate> $rates
     * @param array<string,mixed>             $package
     * @return array<string,\WC_Shipping_Rate>
     */
    public function filter_rates(array $rates, array $package): array
    {
        if ($this->is_eligible($package)) {
            return $rates;
        }

        foreach ($rates as $rate_id => $rate) {
            if ($this->is_target_rate($rate)) {
                unset($rates[$rate_id]);
            }
        }

        return $rates;
    }

    /** @param array<string,mixed> $package */
    public function is_eligible(array $package): bool
    {
        $amount = max(0.0, (float) ($package['contents_cost'] ?? 0.0));

        return $amount <= self::MAX_AMOUNT && $this->package_weight($package) <= self::MAX_WEIGHT;
    }

    /** @param array<string,mixed> $package */
    public function package_weight(array $package): float
    {
        $weight = 0.0;

        foreach (is_array($package['contents'] ?? null) ? $package['contents'] : [] as $item) {
            $product = $item['data'] ?? null;
            $quantity = max(0.0, (float) ($item['quantity'] ?? 0.0));

            if (! $product instanceof \WC_Product || ! $product->needs_shipping() || $quantity <= 0.0) {
                continue;
            }

            $weight += max(0.0, (float) $product->get_weight()) * $quantity;
        }

        return $weight;
    }

    private function is_target_rate(object $rate): bool
    {
        return method_exists($rate, 'get_method_id')
            && method_exists($rate, 'get_label')
            && self::TARGET_METHOD === $rate->get_method_id()
            && self::TARGET_LABEL === $rate->get_label();
    }
}
