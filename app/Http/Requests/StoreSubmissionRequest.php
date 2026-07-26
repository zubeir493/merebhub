<?php

namespace App\Http\Requests;

use App\Enums\BillingInterval;
use App\Enums\BillingModel;
use App\Enums\FulfillmentType;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class StoreSubmissionRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'submitter_name' => ['required', 'string', 'max:255'],
            'submitter_email' => ['required', 'email', 'max:255'],
            'app_name' => ['required', 'string', 'max:255'],
            'description' => ['required', 'string', 'min:50', 'max:10000'],
            'suggested_price' => ['nullable', 'numeric', 'min:0', 'max:999999.99'],
            'platform' => ['required', 'string', 'max:255'],
            'category' => ['nullable', 'string', 'max:255'],
            'demo_url' => ['nullable', 'url:http,https', 'max:2048'],
            'fulfillment_type' => ['required', Rule::enum(FulfillmentType::class)],
            'payment_model' => ['required', Rule::enum(BillingModel::class)],
            'billing_interval' => [
                Rule::requiredIf($this->input('payment_model') === BillingModel::ManualSubscription->value),
                'nullable',
                Rule::enum(BillingInterval::class),
            ],
            'trial_days' => ['nullable', 'integer', 'min:1', 'max:365'],
            'attachments' => ['nullable', 'array', 'max:8'],
            'attachments.*' => [
                'file',
                'max:20480',
                'mimes:jpg,jpeg,png,webp,pdf,zip',
                'extensions:jpg,jpeg,png,webp,pdf,zip',
            ],
        ];
    }
}
