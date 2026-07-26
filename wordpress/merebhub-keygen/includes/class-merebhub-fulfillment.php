<?php

defined('ABSPATH') || exit;

final class MerebHub_Fulfillment
{
    private MerebHub_Keygen_Client $client;

    public function __construct(MerebHub_Keygen_Client $client)
    {
        $this->client = $client;

        add_action('woocommerce_order_status_processing', [$this, 'fulfill']);
        add_action('woocommerce_order_status_completed', [$this, 'fulfill']);
        add_action('woocommerce_order_status_refunded', [$this, 'revoke']);
        add_action('woocommerce_email_after_order_table', [$this, 'email_licenses'], 20, 4);
        add_action('woocommerce_order_details_after_order_table', [$this, 'account_licenses']);
    }

    public function fulfill(int $order_id): void
    {
        $order = wc_get_order($order_id);

        if (! $order || ! $this->client->configured()) {
            return;
        }

        foreach ($order->get_items() as $item) {
            if (! $item instanceof WC_Order_Item_Product) {
                continue;
            }

            $product = $item->get_product();
            $keygen_product_id = $product?->get_meta('_merebhub_keygen_product_id');
            $policy_id = $product?->get_meta('_merebhub_keygen_policy_id');

            if (! $product || ! $keygen_product_id || ! $policy_id) {
                continue;
            }

            try {
                $this->license_for_item($order, $item, $policy_id);
            } catch (Throwable $exception) {
                $order->add_order_note(sprintf('MerebHub license delivery failed for %s: %s', $item->get_name(), $exception->getMessage()));
                $order->update_meta_data('_merebhub_fulfillment_error', $exception->getMessage());
                $order->save();

                return;
            }
        }

        $order->delete_meta_data('_merebhub_fulfillment_error');
        $order->update_meta_data('_merebhub_fulfilled_at', gmdate('c'));
        $order->save();
    }

    public function email_licenses(WC_Order $order, bool $sent_to_admin, bool $plain_text, $email): void
    {
        if ($sent_to_admin) {
            return;
        }

        $this->render_licenses($order, $plain_text);
    }

    public function account_licenses(WC_Order $order): void
    {
        $this->render_licenses($order, false);
    }

    public function revoke(int $order_id): void
    {
        $order = wc_get_order($order_id);

        if (! $order || ! $this->client->configured()) {
            return;
        }

        foreach ($order->get_items() as $item) {
            $license_id = (string) $item->get_meta('_merebhub_license_id');

            if (! $license_id || $item->get_meta('_merebhub_license_status') === 'revoked') {
                continue;
            }

            try {
                $this->client->revoke_license($license_id);
                $item->update_meta_data('_merebhub_license_status', 'revoked');
                $item->save();
            } catch (Throwable $exception) {
                $order->add_order_note('MerebHub license revocation failed: '.$exception->getMessage());
            }
        }
    }

    private function render_licenses(WC_Order $order, bool $plain_text): void
    {
        $licenses = $this->licenses($order);

        if ($licenses === []) {
            return;
        }

        if ($plain_text) {
            echo "\n".esc_html__('Your software licenses', 'merebhub')."\n";
            foreach ($licenses as $license) {
                echo esc_html($license['name'].': '.$license['key']."\n");
            }

            return;
        }

        echo '<h2>'.esc_html__('Your software licenses', 'merebhub').'</h2>';
        echo '<table cellspacing="0" cellpadding="8" style="width:100%;border:1px solid #e5e7eb" border="1">';
        foreach ($licenses as $license) {
            echo '<tr><td><strong>'.esc_html($license['name']).'</strong><br><code>'.esc_html($license['key']).'</code><br><small>'.esc_html(sprintf(__('%d activations', 'merebhub'), $license['limit'])).'</small></td></tr>';
        }
        echo '</table>';
    }

    private function license_for_item(WC_Order $order, WC_Order_Item_Product $item, string $policy_id): array
    {
        $existing_id = (string) $item->get_meta('_merebhub_license_id');

        if ($existing_id) {
            return [
                'data' => [
                    'id' => $existing_id,
                    'attributes' => [
                        'key' => $item->get_meta('_merebhub_license_key'),
                        'expiry' => $item->get_meta('_merebhub_license_expiry') ?: null,
                    ],
                ],
            ];
        }

        $prior_license = $this->prior_license($order, $item->get_product_id());
        $billing_model = function_exists('get_field') ? get_field('merebhub_billing_model', $item->get_product_id()) : 'one_time';
        $response = $prior_license && $billing_model === 'manual_subscription'
            ? $this->client->renew_license($prior_license['id'], max(1, $item->get_quantity()))
            : $this->client->create_license($policy_id, $order, $item);
        $license = $response['data'] ?? [];
        $attributes = $license['attributes'] ?? [];

        if (empty($license['id']) || empty($attributes['key'])) {
            throw new RuntimeException('Keygen returned an incomplete license.');
        }

        $item->update_meta_data('_merebhub_license_id', $license['id']);
        $item->update_meta_data('_merebhub_license_key', $attributes['key']);
        $item->update_meta_data('_merebhub_license_expiry', $attributes['expiry'] ?? '');
        $item->update_meta_data('_merebhub_activation_limit', max(1, $item->get_quantity()));
        $item->update_meta_data('_merebhub_license_status', 'active');
        $item->save();

        return $response;
    }

    private function prior_license(WC_Order $current_order, int $product_id): ?array
    {
        $orders = wc_get_orders([
            'billing_email' => $current_order->get_billing_email(),
            'status' => ['wc-processing', 'wc-completed'],
            'exclude' => [$current_order->get_id()],
            'limit' => 20,
            'orderby' => 'date',
            'order' => 'DESC',
        ]);

        foreach ($orders as $order) {
            foreach ($order->get_items() as $item) {
                if ($item->get_product_id() === $product_id && $item->get_meta('_merebhub_license_id')) {
                    return [
                        'id' => $item->get_meta('_merebhub_license_id'),
                        'key' => $item->get_meta('_merebhub_license_key'),
                    ];
                }
            }
        }

        return null;
    }

    private function licenses(WC_Order $order): array
    {
        $licenses = [];

        foreach ($order->get_items() as $item) {
            $key = $item->get_meta('_merebhub_license_key');

            if ($key) {
                $licenses[] = [
                    'name' => $item->get_name(),
                    'key' => $key,
                    'limit' => $item->get_meta('_merebhub_activation_limit'),
                ];
            }
        }

        return $licenses;
    }
}
