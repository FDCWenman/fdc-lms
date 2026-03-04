# Logo Update - 40°C Branding

**Date**: March 4, 2026  
**Feature**: 001-auth-revamp

## Changes Made

### 1. Logo Components Updated ✅
- **AppLogoIcon.vue**: Replaced with 40°C logo design (orange "4" + red "°C")
- **AppLogo.vue**: Updated to show "FDCLeave" with "Leave Management" subtitle
- **favicon.svg**: Created new SVG favicon with 40°C logo

### 2. Application Name ✅
- **config/app.php**: Changed default app name from "Laravel" to "FDCLeave"

### 3. Environment Configuration Required

**Action needed**: Update your `.env` file to set the application name:

```env
APP_NAME="FDCLeave"
```

This will update:
- Browser tab titles
- Email notifications
- Application header
- All references to the app name throughout the system

### 4. Additional Branding Locations

The 40°C logo now appears in:
- ✅ Main application header (AppHeader.vue)
- ✅ Sidebar logo (AppLogo.vue)  
- ✅ Authentication pages (AuthSplitLayout, AuthCardLayout, AuthSimpleLayout)
- ✅ Browser favicon (favicon.svg)

### 5. Logo Design Details

The logo uses Forty Degrees Celsius Inc. brand colors:
- **Orange (#FF8C00)**: Number "4"
- **Crimson Red (#DC143C)**: Degree symbol "°" and letter "C"

### Visual Hierarchy
```
FDCLeave
Leave Management
```

Primary brand name with descriptive subtitle for context.

## Testing

To see the changes:
1. Refresh your browser (clear cache if needed: Cmd+Shift+R / Ctrl+Shift+R)
2. Check the favicon in your browser tab
3. View the logo in the application header
4. Check authentication pages for consistent branding

## Notes

- The SVG logo is resolution-independent and works well at all sizes
- The logo maintains the Forty Degrees Celsius Inc. brand identity
- All logo references use the centralized components for easy future updates
