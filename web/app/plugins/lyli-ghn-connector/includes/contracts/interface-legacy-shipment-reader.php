<?php

namespace Lyli\GHN\Contracts;

interface Legacy_Shipment_Reader
{
    /** @param object $order @return array<string,mixed> */
    public function read($order): array;
}
