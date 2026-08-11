<?php

namespace Lyli\VietnamAddress;

final class Repository
{
    private static ?self $instance = null;

    /** @var array<string,string>|null */
    private ?array $provinces = null;

    /** @var array<string,array<string,string>>|null */
    private ?array $wards = null;

    public function __construct(private string $data_file = DATA_FILE)
    {
    }

    public static function instance(): self
    {
        return self::$instance ??= new self();
    }

    /** @return array<string,string> */
    public function provinces(): array
    {
        $this->load();
        return $this->provinces ?? [];
    }

    /** @return array<string,string> */
    public function wards(string $province_code): array
    {
        $this->load();
        return $this->wards[$this->normalize_province($province_code)] ?? [];
    }

    public function province_name(string $province_code): ?string
    {
        $provinces = $this->provinces();
        return $provinces[$this->normalize_province($province_code)] ?? null;
    }

    public function ward_name(string $province_code, string $ward_code): ?string
    {
        $wards = $this->wards($province_code);
        return $wards[$this->normalize_ward($ward_code)] ?? null;
    }

    public function resolve(string $province_code, string $ward_code, string $street): ?Address
    {
        $province_code = $this->normalize_province($province_code);
        $ward_code = $this->normalize_ward($ward_code);
        $province_name = $this->province_name($province_code);
        $ward_name = $this->ward_name($province_code, $ward_code);
        if (null === $province_name || null === $ward_name) {
            return null;
        }

        return new Address($province_code, $province_name, $ward_code, $ward_name, trim($street));
    }

    private function normalize_province(string $code): string
    {
        $code = preg_replace('/\D/', '', $code) ?: '';
        return str_pad($code, 2, '0', STR_PAD_LEFT);
    }

    private function normalize_ward(string $code): string
    {
        $code = preg_replace('/\D/', '', $code) ?: '';
        return str_pad($code, 5, '0', STR_PAD_LEFT);
    }

    private function load(): void
    {
        if (null !== $this->provinces) {
            return;
        }

        $json = @file_get_contents($this->data_file);
        if (! is_string($json) || ! hash_equals(DATA_SHA256, hash('sha256', $json))) {
            throw new \RuntimeException('Vietnam address dataset is missing or failed checksum verification.');
        }

        try {
            $rows = json_decode($json, true, 512, JSON_THROW_ON_ERROR);
        } catch (\JsonException $exception) {
            throw new \RuntimeException('Vietnam address dataset is invalid.', 0, $exception);
        }

        $this->provinces = [];
        $this->wards = [];
        foreach ($rows as $province) {
            $province_code = (string) ($province['Code'] ?? '');
            $this->provinces[$province_code] = (string) ($province['FullName'] ?? '');
            $this->wards[$province_code] = [];
            foreach (($province['Wards'] ?? []) as $ward) {
                $this->wards[$province_code][(string) ($ward['Code'] ?? '')] = (string) ($ward['FullName'] ?? '');
            }
        }
    }
}
