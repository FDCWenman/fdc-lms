<?php

namespace App\Http\Requests\Account;

use Illuminate\Foundation\Http\FormRequest;

class CreateAccountRequest extends FormRequest
{
    /**
     * Determine if the user is authorized to make this request.
     */
    public function authorize(): bool
    {
        return $this->user() && $this->user()->can('create', \App\Models\User::class);
    }

    /**
     * Get the validation rules that apply to the request.
     */
    public function rules(): array
    {
        return [
            'name' => ['required', 'string', 'max:255'],
            'email' => ['required', 'email', 'unique:users,email'],
            'slack_id' => ['required', 'string', 'unique:users,slack_id'],
            'primary_role_id' => ['required', 'exists:roles,id'],
            'secondary_role_id' => ['nullable', 'exists:roles,id'],
            'hired_date' => ['nullable', 'date'],
        ];
    }

    /**
     * Get custom messages for validator errors.
     */
    public function messages(): array
    {
        return [
            'name.required' => 'Employee name is required.',
            'email.required' => 'Email address is required.',
            'email.email' => 'Please enter a valid email address.',
            'email.unique' => 'An account with this email already exists.',
            'slack_id.required' => 'Slack ID is required.',
            'slack_id.unique' => 'An account with this Slack ID already exists.',
            'primary_role_id.required' => 'Please select a primary role.',
            'primary_role_id.exists' => 'The selected role is invalid.',
        ];
    }
}
