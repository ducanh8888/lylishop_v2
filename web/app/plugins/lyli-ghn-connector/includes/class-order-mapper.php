<?php

namespace Lyli\GHN;

use Lyli\GHN\Contracts\Address_Resolver;
use Lyli\GHN\Domain\Address;
use Lyli\GHN\Domain\Cod_Policy;
use Lyli\GHN\Domain\Package;

final class Order_Mapper
{
    public function __construct(private Address_Resolver $address_resolver)
    {
    }

    /** @param object $order @param array<string,mixed> $settings */
    public function build_payload($order, array $settings)
    {
        $package = Package::from_settings($settings);
        if (is_wp_error($package)) {
            return $package;
        }
        $destination = $this->destination($order);
        if (is_wp_error($destination)) {
            return $destination;
        }
        $items = $this->items($order, (int) $settings['service_type_id']);
        if (is_wp_error($items)) {
            return $items;
        }

        $payload = array_merge($destination, $package->to_array(), [
            'client_order_code' => self::client_order_code($order),
            'service_type_id' => (int) $settings['service_type_id'],
            'payment_type_id' => (int) $settings['payment_type_id'],
            'required_note' => (string) $settings['required_note'],
            'cod_amount' => Cod_Policy::amount($order, $settings),
            'insurance_value' => Cod_Policy::insurance_value($order, $settings),
            'content' => $this->content($items),
            'items' => $items,
        ]);

        $note = sanitize_textarea_field((string) $order->get_customer_note());
        if ('' !== $note) {
            $payload['note'] = mb_substr($note, 0, 5000);
        }

        return $payload;
    }

    public static function client_order_code($order): string
    {
        /* Retained for idempotency compatibility with existing GHN test/production shipments. */
        return 'LYLI-WC-' . absint($order->get_id());
    }

    private function destination($order)
    {
        $address = $this->address_resolver->resolve($order);
        if (is_wp_error($address)) {
            return $address;
        }
        if (! $address instanceof Address) {
            return new \WP_Error('lyli_ghn_address_contract', __('Address resolver trả về dữ liệu không hợp lệ.', 'lyli-ghn-connector'));
        }
        return $address->to_ghn_payload();
    }

    private function items($order, int $service_type_id)
    {
        $mapped = [];
        foreach ($order->get_items() as $item) {
            $quantity = max(1, (int) $item->get_quantity());
            $product = method_exists($item, 'get_product') ? $item->get_product() : null;
            $entry = [
                'name' => mb_substr(sanitize_text_field((string) $item->get_name()), 0, 255),
                'quantity' => $quantity,
                'price' => max(0, (int) round(((float) $item->get_total()) / $quantity)),
            ];
            if ($product && '' !== (string) $product->get_sku()) {
                $entry['code'] = mb_substr(sanitize_text_field((string) $product->get_sku()), 0, 100);
            }
            if (5 === $service_type_id) {
                $dimensions = $this->product_dimensions($product);
                if (is_wp_error($dimensions)) {
                    return $dimensions;
                }
                $entry = array_merge($entry, $dimensions);
            }
            $mapped[] = $entry;
        }

        return [] === $mapped
            ? new \WP_Error('lyli_ghn_no_items', __('Đơn hàng không có sản phẩm để tạo vận đơn GHN.', 'lyli-ghn-connector'))
            : $mapped;
    }

    private function product_dimensions($product)
    {
        if (! $product) {
            return new \WP_Error('lyli_ghn_missing_item_dimensions', __('Hàng nặng GHN cần đủ khối lượng và kích thước cho từng sản phẩm.', 'lyli-ghn-connector'));
        }
        $values = [
            'weight' => (int) round((float) wc_get_weight($product->get_weight(), 'g')),
            'length' => (int) round((float) wc_get_dimension($product->get_length(), 'cm')),
            'width' => (int) round((float) wc_get_dimension($product->get_width(), 'cm')),
            'height' => (int) round((float) wc_get_dimension($product->get_height(), 'cm')),
        ];
        return min($values) < 1
            ? new \WP_Error('lyli_ghn_missing_item_dimensions', __('Hàng nặng GHN cần đủ khối lượng và kích thước cho từng sản phẩm.', 'lyli-ghn-connector'))
            : $values;
    }

    /** @param array<int,array<string,mixed>> $items */
    private function content(array $items): string
    {
        return mb_substr(implode(', ', array_map(static fn (array $item): string => (string) $item['name'], $items)), 0, 2000);
    }
}
