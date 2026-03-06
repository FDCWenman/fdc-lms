# Implementation Plan: Employee Management & Sidebar Navigation

**Branch**: `003-employee-management-sidebar` | **Date**: March 7, 2026 | **Spec**: [spec.md](spec.md)
**Input**: Feature specification from `/specs/003-employee-management-sidebar/spec.md`

## Summary

Implement employee list viewing with search/filter capabilities and status management (Active/Deactivated) for users with appropriate permissions. Convert application navigation from top navbar to left sidebar with FDC branding. All interactions use Livewire 4 reactive components with Flux UI Free component library for consistent design. Status changes require modal confirmation with optional reason field, all changes logged via Spatie Activity Log for audit compliance.

## Technical Context

**Language/Version**: PHP 8.3.30  
**Framework**: Laravel 12.0  
**Primary Dependencies**: 
- Livewire 4.0 (reactive components)
- Flux UI Free 2.9.0 (component library)
- Spatie Laravel Permission 7.2 (RBAC)
- Spatie Laravel Activity Log 4.12 (audit trail)
- Laravel Fortify 1.30 (authentication)

**Storage**: SQLite (development), supports MySQL/PostgreSQL (production)  
**Testing**: PHPUnit 11.5.3, Laravel Dusk (browser tests)  
**Target Platform**: Web application (Docker container: addfc01e309b)  
**Project Type**: Monolithic Laravel web application with Livewire frontend  
**Performance Goals**: 
- Employee list load time <2 seconds for 1000 employees
- Search/filter response <500ms
- Real-time updates without page refresh

**Constraints**:
- Must use Flux UI components exclusively (no custom HTML form elements)
- Must maintain backward compatibility with existing role management
- All routes must use permission middleware
- No direct database manipulation in controllers (use Eloquent)

**Scale/Scope**: 
- Initial: 10-50 employees
- Target: Up to 1000 employees
- 2 main features (Employee Management + Sidebar Navigation)
- 4 new test suites required

## Constitution Check

*GATE: Must pass before Phase 0 research. Re-check after Phase 1 design.*

### ✅ Approval Workflow Integrity (NON-NEGOTIABLE)
- **Status**: PASS
- **Assessment**: No approval workflows in this feature. Employee status changes are direct administrative actions with audit logging. No bypass mechanisms.

### ✅ Data Consistency & Audit Trail
- **Status**: PASS
- **Assessment**: All status changes will be logged via Spatie Activity Log capturing Who, What, When, and Why (optional reason). No soft deletes needed (status is a state field, not deletion). Audit logs immutable and separate.

### ✅ Permission-Based Access Control
- **Status**: PASS
- **Assessment**: 
  - `view-employees` permission for list viewing (already seeded)
  - `manage-employees` permission for status changes (already seeded)
  - Permission checks at route level (middleware) and UI level (conditional rendering)
  - Server-side validation prevents unauthorized actions

### ⚠️ Leave Balance Accuracy
- **Status**: N/A
- **Assessment**: This feature does not touch leave balances. No gate violation.

### ✅ Test-First Development (NON-NEGOTIABLE)
- **Status**: PASS
- **Commitment**: 
  - Feature tests for complete user workflows (view list, search, filter, status change)
  - Unit tests for Livewire component methods
  - Browser tests for sidebar navigation and reactive updates
  - Tests written after user approval but before implementation
  - Minimum 80% coverage for business logic

### ✅ Visual Documentation (MANDATORY)
- **Status**: PASS
- **Commitment**: Mermaid diagrams will be created for:
  - User authentication and permission flow
  - Status change workflow with modal interaction
  - Data model showing User, Permission, Role, Activity relationships
  - Sidebar navigation structure

### ⚠️ Date & Time Handling Standards
- **Status**: N/A
- **Assessment**: Feature uses timestamps for audit logging only (Laravel handles UTC automatically). No date range validations needed.

### ⚠️ Notification Reliability
- **Status**: N/A
- **Assessment**: No notifications required for this feature (out of scope per spec).

### 🔄 Re-check After Phase 1 Design
All gates will be re-evaluated after data model and contracts are finalized.

---

## Phase 0: Research Complete ✅

[See research.md](research.md) for full details.

**Key Decisions**:
- Single Livewire component with computed properties
- Flux UI modal for status changes with optional reason
- Spatie Activity Log with custom properties
- Eager loading for performance optimization
- Feature + Unit + Browser test strategy

---

## Phase 1: Design Complete ✅

### Data Model ✅
[See data-model.md](data-model.md) for full details.

**Summary**:
- 4 entities: User, Role, Permission, Activity
- 0 new tables required (using existing schema)
- ERD and state diagrams included
- Query optimization strategy defined

### Contracts ✅
[See contracts/README.md](contracts/README.md) for full details.

**Summary**: Internal feature only - no external contracts, APIs, or interfaces exposed.

### Quickstart Guide ✅
[See quickstart.md](quickstart.md) for full details.

**Summary**: Developer onboarding guide with setup instructions, architecture overview, TDD workflow, and troubleshooting.

---

## Constitution Re-Check (Post-Design)

### ✅ Approval Workflow Integrity
- **Re-Assessment**: PASS - Design confirms no approval workflows. Status changes are administrative actions with audit trails.

### ✅ Data Consistency & Audit Trail
- **Re-Assessment**: PASS - Activity log integration confirmed in data-model.md. All status changes logged with causer, timestamp, old/new values, and optional reason.

### ✅ Permission-Based Access Control
- **Re-Assessment**: PASS - Permission checks confirmed at route level (middleware) and component level (authorize() calls). Server-side enforcement validated.

### ✅ Test-First Development
- **Re-Assessment**: PASS - Test structure defined in quickstart.md: 8 feature tests + 6 status tests + 5 sidebar tests + 4 unit tests = 23 new tests.

### ✅ Visual Documentation
- **Re-Assessment**: PASS - Mermaid diagrams created:
  - ERD in data-model.md ✅
  - State diagram for employee status ✅
  - Data flow for status change workflow ✅
  - Permission flow in quickstart.md ✅

### Final Verdict: ALL GATES PASS ✅

---

## Next Steps

1. **Run `/speckit.tasks`** to generate granular implementation tasks (tasks.md)
2. **Review generated tasks** and adjust priorities if needed
3. **Start TDD cycle**:
   - Write failing tests
   - Implement features
   - Pass tests
   - Refactor
4. **Run full test suite** before finalizing
5. **Update CHANGELOG.md** with feature summary
6. **Create pull request** for review

---

## Appendix: File Manifest

### Created Files (Phase 0 & 1)
- [x] `specs/003-employee-management-sidebar/plan.md` (this file)
- [x] `specs/003-employee-management-sidebar/research.md`
- [x] `specs/003-employee-management-sidebar/data-model.md`
- [x] `specs/003-employee-management-sidebar/quickstart.md`
- [x] `specs/003-employee-management-sidebar/contracts/README.md`
- [x] `.github/agents/copilot-instructions.md` (updated)

### To Be Created (Phase 2)
- [ ] `specs/003-employee-management-sidebar/tasks.md` (via `/speckit.tasks`)

### To Be Created (Implementation)
- [ ] `app/Livewire/Employees/ManageEmployees.php`
- [ ] `resources/views/livewire/employees/manage-employees.blade.php`
- [ ] `tests/Feature/EmployeeListTest.php`
- [ ] `tests/Feature/EmployeeStatusTest.php`
- [ ] `tests/Feature/SidebarNavigationTest.php`
- [ ] `tests/Unit/Livewire/ManageEmployeesTest.php`

### To Be Modified (Implementation)
- [ ] `resources/views/layouts/app.blade.php` (navbar → sidebar conversion)
- [ ] `routes/web.php` (add /employees route)

---

**Plan Status**: COMPLETE ✅  
**Date Completed**: March 7, 2026  
**Ready for**: `/speckit.tasks` command

## Project Structure

### Documentation (this feature)

```text
specs/003-employee-management-sidebar/
├── spec.md              # Feature specification (completed)
├── plan.md              # This file (in progress)
├── research.md          # Phase 0: Technology decisions and best practices
├── data-model.md        # Phase 1: Entity relationships and attributes
├── quickstart.md        # Phase 1: Developer onboarding guide
├── contracts/           # Phase 1: No external contracts (internal feature)
└── tasks.md             # Phase 2: Granular implementation checklist (not yet created)
```

### Source Code (Laravel monolith)

```text
app/
├── Livewire/
│   └── Employees/
│       └── ManageEmployees.php        # Main employee list component
├── Models/
│   └── User.php                        # Existing model (status attribute used)
├── Providers/
│   ├── AppServiceProvider.php          # Existing (no changes needed)
│   └── FortifyServiceProvider.php      # Existing authentication config
└── Http/
    └── Middleware/                      # Existing permission middleware

resources/
├── views/
│   ├── layouts/
│   │   └── app.blade.php               # Modified: navbar → sidebar conversion
│   ├── livewire/
│   │   └── employees/
│   │       └── manage-employees.blade.php  # Employee list UI
│   └── dashboard.blade.php              # Existing (no changes)
└── css/
    └── app.css                          # Tailwind CSS (no changes)

routes/
└── web.php                              # Modified: add /employees route

database/
├── seeders/
│   └── PermissionSeeder.php            # Existing (permissions already seeded)
└── migrations/
    └── (no new migrations needed)

tests/
├── Feature/
│   ├── EmployeeListTest.php            # New: employee viewing tests
│   ├── EmployeeStatusTest.php          # New: status management tests
│   └── SidebarNavigationTest.php      # New: sidebar UI tests
└── Unit/
    └── Livewire/
        └── ManageEmployeesTest.php     # New: component unit tests
```

**Structure Decision**: Laravel monolithic web application structure. All employee management logic resides in a single Livewire component (`ManageEmployees`) following Laravel conventions. Sidebar navigation implemented via layout file modification. No new database migrations required (using existing User model status field).

## Complexity Tracking

**No Constitution Violations** - All gates pass. No complexity justification needed.
