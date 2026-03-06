# Quick Start Guide - Testing Registration

## Fixed Issues

1. ✅ **Flux Directives**: Changed `@fluxStyles` and `@fluxScripts` to `@flux:styles` and `@flux:scripts`
2. ✅ **Registration Route**: Updated to `/auth/register` (requires HR role)
3. ✅ **Test Users**: Created HR and employee users with proper roles

## Test Users Created

### HR Admin (Can Register New Users)
- **Email**: `hr@example.com`
- **Password**: `password`
- **Role**: HR
- **Access**: Can access registration at `/auth/register`

### Regular Employee
- **Email**: `employee@example.com`
- **Password**: `password`
- **Role**: Employee
- **Access**: Can access leaves page at `/leaves`

## How to Test Registration

### Step 1: Login as HR
1. Navigate to: `http://localhost/login`
2. Login with:
   - Email: `hr@example.com`
   - Password: `password`

### Step 2: Access Registration
Once logged in as HR, you'll see "Register User" in the navbar:
- Click "Register User" in the navbar, or
- Navigate directly to: `http://localhost/auth/register`

### Step 3: Register a New User
Fill in the registration form:
- **Name**: John Doe
- **Email**: john@example.com
- **Slack ID**: U123456789 (any valid Slack ID format)
- **Role**: Select from employee, hr, team-lead, or project-manager

**Note**: In local environment (APP_ENV=local), Slack validation is bypassed, so any Slack ID format will work.

### Step 4: Verify Registration
After registration:
1. User is created with `status=2` (pending verification)
2. In local environment, verification link is logged (check `storage/logs/laravel.log`)
3. In production, link would be sent via Slack DM

## Registration Flow Summary

```
HR Login → Navigate to /auth/register → Fill Form → Submit
  ↓
User Created (status=2, pending verification)
  ↓
Verification Link Generated
  ↓
Local: Link logged to storage/logs/laravel.log
Production: Link sent via Slack DM
  ↓
User clicks verification link (/auth/verify?token=xxx)
  ↓
Account activated (status=1)
  ↓
User can now login
```

## Available Routes

### Guest Routes
- `/login` - Login page
- `/auth/verify?token=xxx` - Verification link
- `/auth/request-verification` - Request new verification link

### Authenticated Routes (HR Only)
- `/auth/register` - Register new users (HR only)

### Authenticated Routes (All)
- `/dashboard` - Dashboard
- `/leaves` - Employee leave requests (employee role)
- `/portal` - Approval portal (hr|team-lead|project-manager roles)

## Troubleshooting

### Can't see "Register User" link?
- Make sure you're logged in as HR user (hr@example.com)
- Check that user has HR role assigned

### @flux:styles appearing as text?
- Fixed in latest commit
- Make sure to refresh your browser (Ctrl+F5 or Cmd+Shift+R)

### Registration form not loading?
- Verify you're logged in as HR user
- Check browser console for errors
- Verify route exists: `docker exec addfc01e309b php artisan route:list | grep register`

### Slack validation errors in production?
- Set APP_ENV=local to bypass Slack validation for testing
- Or configure proper Slack tokens in .env:
  - SLACK_BOT_TOKEN
  - SLACK_API_USERID
  - SLACK_CHANNEL_ID
  - SLACK_HIGH_TOKEN

## Environment Configuration

For **local testing** (current setup):
```env
APP_ENV=local
# Slack validation bypassed
```

For **production**:
```env
APP_ENV=production
SLACK_BOT_TOKEN=xoxb-your-token
SLACK_API_USERID=U01234567890
SLACK_CHANNEL_ID=C01234567890
SLACK_HIGH_TOKEN=xoxp-your-token
```

## Next Steps

After fixing these issues, you should:
1. Clear your browser cache
2. Login as HR user (hr@example.com / password)
3. Click "Register User" in the navbar
4. Test the registration flow

The registration feature is fully implemented and working!
