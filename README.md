# FDC LMS - Laravel Learning Management System

A comprehensive Laravel 12 application with Livewire 4, Fortify authentication, and role-based access control.

## Table of Contents

- [Requirements](#requirements)
- [Installation](#installation)
- [Authentication System](#authentication-system)
- [Slack Integration](#slack-integration)
- [Environment Configuration](#environment-configuration)
- [Roles & Permissions](#roles--permissions)
- [Testing](#testing)
- [Scheduled Jobs](#scheduled-jobs)
- [Development](#development)

## Requirements

- PHP 8.3.30+
- Composer
- Node.js & NPM
- Docker (optional, recommended for development)
- MySQL 8.0+ or SQLite (for development)
- Slack workspace with API access (for production)

## Installation

### Using Docker (Recommended)

```bash
# Clone the repository
git clone <repository-url>
cd fdc-lms

# Copy environment file
cp .env.example .env

# Install dependencies using Docker
docker exec <container_id> composer install
docker exec <container_id> npm install

# Generate application key
docker exec <container_id> php artisan key:generate

# Run migrations
docker exec <container_id> php artisan migrate

# Build assets
docker exec <container_id> npm run build

# Start the application
docker exec <container_id> php artisan serve
```

### Local Installation

```bash
# Clone the repository
git clone <repository-url>
cd fdc-lms

# Copy environment file
cp .env.example .env

# Install dependencies
composer install
npm install

# Generate application key
php artisan key:generate

# Run migrations
php artisan migrate

# Build assets
npm run build

# Start the application
php artisan serve
```

## Authentication System

### Overview

The application uses **Laravel Fortify** for authentication with custom logic:

- **Login**: Email + password authentication with auto-logout for deactivated users
- **Registration**: HR-only feature with real-time Slack ID validation
- **Email Verification**: Token-based account activation system
- **Role-Based Redirection**: Users redirected based on assigned roles after login
- **Session Management**: Multi-session support with automatic invalidation for deactivated users

### User Statuses

| Status | Code | Description |
|--------|------|-------------|
| Deactivated | 0 | Account disabled, cannot login |
| Active | 1 | Fully active account |
| Pending Verification | 2 | Account created, awaiting email verification |

### Authentication Flow

#### 1. Registration (HR Only)

1. HR navigates to `/auth/register`
2. HR enters new employee details (name, email, Slack ID, role)
3. System validates Slack ID in real-time against Slack API
4. Account created with `status=2` (Pending Verification)
5. Verification token generated and sent via Slack DM

#### 2. Account Verification

1. Employee receives Slack DM with verification link
2. Employee clicks link: `/auth/verify?token=<token>`
3. System validates token (must be used within 24 hours)
4. Account status updated to `status=1` (Active)
5. Employee can now login

#### 3. Login & Redirection

1. Employee logs in with email + password
2. System checks account status (must be `status=1`)
3. User redirected based on primary role:
   - **Employee**: `/leaves` (Leave Request page)
   - **Approvers** (HR, Team Lead, Project Manager): `/portal` (Approval Portal)

#### 4. Logout & Session Cleanup

1. User clicks logout button in navbar
2. System invalidates current session
3. CSRF token regenerated
4. User redirected to login page
5. **Deactivated users**: All sessions automatically invalidated

### Routes

```php
// Guest routes
Route::get('/auth/login', Login::class)->name('login');
Route::get('/auth/register', Register::class)->middleware('auth', 'role:hr')->name('register');
Route::get('/auth/verify', [VerificationController::class, 'verify'])->name('auth.verify');
Route::get('/auth/request-verification', RequestNewVerification::class)->name('auth.request-verification');

// Authenticated routes
Route::middleware(['auth', 'ensure-user-active'])->group(function () {
    Route::get('/leaves', Leaves::class)->middleware('role:employee')->name('leaves');
    Route::get('/portal', Portal::class)->middleware('role:hr|team-lead|project-manager')->name('portal');
});

Route::post('/logout', function (Request $request) {
    Auth::logout();
    $request->session()->invalidate();
    $request->session()->regenerateToken();
    return redirect()->route('login');
})->middleware('auth')->name('logout');
```

## Slack Integration

### Purpose

Slack integration ensures:
- **User Identity Verification**: Validates Slack IDs during registration
- **Direct Messaging**: Sends verification links via Slack DM
- **Environment-Aware**: Bypasses Slack in local development, enforces in production/staging

### Configuration

Add the following to your `.env` file:

```env
# Slack API Configuration
SLACK_BOT_TOKEN=xoxb-your-bot-token-here
SLACK_API_USERID=U01234567890
SLACK_CHANNEL_ID=C01234567890
SLACK_HIGH_TOKEN=xoxp-your-high-privilege-token-here

# Application Environment
APP_ENV=local  # Options: local, staging, production
```

### Slack Token Types

| Token | Purpose | Scopes Required |
|-------|---------|-----------------|
| `SLACK_BOT_TOKEN` | Send DMs, validate users | `chat:write`, `users:read`, `im:write` |
| `SLACK_HIGH_TOKEN` | Administrative operations | Full workspace access |
| `SLACK_API_USERID` | Bot user ID | N/A (from bot configuration) |
| `SLACK_CHANNEL_ID` | Optional reporting channel | N/A |

### Obtaining Slack Tokens

1. **Create Slack App**: [api.slack.com/apps](https://api.slack.com/apps)
2. **Add Bot Scopes**: OAuth & Permissions → Bot Token Scopes
   - `chat:write` - Send messages as bot
   - `users:read` - View Slack user information
   - `im:write` - Open direct message channels
3. **Install to Workspace**: Install App → Copy `xoxb-` token
4. **Get Bot User ID**: Navigate to App Home → Copy Member ID
5. **Optional**: Create User Token for `SLACK_HIGH_TOKEN`

### Environment Behavior

```php
// Local Development (APP_ENV=local)
- Slack validation bypassed
- Verification links logged, not sent via Slack
- Accepts any Slack ID format

// Staging/Production (APP_ENV=staging|production)
- Full Slack validation enforced
- Verification links sent via Slack DM
- Rejects invalid Slack IDs
```

### Troubleshooting

**Error: "Invalid Slack ID"**
- Verify `SLACK_BOT_TOKEN` is valid
- Check bot has `users:read` scope
- Ensure Slack ID starts with `U` (not `W` or `@`)

**Error: "Failed to send verification link"**
- Verify `SLACK_BOT_TOKEN` has `im:write` scope
- Check bot is installed in workspace
- Ensure user hasn't blocked the bot

## Environment Configuration

### Required Variables

```env
# Application
APP_NAME="FDC LMS"
APP_ENV=local
APP_KEY=base64:...
APP_DEBUG=true
APP_TIMEZONE=UTC
APP_URL=http://localhost

# Database
DB_CONNECTION=sqlite
# DB_CONNECTION=mysql
# DB_HOST=127.0.0.1
# DB_PORT=3306
# DB_DATABASE=fdc_lms
# DB_USERNAME=root
# DB_PASSWORD=

# Slack API
SLACK_BOT_TOKEN=xoxb-your-bot-token
SLACK_API_USERID=U01234567890
SLACK_CHANNEL_ID=C01234567890
SLACK_HIGH_TOKEN=xoxp-your-token

# Session
SESSION_DRIVER=database
SESSION_LIFETIME=120

# Queue
QUEUE_CONNECTION=database
```

## Roles & Permissions

### Available Roles

The application uses **Spatie Laravel Permission** for role management:

| Role | Key | Description | Dashboard |
|------|-----|-------------|-----------|
| Employee | `employee` | Standard user | `/leaves` |
| HR | `hr` | Human Resources | `/portal` |
| Team Lead | `team-lead` | Team supervisor | `/portal` |
| Project Manager | `project-manager` | Project oversight | `/portal` |

### Role Assignment

Roles are assigned during registration by HR:

```php
use App\Actions\Auth\RegisterUserAction;

$registerAction = new RegisterUserAction();
$registerAction->execute($validatedData);
// Automatically assigns roles based on $validatedData['roles']
```

### Role-Based Middleware

```php
// Single role
Route::get('/leaves', Leaves::class)->middleware('role:employee');

// Multiple roles (OR logic)
Route::get('/portal', Portal::class)->middleware('role:hr|team-lead|project-manager');

// Multiple roles (AND logic)
Route::get('/admin', Admin::class)->middleware('role:hr,project-manager');
```

## Testing

### Running Tests

```bash
# Run all tests
php artisan test

# Run specific test file
php artisan test tests/Feature/Auth/LoginTest.php

# Run with coverage
php artisan test --coverage

# Run specific test method
php artisan test --filter=testActiveUserCanLogin

# Compact output
php artisan test --compact
```

### Test Structure

```
tests/
├── Feature/
│   ├── Auth/
│   │   ├── LoginTest.php           # Login flow tests
│   │   ├── RegistrationTest.php    # Registration tests
│   │   ├── VerificationTest.php    # Verification tests
│   │   ├── RoleRedirectionTest.php # Role-based redirection
│   │   └── LogoutTest.php          # Logout & session tests
│   ├── DashboardTest.php
│   └── ExampleTest.php
└── Unit/
    ├── Actions/
    │   └── Auth/
    │       ├── RegisterUserActionTest.php
    │       └── VerifyAccountActionTest.php
    └── Jobs/
        └── CleanupExpiredTokensJobTest.php
```

### Test Coverage Targets

- **Unit Tests**: 80%+ coverage for Actions, Services, Models
- **Feature Tests**: All user stories covered with happy/failure paths
- **Integration Tests**: Database, Slack API, session management

## Scheduled Jobs

### Token Cleanup Job

**Purpose**: Delete expired verification tokens (older than 24 hours)

**Schedule**: Daily at 2:00 AM

**Manual Execution**:
```bash
php artisan queue:work --once --queue=default
```

**Testing**:
```bash
php artisan test tests/Unit/Jobs/CleanupExpiredTokensJobTest.php
```

### Scheduler Configuration

Ensure Laravel scheduler is running:

```bash
# Add to crontab
* * * * * cd /path-to-your-project && php artisan schedule:run >> /dev/null 2>&1
```

Or use Laravel's built-in scheduler in development:

```bash
php artisan schedule:work
```

## Development

### Code Style

This project uses **Laravel Pint** for code formatting:

```bash
# Format all files
vendor/bin/pint

# Format specific directory
vendor/bin/pint app/Actions

# Check formatting without fixing
vendor/bin/pint --test

# Agent-friendly format
vendor/bin/pint --dirty --format agent
```

### Running Locally

```bash
# Start development server
php artisan serve

# Watch for asset changes
npm run dev

# Separate terminal for queue worker
php artisan queue:work
```

### Docker Development

```bash
# Access container shell
docker exec -it <container_id> bash

# Run Artisan commands
docker exec <container_id> php artisan migrate

# View logs
docker exec <container_id> tail -f storage/logs/laravel.log
```

### Livewire Components

**Creating Components**:
```bash
php artisan make:livewire Auth/Login
```

**Testing Components**:
```php
use Livewire\Livewire;

Livewire::test(Login::class)
    ->set('email', 'user@example.com')
    ->set('password', 'password')
    ->call('login')
    ->assertRedirect('/leaves');
```

## License

This project is proprietary software. All rights reserved.

## Support

For technical support or questions, contact the development team.
