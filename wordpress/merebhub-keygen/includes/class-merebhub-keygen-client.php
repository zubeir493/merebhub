<?php

defined('ABSPATH') || exit;

final class MerebHub_Keygen_Client
{
    public function configured(): bool
    {
        return $this->account_id() !== '' && $this->token() !== '';
    }

    /**
     * @return array{products: int, policies: int}
     */
    public function connection_summary(): array
    {
        if (! $this->configured()) {
            throw new RuntimeException('Enter a Keygen account ID and API token first.');
        }

        return [
            'products' => count($this->products()),
            'policies' => count($this->policies()),
        ];
    }

    public function products(): array
    {
        return $this->cached('merebhub_keygen_products', function (): array {
            return $this->collection('/products?page[size]=100');
        });
    }

    public function policies(): array
    {
        return $this->cached('merebhub_keygen_policies', function (): array {
            return $this->collection('/policies?page[size]=100');
        });
    }

    public function create_license(string $policy_id, WC_Order $order, WC_Order_Item_Product $item): array
    {
        return $this->request('POST', '/licenses', [
            'data' => [
                'type' => 'licenses',
                'attributes' => [
                    'name' => sprintf('%s - %s', $item->get_name(), $order->get_billing_email()),
                    'maxMachines' => max(1, $item->get_quantity()),
                    'metadata' => [
                        'wooCommerceOrderId' => $order->get_id(),
                        'wooCommerceOrderItemId' => $item->get_id(),
                        'buyerEmail' => $order->get_billing_email(),
                        'activationLimit' => max(1, $item->get_quantity()),
                    ],
                ],
                'relationships' => [
                    'policy' => ['data' => ['type' => 'policies', 'id' => $policy_id]],
                ],
            ],
        ]);
    }

    public function renew_license(string $license_id, int $max_machines): array
    {
        $this->request('POST', '/licenses/'.rawurlencode($license_id).'/actions/renew');

        return $this->request('PATCH', '/licenses/'.rawurlencode($license_id), [
            'data' => [
                'type' => 'licenses',
                'id' => $license_id,
                'attributes' => ['maxMachines' => max(1, $max_machines)],
            ],
        ]);
    }

    public function revoke_license(string $license_id): void
    {
        $this->request('DELETE', '/licenses/'.rawurlencode($license_id).'/actions/revoke');
    }

    public function flush_cache(): void
    {
        delete_transient('merebhub_keygen_products');
        delete_transient('merebhub_keygen_policies');
    }

    private function collection(string $path): array
    {
        $response = $this->request('GET', $path);

        return is_array($response['data'] ?? null) ? $response['data'] : [];
    }

    private function cached(string $key, callable $resolver): array
    {
        $cached = get_transient($key);

        if (is_array($cached)) {
            return $cached;
        }

        $value = $resolver();
        set_transient($key, $value, 5 * MINUTE_IN_SECONDS);

        return $value;
    }

    private function request(string $method, string $path, ?array $body = null): array
    {
        if (! $this->configured()) {
            throw new RuntimeException('Keygen account ID and token are not configured.');
        }

        $arguments = [
            'method' => $method,
            'timeout' => 20,
            'headers' => [
                'Accept' => 'application/vnd.api+json',
                'Content-Type' => 'application/vnd.api+json',
                'Authorization' => 'Bearer '.$this->token(),
            ],
        ];

        if ($body !== null) {
            $arguments['body'] = wp_json_encode($body);
        }

        $response = wp_remote_request($this->base_url().$path, $arguments);

        if (is_wp_error($response)) {
            throw new RuntimeException($response->get_error_message());
        }

        $status = wp_remote_retrieve_response_code($response);
        $payload = json_decode(wp_remote_retrieve_body($response), true);

        if ($status < 200 || $status >= 300) {
            $detail = $payload['errors'][0]['detail'] ?? 'Keygen request failed.';
            throw new RuntimeException(sanitize_text_field($detail));
        }

        return is_array($payload) ? $payload : [];
    }

    private function base_url(): string
    {
        $configured = defined('MEREBHUB_KEYGEN_API_URL')
            ? MEREBHUB_KEYGEN_API_URL
            : ($this->settings()['keygen_api_url'] ?? 'https://api.keygen.sh');

        return untrailingslashit($configured).'/v1/accounts/'.rawurlencode($this->account_id());
    }

    private function account_id(): string
    {
        return (string) (defined('MEREBHUB_KEYGEN_ACCOUNT_ID')
            ? MEREBHUB_KEYGEN_ACCOUNT_ID
            : ($this->settings()['keygen_account_id'] ?? ''));
    }

    private function token(): string
    {
        return (string) (defined('MEREBHUB_KEYGEN_API_TOKEN')
            ? MEREBHUB_KEYGEN_API_TOKEN
            : ($this->settings()['keygen_api_token'] ?? ''));
    }

    private function settings(): array
    {
        $settings = get_option('merebhub_settings', []);

        return is_array($settings) ? $settings : [];
    }
}
