# Leave Management System (LMS) Constitution
## Forty Degrees Celsius Inc.

## Core Principles

### I. Approval Workflow Integrity (NON-NEGOTIABLE)
Every leave request must follow a defined approval chain without bypasses:
- Leave requests must be created by employees only (no proxy submissions except for authorized HR roles)
- Approval hierarchy must be enforced: Direct Manager → Department Head → HR (if applicable)
- Status transitions must be atomic and auditable (Pending → Approved/Rejected → Processed)
- Retroactive changes require explicit justification and higher-level approval
- No direct database manipulation of approval states in production

### II. Data Consistency & Audit Trail
All leave-related actions must maintain complete audit trails:
- Every status change must record: Who, What, When, Why (comment/reason)
- Leave balance calculations must be transactional and verifiable
- Soft deletes required for all leave records (no physical deletion)
- Audit logs must be immutable and stored separately from main application data
- Historical data integrity must be maintained for at least 7 years (or per local labor laws)

### III. Permission-Based Access Control
Access to leave data follows principle of least privilege:
- Employees can only view/create their own leave requests
- Managers can view/approve leaves for direct reports only
- HR can access all leaves with read/write permissions
- Department heads can view department-wide leave data
- System administrators cannot modify leave data without proper authorization trail
- Role-based permissions must be validated at both UI and API layers

### IV. Leave Balance Accuracy
Leave balances must always reflect the true state:
- Balance calculations must account for: Annual allocation, Accruals, Used leaves, Pending approvals, Carry-forwards, Adjustments
- Balance updates must be atomic (all-or-nothing transactions)
- Negative balances require explicit approval workflow
- Balance snapshots must be taken at fiscal year boundaries
- Real-time balance checks required before leave approval

### V. Test-First Development (NON-NEGOTIABLE)
All business-critical features must be test-driven:
- TDD mandatory for: Leave calculation logic, Approval workflows, Balance updates, Permission checks, Date validations
- Tests written → User approved → Tests fail → Implementation → Tests pass
- Feature tests required for complete user workflows
- Unit tests required for all service classes and actions
- Integration tests for external calendar/notification services

### VI. Visual Documentation (MANDATORY)
All specifications and plans must include visual diagrams:
- **Mermaid diagrams required** for all feature specifications and implementation plans
- Flow diagrams must show: Authentication flows, Business process workflows, State transitions
- Data model diagrams (ERD) must show: Entities, Relationships, Key attributes
- Architecture diagrams must show: System components, External integrations, Data flow
- Diagrams must be technology-agnostic in specifications, implementation-specific in plans
- All diagrams must be kept up-to-date with implementation changes

### VII. Date & Time Handling Standards
Consistent handling of dates, time zones, and durations:
- All dates stored in UTC in database
- Display dates in user's timezone
- Leave duration calculated in working days (not calendar days)
- Weekend and holiday exclusions must be configurable
- Half-day, hourly leave support must be explicit in data model
- Date ranges must be validated (start ≤ end, no overlaps with approved leaves)

### VIII. Notification Reliability
Critical notifications must be delivered reliably:
- Leave request submitted → Notify approver(s)
- Leave approved/rejected → Notify employee
- Leave about to expire → Notify employee (configurable lead time)
- Failed notifications must be queued for retry
- In-app notifications + email notifications (fallback to email only if in-app fails)
- Notification preferences must be user-configurable

## Business Rules & Constraints

### Leave Types & Policies
- Each leave type (Annual, Sick, Parental, etc.) must have clearly defined rules:
  - Allocation amount and frequency
  - Accrual rate (if applicable)
  - Maximum carry-forward limit
  - Notice period requirements
  - Documentation requirements (e.g., medical certificate for sick leave)
  - Blackout periods (if any)
- Leave policies must be configurable per department/location/employee type
- Policy changes must not retroactively affect approved leaves

### Calendar Integration
- Public holidays must be maintained in a separate table, configurable per location
- Working days/hours must be configurable (e.g., 5-day vs 6-day work week)
- Leave calendar must sync with external calendar systems (Google Calendar, Outlook)
- Team calendar view must respect permission boundaries

### Reporting & Analytics
- Standard reports required:
  - Leave balance report (per employee, per department)
  - Leave utilization report (trends, patterns)
  - Pending approvals report (for managers)
  - Compliance report (labor law adherence)
- All reports must respect data access permissions
- Export functionality required (CSV, PDF, Excel)

## Security Requirements

### Data Protection
- Personal leave data is sensitive and must be encrypted at rest
- API endpoints must be authenticated and authorized
- CSRF protection required for all state-changing operations
- Rate limiting on API endpoints to prevent abuse
- No PII in logs or error messages

### Compliance
- GDPR/Data Privacy compliance: Right to access, Right to be forgotten (after legal retention period), Data portability
- Audit logs for all compliance-related actions
- Regular security audits of permission system

## Development Workflow

### Code Standards
- PSR-12 coding standards for PHP (enforced by Laravel Pint)
- TypeScript strict mode enabled
- ESLint + Prettier for frontend code
- All business logic in dedicated Action/Service classes
- Controllers should be thin (validation + action invocation)

### Testing Requirements
- Minimum 80% code coverage for business logic
- All API endpoints must have feature tests
- Browser tests for critical user workflows (Dusk or similar)
- Load testing for balance calculation under concurrent requests

### Code Review Process
- All changes require at least one approval
- Business logic changes require approval from product owner or senior developer
- Database migrations must be reviewed for backward compatibility
- No commits directly to main/production branches

### Deployment Standards
- Zero-downtime deployments required
- Database migrations must be backward compatible
- Feature flags for major changes
- Rollback plan required for every deployment
- Post-deployment smoke tests mandatory

## Governance

This constitution supersedes all other development practices and guidelines.

### Amendment Process
- Amendments require: Written proposal with rationale, Review by tech lead + product owner, Team discussion and consensus, Documentation in changelog, Migration plan if affecting existing code

### Enforcement
- All pull requests must verify constitutional compliance
- Non-compliance must be justified and documented
- Code reviews must check for: Test coverage, Permission checks, Audit trail completeness, Data consistency safeguards, Visual documentation presence
- Specifications without required Mermaid diagrams must be rejected

### Exceptions
- Emergency hotfixes may bypass test-first requirement (with follow-up test addition within 24 hours)
- Security patches may be expedited through review process
- All exceptions must be documented in constitution-changelog.md

**Version**: 1.1.0 | **Ratified**: 2026-03-04 | **Last Amended**: 2026-03-05

### Changelog
- **v1.1.0 (2026-03-05)**: Added Section VI - Visual Documentation requirement for Mermaid diagrams in all specifications and plans
- **v1.0.0 (2026-03-04)**: Initial constitution ratified
