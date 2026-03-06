<?php

namespace App\Livewire\Auth;

use App\Actions\Auth\VerifyAccountAction;
use Livewire\Attributes\Layout;
use Livewire\Attributes\Title;
use Livewire\Component;

/**
 * Request New Verification Link Component
 *
 * Allows unverified users to request a new verification link via Slack.
 */
#[Layout('layouts.guest')]
#[Title('Request New Verification')]
class RequestNewVerification extends Component
{
    public string $email = '';

    public bool $isSubmitting = false;

    /**
     * Request a new verification link
     */
    public function requestVerification(VerifyAccountAction $action): void
    {
        $this->validate([
            'email' => ['required', 'email', 'exists:users_fdc_leaves,email'],
        ], [
            'email.required' => 'Email address is required.',
            'email.email' => 'Please provide a valid email address.',
            'email.exists' => 'No account found with this email address.',
        ]);

        $this->isSubmitting = true;

        try {
            $result = $action->requestNewVerification($this->email);

            if ($result['success']) {
                session()->flash('success', $result['message']);
                $this->reset('email');
            } else {
                $this->addError('email', $result['message']);
            }
        } catch (\Exception $e) {
            \Log::error('Request new verification failed', [
                'email' => $this->email,
                'error' => $e->getMessage(),
            ]);

            $this->addError('form', 'An unexpected error occurred. Please try again.');
        } finally {
            $this->isSubmitting = false;
        }
    }

    /**
     * Render the component
     */
    public function render()
    {
        return view('livewire.auth.request-new-verification');
    }
}
