<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class LookupOrderRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'buyer_email' => ['required', 'email', 'max:255'],
            'wc_order_id' => ['required', 'integer', 'min:1'],
        ];
    }
}
