# Implementation Tasks: Authentication & Registration System Revamp

**Branch**: `001-auth-revamp`  
**Date**: 2026-03-04  
**Spec**: [spec.md](./spec.md) | **Plan**: [plan.md](./plan.md)

## Task Overview

**Total User Stories**: 7 (P1: 3, P2: 2, P3: 2)  
**Total Tasks**: 45  
**Estimated Effort**: ~3-4 weeks (1 developer)

---

## Phase 0: Setup & Configuration (2-3 days)

### Task 0.1: Environment Configuration ✅ DONE
**Priority**: P1 | **Effort**: 30 min | **Dependencies**: None

- [x] Add Slack API configuration to `config/services.php`
- [x] Update `.env.example` with Slack variables
- [x] Update `APP_NAME` to "FDCLeave" in config
- [x] Create 40°C logo components

**Acceptance**: 
- Slack configuration available in services config
- .env.example documents all required Slack variables

---

### Task 0.2: Install Dependencies
**Priority**: P1 | **Effort**: 1 hour | **Dependencies**: Task 0.1

- [ ] Install Spatie Laravel Permission: `composer require spatie/laravel-permission`
- [ ] Install Slack PHP SDK: `composer require slack/slack-php-api`
- [ ] Publish Spatie config: `php artisan vendor:publish --provider="Spatie\Permission\PermissionServiceProvider"`
- [ ] Run Spatie migrations: `php artisan migrate`

**Acceptance**:
- All composer packages installed successfully
- Spatie migrations run without errors
- Config files published

---

### Task 0.3: Database Schema Design
**Priority**: P1 | **Effort**: 2 hours | **Dependencies**: Task 0.2

- [ ] Create migration: `users` table enhancements (add columns: `primary_role_id`, `secondary_role_id`, `slack_id`, `default_approvers`, `status`, `verified_at`, `hired_date`)
- [ ] Create migration: `password_reset_tokens` table (token, user_id, ip_address, used, expires_at)
- [ ] Create migration: `email_verification_tokens` table (token, user_id, expires_at)
- [ ] Create migration: `account_audit_logs` table (user_id, action, performed_by, ip_address, reason)
- [ ] Create migration: `failed_login_attempts` table (email, ip_address, attempted_at, locked_until)
- [ ] Update User model with new columns and relationships

**Acceptance**:
- All migrations created and documented
- Migrations follow naming conventions
- Foreign keys and indexes defined

---

## Phase 1: Core Authentication (Priority P1 - 5-7 days)

### Task 1.1: User Model & Roles Setup
**Priority**: P1 | **Effort**: 3 hours | **Dependencies**: Task 0.3

**User Story**: US1 Employee Self-Service Authentication, US3 Role-Based Access Control

- [ ] Update `User` model to use Spatie `HasRoles` trait
- [ ] Add `primary_role_id` and `secondary_role_id` relationships
- [ ] Add `slack_id`, `default_approvers` (cast to JSON), `status`, `verified_at` attributes
- [ ] Create `Role` seeder with 4 roles (Employee=1, HR=2, Lead=3, PM=4)
- [ ] Create `Permission` seeder with 8 permissions
- [ ] Attach permissions to roles according to RBAC diagram

**Acceptance**:
- User model has role relationships
- Seeders create all roles and permissions
- Roles have correct permissions attached

**Files**:
- `app/Models/User.php`
- `database/seeders/RoleSeeder.php`
- `database/seeders/PermissionSeeder.php`

---

### Task 1.2: Login Action & Controller
**Priority**: P1 | **Effort**: 4 hours | **Dependencies**: Task 1.1

**User Story**: US1 Employee Self-Service Authentication

- [ ] Create `AuthenticateUser` action class
- [ ] Implement credential validation (email + password)
- [ ] Check account status (active, verified, not locked)
- [ ] Track failed login attempts (increment counter)
- [ ] Implement account lockout logic (5 attempts = 30min lock)
- [ ] Create session token via Sanctum
- [ ] Reset failed attempts on successful login
- [ ] Create `LoginController` (thin controller, delegates to action)
- [ ] Create `LoginRequest` validation class

**Acceptance**:
- Valid credentials → successful login
- Unverified account → 403 with message
- Deactivated account → 403 with message
- Locked account → 423 with lockout time
- Invalid credentials → 401 (generic message, no email disclosure)
- Failed attempts tracked correctly

**Files**:
- `app/Actions/Auth/AuthenticateUser.php`
- `app/Http/Controllers/Auth/LoginController.php`
- `app/Http/Requests/Auth/LoginRequest.php`

---

### Task 1.3: Logout Action
**Priority**: P1 | **Effort**: 1 hour | **Dependencies**: Task 1.2

**User Story**: US1 Employee Self-Service Authentication

- [ ] Create `LogoutUser` action class
- [ ] Delete current session token
- [ ] Return success response
- [ ] Add logout route to `web.php`

**Acceptance**:
- Logout invalidates current session
- User cannot access authenticated routes after logout

**Files**:
- `app/Actions/Auth/LogoutUser.php`
- `app/Http/Controllers/Auth/LoginController.php` (add logout method)

---

### Task 1.4: Login UI (Vue Component)
**Priority**: P1 | **Effort**: 3 hours | **Dependencies**: Task 1.3

**User Story**: US1 Employee Self-Service Authentication

- [ ] Create `Login.vue` page component
- [ ] Create `LoginForm.vue` component
- [ ] Create `AccountLockoutAlert.vue` component
- [ ] Implement form validation (email required, password required)
- [ ] Show appropriate error messages for each scenario
- [ ] Show lockout countdown timer for locked accounts
- [ ] Redirect to role-appropriate page on success

**Acceptance**:
- Form validates inputs client-side
- Displays server error messages correctly
- Shows lockout timer when account is locked
- Redirects based on user role after login

**Files**:
- `resources/js/pages/Auth/Login.vue`
- `resources/js/components/Auth/LoginForm.vue`
- `resources/js/components/Auth/AccountLockoutAlert.vue`

---

### Task 1.5: Session Management Middleware
**Priority**: P1 | **Effort**: 2 hours | **Dependencies**: Task 1.2

**User Story**: US1 Employee Self-Service Authentication

- [ ] Update Sanctum config for session expiration (8hr inactivity, 24hr absolute)
- [ ] Create `CheckAccountStatus` middleware
- [ ] Check if user account is still active
- [ ] Check if user account is still verified
- [ ] Redirect to login if account status changed
- [ ] Apply middleware to all authenticated routes

**Acceptance**:
- Sessions expire after 8 hours of inactivity
- Sessions expire after 24 hours absolute
- Deactivated users are logged out immediately
- Middleware blocks access for inactive accounts

**Files**:
- `config/sanctum.php`
- `app/Http/Middleware/CheckAccountStatus.php`

---

### Task 1.6: Email Verification Token Service
**Priority**: P1 | **Effort**: 3 hours | **Dependencies**: Task 0.3

**User Story**: US2 Email Verification for New Accounts

- [ ] Create `EmailVerificationToken` model
- [ ] Create `TokenService` for token generation/validation
- [ ] Implement `generateVerificationToken()` (48hr expiry)
- [ ] Implement `validateVerificationToken()`
- [ ] Implement token expiration check
- [ ] Implement token deletion after use

**Acceptance**:
- Tokens are unique and securely generated
- Tokens expire after 48 hours
- Used tokens cannot be reused
- Expired tokens return appropriate error

**Files**:
- `app/Models/EmailVerificationToken.php`
- `app/Services/TokenService.php`

---

### Task 1.7: Email Verification Action & Controller
**Priority**: P1 | **Effort**: 3 hours | **Dependencies**: Task 1.6

**User Story**: US2 Email Verification for New Accounts

- [ ] Create `VerifyEmail` action class
- [ ] Validate token via `TokenService`
- [ ] Mark user as verified (`verified_at` timestamp)
- [ ] Delete verification token
- [ ] Create `VerificationController`
- [ ] Implement verification route (GET `/verify-email/{token}`)
- [ ] Implement resend verification route (POST `/verification/resend`)

**Acceptance**:
- Valid token → user verified, redirected to login
- Expired token → error message, option to resend
- Already verified → message, redirect to login
- Resend generates new token and sends email

**Files**:
- `app/Actions/Auth/VerifyEmail.php`
- `app/Http/Controllers/Auth/VerificationController.php`

---

### Task 1.8: Email Verification UI
**Priority**: P1 | **Effort**: 2 hours | **Dependencies**: Task 1.7

**User Story**: US2 Email Verification for New Accounts

- [ ] Create `VerifyEmail.vue` page
- [ ] Show verification status (success/expired/error)
- [ ] Show "Resend" button for expired tokens
- [ ] Show login link after successful verification

**Acceptance**:
- Successful verification shows success message
- Expired token shows error and resend button
- Resend works and shows confirmation

**Files**:
- `resources/js/pages/Auth/VerifyEmail.vue`

---

### Task 1.9: Role-Based Redirect Middleware
**Priority**: P1 | **Effort**: 2 hours | **Dependencies**: Task 1.1

**User Story**: US3 Role-Based Access Control

- [ ] Create `RedirectByRole` middleware
- [ ] Employees → `/leaves` (leave dashboard)
- [ ] HR/Lead/PM Approvers → `/portal` (calendar portal)
- [ ] Apply middleware to `/` (home) route

**Acceptance**:
- Employees land on leave dashboard after login
- Approvers land on portal calendar after login
- Redirect logic respects both primary and secondary roles

**Files**:
- `app/Http/Middleware/RedirectByRole.php`

---

### Task 1.10: Permission Middleware & Policies
**Priority**: P1 | **Effort**: 4 hours | **Dependencies**: Task 1.1

**User Story**: US3 Role-Based Access Control

- [ ] Create route middleware using Spatie `role` and `permission` middleware
- [ ] Create `UserPolicy` for account management checks
- [ ] Implement `viewAny()` (HR only)
- [ ] Implement `create()` (HR only)
- [ ] Implement `update()` (HR only)
- [ ] Implement `deactivate()` (HR only, not self)
- [ ] Apply permission middleware to routes

**Acceptance**:
- Employees cannot access HR/approval routes
- HR can manage all accounts except their own deactivation
- Approvers can access their specific approval queues
- Permission checks work for multi-role users

**Files**:
- `app/Policies/UserPolicy.php`
- `routes/web.php` (middleware applied)

---

### Task 1.11: Login Feature Tests
**Priority**: P1 | **Effort**: 4 hours | **Dependencies**: Task 1.4

**User Story**: US1 Employee Self-Service Authentication

- [ ] Test successful login with valid credentials
- [ ] Test login rejection for unverified account
- [ ] Test login rejection for deactivated account
- [ ] Test account lockout after 5 failed attempts
- [ ] Test generic error message (no email disclosure)
- [ ] Test session token creation
- [ ] Test role-based redirect after login

**Acceptance**:
- All test scenarios pass
- 100% coverage of authentication logic

**Files**:
- `tests/Feature/Auth/LoginTest.php`

---

### Task 1.12: Email Verification Feature Tests
**Priority**: P1 | **Effort**: 3 hours | **Dependencies**: Task 1.8

**User Story**: US2 Email Verification for New Accounts

- [ ] Test verification with valid token
- [ ] Test verification with expired token
- [ ] Test verification with used token
- [ ] Test verification when already verified
- [ ] Test resend verification email
- [ ] Test token expiration (48 hours)

**Acceptance**:
- All verification scenarios tested
- Token lifecycle fully covered

**Files**:
- `tests/Feature/Auth/EmailVerificationTest.php`

---

## Phase 2: Password Management (Priority P1 - 2-3 days)

### Task 2.1: Slack Service Integration
**Priority**: P1 | **Effort**: 4 hours | **Dependencies**: Task 0.2

**User Story**: US1 Employee Self-Service Authentication

- [ ] Create `SlackService` class
- [ ] Implement `sendDirectMessage($userId, $message)` using Slack API
- [ ] Implement `validateSlackId($slackId)` using `users.info`
- [ ] Implement error handling and timeout logic
- [ ] Add graceful degradation when Slack unavailable

**Acceptance**:
- Can send DM to Slack user
- Can validate Slack ID exists
- Handles API errors gracefully
- Respects timeout configuration

**Files**:
- `app/Services/SlackService.php`

---

### Task 2.2: Password Reset Request Action
**Priority**: P1 | **Effort**: 3 hours | **Dependencies**: Task 2.1

**User Story**: US1 Employee Self-Service Authentication

- [ ] Create `PasswordResetToken` model
- [ ] Create `RequestPasswordReset` action
- [ ] Find user by email
- [ ] Generate reset token (1hr expiry)
- [ ] Store token with IP address
- [ ] Send reset link via Slack DM using `SlackService`
- [ ] Return generic success message (don't reveal if email exists)

**Acceptance**:
- Valid email → token generated, Slack DM sent
- Invalid email → generic success message (no disclosure)
- Reset link contains valid token
- IP address logged

**Files**:
- `app/Models/PasswordResetToken.php`
- `app/Actions/Auth/RequestPasswordReset.php`

---

### Task 2.3: Password Reset Complete Action
**Priority**: P1 | **Effort**: 3 hours | **Dependencies**: Task 2.2

**User Story**: US1 Employee Self-Service Authentication

- [ ] Create `ResetPassword` action
- [ ] Validate token (exists, not expired, not used)
- [ ] Validate new password (min 8 chars, complexity)
- [ ] Update user password (bcrypt hash)
- [ ] Mark token as used
- [ ] Invalidate all other user sessions (except current)
- [ ] Create `PasswordResetController`

**Acceptance**:
- Valid token + valid password → password updated
- Expired token → error message
- Used token → error message
- All other sessions invalidated
- New password meets complexity requirements

**Files**:
- `app/Actions/Auth/ResetPassword.php`
- `app/Http/Controllers/Auth/PasswordResetController.php`

---

### Task 2.4: Password Reset UI
**Priority**: P1 | **Effort**: 3 hours | **Dependencies**: Task 2.3

**User Story**: US1 Employee Self-Service Authentication

- [ ] Create `ForgotPassword.vue` page
- [ ] Create `PasswordResetForm.vue` component
- [ ] Create `ResetPassword.vue` page
- [ ] Implement email input form
- [ ] Implement new password form with confirmation
- [ ] Show password strength indicator
- [ ] Handle token validation errors

**Acceptance**:
- Forgot password form submits successfully
- Reset password form validates inputs
- Password strength shown to user
- Expired/invalid token shows appropriate error

**Files**:
- `resources/js/pages/Auth/ForgotPassword.vue`
- `resources/js/pages/Auth/ResetPassword.vue`
- `resources/js/components/Auth/PasswordResetForm.vue`

---

### Task 2.5: Password Reset Feature Tests
**Priority**: P1 | **Effort**: 3 hours | **Dependencies**: Task 2.4

**User Story**: US1 Employee Self-Service Authentication

- [ ] Test password reset request for valid email
- [ ] Test password reset request for invalid email (no disclosure)
- [ ] Test Slack DM is sent
- [ ] Test reset with valid token
- [ ] Test reset with expired token
- [ ] Test reset with used token
- [ ] Test password complexity validation
- [ ] Test other sessions invalidated

**Acceptance**:
- All password reset scenarios tested
- Slack integration mocked/tested

**Files**:
- `tests/Feature/Auth/PasswordResetTest.php`

---

## Phase 3: Account Management (Priority P2 - 3-4 days)

### Task 3.1: Account Creation Action
**Priority**: P2 | **Effort**: 4 hours | **Dependencies**: Task 2.1

**User Story**: US4 HR Account Management

- [ ] Create `CreateEmployeeAccount` action
- [ ] Validate Slack ID via `SlackService`
- [ ] Validate email uniqueness
- [ ] Create user with status = "for_verification" (2)
- [ ] Generate verification token (48hr)
- [ ] Send verification email
- [ ] Add user to Slack leave channel via API
- [ ] Return created user

**Acceptance**:
- Account created with for_verification status
- Slack ID validated before creation
- Verification email sent
- User added to Slack channel
- Handles Slack API failures gracefully

**Files**:
- `app/Actions/Account/CreateEmployeeAccount.php`

---

### Task 3.2: Account Activation/Deactivation Actions
**Priority**: P2 | **Effort**: 4 hours | **Dependencies**: Task 3.1

**User Story**: US4 HR Account Management

- [ ] Create `AccountAuditLog` model
- [ ] Create `AuditLogService` for logging
- [ ] Create `ActivateAccount` action
- [ ] Create `DeactivateAccount` action
- [ ] Update user status (active=1, deactivated=0)
- [ ] Log action to `AccountAuditLog` (who, what, when, why, IP)
- [ ] Prevent HR from deactivating their own account
- [ ] Invalidate all user sessions on deactivation

**Acceptance**:
- Activation changes status to active
- Deactivation changes status to deactivated
- All status changes logged with full audit trail
- HR cannot deactivate themselves
- Deactivated users logged out immediately

**Files**:
- `app/Models/AccountAuditLog.php`
- `app/Services/AuditLogService.php`
- `app/Actions/Account/ActivateAccount.php`
- `app/Actions/Account/DeactivateAccount.php`

---

### Task 3.3: Account Management Controller
**Priority**: P2 | **Effort**: 3 hours | **Dependencies**: Task 3.2

**User Story**: US4 HR Account Management

- [ ] Create `AccountController` in Admin namespace
- [ ] Implement `index()` - list all employees
- [ ] Implement `create()` - show create form
- [ ] Implement `store()` - create account (delegates to action)
- [ ] Implement `show()` - view account details + audit log
- [ ] Implement `edit()` - show edit form
- [ ] Implement `update()` - update account
- [ ] Implement `activate()` - activate account
- [ ] Implement `deactivate()` - deactivate account
- [ ] Apply `UserPolicy` authorization

**Acceptance**:
- All CRUD operations work
- Authorization enforced via policy
- Audit log visible on account detail page

**Files**:
- `app/Http/Controllers/Admin/AccountController.php`
- `app/Http/Requests/Account/CreateAccountRequest.php`
- `app/Http/Requests/Account/DeactivateAccountRequest.php`

---

### Task 3.4: Account Management UI
**Priority**: P2 | **Effort**: 5 hours | **Dependencies**: Task 3.3

**User Story**: US4 HR Account Management

- [ ] Create `Admin/Accounts/Index.vue` - list employees
- [ ] Create `Admin/Accounts/Create.vue` - create form
- [ ] Create `Admin/Accounts/Show.vue` - details + audit log
- [ ] Create `Admin/Accounts/Edit.vue` - edit form
- [ ] Create `AccountForm.vue` component
- [ ] Create `AccountStatusBadge.vue` component
- [ ] Create `RoleSelector.vue` component
- [ ] Create `AuditLogTable.vue` component
- [ ] Implement Slack ID validation on blur

**Acceptance**:
- Can list all employees with filters
- Can create new employee account
- Can view account details and audit history
- Can activate/deactivate accounts with reason
- Slack ID validated in real-time

**Files**:
- `resources/js/pages/Admin/Accounts/Index.vue`
- `resources/js/pages/Admin/Accounts/Create.vue`
- `resources/js/pages/Admin/Accounts/Show.vue`
- `resources/js/pages/Admin/Accounts/Edit.vue`
- `resources/js/components/Admin/AccountForm.vue`
- `resources/js/components/Admin/AccountStatusBadge.vue`
- `resources/js/components/Admin/RoleSelector.vue`
- `resources/js/components/Admin/AuditLogTable.vue`

---

### Task 3.5: Account Management Feature Tests
**Priority**: P2 | **Effort**: 4 hours | **Dependencies**: Task 3.4

**User Story**: US4 HR Account Management

- [ ] Test HR can create account
- [ ] Test Slack ID validation during creation
- [ ] Test verification email sent
- [ ] Test user added to Slack channel
- [ ] Test HR can activate account
- [ ] Test HR can deactivate account with reason
- [ ] Test audit log created for all actions
- [ ] Test HR cannot deactivate self
- [ ] Test only HR can access account management

**Acceptance**:
- All account management operations tested
- Authorization properly enforced
- Audit trail verified

**Files**:
- `tests/Feature/Account/CreateAccountTest.php`
- `tests/Feature/Account/ActivateAccountTest.php`
- `tests/Feature/Account/DeactivateAccountTest.php`
- `tests/Feature/Account/AuditLogTest.php`

---

## Phase 4: Profile Management (Priority P2 - 2 days)

### Task 4.1: Default Approvers Management
**Priority**: P2 | **Effort**: 3 hours | **Dependencies**: Task 3.1

**User Story**: US5 Profile Management & Default Approvers

- [ ] Create `UpdateDefaultApprovers` action
- [ ] Validate approvers have appropriate roles
- [ ] HR approver must have HR role (2)
- [ ] Team Lead approver must have Lead role (3)
- [ ] PM approver must have PM role (4)
- [ ] Store as JSON in `default_approvers` column
- [ ] Create `ProfileController`

**Acceptance**:
- Can select and save default approvers
- Validation ensures approvers have correct roles
- Stored as structured JSON data
- Multi-role users appear in multiple selector lists

**Files**:
- `app/Actions/Profile/UpdateDefaultApprovers.php`
- `app/Http/Controllers/ProfileController.php`

---

### Task 4.2: Change Password Action
**Priority**: P2 | **Effort**: 2 hours | **Dependencies**: Task 4.1

**User Story**: US5 Profile Management & Default Approvers

- [ ] Create `ChangePassword` action
- [ ] Verify current password
- [ ] Validate new password (complexity requirements)
- [ ] Update password hash
- [ ] Invalidate all other sessions (keep current)
- [ ] Add route to `ProfileController`

**Acceptance**:
- Requires current password verification
- New password meets complexity requirements
- Other sessions invalidated
- Current session remains active

**Files**:
- `app/Actions/Profile/ChangePassword.php`

---

### Task 4.3: Slack Display Name Sync
**Priority**: P2 | **Effort**: 2 hours | **Dependencies**: Task 2.1

**User Story**: US5 Profile Management & Default Approvers

- [ ] Create `RefreshSlackName` action
- [ ] Call Slack API `users.profile.get` via `SlackService`
- [ ] Update local user record with current Slack name
- [ ] Handle API failures gracefully
- [ ] Add route to `ProfileController`

**Acceptance**:
- Fetches current Slack display name
- Updates local database
- Shows success/error message to user

**Files**:
- `app/Actions/Profile/RefreshSlackName.php`
- `app/Services/SlackService.php` (add method)

---

### Task 4.4: Profile Management UI
**Priority**: P2 | **Effort**: 4 hours | **Dependencies**: Task 4.3

**User Story**: US5 Profile Management & Default Approvers

- [ ] Create `Profile/Edit.vue` page
- [ ] Create `Profile/ChangePassword.vue` component
- [ ] Create `Profile/DefaultApprovers.vue` component
- [ ] Display user information (read-only)
- [ ] Show current default approvers
- [ ] Approver selectors filtered by role
- [ ] Password change form with current password
- [ ] Slack name refresh button

**Acceptance**:
- Can view profile information
- Can update default approvers
- Can change password
- Can refresh Slack display name
- Form validation works correctly

**Files**:
- `resources/js/pages/Profile/Edit.vue`
- `resources/js/pages/Profile/ChangePassword.vue`
- `resources/js/pages/Profile/DefaultApprovers.vue`

---

### Task 4.5: Profile Feature Tests
**Priority**: P2 | **Effort**: 3 hours | **Dependencies**: Task 4.4

**User Story**: US5 Profile Management & Default Approvers

- [ ] Test update default approvers
- [ ] Test approver role validation
- [ ] Test change password with correct current password
- [ ] Test change password with incorrect current password
- [ ] Test password complexity validation
- [ ] Test Slack display name refresh
- [ ] Test other sessions invalidated on password change

**Acceptance**:
- All profile operations tested
- Validation rules enforced

**Files**:
- `tests/Feature/Profile/UpdateApproversTest.php`
- `tests/Feature/Profile/ChangePasswordTest.php`
- `tests/Feature/Profile/RefreshSlackNameTest.php`

---

## Phase 5: Multi-Role Support (Priority P3 - 1-2 days)

### Task 5.1: Multi-Role Permission Logic
**Priority**: P3 | **Effort**: 3 hours | **Dependencies**: Task 1.10

**User Story**: US6 Multi-Role Support

- [ ] Update `User` model to check both primary and secondary roles
- [ ] Create helper method `hasAnyRole()` that checks both
- [ ] Update permission checks to include secondary role
- [ ] Update middleware to handle multi-role scenarios
- [ ] Update policy checks for multi-role users

**Acceptance**:
- Users with secondary roles have combined permissions
- Can access features from both roles
- Permission middleware works correctly
- No duplicate permission checks

**Files**:
- `app/Models/User.php`
- `app/Policies/UserPolicy.php`

---

### Task 5.2: Multi-Role Assignment UI
**Priority**: P3 | **Effort**: 2 hours | **Dependencies**: Task 3.4

**User Story**: US6 Multi-Role Support

- [ ] Update `AccountForm.vue` to include secondary role selector
- [ ] Update `CreateAccountRequest` to accept secondary role
- [ ] Update `CreateEmployeeAccount` action to save secondary role
- [ ] Show both roles in account listing

**Acceptance**:
- Can assign secondary role during account creation
- Can update secondary role
- Both roles displayed in UI
- Removing secondary role works correctly

**Files**:
- `resources/js/components/Admin/AccountForm.vue`
- `app/Actions/Account/CreateEmployeeAccount.php`

---

### Task 5.3: Multi-Role Feature Tests
**Priority**: P3 | **Effort**: 3 hours | **Dependencies**: Task 5.2

**User Story**: US6 Multi-Role Support

- [ ] Test user with primary + secondary role has combined permissions
- [ ] Test permission checks work for both roles
- [ ] Test user can access features from both roles
- [ ] Test secondary role removal reverts to primary only
- [ ] Test middleware handles multi-role correctly

**Acceptance**:
- All multi-role scenarios tested
- Permission logic verified

**Files**:
- `tests/Feature/Permissions/MultiRoleTest.php`

---

## Phase 6: Advanced Slack Integration (Priority P3 - 2 days)

### Task 6.1: Extended Slack Service Methods
**Priority**: P3 | **Effort**: 3 hours | **Dependencies**: Task 2.1

**User Story**: US7 Slack Integration for User Management

- [ ] Implement `addToChannel($userId, $channelId)` using `admin.conversations.invite`
- [ ] Implement `getDisplayName($userId)` using `users.profile.get`
- [ ] Implement `updateDisplayName($userId, $name)` using `users.profile.set`
- [ ] Add comprehensive error handling
- [ ] Add logging for all Slack API calls

**Acceptance**:
- Can add users to Slack channels
- Can fetch user display names
- Can update display names (for future leave indicator feature)
- All errors handled gracefully

**Files**:
- `app/Services/SlackService.php`

---

### Task 6.2: Slack Integration Feature Tests
**Priority**: P3 | **Effort**: 3 hours | **Dependencies**: Task 6.1

**User Story**: US7 Slack Integration for User Management

- [ ] Test Slack ID validation
- [ ] Test adding user to channel
- [ ] Test fetching display name
- [ ] Test Slack API error handling
- [ ] Test graceful degradation when Slack unavailable
- [ ] Mock Slack API responses

**Acceptance**:
- All Slack operations tested
- API mocked correctly
- Error scenarios covered

**Files**:
- `tests/Unit/Services/SlackServiceTest.php`

---

## Phase 7: Testing & Polish (3-4 days)

### Task 7.1: Comprehensive Feature Tests
**Priority**: P1 | **Effort**: 6 hours | **Dependencies**: All feature tasks

- [ ] Write end-to-end authentication flow test
- [ ] Write end-to-end account creation flow test
- [ ] Test all user stories' acceptance scenarios
- [ ] Test all edge cases identified in spec
- [ ] Verify constitution compliance (audit logs, permissions)

**Acceptance**:
- All user stories have passing tests
- All edge cases covered
- Test coverage > 80% for business logic

**Files**:
- `tests/Feature/Auth/*Test.php`
- `tests/Feature/Account/*Test.php`
- `tests/Feature/Profile/*Test.php`

---

### Task 7.2: Browser Tests (Dusk)
**Priority**: P2 | **Effort**: 6 hours | **Dependencies**: Task 7.1

- [ ] Install Laravel Dusk
- [ ] Create login flow browser test
- [ ] Create account lockout browser test
- [ ] Create email verification browser test
- [ ] Create account management browser test
- [ ] Test all critical user journeys

**Acceptance**:
- All critical flows tested in browser
- Tests pass in headless mode

**Files**:
- `tests/Browser/Auth/LoginFlowTest.php`
- `tests/Browser/Auth/AccountManagementTest.php`

---

### Task 7.3: Performance Optimization
**Priority**: P2 | **Effort**: 4 hours | **Dependencies**: Task 7.1

- [ ] Add database indexes (email, slack_id, status, role_id)
- [ ] Optimize permission checks (eager loading)
- [ ] Cache role/permission lookups
- [ ] Add API rate limiting
- [ ] Test login performance (<200ms)

**Acceptance**:
- Login completes in <5 seconds (SC-001)
- Permission checks <100ms
- Database queries optimized

**Files**:
- Database migrations (add indexes)
- `app/Models/User.php` (eager loading)

---

### Task 7.4: Documentation
**Priority**: P2 | **Effort**: 4 hours | **Dependencies**: All tasks

- [ ] Create API documentation for auth endpoints
- [ ] Document Slack setup process
- [ ] Create developer quickstart guide
- [ ] Document role/permission structure
- [ ] Update README with setup instructions

**Acceptance**:
- All endpoints documented
- Setup guide complete
- New developers can get started quickly

**Files**:
- `specs/001-auth-revamp/quickstart.md`
- `specs/001-auth-revamp/contracts/*.md`
- `README.md`

---

### Task 7.5: Security Audit
**Priority**: P1 | **Effort**: 3 hours | **Dependencies**: Task 7.3

- [ ] Review password storage (bcrypt confirmed)
- [ ] Review CSRF protection (Sanctum confirmed)
- [ ] Review session security (timeouts configured)
- [ ] Review SQL injection prevention (Eloquent ORM)
- [ ] Review XSS prevention (Vue escaping)
- [ ] Review rate limiting (account lockout)
- [ ] Review audit logging (complete trail)

**Acceptance**:
- All security checklist items passed
- No critical vulnerabilities found
- Constitution security requirements met

**Files**:
- Security audit report

---

## Task Dependencies Graph

```
Phase 0 (Setup)
  0.1 → 0.2 → 0.3

Phase 1 (Core Auth)
  0.3 → 1.1 → 1.2 → 1.3 → 1.4 → 1.11
             → 1.5
  0.3 → 1.6 → 1.7 → 1.8 → 1.12
  1.1 → 1.9
  1.1 → 1.10

Phase 2 (Password)
  0.2 → 2.1 → 2.2 → 2.3 → 2.4 → 2.5

Phase 3 (Account Mgmt)
  2.1, 3.1 → 3.2 → 3.3 → 3.4 → 3.5

Phase 4 (Profile)
  3.1 → 4.1 → 4.4 → 4.5
  4.1 → 4.2
  2.1 → 4.3

Phase 5 (Multi-Role)
  1.10 → 5.1 → 5.2 → 5.3
  3.4 → 5.2

Phase 6 (Slack Extended)
  2.1 → 6.1 → 6.2

Phase 7 (Testing & Polish)
  All → 7.1 → 7.2
  7.1 → 7.3 → 7.5
  All → 7.4
```

## Success Metrics

Track these metrics to verify success criteria from spec:

- **SC-001**: Login completes in <5 seconds ✓
- **SC-002**: Account creation end-to-end <3 minutes ✓
- **SC-003**: 100% RBAC enforcement (automated tests) ✓
- **SC-004**: Password reset completion >95% within 10min ✓
- **SC-005**: Zero unauthorized access incidents ✓
- **SC-006**: Account management tasks <2 minutes each ✓
- **SC-007**: 100% audit log accuracy ✓
- **SC-008**: Slack integration >95% success rate ✓
- **SC-009**: Multi-role users access all features ✓
- **SC-010**: No email disclosure on failed login ✓

## Notes

- **Estimated Timeline**: 3-4 weeks for 1 developer
- **Critical Path**: Phase 0 → Phase 1 → Phase 2 → Phase 7.1 → Phase 7.5
- **Can Parallelize**: Phase 3 & Phase 4 after Phase 2 complete
- **Optional**: Phase 5 (Multi-Role) and Phase 6 (Slack Extended) can be deferred if timeline is tight
- **Constitution Compliance**: All phases include audit logging and permission checks as required
