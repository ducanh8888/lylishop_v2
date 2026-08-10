<?php

namespace Lyli\GHN\WooCommerce;

use Lyli\GHN\Contracts\Address_Resolver;
use Lyli\GHN\Domain\Address;

final class Composite_Address_Resolver implements Address_Resolver
{
    /** @param array<int,Address_Resolver> $resolvers */
    public function __construct(private array $resolvers)
    {
    }

    public function resolve($order)
    {
        $last_error = null;
        foreach ($this->resolvers as $resolver) {
            $result = $resolver->resolve($order);
            if ($result instanceof Address) {
                return $result;
            }
            if (is_wp_error($result)) {
                $last_error = $result;
            }
        }

        return $last_error ?: new \WP_Error('lyli_ghn_address_unresolved', __('Không thể chuẩn hóa địa chỉ giao GHN.', 'lyli-ghn-connector'));
    }
}
