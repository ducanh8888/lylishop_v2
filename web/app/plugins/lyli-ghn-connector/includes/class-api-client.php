<?php

namespace Lyli\GHN;

final class Api_Client
{
    private const BASE_URLS = [
        'test' => 'https://dev-online-gateway.ghn.vn/shiip/public-api/',
        'production' => 'https://online-gateway.ghn.vn/shiip/public-api/',
    ];

    /** @var array<string,mixed> */
    private array $settings;
    private string $token;
    /** @var callable|null */
    private $transport;

    /** @param array<string,mixed> $settings */
    public function __construct(array $settings, string $token, ?callable $transport = null)
    {
        $this->settings = $settings;
        $this->token = trim($token);
        $this->transport = $transport;
    }

    /** @param array<string,mixed> $payload */
    public function preview_order(array $payload)
    {
        return $this->request('v2/shipping-order/preview', $payload, true, 12);
    }

    /** @param array<string,mixed> $payload */
    public function create_order(array $payload)
    {
        return $this->request('v2/shipping-order/create', $payload, true, 15);
    }

    public function order_info(string $order_code)
    {
        return $this->request('v2/shipping-order/detail', ['order_code' => $order_code], false, 8);
    }

    public function order_info_by_client_code(string $client_order_code)
    {
        return $this->request('v2/shipping-order/detail-by-client-code', ['client_order_code' => $client_order_code], false, 8);
    }

    public function cancel_order(string $order_code)
    {
        return $this->request('v2/switch-status/cancel', ['order_codes' => [$order_code]], true, 10);
    }

    public function print_order(string $order_code)
    {
        return $this->request('v2/a5/gen-token', ['order_codes' => [$order_code]], false, 10);
    }

    public function fetch_print_document(string $print_token)
    {
        if (! preg_match('/^[A-Za-z0-9_-]{8,200}$/', $print_token)) {
            return new \WP_Error('lyli_ghn_invalid_print_token', __('GHN trả về print token không hợp lệ.', 'lyli-ghn-connector'));
        }

        $environment = $this->environment();
        $host = 'production' === $environment
            ? 'https://online-gateway.ghn.vn'
            : 'https://dev-online-gateway.ghn.vn';
        $url = $host . '/a5/public-api/printA5?token=' . rawurlencode($print_token);
        $response = $this->send($url, [
            'method' => 'GET',
            'timeout' => 12,
            'redirection' => 2,
            'reject_unsafe_urls' => true,
            'limit_response_size' => 10485760,
        ]);

        if (is_wp_error($response)) {
            return $this->redacted_error($response, $print_token);
        }

        $status = (int) wp_remote_retrieve_response_code($response);
        $body = (string) wp_remote_retrieve_body($response);
        if ($status < 200 || $status >= 300 || '' === $body) {
            return new \WP_Error('lyli_ghn_print_failed', __('Không thể tải nhãn GHN.', 'lyli-ghn-connector'));
        }

        $content_type = (string) wp_remote_retrieve_header($response, 'content-type');
        $content_type = strtolower(trim(explode(';', $content_type)[0] ?? ''));
        if ('application/pdf' !== $content_type) {
            return new \WP_Error('lyli_ghn_print_type', __('Định dạng nhãn GHN không được hỗ trợ.', 'lyli-ghn-connector'));
        }

        return ['content' => $body, 'content_type' => $content_type];
    }

    public function is_not_found_error($error): bool
    {
        if (! is_wp_error($error)) {
            return false;
        }

        $message = remove_accents(strtolower($error->get_error_message()));
        foreach (['not found', 'not exist', 'khong ton tai', 'order_not_found'] as $needle) {
            if (str_contains($message, $needle)) {
                return true;
            }
        }

        $data = $error->get_error_data();
        return is_array($data) && in_array((int) ($data['ghn_code'] ?? 0), [404, 400404], true);
    }

    /** @param array<string,mixed> $payload */
    private function request(string $path, array $payload, bool $with_shop_id, int $timeout)
    {
        if ('' === $this->token) {
            return new \WP_Error('lyli_ghn_missing_token', __('Chưa cấu hình GHN Token.', 'lyli-ghn-connector'));
        }

        $base = self::BASE_URLS[$this->environment()];
        $headers = [
            'Accept' => 'application/json',
            'Content-Type' => 'application/json',
            'Token' => $this->token,
        ];
        if ($with_shop_id) {
            $headers['ShopId'] = (string) absint($this->settings['shop_id'] ?? 0);
        }

        $response = $this->send($base . $path, [
            'method' => 'POST',
            'timeout' => $timeout,
            'redirection' => 0,
            'reject_unsafe_urls' => true,
            'limit_response_size' => 1048576,
            'headers' => $headers,
            'body' => wp_json_encode($payload, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES),
        ]);

        if (is_wp_error($response)) {
            return $this->redacted_error($response);
        }

        $http_status = (int) wp_remote_retrieve_response_code($response);
        $body = (string) wp_remote_retrieve_body($response);
        $decoded = json_decode($body, true);
        if (! is_array($decoded)) {
            return new \WP_Error('lyli_ghn_invalid_json', __('GHN trả về dữ liệu JSON không hợp lệ.', 'lyli-ghn-connector'), ['http_status' => $http_status]);
        }

        $ghn_code = isset($decoded['code']) ? (int) $decoded['code'] : 0;
        if ($http_status < 200 || $http_status >= 300 || 200 !== $ghn_code) {
            $message = isset($decoded['message']) ? (string) $decoded['message'] : __('GHN từ chối yêu cầu.', 'lyli-ghn-connector');
            return new \WP_Error(
                'lyli_ghn_api_error',
                $this->redact($message),
                [
                    'http_status' => $http_status,
                    'ghn_code' => $ghn_code,
                    'ghn_code_message' => sanitize_text_field((string) ($decoded['code_message'] ?? '')),
                ]
            );
        }

        return $decoded['data'] ?? [];
    }

    /** @param array<string,mixed> $args */
    private function send(string $url, array $args)
    {
        if (null !== $this->transport) {
            return ($this->transport)($url, $args);
        }

        return wp_safe_remote_request($url, $args);
    }

    private function environment(): string
    {
        return 'production' === ($this->settings['environment'] ?? '') ? 'production' : 'test';
    }

    private function redacted_error(\WP_Error $error, string $additional_secret = ''): \WP_Error
    {
        return new \WP_Error(
            'lyli_ghn_transport_error',
            $this->redact($error->get_error_message(), $additional_secret),
            $error->get_error_data()
        );
    }

    private function redact(string $message, string $additional_secret = ''): string
    {
        $secrets = array_filter([$this->token, $additional_secret]);
        return sanitize_text_field(str_replace($secrets, '[redacted]', $message));
    }
}
