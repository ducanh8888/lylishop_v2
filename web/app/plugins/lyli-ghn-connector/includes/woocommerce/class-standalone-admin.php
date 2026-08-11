<?php

namespace Lyli\GHN\WooCommerce;

use Lyli\GHN\Application\Shipment_Application;
use Lyli\GHN\Admin\Create_Summary;
use Lyli\GHN\Print_Controller;
use Lyli\GHN\Settings;

final class Standalone_Admin
{
    private const ACTIONS = [
        'create' => 'lyli_ghn_create_shipment',
        'sync' => 'lyli_ghn_sync_shipment',
        'cancel' => 'lyli_ghn_cancel_shipment',
        'print' => 'lyli_ghn_print_shipment',
    ];

    public function __construct(private Shipment_Application $application, private Shipment_Repository $shipments)
    {
    }

    public function init(): void
    {
        add_action('add_meta_boxes', [$this, 'add_meta_box']);
        add_action('admin_notices', [$this, 'admin_notice']);
        foreach (self::ACTIONS as $operation => $action) {
            add_action('admin_post_' . $action, fn () => $this->handle($operation));
        }
    }

    public function add_meta_box(): void
    {
        $screens = ['shop_order'];
        if (function_exists('wc_get_page_screen_id')) {
            $screens[] = wc_get_page_screen_id('shop-order');
        }
        foreach (array_unique($screens) as $screen) {
            add_meta_box('lyli-ghn-shipment', __('Vận đơn GHN', 'lyli-ghn-connector'), [$this, 'render'], $screen, 'side', 'high');
        }
    }

    /** @param mixed $post_or_order */
    public function render($post_or_order): void
    {
        if (! current_user_can('manage_woocommerce')) {
            return;
        }
        $order = $post_or_order instanceof \WC_Order ? $post_or_order : wc_get_order($post_or_order->ID ?? 0);
        if (! $order) {
            return;
        }
        if (! Settings::is_ready()) {
            echo '<p>' . esc_html__('Connector đang tắt hoặc chưa đủ cấu hình.', 'lyli-ghn-connector') . '</p>';
            return;
        }

        $data = $this->shipments->read($order);
        if (! empty($data['tracking_code'])) {
            echo '<p><strong>' . esc_html__('Mã GHN:', 'lyli-ghn-connector') . '</strong> ' . esc_html((string) $data['tracking_code']) . '</p>';
            echo '<p><strong>' . esc_html__('Trạng thái:', 'lyli-ghn-connector') . '</strong> ' . esc_html((string) ($data['status'] ?? '')) . '</p>';
            $this->button($order, 'sync', __('Đồng bộ', 'lyli-ghn-connector'));
            $this->button($order, 'print', __('In nhãn', 'lyli-ghn-connector'), true);
            $this->button($order, 'cancel', __('Hủy vận đơn', 'lyli-ghn-connector'));
        } else {
            Create_Summary::render($this->application, $order);
            $this->button($order, 'create', __('Tạo vận đơn GHN', 'lyli-ghn-connector'));
        }
    }

    private function button($order, string $operation, string $label, bool $new_tab = false): void
    {
        $action = self::ACTIONS[$operation];
        echo '<form method="post" action="' . esc_url(admin_url('admin-post.php')) . '"' . ($new_tab ? ' target="_blank" rel="noopener noreferrer"' : '') . ' style="display:inline-block;margin:0 6px 6px 0">';
        echo '<input type="hidden" name="action" value="' . esc_attr($action) . '">';
        echo '<input type="hidden" name="order_id" value="' . esc_attr((string) $order->get_id()) . '">';
        wp_nonce_field($this->nonce_action($order->get_id()), '_lyli_ghn_nonce');
        echo '<button type="submit" class="button">' . esc_html($label) . '</button></form>';
    }

    private function handle(string $operation): void
    {
        $order_id = isset($_POST['order_id']) ? absint($_POST['order_id']) : 0;
        $nonce = isset($_POST['_lyli_ghn_nonce']) && is_scalar($_POST['_lyli_ghn_nonce'])
            ? sanitize_text_field(wp_unslash($_POST['_lyli_ghn_nonce'])) : '';
        $authorized = Print_Controller::authorize($order_id, $nonce, $this->nonce_action($order_id));
        if (is_wp_error($authorized)) {
            wp_die(esc_html($authorized->get_error_message()), '', ['response' => 403]);
        }
        if ('print' === $operation) {
            Print_Controller::handle_authorized_request($order_id, $nonce, $this->nonce_action($order_id), $this->application);
        }

        $order = wc_get_order($order_id);
        if (! $order) {
            wp_die(esc_html__('Không tìm thấy đơn WooCommerce.', 'lyli-ghn-connector'), '', ['response' => 404]);
        }
        $result = $this->application->{$operation}($order);
        $args = is_wp_error($result)
            ? ['lyli_ghn_error' => $result->get_error_message()]
            : ['lyli_ghn_notice' => $operation];
        wp_safe_redirect(add_query_arg($args, $order->get_edit_order_url()));
        exit;
    }

    public function admin_notice(): void
    {
        if (! current_user_can('manage_woocommerce')) {
            return;
        }
        $notice = isset($_GET['lyli_ghn_notice']) ? sanitize_key(wp_unslash($_GET['lyli_ghn_notice'])) : '';
        $error = isset($_GET['lyli_ghn_error']) ? sanitize_text_field(wp_unslash($_GET['lyli_ghn_error'])) : '';
        if ('' !== $error) {
            echo '<div class="notice notice-error is-dismissible"><p>' . esc_html($error) . '</p></div>';
        } elseif ('' !== $notice) {
            echo '<div class="notice notice-success is-dismissible"><p>' . esc_html__('Đã cập nhật vận đơn GHN.', 'lyli-ghn-connector') . '</p></div>';
        }
    }

    private function nonce_action(int $order_id): string
    {
        return 'lyli_ghn_shipment_action_' . $order_id;
    }

}
