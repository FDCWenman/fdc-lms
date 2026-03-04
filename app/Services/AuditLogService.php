<?php

namespace App\Services;

use App\Models\AccountAuditLog;
use App\Models\User;
use Illuminate\Support\Facades\Request;

class AuditLogService
{
    /**
     * Log an account action to the audit trail.
     *
     * @param User $user The user account being acted upon
     * @param string $action The action being performed (use AccountAuditLog constants)
     * @param User|null $performedBy The user performing the action (null for system actions)
     * @param string|null $reason Optional reason for the action
     * @param array|null $metadata Additional context data
     */
    public function log(
        User $user,
        string $action,
        ?User $performedBy = null,
        ?string $reason = null,
        ?array $metadata = null
    ): AccountAuditLog {
        return AccountAuditLog::create([
            'user_id' => $user->id,
            'performed_by' => $performedBy?->id,
            'action' => $action,
            'reason' => $reason,
            'metadata' => $metadata,
            'ip_address' => Request::ip(),
        ]);
    }

    /**
     * Log account creation.
     */
    public function logAccountCreated(User $user, User $createdBy, ?string $reason = null): AccountAuditLog
    {
        return $this->log(
            $user,
            AccountAuditLog::ACTION_ACCOUNT_CREATED,
            $createdBy,
            $reason,
            ['email' => $user->email, 'slack_id' => $user->slack_id]
        );
    }

    /**
     * Log account activation.
     */
    public function logAccountActivated(User $user, User $activatedBy, string $reason): AccountAuditLog
    {
        return $this->log(
            $user,
            AccountAuditLog::ACTION_ACCOUNT_ACTIVATED,
            $activatedBy,
            $reason,
            ['previous_status' => $user->getOriginal('status'), 'new_status' => User::STATUS_ACTIVE]
        );
    }

    /**
     * Log account deactivation.
     */
    public function logAccountDeactivated(User $user, User $deactivatedBy, string $reason): AccountAuditLog
    {
        return $this->log(
            $user,
            AccountAuditLog::ACTION_ACCOUNT_DEACTIVATED,
            $deactivatedBy,
            $reason,
            ['previous_status' => $user->getOriginal('status'), 'new_status' => User::STATUS_DEACTIVATED]
        );
    }

    /**
     * Log email verification.
     */
    public function logEmailVerified(User $user): AccountAuditLog
    {
        return $this->log(
            $user,
            AccountAuditLog::ACTION_EMAIL_VERIFIED,
            $user,
            'Email verified by user'
        );
    }

    /**
     * Log password reset.
     */
    public function logPasswordReset(User $user): AccountAuditLog
    {
        return $this->log(
            $user,
            AccountAuditLog::ACTION_PASSWORD_RESET,
            $user,
            'Password reset via Slack link'
        );
    }

    /**
     * Log password change.
     */
    public function logPasswordChanged(User $user): AccountAuditLog
    {
        return $this->log(
            $user,
            AccountAuditLog::ACTION_PASSWORD_CHANGED,
            $user,
            'Password changed by user'
        );
    }

    /**
     * Log role change.
     */
    public function logRoleChanged(
        User $user,
        User $changedBy,
        ?int $oldPrimaryRole,
        ?int $newPrimaryRole,
        ?int $oldSecondaryRole,
        ?int $newSecondaryRole
    ): AccountAuditLog {
        return $this->log(
            $user,
            AccountAuditLog::ACTION_ROLE_CHANGED,
            $changedBy,
            'User roles updated',
            [
                'old_primary_role_id' => $oldPrimaryRole,
                'new_primary_role_id' => $newPrimaryRole,
                'old_secondary_role_id' => $oldSecondaryRole,
                'new_secondary_role_id' => $newSecondaryRole,
            ]
        );
    }

    /**
     * Log default approvers update.
     */
    public function logApproversUpdated(
        User $user,
        ?array $oldApprovers,
        ?array $newApprovers
    ): AccountAuditLog {
        return $this->log(
            $user,
            AccountAuditLog::ACTION_APPROVERS_UPDATED,
            $user,
            'Default approvers updated',
            [
                'old_approvers' => $oldApprovers,
                'new_approvers' => $newApprovers,
            ]
        );
    }

    /**
     * Log successful login.
     */
    public function logLoginSuccess(User $user): AccountAuditLog
    {
        return $this->log(
            $user,
            AccountAuditLog::ACTION_LOGIN_SUCCESS,
            $user,
            null,
            ['user_agent' => Request::userAgent()]
        );
    }

    /**
     * Log failed login attempt.
     */
    public function logLoginFailed(string $email, string $reason): void
    {
        // Since we don't have a user object for failed logins, we'll just log this
        // The FailedLoginAttempt model handles the actual tracking
        // This is just for additional audit trail if needed
    }

    /**
     * Log account lockout.
     */
    public function logAccountLocked(User $user, int $lockoutMinutes): AccountAuditLog
    {
        return $this->log(
            $user,
            AccountAuditLog::ACTION_ACCOUNT_LOCKED,
            null,
            "Account locked for {$lockoutMinutes} minutes due to failed login attempts",
            ['lockout_duration_minutes' => $lockoutMinutes]
        );
    }
}
