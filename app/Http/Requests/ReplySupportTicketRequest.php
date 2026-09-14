<?php

namespace App\Http\Requests;

use App\Models\Staff;
use App\Models\User;
use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rules\File;

class ReplySupportTicketRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user() instanceof User || $this->user('staff') instanceof Staff;
    }

    /**
     * @return array<string, ValidationRule|array<mixed>|string>
     */
    public function rules(): array
    {
        return [
            'body' => ['required', 'string', 'max:10000'],
            'is_internal' => ['sometimes', 'boolean'],
            'attachments' => ['nullable', 'array', 'max:3'],
            'attachments.*' => [
                'file',
                File::types(['pdf', 'png', 'jpg', 'jpeg', 'txt'])->max('10mb'),
                'extensions:pdf,png,jpg,jpeg,txt',
            ],
        ];
    }
}
