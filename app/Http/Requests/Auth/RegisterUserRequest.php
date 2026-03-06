<?php

namespace App\Http\Requests\Auth;

use App\Models\User;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rules\Password;

/**
 * Registration form validation request
 *
 * Validates all registration fields including Slack ID uniqueness,
 * email uniqueness, password requirements, and role assignment.
 *
 * Requirements: FR-012, FR-013, FR-038
 */
class RegisterUserRequest extends FormRequest
{
    /**
     * Determine if the user is authorized to make this request.
     */
    public function authorize(): bool
    {
        // Only HR admins can register users (FR-010)
        return $this->user()?->hasRole('hr') ?? false;
    }

    /**
     * Get the validation rules that apply to the request.
     *
     * @return array<string, \Illuminate\Contracts\Validation\ValidationRule|array<mixed>|string>
     */
    public function rules(): array
    {
        return [
            'name' => ['required', 'string', 'max:255'],
            'email' => [
                'required',
                'string',
                'email:rfc,dns',
                'max:255',
                'unique:users_fdc_leaves,email', // FR-013
            ],
            'password' => [
                'required',
                'string',
                Password::min(8)
                    ->letters()
                    ->mixedCase()
                    ->numbers()
                    ->symbols(),
                'confirmed',
            ],
            'password_confirmation' => ['required', 'string'],
            'slack_id' => [
                'required',
                'string',
                'max:50',
                'unique:users_fdc_leaves,slack_id', // FR-038
                'regex:/^[UW][A-Z0-9]{8,10}$/', // Slack ID format
            ],
            'roles' => ['required', 'array', 'min:1'],
            'roles.*' => ['required', 'string', 'exists:roles,name'],
            'default_approvers' => ['nullable', 'array'],
            'default_approvers.hr_id' => ['nullable', 'integer', 'exists:users_fdc_leaves,id'],
            'default_approvers.tl_id' => ['nullable', 'integer', 'exists:users_fdc_leaves,id'],
            'default_approvers.pm_id' => ['nullable', 'integer', 'exists:users_fdc_leaves,id'],
        ];
    }

    /**
     * Get custom error messages for validation rules.
     *
     * @return array<string, string>
     */
    public function messages(): array
    {
        return [
            'name.required' => 'Employee name is required.',
            'name.max' => 'Employee name must not exceed 255 characters.',

            'email.required' => 'Email address is required.',
            'email.email' => 'Please provide a valid email address.',
            'email.unique' => 'This email address is already registered in the system.',

            'password.required' => 'Password is required.',
            'password_confirmation.required' => 'Password confirmation is required.',

            'slack_id.required' => 'Slack ID is required.',
            'slack_id.unique' => 'This Slack ID is already assigned to another user.',
            'slack_id.regex' => 'Slack ID must be in the format U123456789 or W123456789.',

            'roles.required' => 'At least one role must be selected.',
            'roles.*.exists' => 'Selected role does not exist in the system.',

            'default_approvers.hr_id.exists' => 'Selected HR approver does not exist.',
            'default_approvers.tl_id.exists' => 'Selected Team Lead approver does not exist.',
            'default_approvers.pm_id.exists' => 'Selected Project Manager approver does not exist.',
        ];
    }

    /**
     * Get custom attribute names for error messages.
     *
     * @return array<string, string>
     */
    public function attributes(): array
    {
        return [
            'name' => 'employee name',
            'slack_id' => 'Slack ID',
            'default_approvers.hr_id' => 'HR approver',
            'default_approvers.tl_id' => 'Team Lead approver',
            'default_approvers.pm_id' => 'Project Manager approver',
        ];
    }
}
