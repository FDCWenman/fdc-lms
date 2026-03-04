<?php

namespace App\Http\Requests\Account;

use Illuminate\Foundation\Http\FormRequest;

class DeactivateAccountRequest extends FormRequest
{
    /**
     * Determine if the user is authorized to make this request.
     */
    public function authorize(): bool
    {
        $account = $this->route('account');
        return $this->user() && $this->user()->can('deactivate', $account);
    }

    /**
     * Get the validation rules that apply to the request.
     */
    public function rules(): array
    {
        return [
            'reason' => ['required', 'string', 'min:10', 'max:500'],
        ];
    }

    /**
     * Get custom messages for validator errors.
     */
    public function messages(): array
    {
        return [
            'reason.required' => 'Please provide a reason for deactivating this account.',
            'reason.min' => 'The reason must be at least 10 characters.',
            'reason.max' => 'The reason must not exceed 500 characters.',
        ];
    }
}
