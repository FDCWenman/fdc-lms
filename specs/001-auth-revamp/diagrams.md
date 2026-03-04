# Architecture Diagrams: Authentication & Registration System

**Feature**: 001-auth-revamp  
**Created**: 2026-03-04

## Authentication Flow

```mermaid
sequenceDiagram
    actor User
    participant UI as Vue UI
    participant API as Laravel API
    participant Auth as Auth Service
    participant DB as Database
    participant Slack as Slack API
    
    User->>UI: Enter email + password
    UI->>API: POST /login
    API->>Auth: Authenticate credentials
    Auth->>DB: Verify user exists & active
    
    alt Account locked (5 failed attempts)
        DB-->>Auth: Account locked
        Auth-->>API: 423 Locked
        API-->>UI: Show lockout message
        UI-->>User: "Account locked for 30 min"
    else Account unverified
        DB-->>Auth: Unverified
        Auth-->>API: 403 Forbidden
        API-->>UI: Show verification prompt
        UI-->>User: "Please verify email"
    else Account deactivated
        DB-->>Auth: Deactivated
        Auth-->>API: 403 Forbidden
        API-->>UI: Show deactivation message
        UI-->>User: "Account inactive"
    else Valid credentials
        Auth->>DB: Create session token
        Auth->>DB: Reset failed login counter
        Auth-->>API: 200 OK + token
        API-->>UI: Return token + user data
        UI->>UI: Store token
        UI->>UI: Redirect by role
        UI-->>User: Show dashboard/portal
    else Invalid credentials
        Auth->>DB: Increment failed attempts
        Auth-->>API: 401 Unauthorized
        API-->>UI: Generic error
        UI-->>User: "Invalid credentials"
    end
```

## Password Reset Flow

```mermaid
sequenceDiagram
    actor User
    participant UI as Vue UI
    participant API as Laravel API
    participant Token as Token Service
    participant DB as Database
    participant Slack as Slack API
    
    User->>UI: Click "Forgot Password"
    UI->>API: POST /password/reset-request
    API->>DB: Find user by email
    
    alt User exists
        API->>Token: Generate reset token (1hr expiry)
        Token->>DB: Store token + IP
        Token-->>API: Token created
        API->>Slack: Send DM with reset link
        Slack-->>API: Message sent
        API-->>UI: 200 OK (generic message)
        UI-->>User: "Check Slack DM"
        
        Note over User,Slack: User clicks link in Slack
        
        User->>UI: Click reset link
        UI->>API: GET /password/reset/{token}
        API->>Token: Validate token
        Token->>DB: Check token exists & not expired
        
        alt Token valid
            Token-->>API: Valid
            API-->>UI: Show reset form
            UI-->>User: Enter new password
            User->>UI: Submit new password
            UI->>API: POST /password/reset
            API->>Token: Mark token as used
            API->>DB: Update password hash
            API->>DB: Invalidate all other sessions
            API-->>UI: 200 OK
            UI-->>User: "Password updated"
        else Token expired/invalid
            Token-->>API: Invalid
            API-->>UI: 400 Bad Request
            UI-->>User: "Request new reset link"
        end
    else User not found
        API-->>UI: 200 OK (generic message)
        UI-->>User: "Check Slack DM"
        Note over API,UI: Don't reveal if email exists
    end
```

## Role-Based Access Control

```mermaid
graph TB
    subgraph "Roles & Permissions"
        Employee[Employee<br/>Role ID: 1]
        HR[HR Approver<br/>Role ID: 2]
        Lead[Lead Approver<br/>Role ID: 3]
        PM[PM Approver<br/>Role ID: 4]
    end
    
    subgraph "Permissions"
        P1[view_own_leaves]
        P2[file_leave]
        P3[approve_hr_level]
        P4[manage_accounts]
        P5[approve_lead_level]
        P6[view_portal]
        P7[approve_pm_level]
        P8[bulk_actions]
    end
    
    subgraph "Features"
        F1[Leave Dashboard]
        F2[File Leave Request]
        F3[HR Approval Queue]
        F4[Account Management]
        F5[Lead Approval Queue]
        F6[Calendar Portal]
        F7[PM Approval Queue]
        F8[Bulk Approve/Decline]
    end
    
    Employee --> P1
    Employee --> P2
    P1 --> F1
    P2 --> F2
    
    HR --> P1
    HR --> P2
    HR --> P3
    HR --> P4
    HR --> P6
    P3 --> F3
    P4 --> F4
    P6 --> F6
    
    Lead --> P1
    Lead --> P2
    Lead --> P5
    Lead --> P6
    P5 --> F5
    
    PM --> P1
    PM --> P2
    PM --> P6
    PM --> P7
    PM --> P8
    P7 --> F7
    P8 --> F8
    
    style Employee fill:#e1f5ff
    style HR fill:#fff4e1
    style Lead fill:#f0e1ff
    style PM fill:#ffe1e1
```

## Multi-Role Support

```mermaid
graph LR
    subgraph "User Account"
        U[User: Jane Doe]
        PR[Primary Role:<br/>HR Approver]
        SR[Secondary Role:<br/>Lead Approver]
    end
    
    subgraph "Combined Permissions"
        HP[HR Permissions:<br/>• approve_hr_level<br/>• manage_accounts<br/>• view_portal]
        LP[Lead Permissions:<br/>• approve_lead_level]
        CP[Combined:<br/>All Above Permissions]
    end
    
    subgraph "Accessible Features"
        F1[HR Approval Queue]
        F2[Account Management]
        F3[Lead Approval Queue]
        F4[Calendar Portal]
    end
    
    U --> PR
    U --> SR
    PR --> HP
    SR --> LP
    HP --> CP
    LP --> CP
    CP --> F1
    CP --> F2
    CP --> F3
    CP --> F4
    
    style U fill:#d4edff
    style PR fill:#fff4d4
    style SR fill:#f4d4ff
    style CP fill:#d4ffd4
```

## Account Management Flow

```mermaid
stateDiagram-v2
    [*] --> ForVerification: HR creates account
    ForVerification --> Active: Email verified
    Active --> Deactivated: HR deactivates<br/>(with reason)
    Deactivated --> Active: HR reactivates<br/>(with reason)
    
    ForVerification --> Deactivated: HR deactivates<br/>before verification
    
    note right of ForVerification
        Status Code: 2
        Can't log in
        Verification email sent
    end note
    
    note right of Active
        Status Code: 1
        Can log in
        Full access
    end note
    
    note right of Deactivated
        Status Code: 0
        Can't log in
        Logged to audit trail
    end note
```

## Email Verification Flow

```mermaid
sequenceDiagram
    actor HR
    actor Employee
    participant API as Laravel API
    participant Mail as Mail Service
    participant DB as Database
    participant Slack as Slack API
    
    HR->>API: POST /admin/users (create account)
    API->>DB: Validate Slack ID
    API->>Slack: users.info (validate)
    Slack-->>API: User valid
    API->>DB: Create user (status: for_verification)
    API->>DB: Generate verification token (48hr)
    API->>Mail: Send verification email
    Mail-->>Employee: Email with link
    API->>Slack: admin.conversations.invite
    Slack-->>API: User added to channel
    API-->>HR: 201 Created
    
    Note over Employee,Mail: Employee receives email
    
    Employee->>API: GET /verify-email/{token}
    API->>DB: Find token
    
    alt Token valid & not expired
        API->>DB: Mark user as verified
        API->>DB: Set verified_at timestamp
        API->>DB: Delete verification token
        API-->>Employee: Redirect to login
    else Token expired
        API-->>Employee: Show "request new link"
        Employee->>API: POST /verification/resend
        API->>DB: Generate new token
        API->>Mail: Send new email
        Mail-->>Employee: New verification email
        API-->>Employee: "Check email"
    else Already verified
        API-->>Employee: "Already verified, login"
    end
```

## Session Management

```mermaid
graph TD
    A[User Logs In] --> B[Create Session Token]
    B --> C[Store Token in DB]
    C --> D[Return Token to Client]
    D --> E[Client Stores Token]
    
    E --> F{User Activity?}
    F -->|Active| G[Update last_activity]
    F -->|Idle 8 hours| H[Session Expires]
    G --> I{24 hours passed?}
    I -->|Yes| H
    I -->|No| F
    
    H --> J[Redirect to Login]
    
    K[User Logs Out] --> L[Delete Session Token]
    L --> J
    
    M[Password Changed] --> N[Delete All Other Sessions]
    N --> O[Keep Current Session]
    
    style H fill:#ffcccc
    style J fill:#ffcccc
```

## Data Model Entity Relationships

```mermaid
erDiagram
    User ||--o{ AccountAuditLog : "has audit history"
    User ||--o{ PasswordResetToken : "can request"
    User ||--o{ EmailVerificationToken : "needs verification"
    User ||--o{ FailedLoginAttempt : "tracks failures"
    User }o--|| Role : "has primary role"
    User }o--o| Role : "has secondary role"
    Role ||--o{ Permission : "has many"
    User ||--o{ PersonalAccessToken : "has sessions"
    
    User {
        bigint id PK
        string email UK
        string password
        string name
        date hired_date
        bigint primary_role_id FK
        bigint secondary_role_id FK
        string slack_id UK
        json default_approvers
        tinyint status
        timestamp verified_at
        timestamp created_at
        timestamp updated_at
    }
    
    Role {
        bigint id PK
        string name UK
        string slug UK
        timestamp created_at
    }
    
    Permission {
        bigint id PK
        string name UK
        string slug UK
        timestamp created_at
    }
    
    AccountAuditLog {
        bigint id PK
        bigint user_id FK
        string action
        bigint performed_by FK
        string ip_address
        text reason
        timestamp created_at
    }
    
    PasswordResetToken {
        string token PK
        bigint user_id FK
        string ip_address
        boolean used
        timestamp expires_at
        timestamp created_at
    }
    
    EmailVerificationToken {
        string token PK
        bigint user_id FK
        timestamp expires_at
        timestamp created_at
    }
    
    FailedLoginAttempt {
        bigint id PK
        string email
        string ip_address
        timestamp attempted_at
        timestamp locked_until
    }
    
    PersonalAccessToken {
        bigint id PK
        bigint user_id FK
        string token UK
        timestamp last_used_at
        timestamp expires_at
        timestamp created_at
    }
```
