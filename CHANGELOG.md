# Changelog - FDC Leave Management System

All notable changes to this project will be documented in this file.

The format is based on [Keep a Changelog](https://keepachangelog.com/en/1.0.0/),
and this project adheres to [Semantic Versioning](https://semver.org/spec/v2.0.0.html).

## [Unreleased]

### Added - March 6, 2026

#### Authentication System
- ✅ **Public User Registration** - `/register` route accessible to anyone without HR restrictions
- ✅ **Enhanced User Model** - Added `first_name`, `middle_name`, `last_name`, and `hired_date` fields
- ✅ **Password Fields in Registration** - Users can set their own password during registration
- ✅ **Slack Integration** - Real-time Slack ID validation with environment-aware behavior
- ✅ **Environment-Specific Slack Control** - Added `ALLOW_SLACK_LOCAL` environment variable to enable/disable Slack integration in local environment
- ✅ **Email Verification via Slack DM** - Verification tokens sent via Slack direct messages
- ✅ **Auto-Role Assignment** - New registrations automatically assigned "employee" role
- ✅ **Login System** - Email/password authentication with Fortify
- ✅ **Logout Functionality** - Session termination with redirect to login page
- ✅ **Password Reset Flow** - Forgot password with Slack DM delivery
- ✅ **Profile Management** - Users can update default approvers and settings

#### User Interface
- ✅ **Flux UI Integration** - Replaced Flux icon components with inline SVG (Free edition compatibility)
- ✅ **Responsive Registration Form** - Multi-column layout with proper field alignment
- ✅ **Real-time Slack Validation UI** - Loading states and success indicators
- ✅ **Role-Based Navigation** - Different dashboards for employees, HR, approvers

#### Access Control
- ✅ **Role-Based Middleware** - `CheckRole` middleware for role verification
- ✅ **Active User Enforcement** - `EnsureUserIsActive` middleware blocks inactive accounts
- ✅ **Spatie Permission Package** - Integrated for advanced role and permission management
- ✅ **Route Protection** - Separate route groups for guest, authenticated, and role-specific access

#### Database & Models
- ✅ **User Model Enhancements** - Added name fields, hired date, verification status, default approvers
- ✅ **Verification Token System** - Token-based email verification with 24-hour expiration
- ✅ **Migration for User Fields** - Added `first_name`, `middle_name`, `last_name`, `hired_date` columns
- ✅ **User Status Enum** - Defined statuses: deactivated (0), active (1), for_verification (2)

#### Testing Infrastructure
- ✅ **Laravel Dusk E2E Tests** - Comprehensive browser automation testing for authentication flows
- ✅ **Test Coverage** - 14 Dusk tests for login, registration, logout, and role-based access
- ✅ **CI/CD Integration** - GitHub Actions workflow with separate Dusk test job
- ✅ **Makefile Commands** - Convenient `make test-dusk` and `make test-dusk-auth` commands
- ✅ **Headless Chrome Setup** - Configured DuskTestCase with headless browser support

#### Development Tools
- ✅ **Laravel Boost MCP Integration** - Enhanced development experience with Boost tools
- ✅ **Config File Cleanup** - Flattened Slack config structure for easier access
- ✅ **View Caching** - Proper cache clearing for Blade templates
- ✅ **Development Logging** - Verification URLs logged in local environment

### Changed - March 6, 2026

#### Registration Flow
- **BREAKING**: Registration no longer requires HR role access
- **BREAKING**: Password auto-generation removed - users now set passwords during registration
- **Enhanced**: Registration form now collects first_name, middle_name, last_name, hired_date instead of single name field
- **Enhanced**: Slack ID validation moved from registration-time to real-time on blur event

#### Configuration
- **Improved**: Slack configuration simplified from nested structure to flat structure
- **Added**: Default empty string values for Slack config to prevent null property errors
- **Added**: `ALLOW_SLACK_LOCAL` environment variable for local Slack testing

#### Service Layer
- **Enhanced**: SlackService now respects local environment and `ALLOW_SLACK_LOCAL` setting
- **Fixed**: SlackService typed properties now have default empty string values
- **Enhanced**: RegisterUserAction logs verification URL in local environment when Slack is disabled

### Fixed - March 6, 2026

#### UI Issues
- ✅ Fixed uneven password field heights in registration form (added matching description placeholders)
- ✅ Replaced incompatible Flux Pro icon components with SVG alternatives
- ✅ Fixed form alignment issues with proper grid spacing

#### Configuration Issues
- ✅ Fixed SlackService null property assignment errors
- ✅ Fixed config key access pattern mismatch (nested vs flat structure)
- ✅ Added config cache clearing after config changes

#### Verification Flow
- ✅ Fixed verification link delivery in local environment (now logs URL when Slack disabled)
- ✅ Added environment-aware Slack DM sending

### Security

#### Authentication
- ✅ Password hashing with bcrypt via Laravel's Hash facade
- ✅ CSRF protection on all forms
- ✅ Email verification required before account activation
- ✅ Token-based password reset with 24-hour expiration
- ✅ Unique Slack ID validation to prevent duplicates

#### Access Control
- ✅ Role-based route protection with middleware
- ✅ Active user status enforcement
- ✅ Guest-only routes for unauthenticated users
- ✅ Authenticated-only routes with redirect to login

### Developer Notes

#### Environment Variables Required
```env
# Application
APP_NAME="FDC Leave Management System"
APP_ENV=local
APP_DEBUG=true
APP_URL=http://localhost

# Database
DB_CONNECTION=sqlite

# Slack Integration (Optional in local)
ALLOW_SLACK_LOCAL=0  # Set to 1 to enable Slack in local environment
SLACK_BOT_TOKEN=xoxb-your-token
SLACK_CHANNEL_ID=C01234567890
SLACK_WEBHOOK_URL=https://hooks.slack.com/services/...
```

#### Testing Commands
```bash
# Run all tests
make test

# Run Dusk E2E tests
make test-dusk

# Run auth-specific Dusk tests
make test-dusk-auth

# Run unit and feature tests
php artisan test
```

#### Known Limitations
- Slack integration requires valid bot token in production/staging
- Local environment skips actual Slack API calls unless `ALLOW_SLACK_LOCAL=1`
- Registration form requires hired_date to be today or earlier

### Migration Status
- ✅ `0001_01_01_000000_create_users_table` - Base user table
- ✅ `0001_01_01_000001_create_cache_table` - Laravel cache
- ✅ `0001_01_01_000002_create_jobs_table` - Queue jobs
- ✅ `2025_08_14_170933_add_two_factor_columns_to_users_table` - 2FA columns
- ✅ `2026_03_05_000001_create_verification_tokens_table` - Email verification
- ✅ `2026_03_05_000002_add_auth_columns_to_users_table` - Status, Slack ID
- ✅ `2026_03_06_034631_create_permission_tables` - Spatie permissions
- ✅ `2026_03_06_100000_add_name_fields_and_hired_date_to_users_table` - Name fields

---

## Pending Tasks

### Phase 8: Polish & Cleanup
- [ ] Implement CleanupExpiredTokensJob for verification token cleanup (T083-T085)
- [ ] Create VerificationTokenFactory for testing (T086-T087)
- [ ] Update README with setup instructions (T088-T090)
- [ ] Run Laravel Pint for code formatting (T091)
- [ ] Verify test coverage metrics (T092)
- [ ] Review and update inline documentation (T093)

### Future Enhancements
- [ ] Two-factor authentication (2FA) implementation
- [ ] Password strength requirements configuration
- [ ] Account lockout after failed login attempts
- [ ] Email notification fallback when Slack unavailable
- [ ] Admin dashboard for user management
- [ ] Audit logging for user actions

---

## Version History

### v0.1.0 - 2026-03-06 (Alpha)
- Initial authentication system implementation
- Public user registration with Slack integration
- Role-based access control foundation
- E2E testing infrastructure with Laravel Dusk
