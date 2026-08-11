<?php

declare(strict_types=1);

const LYLI_VN_ADDRESS_RELEASE = 'v4.0.0';
const LYLI_VN_ADDRESS_URL = 'https://raw.githubusercontent.com/thanglequoc/vietnamese-provinces-database/v4.0.0/json/vn_only_simplified_json_generated_data_vn_units_minified.json';
const LYLI_VN_ADDRESS_SHA256 = 'f36c1b4fd6f0c61065936c365395d66cc4a1d12b4e0f313819f2930fd27293e2';
const LYLI_VN_ADDRESS_PROVINCES = 34;
const LYLI_VN_ADDRESS_WARDS = 3321;

$source = $argv[1] ?? LYLI_VN_ADDRESS_URL;
$target = dirname(__DIR__) . '/web/app/plugins/lyli-vietnam-address/data/vietnam-addresses.json';

if (filter_var($source, FILTER_VALIDATE_URL)) {
    $context = stream_context_create([
        'http' => [
            'timeout' => 30,
            'follow_location' => 0,
            'user_agent' => 'LyliShop address data updater/' . LYLI_VN_ADDRESS_RELEASE,
        ],
        'ssl' => [
            'verify_peer' => true,
            'verify_peer_name' => true,
        ],
    ]);
    $json = @file_get_contents($source, false, $context);
} else {
    $json = @file_get_contents($source);
}

if (! is_string($json) || '' === $json) {
    fwrite(STDERR, "Unable to read the pinned address dataset.\n");
    exit(1);
}

$actual_hash = hash('sha256', $json);
if (! hash_equals(LYLI_VN_ADDRESS_SHA256, $actual_hash)) {
    fwrite(STDERR, "Address dataset checksum mismatch: {$actual_hash}\n");
    exit(1);
}

try {
    $rows = json_decode($json, true, 512, JSON_THROW_ON_ERROR);
} catch (JsonException $exception) {
    fwrite(STDERR, "Invalid address JSON: {$exception->getMessage()}\n");
    exit(1);
}

$province_codes = [];
$ward_codes = [];
$ward_count = 0;
foreach ($rows as $province) {
    $province_code = (string) ($province['Code'] ?? '');
    if (! preg_match('/^\d{2}$/', $province_code) || isset($province_codes[$province_code])) {
        fwrite(STDERR, "Invalid or duplicate province code.\n");
        exit(1);
    }
    $province_codes[$province_code] = true;
    foreach (($province['Wards'] ?? []) as $ward) {
        $ward_code = (string) ($ward['Code'] ?? '');
        if (! preg_match('/^\d{5}$/', $ward_code)
            || isset($ward_codes[$ward_code])
            || $province_code !== (string) ($ward['ProvinceCode'] ?? '')
        ) {
            fwrite(STDERR, "Invalid ward code or province relationship.\n");
            exit(1);
        }
        $ward_codes[$ward_code] = true;
        $ward_count++;
    }
}

if (LYLI_VN_ADDRESS_PROVINCES !== count($province_codes) || LYLI_VN_ADDRESS_WARDS !== $ward_count) {
    fwrite(STDERR, sprintf("Unexpected dataset counts: %d provinces / %d wards.\n", count($province_codes), $ward_count));
    exit(1);
}

$directory = dirname($target);
if (! is_dir($directory) && ! mkdir($directory, 0775, true) && ! is_dir($directory)) {
    fwrite(STDERR, "Unable to create address data directory.\n");
    exit(1);
}

$temporary = $target . '.tmp';
if (false === file_put_contents($temporary, $json, LOCK_EX) || ! rename($temporary, $target)) {
    @unlink($temporary);
    fwrite(STDERR, "Unable to atomically update address data.\n");
    exit(1);
}

printf("Address data %s verified: %d provinces / %d wards / sha256 %s\n", LYLI_VN_ADDRESS_RELEASE, count($province_codes), $ward_count, $actual_hash);
