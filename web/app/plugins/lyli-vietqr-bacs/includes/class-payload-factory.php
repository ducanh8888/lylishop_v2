<?php

namespace Lyli\VietQRBACS;

use Liopay\VietQR\Builder\QRIBFTBuilder;

final class Payload_Factory
{
    public function build(string $bank_bin, string $account_number, int $amount, string $reference): string
    {
        if (! preg_match('/^\d{6}$/', $bank_bin)) {
            throw new \InvalidArgumentException('Bank BIN must contain exactly six digits.');
        }
        if (! preg_match('/^[A-Za-z0-9]{1,19}$/', $account_number)) {
            throw new \InvalidArgumentException('Account number must contain 1-19 letters or digits.');
        }
        if ($amount <= 0) {
            throw new \InvalidArgumentException('Transfer amount must be positive.');
        }
        $reference = strtoupper(preg_replace('/[^A-Za-z0-9]/', '', $reference) ?: '');
        if ('' === $reference) {
            throw new \InvalidArgumentException('Transfer reference is required.');
        }
        $reference = substr($reference, 0, 25);

        return (new QRIBFTBuilder())
            ->setPointOfInitiation('12')
            ->setBeneficiaryBankBin($bank_bin)
            ->setConsumerId($account_number)
            ->setIBFTToAccount()
            ->setAmount((string) $amount)
            ->setReferenceLabel($reference)
            ->setPurposeOfTransaction($reference)
            ->build();
    }

    public function reference(string $prefix, string $order_number): string
    {
        $prefix = strtoupper(preg_replace('/[^A-Za-z0-9]/', '', $prefix) ?: 'LYLI');
        $order_number = strtoupper(preg_replace('/[^A-Za-z0-9]/', '', $order_number) ?: '');
        return substr($prefix . $order_number, 0, 25);
    }

    public function amount($order): int
    {
        if (! is_object($order) || ! method_exists($order, 'get_total')) {
            throw new \InvalidArgumentException('A valid WooCommerce order is required.');
        }
        $refunded = method_exists($order, 'get_total_refunded') ? (float) $order->get_total_refunded() : 0.0;
        return max(0, (int) round((float) $order->get_total() - $refunded));
    }
}
