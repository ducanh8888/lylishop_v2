<?php

namespace Lyli\GHN\Domain;

final class Cod_Policy
{
    /** @param object $order @param array<string,mixed> $settings */
    public static function amount($order, array $settings): int
    {
        if ('cod_gateway_only' !== ($settings['cod_policy'] ?? 'disabled')) {
            return 0;
        }
        if ('cod' !== (string) $order->get_payment_method() || $order->is_paid()) {
            return 0;
        }

        return min(50000000, self::remaining_total($order));
    }

    /** @param object $order */
    public static function insurance_value($order, array $settings): int
    {
        return 'remaining_total' === ($settings['insurance_policy'] ?? 'disabled')
            ? min(5000000, self::remaining_total($order))
            : 0;
    }

    /** @param object $order */
    private static function remaining_total($order): int
    {
        return max(0, (int) round((float) $order->get_total() - (float) $order->get_total_refunded()));
    }
}
