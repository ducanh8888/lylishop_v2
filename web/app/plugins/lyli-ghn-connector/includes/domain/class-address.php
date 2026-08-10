<?php

namespace Lyli\GHN\Domain;

final class Address
{
    public function __construct(
        public readonly string $recipient,
        public readonly string $phone,
        public readonly string $province_name,
        public readonly string $ward_name,
        public readonly string $street,
        public readonly string $province_code = '',
        public readonly string $ward_code = ''
    ) {
    }

    /** @return array<string,mixed> */
    public function to_ghn_payload(): array
    {
        return [
            'to_name' => mb_substr(sanitize_text_field($this->recipient), 0, 1024),
            'to_phone' => mb_substr(preg_replace('/[^0-9+]/', '', $this->phone) ?: '', 0, 20),
            'to_address' => mb_substr(sanitize_text_field($this->street . ', ' . $this->ward_name . ', ' . $this->province_name), 0, 1024),
            'to_ward_name' => sanitize_text_field($this->ward_name),
            'to_province_name' => sanitize_text_field($this->province_name),
            'is_new_to_address' => true,
        ];
    }
}
