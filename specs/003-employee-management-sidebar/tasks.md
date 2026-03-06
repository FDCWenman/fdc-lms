# Tasks: Employee Management & Sidebar Navigation

**Branch**: `003-employee-management-sidebar`  
**Input**: Design documents from `/specs/003-employee-management-sidebar/`  
**Prerequisites**: plan.md ✅ | spec.md ✅ | research.md ✅ | data-model.md ✅ | contracts/ ✅

**Implementation Strategy**: Build MVP first (User Stories 1 & 3), then add status management (User Story 2). Each story is independently testable.

## Format: `- [ ] [ID] [P?] [Story?] Description`

- **[P]**: Can run in parallel (different files, no dependencies)
- **[US#]**: User Story number (US1, US2, US3)
- All paths relative to repository root
- Tests written FIRST (TDD approach), implementation follows

---

## Phase 1: Setup

**Purpose**: Initialize feature branch and verify prerequisites

- [X] T001 Verify branch `003-employee-management-sidebar` is checked out
- [X] T002 Verify permissions seeded in database: view-employees, manage-employees
- [X] T003 Verify FDC logo exists at public/images/fdc.png
- [X] T004 Run baseline test suite to confirm 41 tests passing from previous features

---

## Phase 2: Foundational (BLOCKS all user stories)

**Purpose**: Core structure that ALL user stories depend on

⚠️ **CRITICAL**: Complete this phase before starting ANY user story work

- [X] T005 Create Livewire component directory: app/Livewire/Employees/
- [X] T006 Create view directory: resources/views/livewire/employees/
- [X] T007 Create test directories: tests/Feature/, tests/Unit/Livewire/
- [X] T008 Add employee management route in routes/web.php with auth and permission middleware

**Checkpoint**: Foundation ready - User story implementation can now begin

---

## Phase 3: User Story 1 - View Employee Directory (Priority: P1) 🎯 MVP PART 1

**Goal**: Enable users with view-employees permission to view, search, and filter employees

**Independent Test**: Log in with view-employees permission, navigate to /employees, verify employee list displays with search/filter working

### Tests for User Story 1 (TDD - Write FIRST, ensure they FAIL)

- [X] T009 [P] [US1] Feature test: User with view-employees permission can access employee list in tests/Feature/EmployeeListTest.php
- [X] T010 [P] [US1] Feature test: User without view-employees permission receives 403 in tests/Feature/EmployeeListTest.php
- [X] T011 [P] [US1] Feature test: Search filters employees by name in tests/Feature/EmployeeListTest.php
- [X] T012 [P] [US1] Feature test: Search filters employees by email in tests/Feature/EmployeeListTest.php
- [X] T013 [P] [US1] Feature test: Status filter shows only matching employees in tests/Feature/EmployeeListTest.php
- [X] T014 [P] [US1] Feature test: Pagination shows 15 employees per page in tests/Feature/EmployeeListTest.php
- [X] T015 [P] [US1] Feature test: Employee list displays roles correctly in tests/Feature/EmployeeListTest.php
- [X] T016 [P] [US1] Feature test: Empty search shows appropriate message in tests/Feature/EmployeeListTest.php

**Run tests - Expected: 8 failures** → `php artisan test --filter=EmployeeListTest`

### Implementation for User Story 1

- [ ] T017 [US1] Create ManageEmployees Livewire component in app/Livewire/Employees/ManageEmployees.php with properties: $search, $statusFilter
- [ ] T018 [US1] Add computed property employees() with User query, eager load roles in app/Livewire/Employees/ManageEmployees.php
- [ ] T019 [US1] Implement search filtering (name/email) in employees() method in app/Livewire/Employees/ManageEmployees.php
- [ ] T020 [US1] Implement status filtering in employees() method in app/Livewire/Employees/ManageEmployees.php
- [ ] T021 [US1] Add pagination (15 per page) in employees() method in app/Livewire/Employees/ManageEmployees.php
- [ ] T022 [US1] Create Blade view with Flux UI card in resources/views/livewire/employees/manage-employees.blade.php
- [ ] T023 [US1] Add Flux search input (wire:model.live="search") in resources/views/livewire/employees/manage-employees.blade.php
- [ ] T024 [US1] Add Flux status filter dropdown in resources/views/livewire/employees/manage-employees.blade.php
- [ ] T025 [US1] Add Flux table displaying employees (name, email, status, roles) in resources/views/livewire/employees/manage-employees.blade.php
- [ ] T026 [US1] Add Flux badges for status display (Active/Deactivated/Pending) in resources/views/livewire/employees/manage-employees.blade.php
- [ ] T027 [US1] Add Flux avatars or initials for employees in resources/views/livewire/employees/manage-employees.blade.php
- [ ] T028 [US1] Add pagination controls in resources/views/livewire/employees/manage-employees.blade.php
- [ ] T029 [US1] Add empty state message when no employees match filters in resources/views/livewire/employees/manage-employees.blade.php
- [ ] T030 [US1] Add loading states (wire:loading) for search and filter in resources/views/livewire/employees/manage-employees.blade.php

**Run tests - Expected: 8 passes** → `php artisan test --filter=EmployeeListTest`

**Checkpoint**: User Story 1 complete - Employee viewing, search, and filter fully functional

---

## Phase 4: User Story 3 - Navigate Via Sidebar (Priority: P1) 🎯 MVP PART 2

**Goal**: Replace navbar with sidebar navigation showing FDC logo and permission-based menu items

**Independent Test**: Log in, verify sidebar appears with logo, click menu items, confirm navigation works and current page is highlighted

### Tests for User Story 3 (TDD - Write FIRST, ensure they FAIL)

- [ ] T031 [P] [US3] Browser test: Sidebar displays with FDC logo and branding in tests/Feature/SidebarNavigationTest.php
- [ ] T032 [P] [US3] Browser test: Dashboard link appears as first menu item in tests/Feature/SidebarNavigationTest.php
- [ ] T033 [P] [US3] Browser test: Employee Management link visible with view-employees permission in tests/Feature/SidebarNavigationTest.php
- [ ] T034 [P] [US3] Browser test: Administration section visible with role management permissions in tests/Feature/SidebarNavigationTest.php
- [ ] T035 [P] [US3] Browser test: Current page highlighted in sidebar navigation in tests/Feature/SidebarNavigationTest.php

**Run tests - Expected: 5 failures** → `php artisan test --filter=SidebarNavigationTest`

### Implementation for User Story 3

- [ ] T036 [US3] Replace navbar with Flux sidebar in resources/views/layouts/app.blade.php
- [ ] T037 [US3] Add Flux brand component with FDC logo and "FDC LMS" text in resources/views/layouts/app.blade.php
- [ ] T038 [US3] Add Flux navlist with Dashboard link (icon: home) in resources/views/layouts/app.blade.php
- [ ] T039 [US3] Add Employee Management link with @can('view-employees') directive in resources/views/layouts/app.blade.php
- [ ] T040 [US3] Create Administration navlist group (expandable) with @can directives in resources/views/layouts/app.blade.php
- [ ] T041 [US3] Add Roles link under Administration with @can('view-roles') in resources/views/layouts/app.blade.php
- [ ] T042 [US3] Add Permissions link under Administration with @can('view-roles') in resources/views/layouts/app.blade.php
- [ ] T043 [US3] Add Flux navlist.user component at bottom with logout option in resources/views/layouts/app.blade.php
- [ ] T044 [US3] Verify active page highlighting works (Flux handles this automatically) in resources/views/layouts/app.blade.php

**Run tests - Expected: 5 passes** → `php artisan test --filter=SidebarNavigationTest`

**Checkpoint**: User Story 3 complete - Sidebar navigation fully functional

**🎉 MVP COMPLETE**: At this point, users can view employees and navigate via sidebar

---

## Phase 5: User Story 2 - Manage Employee Status (Priority: P2)

**Goal**: Enable HR administrators to activate/deactivate employee accounts with optional reason and audit logging

**Independent Test**: Log in with manage-employees permission, open status change modal, provide reason, confirm status changes and audit log records it

### Tests for User Story 2 (TDD - Write FIRST, ensure they FAIL)

- [ ] T045 [P] [US2] Feature test: User with manage-employees can open status change modal in tests/Feature/EmployeeStatusTest.php
- [ ] T046 [P] [US2] Feature test: Status changes when confirmed with reason in tests/Feature/EmployeeStatusTest.php
- [ ] T047 [P] [US2] Feature test: Status changes when confirmed without reason in tests/Feature/EmployeeStatusTest.php
- [ ] T048 [P] [US2] Feature test: Status unchanged when modal cancelled in tests/Feature/EmployeeStatusTest.php
- [ ] T049 [P] [US2] Feature test: Cannot deactivate own account in tests/Feature/EmployeeStatusTest.php
- [ ] T050 [P] [US2] Feature test: Activity log records status change with reason in tests/Feature/EmployeeStatusTest.php
- [ ] T051 [P] [US2] Unit test: updateStatus method validates permissions in tests/Unit/Livewire/ManageEmployeesTest.php
- [ ] T052 [P] [US2] Unit test: updateStatus method prevents self-deactivation in tests/Unit/Livewire/ManageEmployeesTest.php

**Run tests - Expected: 8 failures** → `php artisan test --filter=EmployeeStatus`

### Implementation for User Story 2

- [ ] T053 [US2] Add properties: $showStatusModal, $selectedUser, $statusChangeReason in app/Livewire/Employees/ManageEmployees.php
- [ ] T054 [US2] Add openStatusModal(User $user) method with self-protection check in app/Livewire/Employees/ManageEmployees.php
- [ ] T055 [US2] Add confirmStatusChange() method with authorization check in app/Livewire/Employees/ManageEmployees.php
- [ ] T056 [US2] Implement status toggle logic (1↔2) in confirmStatusChange() in app/Livewire/Employees/ManageEmployees.php
- [ ] T057 [US2] Add Activity Log integration in confirmStatusChange() with properties: old_status, new_status, reason in app/Livewire/Employees/ManageEmployees.php
- [ ] T058 [US2] Add success message after status change in app/Livewire/Employees/ManageEmployees.php
- [ ] T059 [US2] Add status toggle button column with @can('manage-employees') in resources/views/livewire/employees/manage-employees.blade.php
- [ ] T060 [US2] Create Flux modal (wire:model="showStatusModal") in resources/views/livewire/employees/manage-employees.blade.php
- [ ] T061 [US2] Add Flux textarea for optional reason field in status modal in resources/views/livewire/employees/manage-employees.blade.php
- [ ] T062 [US2] Add modal confirm and cancel buttons in resources/views/livewire/employees/manage-employees.blade.php
- [ ] T063 [US2] Add wire:loading state to confirm button in resources/views/livewire/employees/manage-employees.blade.php

**Run tests - Expected: 8 passes** → `php artisan test --filter=EmployeeStatus`

**Checkpoint**: User Story 2 complete - Status management with audit trail fully functional

**🎉 ALL USER STORIES COMPLETE**: Full feature set delivered

---

## Phase 6: Polish & Cross-Cutting Concerns

**Purpose**: Final refinements affecting multiple user stories

- [ ] T064 [P] Run Laravel Pint code formatter: vendor/bin/pint --format agent
- [ ] T065 [P] Verify no N+1 queries using Laravel Debugbar on /employees page
- [ ] T066 [P] Test with 1000 employees to verify <2 second load time
- [ ] T067 [P] Verify mobile responsiveness (sidebar collapse, table scroll)
- [ ] T068 [P] Check accessibility: keyboard navigation, screen reader labels
- [ ] T069 Run full test suite - Expected: 64 tests passing (41 existing + 23 new)
- [ ] T070 Update CHANGELOG.md with feature summary
- [ ] T071 Validate quickstart.md instructions work for new developer
- [ ] T072 Create pull request with implementation summary

---

## Dependencies & Execution Order

### Phase Dependencies

```
Phase 1 (Setup)
    ↓
Phase 2 (Foundational) ← BLOCKS all user stories
    ↓
    ├─→ Phase 3 (US1 - View Directory) ─┐
    ├─→ Phase 4 (US3 - Sidebar Nav)     ├─→ MVP Complete
    └─→ Phase 5 (US2 - Status Mgmt)     ─┘
                ↓
Phase 6 (Polish) ← Depends on desired stories complete
```

### User Story Dependencies

- **US1 (View Directory)**: No dependencies on other stories - Can start after Phase 2
- **US3 (Sidebar Navigation)**: No dependencies on other stories - Can start after Phase 2
- **US2 (Status Management)**: REQUIRES US1 complete (needs employee list context) - Can start after Phase 3

### Recommended Execution Order

**Single Developer**:
1. Phase 1 → Phase 2 (sequential)
2. Phase 3 (US1) → Phase 4 (US3) = MVP ✅
3. Phase 5 (US2) = Full feature
4. Phase 6 (Polish)

**Team of 2**:
1. Phase 1 → Phase 2 (sequential)
2. **Parallel**: Developer A → Phase 3 (US1) | Developer B → Phase 4 (US3)
3. Developer A → Phase 5 (US2) while Developer B starts Phase 6
4. Both → Phase 6 completion

### Parallel Opportunities

**Within Phase 2**: All test files can be created in parallel (T009-T016, T031-T035, T045-T052)

**Within Phase 3 (US1)**: 
- All 8 tests (T009-T016) can run in parallel
- Blade view tasks (T022-T030) can be worked on while component logic (T017-T021) is in progress

**Within Phase 4 (US3)**:
- All 5 tests (T031-T035) can run in parallel
- Layout tasks (T036-T044) are sequential (same file)

**Within Phase 5 (US2)**:
- All 8 tests (T045-T052) can run in parallel
- Component and view tasks can overlap

**Phase 6**: Most polish tasks (T064-T068, T071) can run in parallel

---

## Parallel Example: User Story 1 Implementation

```bash
# Terminal 1: Write tests (TDD)
php artisan make:test Feature/EmployeeListTest
# Implement T009-T016
php artisan test --filter=EmployeeListTest  # Should see 8 failures

# Terminal 2: Create Livewire component
php artisan make:livewire Employees/ManageEmployees
# Implement T017-T021 (component logic)

# Terminal 3: Create Blade view
# Implement T022-T030 (UI with Flux components)

# After implementation complete
php artisan test --filter=EmployeeListTest  # Should see 8 passes
```

---

## Testing Strategy

### Test Execution Phases

1. **Pre-Implementation**: Write all tests, verify they FAIL
2. **During Implementation**: Run specific test suite frequently
3. **Post-Implementation**: Run full test suite to ensure no regressions

### Test Commands

```bash
# Run specific user story tests
php artisan test --filter=EmployeeListTest      # US1
php artisan test --filter=SidebarNavigationTest # US3
php artisan test --filter=EmployeeStatus        # US2

# Run all new tests
php artisan test tests/Feature/EmployeeListTest.php
php artisan test tests/Feature/EmployeeStatusTest.php
php artisan test tests/Feature/SidebarNavigationTest.php
php artisan test tests/Unit/Livewire/ManageEmployeesTest.php

# Run full suite
php artisan test --compact

# Run with coverage
php artisan test --coverage
```

### Expected Test Count

| Phase | New Tests | Cumulative |
|-------|-----------|------------|
| Baseline | 0 | 41 |
| Phase 3 (US1) | +8 | 49 |
| Phase 4 (US3) | +5 | 54 |
| Phase 5 (US2) | +8 | 62 |
| Phase 6 (Polish) | +2 (if added) | 64 |

---

## Implementation Strategy Summary

### MVP Approach (Phases 1-4)

**Deliverable**: Employee viewing with search/filter + Sidebar navigation  
**Time Estimate**: 6-7 hours  
**Value**: Core functionality for 90% of users  
**User Stories**: US1 (P1) + US3 (P1)

### Full Feature (Add Phase 5)

**Deliverable**: + Status management with audit trail  
**Time Estimate**: +3-4 hours  
**Value**: Administrative capabilities for HR staff  
**User Stories**: US1 (P1) + US3 (P1) + US2 (P2)

### Recommended Approach

✅ **Build MVP first** (Phases 1-4), deploy to staging, gather feedback, then add Phase 5 (US2)

This allows:
- Early user testing and feedback
- Risk reduction (MVP is simpler)
- Value delivery sooner
- Ability to pause if priorities change

---

## Task Summary

- **Total Tasks**: 72
- **Setup**: 4 tasks
- **Foundational**: 4 tasks (blocks all stories)
- **User Story 1 (P1)**: 22 tasks (8 tests + 14 implementation)
- **User Story 3 (P1)**: 14 tasks (5 tests + 9 implementation)
- **User Story 2 (P2)**: 19 tasks (8 tests + 11 implementation)
- **Polish**: 9 tasks

**Parallelizable Tasks**: 38 marked with [P]  
**Sequential Tasks**: 34 (due to dependencies)

---

**Ready to Start**: ✅ All prerequisites met, tasks defined, execution order clear

**Next Action**: Begin Phase 1 (Setup) → T001
