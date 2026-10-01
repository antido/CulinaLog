<?php

namespace App\Http\Requests;

use App\Enums\ReportReason;
use App\Models\Report;
use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class StoreReportRequest extends FormRequest
{
    /**
     * Determine if the user is authorized to make this request.
     * Guests may report too; members need the reports.create permission.
     */
    public function authorize(): bool
    {
        $user = $this->user();

        return $user !== null
            ? $user->can('reports.create')
            : (bool) config('community.guest_reports');
    }

    /**
     * Get the validation rules that apply to the request.
     *
     * @return array<string, ValidationRule|array<mixed>|string>
     */
    public function rules(): array
    {
        return [
            // Only the short names listed in Report::REPORTABLE_TYPES are accepted.
            'type' => ['required', 'string', Rule::in(array_keys(Report::REPORTABLE_TYPES))],
            'id' => ['required', 'integer', 'min:1'],
            'reason' => ['required', Rule::enum(ReportReason::class)],
            'description' => [
                Rule::requiredIf($this->input('reason') === ReportReason::Other->value),
                'nullable',
                'string',
                'max:1000',
            ],
        ];
    }

    /**
     * Get custom messages for validator errors.
     *
     * @return array<string, string>
     */
    public function messages(): array
    {
        return [
            'description.required' => 'Tell us what is wrong when you choose "Other".',
        ];
    }
}
