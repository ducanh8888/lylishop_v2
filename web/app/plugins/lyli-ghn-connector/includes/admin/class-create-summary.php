<?php

namespace Lyli\GHN\Admin;

use Lyli\GHN\Application\Shipment_Application;

final class Create_Summary
{
    public static function render(Shipment_Application $application, $order): void
    {
        $payload = $application->preview_payload($order);
        if (is_wp_error($payload)) {
            echo '<p class="description" style="color:#b32d2e">' . esc_html($payload->get_error_message()) . '</p>';
            return;
        }

        echo '<p class="description">' . esc_html(sprintf(
            __('Kiện %1$s cm, %2$s g. Thu hộ dự kiến: %3$s.', 'lyli-ghn-connector'),
            $payload['length'] . '×' . $payload['width'] . '×' . $payload['height'],
            $payload['weight'],
            wp_strip_all_tags(wc_price($payload['cod_amount']))
        )) . '</p>';
    }

    private function __construct()
    {
    }
}
