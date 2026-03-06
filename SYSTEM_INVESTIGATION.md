# FDCLeave — Leave Management System: Feature Investigation

> **Purpose**: Comprehensive feature audit for Laravel + Livewire revamp planning  
> **Investigated**: March 4, 2026  
> **Last Updated**: March 6, 2026  
> **Current Stack**: CakePHP 2.x + MySQL  
> **Target Stack**: Laravel 12 + Livewire 4 + Flux UI (Free)  
> **Implementation Status**: Authentication System (Phase 1-7 Complete)

---

## Implementation Progress

### ✅ Completed (Phase 1-8: Authentication System)
- **User Registration**: Public access with first_name, middle_name, last_name, hired_date, password fields
- **Slack Integration**: Real-time Slack ID validation, verification via DM, environment-aware Slack toggling
- **Access Control**: Role-based middleware, Spatie permissions, automatic employee role assignment
- **Password Reset**: Token-based password reset with 1-hour expiration, Slack notifications
- **Testing Infrastructure**: Comprehensive PHPUnit tests, Laravel Dusk E2E tests, GitHub Actions CI/CD
- **User Model**: Enhanced with separate name fields (first/middle/last), hired date, verification status
- **Token Management**: CleanupExpiredTokensJob for automatic token cleanup (daily at 2:00 AM)
- **Factory Classes**: VerificationTokenFactory with expires_at field and test states
- **Documentation**: Complete README.md with authentication flows, configuration, testing guide
- **Code Quality**: Laravel Pint formatting, PSR-12 compliance

### 🎯 Next Phase (Leave Application Features)
- Leave request creation
- Leave types and policies
- Leave balance tracking
- Approval workflow
- Calendar integration

### ⏳ Future Phases
- Reports & analytics
- Team calendar portal
- Advanced leave credits system

---

## Table of Contents

1. [System Overview](#1-system-overview)
2. [User Roles & Access Control](#2-user-roles--access-control)
3. [Authentication & Account Management](#3-authentication--account-management)
4. [Leave Application Features](#4-leave-application-features)
5. [Approval Workflow](#5-approval-workflow)
6. [Leave Credits System](#6-leave-credits-system)
7. [Calendar Portal](#7-calendar-portal)
8. [Reports & Summaries](#8-reports--summaries)
9. [App Settings & Configuration](#9-app-settings--configuration)
10. [Slack Integration](#10-slack-integration)
11. [Cron Jobs / Scheduled Tasks](#11-cron-jobs--scheduled-tasks)
12. [Database Schema Summary](#12-database-schema-summary)
13. [Revamp Notes for Laravel + Livewire](#13-revamp-notes-for-laravel--livewire)

---

## 1. System Overview

FDCLeave is an internal leave management system for NativeCamp employees. It handles the full lifecycle of leave requests — from filing, multi-step approval, to reporting — with deep Slack integration for notifications and display name updates.

| Attribute | Current Value |
|---|---|
| Framework | CakePHP 2.x |
| Database | MySQL |
| Frontend | jQuery + Bootstrap 5 + FullCalendar 6.1.8 |
| Notification System | Slack (Webhooks + Bot API) |
| File Storage | Local (`app/webroot/attachments/`) |
| Auth Method | Email + Blowfish-hashed password |
| Fiscal Year / Cutoff | May 1 → April 30 |

---

## 2. User Roles & Access Control

| Role ID | Role Name | Key Permissions |
|---|---|---|
| 1 | Employee | File/edit/cancel own leaves; view own leave list and reports |
| 2 | HR Approver | First-level approval; offline leave entry; account management; deactivate/activate users |
| 3 | Lead (TL) Approver | Second-level approval for their team |
| 4 | PM Approver | Third-level approval; bulk approve/decline; can cancel any leave |

**Secondary Role**: A user can hold a dual role via `secondary_role_id`. For example, HR can also act as TL Approver, and TL can also act as PM Approver.

**Access Rules**:
- Employees are blocked from approval, admin, portal, and settings pages
- Approvers are redirected to `/portal` after login; employees go to `/leaves`
- All admin/approval pages are role-gated in middleware

---

## 3. Authentication & Account Management

### Authentication
- Email + password login (Blowfish/bcrypt hashed)
- Account must be `active` AND `verified` to log in
- Account statuses: `for_verification (2)` → `active (1)` → `deactivated (0)`

### Email Verification
- On registration, a verification link is sent via **Slack DM** (not email)
- Account activates on link click (`verified_at` timestamp set)
- Verification tokens expire after 24 hours
- In local environment with `ALLOW_SLACK_LOCAL=0`, verification URL is logged instead of sent via Slack

### Password Management
- **Forgot Password**: Sends a reset link via **Slack DM** (not email)
- **Password Reset**: Token-based form; link is consumed (marked `modified_at`) after use
- **Change Password**: Available from `/profile` with current password confirmation

### User Registration
- Admin-driven registration (UPDATED: Now publicly accessible)
- **Registration Fields**: first_name, middle_name (optional), last_name, email, password, slack_id, hired_date
- Slack ID is validated live against the Slack API during registration (real-time on blur event)
- User is added to the Slack workspace channel upon registration (if `ALLOW_SLACK_LOCAL=1` in local env)
- **Default Role**: Automatically assigned "employee" role
- **Status**: Created with `for_verification` status until Slack DM verification link is clicked

### Profile Management
- User can update their **default approvers** (HR, TL, PM) stored as JSON
- Slack display name can be refreshed from the Slack API

### Account Administration (HR/Admin)
- **Activate / Deactivate** accounts with a reason
- All activation/deactivation events logged in `user_fdc_logs` (log type, doer, IP address)
- **Offline Leave Entry**: HR/Admin can manually create already-approved leaves for an employee, bypassing the normal approval flow (marked `[manual_entry]` in reason field)

---

## 4. Leave Application Features

### Leave Types (10 types)

| ID | Type | Date Restriction | Notes |
|---|---|---|---|
| 1 | Sick Leave (SL) | Past dates only | No advance filing required |
| 2 | Vacation Leave (VL) | Future dates | Subject to advance days & cutoff |
| 3 | Undertime – Scheduled | Same day only | Time range required; min 1 hour |
| 4 | Bereavement Leave | Past/current | — |
| 5 | Maternity Leave | Future dates | — |
| 6 | Paternity Leave | Past/current | — |
| 7 | Emergency Leave | Past dates only | — |
| 8 | Holiday Leave | Must fall on registered holidays | Validates against holidays table |
| 9 | Undertime – Urgent | Same day only | Must be filed ≥30 min before start time |
| 10 | Unpaid Leave | Past/current | Configurable per type |

### Filing a Leave Request
- Select leave type → date range (or time range for undertime) → reason → attach proof (if required)
- Real-time form validation (AJAX) for date restrictions, credit limits, and cutoff rules
- Date picker disables unavailable dates dynamically based on cutoff periods and holidays
- Notification sent to Slack upon submission

### Editing a Leave Request
- Only pending leaves (`For HR Approval` status) can be edited
- Edit uses the same form with same validations
- Slack notification sent on edit

### Cancelling a Leave Request
- Employees can cancel their own pending requests
- A cancel policy per leave type controls whether cancellation is allowed
- Cancellation reason recorded in `cancelled_declined_leaves` table

### Multi-File Attachments
- Multiple files can be uploaded per leave request
- Files are stored locally with MD5-hashed filenames
- Filenames stored as a JSON array in the database

### Proof Requirements
- Configured per leave type: `proof_required` (yes/no) + `num_proof` (free days before proof is needed)
- System checks total consumed leave days vs the proof-free threshold
- Example: after 2 days of SL, a medical certificate is required

### Cutoff / Advance Filing Rules
- Per-leave-type "advance days" requirement (e.g. VL must be filed X working days in advance)
- Per-leave-type cutoff window: filings are blocked within a specific calendar window each month
- HR role is exempt from advance filing and cutoff restrictions when filing on behalf of others

---

## 5. Approval Workflow

### Three-Stage Approval Chain

```
Employee Files → [For HR Approval] → [For TL Approval] → [For PM Approval] → [Approved]
                        ↓                     ↓                    ↓
                    Declined              Declined              Declined
```

### Approval Actions
- **Approve**: Moves request to next stage; Slack notification sent at each stage
- **Decline**: Request marked declined; reason recorded; Slack notification sent
- **Bulk Approve/Decline**: PM Approver role can approve/decline multiple requests at once

### Approval View
- Approvers see only the queue relevant to their role
- Each request shows a full approval timeline (HR → TL → PM status indicators)
- Related/concurrent leaves from other employees are shown alongside

### Concurrent Leave Detection
- When viewing a leave request for approval, the system shows other employees who have overlapping approved leaves — to help approvers make informed decisions

---

## 6. Leave Credits System

### Credit Configuration
- Credits are configured monthly within the fiscal year (May–April)
- Per **month of hire** — newer employees get prorated credits based on their join month
- Credit types tracked: **Vacation Leave (VL)**, **Sick Leave (SL)**, **Emergency Leave (EL)**
- Configuration stores: `max_count` (monthly max) and `ctoc_count` (carry-over between cutoffs)

### Credit Enforcement
- System calculates total leaves consumed per type within the current cutoff period
- New leave request is rejected if it would exceed the credit limit
- VL uses a separate carry-over window (starts in April each year)

---

## 7. Calendar Portal

Available to approvers and HR (not employees).

- **Library**: FullCalendar 6.1.8 + Bootstrap 5.3
- **Views**: Month, Week (time grid), Day (time grid), List Week
- **Features**:
  - Color-coded events per leave type (10 colors)
  - Click a day → modal popup with a DataTable of all employees on leave that day
  - Click an event → filtered modal by leave type
  - "Go to date" date picker input
  - Loading spinner during API fetches
  - Weekends hidden; working hours displayed: 06:00–20:00; 30-min slot duration
  - Multi-day leaves are expanded across calendar days server-side

---

## 8. Reports & Summaries

### Generate Reports (`/generate-reports`)
- Filterable by: date range, leave type(s), status(es)
- Displays a full table of leave requests matching filters
- **CSV download** of filtered results

### Leave Summary by Status (`/leave-summary/status`)
- Per-employee totals grouped by leave status (pending, approved, cancelled, declined)
- Available to HR/Admin; employees see only their own data

### Leave Summary by Type (`/leave-summary/type`)
- Per-employee totals grouped by leave type within the current cutoff period
- Includes leave credit information (credits used vs. available)
- Available to HR/Admin; employees see only their own data

---

## 9. App Settings & Configuration

All settings are stored in a single `leave_app_settings` table as JSON blobs, broken into 4 sections:

### General Settings
- Cutoff date configuration
- Leave types subject to cutoff restrictions
- Daily cron announcement schedule time

### Notification Settings
- Per-event Slack alert recipient configuration
- 6 configurable notification events (new request, HR approved, TL approved, cancelled, declined, fully approved)
- Recipients configurable per event: All channel, Requestor, HR Approver, Lead, PM Approver

### Leave Settings (per leave type)
- Advance days required before filing
- Cancel policy (can employees cancel?)
- Proof requirements (`proof_required`, `num_proof` threshold)
- Allow unpaid toggle

### Leave Credits Settings
- Monthly maximum leave counts per type
- Carry-over counts per type
- Broken down by month of hire (for prorated new hire credits)

### Holiday Management
- Add/remove holidays from the General settings page
- Holidays stored in a dedicated table with date + description

---

## 10. Slack Integration

### Connection
- Slack Bot OAuth token (xoxb) for API calls
- Incoming Webhook URL for channel posts

### Slack API Methods Used

| Method | Purpose |
|---|---|
| `chat.postMessage` | Broadcast message to leave channel |
| `chat.postEphemeral` | DM a specific user (approver or requestor) |
| `users.info` | Validate Slack ID during registration |
| `users.profile.get` | Fetch current Slack display name |
| `users.profile.set` | Update display name with on-leave indicator |
| `admin.conversations.invite` | Add new user to Slack channel |

### Notification Events (6 types, all configurable)

| # | Trigger Event |
|---|---|
| 1 | Employee files new leave → For HR Approval |
| 2 | HR approves → For TL Approval |
| 3 | TL approves → For PM Approval |
| 4 | Leave cancelled (by employee or admin) |
| 5 | Leave declined (by any approver) |
| 6 | Leave fully approved (PM approved) |

### Slack Display Name Update (Cron-driven)
- On-leave employees get their Slack display name appended with the leave type abbreviation + date range
  - Example: `John Doe [VL 03/10–03/12]`
- **Undertime**: name updated 30 minutes before the scheduled start time; reset 30 minutes after end time
- **Full-day leaves**: name reset one day after `to_date`
- Resets handled automatically by the cron job

---

## 11. Cron Jobs / Scheduled Tasks

### Daily Leave Announcement (`CronDailyLeaveAnnouncement`)
- Posts a daily Slack message summarizing today's and the next business day's approved leaves
- Weekend-aware: skips Saturday/Sunday; next business day logic applied
- Holiday-aware: only includes Holiday Leave type on actual registered holidays
- Leave types included in announcement: VL, Scheduled UT, Urgent UT, Holiday

### Slack Display Name Updater (`CronDisplayNameUpdater`)
- Periodically updates Slack display names for employees currently on leave
- Handles both setting (on leave start) and resetting (on leave end) of display names
- Supports a `?reset=true` mode to force-reset all display names
- Runs on a configurable schedule defined in app settings

---

## 12. Database Schema Summary

| Table | Purpose |
|---|---|
| `users_fdc_leaves` | Users with roles, Slack ID, status, default approvers (JSON) |
| `roles` | 4 roles: Employee, HR, TL, PM |
| `leave_types` | 10 leave type definitions |
| `leave_requests` | All leave requests with status, date range, reason |
| `leave_request_approvals` | HR / TL / PM approval status + timestamps per request |
| `leave_request_attachments` | File attachments per request (JSON filename array) |
| `leave_app_settings` | App configuration (4 rows, JSON blob per section) |
| `holidays` | Registered holidays (date PK + description) |
| `cancelled_declined_leaves` | Audit log of cancelled/declined leaves with remarks |
| `fdc_leaves_forgot_password_requests` | Password reset tokens with IP tracking |
| `user_fdc_logs` | Account activation/deactivation audit log |

---

## 13. Revamp Notes for Laravel + Livewire

### What to Rebuild (Feature Checklist)

#### Authentication & Users
- [ ] Email + password auth (Laravel Fortify)
- [ ] Email verification flow
- [ ] Forgot password (consider: Slack DM vs email — currently Slack only)
- [ ] Role-based access control (4 roles + secondary role support)
- [ ] User registration with Slack ID validation
- [ ] Profile: change password, update default approvers
- [ ] Account activate/deactivate with audit logging

#### Leave Management
- [ ] Leave request CRUD (file, edit, cancel)
- [ ] 10 leave types with type-specific validation rules
- [ ] Date range picker with dynamically disabled dates
- [ ] Time range support for undertime types (Scheduled + Urgent)
- [ ] Multi-file attachment upload
- [ ] Proof requirement logic per leave type
- [ ] Advance filing and cutoff period enforcement
- [ ] Real-time form validation (Livewire reactive properties)
- [ ] Offline leave entry (HR bypass)

#### Approval Workflow
- [ ] Three-stage approval chain (HR → TL → PM)
- [ ] Approve / Decline with reason
- [ ] Bulk approve/decline (PM role)
- [ ] Concurrent leave detection in approval view
- [ ] Approval timeline component

#### Leave Credits
- [ ] Credits by leave type (VL, SL, EL)
- [ ] Prorated credits by month of hire
- [ ] Cutoff period carry-over logic
- [ ] Credit consumption tracking

#### Calendar Portal
- [ ] FullCalendar integration with Livewire
- [ ] Month/Week/Day/List views
- [ ] Color-coded events per leave type
- [ ] Day click → detail modal with DataTable
- [ ] Multi-day leave expansion API

#### Reports
- [ ] Filtered report generation (date range, type, status)
- [ ] CSV export
- [ ] Leave summary by status
- [ ] Leave summary by type with credit info

#### Settings (Admin)
- [ ] General settings (cutoff, holidays)
- [ ] Notification configuration per event (6 events)
- [ ] Per-leave-type settings (advance days, proof, cancel policy)
- [ ] Leave credits configuration per hire month

#### Slack Integration
- [ ] Incoming webhook notifications (6 event types)
- [ ] Bot API: DMs, profile updates
- [ ] Slack ID validation on registration
- [ ] Display name update logic (on-leave indicator)

#### Cron / Scheduled Tasks
- [ ] Laravel Scheduler for daily leave announcement
- [ ] Slack display name updater (set + reset logic)

### Architecture Recommendations

| Concern | Recommendation |
|---|---|
| Framework | Laravel 12 |
| Frontend | Livewire 4 (no separate API needed) |
| Navigation | wire:navigate for SPA-like navigation |
| UI Library | Flux UI (Livewire component library) |
| Calendar | FullCalendar with Alpine.js integration |
| File Storage | Laravel Storage (local or S3) |
| Auth | Laravel Fortify |
| Scheduler | Laravel Task Scheduling (`php artisan schedule:run`) |
| Queue | Laravel Queues for Slack notifications |
| Roles/Permissions | Spatie Laravel Permission |

### Key Complexity Areas to Design Carefully

1. **Leave credit calculation** — involves hire date, fiscal year cutoff, carry-over logic, and proration for new hires
2. **Date validation per leave type** — each of the 10 types has distinct rules; must be implemented server-side and reflected in Livewire components
3. **Cutoff period logic** — date ranges that block certain leave types; needs to be dynamically loaded in Livewire components for the date picker
4. **Three-stage approval with role overlap** — secondary roles mean a user can occupy multiple positions in the approval chain
5. **Slack display name cron** — timing-sensitive; must handle undertime (30-min window) and full-day leaves separately
6. **Multi-day leave expansion** — calendar portal needs server-side date expansion to show multi-day leaves across each calendar day
