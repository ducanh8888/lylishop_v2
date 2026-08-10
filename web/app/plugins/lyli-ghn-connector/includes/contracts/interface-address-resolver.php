<?php

namespace Lyli\GHN\Contracts;

interface Address_Resolver
{
    /** @param object $order @return \Lyli\GHN\Domain\Address|\WP_Error */
    public function resolve($order);
}
