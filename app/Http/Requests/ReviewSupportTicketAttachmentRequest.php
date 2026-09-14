<?php

namespace App\Http\Requests;

use App\Domain\Support\Enums\SupportAttachmentScanStatus;
use App\Models\Staff;
use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class ReviewSupportTicketAttachmentRequest extends FormRequest
{
    public function authorize(): bool
    {
        $staff = $this->user('staff');

        return $staff instanceof Staff && $staff->admin;
    }

    /**
     * @return array<string, ValidationRule|array<mixed>|string>
     */
    public function rules(): array
    {
        return [
            'scan_status' => [
                'required',
                Rule::in([
                    SupportAttachmentScanStatus::Clean->value,
                    SupportAttachmentScanStatus::Rejected->value,
                ]),
            ],
        ];
    }
}
