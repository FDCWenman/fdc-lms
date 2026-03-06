<?php

namespace App\Livewire\Auth;

use App\Models\User;
use App\Models\VerificationToken;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Log;
use Livewire\Attributes\Layout;
use Livewire\Attributes\Title;
use Livewire\Component;

/**
 * Reset Password Component
 *
 * Allows users to reset their password using a valid token.
 */
#[Layout('layouts.guest')]
#[Title('Reset Password')]
class ResetPassword extends Component
{
    public string $token = '';
    public string $password = '';
    public string $password_confirmation = '';
    public bool $isResetting = false;
    public bool $tokenValid = true;
    public ?User $user = null;

    /**
     * Mount component and validate token
     */
    public function mount(string $token): void
    {
        $this->token = $token;

        // Validate token
        $verificationToken = VerificationToken::where('token', $token)
            ->where('expires_at', '>', now())
            ->first();

        if (!$verificationToken) {
            $this->tokenValid = false;
            return;
        }

        // Load user
        $this->user = User::find($verificationToken->user_id);

        if (!$this->user) {
            $this->tokenValid = false;
        }
    }

    /**
     * Reset user password
     */
    public function resetPassword(): void
    {
        if (!$this->tokenValid || !$this->user) {
            return;
        }

        $this->validate([
            'password' => ['required', 'string', 'min:8', 'confirmed'],
        ], [
            'password.required' => 'Password is required.',
            'password.min' => 'Password must be at least 8 characters.',
            'password.confirmed' => 'Password confirmation does not match.',
        ]);

        $this->isResetting = true;

        try {
            // Update password
            $this->user->update([
                'password' => Hash::make($this->password),
            ]);

            // Delete used token
            VerificationToken::where('token', $this->token)->delete();

            // Log password reset
            Log::info('User password reset successfully', [
                'user_id' => $this->user->id,
                'email' => $this->user->email,
            ]);

            // Success message and redirect to login
            session()->flash('success', 'Password reset successfully! You can now log in with your new password.');

            $this->redirect(route('login'), navigate: true);

        } catch (\Exception $e) {
            Log::error('Password reset failed', [
                'user_id' => $this->user->id,
                'error' => $e->getMessage(),
            ]);

            $this->addError('password', 'Failed to reset password. Please try again.');
        } finally {
            $this->isResetting = false;
        }
    }

    /**
     * Render the component
     */
    public function render()
    {
        return view('livewire.auth.reset-password');
    }
}
