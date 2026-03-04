<?php

namespace App\Http\Requests\Profile;

use Illuminate\Foundation\Http\FormRequest;

class UpdateApproversRequest extends FormRequest
{
    /**
     * Determine if the user is authorized to make this request.
     */
    public function authorize(): bool
    {
        return true;
    }

    /**
     * Get the validation rules that apply to the request.
     */
    public function rules(): array
    {
        return [
            'hr_approver_id' => ['nullable', 'exists:users,id'],
            'lead_approver_id' => ['nullable', 'exists:users,id'],
            'pm_approver_id' => ['nullable', 'exists:users,id'],
        ];
    }

    /**
     * Get custom messages for validator errors.
     */
    public function messages(): array
    {
        return [
            'hr_approver_id.exists' => 'The selected HR approver is invalid.',
            'lead_approver_id.exists' => 'The selected Team Lead approver is invalid.',
            'pm_approver_id.exists' => 'The selected Project Manager approver is invalid.',
        ];
    }
}
