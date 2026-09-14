<?php

namespace App\Http\Requests;

use App\Domain\Support\Enums\SupportTicketPriority;
use App\Domain\Support\Enums\SupportTicketStatus;
use App\Models\Staff;
use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Database\Query\Builder;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class UpdateSupportTicketRequest extends FormRequest
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
        $staffTable = (new Staff)->getTable();

        return [
            'status' => ['required', Rule::enum(SupportTicketStatus::class)],
            'priority' => ['required', Rule::enum(SupportTicketPriority::class)],
            'assigned_staff_id' => [
                'nullable',
                'integer',
                Rule::exists($staffTable, 'id')->where(
                    fn (Builder $query): Builder => $query->where('admin', true)->whereNull('deleted_at'),
                ),
            ],
        ];
    }
}
