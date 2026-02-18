# Issue #6: Payment Method AJAX Endpoint 404 Error in Submenu Context

## Problem Summary

**Bug**: Qvik and Revolut payment method fetch endpoints (AJAX/JS) return 404 errors when called from submenu context, but work correctly when called from root context.

**Symptoms**:
- `payment_method_id` is correct in both contexts
- Only the AJAX endpoint itself is unreachable (404 error)
- Works: Root menu context (e.g., `/index.php?option=com_solidres...`)
- Fails: Submenu context (e.g., `/hotels/index.php?option=com_solidres...`)

## Root Cause Analysis

### 1. URL Construction Issue

The core problem is **relative URL construction** in JavaScript fetch calls. When building AJAX endpoint URLs, the code uses relative paths that work in root context but fail in submenu contexts.

**Problematic Pattern:**
```javascript
// This fails in submenu context
const url = '/index.php?option=com_solidres&task=updatePaymentMethod';
```

**Why it fails:**
- In root context: `/index.php` correctly points to the Joomla entry point
- In submenu context: `/index.php` bypasses the submenu path segment (e.g., `/hotels/`)
- Result: The web server cannot find the endpoint at the absolute root path

### 2. Context Parameters Not Preserved

Even when the URL is constructed correctly, critical context parameters may not be preserved:

**Missing Parameters:**
- `Itemid` - Menu item identifier (critical for Joomla routing)
- `hub_id` - Hub/location context identifier
- `property_id` - Property identifier
- `site_id` - Site identifier
- `reservation_id` - Current reservation identifier

**Impact:**
Without these parameters, the server-side controller cannot:
- Validate the session context
- Load the correct reservation data
- Match the request to the active session

### 3. Path Detection Failure

The JavaScript code fails to detect and preserve the full path structure:

**Path Structure:**
```
Root context:    /index.php?option=com_solidres&...
Submenu context: /hotels/index.php?option=com_solidres&...
Hub context:     /budapest/index.php?option=com_solidres&...
```

Without proper path detection, the code cannot determine:
- Whether it's running in a submenu context
- What the full base path should be
- How to construct context-aware URLs

## Technical Details

### URL Analysis by Context

#### Root Context (Working)
```
Current URL: https://example.com/index.php?option=com_solidres&view=reservationasset&Itemid=101
AJAX URL:    https://example.com/index.php?option=com_solidres&task=updatePaymentMethod&Itemid=101
Result:      ✓ Success (200 OK)
```

#### Submenu Context (Failing)
```
Current URL: https://example.com/hotels/index.php?option=com_solidres&view=reservationasset&Itemid=102
AJAX URL:    https://example.com/index.php?option=com_solidres&task=updatePaymentMethod&Itemid=102
Expected:    https://example.com/hotels/index.php?option=com_solidres&task=updatePaymentMethod&Itemid=102
Result:      ✗ 404 Not Found (missing /hotels/ segment)
```

#### Hub Context (Also Failing)
```
Current URL: https://example.com/budapest/index.php?option=com_solidres&view=reservationasset&Itemid=103
AJAX URL:    https://example.com/index.php?option=com_solidres&task=updatePaymentMethod&Itemid=103
Expected:    https://example.com/budapest/index.php?option=com_solidres&task=updatePaymentMethod&Itemid=103
Result:      ✗ 404 Not Found (missing /budapest/ segment)
```

### Session Context Mismatch

The 404 error is primarily a URL routing issue, but there's also a secondary session context problem:

1. **User loads page** in submenu context → Session stores context with `Itemid=102`
2. **AJAX call** uses wrong URL → Request goes to root context
3. **Server receives** request in root context → Cannot match session
4. **Even if URL worked**, session validation might fail due to `Itemid` mismatch

### Parameters Causing Issues

| Parameter | Purpose | Impact if Missing/Wrong |
|-----------|---------|------------------------|
| **Path segment** | Route to correct Joomla instance | 404 - Endpoint not found |
| `Itemid` | Menu item context | Session mismatch, wrong module |
| `property_id` | Property context | Wrong property data loaded |
| `hub_id` | Hub/location context | Wrong location context |
| `reservation_id` | Current reservation | Cannot identify reservation |

## Solution Requirements

### 1. Context-Aware URL Building

Must detect and preserve:
- Full path including submenu segments (`/hotels/`, `/budapest/`, etc.)
- Origin (protocol + domain)
- All query parameters from current context

### 2. Parameter Preservation

Must read and include:
- `Itemid` - from current URL
- `property_id` - from current URL or session
- `hub_id` - from current URL or session
- `site_id` - from current URL or session
- `reservation_id` - from current URL or session

### 3. Robust Path Detection

Must handle:
- Root context (no path segment)
- Single-level submenu (`/hotels/`)
- Multi-level paths (`/europe/budapest/`)
- Hub contexts with custom routing

### 4. Error Handling

Must implement:
- HTTP status code checking
- API response validation
- Network error handling
- Fallback mechanisms

## Implementation Strategy

See `PAYMENT_AJAX_FIX.js` for the complete implementation.

Key components:
1. `buildContextAwareAjaxUrl()` - Constructs URLs with full path preservation
2. `getCurrentContextParameters()` - Extracts all critical parameters
3. `performContextAwareAjaxCall()` - Executes fetch with proper error handling
4. Three-level error handling pattern

## Prevention Guidelines

To avoid similar issues in future Solidres AJAX implementations:

1. **Always use `window.location.pathname`** - Never hardcode `/index.php`
2. **Always preserve context parameters** - Extract from current URL
3. **Test in all contexts** - Root, submenu, and hub contexts
4. **Use helper functions** - Don't repeat URL building logic
5. **Implement proper error handling** - Three-level pattern
6. **Validate session context** - Server-side validation with context parameters
7. **Document URL structure** - Make path structure explicit
8. **Use absolute URLs** - Combine origin + pathname + query

## References

- Original memories: Payment AJAX endpoint fixes
- Related files: `PAYMENT_AJAX_FIX.js`, `IMPLEMENTATION_GUIDE.md`
- Similar fixes: Guest form AJAX, payment method updates
