# Research: Role & Permission Management Implementation

**Date**: March 6, 2026  
**Feature**: 002-role-permission-management  
**Laravel Version**: 12  
**Spatie Package**: laravel-permission ^7.2

---

## 1. Spatie/Laravel-Permission v7.2 Best Practices for Laravel 12

### Decision
Use spatie/laravel-permission v7.2 with Laravel 12's declarative middleware configuration pattern in `bootstrap/app.php`.

### Rationale
- **Already Installed**: Package is installed and middleware already registered in `bootstrap/app.php` (confirmed lines 18-20)
- **Laravel 12 Compatibility**: Middleware registered declaratively using `$middleware->alias()` following Laravel 12 structure
- **HasRoles Trait**: User model already implements `HasRoles` trait (confirmed in `app/Models/User.php` line 17)
- **Database Structure**: Permission tables migration already exists (created 2026_03_06_034631)
- **Configuration**: Package configured with default models in `config/permission.php`

### Code Pattern - Current Setup (Already Implemented)
```php
// bootstrap/app.php (Lines 18-20)
$middleware->alias([
    'role' => \Spatie\Permission\Middleware\RoleMiddleware::class,
    'permission' => \Spatie\Permission\Middleware\PermissionMiddleware::class,
    'role_or_permission' => \Spatie\Permission\Middleware\RoleOrPermissionMiddleware::class,
]);

// app/Models/User.php (Line 17)
use HasFactory, HasRoles, Notifiable, TwoFactorAuthenticatable;
```

### Key Implementation Notes
- Permission caching is enabled by default; cache automatically clears on permission/role updates
- Use `Permission::create()` and `Role::create()` for seeding
- Use `$user->givePermissionTo()`, `$role->givePermissionTo()` for assignment
- Use `$user->hasPermissionTo()`, `$user->can()` for checking permissions
- Middleware can be applied to routes: `->middleware('permission:edit articles')`

---

## 2. Permission Seeding - Database Seeders vs Config

### Decision
Use **database seeders** with a dedicated `PermissionSeeder` class to seed permissions.

### Rationale
1. **Clarity & Organization**: Permissions are explicit data entries, not configuration
2. **Version Control**: Easy to track permission changes through migration-style seeders
3. **Deployment**: Seeders run during deployment; permissions automatically populate on fresh installs
4. **Consistency**: Existing `RoleSeeder.php` already uses this pattern
5. **Testing**: Can be called in tests via `$this->seed(PermissionSeeder::class)`
6. **Laravel Convention**: Seeders are the standard approach for initial data population

### Code Pattern
```php
// database/seeders/PermissionSeeder.php
<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Spatie\Permission\Models\Permission;

class PermissionSeeder extends Seeder
{
    public function run(): void
    {
        // Define permissions grouped by functional area
        $permissions = [
            // Leave Management
            'view-leave-applications',
            'create-leave-application',
            'edit-own-leave-application',
            'delete-own-leave-application',
            'approve-leave-requests',
            'reject-leave-requests',
            'view-team-leave-applications',
            'view-all-leave-applications',
            
            // Employee Management
            'view-employees',
            'create-employee',
            'edit-employee',
            'delete-employee',
            
            // Role & Permission Management
            'view-roles',
            'create-role',
            'edit-role',
            'delete-role',
            'assign-permissions',
            'assign-roles',
            
            // System Settings
            'view-settings',
            'edit-settings',
        ];

        foreach ($permissions as $permission) {
            Permission::firstOrCreate(
                ['name' => $permission, 'guard_name' => 'web']
            );
        }

        $this->command->info('Permissions seeded successfully.');
    }
}
```

### Database Seeder Registration
```php
// database/seeders/DatabaseSeeder.php
public function run(): void
{
    $this->call([
        PermissionSeeder::class,
        RoleSeeder::class,
        // ... other seeders
    ]);
}
```

### Permission Organization Strategy
- Use kebab-case naming: `view-leave-applications`, `create-role`
- Group by functional area for UI display
- Name format: `{action}-{resource}` (e.g., `edit-employee`, `approve-leave-requests`)
- Store permissions with descriptions in separate lookup or use naming convention for UI display

---

## 3. Role Description Field Implementation

### Decision
Create a migration to add an optional `description` column to the `roles` table.

### Rationale
1. **Schema Modification Required**: Spatie's default migration doesn't include a description field
2. **Optional Field**: Spec confirms description is optional (Session 2026-03-06)
3. **User Experience**: Provides context for administrators when viewing/selecting roles
4. **Standard Laravel Approach**: Migration to modify existing table schema
5. **Backwards Compatible**: Nullable column won't affect existing roles

### Code Pattern
```php
// database/migrations/2026_03_06_XXXXXX_add_description_to_roles_table.php
<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('roles', function (Blueprint $table) {
            $table->text('description')->nullable()->after('guard_name');
        });
    }

    public function down(): void
    {
        Schema::table('roles', function (Blueprint $table) {
            $table->dropColumn('description');
        });
    }
};
```

### Usage in Forms
```php
// Form Request validation
public function rules(): array
{
    return [
        'name' => ['required', 'string', 'max:50', 'unique:roles,name'],
        'description' => ['nullable', 'string', 'max:500'],
    ];
}
```

### Display in UI
- Show description in role listing table as truncated text
- Display full description in role detail/edit views
- Optional field in create/edit forms with textarea input

---

## 4. Audit Trail Implementation

### Decision
Implement audit logging using **spatie/laravel-activitylog v4** package.

### Rationale
1. **Battle-Tested**: Industry-standard package with 43M+ downloads and active maintenance
2. **Feature-Rich**: Automatic model event logging, property changes tracking, batch logs support
3. **Future-Proof**: Can expand to system-wide activity logging as requirements grow
4. **Well-Documented**: Comprehensive documentation and community support
5. **Laravel Integration**: Seamless integration with Eloquent models using traits
6. **Flexible**: Supports custom properties, causers, subjects, and log descriptions

### Package Information
- **Package**: spatie/laravel-activitylog
- **Version**: ^4.0
- **Documentation**: https://spatie.be/docs/laravel-activitylog/v4/introduction
- **Repository**: https://github.com/spatie/laravel-activitylog

### Code Pattern

#### Installation
```bash
composer require spatie/laravel-activitylog
php artisan vendor:publish --provider="Spatie\Activitylog\ActivitylogServiceProvider" --tag="activitylog-migrations"
php artisan migrate
php artisan vendor:publish --provider="Spatie\Activitylog\ActivitylogServiceProvider" --tag="activitylog-config"
```

#### Basic Usage - Manual Logging
```php
// Log a simple activity
activity()->log('Role created');

// Log with subject and causer
activity()
    ->performedOn($role)
    ->causedBy(auth()->user())
    ->log('created');

// Log with properties (old/new values)
activity()
    ->performedOn($role)
    ->causedBy(auth()->user())
    ->withProperties([
        'old' => ['name' => 'Old Name'],
        'attributes' => ['name' => 'New Name']
    ])
    ->log('updated');
```

#### Automatic Model Event Logging
```php
// Add trait to Role model or create custom Role model extending Spatie's
// If using custom model:
// app/Models/Role.php
use Spatie\Activitylog\Traits\LogsActivity;
use Spatie\Activitylog\LogOptions;

class Role extends \Spatie\Permission\Models\Role
{
    use LogsActivity;

    public function getActivitylogOptions(): LogOptions
    {
        return LogOptions::defaults()
            ->logOnly(['name', 'description', 'is_protected'])
            ->logOnlyDirty()
            ->dontSubmitEmptyLogs();
    }
}

// Now all role changes are automatically logged
$role = Role::create(['name' => 'Team Lead']);
// Automatically creates activity log with 'created' description

$role->name = 'Senior Team Lead';
$role->save();
// Automatically creates activity log with 'updated' description and changes
```

#### Permission Assignment Logging
```php
// In Livewire component or controller
use Spatie\Permission\Models\Role;

// When assigning permissions
$role = Role::findOrFail($roleId);
$oldPermissions = $role->permissions->pluck('name')->toArray();

$role->syncPermissions($permissionIds);

activity()
    ->performedOn($role)
    ->causedBy(auth()->user())
    ->withProperties([
        'old' => ['permissions' => $oldPermissions],
        'attributes' => ['permissions' => $role->permissions->pluck('name')->toArray()]
    ])
    ->log('permissions_assigned');
```

#### User Role Assignment Logging
```php
// When assigning roles to users
use App\Models\User;

$user = User::findOrFail($userId);
$oldRoles = $user->roles->pluck('name')->toArray();

$user->syncRoles($roleIds);

activity()
    ->performedOn($user)
    ->causedBy(auth()->user())
    ->withProperties([
        'old' => ['roles' => $oldRoles],
        'attributes' => ['roles' => $user->roles->pluck('name')->toArray()]
    ])
    ->log('roles_assigned');
```

#### Retrieving Activity Logs
```php
// Get all activity logs
use Spatie\Activitylog\Models\Activity;

$activities = Activity::all();

// Get logs for specific model
$roleActivities = Activity::forSubject($role)->get();

// Get logs by specific user
$userActivities = Activity::causedBy(auth()->user())->get();

// Get recent logs with pagination
$recentActivities = Activity::latest()->paginate(50);

// Access log properties
foreach ($activities as $activity) {
    $activity->description;  // 'created', 'updated', 'permissions_assigned'
    $activity->subject;      // The role/user instance
    $activity->causer;       // The user who performed the action
    $activity->changes();    // Array of old/new values
    $activity->getExtraProperty('key'); // Custom properties
}
```

### Activity Log Descriptions
- `created` - Role/User created
- `updated` - Role/User name/description updated
- `deleted` - Role/User deleted
- `permissions_assigned` - Permissions modified on role
- `roles_assigned` - Roles modified on user

---

## 5. Permission Checking Patterns - Middleware vs Gates vs Policies

### Decision
Use a **layered approach**: Middleware for routes, Gates for Livewire/UI, and Blade directives for conditional rendering.

### Rationale
1. **Route Protection (Middleware)**: Prevent unauthorized access at the HTTP layer
2. **Component Logic (Gates)**: Check permissions in Livewire components and controllers
3. **UI Rendering (Blade Directives)**: Show/hide UI elements based on permissions
4. **No Policies Needed**: Role management doesn't follow resource-based authorization pattern
5. **Laravel 12 Best Practice**: Middleware for HTTP, gates for business logic

### Code Patterns

#### 1. Route Middleware (HTTP Layer)
```php
// routes/web.php
use Illuminate\Support\Facades\Route;

Route::middleware(['auth', 'user.active'])->group(function () {
    
    // Role Management Routes - Protected by permission middleware
    Route::prefix('admin/roles')->name('admin.roles.')->middleware('permission:view-roles')->group(function () {
        Route::get('/', [RoleController::class, 'index'])->name('index');
        Route::get('/create', [RoleController::class, 'create'])->name('create')->middleware('permission:create-role');
        Route::post('/', [RoleController::class, 'store'])->name('store')->middleware('permission:create-role');
        Route::get('/{role}/edit', [RoleController::class, 'edit'])->name('edit')->middleware('permission:edit-role');
        Route::put('/{role}', [RoleController::class, 'update'])->name('update')->middleware('permission:edit-role');
        Route::delete('/{role}', [RoleController::class, 'destroy'])->name('destroy')->middleware('permission:delete-role');
    });
    
    // Or use role middleware for broader access
    Route::middleware('role:administrator')->group(function () {
        // Admin-only routes
    });
});
```

#### 2. Gates for Livewire/Controller Logic
```php
// app/Providers/AppServiceProvider.php (or dedicated AuthServiceProvider if needed)
use Illuminate\Support\Facades\Gate;

public function boot(): void
{
    // Define gates for role management
    Gate::define('manage-roles', function (User $user) {
        return $user->hasPermissionTo('view-roles');
    });
    
    Gate::define('create-role', function (User $user) {
        return $user->hasPermissionTo('create-role');
    });
    
    Gate::define('edit-role', function (User $user) {
        return $user->hasPermissionTo('edit-role');
    });
    
    Gate::define('delete-role', function (User $user, Role $role) {
        // Prevent deletion of Administrator role
        if ($role->name === 'Administrator') {
            return false;
        }
        return $user->hasPermissionTo('delete-role');
    });
}
```

#### 3. Livewire Component Usage
```php
// app/Livewire/Admin/RoleManagement.php
use Illuminate\Support\Facades\Gate;

class RoleManagement extends Component
{
    public function mount()
    {
        // Check permission in component
        abort_unless(Gate::allows('manage-roles'), 403);
    }
    
    public function deleteRole($roleId)
    {
        $role = Role::findOrFail($roleId);
        
        // Check permission before action
        if (Gate::denies('delete-role', $role)) {
            $this->addError('role', 'You do not have permission to delete this role.');
            return;
        }
        
        $role->delete();
    }
    
    public function render()
    {
        return view('livewire.admin.role-management', [
            'roles' => Role::withCount('users')->get(),
            'canCreate' => Gate::allows('create-role'),
            'canEdit' => Gate::allows('edit-role'),
        ]);
    }
}
```

#### 4. Blade Directives for UI Rendering
```blade
{{-- resources/views/livewire/admin/role-management.blade.php --}}
<div>
    @can('create-role')
        <flux:button wire:click="createRole">Create New Role</flux:button>
    @endcan
    
    <table>
        @foreach($roles as $role)
            <tr>
                <td>{{ $role->name }}</td>
                <td>
                    @can('edit-role')
                        <flux:button wire:click="editRole({{ $role->id }})">Edit</flux:button>
                    @endcan
                    
                    @can('delete-role', $role)
                        <flux:button wire:click="deleteRole({{ $role->id }})">Delete</flux:button>
                    @endcan
                </td>
            </tr>
        @endforeach
    </table>
</div>

{{-- Alternative: Check permissions directly --}}
@if(auth()->user()->hasPermissionTo('create-role'))
    <flux:button>Create Role</flux:button>
@endif
```

#### 5. API Routes (If Applicable)
```php
// routes/api.php (if building API)
Route::middleware(['auth:sanctum'])->group(function () {
    Route::apiResource('roles', RoleApiController::class)
        ->middleware('permission:view-roles');
});

// In controller
public function store(Request $request)
{
    abort_unless(auth()->user()->hasPermissionTo('create-role'), 403);
    // ... create logic
}
```

### Summary - When to Use Each Pattern

| Layer | Method | Use Case | Example |
|-------|--------|----------|---------|
| **HTTP/Route** | Middleware | Protect entire routes/route groups | `->middleware('permission:view-roles')` |
| **Business Logic** | Gates | Check permissions in controllers/Livewire | `Gate::allows('create-role')` |
| **UI Rendering** | Blade Directives | Show/hide UI elements | `@can('edit-role')` |
| **Direct Check** | HasPermissionTo | Simple permission checks | `$user->hasPermissionTo('delete-role')` |

---

## 6. Role Name Validation Rules

### Decision
Implement Laravel validation with custom regex rule for alphanumeric + spaces/hyphens/underscores, max 50 characters.

### Rationale
1. **Spec Requirement**: Maximum 50 characters; alphanumeric plus spaces, hyphens, and underscores only
2. **Unique Constraint**: Role names must be unique per guard
3. **Required Field**: Name is mandatory (description is optional)
4. **User-Friendly**: Allow spaces for readable role names like "Team Lead" or "HR Manager"
5. **Database Consistency**: Prevent special characters that could cause issues

### Code Pattern

#### Form Request Class
```php
// app/Http/Requests/StoreRoleRequest.php
<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class StoreRoleRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()->hasPermissionTo('create-role');
    }

    public function rules(): array
    {
        return [
            'name' => [
                'required',
                'string',
                'max:50',
                'regex:/^[a-zA-Z0-9\s\-_]+$/',
                Rule::unique('roles', 'name')->where('guard_name', 'web'),
            ],
            'description' => [
                'nullable',
                'string',
                'max:500',
            ],
        ];
    }

    public function messages(): array
    {
        return [
            'name.required' => 'Role name is required.',
            'name.max' => 'Role name must not exceed 50 characters.',
            'name.regex' => 'Role name may only contain letters, numbers, spaces, hyphens, and underscores.',
            'name.unique' => 'A role with this name already exists.',
            'description.max' => 'Description must not exceed 500 characters.',
        ];
    }
}
```

#### Update Request (For Editing)
```php
// app/Http/Requests/UpdateRoleRequest.php
<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class UpdateRoleRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()->hasPermissionTo('edit-role');
    }

    public function rules(): array
    {
        $roleId = $this->route('role')->id;
        
        return [
            'name' => [
                'required',
                'string',
                'max:50',
                'regex:/^[a-zA-Z0-9\s\-_]+$/',
                Rule::unique('roles', 'name')
                    ->where('guard_name', 'web')
                    ->ignore($roleId),
            ],
            'description' => [
                'nullable',
                'string',
                'max:500',
            ],
        ];
    }

    public function messages(): array
    {
        return [
            'name.required' => 'Role name is required.',
            'name.max' => 'Role name must not exceed 50 characters.',
            'name.regex' => 'Role name may only contain letters, numbers, spaces, hyphens, and underscores.',
            'name.unique' => 'A role with this name already exists.',
            'description.max' => 'Description must not exceed 500 characters.',
        ];
    }
}
```

#### Livewire Component Validation
```php
// app/Livewire/Admin/CreateRole.php
use Livewire\Component;
use Illuminate\Validation\Rule;

class CreateRole extends Component
{
    public string $name = '';
    public string $description = '';
    
    protected function rules(): array
    {
        return [
            'name' => [
                'required',
                'string',
                'max:50',
                'regex:/^[a-zA-Z0-9\s\-_]+$/',
                Rule::unique('roles', 'name')->where('guard_name', 'web'),
            ],
            'description' => [
                'nullable',
                'string',
                'max:500',
            ],
        ];
    }
    
    protected function messages(): array
    {
        return [
            'name.required' => 'Role name is required.',
            'name.max' => 'Role name must not exceed 50 characters.',
            'name.regex' => 'Role name may only contain letters, numbers, spaces, hyphens, and underscores.',
            'name.unique' => 'A role with this name already exists.',
        ];
    }
    
    public function save(): void
    {
        $this->validate();
        
        Role::create([
            'name' => $this->name,
            'guard_name' => 'web',
            'description' => $this->description,
        ]);
        
        $this->redirect(route('admin.roles.index'));
    }
}
```

#### Test Examples
```php
// tests/Feature/RoleValidationTest.php
public function test_role_name_is_required(): void
{
    $user = User::factory()->create();
    $user->givePermissionTo('create-role');
    
    $response = $this->actingAs($user)->post(route('admin.roles.store'), [
        'name' => '',
    ]);
    
    $response->assertSessionHasErrors('name');
}

public function test_role_name_cannot_exceed_50_characters(): void
{
    $user = User::factory()->create();
    $user->givePermissionTo('create-role');
    
    $response = $this->actingAs($user)->post(route('admin.roles.store'), [
        'name' => str_repeat('a', 51),
    ]);
    
    $response->assertSessionHasErrors('name');
}

public function test_role_name_must_match_allowed_characters(): void
{
    $user = User::factory()->create();
    $user->givePermissionTo('create-role');
    
    // Invalid: special characters
    $response = $this->actingAs($user)->post(route('admin.roles.store'), [
        'name' => 'Role@Name!',
    ]);
    $response->assertSessionHasErrors('name');
    
    // Valid: alphanumeric, spaces, hyphens, underscores
    $response = $this->actingAs($user)->post(route('admin.roles.store'), [
        'name' => 'Team Lead-HR_Manager 2024',
        'guard_name' => 'web',
    ]);
    $response->assertSessionHasNoErrors();
}

public function test_role_name_must_be_unique(): void
{
    Role::create(['name' => 'HR Manager', 'guard_name' => 'web']);
    
    $user = User::factory()->create();
    $user->givePermissionTo('create-role');
    
    $response = $this->actingAs($user)->post(route('admin.roles.store'), [
        'name' => 'HR Manager',
    ]);
    
    $response->assertSessionHasErrors('name');
}
```

### Validation Regex Breakdown
- `^` - Start of string
- `[a-zA-Z0-9\s\-_]+` - One or more of:
  - `a-zA-Z` - Uppercase or lowercase letters
  - `0-9` - Numbers
  - `\s` - Spaces
  - `\-` - Hyphens
  - `_` - Underscores
- `+` - One or more occurrences
- `$` - End of string

### Valid Examples
- ✅ "Team Lead"
- ✅ "HR-Manager"
- ✅ "Project_Manager"
- ✅ "Senior Developer 2024"
- ✅ "team-lead_hr"

### Invalid Examples
- ❌ "Role@Name" (special character @)
- ❌ "Admin!" (special character !)
- ❌ "Team.Lead" (period not allowed)
- ❌ "Manager#1" (hash not allowed)

---

## Additional Recommendations

### 1. Protected Roles
Create a constant or configuration for protected system roles:

```php
// config/roles.php
return [
    'protected' => ['Administrator', 'Super Admin'],
];

// Usage in controller/component
if (in_array($role->name, config('roles.protected'))) {
    throw new \Exception('Cannot delete protected system role.');
}
```

### 2. Permission Grouping for UI
Organize permissions by functional area for better UX:

```php
// app/Services/PermissionGroupService.php
class PermissionGroupService
{
    public static function grouped(): array
    {
        return [
            'Leave Management' => Permission::where('name', 'like', '%leave%')->get(),
            'Employee Management' => Permission::where('name', 'like', '%employee%')->get(),
            'Role & Permission Management' => Permission::where('name', 'like', '%role%')
                ->orWhere('name', 'like', '%permission%')->get(),
            'System Settings' => Permission::where('name', 'like', '%setting%')->get(),
        ];
    }
}
```

### 3. Role Usage Tracking
Add a helper method to prevent deleting roles with assigned users:

```php
// In RoleController or Livewire component
public function destroy(Role $role)
{
    if ($role->users()->count() > 0) {
        return back()->withErrors([
            'role' => "Cannot delete role '{$role->name}' as it is assigned to {$role->users()->count()} user(s)."
        ]);
    }
    
    $role->delete();
    RoleAuditService::log('deleted', 'Role', $role->id, $role->toArray(), null, "Deleted role: {$role->name}");
}
```

### 4. Testing Strategy
Follow test-first development as per constitution:

```php
// tests/Feature/RoleManagement/CreateRoleTest.php
// tests/Feature/RoleManagement/EditRoleTest.php
// tests/Feature/RoleManagement/DeleteRoleTest.php
// tests/Feature/RoleManagement/AssignPermissionsTest.php
// tests/Feature/RoleManagement/AssignRolesToUsersTest.php
```

Use existing test patterns from `tests/Feature/Auth/` as reference.

---

## Summary of Decisions

| Topic | Decision | Key Files to Create/Modify |
|-------|----------|---------------------------|
| **Spatie Setup** | Use existing v7.2 configuration | None (already configured) |
| **Permission Seeding** | Database seeder approach | `PermissionSeeder.php` |
| **Role Description** | Add migration for nullable text column | `add_description_to_roles_table.php` |
| **Audit Trail** | Custom logging solution | Migration, `RoleAuditLog.php`, `RoleAuditService.php` |
| **Permission Checking** | Layered: Middleware + Gates + Blade | Routes, Gates in AppServiceProvider, Blade directives |
| **Validation** | Form Requests with regex + unique rules | `StoreRoleRequest.php`, `UpdateRoleRequest.php` |

---

## Next Steps for Implementation

1. **Create PermissionSeeder** with all system permissions
2. **Create migration** to add `description` column to `roles` table
3. **Create audit logging infrastructure** (migration, model, service)
4. **Define Gates** in AppServiceProvider for role management
5. **Create Form Request classes** for validation
6. **Build Livewire components** for role CRUD operations
7. **Write tests first** following PHPUnit patterns
8. **Implement UI** using Flux UI components
9. **Run Pint** to format code
10. **Test all acceptance scenarios** from spec.md

---

**Research Complete**: All technical decisions documented with rationale and code patterns for implementation.
