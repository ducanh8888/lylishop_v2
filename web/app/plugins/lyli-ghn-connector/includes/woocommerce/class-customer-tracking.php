<?php

namespace Lyli\GHN\WooCommerce;

final class Customer_Tracking
{
    public function __construct(private Shipment_Repository $shipments)
    {
    }

    public function init(): void
    {
        add_action('woocommerce_thankyou', [$this, 'render'], 25);
        add_action('woocommerce_view_order', [$this, 'render'], 25);
    }

    public function render($order_id): void
    {
        $order = wc_get_order($order_id);
        if (! $order) {
            return;
        }
        $data = $this->shipments->read($order);
        if (empty($data['tracking_code'])) {
            return;
        }
        echo '<section class="woocommerce-order-details ghn-order-tracking"><h2 class="woocommerce-order-details__title">' . esc_html__('Thông tin vận chuyển', 'lyli-ghn-connector') . '</h2><table class="woocommerce-table shop_table shop_table_responsive"><tbody>';
        echo '<tr><th scope="row">' . esc_html__('Đơn vị', 'lyli-ghn-connector') . '</th><td>GHN</td></tr>';
        echo '<tr><th scope="row">' . esc_html__('Mã vận đơn', 'lyli-ghn-connector') . '</th><td><a href="https://donhang.ghn.vn/" target="_blank" rel="noopener noreferrer">' . esc_html((string) $data['tracking_code']) . '</a></td></tr>';
        echo '<tr><th scope="row">' . esc_html__('Trạng thái', 'lyli-ghn-connector') . '</th><td>' . esc_html((string) ($data['status'] ?? '')) . '</td></tr>';
        echo '</tbody></table></section>';
    }
}
