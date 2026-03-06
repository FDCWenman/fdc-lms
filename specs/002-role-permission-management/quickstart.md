# Quick Start: Role & Permission Management

**Feature**: 002-role-permission-management  
**For**: Developers implementing this feature  
**Last Updated**: March 6, 2026

## Overview

This feature implements role-based access control (RBAC) for the LMS application using spatie/laravel-permission. Administrators can create roles, assign permissions to roles, and assign roles to users.

## Prerequisites

- Laravel 12 application running
- Database configured
- `spatie/laravel-permission` ^7.2 installed (already present)
- Authentication system in place (Fortify)

## Setup (5 minutes)

###1. Run Migrations

```bash
# Create new migrations
php artisan make:migration add_columns_to_permissions_table
php artisan make:migration add_columns_to_roles_table  
php artisan make:migration create_role_audit_logs_table

# Run all pending migrations
php artisan migrate
```

### 2. Seed Permissions and Roles

```bash
php artisan make:seeder PermissionSeeder
php artisan make:seeder RoleSeeder

# Run seeders
php artisan db:seed --class=PermissionSeeder
php artisan db:seed --class=RoleSeeder
```

### 3. Assign Administrator Role

```bash
# Via tinker
php artisan tinker
>>> $user = User::find(1); // Your admin user
>>> $user->assignRole('Administrator');
```

## Development Workflow (TDD)

### Step 1: Write the Test First

```bash
php artisan make:test RoleManagementTest
```

```php
// tests/Feature/RoleManagementTest.php
test('administrator can create a new role', function () {
    $admin = User::factory()->create();
    $admin->assignRole('Administrator');

    $response = $this->actingAs($admin)
        ->post(route('roles.store'), [
            'name' => 'Team Lead',
            'description' => 'Manages team leave approvals',
        ]);

    $response->assertRedirect();
    $this->assertDatabaseHas('roles', ['name' => 'Team Lead']);
});
```

### Step 2: Run Test (It Should Fail)

```bash
php artisan test --filter=RoleManagementTest
# Expected: Test fails because route/component doesn't exist
```

### Step 3: Implement Feature

```bash
# Create Livewire component
php artisan make:livewire ManageRoles

# Create Form Request
php artisan make:request StoreRoleRequest
```

### Step 4: Run Test Again (It Should Pass)

```bash
php artisan test --filter=RoleManagementTest
# Expected: All tests pass
```

## Key Files & Their Purpose

| File | Purpose |
|------|---------|
| `app/Models/User.php` | Already has `HasRoles` trait |
| `app/Livewire/ManageRoles.php` | Main UI component for role management |
| `app/Http/Requests/StoreRoleRequest.php` | Validation for creating roles |
| `app/Services/RoleAuditService.php` | Centralized audit logging |
| `database/seeders/PermissionSeeder.php` | Seeds all system permissions |
| `database/migrations/*_role_audit_logs_table.php` | Audit trail schema |
| `resources/views/livewire/manage-roles.blade.php` | Role management UI |

## Common Tasks

### Create a New Role

```php
use Spatie\Permission\Models\Role;

$role = Role::create([
    'name' => 'HR Manager',
    'description' => 'Handles HR-related tasks',
    'is_protected' => false,
]);

// Log the action
app(RoleAuditService::class)->logRoleCreated($role, auth()->user());
```

### Assign Permissions to Role

```php
$role = Role::findByName('HR Manager');
$role->givePermissionTo(['view_leave_applications', 'approve_leave_requests']);

// Log each permission assignment
foreach ($role->permissions as $permission) {
    app(RoleAuditService::class)->logPermissionAssigned($role, $permission, auth()->user());
}
```

### Assign Role to User

```php
$user = User::find($userId);
$user->assignRole('HR Manager');

// User now has all permissions from 'HR Manager' role
$user->can('approve_leave_requests'); // true
```

### Check Permissions in Code

```php
// In controller/Livewire
if (auth()->user()->can('delete_roles')) {
    // Allow deletion
}

// Or throw exception if not authorized
Gate::authorize('delete_roles');
```

### Check Permissions in Blade

```blade
@can('create_roles')
    <button wire:click="createRole">Create Role</button>
@endcan

@role('Administrator')
    <a href="{{ route('audit-logs.index') }}">Audit Logs</a>
@endrole
```

### Protect Routes

```php
// routes/web.php
Route::middleware(['auth', 'role:Administrator'])->group(function () {
    Route::get('/roles', ManageRoles::class)->name('roles.index');
});

// Or with specific permissions
Route::middleware(['auth', 'permission:view_roles'])->group(function () {
    Route::get('/roles', ManageRoles::class);
});
```

## Testing Quick Reference

```bash
# Run all role management tests
php artisan test --filter=Role

# Run specific test
php artisan test --filter=test_administrator_can_create_role

# Run with coverage
php artisan test --coverage --min=80
```

### Test Structure Template

```php
use App\Models\User;
use Spatie\Permission\Models\Role;
use Spatie\Permission\Models\Permission;

beforeEach(function () {
    $this->admin = User::factory()->create();
    $this->admin->assignRole('Administrator');
    
    $this->user = User::factory()->create();
});

test('descriptive test name', function () {
    // Arrange: Set up test data
    
    // Act: Perform the action
    
    // Assert: Verify the outcome
});
```

## Debugging Tips

### Permission Issues

```bash
# Clear permission cache
php artisan permission:cache-reset

# Or in code
app()[\Spatie\Permission\PermissionRegistrar::class]->forgetCachedPermissions();
```

### Check User Permissions

```php
// Via tinker
php artisan tinker
>>> $user = User::find(1);
>>> $user->getAllPermissions()->pluck('name');
>>> $user->getRoleNames();
```

### View Audit Logs

```php
// Get recent audit logs
RoleAuditLog::with('user:id,name')
    ->latest()
    ->limit(20)
    ->get();
```

## API Examples (if REST API needed)

```php
// routes/api.php
Route::middleware(['auth:sanctum', 'permission:view_roles'])->group(function () {
    Route::get('/roles', [RoleApiController::class, 'index']);
    Route::post('/roles', [RoleApiController::class, 'store'])->middleware('permission:create_roles');
});
```

```bash
# Test API endpoint
curl -H "Authorization: Bearer YOUR_TOKEN" \
     -H "Accept: application/json" \
     http://localhost/api/roles
```

## Performance Tips

1. **Always eager load relationships**:
   ```php
   Role::with(['permissions', 'users'])->get();
   ```

2. **Use `withCount()` for counts only**:
   ```php
   Role::withCount('users')->get();
   ```

3. **Paginate large datasets**:
   ```php
   Role::paginate(25);
   RoleAuditLog::latest()->paginate(50);
   ```

4. **Cache permission checks** (done automatically by spatie)

## Common Errors & Solutions

| Error | Solution |
|-------|----------|
| "Role does not exist" | Run `PermissionSeeder` |
| "Permission not found" | Check permission name matches seeded value |
| "SQLSTATE: column not found" | Run pending migrations |
| Permissions not updating | Clear permission cache |
| "Trying to get property of non-object" | Check user is authenticated |

## Next Steps After Implementation

1. ✅ Run full test suite: `php artisan test`
2. ✅ Run Pint for code style: `vendor/bin/pint`
3. ✅ Verify all audit logs working
4. ✅ Test with multiple users and roles
5. ✅ Review security checklist
6. ✅ Update documentation if needed

## Security Checklist

- [ ] All routes protected with authentication
- [ ] Permission checks at route and component level
- [ ] Administrator role marked as protected
- [ ] Cannot remove last administrator
- [ ] Role name validation prevents XSS
- [ ] Audit logs are immutable
- [ ] CSRF protection enabled (automatic)
- [ ] Rate limiting on API endpoints (if applicable)

## Resources

- [Spatie Permission Docs](https://spatie.be/docs/laravel-permission/v6/introduction)
- [Laravel Authorization](https://laravel.com/docs/12.x/authorization)
- [Livewire Documentation](https://livewire.laravel.com/docs)
- [Feature Spec](./spec.md)
- [Data Model](./data-model.md)
- [Research](./research.md)

## Need Help?

- Check `research.md` for implementation patterns
- Review `data-model.md` for database schema
- See test examples in `tests/Feature/RoleManagementTest.php`
- Use `php artisan tinker` to test Eloquent queries
- Check audit logs for debugging permission issues