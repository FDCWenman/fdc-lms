<?php

namespace App\Actions\Account;

use App\Models\User;
use App\Services\AuditLogService;
use Illuminate\Validation\ValidationException;

class ActivateAccount
{
    public function __construct(
        protected AuditLogService $auditLog
    ) {}

    /**
     * Activate a user account.
     *
     * @throws ValidationException
     */
    public function execute(User $user, User $activatedBy, string $reason): User
    {
        // Check if already active
        if ($user->isActive()) {
            throw ValidationException::withMessages([
                'status' => ['Account is already active.'],
            ]);
        }

        // Update status
        $user->update([
            'status' => User::STATUS_ACTIVE,
        ]);

        // Log the activation
        $this->auditLog->logAccountActivated($user, $activatedBy, $reason);

        return $user;
    }
}
