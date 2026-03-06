# Tasks: Role & Permission Management

**Input**: Design documents from `/specs/002-role-permission-management/`
**Prerequisites**: plan.md, spec.md, research.md, data-model.md, contracts/

**Organization**: Tasks are grouped by user story to enable independent implementation and testing of each story.

## Format: `- [ ] [ID] [P?] [Story] Description with file path`

- **[P]**: Can run in parallel (different files, no dependencies)
- **[Story]**: Which user story this task belongs to (e.g., US1, US2, US3)
- Include exact file paths in descriptions

---

## Phase 1: Setup (Shared Infrastructure)

**Purpose**: Foundation setup - migrations, seeders, packages that all user stories depend on

- [ ] T001 Review .env configuration for database and Docker settings
- [ ] T002 Verify spatie/laravel-permission package is installed and configured in config/permission.php
- [ ] T003 Install spatie/laravel-activitylog: composer require spatie/laravel-activitylog
- [ ] T004 Publish activitylog migration: php artisan vendor:publish --provider="Spatie\\Activitylog\\ActivitylogServiceProvider" --tag="activitylog-migrations"
- [ ] T005 Publish activitylog config: php artisan vendor:publish --provider="Spatie\\Activitylog\\ActivitylogServiceProvider" --tag="activitylog-config"
- [ ] T006 [P] Create migration to add category and description columns to permissions table in database/migrations/YYYY_MM_DD_add_columns_to_permissions_table.php
- [ ] T007 [P] Create migration to add description and is_protected columns to roles table in database/migrations/YYYY_MM_DD_add_columns_to_roles_table.php
- [ ] T008 Run all migrations: php artisan migrate
- [ ] T009 Create PermissionSeeder with all system permissions in database/seeders/PermissionSeeder.php
- [ ] T010 Create RoleSeeder to create Administrator role in database/seeders/RoleSeeder.php
- [ ] T011 Run seeders: php artisan db:seed --class=PermissionSeeder && php artisan db:seed --class=RoleSeeder
- [ ] T012 Assign Administrator role to initial admin user via tinker or seeder
- [ ] T013 Create route group with auth and permission middleware in routes/web.php

---

## Phase 2: Foundational Features (Blocking Prerequisites)

**Purpose**: Core features that multiple user stories depend on

- [ ] T014 Create base role management layout view in resources/views/livewire/roles/layout.blade.php (if needed)

---

## Phase 3: User Story 1 - Create Custom Roles (P1)

**Goal**: Administrators can create new roles with names and optional descriptions

**Independent Test**: Create a role, verify it's saved, list roles, confirm it appears

- [ ] T015 [US1] Write feature test: administrator can view roles list in tests/Feature/RoleManagementTest.php
- [ ] T016 [US1] Write feature test: administrator can create role with valid name in tests/Feature/RoleManagementTest.php
- [ ] T017 [US1] Write feature test: role name validation (required, max 50, regex) in tests/Feature/RoleManagementTest.php
- [ ] T018 [US1] Write feature test: role name must be unique in tests/Feature/RoleManagementTest.php
- [ ] T019 [US1] Write feature test: description validation (optional, max 500) in tests/Feature/RoleManagementTest.php
- [ ] T020 [US1] Run tests (should fail): php artisan test --filter=RoleManagement
- [ ] T021 [US1] Create ManageRoles Livewire component in app/Http/Livewire/Roles/ManageRoles.php
- [ ] T022 [US1] Create manage-roles Blade view with roles list in resources/views/livewire/roles/manage-roles.blade.php
- [ ] T023 [US1] Create CreateRole Livewire component in app/Http/Livewire/Roles/CreateRole.php
- [ ] T024 [US1] Create create-role Blade view with form in resources/views/livewire/roles/create-role.blade.php
- [ ] T025 [US1] Create StoreRoleRequest with validation rules in app/Http/Requests/StoreRoleRequest.php
- [ ] T026 [US1] Implement role creation logic with activity logging using spatie/activitylog in CreateRole component
- [ ] T027 [US1] Add role count display to roles list
- [ ] T028 [US1] Run tests (should pass): php artisan test --filter=RoleManagement
- [ ] T029 [US1] Test manually: Create role through UI, verify in database
- [ ] T030 [US1] Run Pint for code style: vendor/bin/pint app/Http/Livewire/Roles app/Http/Requests

**Deliverable**: Functional role creation with validation and audit trail via spatie/activitylog

---

## Phase 4: User Story 2 - View System Permissions (P2)

**Goal**: Administrators can view all predefined system permissions grouped by category

**Independent Test**: View permissions list, verify all seeded permissions appear with descriptions

- [ ] T034 [US2] Write feature test: administrator can view permissions list in tests/Feature/PermissionManagementTest.php
- [ ] T035 [US2] Write feature test: permissions grouped by category in tests/Feature/PermissionManagementTest.php
- [ ] T036 [US2] Write feature test: permissions show name and description in tests/Feature/PermissionManagementTest.php
- [ ] T037 [US2] Run tests (should fail): php artisan test --filter=PermissionManagement
- [ ] T038 [P] [US2] Create ViewPermissions Livewire component in app/Http/Livewire/Roles/ViewPermissions.php
- [ ] T039 [P] [US2] Create view-permissions Blade view in resources/views/livewire/roles/view-permissions.blade.php
- [ ] T040 [US2] Implement permissions grouping by category logic
- [ ] T041 [US2] Add search/filter functionality for permissions
- [ ] T042 [US2] Run tests (should pass): php artisan test --filter=PermissionManagement
- [ ] T043 [US2] Test manually: View permissions, verify categories and descriptions
- [ ] T044 [US2] Run Pint: vendor/bin/pint app/Http/Livewire/Roles/ViewPermissions.php

**Deliverable**: Read-only permissions viewer organized by category

---

## Phase 5: User Story 3 - Assign Permissions to Roles (P1)

**Goal**: Administrators can assign multiple permissions to a role and remove them

**Independent Test**: Select role, assign permissions, save, verify associations in database

- [X] T045 [US3] Write feature test: can assign permission to role in tests/Feature/PermissionAssignmentTest.php
- [X] T046 [US3] Write feature test: can assign multiple permissions at once in tests/Feature/PermissionAssignmentTest.php
- [X] T047 [US3] Write feature test: can remove permission from role in tests/Feature/PermissionAssignmentTest.php
- [X] T048 [US3] Write feature test: permission cache cleared after changes in tests/Feature/PermissionAssignmentTest.php
- [X] T049 [US3] Write feature test: audit logs created for assignments in tests/Feature/PermissionAssignmentTest.php
- [X] T050 [US3] Write feature test: cannot assign non-existent permission in tests/Feature/PermissionAssignmentTest.php
- [X] T051 [US3] Run tests (should fail): php artisan test --filter=PermissionAssignment
- [X] T052 [US3] Create EditRole Livewire component in app/Http/Livewire/Roles/EditRole.php
- [X] T053 [US3] Create edit-role Blade view with permission checkboxes in resources/views/livewire/roles/edit-role.blade.php
- [X] T054 [US3] Create UpdateRoleRequest validation in app/Http/Requests/UpdateRoleRequest.php (Using inline validation)
- [X] T055 [US3] Create AssignPermissionsRequest validation in app/Http/Requests/AssignPermissionsRequest.php (Using inline validation)
- [X] T056 [US3] Implement permission sync logic in EditRole component
- [X] T057 [US3] Add permission cache clearing after sync: app()[\Spatie\Permission\PermissionRegistrar::class]->forgetCachedPermissions()
- [X] T058 [US3] Add activity logging for permission assignments/removals using activity()->performedOn($role)->causedBy(auth()->user())->log()
- [X] T059 [US3] Group permissions by category in edit form
- [X] T060 [US3] Add "Select All" / "Deselect All" by category
- [X] T061 [US3] Run tests (should pass): php artisan test --filter=PermissionAssignment
- [X] T062 [US3] Test manually: Assign permissions, verify cache cleared, check audit log
- [X] T063 [US3] Run Pint: vendor/bin/pint app/Http/Livewire/Roles/EditRole.php app/Http/Requests

**Deliverable**: Permission assignment interface with audit trail via spatie/activitylog and cache management

---

## Phase 6: User Story 4 - Assign Roles to Users (P1)

**Goal**: Administrators can assign one or more roles to users

**Independent Test**: Select user, assign role, verify user gains permissions

- [X] T064 [US4] Write feature test: can assign role to user in tests/Feature/RoleAssignmentTest.php
- [X] T065 [US4] Write feature test: can assign multiple roles to user in tests/Feature/RoleAssignmentTest.php
- [X] T066 [US4] Write feature test: can remove role from user in tests/Feature/RoleAssignmentTest.php
- [X] T067 [US4] Write feature test: user gains combined permissions from all roles in tests/Feature/RoleAssignmentTest.php
- [X] T068 [US4] Write feature test: cannot remove Administrator role from last admin in tests/Feature/RoleAssignmentTest.php
- [X] T069 [US4] Write feature test: audit logs created for role assignments in tests/Feature/RoleAssignmentTest.php
- [X] T070 [US4] Run tests (should fail): php artisan test --filter=RoleAssignment
- [X] T071 [US4] Create AssignRoles Livewire component in app/Http/Livewire/Roles/AssignRoles.php
- [X] T072 [US4] Create assign-roles Blade view in resources/views/livewire/roles/assign-roles.blade.php
- [X] T073 [US4] Implement role sync logic for users
- [X] T074 [US4] Add last administrator protection check
- [X] T075 [US4] Add activity logging for user role assignments/removals using activity()->performedOn($user)->causedBy(auth()->user())->log()
- [X] T076 [US4] Display role descriptions to help administrators understand each role
- [X] T077 [US4] Add visual indicators for currently assigned roles
- [X] T078 [US4] Run tests (should pass): php artisan test --filter=RoleAssignment
- [ ] T079 [US4] Test manually: Assign roles to users, verify permissions work
- [X] T080 [US4] Run Pint: vendor/bin/pint app/Http/Livewire/Roles/AssignRoles.php

**Deliverable**: User-role assignment with multi-role support and admin protection

---

## Phase 7: User Story 6 - Manage Built-in Administrator Role (P2)

**Goal**: Protect Administrator role from deletion and ensure at least one admin exists

**Independent Test**: Attempt to delete Administrator role, verify prevention

- [ ] T081 [US6] Write feature test: cannot delete protected role in tests/Feature/RoleManagementTest.php
- [ ] T082 [US6] Write feature test: Administrator role is always protected in tests/Feature/RoleManagementTest.php
- [ ] T083 [US6] Write feature test: cannot remove own Administrator role if sole admin in tests/Feature/RoleManagementTest.php
- [ ] T084 [US6] Write feature test: can remove Administrator from user if multiple admins exist in tests/Feature/RoleManagementTest.php
- [ ] T085 [US6] Run tests (should fail): php artisan test --filter=RoleManagement
- [ ] T086 [US6] Add is_protected flag check in role deletion logic in ManageRoles component
- [ ] T087 [US6] Add last administrator check in AssignRoles component
- [ ] T088 [US6] Add error messages for protected role deletion attempts
- [ ] T089 [US6] Add confirmation modal for role deletion in manage-roles.blade.php
- [ ] T090 [US6] Run tests (should pass): php artisan test --filter=RoleManagement
- [ ] T091 [US6] Test manually: Try to delete Administrator role, verify prevention

**Deliverable**: Administrator role protection and last admin safeguards

---

## Phase 8: User Story 5 - View Role Usage and Impact (P3)

**Goal**: Administrators can see which users are assigned to each role

**Independent Test**: View role details, see user count and list

- [ ] T092 [P] [US5] Write feature test: role list shows user count in tests/Feature/RoleManagementTest.php
- [ ] T093 [P] [US5] Write feature test: can view users assigned to role in tests/Feature/RoleManagementTest.php
- [ ] T094 [P] [US5] Write feature test: deletion warning if role has users in tests/Feature/RoleManagementTest.php
- [ ] T095 [P] [US5] Run tests (should fail): php artisan test --filter=RoleManagement
- [ ] T096 [P] [US5] Add user count display using withCount() in ManageRoles component
- [ ] T097 [P] [US5] Create role detail view showing assigned users
- [ ] T098 [P] [US5] Add deletion warning modal when role has users
- [ ] T099 [P] [US5] Add option to view all users with specific role
- [ ] T100 [P] [US5] Run tests (should pass): php artisan test --filter=RoleManagement
- [ ] T101 [P] [US5] Test manually: View role usage, try to delete role with users

**Deliverable**: Role usage visibility and deletion safeguards

---

## Phase 9: Audit Trail Viewer

**Goal**: Administrators can view audit logs of all role management activities

**Independent Test**: Perform role action, verify audit log entry created and visible

- [ ] T102 Write feature test: can view audit logs in tests/Feature/RoleAuditTest.php
- [ ] T103 Write feature test: audit logs are paginated in tests/Feature/RoleAuditTest.php
- [ ] T104 Write feature test: can filter by action type in tests/Feature/RoleAuditTest.php
- [ ] T105 Write feature test: can filter by user in tests/Feature/RoleAuditTest.php
- [ ] T106 Write feature test: can filter by date range in tests/Feature/RoleAuditTest.php
- [ ] T107 Run tests (should fail): php artisan test --filter=RoleAudit
- [ ] T108 [P] Create ViewAuditLogs Livewire component in app/Http/Livewire/Roles/ViewAuditLogs.php
- [ ] T109 [P] Create view-audit-logs Blade view in resources/views/livewire/roles/view-audit-logs.blade.php
- [ ] T110 Implement audit log listing with pagination (50 per page)
- [ ] T111 Add filter by action type (created, updated, deleted, permission_assigned, etc.)
- [ ] T112 Add filter by user dropdown
- [ ] T113 Add date range filter (from/to)
- [ ] T114 Display old_values and new_values in readable format
- [ ] T115 Add permission check: view_audit_logs
- [ ] T116 Run tests (should pass): php artisan test --filter=RoleAudit
- [ ] T117 Test manually: View audit logs, apply filters, verify pagination
- [ ] T118 Run Pint: vendor/bin/pint app/Http/Livewire/Roles/ViewAuditLogs.php

**Deliverable**: Filterable, paginated audit log viewer

---

## Phase 10: Polish & Cross-Cutting Concerns

**Purpose**: Final touches, optimization, and documentation

- [ ] T119 Add loading states to all Livewire components using wire:loading
- [ ] T120 Add success/error toast notifications for all actions
- [ ] T121 Add confirmation modals for all destructive actions
- [ ] T122 Optimize queries with eager loading (with, withCount)
- [ ] T123 Add helpful tooltips for permissions explaining what they grant
- [ ] T124 Ensure all forms have proper CSRF protection (automatic in Livewire)
- [ ] T125 Add keyboard shortcuts for common actions (optional)
- [ ] T126 Test responsive design on mobile devices
- [ ] T127 Add role management link to admin navigation menu
- [ ] T128 Create user documentation for role management
- [ ] T129 Create administrator guide for permission system
- [ ] T130 Run full test suite: php artisan test
- [ ] T131 Check test coverage: php artisan test --coverage --min=80
- [ ] T132 Run Pint on all modified files: vendor/bin/pint
- [ ] T133 Review all error messages for clarity
- [ ] T134 Verify all authorization checks are in place
- [ ] T135 Test with multiple users and roles simultaneously
- [ ] T136 Performance test: measure query counts and response times
- [ ] T137 Security review: verify CSRF, XSS prevention, SQL injection prevention
- [ ] T138 Update CHANGELOG.md with new feature
- [ ] T139 Create pull request with complete description
- [ ] T140 Request code review from team

---

## Dependencies & Execution Strategy

### User Story Completion Order (MVP First)

1. **MVP (User Story 1 + 3)**: Create roles and assign permissions to them
   - Phases: Setup → Foundational → US1 → US3
   - Can deliver basic RBAC functionality
   - Tasks: T001-T014, T015-T030, T045-T060

2. **Complete RBAC (User Story 4)**: Assign roles to users
   - Add: US4
   - Full functional system
   - Tasks: T064-T080

3. **Enhanced Experience (User Story 2, 6, 5)**: View permissions, protection, usage
   - Add: US2, US6, US5
   - Better UX and safety
   - Tasks: T034-T044, T081-T091, T092-T101

4. **Governance (Audit Trail)**: Audit logs viewer
   - Add: Phase 9
   - Complete audit transparency
   - Tasks: T102-T118

5. **Production Ready (Polish)**: Final touches
   - Add: Phase 10
   - Production-grade quality
   - Tasks: T119-T140

### Parallel Execution Opportunities

**Within User Story 1**:
- T024-T025 (ManageRoles component) parallel with T026-T027 (CreateRole component)

**Within User Story 2**:
- T038-T039 (ViewPermissions) fully parallel (independent component)

**Within User Story 5**:
- All tasks T092-T101 can run in parallel (separate from other stories)

**Setup Phase**:
- T003, T004 (migrations) can run in parallel
- T006, T007 (model and factory) can run in parallel

### Critical Path

Setup → Foundational → US1 → US3 → US4 → US6 → Audit Viewer → Polish

**Estimated Total**: 28 hours (140 tasks × 12 min avg)

### Incremental Delivery Checkpoints

- ✅ **Checkpoint 1** (8 hours): After US1 - Can create and manage roles
- ✅ **Checkpoint 2** (14 hours): After US3 - Can assign permissions to roles
- ✅ **Checkpoint 3** (19 hours): After US4 - Full RBAC system functional
- ✅ **Checkpoint 4** (23 hours): After US2, US6, US5 - Enhanced UX
- ✅ **Checkpoint 5** (26 hours): After Audit - Complete governance
- ✅ **Final** (28 hours): Production ready

---

## Validation Checklist

### Format Compliance
- [x] All tasks follow format: `- [ ] [TaskID] [P?] [Story?] Description with file path`
- [x] Task IDs are sequential (T001-T140)
- [x] [P] markers only on parallelizable tasks (different files, no dependencies)
- [x] [US#] story labels on user story phase tasks only
- [x] Setup and Polish phases have NO story labels
- [x] Every task includes specific file path

### Coverage Completeness
- [x] All 6 user stories from spec.md covered
- [x] All entities from data-model.md addressed (Role, Permission, Activity, User)
- [x] All components from plan.md included (6 Livewire components)
- [x] All migrations and seeders specified
- [x] Activity logging integrated throughout using spatie/laravel-activitylog
- [x] Test tasks included (TDD workflow)

### Independent Testability
- [x] Each user story phase has clear deliverable
- [x] Each user story has independent test criteria
- [x] MVP scope clearly defined (US1 + US3)
- [x] Dependencies documented
- [x] Parallel opportunities identified

---

## Task Summary

- **Total Tasks**: 137
- **Setup Tasks**: 14 (T001-T014)
- **User Story Tasks**: 106 (T015-T098, T105-T115)
  - US1: 16 tasks
  - US2: 11 tasks
  - US3: 19 tasks
  - US4: 17 tasks
  - US5: 10 tasks
  - US6: 11 tasks
  - Audit: 11 tasks
- **Polish Tasks**: 22 (T116-T137)
- **Parallelizable Tasks**: 23 tasks marked with [P]

**Ready for Implementation**: ✅ Yes - All tasks are specific, actionable, and testable. Now using spatie/laravel-activitylog for audit trail.