# Payment Plugin Confirmation Pages - URL Navigation Fix

## Overview
This directory contains the confirmation page templates for Qvik and Revolut payment plugins with robust AJAX URL handling and redirect logic that works correctly in all Joomla/Solidres deployment scenarios.

## Files
- `confirmation.php` - Payment confirmation page template with JavaScript for AJAX processing and redirects

## Problem Solved
Previously, payment confirmation pages only worked when accessed via root menu items. When accessed through submenus or in hub/multisite contexts, AJAX calls and redirects would fail with 404 errors or lose navigation context.

## Solution
The confirmation pages now use robust JavaScript functions that:
1. **Preserve all URL path segments** - Works in root, submenu, and deeply nested menu structures
2. **Maintain hub/multisite context** - Preserves hub_id, site_id, and property_id parameters
3. **Keep menu context** - Maintains Itemid parameter for proper navigation
4. **Work with deep links** - Handles directly accessed booking URLs

## Technical Approach

### buildAjaxUrl(params)
Constructs AJAX URLs that preserve the complete path context:
```javascript
// Example: /properties/hotel-a/booking?Itemid=123
buildAjaxUrl({ option: 'com_solidres', task: 'payment.confirm' })
// Returns: /properties/hotel-a/booking/index.php?Itemid=123&option=com_solidres&task=payment.confirm
```

### buildRedirectUrl(view, additionalParams)
Constructs redirect URLs that maintain navigation state:
```javascript
// Example: /hub-site/property-123/booking?hub_id=5&Itemid=456
buildRedirectUrl('confirmation', { booking_id: 999 })
// Returns: /hub-site/property-123/booking/index.php?hub_id=5&Itemid=456&view=confirmation&booking_id=999
```

## Key Features

### Path Preservation
- Uses `window.location.pathname` to capture full path
- Removes filename if present to get base directory
- Maintains all path segments for proper routing

### Parameter Management
- Preserves critical Joomla parameters: option, Itemid
- Preserves Solidres parameters: property_id, hub_id, site_id
- Merges new parameters while maintaining context
- Uses URLSearchParams for proper URL encoding

### Error Handling
- Validates required parameters (payment_id)
- Graceful error messages for users
- Console logging for debugging
- Network error handling with catch blocks

### Security
- Uses `credentials: 'same-origin'` for AJAX calls
- Adds `X-Requested-With: XMLHttpRequest` header
- No eval() or dynamic code execution
- Proper input validation
- Safe innerHTML usage (no string concatenation)

## Usage by Plugin Installer

The plugin installer (script.php) copies this file to:
```
{template}/html/com_solidres/reservationasset/confirmation.php
```

Where `{template}` is the active Joomla frontend template.

## Testing Scenarios

### ✓ Root Menu Item
```
URL: https://example.com/booking
AJAX: Works correctly
Redirect: Preserves /booking path
```

### ✓ Single-Level Submenu
```
URL: https://example.com/properties/booking?Itemid=123
AJAX: Preserves /properties/ path and Itemid
Redirect: Maintains full context
```

### ✓ Deep Nested Submenu
```
URL: https://example.com/properties/region/city/hotel/booking?Itemid=456
AJAX: Preserves entire path hierarchy
Redirect: Maintains all segments
```

### ✓ Hub/Multisite Context
```
URL: https://example.com/hub/property-123/booking?hub_id=5&property_id=123&Itemid=789
AJAX: Preserves hub context
Redirect: Maintains hub_id and property_id
```

### ✓ Deep Link
```
URL: https://example.com/props/hotel/book?id=999&Itemid=111&custom=value
AJAX: Preserves all existing parameters
Redirect: Maintains original context
```

## Browser Compatibility
- Chrome 49+
- Firefox 44+
- Safari 10+
- Edge (all versions)
- Mobile browsers (iOS Safari, Chrome Mobile)

Uses standard JavaScript APIs:
- `window.location` (universal)
- `URLSearchParams` (IE11+ with polyfill)
- `fetch()` (IE11+ with polyfill)
- `Promise` (IE11+ with polyfill)

## Maintenance

### When to Update
Update these files when:
- Adding new critical parameters to preserve
- Changing AJAX endpoint URLs
- Modifying error handling logic
- Adding new payment methods

### What NOT to Change
Do not:
- Use simple relative URLs like `'index.php?...'`
- Remove path preservation logic
- Remove parameter preservation
- Bypass buildAjaxUrl() or buildRedirectUrl() functions

### Adding New Parameters to Preserve
Edit the `preserveParams` array in `buildRedirectUrl()`:
```javascript
var preserveParams = [
    'option', 'Itemid', 'property_id', 'hub_id', 'site_id',
    'your_new_param'  // Add here
];
```

## Debugging

### Enable Console Logging
The code includes console.log statements for debugging:
```javascript
console.log('[Qvik] Built AJAX URL preserving path:', ajaxUrl);
console.log('[Qvik] Built redirect URL preserving context:', redirectUrl);
```

### Common Issues

**404 Error on AJAX Call**
- Check browser console for actual URL being called
- Verify all path segments are present
- Confirm Itemid parameter is preserved

**Redirect to Wrong Page**
- Check browser console for redirect URL
- Verify critical parameters (Itemid, property_id) are present
- Confirm path hierarchy is maintained

**Lost Menu Context**
- Verify Itemid parameter in URL
- Check that buildRedirectUrl() preserves Itemid
- Confirm menu structure in Joomla admin

## Security Considerations

### Input Validation
- Payment and booking IDs are extracted from URL
- Server-side validation is required (not implemented here)
- This file only handles client-side UI and navigation

### CSRF Protection
- Uses same-origin credentials
- Server must validate CSRF tokens
- Client-side code preserves existing parameters

### XSS Prevention
- No string concatenation in innerHTML
- Data from server is sanitized before display
- User input is not directly rendered

## Additional Resources
- See `IMPLEMENTATION_NOTES.md` in repository root for detailed technical documentation
- Joomla menu system: https://docs.joomla.org/Menu_Item_Type
- Solidres documentation: Contact support for booking system details
