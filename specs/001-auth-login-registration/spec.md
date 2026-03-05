# Feature Specification: Authentication System - Login & Registration

**Feature Branch**: `001-auth-login-registration`  
**Created**: March 5, 2026  
**Status**: Draft  
**Input**: User description: "Complete authentication system with Login and Registration for Laravel + Livewire migration from CakePHP"

---

## Clarifications

### Session 2026-03-05

- Q: When a user's account is deactivated while they are actively logged in, how should the system handle their session? → A: Session invalidated at next request - user logged out automatically when they perform any action
- Q: If the Slack API is unavailable during user registration, should the system allow the registration to proceed or require Slack validation to complete? → A: Block registration and require Slack API validation in production/staging environments, but bypass Slack validation when APP_ENV is "local" for development convenience
- Q: How should expired verification tokens be cleaned up from the database? → A: Scheduled daily cleanup task removes tokens older than 30 days
- Q: Is verification done via email or Slack? → A: Verification is done exclusively through Slack (not email). The company only uses Slack verification for account activation
- Q: Should the system allow multiple concurrent sessions for the same user account? → A: Allow multiple concurrent sessions - user can be logged in from multiple devices/browsers
- Q: What should happen when an admin tries to register a user with a Slack ID that's already assigned to another existing user? → A: Block registration and show error indicating Slack ID already exists in system

---

## User Scenarios & Testing *(mandatory)*

### User Story 1 - Employee Login (Priority: P1)

An employee wants to access the leave management system using their email and password. They must be able to log in securely and be redirected to the appropriate dashboard based on their role.

**Why this priority**: Login is the gateway to the entire system. Without it, no other functionality is accessible. This is the foundation of all user interactions.

**Independent Test**: Can be fully tested by registering a user, logging in with valid credentials, and verifying the redirect to the correct dashboard (employees go to `/leaves`, approvers go to `/portal`). This delivers immediate access to the system.

**Acceptance Scenarios**:

1. **Given** an active, verified employee account, **When** they enter valid email and password, **Then** they are logged in and redirected to the leaves page (`/leaves`)
2. **Given** an active, verified approver account (HR/TL/PM), **When** they enter valid credentials, **Then** they are logged in and redirected to the portal page (`/portal`)
3. **Given** an inactive or unverified account, **When** they attempt to login, **Then** they see an error message and remain on the login page
4. **Given** incorrect credentials, **When** they attempt to login, **Then** they see an appropriate error message without revealing whether email or password was incorrect
5. **Given** a logged-in user, **When** they navigate to the login page, **Then** they are automatically redirected to their dashboard

---

### User Story 2 - Admin-Driven User Registration (Priority: P2)

An HR administrator needs to create new employee accounts in the system. They must be able to register new users with their basic information, assign roles, and validate Slack integration details.

**Why this priority**: Without user registration, the system cannot grow beyond initial seeded users. This is critical for onboarding new employees and maintaining the user base.

**Independent Test**: Can be tested by logging in as an HR admin, accessing the user registration form, creating a new user with valid Slack ID, and verifying the account is created with "for_verification" status. This delivers the ability to onboard new users.

**Acceptance Scenarios**:

1. **Given** an HR administrator is logged in, **When** they access the registration form, **Then** they can input: employee name, email, password, Slack ID, role, and default approvers
2. **Given** valid registration data with a correct Slack ID, **When** the form is submitted, **Then** the user account is created with status "for_verification" and a verification email is sent
3. **Given** an invalid or non-existent Slack ID, **When** the form is submitted, **Then** an error message is displayed and the account is not created
4. **Given** a duplicate email address, **When** registration is attempted, **Then** an error message indicates the email is already registered
5. **Given** a newly registered user, **When** the account is created, **Then** the user is automatically added to the Slack leave management channel

---

### User Story 3 - Slack Verification (Priority: P2)

A newly registered employee receives a verification message via Slack DM with a unique link. They must be able to click the link to activate their account and gain login access.

**Why this priority**: Slack verification ensures account ownership and validates that the user has access to their Slack account. It's a security requirement that must be in place before users can access the system.

**Independent Test**: Can be tested by registering a user, checking for the verification Slack DM, clicking the link, and attempting to log in with the newly verified account. This delivers account activation capability.

**Acceptance Scenarios**:

1. **Given** a newly registered user with "for_verification" status, **When** they click the verification link in their Slack DM, **Then** their account status changes to "active", `verified_at` timestamp is set, and they see a success message
2. **Given** an already verified account, **When** the verification link is clicked again, **Then** they see a message indicating the account is already verified
3. **Given** an expired or invalid verification token, **When** clicked, **Then** they see an error message and are prompted to request a new verification link via Slack
4. **Given** a verified and active account, **When** they attempt to log in, **Then** they can successfully access the system

---

### User Story 4 - Role-Based Dashboard Redirection (Priority: P3)

After successful login, users should be automatically redirected to the appropriate page based on their role and permissions to ensure optimal workflow.

**Why this priority**: Proper role-based routing improves user experience by eliminating unnecessary navigation steps. While important, basic login can work without sophisticated routing.

**Independent Test**: Can be tested by logging in with different role accounts (Employee, HR, TL, PM) and verifying each is redirected to their appropriate dashboard. This delivers role-aware navigation.

**Acceptance Scenarios**:

1. **Given** an employee (Role ID: 1), **When** they log in, **Then** they are redirected to `/leaves` (their leave requests page)
2. **Given** an HR approver (Role ID: 2), **When** they log in, **Then** they are redirected to `/portal` (approval dashboard)
3. **Given** a TL approver (Role ID: 3), **When** they log in, **Then** they are redirected to `/portal`
4. **Given** a PM approver (Role ID: 4), **When** they log in, **Then** they are redirected to `/portal`
5. **Given** a user with a secondary role, **When** they log in, **Then** they have access to both role capabilities and are redirected based on their primary role

---

### User Story 5 - Logout Functionality (Priority: P3)

Users need the ability to securely log out of the system to protect their account when using shared or public computers.

**Why this priority**: While security is important, logout is a standard feature that can be implemented after core login functionality is working.

**Independent Test**: Can be tested by logging in, clicking logout, and verifying the session is destroyed and attempting to access protected pages redirects to login. This delivers session security.

**Acceptance Scenarios**:

1. **Given** a logged-in user, **When** they click the logout button, **Then** their session is terminated and they are redirected to the login page
2. **Given** a logged-out user, **When** they attempt to access protected pages, **Then** they are redirected to the login page
3. **Given** a user has logged out, **When** they click the browser back button, **Then** they cannot access previously viewed protected pages without logging in again

---

### Edge Cases

- When a user's account is deactivated while they are logged in, their session is invalidated at the next request and they are automatically logged out
- The system allows multiple concurrent sessions for the same user account across different devices and browsers
- If the Slack API is unavailable during registration in production/staging, registration fails with an error message requiring retry; in local development environment (APP_ENV=local), Slack validation is bypassed
- How does the system handle password reset for unverified accounts?
- What happens when a verification link is clicked after the account has been deactivated?
- Expired verification tokens are automatically cleaned up by a scheduled daily task that removes tokens older than 30 days
- When an admin tries to register a user with a Slack ID already assigned to another user, registration is blocked and an error message indicates the Slack ID is already in use

---

## Requirements *(mandatory)*

### Functional Requirements

#### Authentication

- **FR-001**: System MUST authenticate users using email and password combination
- **FR-002**: System MUST hash all passwords using bcrypt algorithm before storage
- **FR-003**: System MUST enforce that only accounts with status "active" (1) AND `verified_at` timestamp set can log in
- **FR-004**: System MUST redirect employees (Role ID: 1) to `/leaves` after successful login
- **FR-005**: System MUST redirect approvers (Role IDs: 2, 3, 4) to `/portal` after successful login
- **FR-006**: System MUST display generic error messages for failed login attempts without revealing whether email or password was incorrect
- **FR-007**: System MUST prevent access to protected routes for unauthenticated users
- **FR-008**: System MUST provide a logout mechanism that terminates the user session
- **FR-009**: System MUST prevent access to previously viewed pages after logout via browser back button
- **FR-034**: System MUST invalidate user sessions at the next request when an account is deactivated, automatically logging out the user

#### User Registration (Admin-Only)

- **FR-010**: System MUST restrict user registration to HR administrators (Role ID: 2) only
- **FR-011**: System MUST validate Slack ID against Slack API during registration in real-time in production and staging environments; Slack validation MUST be bypassed when APP_ENV is set to "local"
- **FR-012**: System MUST collect the following required fields during registration: full name, email, password, Slack ID, primary role, default approvers (HR, TL, PM)
- **FR-013**: System MUST enforce unique email addresses across all user accounts
- **FR-038**: System MUST enforce unique Slack IDs across all user accounts and block registration if Slack ID already exists
- **FR-014**: System MUST create new user accounts with status "for_verification" (2) by default
- **FR-015**: System MUST support assignment of both primary role and optional secondary role during registration
- **FR-016**: System MUST store default approvers as a JSON structure containing HR approver, TL approver, and PM approver user IDs
- **FR-017**: System MUST automatically add newly registered users to the Slack leave management channel via Slack API in production and staging; MUST skip Slack channel invitation when APP_ENV is "local"
- **FR-018**: System MUST send a verification Slack DM with a unique token link immediately after successful registration
- **FR-035**: System MUST block registration and display an error message when Slack API is unavailable in production or staging environments

#### Slack Verification

- **FR-019**: System MUST generate a unique verification token for each new user registration
- **FR-020**: System MUST send verification Slack DM containing a clickable link with the verification token to the user's Slack account
- **FR-021**: System MUST update account status from "for_verification" (2) to "active" (1) when verification link is clicked
- **FR-022**: System MUST set the `verified_at` timestamp when account is verified
- **FR-023**: System MUST display appropriate messages for already-verified accounts when verification link is clicked
- **FR-024**: System MUST handle expired or invalid verification tokens gracefully with clear error messages
- **FR-025**: System MUST allow users to request a new verification Slack DM if their token has expired
- **FR-036**: System MUST run a scheduled daily cleanup task to remove verification tokens older than 30 days from the database

#### Role Management

- **FR-026**: System MUST support four role types: Employee (1), HR Approver (2), TL Approver (3), PM Approver (4)
- **FR-027**: System MUST support secondary role assignment, allowing users to hold dual roles
- **FR-028**: System MUST enforce role-based access control for all protected routes
- **FR-029**: System MUST grant access permissions based on both primary and secondary roles

#### UI/UX Requirements

- **FR-030**: System MUST display the FDCLeave logo (from `public/images/fdc.png`) on login and registration pages
- **FR-031**: System MUST provide clear, user-friendly error messages for all validation failures
- **FR-032**: System MUST display loading indicators during Slack API validation calls
- **FR-033**: System MUST redirect already-authenticated users away from login/registration pages to their appropriate dashboard
- **FR-037**: System MUST allow multiple concurrent sessions for the same user account across different devices and browsers

### Key Entities

- **User**: Represents an employee or administrator in the system
  - Attributes: name, email, password hash, Slack ID, primary role, secondary role (optional), account status (for_verification/active/deactivated), email verification status (`verified_at` timestamp), default approvers (JSON: HR, TL, PM user IDs)
  - Status values: 0 (deactivated), 1 (active), 2 (for_verification)

- **Role**: Defines user permissions and access levels
  - Types: Employee (1), HR Approver (2), TL Approver (3), PM Approver (4)
  - Each role determines dashboard redirection and feature access

- **Verification Token**: Temporary token for Slack-based account verification
  - Attributes: token hash, associated user, creation timestamp, verification timestamp
  - Single-use tokens that expire after 24 hours; automatically cleaned up after 30 days

---

## Success Criteria *(mandatory)*

### Measurable Outcomes

- **SC-001**: Users can complete login process in under 10 seconds with valid credentials
- **SC-002**: Slack verification flow completes successfully for 100% of valid verification links
- **SC-003**: 100% of users are redirected to the correct dashboard based on their role after login
- **SC-004**: System prevents 100% of login attempts from unverified or inactive accounts
- **SC-005**: Registration form validates Slack IDs in under 3 seconds via real-time API checks
- **SC-006**: Zero successful logins occur after a user logs out, even when using browser back button
- **SC-007**: System handles authentication for at least 100 concurrent users without performance degradation
- **SC-008**: All password storage uses bcrypt hashing with zero plain-text passwords in database
- **SC-009**: 95% of users successfully verify their account via Slack within 24 hours of registration
- **SC-010**: Registration failure rate due to invalid Slack IDs is under 5%

---

## Assumptions

1. **Slack Integration**: Slack Bot OAuth token and Incoming Webhook URL are already configured in the Laravel application environment for both verification DMs and channel notifications
2. **Existing Database**: User and role tables exist in the database with the schema matching the legacy system structure
4. **Laravel Fortify**: Laravel Fortify is installed and configured as the authentication foundation
5. **Logo Format**: The FDCLeave logo at `public/images/fdc.png` can be displayed directly or converted to SVG format for better scalability
6. **Admin Seeding**: At least one HR administrator account exists in the system for initial user registration
7. **Slack Channel**: The Slack leave management channel exists and the bot has permissions to add users and send DMs
8. **Session Management**: Laravel's default session handling is sufficient for authentication state management
9. **Token Expiration**: Verification tokens expire after 24 hours and are cleaned up after 30 days
10. **Task Scheduling**: Laravel's task scheduler is configured to run daily cleanup tasks

---

## Out of Scope

The following features from the complete authentication system are explicitly excluded from this specification and will be addressed in future iterations:

- Forgot password functionality (Slack DM-based reset)
- Password reset with token validation
- Change password from user profile
- Account deactivation/activation by HR admins
- User profile management (updating default approvers, refreshing Slack display name)
- Offline leave entry (HR bypass registration)
- User activity audit logging
- Account activation/deactivation logging
- Two-factor authentication (if planned)
- Social authentication (OAuth providers)

---

## Dependencies

1. **Laravel Fortify**: Required for authentication scaffolding and session management
2. **Slack API**: Required for Slack ID validation, channel invitation, and sending verification DMs during registration
3. **Existing Database Schema**: User and role tables must exist with appropriate columns
4. **Livewire 4**: Required for reactive UI components on authentication pages
5. **Flux UI**: Component library for consistent UI design across authentication forms
6. **Laravel Task Scheduler**: Required for running daily verification token cleanup tasks

---

## Notes

- This specification focuses on the core authentication foundation (login + registration) as the first step in migrating from CakePHP to Laravel + Livewire
- The registration flow is admin-driven (HR only), not public self-registration
- Slack verification (via DM) is the exclusive verification method - no email verification is used
- Slack integration is critical to the registration process and must be validated before account creation
- Role-based redirection ensures users land on the most relevant page for their workflow
- The system maintains backward compatibility with the legacy role structure (4 roles + secondary role support)
- Verification tokens expire after 24 hours and are automatically cleaned up after 30 days via scheduled task
