<?php

namespace App\Actions\Profile;

use App\Models\User;
use App\Services\AuditLogService;
use Illuminate\Validation\ValidationException;
use Spatie\Permission\Models\Role;

class UpdateDefaultApprovers
{
    public function __construct(
        protected AuditLogService $auditLog
    ) {}

    /**
     * Update a user's default approvers.
     *
     * @throws ValidationException
     */
    public function execute(
        User $user,
        ?int $hrApproverId = null,
        ?int $leadApproverId = null,
        ?int $pmApproverId = null
    ): User {
        $approvers = [];
        $errors = [];

        // Validate HR approver
        if ($hrApproverId) {
            $hrApprover = User::find($hrApproverId);
            if (!$hrApprover || !$hrApprover->hasAnyRole('HR Approver')) {
                $errors['hr_approver_id'] = ['The selected HR approver must have the HR Approver role.'];
            } else {
                $approvers['hr_approver_id'] = $hrApproverId;
            }
        }

        // Validate Lead approver
        if ($leadApproverId) {
            $leadApprover = User::find($leadApproverId);
            if (!$leadApprover || !$leadApprover->hasAnyRole('Lead Approver')) {
                $errors['lead_approver_id'] = ['The selected Lead approver must have the Lead Approver role.'];
            } else {
                $approvers['lead_approver_id'] = $leadApproverId;
            }
        }

        // Validate PM approver
        if ($pmApproverId) {
            $pmApprover = User::find($pmApproverId);
            if (!$pmApprover || !$pmApprover->hasAnyRole('PM Approver')) {
                $errors['pm_approver_id'] = ['The selected PM approver must have the PM Approver role.'];
            } else {
                $approvers['pm_approver_id'] = $pmApproverId;
            }
        }

        if (!empty($errors)) {
            throw ValidationException::withMessages($errors);
        }

        // Store old approvers for audit log
        $oldApprovers = $user->default_approvers;

        // Update approvers
        $user->update([
            'default_approvers' => empty($approvers) ? null : $approvers,
        ]);

        // Log the change
        $this->auditLog->logApproversUpdated($user, $oldApprovers, $approvers);

        return $user;
    }
}
