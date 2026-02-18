# Context-Aware URL Building - Examples

## Problem: AJAX 404 Errors in Different Contexts

### Before Fix (Broken)

#### Root Context
```javascript
// ❌ WRONG - Hardcoded root path
const url = 'https://example.com/index.php?option=com_solidres&task=save';
// Works in root, but fails in submenu/hub
```

#### Submenu Context (e.g., /foglalas/)
```javascript
// ❌ WRONG - Same hardcoded path
const url = 'https://example.com/index.php?option=com_solidres&task=save';
// FAILS with 404 - should be /foglalas/index.php
```

#### Hub Context
```javascript
// ❌ WRONG - Missing hub_id parameter
const url = 'https://example.com/index.php?option=com_solidres&task=save';
// Context mismatch - hub_id lost
```

### After Fix (Correct)

#### Root Context
```javascript
// ✅ CORRECT - Context-aware URL building
const url = buildContextAwareUrl('index.php', {
    option: 'com_solidres',
    task: 'save',
    Itemid: 101,
    property_id: 45
});
// Result: https://example.com/index.php?option=com_solidres&task=save&Itemid=101&property_id=45
```

#### Submenu Context (e.g., /foglalas/)
```javascript
// ✅ CORRECT - Automatically detects submenu
const url = buildContextAwareUrl('index.php', {
    option: 'com_solidres',
    task: 'save',
    Itemid: 102,
    property_id: 50
});
// Result: https://example.com/foglalas/index.php?option=com_solidres&task=save&Itemid=102&property_id=50
// No more 404 errors!
```

#### Hub Context
```javascript
// ✅ CORRECT - Hub ID preserved
const url = buildContextAwareUrl('index.php', {
    option: 'com_solidres',
    task: 'save',
    Itemid: 103,
    hub_id: 5,
    property_id: 60
});
// Result: https://example.com/index.php?option=com_solidres&task=save&Itemid=103&hub_id=5&property_id=60
// Hub context maintained!
```

## Payment Method Display

### Before Fix (Problematic)

```php
// ❌ WRONG - Direct access without validation
echo $reservationDetails->guest['payment_method_id'];
// Risks: undefined index, XSS vulnerability, no translation
```

### After Fix (Secure)

```php
// ✅ CORRECT - Secure with translation and fallback
$paymentMethodId = $reservationDetails->guest['payment_method_id'] ?? '';
$paymentMethodKey = 'SR_PAYMENT_METHOD_' . strtoupper($paymentMethodId);
$paymentMethodName = Text::_($paymentMethodKey);

// Fallback if no translation
if ($paymentMethodName === $paymentMethodKey) {
    $paymentMethodName = ucfirst(str_replace('_', ' ', $paymentMethodId));
}

echo htmlspecialchars($paymentMethodName, ENT_QUOTES, 'UTF-8');
// Result: "Bankkártya" instead of "credit_card"
```

## Session Context Management

### Before Fix (Unreliable)

```php
// ❌ WRONG - Direct session access without context
$reservationDetails = $session->get('reservation_details');
// Risks: context mismatch, missing data, no fallback
```

### After Fix (Robust)

```php
// ✅ CORRECT - Context-aware with fallback
$reservationDetails = SolidresSessionContextHelper::getReservationDetails();
// 1. Tries session first (cached)
// 2. Falls back to database if needed
// 3. Validates context parameters
// 4. Saves to session for future use
```

## Complete Example: AJAX Request

### Before Fix

```javascript
// ❌ WRONG - Brittle AJAX request
fetch('/index.php?option=com_solidres&task=save', {
    method: 'POST',
    body: JSON.stringify({reservation_id: 123})
})
.then(response => response.json())
.then(data => console.log(data));

// Problems:
// - 404 in submenu context
// - Missing context parameters
// - No CSRF token
// - Loses hub_id
```

### After Fix

```javascript
// ✅ CORRECT - Robust AJAX request
const ajaxUrl = buildAjaxUrl('reservationasset.save', {
    reservation_id: 123
});

fetch(ajaxUrl, {
    method: 'POST',
    headers: {
        'Content-Type': 'application/json'
    },
    credentials: 'same-origin',
    body: JSON.stringify({
        reservation_id: 123,
        payment_method_id: 'credit_card'
    })
})
.then(response => {
    if (!response.ok) {
        throw new Error('HTTP error ' + response.status);
    }
    return response.json();
})
.then(data => {
    if (!data.success) {
        throw new Error(data.message || 'Unknown error');
    }
    console.log('Success:', data);
})
.catch(error => {
    console.error('Error:', error);
});

// Advantages:
// - Works in all contexts (root/submenu/hub)
// - Preserves all context parameters
// - Includes CSRF token
// - Three-level error handling
```

## URL Pattern Comparison

| Context Type | Before (Wrong) | After (Correct) |
|--------------|----------------|-----------------|
| **Root** | `/index.php?task=save` | `/index.php?option=com_solidres&task=save&Itemid=101&property_id=45` |
| **Submenu** | `/index.php?task=save` ❌ 404 | `/foglalas/index.php?option=com_solidres&task=save&Itemid=102&property_id=50` ✅ |
| **Hub** | `/index.php?task=save` ❌ Lost context | `/index.php?option=com_solidres&task=save&Itemid=103&hub_id=5&property_id=60` ✅ |

## Context Detection Logic

```javascript
function buildContextAwareUrl(baseUrl, additionalParams) {
    const pathname = window.location.pathname;
    const origin = window.location.origin;
    
    let fullBaseUrl = baseUrl;
    if (!baseUrl.startsWith('http')) {
        // ✅ Detect submenu context
        if (pathname.indexOf('index.php') > 0) {
            // Submenu: /foglalas/index.php
            fullBaseUrl = origin + pathname.substring(0, pathname.lastIndexOf('/') + 1) + baseUrl;
        } else {
            // Root: /index.php
            fullBaseUrl = origin + '/' + baseUrl;
        }
    }
    
    // ✅ Preserve all context parameters
    const urlParams = new URLSearchParams(window.location.search);
    const params = new URLSearchParams();
    
    // Add context params
    ['Itemid', 'property_id', 'hub_id', 'site_id', 'reservation_id'].forEach(key => {
        const value = urlParams.get(key);
        if (value) params.append(key, value);
    });
    
    // Add additional params
    Object.entries(additionalParams || {}).forEach(([key, value]) => {
        params.append(key, value);
    });
    
    return fullBaseUrl + '?' + params.toString();
}
```

## Testing Different Contexts

### Test Script

```bash
# Root context
php test_session_context.php
# ✅ Payment method: credit_card → "Bankkártya"
# ✅ URL: https://example.com/index.php?...

# Submenu context (simulate)
TEST_CONTEXT=submenu php test_session_context.php
# ✅ URL: https://example.com/foglalas/index.php?...

# Hub context (simulate)
TEST_CONTEXT=hub php test_session_context.php
# ✅ URL: https://example.com/index.php?hub_id=5&...
```

### Expected Results

```
=== All Tests Passed ===

Summary:
✓ Session context kezelés működik
✓ Payment method ID helyesen jelenik meg
✓ Context paraméterek megmaradnak
✓ AJAX URL-ek helyesen épülnek minden context-ben
✓ Nincs 404 hiba submenu/hub context-ben
```

## Benefits of the Fix

1. **No More 404 Errors**: AJAX requests work in all contexts
2. **Context Preservation**: hub_id, property_id, Itemid always maintained
3. **Secure Display**: XSS protection, proper escaping
4. **Reliable Session**: Fallback to database if session lost
5. **Internationalization**: Language constant support
6. **Debug Support**: Visual context information in debug mode
7. **Maintainable**: Centralized helper functions
8. **Tested**: Automated tests verify all contexts

## Migration Guide

### Step 1: Replace hardcoded URLs

```javascript
// Before
const url = '/index.php?option=com_solidres&task=save';

// After
const url = buildContextAwareUrl('index.php', {
    option: 'com_solidres',
    task: 'save'
});
```

### Step 2: Use helper for session data

```php
// Before
$reservationDetails = $session->get('reservation_details');

// After
require_once JPATH_SITE . '/components/com_solidres/helpers/sessioncontext.php';
$reservationDetails = SolidresSessionContextHelper::getReservationDetails();
```

### Step 3: Secure payment method display

```php
// Before
echo $reservationDetails->guest['payment_method_id'];

// After
$paymentMethodId = $reservationDetails->guest['payment_method_id'] ?? '';
$paymentMethodName = SolidresSessionContextHelper::getPaymentMethodName($paymentMethodId);
echo htmlspecialchars($paymentMethodName, ENT_QUOTES, 'UTF-8');
```

## Conclusion

This fix ensures reliable payment method display and AJAX functionality across all Joomla/Solidres contexts (root, submenu, hub). The solution is secure, maintainable, and thoroughly tested.
