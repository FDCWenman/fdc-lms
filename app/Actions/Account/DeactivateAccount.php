<?php

namespace App\Actions\Account;

use App\Models\User;
use App\Services\AuditLogService;
use Illuminate\Validation\ValidationException;

class DeactivateAccount
{
    public function __construct(
        protected AuditLogService $auditLog
    ) {}

    /**
     * Deactivate a user account.
     *
     * @throws ValidationException
     */
    public function execute(User $user, User $deactivatedBy, string $reason): User
    {
        // Prevent self-deactivation
        if ($user->id === $deactivatedBy->id) {
            throw ValidationException::withMessages([
                'user_id' => ['You cannot deactivate your own account.'],
            ]);
        }

        // Check if already deactivated
        if ($user->isDeactivated()) {
            throw ValidationException::withMessages([
                'status' => ['Account is already deactivated.'],
            ]);
        }

        // Update status
        $user->update([
            'status' => User::STATUS_DEACTIVATED,
        ]);

        // Invalidate all user sessions/tokens
        $user->tokens()->delete();

        // Log the deactivation
        $this->auditLog->logAccountDeactivated($user, $deactivatedBy, $reason);

        return $user;
    }
}
