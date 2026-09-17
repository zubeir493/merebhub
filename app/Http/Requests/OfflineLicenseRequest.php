<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class OfflineLicenseRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user() !== null;
    }

    public function rules(): array
    {
        return [
            'offline_request' => [
                'required',
                'file',
                'max:200',
                'extensions:lreq',
                'mimetypes:text/plain,application/octet-stream,application/x-lreq',
            ],
        ];
    }
}
