<?php

namespace App\Http\Controllers\Admin;

use App\Actions\Account\ActivateAccount;
use App\Actions\Account\CreateEmployeeAccount;
use App\Actions\Account\DeactivateAccount;
use App\Http\Controllers\Controller;
use App\Http\Requests\Account\CreateAccountRequest;
use App\Http\Requests\Account\DeactivateAccountRequest;
use App\Models\User;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;
use Spatie\Permission\Models\Role;

class AccountController extends Controller
{
    /**
     * Display a listing of employee accounts.
     */
    public function index(Request $request): Response
    {
        $this->authorize('viewAny', User::class);

        $query = User::with(['primaryRole', 'secondaryRole'])
            ->orderBy('created_at', 'desc');

        // Filter by status
        if ($request->has('status')) {
            $query->where('status', $request->input('status'));
        }

        // Search by name or email
        if ($request->has('search')) {
            $search = $request->input('search');
            $query->where(function ($q) use ($search) {
                $q->where('name', 'like', "%{$search}%")
                  ->orWhere('email', 'like', "%{$search}%");
            });
        }

        $accounts = $query->paginate(20)->withQueryString();

        return Inertia::render('Admin/Accounts/Index', [
            'accounts' => $accounts,
            'filters' => $request->only(['status', 'search']),
        ]);
    }

    /**
     * Show the form for creating a new account.
     */
    public function create(): Response
    {
        $this->authorize('create', User::class);

        $roles = Role::all();

        return Inertia::render('Admin/Accounts/Create', [
            'roles' => $roles,
        ]);
    }

    /**
     * Store a newly created account.
     */
    public function store(
        CreateAccountRequest $request,
        CreateEmployeeAccount $createEmployee
    ): RedirectResponse {
        $this->authorize('create', User::class);

        $user = $createEmployee->execute(
            $request->input('name'),
            $request->input('email'),
            $request->input('slack_id'),
            $request->input('primary_role_id'),
            $request->input('secondary_role_id'),
            $request->input('hired_date'),
            $request->user()
        );

        return redirect()->route('accounts.show', $user)
            ->with('success', 'Employee account created successfully. Verification email sent via Slack.');
    }

    /**
     * Display the specified account.
     */
    public function show(User $account): Response
    {
        $this->authorize('view', $account);

        $account->load([
            'primaryRole',
            'secondaryRole',
            'auditLogs' => function ($query) {
                $query->with('performer')->latest()->limit(50);
            },
        ]);

        return Inertia::render('Admin/Accounts/Show', [
            'account' => $account,
            'auditLogs' => $account->auditLogs,
        ]);
    }

    /**
     * Show the form for editing the specified account.
     */
    public function edit(User $account): Response
    {
        $this->authorize('update', $account);

        $roles = Role::all();

        return Inertia::render('Admin/Accounts/Edit', [
            'account' => $account,
            'roles' => $roles,
        ]);
    }

    /**
     * Update the specified account.
     */
    public function update(Request $request, User $account): RedirectResponse
    {
        $this->authorize('update', $account);

        $validated = $request->validate([
            'name' => 'required|string|max:255',
            'email' => 'required|email|unique:users,email,' . $account->id,
            'slack_id' => 'required|string|unique:users,slack_id,' . $account->id,
            'primary_role_id' => 'required|exists:roles,id',
            'secondary_role_id' => 'nullable|exists:roles,id',
            'hired_date' => 'nullable|date',
        ]);

        // Track old values for audit
        $oldPrimaryRole = $account->primary_role_id;
        $oldSecondaryRole = $account->secondary_role_id;

        $account->update($validated);

        // Update Spatie roles if changed
        if ($oldPrimaryRole != $validated['primary_role_id'] || 
            $oldSecondaryRole != ($validated['secondary_role_id'] ?? null)) {
            
            $account->syncRoles([
                $validated['primary_role_id'],
                $validated['secondary_role_id'] ?? null,
            ]);

            // Log role change
            app(\App\Services\AuditLogService::class)->logRoleChanged(
                $account,
                $request->user(),
                $oldPrimaryRole,
                $validated['primary_role_id'],
                $oldSecondaryRole,
                $validated['secondary_role_id'] ?? null
            );
        }

        return redirect()->route('accounts.show', $account)
            ->with('success', 'Account updated successfully.');
    }

    /**
     * Activate an account.
     */
    public function activate(
        User $account,
        ActivateAccount $activateAccount
    ): RedirectResponse {
        $this->authorize('activate', User::class);

        $activateAccount->execute(
            $account,
            auth()->user(),
            'Account activated by HR'
        );

        return back()->with('success', 'Account activated successfully.');
    }

    /**
     * Deactivate an account.
     */
    public function deactivate(
        DeactivateAccountRequest $request,
        User $account,
        DeactivateAccount $deactivateAccount
    ): RedirectResponse {
        $this->authorize('deactivate', $account);

        $deactivateAccount->execute(
            $account,
            $request->user(),
            $request->input('reason')
        );

        return back()->with('success', 'Account deactivated successfully.');
    }
}
