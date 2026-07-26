<?php

namespace App\Http\Requests;

use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Foundation\Http\FormRequest;

class FulfillmentWebhookRequest extends FormRequest
{
    /**
     * Determine if the user is authorized to make this request.
     */
    public function authorize(): bool
    {
        $secret = (string) config('services.woocommerce.callback_secret');
        $signature = (string) $this->header('X-MerebHub-Signature');
        $expected = hash_hmac('sha256', $this->getContent(), $secret);

        return filled($secret) && hash_equals($expected, $signature);
    }

    /**
     * Get the validation rules that apply to the request.
     *
     * @return array<string, ValidationRule|array<mixed>|string>
     */
    public function rules(): array
    {
        return [
            'event_id' => ['required', 'string', 'max:255'],
            'order.id' => ['required', 'integer'],
            'order.email' => ['required', 'email', 'max:255'],
            'order.currency' => ['required', 'string', 'size:3'],
            'order.total' => ['required', 'numeric', 'min:0'],
            'order.paid_at' => ['nullable', 'date'],
            'items' => ['required', 'array', 'min:1'],
            'items.*.order_item_id' => ['required', 'integer'],
            'items.*.product_id' => ['required', 'integer'],
            'items.*.quantity' => ['required', 'integer', 'min:1'],
            'items.*.unit_amount' => ['required', 'numeric', 'min:0'],
            'items.*.license.id' => ['required', 'string', 'max:255'],
            'items.*.license.key' => ['required', 'string', 'max:2048'],
            'items.*.license.status' => ['required', 'in:active,revoked,expired'],
            'items.*.license.activation_limit' => ['required', 'integer', 'min:1'],
            'items.*.license.expires_at' => ['nullable', 'date'],
        ];
    }
}
