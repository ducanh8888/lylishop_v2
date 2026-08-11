<?php

namespace Lyli\VietnamAddress;

final class Address
{
    public function __construct(
        public readonly string $province_code,
        public readonly string $province_name,
        public readonly string $ward_code,
        public readonly string $ward_name,
        public readonly string $street
    ) {
    }

    /** @return array<string,string> */
    public function to_array(): array
    {
        return [
            'province_code' => $this->province_code,
            'province_name' => $this->province_name,
            'ward_code' => $this->ward_code,
            'ward_name' => $this->ward_name,
            'street' => $this->street,
        ];
    }
}
