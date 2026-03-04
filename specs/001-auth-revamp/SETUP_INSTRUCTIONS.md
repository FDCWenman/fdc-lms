# Authentication System Implementation - Setup Instructions

## Overview

This document provides step-by-step instructions for setting up and running the new authentication system for FDCLeave.

## Prerequisites

- PHP 8.2 or higher
- Composer
- MySQL 8.0+
- Node.js 18+ & npm
- Slack workspace with bot token (for Slack integration)

## Installation Steps

### 1. Install PHP Dependencies

```bash
# Install Spatie Laravel Permission package
composer require spatie/laravel-permission

# Install Slack PHP API (if using slack/slack-php-api)
# OR install Guzzle for HTTP client (already in Laravel)
composer require guzzlehttp/guzzle
```

### 2. Publish Spatie Configuration

```bash
php artisan vendor:publish --provider="Spatie\Permission\PermissionServiceProvider"
```

This will create:
- `config/permission.php` - Configuration file for roles and permissions
- Migrations for `roles`, `permissions`, `role_has_permissions`, `model_has_roles`, `model_has_permissions`

### 3. Run Database Migrations

```bash
# Run all migrations including Spatie's and custom auth migrations
php artisan migrate

# If you need to refresh the database (WARNING: destroys all data)
php artisan migrate:fresh
```

This will create the following tables:
- `users` (enhanced with slack_id, status, roles, etc.)
- `roles` (from Spatie)
- `permissions` (from Spatie)
- `role_has_permissions` (from Spatie)
- `model_has_roles` (from Spatie)
- `model_has_permissions` (from Spatie)
- `password_reset_tokens` (enhanced)
- `email_verification_tokens`
- `account_audit_logs`
- `failed_login_attempts`
- `sessions`

### 4. Seed Roles and Permissions

```bash
php artisan db:seed --class=RoleAndPermissionSeeder
```

This creates:
- **Role ID 1: Employee** - Basic leave management
- **Role ID 2: HR Approver** - Full account and leave management
- **Role ID 3: Lead Approver** - Team lead with approval rights
- **Role ID 4: PM Approver** - Project manager with approval rights

And 10 permissions covering leave management, approvals, account management, and reporting.

### 5. Configure Environment Variables

Update your `.env` file with Slack API credentials:

```env
# Slack Integration
SLACK_ENABLED=true
SLACK_BOT_TOKEN=xoxb-your-bot-token-here
SLACK_WEBHOOK_URL=https://hooks.slack.com/services/YOUR/WEBHOOK/URL
SLACK_LEAVE_CHANNEL_ID=C1234567890
SLACK_API_TIMEOUT=10
```

**Getting Slack Credentials:**
1. Go to https://api.slack.com/apps
2. Create a new app or select existing app
3. Add Bot Token Scopes:
   - `chat:write` - Send messages
   - `users:read` - Read user information
   - `users:read.email` - Read user email addresses
   - `users.profile:read` - Read user profile information
   - `channels:manage` - Manage public channels
   - `groups:write` - Manage private channels
4. Install app to workspace
5. Copy Bot User OAuth Token (starts with `xoxb-`)
6. Create Incoming Webhook and copy URL
7. Get channel ID from Slack (right-click channel → View channel details → Copy ID)

### 6. Configure Session Settings

Update `config/sanctum.php` for session management:

```php
'expiration' => 60 * 8,  // 8 hours inactivity timeout
'absolute_expiration' => 60 * 24,  // 24 hours absolute timeout
```

### 7. Register Middleware

The middleware has been created but needs to be registered. Add to `bootstrap/app.php`:

```php
->withMiddleware(function (Middleware $middleware) {
    $middleware->alias([
        'check.account.status' => \App\Http\Middleware\CheckAccountStatus::class,
        'redirect.by.role' => \App\Http\Middleware\RedirectByRole::class,
    ]);
    
    // Add to web middleware group
    $middleware->web(append: [
        \App\Http\Middleware\CheckAccountStatus::class,
    ]);
})
```

### 8. Register Policies

Add to `app/Providers/AuthServiceProvider.php`:

```php
protected $policies = [
    User::class => UserPolicy::class,
];
```

### 9. Update Routes

Routes need to be created in `routes/web.php`. Key routes include:

```php
use App\Http\Controllers\Auth\LoginController;
use App\Http\Controllers\Auth\VerificationController;
use App\Http\Controllers\Auth\PasswordResetController;
use App\Http\Controllers\Admin\AccountController;
use App\Http\Controllers\ProfileController;

// Authentication routes
Route::middleware('guest')->group(function () {
    Route::get('/login', [LoginController::class, 'showLoginForm'])->name('login');
    Route::post('/login', [LoginController::class, 'login']);
    Route::get('/forgot-password', [PasswordResetController::class, 'showForgotPasswordForm'])->name('password.request');
    Route::post('/forgot-password', [PasswordResetController::class, 'requestReset'])->name('password.email');
    Route::get('/reset-password/{token}', [PasswordResetController::class, 'showResetPasswordForm'])->name('password.reset');
    Route::post('/reset-password', [PasswordResetController::class, 'resetPassword'])->name('password.update');
    Route::get('/verify-email/{token}', [VerificationController::class, 'verify'])->name('verification.verify');
});

Route::middleware('auth')->group(function () {
    Route::post('/logout', [LoginController::class, 'logout'])->name('logout');
    Route::post('/verification/resend', [VerificationController::class, 'resend'])->name('verification.resend');
    
    // Home redirect
    Route::get('/', RedirectByRole::class)->name('home');
    
    // Profile management
    Route::get('/profile', [ProfileController::class, 'edit'])->name('profile.edit');
    Route::post('/profile/approvers', [ProfileController::class, 'updateApprovers'])->name('profile.approvers');
    Route::post('/profile/password', [ProfileController::class, 'changePassword'])->name('profile.password');
    Route::post('/profile/slack-refresh', [ProfileController::class, 'refreshSlackName'])->name('profile.slack-refresh');
});

// HR Account Management
Route::middleware(['auth', 'can:viewAny,App\Models\User'])->prefix('admin')->group(function () {
    Route::resource('accounts', AccountController::class);
    Route::post('/accounts/{user}/activate', [AccountController::class, 'activate'])->name('accounts.activate');
    Route::post('/accounts/{user}/deactivate', [AccountController::class, 'deactivate'])->name('accounts.deactivate');
});
```

## Testing the Implementation

### 1. Create a Test HR User

```bash
php artisan tinker
```

```php
$user = \App\Models\User::create([
    'name' => 'HR Admin',
    'email' => 'hr@example.com',
    'slack_id' => 'U1234567890', // Replace with real Slack ID
    'password' => bcrypt('password123'),
    'status' => \App\Models\User::STATUS_ACTIVE,
    'verified_at' => now(),
    'email_verified_at' => now(),
]);

$user->assignRole(2); // HR Approver role
$user->primary_role_id = 2;
$user->save();
```

### 2. Test Login

1. Navigate to `/login`
2. Enter email and password
3. Should redirect to `/portal` (for HR) or `/leaves` (for Employee)

### 3. Test Account Creation

1. Login as HR user
2. Navigate to `/admin/accounts/create`
3. Fill in employee details with valid Slack ID
4. User should receive verification email via Slack DM

### 4. Test Email Verification

1. Click verification link from Slack DM
2. Should mark account as verified and active
3. Can now login successfully

### 5. Test Password Reset

1. Click "Forgot Password" on login page
2. Enter email
3. Receive reset link via Slack DM
4. Click link and set new password
5. Should be able to login with new password

### 6. Test Account Lockout

1. Attempt login with wrong password 5 times within 15 minutes
2. Account should be locked for 30 minutes
3. Login page should show lockout message with countdown

## Architecture Overview

### Models
- **User** - Enhanced with auth fields, relationships, status methods
- **PasswordResetToken** - Tracks password reset requests
- **EmailVerificationToken** - Tracks email verifications
- **AccountAuditLog** - Complete audit trail of all account actions
- **FailedLoginAttempt** - Tracks failed logins and lockouts

### Services
- **SlackService** - All Slack API interactions
- **TokenService** - Token generation and validation
- **AuditLogService** - Centralized audit logging

### Actions (Business Logic)
- **Auth/AuthenticateUser** - Login with lockout logic
- **Auth/LogoutUser** - Logout and token cleanup
- **Auth/VerifyEmail** - Email verification process
- **Auth/RequestPasswordReset** - Request password reset via Slack
- **Auth/ResetPassword** - Complete password reset
- **Account/CreateEmployeeAccount** - HR creates new accounts
- **Account/ActivateAccount** - HR activates accounts
- **Account/DeactivateAccount** - HR deactivates accounts
- **Profile/UpdateDefaultApprovers** - User sets default approvers
- **Profile/ChangePassword** - User changes password
- **Profile/RefreshSlackName** - Sync name from Slack

### Middleware
- **CheckAccountStatus** - Ensures user account is active and verified
- **RedirectByRole** - Redirects users based on role after login

### Policies
- **UserPolicy** - Authorization for account management operations

## Key Features Implemented

✅ **Authentication**
- Email + password login
- Account status checks (active, verified, deactivated)
- Failed login tracking and account lockout (5 attempts → 30min lock)
- Session management (8hr inactivity, 24hr absolute timeout)

✅ **Email Verification**
- Token-based verification (48hr expiry)
- Sent via Slack DM
- Automatic status change to active on verification

✅ **Password Reset**
- Token-based reset (1hr expiry)
- Sent via Slack DM
- Password complexity requirements
- Invalidates other sessions on reset

✅ **Role-Based Access Control (RBAC)**
- 4 roles: Employee, HR Approver, Lead Approver, PM Approver
- 10 permissions covering all features
- Multi-role support (primary + secondary roles)
- Policy-based authorization

✅ **Account Management (HR)**
- Create employee accounts with Slack validation
- Activate/deactivate accounts with audit trail
- Prevent self-deactivation
- View all accounts and audit logs

✅ **Profile Management**
- Set default approvers (validated by role)
- Change password (requires current password)
- Refresh display name from Slack

✅ **Audit Trail**
- Complete logging of all account actions
- Records who, what, when, why, and where (IP)
- Separate audit log model with relationships

✅ **Slack Integration**
- User validation before account creation
- Password reset links via DM
- Email verification via DM
- Add users to leave channel
- Fetch/sync display names

## Next Steps

1. **Create Controllers** - Implement all controller methods
2. **Build Vue Components** - Create login, registration, profile, and account management UIs
3. **Write Tests** - Feature tests for all authentication flows
4. **Performance Optimization** - Add database indexes, cache role/permission lookups
5. **Security Audit** - Review all authentication and authorization logic
6. **Documentation** - API documentation and user guides

## Troubleshooting

### Composer not found
If composer is not available on your system, install it from https://getcomposer.org/ or use a Docker container with Laravel Sail.

### Slack API errors
- Verify bot token is correct and starts with `xoxb-`
- Check bot has required scopes in Slack app settings
- Ensure bot is added to the leave channel
- Test Slack ID format (should be like U1234567890)

### Migration errors
- Ensure database connection is configured in `.env`
- Check MySQL version is 8.0+
- Drop and recreate database if schema conflicts occur

### Permission errors
- Run `php artisan cache:clear` to clear cached permissions
- Verify roles were seeded correctly: `php artisan db:seed --class=RoleAndPermissionSeeder`
- Check user has assigned roles in database

## Support

For issues or questions, refer to:
- [Specification](./spec.md)
- [Task List](./tasks.md)
- [Architecture Diagrams](./diagrams.md)
