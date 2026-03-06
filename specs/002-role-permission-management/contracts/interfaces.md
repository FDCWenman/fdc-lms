# Contracts: Role & Permission Management

**Feature**: 002-role-permission-management  
**Date**: March 6, 2026  
**Contract Type**: Internal UI Interfaces (Livewire Components)

## Overview

This feature exposes interfaces through Livewire components that administrators interact with to manage roles and permissions. Since this is an internal administrative feature, contracts define the component interfaces, validation rules, and audit requirements rather than external APIs.

## Component Contracts

### 1. ManageRoles Component

**Purpose**: Main interface for viewing, creating, editing, and deleting roles

**Public Properties**:
```php
public bool $showCreateModal = false;
public bool $showEditModal = false;
public ?int $selectedRoleId = null;
public string $search = '';
public string $sortBy = 'name';
public string $sortDirection = 'asc';
```

**Public Methods**:
```php
// List roles with filtering/sorting
public function render(): View
// Returns: Collection of roles with permission counts

// Open create modal
public function openCreateModal(): void

// Open edit modal for specific role
public function openEditModal(int $roleId): void

// Close modals
public function closeModal(): void

// Handle role deletion
public function deleteRole(int $roleId): void
// Throws: AuthorizationException if user lacks permission
// Throws: ValidationException if role is protected or has users
```

**Events Emitted**:
- `role-created`: When new role successfully created
- `role-updated`: When role successfully updated
- `role-deleted`: When role successfully deleted

**Authorization Requirements**:
- Component requires `view_roles` permission
- Create action requires `create_roles` permission
- Edit action requires `edit_roles` permission
- Delete action requires `delete_roles` permission

**Validation Contract**:
```php
// Role name validation
'name' => 'required|string|max:50|regex:/^[a-zA-Z0-9\s\-_]+$/|unique:roles,name'
'description' => 'nullable|string|max:500'
```

---

### 2. CreateRole Component

**Purpose**: Form interface for creating new roles

**Public Properties**:
```php
public string $name = '';
public string $description = '';
```

**Public Methods**:
```php
// Submit form and create role
public function save(): void
// On success: Emits 'role-created', closes modal, shows success message
// On failure: Shows validation errors

// Reset form
public function resetForm(): void
```

**Validation Rules**:
```php
[
    'name' => ['required', 'string', 'max:50', 'regex:/^[a-zA-Z0-9\s\-_]+$/', 'unique:roles,name'],
    'description' => ['nullable', 'string', 'max:500'],
]
```

**Error Messages**:
```php
'name.required' => 'Role name is required.'
'name.max' => 'Role name cannot exceed 50 characters.'
'name.regex' => 'Role name can only contain letters, numbers, spaces, hyphens, and underscores.'
'name.unique' => 'A role with this name already exists.'
'description.max' => 'Description cannot exceed 500 characters.'
```

**Audit Requirement**:
- MUST log role creation via `RoleAuditService::logRoleCreated()`

---

### 3. EditRole Component

**Purpose**: Form interface for editing existing roles and assigning permissions

**Public Properties**:
```php
public Role $role;
public string $name = '';
public string $description = '';
public array $selectedPermissions = [];
public array $availablePermissions = [];
```

**Public Methods**:
```php
// Load role data
public function mount(int $roleId): void

// Update role details
public function updateRole(): void
// On success: Emits 'role-updated', shows success message
// On failure: Shows validation errors

// Assign/remove permissions
public function updatePermissions(): void
// On success: Updates role permissions, clears cache, logs changes

// Check if permission is assigned
public function hasPermission(int $permissionId): bool
```

**Validation Rules**:
```php
[
    'name' => ['required', 'string', 'max:50', 'regex:/^[a-zA-Z0-9\s\-_]+$/', Rule::unique('roles')->ignore($this->role->id)],
    'description' => ['nullable', 'string', 'max:500'],
    'selectedPermissions' => ['array'],
    'selectedPermissions.*' => ['exists:permissions,id'],
]
```

**Business Rules**:
- Cannot edit name of protected roles
- Cannot remove all permissions from Administrator role
- Permission changes take effect immediately (cache cleared)

**Audit Requirements**:
- Log role updates via `RoleAuditService::logRoleUpdated()`
- Log permission assignments via `RoleAuditService::logPermissionAssigned()`
- Log permission removals via `RoleAuditService::logPermissionRemoved()`

---

### 4. AssignRoles Component

**Purpose**: Interface for assigning roles to users

**Public Properties**:
```php
public User $user;
public array $selectedRoles = [];
public array $availableRoles = [];
```

**Public Methods**:
```php
// Load user and roles
public function mount(int $userId): void

// Update user roles
public function updateRoles(): void
// On success: Syncs user roles, shows success message
// On failure: Shows validation errors

// Check if user has role
public function hasRole(int $roleId): bool
```

**Validation Rules**:
```php
[
    'selectedRoles' => ['required', 'array', 'min:1'],
    'selectedRoles.*' => ['exists:roles,id'],
]
```

**Business Rules**:
- User must have at least one role
- Cannot remove Administrator role from last administrator
- Cannot remove own Administrator role if sole administrator
- Role changes take effect on user's next request

**Audit Requirements**:
- Log role assignments via `RoleAuditService::logUserRoleAssigned()`
- Log role removals via `RoleAuditService::logUserRoleRemoved()`

---

### 5. ViewPermissions Component

**Purpose**: Display all system permissions organized by category

**Public Properties**:
```php
public string $searchTerm = '';
public ?string $filterCategory = null;
```

**Public Methods**:
```php
// Display permissions grouped by category
public function render(): View
// Returns: Collection of permissions grouped by category

// Filter by category
public function filterByCategory(?string $category): void
```

**Data Structure**:
```php
// Permissions returned as:
[
    'Role Management' => [
        ['id' => 1, 'name' => 'view_roles', 'description' => 'View all roles'],
        ['id' => 2, 'name' => 'create_roles', 'description' => 'Create new roles'],
    ],
    'Leave Management' => [
        ['id' => 10, 'name' => 'view_leave_applications', 'description' => '...'],
    ],
]
```

**Authorization**:
- Requires `view_roles` permission (permissions are viewed in context of role management)
- Read-only interface (no create/edit/delete)

---

### 6. ViewAuditLogs Component

**Purpose**: Display audit trail of role management activities

**Public Properties**:
```php
public string $filterAction = '';
public ?int $filterUserId = null;
public string $dateFrom = '';
public string $dateTo = '';
```

**Public Methods**:
```php
// Display paginated audit logs
public function render(): View
// Returns: Paginated collection of audit logs with user info

// Apply filters
public function applyFilters(): void

// Clear filters
public function clearFilters(): void

// Export audit logs (future enhancement)
public function export(): void
```

**Data Structure**:
```php
// Each audit log entry:
[
    'id' => 123,
    'action' => 'permission_assigned',
    'description' => "Permission 'approve_leave_requests' assigned to role 'Team Lead'",
    'user' => ['id' => 1, 'name' => 'Admin User'],
    'old_values' => ['permissions' => [...]],
    'new_values' => ['permissions' => [...]],
    'created_at' => '2026-03-06 14:30:00',
]
```

**Authorization**:
- Requires `view_audit_logs` permission
- Read-only interface
- No modification or deletion of audit logs

**Performance**:
- Always paginated (50 records per page)
- Indexed queries on date ranges and user_id

---

## Service Contracts

### RoleAuditService

**Purpose**: Centralized service for creating audit log entries

**Public Methods**:

```php
// Log role creation
public function logRoleCreated(Role $role, User $user): void

// Log role update
public function logRoleUpdated(Role $role, array $oldValues, User $user): void

// Log role deletion
public function logRoleDeleted(Role $role, User $user): void

// Log permission assigned to role
public function logPermissionAssigned(Role $role, Permission $permission, User $user): void

// Log permission removed from role
public function logPermissionRemoved(Role $role, Permission $permission, User $user): void

// Log role assigned to user
public function logUserRoleAssigned(User $targetUser, Role $role, User $performer): void

// Log role removed from user
public function logUserRoleRemoved(User $targetUser, Role $role, User $performer): void
```

**Contract Guarantees**:
- All methods create immutable audit log entries
- Timestamps automatically recorded
- User ID of performer always recorded
- Description field always human-readable
- Never throws exceptions (logs errors internally)

---

## Validation Contracts

### Role Name Validation

**Pattern**: `/^[a-zA-Z0-9\s\-_]+$/`
**Max Length**: 50 characters
**Uniqueness**: Per guard (typically 'web')

**Valid Examples**:
- "Team Lead"
- "HR_Manager-2024"
- "Department Head"

**Invalid Examples**:
- "Team@Lead" (special character @)
- "HR!Manager" (special character !)
- "A" repeated 51 times (too long)

### Role Description Validation

**Max Length**: 500 characters
**Required**: No (nullable)

---

## Authorization Contracts

### Permission Hierarchy

All role management features require one of these permissions:

| Action | Required Permission |
|--------|-------------------|
| View roles list | `view_roles` |
| Create role | `create_roles` |
| Edit role name/description | `edit_roles` |
| Delete role | `delete_roles` |
| Assign permissions to role | `assign_permissions` |
| Assign roles to users | `assign_roles` |
| View audit logs | `view_audit_logs` |

### Protected Role Rules

**Administrator Role**:
- `is_protected = true` (database level)
- Cannot be deleted
- Cannot have all permissions removed
- Cannot be removed from last administrator user

**Enforcement**:
- Database flag: `is_protected` boolean
- Application-level checks before deletion
- Middleware and gate checks on all actions

---

## Event Contracts

### Events Emitted

```php
// Livewire events
'role-created' => ['roleId' => int, 'roleName' => string]
'role-updated' => ['roleId' => int, 'changes' => array]
'role-deleted' => ['roleId' => int, 'roleName' => string]
```

### Event Listeners

```php
// Other components can listen
protected $listeners = [
    'role-created' => 'refreshRolesList',
    'role-updated' => 'refreshRolesList',
    'role-deleted' => 'refreshRolesList',
];
```

---

## Cache Contracts

### Permission Cache Management

**Automatic Caching**: Spatie package automatically caches permission checks
**Cache Lifetime**: Until explicitly cleared
**Cache Key Pattern**: `spatie.permission.cache`

**When to Clear Cache**:
- After assigning/removing permissions to/from roles
- After syncing user roles
- After creating/updating permissions (if needed)

**How to Clear**:
```php
app()[\Spatie\Permission\PermissionRegistrar::class]->forgetCachedPermissions();
```

**Contract Guarantee**: Cache cleared automatically after all permission/role changes

---

## Error Handling Contracts

### HTTP Status Codes

| Scenario | Status Code | Response |
|----------|-------------|----------|
| Unauthorized access | 403 | Redirect to login or show error |
| Role not found | 404 | Show "Role not found" message |
| Validation failure | 422 | Show validation errors inline |
| Protected role deletion attempt | 403 | Show "Cannot delete protected role" |
| Last admin removal attempt | 403 | Show "Cannot remove last administrator" |

### User-Facing Error Messages

All error messages must be:
- **Clear**: Non-technical language
- **Actionable**: Tell user what to do
- **Specific**: Explain exactly what went wrong

**Examples**:
- ✅ "Role name must be unique. 'Team Lead' already exists."
- ❌ "Unique constraint violation on roles.name"

---

## Testing Contracts

### Test Coverage Requirements

Per constitution, minimum 80% coverage required for:
- Role CRUD operations
- Permission assignment logic
- User role assignment logic
- Audit logging
- Validation rules
- Authorization checks

### Required Test Cases

```php
// Role Management
- Can create role with valid data
- Cannot create role with invalid name
- Cannot create duplicate role
- Can update role name and description
- Can delete role without users
- Cannot delete role with assigned users
- Cannot delete protected role

// Permission Assignment
- Can assign permissions to role
- Can remove permissions from role
- Permission cache cleared after changes
- Audit log created for assignments

// User Role Assignment
- Can assign roles to user
- Can remove roles from user
- Cannot remove last administrator role
- User gains permissions from assigned roles

// Authorization
- Non-admin cannot access role management
- Specific permissions required for each action

// Audit Logging
- All actions create audit logs
- Audit logs are immutable
- Audit logs contain correct data
```

---

## Performance Contracts

### Response Time Targets

| Operation | Max Response Time |
|-----------|------------------|
| List roles (paginated) | < 200ms |
| Create role | < 300ms |
| Update role | < 300ms |
| Delete role | < 300ms |
| Assign permissions | < 500ms (cache clear overhead) |
| View audit logs | < 300ms |

### Scalability Targets

- Support up to 50 custom roles
- Support up to 100 permissions
- Support 1000+ users with role assignments
- Audit log pagination handles 100k+ entries

---

## Security Contracts

### CSRF Protection

- All state-changing operations protected by CSRF tokens (automatic in Laravel/Livewire)
- Token validation enforced on all POST/PUT/DELETE requests

### SQL Injection Prevention

- All database queries use Eloquent ORM or parameter binding
- No raw SQL queries except in reviewed migrations

### XSS Prevention

- Role names validated with regex (alphanumeric + limited special chars)
- All output escaped in Blade templates (automatic)
- No user-generated HTML accepted

### Authorization Layers

1. **Route Level**: Middleware checks authentication
2. **Component Level**: Gates check specific permissions
3. **Action Level**: Service methods verify authorization
4. **UI Level**: Blade directives hide unauthorized elements

---

## Backward Compatibility

### Database Changes

- New columns added as nullable or with defaults
- Existing spatie tables not modified (only extended)
- Migration rollbacks supported
- Data integrity maintained during migrations

### API Stability

- Component interfaces remain stable
- New methods can be added
- Existing method signatures unchanged
- Deprecation warnings for any future breaking changes

---

## Documentation Requirements

- All public methods must have docblocks
- Complex business logic must have inline comments
- Validation rules must be documented
- Authorization requirements must be explicit
- Audit requirements must be stated

## Contract Compliance

✅ **All contracts in this document are mandatory and must be implemented as specified**  
✅ **Deviations require documented justification**  
✅ **Tests must verify contract compliance**  
✅ **Code reviews must check against contracts**