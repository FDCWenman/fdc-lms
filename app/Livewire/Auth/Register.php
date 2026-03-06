<?php

namespace App\Livewire\Auth;

use App\Actions\Auth\RegisterUserAction;
use App\Services\SlackService;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Livewire\Attributes\Layout;
use Livewire\Attributes\Title;
use Livewire\Component;

/**
 * Public User Registration Component
 *
 * Allows anyone to register with default employee role.
 * Creates users with "for_verification" status and sends Slack DM for account activation.
 */
#[Layout('layouts.guest')]
#[Title('Register')]
class Register extends Component
{
    // Form fields
    public string $first_name = '';

    public string $middle_name = '';

    public string $last_name = '';

    public string $email = '';

    public string $password = '';

    public string $password_confirmation = '';

    public string $slack_id = '';

    public string $hired_date = '';

    // State management
    public bool $isValidatingSlackId = false;

    public bool $slackIdValid = false;

    public ?string $slackIdError = null;

    public bool $isRegistering = false;


    /**
     * Real-time Slack ID validation
     */
    public function validateSlackId(): void
    {
        $this->slackIdError = null;
        $this->slackIdValid = false;

        // Validate format first
        if (! preg_match('/^[UW][A-Z0-9]{8,10}$/', $this->slack_id)) {
            $this->slackIdError = 'Slack ID must be in the format U123456789 or W123456789';

            return;
        }

        // Check uniqueness
        if (DB::table('users')->where('slack_id', $this->slack_id)->exists()) {
            $this->slackIdError = 'This Slack ID is already assigned to another user';

            return;
        }

        // Validate with Slack API in production/staging
        if (config('app.env') !== 'local') {
            $this->isValidatingSlackId = true;

            try {
                $slackService = app(SlackService::class);
                $isValid = $slackService->validateSlackId($this->slack_id);

                if (! $isValid) {
                    $this->slackIdError = 'Invalid Slack ID or user not found in workspace';
                } else {
                    $this->slackIdValid = true;
                }
            } catch (\Exception $e) {
                $this->slackIdError = 'Slack API unavailable. Please try again later.';
                Log::error('Slack ID validation failed', [
                    'slack_id' => $this->slack_id,
                    'error' => $e->getMessage(),
                ]);
            } finally {
                $this->isValidatingSlackId = false;
            }
        } else {
            // Local environment: skip Slack validation
            $this->slackIdValid = true;
        }
    }

    /**
     * Handle user registration form submission
     */
    public function register(RegisterUserAction $action): void
    {
        // Validate form data
        $validated = $this->validate([
            'first_name' => ['required', 'string', 'max:255'],
            'middle_name' => ['nullable', 'string', 'max:255'],
            'last_name' => ['required', 'string', 'max:255'],
            'email' => ['required', 'string', 'email:rfc,dns', 'max:255', 'unique:users,email'],
            'password' => ['required', 'string', 'min:8', 'confirmed'],
            'slack_id' => ['required', 'string', 'max:50', 'unique:users,slack_id', 'regex:/^[UW][A-Z0-9]{8,10}$/'],
            'hired_date' => ['required', 'date', 'before_or_equal:today'],
        ], [
            'first_name.required' => 'First name is required.',
            'last_name.required' => 'Last name is required.',
            'email.required' => 'Email address is required.',
            'email.unique' => 'This email address is already registered.',
            'password.required' => 'Password is required.',
            'password.min' => 'Password must be at least 8 characters.',
            'password.confirmed' => 'Password confirmation does not match.',
            'slack_id.required' => 'Slack ID is required.',
            'slack_id.unique' => 'This Slack ID is already assigned to another user.',
            'slack_id.regex' => 'Slack ID must be in the format U123456789 or W123456789.',
            'hired_date.required' => 'Hired date is required.',
            'hired_date.before_or_equal' => 'Hired date cannot be in the future.',
        ]);

        $this->isRegistering = true;

        try {
            DB::beginTransaction();

            // Build full name
            $fullName = trim($validated['first_name'].' '.$validated['middle_name'].' '.$validated['last_name']);

            // Execute registration action with employee role as default
            $user = $action->execute([
                'first_name' => $validated['first_name'],
                'middle_name' => $validated['middle_name'] ?? null,
                'last_name' => $validated['last_name'],
                'name' => $fullName,
                'email' => $validated['email'],
                'password' => $validated['password'],
                'slack_id' => $validated['slack_id'],
                'hired_date' => $validated['hired_date'],
                'roles' => ['employee'], // Default role
            ]);

            DB::commit();

            // Success notification
            session()->flash('success', "Registration successful! Verification link sent via Slack to {$user->email}.");

            // Reset form
            $this->reset();

        } catch (\RuntimeException $e) {
            DB::rollBack();
            $this->isRegistering = false;

            // Handle specific errors
            if (str_contains($e->getMessage(), 'Slack')) {
                $this->addError('slack_id', $e->getMessage());
            } else {
                $this->addError('form', 'Registration failed: '.$e->getMessage());
            }
        } catch (\Exception $e) {
            DB::rollBack();
            $this->isRegistering = false;

            Log::error('User registration failed', [
                'email' => $validated['email'],
                'error' => $e->getMessage(),
            ]);

            $this->addError('form', 'An unexpected error occurred. Please try again.');
        } finally {
            $this->isRegistering = false;
        }
    }

    /**
     * Render the registration component
     */
    public function render()
    {
        return view('livewire.auth.register');
    }
}
