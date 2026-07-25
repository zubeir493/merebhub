<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

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
            'build' => ['required', 'file', 'max:1048576', 'mimes:zip,rar,7z,apk,aab,exe,msi,dmg,deb,rpm,tar,gz'],
        ];
    }
}
