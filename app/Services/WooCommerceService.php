<?php

namespace App\Services;

use App\Enums\ProductStatus;
use App\Models\Product;
use App\Models\User;
use Illuminate\Http\Client\PendingRequest;
use Illuminate\Support\Arr;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Str;
use RuntimeException;

class WooCommerceService
{
    public function fetchProducts(array $query = []): array
    {
        return $this->request()
            ->get($this->endpoint('products'), $query)
            ->throw()
            ->json();
    }

    public function fetchProduct(int $wooCommerceProductId): array
    {
        return $this->request()
            ->get($this->endpoint("products/{$wooCommerceProductId}"))
            ->throw()
            ->json();
    }

    public function createProduct(Product $product): array
    {
        return $this->request()
            ->post($this->endpoint('products'), $this->productPayload($product))
            ->throw()
            ->json();
    }

    public function updateProduct(Product $product): array
    {
        return $this->request()
            ->put($this->endpoint("products/{$product->wc_product_id}"), $this->productPayload($product))
            ->throw()
            ->json();
    }

    public function syncProduct(Product $product): array
    {
        return $product->wc_product_id
            ? $this->updateProduct($product)
            : $this->createProduct($product);
    }

    public function isConfigured(): bool
    {
        return filled(config('services.woocommerce.api_url'))
            && filled(config('services.woocommerce.consumer_key'))
            && filled(config('services.woocommerce.consumer_secret'))
            && filled(config('services.woocommerce.webhook_secret'));
    }

    private function productPayload(Product $product): array
    {
        return [
            'name' => $product->name,
            'type' => 'simple',
            'regular_price' => (string) $product->price,
            'description' => $product->description,
            'short_description' => $product->tagline ?: Str::limit(strip_tags($product->description), 180),
            'status' => $product->status === ProductStatus::Published ? 'publish' : 'draft',
            'categories' => [
                ['name' => $product->category],
            ],
        ];
    }

    /**
     * @param  array<int, array{product_id: int, quantity: int}>  $lineItems
     */
    public function createOrder(array $lineItems, User $buyer): array
    {
        $payload = [
            'billing' => [
                'first_name' => $buyer->name,
                'email' => $buyer->email,
            ],
            'line_items' => $lineItems,
            'set_paid' => false,
            'meta_data' => [
                ['key' => '_merebhub_user_id', 'value' => (string) $buyer->id],
            ],
        ];

        if ($paymentMethod = config('services.woocommerce.payment_method')) {
            $payload['payment_method'] = $paymentMethod;
        }

        return $this->request()
            ->post($this->endpoint('orders'), $payload)
            ->throw()
            ->json();
    }

    public function checkoutUrl(array $order): string
    {
        if ($paymentUrl = Arr::get($order, 'payment_url')) {
            return $paymentUrl;
        }

        $checkoutUrl = rtrim((string) config('services.woocommerce.checkout_url'), '/');
        $siteUrl = rtrim((string) config('services.woocommerce.site_url'), '/');
        $baseUrl = $checkoutUrl ?: ($siteUrl ? $siteUrl.'/checkout' : $this->derivedSiteUrl().'/checkout');

        return sprintf(
            '%s/order-pay/%s/?pay_for_order=true&key=%s',
            rtrim($baseUrl, '/'),
            Arr::get($order, 'id'),
            Arr::get($order, 'order_key')
        );
    }

    private function request(): PendingRequest
    {
        $consumerKey = config('services.woocommerce.consumer_key');
        $consumerSecret = config('services.woocommerce.consumer_secret');

        if (! $consumerKey || ! $consumerSecret) {
            throw new RuntimeException('WooCommerce API credentials are not configured.');
        }

        return Http::connectTimeout(5)
            ->timeout((int) config('services.woocommerce.timeout', 20))
            ->retry([200, 500, 1000], throw: false)
            ->acceptJson()
            ->asJson()
            ->withBasicAuth($consumerKey, $consumerSecret);
    }

    private function endpoint(string $path): string
    {
        $apiUrl = rtrim((string) config('services.woocommerce.api_url'), '/');

        if (! $apiUrl) {
            throw new RuntimeException('WC_API_URL is not configured.');
        }

        return $apiUrl.'/'.ltrim($path, '/');
    }

    private function derivedSiteUrl(): string
    {
        $apiUrl = (string) config('services.woocommerce.api_url');
        $siteUrl = preg_replace('#/wp-json.*$#', '', $apiUrl);

        return rtrim($siteUrl ?: $apiUrl, '/');
    }
}
