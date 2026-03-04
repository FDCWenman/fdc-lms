<?php

namespace App\Http\Controllers;

use App\Actions\Profile\ChangePassword;
use App\Actions\Profile\RefreshSlackName;
use App\Actions\Profile\UpdateDefaultApprovers;
use App\Http\Requests\Profile\ChangePasswordRequest;
use App\Http\Requests\Profile\UpdateApproversRequest;
use App\Models\User;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;

class ProfileController extends Controller
{
    /**
     * Show the profile edit form.
     */
    public function edit(Request $request): Response
    {
        $user = $request->user();
        $user->load(['primaryRole', 'secondaryRole']);

        // Get available approvers by role
        $hrApprovers = User::whereHas('roles', function ($query) {
            $query->where('name', 'HR Approver');
        })->where('status', User::STATUS_ACTIVE)->get(['id', 'name', 'email']);

        $leadApprovers = User::whereHas('roles', function ($query) {
            $query->where('name', 'Lead Approver');
        })->where('status', User::STATUS_ACTIVE)->get(['id', 'name', 'email']);

        $pmApprovers = User::whereHas('roles', function ($query) {
            $query->where('name', 'PM Approver');
        })->where('status', User::STATUS_ACTIVE)->get(['id', 'name', 'email']);

        return Inertia::render('Profile/Edit', [
            'user' => $user,
            'hrApprovers' => $hrApprovers,
            'leadApprovers' => $leadApprovers,
            'pmApprovers' => $pmApprovers,
        ]);
    }

    /**
     * Update default approvers.
     */
    public function updateApprovers(
        UpdateApproversRequest $request,
        UpdateDefaultApprovers $updateApprovers
    ): RedirectResponse {
        $updateApprovers->execute(
            $request->user(),
            $request->input('hr_approver_id'),
            $request->input('lead_approver_id'),
            $request->input('pm_approver_id')
        );

        return back()->with('success', 'Default approvers updated successfully.');
    }

    /**
     * Change password.
     */
    public function changePassword(
        ChangePasswordRequest $request,
        ChangePassword $changePassword
    ): RedirectResponse {
        $changePassword->execute(
            $request->user(),
            $request->input('current_password'),
            $request->input('new_password'),
            $request->input('new_password_confirmation')
        );

        return back()->with('success', 'Password changed successfully. Other sessions have been logged out.');
    }

    /**
     * Refresh Slack display name.
     */
    public function refreshSlackName(
        Request $request,
        RefreshSlackName $refreshSlackName
    ): RedirectResponse {
        $result = $refreshSlackName->execute($request->user());

        if ($result['success']) {
            return back()->with('success', $result['message']);
        }

        return back()->withErrors(['slack' => $result['message']]);
    }
}
