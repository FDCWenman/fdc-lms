<?php

namespace App\Livewire\Auth;

use App\Actions\Auth\RegisterUserAction;
use App\Http\Requests\Auth\RegisterUserRequest;
use App\Models\User;
use App\Services\SlackService;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Livewire\Attributes\Layout;
use Livewire\Attributes\Title;
use Livewire\Component;

/**
 * User Registration Component (HR Admin Only)
 *
 * Allows HR administrators to register new users with real-time Slack ID validation.
 * Creates users with "for_verification" status and sends Slack DM for account activation.
 *
 * Requirements: FR-010, FR-011, FR-012, FR-013, FR-014, FR-015, FR-016, FR-017, FR-018, FR-035, FR-038
 */
#[Layout('layouts.app')]
#[Title('Register New Employee')]
class Register extends Component
{
    // Form fields
    public string $name = '';

    public string $email = '';

    public string $password = '';

    public string $password_confirmation = '';

    public string $slack_id = '';

    public array $roles = [];

    public ?int $hr_approver_id = null;

    public ?int $tl_approver_id = null;

    public ?int $pm_approver_id = null;

    // State management
    public bool $isValidatingSlackId = false;

    public bool $slackIdValid = false;

    public ?string $slackIdError = null;

    public bool $isRegistering = false;

    /**
     * Component initialization
     */
    public function mount(): void
    {
        // Ensure only HR admins can access (FR-010)
        if (! auth()->user()->hasRole('hr')) {
            abort(403, 'Unauthorized access to registration');
        }
    }

    /**
     * Real-time Slack ID validation (FR-011)
     *
     * Validates Slack ID format and checks against Slack API in production/staging.
     * Bypasses Slack API in local environment.
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

        // Check uniqueness (FR-038)
        if (User::where('slack_id', $this->slack_id)->exists()) {
            $this->slackIdError = 'This Slack ID is already assigned to another user';

            return;
        }

        // Validate with Slack API in production/staging (FR-011)
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
            'name' => ['required', 'string', 'max:255'],
            'email' => ['required', 'string', 'email:rfc,dns', 'max:255', 'unique:users_fdc_leaves,email'],
            'password' => ['required', 'string', 'min:8', 'confirmed'],
            'password_confirmation' => ['required', 'string'],
            'slack_id' => ['required', 'string', 'max:50', 'unique:users_fdc_leaves,slack_id', 'regex:/^[UW][A-Z0-9]{8,10}$/'],
            'roles' => ['required', 'array', 'min:1'],
            'roles.*' => ['required', 'string', 'exists:roles,name'],
            'hr_approver_id' => ['nullable', 'integer', 'exists:users_fdc_leaves,id'],
            'tl_approver_id' => ['nullable', 'integer', 'exists:users_fdc_leaves,id'],
            'pm_approver_id' => ['nullable', 'integer', 'exists:users_fdc_leaves,id'],
        ], [
            'name.required' => 'Employee name is required.',
            'email.required' => 'Email address is required.',
            'email.unique' => 'This email address is already registered.',
            'password.required' => 'Password is required.',
            'password.min' => 'Password must be at least 8 characters.',
            'password.confirmed' => 'Password confirmation does not match.',
            'slack_id.required' => 'Slack ID is required.',
            'slack_id.unique' => 'This Slack ID is already assigned to another user.',
            'slack_id.regex' => 'Slack ID must be in the format U123456789 or W123456789.',
            'roles.required' => 'At least one role must be selected.',
            'roles.*.exists' => 'Selected role does not exist.',
        ]);

        $this->isRegistering = true;

        try {
            DB::beginTransaction();

            // Execute registration action (FR-012, FR-014, FR-015, FR-016, FR-017, FR-018)
            $user = $action->execute([
                'name' => $validated['name'],
                'email' => $validated['email'],
                'password' => $validated['password'],
                'slack_id' => $validated['slack_id'],
                'roles' => $validated['roles'],
                'default_approvers' => [
                    'hr_id' => $validated['hr_approver_id'] ?? null,
                    'tl_id' => $validated['tl_approver_id'] ?? null,
                    'pm_id' => $validated['pm_approver_id'] ?? null,
                ],
            ]);

            DB::commit();

            // Success notification
            session()->flash('success', "User {$user->name} registered successfully. Verification link sent via Slack.");

            // Reset form
            $this->reset();

        } catch (\RuntimeException $e) {
            DB::rollBack();
            $this->isRegistering = false;

            // Handle specific errors (FR-035)
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
     * Get list of available approvers for selection
     */
    public function getApproversProperty(): array
    {
        return User::query()
            ->where('status', 1) // Active users only
            ->whereNotNull('verified_at')
            ->whereHas('roles', function ($query) {
                $query->whereIn('name', ['hr', 'team-lead', 'project-manager']);
            })
            ->orderBy('name')
            ->get(['id', 'name', 'email'])
            ->toArray();
    }

    /**
     * Get list of available roles
     */
    public function getAvailableRolesProperty(): array
    {
        return DB::table('roles')
            ->where('guard_name', 'web')
            ->orderBy('name')
            ->pluck('name')
            ->toArray();
    }

    /**
     * Render the registration component
     */
    public function render()
    {
        return view('livewire.auth.register', [
            'approvers' => $this->approvers,
            'availableRoles' => $this->availableRoles,
        ]);
    }
}
