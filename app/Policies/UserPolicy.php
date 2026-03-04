<?php

namespace App\Policies;

use App\Models\User;

class UserPolicy
{
    /**
     * Determine if the user can view any accounts.
     * Only HR Approvers can view all accounts.
     */
    public function viewAny(User $user): bool
    {
        return $user->hasAnyRole('HR Approver');
    }

    /**
     * Determine if the user can view a specific account.
     * HR Approvers can view any account, users can view their own.
     */
    public function view(User $user, User $account): bool
    {
        return $user->hasAnyRole('HR Approver') || $user->id === $account->id;
    }

    /**
     * Determine if the user can create accounts.
     * Only HR Approvers can create accounts.
     */
    public function create(User $user): bool
    {
        return $user->hasAnyRole('HR Approver');
    }

    /**
     * Determine if the user can update an account.
     * HR Approvers can update any account, users can update their own profile.
     */
    public function update(User $user, User $account): bool
    {
        return $user->hasAnyRole('HR Approver') || $user->id === $account->id;
    }

    /**
     * Determine if the user can deactivate an account.
     * Only HR Approvers can deactivate accounts, but not their own.
     */
    public function deactivate(User $user, User $account): bool
    {
        // Must be HR and cannot deactivate self
        return $user->hasAnyRole('HR Approver') && $user->id !== $account->id;
    }

    /**
     * Determine if the user can activate an account.
     * Only HR Approvers can activate accounts.
     */
    public function activate(User $user): bool
    {
        return $user->hasAnyRole('HR Approver');
    }

    /**
     * Determine if the user can delete an account.
     * Accounts cannot be deleted, only deactivated.
     */
    public function delete(User $user, User $account): bool
    {
        return false;
    }
}
