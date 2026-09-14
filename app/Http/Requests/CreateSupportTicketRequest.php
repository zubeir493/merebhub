<?php

namespace App\Http\Requests;

use App\Models\User;
use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rules\File;

class CreateSupportTicketRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user() instanceof User;
    }

    /**
     * @return array<string, ValidationRule|array<mixed>|string>
     */
    public function rules(): array
    {
        return [
            'subject' => ['required', 'string', 'max:160'],
            'body' => ['required', 'string', 'max:10000'],
            'attachments' => ['nullable', 'array', 'max:3'],
            'attachments.*' => [
                'file',
                File::types(['pdf', 'png', 'jpg', 'jpeg', 'txt'])->max('10mb'),
                'extensions:pdf,png,jpg,jpeg,txt',
            ],
        ];
    }
}
