# Contracts

**Feature**: Employee Management & Sidebar Navigation  
**Contract Type**: Internal Feature - No External Interfaces

## Summary

This feature does not expose any external contracts, APIs, or interfaces. All interactions occur within the Laravel application boundary through:

- Web routes (authenticated users only)
- Livewire components (server-side rendered)
- Blade templates (HTML output)

## Internal Contracts (For Reference)

### Livewire Component Public API

While not an external contract, the ManageEmployees Livewire component exposes these public methods and properties for internal use:

#### Public Properties
```php
public string $search = '';           // Search term for filtering
public int|string $statusFilter = ''; // Status filter (0,1,2 or empty)
public bool $showStatusModal = false; // Modal visibility state
public ?User $selectedUser = null;    // User being modified
public string $statusChangeReason = '';  // Optional reason for status change
```

#### Public Methods
```php
public function updateStatus(User $user): void
    // Opens modal for status change confirmation
    
public function confirmStatusChange(): void
    // Executes status change with audit logging
    
#[Computed]
public function employees(): LengthAwarePaginator
    // Returns filtered and paginated employee list
```

### Permission Requirements

**Consumer Contract**: Any user or system component attempting to access employee management features must satisfy these permission requirements:

| Feature | Required Permission | Enforced At |
|---------|-------------------|-------------|
| View employee list | `view-employees` | Route middleware + Component |
| Change employee status | `manage-employees` | Route middleware + Component method |
| View sidebar navigation | Authenticated (no specific permission) | Authentication middleware |

### Activity Log Contract

**Data Contract**: All status changes produce activity log entries with this structure:

```json
{
  "log_name": "employee-management",
  "description": "employee_status_changed",
  "subject_type": "App\\Models\\User",
  "subject_id": 123,
  "causer_type": "App\\Models\\User",
  "causer_id": 456,
  "properties": {
    "old_status": 1,
    "new_status": 2,
    "reason": "Optional reason string or null"
  },
  "created_at": "2026-03-07T10:30:00.000000Z"
}
```

## No External Contracts

This feature intentionally does not provide:
- ❌ REST API endpoints
- ❌ GraphQL schema
- ❌ CLI commands
- ❌ Event webhooks
- ❌ Third-party integrations
- ❌ JavaScript API
- ❌ Database views
- ❌ Stored procedures

All functionality is accessible exclusively through the authenticated web interface.

## Future Contract Considerations

If external access is needed in the future, consider:

1. **REST API**: Add `/api/employees` endpoints with API token authentication
2. **Events**: Broadcast `EmployeeStatusChanged` event for external listeners
3. **CLI Command**: `php artisan employees:deactivate {email}` for batch operations
4. **Export Contract**: Standardized CSV/JSON format for employee data export

These are explicitly out of scope for the current implementation (see spec.md § Out of Scope).
