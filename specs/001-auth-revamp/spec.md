# Feature Specification: Authentication & Registration System Revamp

**Feature Branch**: `001-auth-revamp`  
**Created**: March 4, 2026  
**Status**: Draft  
**Input**: User description: "Complete authentication and registration system revamp with role-based access control using Spatie Laravel Permission. Includes user registration, email verification, password management, multi-role support, account management, and Slack integration."

## Clarifications

### Session 2026-03-04

- Q: Session duration policy - How long should sessions remain active and should there be timeout behavior? → A: Sessions expire after 8 hours of inactivity with sliding expiration on activity; 24-hour absolute limit requires re-authentication
- Q: Password reset token expiration - How long should password reset tokens remain valid? → A: 1 hour expiration
- Q: Organization email domain validation - What email domain(s) should be allowed for employee accounts? → A: any valid email format
- Q: Default approver assignment strategy - How are organizational default approvers determined and managed? → A: Employees must manually set all approvers during first login; no system defaults
- Q: Maximum failed login attempts - Should there be account lockout or rate limiting after repeated failed login attempts? → A: 5 failed attempts within 15 minutes locks account for 30 minutes

## User Scenarios & Testing *(mandatory)*

### User Story 1 - Employee Self-Service Authentication (Priority: P1)

Employees need to securely log into the system to access their leave management features. They should be able to authenticate using their email and password, reset forgotten passwords, and maintain their profile information.

**Why this priority**: Core authentication is the foundation of the entire system. Without the ability to log in, no other features are accessible. This represents the minimum viable authentication system.

**Independent Test**: Can be fully tested by creating a user account, logging in with valid credentials, testing invalid credential rejection, and verifying session persistence across page refreshes.

**Acceptance Scenarios**:

1. **Given** a registered and verified employee account, **When** the employee enters valid email and password credentials, **Then** they are authenticated and redirected to their leave dashboard
2. **Given** an employee with an unverified account, **When** they attempt to log in, **Then** they receive a message prompting them to verify their email address first
3. **Given** an authenticated employee session, **When** the employee navigates between pages, **Then** their session persists and they remain logged in
4. **Given** an employee who forgot their password, **When** they request a password reset, **Then** they receive a secure reset link via Slack DM and can set a new password
5. **Given** an employee with invalid credentials, **When** they attempt to log in, **Then** they receive an error message without revealing whether the email exists
6. **Given** an employee with a deactivated account, **When** they attempt to log in, **Then** they receive a message indicating their account is inactive
7. **Given** an employee who has failed login 5 times within 15 minutes, **When** they attempt to log in again, **Then** their account is locked for 30 minutes and they receive a message indicating the lockout time

---

### User Story 2 - Email Verification for New Accounts (Priority: P1)

New employees must verify their email address before they can access the system. This ensures that only legitimate company email addresses are used and that employees can receive important notifications.

**Why this priority**: Security requirement that prevents unauthorized access and validates employee identity. Must be implemented alongside basic authentication to ensure secure account activation.

**Independent Test**: Can be fully tested by creating a new account, receiving verification email, clicking the link, and confirming the account becomes active and accessible.

**Acceptance Scenarios**:

1. **Given** a newly created employee account, **When** the account is created, **Then** a verification email is sent to the employee's registered email address
2. **Given** an unverified employee account, **When** the employee clicks the verification link in their email, **Then** their account status changes to verified and they can log in
3. **Given** an unverified employee account, **When** 24 hours pass without verification, **Then** the employee can request a new verification email
4. **Given** a verified employee account, **When** the employee clicks an old verification link, **Then** they are informed the account is already verified and can proceed to log in
5. **Given** an expired verification link, **When** the employee clicks it, **Then** they are prompted to request a new verification email

---

### User Story 3 - Role-Based Access Control (Priority: P1)

Different users have different responsibilities in the leave management system. Employees file and manage their own leaves, HR Approvers handle first-level approvals and account management, Lead Approvers manage team approvals, and PM Approvers handle final approvals and administrative tasks.

**Why this priority**: Critical for security and proper workflow. Without role-based access control, employees could access approval features or HR could be restricted from performing their duties. This is foundational for all approval workflows.

**Independent Test**: Can be fully tested by creating users with each role, logging in as each user type, and verifying that each role can only access their designated features and pages.

**Acceptance Scenarios**:

1. **Given** an authenticated employee without approver roles, **When** they attempt to access the approval queue, **Then** they are denied access and redirected to their leave dashboard
2. **Given** an authenticated HR Approver, **When** they log in, **Then** they are redirected to the portal calendar view and can access HR-specific features
3. **Given** an authenticated Lead Approver, **When** they view the approval queue, **Then** they only see leave requests that are pending their approval level
4. **Given** an authenticated PM Approver, **When** they access the approval queue, **Then** they can see all pending PM-level approvals and have access to bulk approval actions
5. **Given** a user with multiple roles (primary and secondary), **When** they navigate the system, **Then** they can access features from both roles
6. **Given** a user with a specific role, **When** their permissions are checked, **Then** the system correctly identifies their capabilities based on Spatie permission assignments

---

### User Story 4 - HR Account Management (Priority: P2)

HR staff need to create and manage employee accounts, including registration, activation, deactivation, and reactivation. All account status changes must be logged for audit purposes.

**Why this priority**: Essential for HR operations but can be implemented after basic authentication. HR needs to onboard new employees and manage account lifecycles as part of normal operations.

**Independent Test**: Can be fully tested by logging in as HR, creating a new employee account, activating it, deactivating it with a reason, and verifying all actions are logged.

**Acceptance Scenarios**:

1. **Given** an authenticated HR Approver, **When** they create a new employee account with valid information including Slack ID, **Then** the account is created in "for_verification" status and a verification email is sent
2. **Given** an authenticated HR Approver creating an account, **When** they enter a Slack ID, **Then** the system validates the Slack ID against the Slack API in real-time
3. **Given** an authenticated HR Approver, **When** they deactivate an employee account, **Then** they must provide a reason, the account status changes to "deactivated", and the action is logged with timestamp, user, and IP address
4. **Given** an authenticated HR Approver, **When** they reactivate a deactivated account, **Then** the account status changes to "active", the employee can log in again, and the reactivation is logged
5. **Given** a newly registered employee, **When** their account is created, **Then** they are automatically added to the designated Slack workspace channel
6. **Given** an HR Approver viewing account history, **When** they access the audit log for an employee, **Then** they can see all activation/deactivation events with dates, reasons, and who performed the action

---

### User Story 5 - Profile Management & Default Approvers (Priority: P2)

Employees need to maintain their profile information, including updating their default approvers (HR, Team Lead, PM) for their leave requests. This personalizes the approval workflow for each employee.

**Why this priority**: Improves workflow efficiency by allowing employees to pre-select their usual approvers, but the system can function with default approvers assigned by HR during initial setup.

**Independent Test**: Can be fully tested by logging in as an employee, accessing profile settings, updating default approvers, and verifying these approvers are used when filing a leave request.

**Acceptance Scenarios**:

1. **Given** an authenticated employee, **When** they access their profile settings, **Then** they can view their current default approvers (HR, Team Lead, and PM)
2. **Given** an authenticated employee updating their profile, **When** they select new default approvers from a list of users with appropriate roles, **Then** the selections are saved and used for future leave requests
3. **Given** an authenticated employee, **When** they change their password, **Then** they must provide their current password, the new password must meet security requirements, and they receive confirmation of the change
4. **Given** an authenticated employee, **When** they update their Slack display name from their profile, **Then** the system fetches the current name from the Slack API and updates the local database
5. **Given** an authenticated employee without default approvers set, **When** they attempt to file a leave request, **Then** they are prompted to select their default approvers first

---

### User Story 6 - Multi-Role Support (Priority: P3)

Some employees hold multiple roles in the organization. For example, an HR Approver might also serve as a Team Lead, or a Team Lead might also have PM responsibilities. The system must support both primary and secondary roles for these users.

**Why this priority**: Supports organizational flexibility but is not critical for initial launch. Most users have a single role, and multi-role support can be added after the basic role system is stable.

**Independent Test**: Can be fully tested by creating a user with both primary and secondary roles, logging in, and verifying they have access to features from both roles.

**Acceptance Scenarios**:

1. **Given** a user assigned both a primary role and a secondary role, **When** they access the system, **Then** they have permissions from both roles
2. **Given** an HR Approver with a secondary role as Team Lead, **When** they view the approval queue, **Then** they see both HR-level approvals and Team Lead-level approvals
3. **Given** a user with multiple roles, **When** they approve a leave request, **Then** the approval is recorded under the appropriate role level based on the request's current status
4. **Given** a user assigned a secondary role, **When** their secondary role is removed, **Then** they retain access through their primary role but lose secondary role permissions

---

### User Story 7 - Slack Integration for User Management (Priority: P3)

The system integrates with Slack for user validation and communication. When creating accounts, Slack IDs must be validated. The system should support adding users to Slack channels and syncing display names.

**Why this priority**: Enhances user experience and workflow integration but is not essential for basic authentication. The system can function without real-time Slack integration if needed.

**Independent Test**: Can be fully tested by creating a user account with Slack integration enabled, verifying Slack ID validation occurs, and confirming the user is added to the Slack channel.

**Acceptance Scenarios**:

1. **Given** an HR Approver creating a new account, **When** they enter a Slack ID, **Then** the system validates it against the Slack API and shows an error if invalid
2. **Given** a newly created employee account, **When** the account is activated, **Then** the user is automatically invited to the organization's leave management Slack channel
3. **Given** an authenticated employee, **When** they refresh their Slack display name from their profile, **Then** the system queries the Slack API and updates the local database with the current Slack name
4. **Given** a deactivated employee account, **When** the account is deactivated, **Then** the system does not remove them from Slack but marks them as inactive in the database

---

### Edge Cases

- What happens when a user's Slack ID is changed or becomes invalid after account creation?
- How does the system handle password reset requests for accounts that are currently deactivated?
- What happens if an employee verifies their email after their account has been deactivated by HR?
- How does the system prevent HR from deactivating their own account?
- What happens when a user with multiple roles is viewing an approval queue and a request that they filed themselves appears?
- How does the system handle simultaneous password change attempts from different devices?
- What happens if the Slack API is unavailable during account creation or name refresh?
- How does the system handle expired verification tokens when an employee clicks an old verification link?
- What happens when an employee with a secondary role has that role removed while they're actively using the system?
- How does the system prevent duplicate email addresses during registration?

## Requirements *(mandatory)*
 with sliding expiration after 8 hours of inactivity and absolute expiration after 24 hours
### Functional Requirements

**Authentication & Session Management**

- **FR-001**: System MUST authenticate users using email and password credentials with secure password hashing
- **FR-002**: System MUST maintain user sessions across page navigation and browser refreshes
- **FR-003**: System MUST invalidate sessions when users explicitly log out
- **FR-004**: System MUST reject login attempts from unverified accounts with a clear message
- **FR-005**: System MUST reject login attempts from deactivated accounts with a clear message
- **FR-006**: System MUST not reveal whether an email address exists in the system when login fails
- **FR-007**: System MUST redirect authenticated users to role-appropriate landing pages (employees to leave dashboard, approvers to portal calendar)
- **FR-007a**: System MUST lock accounts for 30 minutes after 5 failed login attempts within a 15-minute window and track failed attempts per account

**Email Verification**

- **FR-008**: System MUST send a verification email with a unique token immediately upon account creation
- **FR-009**: System MUST mark accounts as verified when users click the verification link with a valid token
- **FR-010**: System MUST allow users to request a new verification email if the previous one expired or was not received
- **FR-011**: System MUST set a verification token expiration period of 48 hours
- **FR-012**: System MUST handle already-verified accounts gracefully when verification links are clicked again
- **FR-013**: System MUST record the verification timestamp when an account is verified

**Password Management**

- **FR-014**: System MUST provide a password reset mechanism that sends a secure reset link via Slack DM
- **FR-015**: System MUST validate that password reset tokens are valid and not expired (1 hour expiration) before allowing password changes
- **FR-016**: System MUST mark password reset tokens as used after a successful password reset
- **FR-017**: System MUST enforce password complexity requirements (minimum 8 characters, mix of uppercase, lowercase, numbers)
- **FR-018**: System MUST allow authenticated users to change their password by providing their current password
- **FR-019**: System MUST invalidate all active sessions when a password is changed (except the session performing the change)
- **FR-020**: System MUST record the IP address and timestamp for all password reset requests

**Role-Based Access Control**

- **FR-021**: System MUST implement four distinct roles: Employee (role 1), HR Approver (role 2), Lead Approver (role 3), and PM Approver (role 4)
- **FR-022**: System MUST support both primary and secondary role assignments for users
- **FR-023**: System MUST grant permissions based on both primary and secondary roles using Spatie Laravel Permission
- **FR-024**: System MUST restrict access to approval queues based on user roles (employees cannot access)
- **FR-025**: System MUST restrict access to account management features to HR Approver role and above
- **FR-026**: System MUST restrict access to bulk approval actions to PM Approver role only
- **FR-027**: System MUST restrict access to the calendar portal to approver roles (HR, Lead, PM)
- **FR-028**: System MUST prevent users from escalating their own permissions without proper authorization

**User Registration & Account Management**

- **FR-029**: System MUST allow HR Approvers to create new employee accounts with email, name, hire date, role, and Slack ID
- **FR-030**: System MUST validate email addresses are in proper format (any valid email domain accepted)
- **FR-031**: System MUST validate Slack IDs in real-time against the Slack API during account creation
- **FR-032**: System MUST prevent duplicate email addresses in the system
- **FR-033**: System MUST create accounts in "for_verification" status (status code 2) initially
- **FR-034**: System MUST allow HR Approvers to activate accounts, changing status to "active" (status code 1)
- **FR-035**: System MUST allow HR Approvers to deactivate accounts with a mandatory reason, changing status to "deactivated" (status code 0)
- **FR-036**: System MUST allow HR Approvers to reactivate deactivated accounts
- **FR-037**: System MUST log all account status changes (activation/deactivation) with timestamp, performing user, IP address, and reason
- **FR-038**: System MUST prevent HR Approvers from deactivating their own account
- **FR-039**: System MUST add newly registered users to the designated Slack workspace channel automatically

**Profile Management**

- **FR-040**: System MUST allow employees to view their profile information including name, email, hire date, and role
- **FR-041**: System MUST require employees to set their default approvers (HR, Team Lead, PM) by selecting from users with appropriate roles before filing their first leave request
- **FR-042**: System MUST store default approvers as structured data (not plain text)
- **FR-043**: System MUST allow employees to change their password from their profile with current password verification
- **FR-044**: System MUST allow employees to refresh their Slack display name from their profile, fetching current name from Slack API
- **FR-045**: System MUST allow HR Approvers to update employee hire dates through the admin interface

**Slack Integration**

- **FR-046**: System MUST validate Slack IDs against the Slack API before saving to the database
- **FR-047**: System MUST send password reset links via Slack DM using the Slack API
- **FR-048**: System MUST add new users to the Slack workspace channel using the Slack admin API
- **FR-049**: System MUST fetch and update user display names from Slack using the Slack users.profile.get API
- **FR-050**: System MUST handle Slack API failures gracefully without blocking critical operations like account creation
- **FR-051**: System MUST store Slack Bot OAuth token and webhook URL securely in configuration

**Audit Logging**

- **FR-052**: System MUST log all account activation events with user ID, timestamp, performing user, IP address, and reason
- **FR-053**: System MUST log all account deactivation events with user ID, timestamp, performing user, IP address, and reason
- **FR-054**: System MUST log all password reset requests with user ID, timestamp, and IP address
- **FR-055**: System MUST provide HR Approvers with access to account audit logs for any employee
- **FR-056**: System MUST retain audit logs indefinitely for compliance purposes

### Key Entities

- **User**: Represents an employee in the system with authentication credentials, personal information (name, email, hire date), account status (for_verification, active, deactivated), verification status, primary role, optional secondary role, Slack ID, and default approvers (HR, Team Lead, PM as structured data). Related to roles and audit logs.

- **Role**: Represents a system role (Employee, HR Approver, Lead Approver, PM Approver) with associated permissions. Users have one primary role and optionally one secondary role. Roles are managed through Spatie Laravel Permission.

- **Permission**: Represents a specific system capability (e.g., "approve_leave", "manage_accounts", "view_portal") managed by Spatie Laravel Permission. Permissions are assigned to roles, and users inherit permissions from their assigned roles.

- **Email Verification Token**: Represents a time-limited token sent to users for email verification. Contains a unique token string, associated user, creation timestamp, and expiration timestamp. Marked as used when clicked.

- **Password Reset Token**: Represents a time-limited token for password reset requests. Contains a unique token string, associated user, creation timestamp, expiration timestamp, and IP address of requester. Marked as used after successful password reset.

- **Account Audit Log**: Represents a record of account status changes (activation/deactivation). Contains the affected user, action type (activate/deactivate), performing user, timestamp, IP address, and reason for the change.

## Success Criteria *(mandatory)*

### Measurable Outcomes

- **SC-001**: Users can complete login with valid credentials in under 5 seconds
- **SC-002**: New employee accounts can be created and verified in under 3 minutes end-to-end
- **SC-003**: 100% of authentication attempts correctly enforce role-based access control (verified through automated testing)
- **SC-004**: Password reset completion rate reaches 95% or higher within 10 minutes of request
- **SC-005**: Zero unauthorized access incidents to role-restricted features during initial deployment period
- **SC-006**: HR account management tasks (create, activate, deactivate) complete in under 2 minutes each
- **SC-007**: All account status changes are logged with 100% accuracy for audit compliance
- **SC-008**: Slack integration (ID validation, channel addition) succeeds for 95% or more of account creations
- **SC-009**: Users with multiple roles can access all appropriate features without requiring role switching
- **SC-010**: Failed login attempts provide no information about whether the email exists (verified through security testing)
