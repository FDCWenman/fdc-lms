<?php

namespace App\Actions\Profile;

use App\Models\User;
use App\Services\AuditLogService;
use Illuminate\Support\Facades\Hash;
use Illuminate\Validation\Rules\Password;
use Illuminate\Validation\ValidationException;

class ChangePassword
{
    public function __construct(
        protected AuditLogService $auditLog
    ) {}

    /**
     * Change a user's password.
     *
     * @throws ValidationException
     */
    public function execute(
        User $user,
        string $currentPassword,
        string $newPassword,
        string $newPasswordConfirmation
    ): User {
        // Verify current password
        if (!Hash::check($currentPassword, $user->password)) {
            throw ValidationException::withMessages([
                'current_password' => ['The current password is incorrect.'],
            ]);
        }

        // Validate new password confirmation
        if ($newPassword !== $newPasswordConfirmation) {
            throw ValidationException::withMessages([
                'new_password' => ['The password confirmation does not match.'],
            ]);
        }

        // Validate new password strength
        $passwordRule = Password::min(8)
            ->letters()
            ->mixedCase()
            ->numbers()
            ->symbols()
            ->uncompromised();

        try {
            validator(['new_password' => $newPassword], [
                'new_password' => ['required', $passwordRule],
            ])->validate();
        } catch (ValidationException $e) {
            throw $e;
        }

        // Update password
        $user->update([
            'password' => Hash::make($newPassword),
        ]);

        // Invalidate all other user sessions (keep current one)
        // Delete all tokens except the current one
        $currentToken = $user->currentAccessToken();
        $user->tokens()->where('id', '!=', $currentToken?->id)->delete();

        // Log the password change
        $this->auditLog->logPasswordChanged($user);

        return $user;
    }
}
