<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class StoreProductReviewRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user() !== null;
    }

    /**
     * Get the validation rules that apply to the request.
     *
     * @return array<string, array<int, mixed>>
     */
    public function rules(): array
    {
        return [
            'rating' => ['required', 'integer', Rule::in([1, 2, 3, 4, 5])],
            'title' => ['nullable', 'string', 'max:120'],
            'body' => ['required', 'string', 'min:20', 'max:2000'],
        ];
    }
}
