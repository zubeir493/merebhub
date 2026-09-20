<?php

namespace App\Http\Requests;

use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class StoreDeveloperApplicationRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    /**
     * Get the validation rules that apply to the request.
     *
     * @return array<string, ValidationRule|array<mixed>|string>
     */
    public function rules(): array
    {
        return [
            'name' => ['required', 'string', 'max:120'],
            'email' => ['required', 'email:rfc', 'max:255'],
            'studio' => ['nullable', 'string', 'max:160'],
            'website_url' => ['nullable', 'url:http,https', 'max:2048'],
            'product_name' => ['required', 'string', 'max:160'],
            'product_url' => ['nullable', 'url:http,https', 'max:2048'],
            'product_stage' => ['required', Rule::in(['live', 'beta', 'in_development'])],
            'summary' => ['required', 'string', 'max:1500'],
            'audience' => ['nullable', 'string', 'max:500'],
        ];
    }

    /**
     * @return array<string, string>
     */
    public function messages(): array
    {
        return [
            'product_stage.required' => 'Choose the stage that best describes your product.',
            'product_stage.in' => 'Choose a valid product stage.',
            'summary.required' => 'Tell us what your product does.',
        ];
    }
}
