<?php

namespace Lyli\VietnamAddress;

final class Woo_Adapter
{
    public function __construct(private Repository $repository, private string $script_url)
    {
    }

    public function init(): void
    {
        add_filter('woocommerce_states', [$this, 'states']);
        add_filter('woocommerce_country_locale', [$this, 'country_locale']);
        add_filter('woocommerce_checkout_fields', [$this, 'checkout_fields']);
        add_filter('woocommerce_billing_fields', [$this, 'billing_fields'], 20, 2);
        add_filter('woocommerce_shipping_fields', [$this, 'shipping_fields'], 20, 2);
        add_filter('woocommerce_formatted_address_replacements', [$this, 'formatted_address'], 20, 2);
        add_action('wp_enqueue_scripts', [$this, 'enqueue']);
        add_action('wc_ajax_lyli_vn_wards', [$this, 'ajax_wards']);
        add_action('woocommerce_after_checkout_validation', [$this, 'validate_checkout'], 20, 2);
        add_action('woocommerce_after_save_address_validation', [$this, 'validate_account_address'], 20, 4);
    }

    /** @param array<string,array<string,string>> $states */
    public function states(array $states): array
    {
        $states['VN'] = $this->repository->provinces();
        return $states;
    }

    /** @param array<string,mixed> $locale */
    public function country_locale(array $locale): array
    {
        $locale['VN']['state']['label'] = __('Tỉnh/Thành phố', 'lyli-vietnam-address');
        $locale['VN']['city']['label'] = __('Phường/Xã', 'lyli-vietnam-address');
        $locale['VN']['city']['required'] = true;
        return $locale;
    }

    /** @param array<string,mixed> $fields */
    public function checkout_fields(array $fields): array
    {
        foreach (['billing', 'shipping'] as $scope) {
            $state_key = $scope . '_state';
            $city_key = $scope . '_city';
            if ('VN' === $this->address_country($scope) && isset($fields[$scope][$city_key])) {
                $state = $this->request_value($state_key);
                $fields[$scope][$city_key] = $this->ward_field($fields[$scope][$city_key], $state);
            }
        }
        return $fields;
    }

    /** @param array<string,mixed> $fields */
    public function billing_fields(array $fields, string $country): array
    {
        if ('VN' === $country && isset($fields['billing_city'])) {
            $fields['billing_city'] = $this->ward_field($fields['billing_city'], $this->request_value('billing_state'));
        }
        return $fields;
    }

    /** @param array<string,mixed> $fields */
    public function shipping_fields(array $fields, string $country): array
    {
        if ('VN' === $country && isset($fields['shipping_city'])) {
            $fields['shipping_city'] = $this->ward_field($fields['shipping_city'], $this->request_value('shipping_state'));
        }
        return $fields;
    }

    public function enqueue(): void
    {
        if (! (is_checkout() || is_account_page())) {
            return;
        }
        wp_enqueue_script('lyli-vietnam-address', $this->script_url, ['jquery'], VERSION, true);
        wp_localize_script('lyli-vietnam-address', 'lyliVietnamAddress', [
            'endpoint' => \WC_AJAX::get_endpoint('lyli_vn_wards'),
            'nonce' => wp_create_nonce('lyli_vn_wards'),
            'placeholder' => __('Chọn Phường/Xã', 'lyli-vietnam-address'),
        ]);
    }

    public function ajax_wards(): void
    {
        check_ajax_referer('lyli_vn_wards', 'nonce');
        $province = isset($_GET['province']) ? sanitize_text_field(wp_unslash($_GET['province'])) : '';
        wp_send_json_success(['wards' => $this->repository->wards($province)]);
    }

    /** @param array<string,mixed> $data */
    public function validate_checkout(array $data, \WP_Error $errors): void
    {
        foreach (['billing', 'shipping'] as $scope) {
            if ('shipping' === $scope && empty($data['ship_to_different_address'])) {
                continue;
            }
            if ('VN' !== strtoupper((string) ($data[$scope . '_country'] ?? 'VN'))) {
                continue;
            }
            $province = (string) ($data[$scope . '_state'] ?? '');
            $ward = (string) ($data[$scope . '_city'] ?? '');
            if (null === $this->repository->resolve($province, $ward, '')) {
                $errors->add('lyli_vietnam_address', __('Vui lòng chọn Tỉnh/Thành phố và Phường/Xã hợp lệ.', 'lyli-vietnam-address'));
            }
        }
    }

    /** @param array<string,mixed> $address */
    public function validate_account_address(int $user_id, string $scope, array $address, $customer): void
    {
        unset($user_id, $address);
        if (! is_object($customer)) {
            return;
        }
        $country_getter = 'get_' . $scope . '_country';
        $state_getter = 'get_' . $scope . '_state';
        $city_getter = 'get_' . $scope . '_city';
        if (! method_exists($customer, $country_getter)
            || ! method_exists($customer, $state_getter)
            || ! method_exists($customer, $city_getter)
            || 'VN' !== strtoupper((string) $customer->{$country_getter}())
        ) {
            return;
        }
        if (null === $this->repository->resolve((string) $customer->{$state_getter}(), (string) $customer->{$city_getter}(), '')) {
            wc_add_notice(__('Vui lòng chọn Tỉnh/Thành phố và Phường/Xã hợp lệ.', 'lyli-vietnam-address'), 'error');
        }
    }

    /** @param array<string,string> $replacements @param array<string,string> $args */
    public function formatted_address(array $replacements, array $args): array
    {
        if ('VN' !== strtoupper((string) ($args['country'] ?? ''))) {
            return $replacements;
        }
        $province = (string) ($args['state'] ?? '');
        $ward = (string) ($args['city'] ?? '');
        $replacements['{state}'] = $this->repository->province_name($province) ?? $replacements['{state}'];
        $replacements['{city}'] = $this->repository->ward_name($province, $ward) ?? $replacements['{city}'];
        return $replacements;
    }

    /** @param array<string,mixed> $field @return array<string,mixed> */
    private function ward_field(array $field, string $province): array
    {
        $field['type'] = 'select';
        $field['label'] = __('Phường/Xã', 'lyli-vietnam-address');
        $field['required'] = true;
        $field['class'] = array_values(array_unique(array_merge((array) ($field['class'] ?? []), ['address-field', 'update_totals_on_change'])));
        $field['options'] = ['' => __('Chọn Phường/Xã', 'lyli-vietnam-address')] + $this->repository->wards($province);
        return $field;
    }

    private function request_value(string $key): string
    {
        if (isset($_POST[$key])) {
            return sanitize_text_field(wp_unslash($_POST[$key]));
        }
        if (is_user_logged_in()) {
            return sanitize_text_field((string) get_user_meta(get_current_user_id(), $key, true));
        }
        return '';
    }

    private function address_country(string $scope): string
    {
        $country = strtoupper($this->request_value($scope . '_country'));
        if ('' === $country && function_exists('WC') && \WC() && \WC()->customer) {
            $method = 'get_' . $scope . '_country';
            if (method_exists(\WC()->customer, $method)) {
                $country = strtoupper((string) \WC()->customer->{$method}());
            }
        }
        if ('' === $country && function_exists('wc_get_base_location')) {
            $country = strtoupper((string) (wc_get_base_location()['country'] ?? ''));
        }
        return $country;
    }
}
