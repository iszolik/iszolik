# Prevention Guidelines: Avoiding Context/Fetch Errors in Solidres

## Overview

This document provides comprehensive guidelines for preventing AJAX/fetch context errors in Joomla/Solidres applications, specifically addressing the type of bug found in Issue #6 where payment method endpoints fail in submenu contexts.

## Core Principles

### 1. Always Use Full Path URLs

**❌ DON'T: Use hardcoded paths**
```javascript
// BAD: Assumes root context
const url = '/index.php?option=com_solidres&task=updatePaymentMethod';

// BAD: Assumes relative to current path
const url = 'index.php?option=com_solidres&task=updatePaymentMethod';
```

**✅ DO: Use dynamic path construction**
```javascript
// GOOD: Detects and preserves full path
const basePath = getContextBasePath(); // Returns '/' or '/hotels/' or '/budapest/'
const url = window.location.origin + basePath + 'index.php?option=com_solidres&task=updatePaymentMethod';
```

### 2. Always Preserve Context Parameters

**❌ DON'T: Omit critical parameters**
```javascript
// BAD: Missing Itemid and other context
const url = baseUrl + '?option=com_solidres&task=updatePaymentMethod';
```

**✅ DO: Include all context parameters**
```javascript
// GOOD: Preserves all context
const params = new URLSearchParams();
params.append('option', 'com_solidres');
params.append('task', 'updatePaymentMethod');
params.append('Itemid', getUrlParameter('Itemid'));
params.append('property_id', getUrlParameter('property_id'));
params.append('hub_id', getUrlParameter('hub_id'));
params.append('site_id', getUrlParameter('site_id'));
params.append('reservation_id', getUrlParameter('reservation_id'));
```

### 3. Always Implement Proper Error Handling

**❌ DON'T: Ignore errors**
```javascript
// BAD: No error handling
fetch(url).then(response => response.json()).then(data => {
    console.log(data);
});
```

**✅ DO: Use three-level error handling**
```javascript
// GOOD: Comprehensive error handling
try {
    const response = await fetch(url);
    
    // Level 1: Check HTTP status
    if (!response.ok) {
        throw new Error(`HTTP error! status: ${response.status}`);
    }
    
    // Level 2: Parse and validate response
    const data = await response.json();
    if (!data.success) {
        throw new Error(data.message || 'API error');
    }
    
    // Success handling
    return data;
    
} catch (error) {
    // Level 3: Handle all errors
    console.error('AJAX failed:', error);
    alert('Operation failed. Please try again.');
    throw error;
}
```

## Implementation Checklist

### For Every AJAX Endpoint

- [ ] **Path Detection**: Detect if in submenu/hub context
- [ ] **Base URL**: Use `window.location.origin + getContextBasePath()`
- [ ] **Parameter Extraction**: Read all params from current URL
- [ ] **Parameter Preservation**: Include all context params in AJAX URL
- [ ] **Error Handling**: Implement three-level error handling
- [ ] **User Feedback**: Show appropriate messages on success/failure
- [ ] **State Management**: Restore UI state on errors
- [ ] **Debug Logging**: Include console logging for troubleshooting
- [ ] **Testing**: Test in root, submenu, and hub contexts

### For Every Payment Plugin

- [ ] **Context-Aware URLs**: All AJAX calls use context-aware URL building
- [ ] **Session Validation**: Server validates context matches session
- [ ] **Parameter Validation**: Server validates all required parameters
- [ ] **Redirect URLs**: Use same path preservation for redirects
- [ ] **Form Submissions**: Use same pattern for form actions
- [ ] **Asset Loading**: Load CSS/JS with context-aware paths
- [ ] **Documentation**: Document URL structure and parameters
- [ ] **Examples**: Provide working examples for developers

## Reusable Code Patterns

### Pattern 1: Context Detection Functions

```javascript
/**
 * Reusable context detection - include in all AJAX implementations
 */

function isSubmenuContext() {
    return window.location.pathname.indexOf('index.php') > 1;
}

function getContextBasePath() {
    const pathname = window.location.pathname;
    const indexPos = pathname.indexOf('index.php');
    return indexPos > 1 ? pathname.substring(0, indexPos) : '/';
}

function getUrlParameter(name) {
    return new URLSearchParams(window.location.search).get(name);
}
```

### Pattern 2: URL Builder Function

```javascript
/**
 * Reusable URL builder - use for all AJAX endpoints
 */

function buildAjaxUrl(task, additionalParams = {}) {
    const baseUrl = window.location.origin + getContextBasePath() + 'index.php';
    const params = new URLSearchParams();
    
    // Core parameters
    params.append('option', 'com_solidres');
    params.append('task', task);
    params.append('format', 'json');
    
    // Context parameters
    const context = {
        Itemid: getUrlParameter('Itemid'),
        property_id: getUrlParameter('property_id'),
        hub_id: getUrlParameter('hub_id'),
        site_id: getUrlParameter('site_id'),
        reservation_id: getUrlParameter('reservation_id')
    };
    
    for (const [key, value] of Object.entries(context)) {
        if (value) params.append(key, value);
    }
    
    // Additional parameters
    for (const [key, value] of Object.entries(additionalParams)) {
        if (value !== null && value !== undefined) {
            params.append(key, value);
        }
    }
    
    return baseUrl + '?' + params.toString();
}
```

### Pattern 3: AJAX Call Wrapper

```javascript
/**
 * Reusable AJAX wrapper - use for all fetch calls
 */

async function performAjaxCall(url, options = {}) {
    try {
        const response = await fetch(url, {
            method: options.method || 'POST',
            headers: {
                'Content-Type': 'application/x-www-form-urlencoded',
                'X-Requested-With': 'XMLHttpRequest',
                ...options.headers
            },
            body: options.body,
            credentials: 'same-origin'
        });
        
        if (!response.ok) {
            throw new Error(`HTTP error! status: ${response.status}`);
        }
        
        const data = await response.json();
        
        if (!data.success) {
            throw new Error(data.message || 'API request failed');
        }
        
        return data;
        
    } catch (error) {
        console.error('AJAX call failed:', error);
        throw error;
    }
}
```

### Pattern 4: Server-Side Validation

```php
/**
 * Reusable server-side context validation
 */

class ContextValidator {
    
    public static function validateAjaxRequest() {
        $app = JFactory::getApplication();
        $input = $app->input;
        $session = $app->getSession();
        
        // Get request parameters
        $itemid = $input->getInt('Itemid');
        $propertyId = $input->getInt('property_id');
        $hubId = $input->getInt('hub_id');
        $reservationId = $input->getInt('reservation_id');
        
        // Get session context
        $sessionContext = $session->get('solidres.context', []);
        
        // Validate Itemid matches
        if ($itemid && isset($sessionContext['itemid'])) {
            if ($itemid != $sessionContext['itemid']) {
                throw new Exception('Context mismatch: Itemid');
            }
        }
        
        // Validate property_id matches
        if ($propertyId && isset($sessionContext['property_id'])) {
            if ($propertyId != $sessionContext['property_id']) {
                throw new Exception('Context mismatch: property_id');
            }
        }
        
        // Store current context
        $sessionContext = [
            'itemid' => $itemid ?: $sessionContext['itemid'] ?? null,
            'property_id' => $propertyId ?: $sessionContext['property_id'] ?? null,
            'hub_id' => $hubId ?: $sessionContext['hub_id'] ?? null,
            'reservation_id' => $reservationId ?: $sessionContext['reservation_id'] ?? null
        ];
        
        $session->set('solidres.context', $sessionContext);
        
        return true;
    }
}
```

## Common Mistakes and Solutions

### Mistake 1: Using Relative URLs

**Problem:**
```javascript
fetch('index.php?option=com_solidres&task=getData')
```

**Why it fails:**
- In submenu: `/hotels/` + relative path = `/hotels/index.php` (correct by accident)
- In root: `/` + relative path = `/index.php` (correct)
- But inconsistent and unreliable

**Solution:**
```javascript
fetch(window.location.origin + getContextBasePath() + 'index.php?...')
```

### Mistake 2: Forgetting Itemid

**Problem:**
```javascript
const url = baseUrl + '?option=com_solidres&task=getData';
// Missing Itemid causes session mismatch
```

**Why it fails:**
- Joomla uses Itemid for menu routing
- Session stores Itemid from page load
- AJAX without Itemid may load wrong module context

**Solution:**
```javascript
const itemid = getUrlParameter('Itemid');
const url = baseUrl + '?option=com_solidres&task=getData&Itemid=' + itemid;
```

### Mistake 3: Not Testing All Contexts

**Problem:**
- Developers test only in their local setup (usually root context)
- Bug appears in production (with submenu/hub structure)

**Solution:**
- Set up test environments for all contexts
- Include context testing in CI/CD
- Use the testing guide provided

### Mistake 4: Inadequate Error Handling

**Problem:**
```javascript
fetch(url)
    .then(response => response.json())
    .then(data => console.log(data));
// Silent failures, no user feedback
```

**Why it fails:**
- 404 errors silently fail
- Users see no feedback
- Hard to debug issues

**Solution:**
```javascript
try {
    const response = await fetch(url);
    if (!response.ok) throw new Error(`Status: ${response.status}`);
    const data = await response.json();
    if (!data.success) throw new Error(data.message);
    return data;
} catch (error) {
    console.error('Failed:', error);
    alert('Operation failed. Please try again.');
    throw error;
}
```

### Mistake 5: Server-Side Assumptions

**Problem:**
```php
// Assumes request always from same context
$itemid = 101; // Hardcoded
```

**Why it fails:**
- Different menu items have different Itemids
- Hardcoded values don't match current context

**Solution:**
```php
$itemid = $input->getInt('Itemid', 0);
$session->set('current.itemid', $itemid);
```

## Testing Requirements

### Minimum Test Coverage

Every AJAX implementation must be tested in:

1. **Root Context** (`/index.php`)
   - URL: `https://example.com/index.php?...`
   - Expected: Works correctly

2. **Submenu Context** (`/submenu/index.php`)
   - URL: `https://example.com/hotels/index.php?...`
   - Expected: Works correctly (this was broken)

3. **Hub Context** (`/hub/index.php`)
   - URL: `https://example.com/budapest/index.php?...`
   - Expected: Works correctly (this was broken)

4. **Multi-level** (`/level1/level2/index.php`)
   - URL: `https://example.com/europe/budapest/index.php?...`
   - Expected: Works correctly

### Test Scenarios

For each context, test:

- [ ] AJAX call succeeds (200 OK)
- [ ] Response is valid JSON
- [ ] Parameters are preserved
- [ ] Session context matches
- [ ] Error handling works
- [ ] UI updates correctly

## Code Review Checklist

When reviewing AJAX/fetch code:

- [ ] Uses dynamic path detection (not hardcoded `/index.php`)
- [ ] Preserves all context parameters
- [ ] Implements three-level error handling
- [ ] Provides user feedback on errors
- [ ] Has debug logging
- [ ] Includes inline documentation
- [ ] Follows existing code patterns
- [ ] Has been tested in all contexts
- [ ] No security vulnerabilities (XSS, CSRF, etc.)

## Documentation Requirements

Every AJAX endpoint must document:

1. **URL Structure**
   ```
   Base: {origin}{contextPath}index.php
   Required params: option, task, format, Itemid
   Optional params: property_id, hub_id, site_id, reservation_id
   ```

2. **Request Format**
   ```
   Method: POST
   Content-Type: application/x-www-form-urlencoded
   Body: key=value&key2=value2
   ```

3. **Response Format**
   ```json
   {
       "success": true|false,
       "message": "...",
       "data": {...}
   }
   ```

4. **Error Codes**
   ```
   200: Success
   400: Bad request
   401: Unauthorized
   404: Not found (URL construction error)
   500: Server error
   ```

## Helper Library

Consider creating a shared JavaScript library:

**File**: `media/com_solidres/js/ajax-helper.js`

```javascript
window.SolidresAjax = {
    isSubmenuContext: function() { ... },
    getContextBasePath: function() { ... },
    getUrlParameter: function(name) { ... },
    getCurrentContextParameters: function() { ... },
    buildAjaxUrl: function(task, params) { ... },
    performAjaxCall: function(url, options) { ... },
    debugContext: function() { ... }
};
```

Include in all pages:
```php
JHtml::_('script', 'com_solidres/ajax-helper.js', false, true);
```

## Training Recommendations

### For Developers

1. **Understand Joomla Routing**
   - How menu items create URL structure
   - How Itemid affects routing
   - How submenu aliases work

2. **Learn JavaScript Fetch API**
   - Promise-based error handling
   - Async/await patterns
   - Response validation

3. **Master Context Awareness**
   - When to detect context
   - How to preserve parameters
   - Testing in multiple contexts

### For QA Team

1. **Test All Contexts**
   - Set up test environments for each context type
   - Use testing guide provided
   - Automate where possible

2. **Use Browser DevTools**
   - Monitor Network tab for AJAX calls
   - Check Console for errors
   - Verify URL structure

3. **Report Issues Clearly**
   - Include full URL of page
   - Include AJAX URL that failed
   - Include browser console output
   - Include network tab screenshot

## Summary

The key to preventing context/fetch errors is:

1. ✅ **Never hardcode paths** - Always detect dynamically
2. ✅ **Always preserve context** - Include all parameters
3. ✅ **Implement error handling** - Three-level pattern
4. ✅ **Test all contexts** - Root, submenu, hub
5. ✅ **Document everything** - URLs, parameters, responses
6. ✅ **Use helper functions** - Don't repeat logic
7. ✅ **Validate server-side** - Check session matches
8. ✅ **Provide user feedback** - Show errors appropriately

Following these guidelines will prevent the type of bug found in Issue #6 and ensure robust AJAX functionality across all Joomla/Solidres contexts.
