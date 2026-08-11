<?php

namespace Lyli\GHN\Integrations\VietnamStoreToolkit;

use Lyli\GHN\Application\Shipment_Application;
use Lyli\GHN\Admin\Create_Summary;
use Lyli\GHN\Print_Controller;

final class Toolkit_Adapter
{
    private const PRINT_ACTION = 'yoohw_vietnam_store_tools_print_shipment';
    private const PROVIDER_ID = 'lyli_ghn';
    private const NONCE_FIELD = 'yoohw_vietnam_store_tools_shipping_nonce';

    public function __construct(private Shipment_Application $application)
    {
    }

    public static function is_supported(): bool
    {
        return class_exists('Yoohw_Vietnam_Store_Tools_Shipping')
            && method_exists('Yoohw_Vietnam_Store_Tools_Shipping', 'get_order_shipping_data')
            && method_exists('Yoohw_Vietnam_Store_Tools_Shipping', 'update_order_shipping_data');
    }

    public function init(): void
    {
        add_filter('yoohw_vietnam_store_tools_shipping_providers', [$this, 'register_provider']);
        add_action('admin_post_' . self::PRINT_ACTION, [$this, 'handle_print'], 1);
        add_action('admin_footer', [$this, 'render_print_guard'], 1);
    }

    /** @param array<string,array<string,mixed>> $providers */
    public function register_provider(array $providers): array
    {
        $providers[self::PROVIDER_ID] = [
            'id' => self::PROVIDER_ID,
            'name' => __('GHN', 'lyli-ghn-connector'),
            'supports' => ['create', 'sync', 'cancel', 'print'],
            'render_create_fields' => [$this, 'render_create_fields'],
            'create_shipment' => fn ($order, array $context = []) => $this->toolkit_result($this->application->create($order)),
            'sync_shipment' => fn ($order, array $context = []) => $this->toolkit_result($this->application->sync($order)),
            'cancel_shipment' => fn ($order, array $context = []) => $this->toolkit_result($this->application->cancel($order)),
            'print_shipment' => [$this->application, 'print'],
        ];

        return $providers;
    }

    public function handle_print(): void
    {
        $provider_id = isset($_POST['provider_id']) && is_scalar($_POST['provider_id'])
            ? sanitize_key(wp_unslash($_POST['provider_id'])) : '';
        if (self::PROVIDER_ID !== $provider_id) {
            return;
        }

        $order_id = isset($_POST['order_id']) ? absint($_POST['order_id']) : 0;
        $nonce = isset($_POST[self::NONCE_FIELD]) && is_scalar($_POST[self::NONCE_FIELD])
            ? sanitize_text_field(wp_unslash($_POST[self::NONCE_FIELD])) : '';
        Print_Controller::handle_authorized_request(
            $order_id,
            $nonce,
            'yoohw_vietnam_store_tools_shipping_action_' . $order_id,
            $this->application
        );
    }

    public function render_create_fields($order, array $context = []): void
    {
        Create_Summary::render($this->application, $order);
    }

    /** @param mixed $result @return mixed */
    private function toolkit_result($result)
    {
        if (is_array($result)) {
            $result['provider'] = self::PROVIDER_ID;
            $result['provider_name'] = __('GHN', 'lyli-ghn-connector');
        }

        return $result;
    }

    public function render_print_guard(): void
    {
        if (! current_user_can('manage_woocommerce')) {
            return;
        }
        ?>
        <script>
        document.addEventListener('click', function (event) {
            var button = event.target.closest('[data-vck-shipping-action="<?php echo esc_js(self::PRINT_ACTION); ?>"][data-vck-shipping-provider-id="<?php echo esc_js(self::PROVIDER_ID); ?>"]');
            if (!button) return;
            event.preventDefault(); event.stopImmediatePropagation();
            var form = document.createElement('form');
            form.method = 'post'; form.action = <?php echo wp_json_encode(admin_url('admin-post.php')); ?>;
            form.target = '_blank'; form.setAttribute('rel', 'noopener noreferrer');
            var fields = {
                action: <?php echo wp_json_encode(self::PRINT_ACTION); ?>,
                order_id: button.getAttribute('data-vck-shipping-order-id') || '',
                provider_id: <?php echo wp_json_encode(self::PROVIDER_ID); ?>,
                <?php echo wp_json_encode(self::NONCE_FIELD); ?>: button.getAttribute('data-vck-shipping-nonce') || ''
            };
            Object.keys(fields).forEach(function (name) {
                var input = document.createElement('input');
                input.type = 'hidden'; input.name = name; input.value = fields[name]; form.appendChild(input);
            });
            document.body.appendChild(form); form.submit(); form.remove();
        }, true);
        </script>
        <?php
    }
}
