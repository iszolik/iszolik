# Testing and Verification Guide for Payment AJAX Fix

## Overview

This document provides comprehensive testing procedures to verify that the payment method AJAX fix works correctly across all contexts.

## Test Environments

### 1. Root Context
- **URL Pattern**: `https://example.com/index.php?option=com_solidres...`
- **Description**: Default Joomla installation with no path segments
- **Expected Behavior**: AJAX should work (this was already working)

### 2. Submenu Context
- **URL Pattern**: `https://example.com/hotels/index.php?option=com_solidres...`
- **Description**: Joomla menu alias creating a path segment
- **Expected Behavior**: AJAX should work (this was failing, now fixed)

### 3. Hub Context
- **URL Pattern**: `https://example.com/budapest/index.php?option=com_solidres...`
- **Description**: Custom hub routing with location-based path
- **Expected Behavior**: AJAX should work (this was failing, now fixed)

### 4. Multi-level Context
- **URL Pattern**: `https://example.com/europe/budapest/index.php?option=com_solidres...`
- **Description**: Multi-level path structure
- **Expected Behavior**: AJAX should work (this was failing, now fixed)

## Test Cases

### Test Case 1: Root Context Payment Selection

**Setup:**
1. Navigate to: `https://example.com/index.php?option=com_solidres&view=reservationasset&Itemid=101`
2. Ensure session is clean (no previous payment selection)

**Steps:**
1. Open browser developer console (F12)
2. Go to Network tab
3. Select Qvik payment method
4. Observe AJAX request

**Expected Results:**
- AJAX URL: `https://example.com/index.php?option=com_solidres&task=updatePaymentMethod&format=json&Itemid=101&payment_method_id=qvik`
- HTTP Status: `200 OK`
- Response: `{"success": true, "message": "Payment method updated"}`
- Console: "Payment method updated successfully"

**Pass Criteria:**
✅ No 404 error
✅ Response is successful
✅ Payment method is saved in session

---

### Test Case 2: Submenu Context Payment Selection (Primary Bug Fix)

**Setup:**
1. Navigate to: `https://example.com/hotels/index.php?option=com_solidres&view=reservationasset&Itemid=102`
2. Ensure session is clean

**Steps:**
1. Open browser developer console (F12)
2. Go to Network tab
3. Select Revolut payment method
4. Observe AJAX request

**Expected Results:**
- AJAX URL: `https://example.com/hotels/index.php?option=com_solidres&task=updatePaymentMethod&format=json&Itemid=102&payment_method_id=revolut`
- HTTP Status: `200 OK` (was `404 Not Found` before fix)
- Response: `{"success": true, "message": "Payment method updated"}`
- Console: "Payment method updated successfully"

**Pass Criteria:**
✅ No 404 error (this was the main bug)
✅ Response is successful
✅ Payment method is saved in session
✅ URL includes `/hotels/` path segment

---

### Test Case 3: Hub Context Payment Selection

**Setup:**
1. Navigate to: `https://example.com/budapest/index.php?option=com_solidres&view=reservationasset&Itemid=103`
2. Ensure session is clean

**Steps:**
1. Open browser developer console (F12)
2. Go to Network tab
3. Select Qvik payment method
4. Observe AJAX request

**Expected Results:**
- AJAX URL: `https://example.com/budapest/index.php?option=com_solidres&task=updatePaymentMethod&format=json&Itemid=103&hub_id=5&payment_method_id=qvik`
- HTTP Status: `200 OK` (was `404 Not Found` before fix)
- Response: `{"success": true, "message": "Payment method updated"}`
- Console: "Payment method updated successfully"

**Pass Criteria:**
✅ No 404 error
✅ Response is successful
✅ URL includes `/budapest/` path segment
✅ hub_id parameter is preserved

---

### Test Case 4: Context Parameter Preservation

**Setup:**
1. Navigate to: `https://example.com/hotels/index.php?option=com_solidres&view=reservationasset&Itemid=102&property_id=7&hub_id=3&site_id=1&reservation_id=12345`

**Steps:**
1. Open browser developer console
2. Run: `SolidresPaymentAjax.debugContextInfo()`
3. Verify output
4. Select payment method
5. Check AJAX URL in Network tab

**Expected Results:**
```javascript
Context Debug Info
  Is Submenu Context: true
  Base Path: "/hotels/"
  Full Origin: "https://example.com"
  Full Pathname: "/hotels/index.php"
  Context Parameters: {
    itemid: "102",
    propertyId: "7",
    hubId: "3",
    siteId: "1",
    reservationId: "12345"
  }
  Example AJAX URL: "https://example.com/hotels/index.php?option=com_solidres&task=updatePaymentMethod&format=json&Itemid=102&property_id=7&hub_id=3&site_id=1&reservation_id=12345&payment_method_id=test_method"
```

**Pass Criteria:**
✅ All parameters detected correctly
✅ Base path includes submenu segment
✅ AJAX URL preserves all parameters

---

### Test Case 5: Error Handling - Network Failure

**Setup:**
1. Navigate to any payment page
2. Open browser developer console
3. Go to Network tab
4. Enable network throttling to "Offline"

**Steps:**
1. Select payment method
2. Observe error handling

**Expected Results:**
- Console error: "AJAX call failed: TypeError: Failed to fetch"
- User alert: "Failed to update payment method. Please try again or contact support."
- Payment radio unchecked
- Payment details hidden

**Pass Criteria:**
✅ Error caught gracefully
✅ User-friendly error message shown
✅ UI state restored

---

### Test Case 6: Error Handling - Server Error

**Setup:**
1. Temporarily modify server to return 500 error
2. Navigate to payment page

**Steps:**
1. Select payment method
2. Observe error handling

**Expected Results:**
- Console error: "HTTP error! status: 500"
- User alert shown
- Payment selection reverted

**Pass Criteria:**
✅ HTTP error detected
✅ Error message shown
✅ State restored

---

### Test Case 7: Cross-Context Navigation

**Setup:**
1. Start in root context
2. Select payment method
3. Navigate to submenu context

**Steps:**
1. Start: `https://example.com/index.php?option=com_solidres&view=reservationasset&Itemid=101`
2. Select Qvik payment method
3. Navigate: `https://example.com/hotels/index.php?option=com_solidres&view=reservationasset&Itemid=102`
4. Select Revolut payment method

**Expected Results:**
- First AJAX (root): Success with root URL
- Second AJAX (submenu): Success with submenu URL
- Both payments saved correctly
- No 404 errors

**Pass Criteria:**
✅ Both contexts work independently
✅ URL detection adapts to context
✅ No cross-contamination

---

## Manual Testing Checklist

### Pre-Test Setup
- [ ] Clear browser cache
- [ ] Clear Joomla session
- [ ] Enable browser developer console
- [ ] Set up test payment methods (Qvik, Revolut)

### Root Context Tests
- [ ] Test Case 1: Root context payment selection
- [ ] Verify AJAX URL format
- [ ] Verify parameter preservation
- [ ] Verify success response

### Submenu Context Tests (Critical)
- [ ] Test Case 2: Submenu context payment selection
- [ ] Verify no 404 error (main bug fix)
- [ ] Verify `/hotels/` in AJAX URL
- [ ] Verify parameter preservation
- [ ] Verify success response

### Hub Context Tests
- [ ] Test Case 3: Hub context payment selection
- [ ] Verify hub parameter preservation
- [ ] Verify no 404 error

### Advanced Tests
- [ ] Test Case 4: Context parameter preservation
- [ ] Test Case 5: Network error handling
- [ ] Test Case 6: Server error handling
- [ ] Test Case 7: Cross-context navigation

### Browser Compatibility
- [ ] Chrome/Chromium
- [ ] Firefox
- [ ] Safari
- [ ] Edge

## Automated Testing Script

```javascript
/**
 * Automated test script for payment AJAX fix
 * Run this in browser console on payment page
 */

async function runPaymentAjaxTests() {
    console.group('Payment AJAX Tests');
    
    // Test 1: Context Detection
    console.group('Test 1: Context Detection');
    console.log('Is Submenu:', SolidresPaymentAjax.isSubmenuContext());
    console.log('Base Path:', SolidresPaymentAjax.getContextBasePath());
    console.groupEnd();
    
    // Test 2: Parameter Extraction
    console.group('Test 2: Parameter Extraction');
    const params = SolidresPaymentAjax.getCurrentContextParameters();
    console.log('Parameters:', params);
    console.log('Has Itemid:', params.itemid !== null);
    console.groupEnd();
    
    // Test 3: URL Building
    console.group('Test 3: URL Building');
    const testUrl = SolidresPaymentAjax.buildContextAwareAjaxUrl('updatePaymentMethod', {
        payment_method_id: 'test'
    });
    console.log('Generated URL:', testUrl);
    console.log('Includes origin:', testUrl.includes(window.location.origin));
    console.log('Includes component:', testUrl.includes('option=com_solidres'));
    console.log('Includes task:', testUrl.includes('task=updatePaymentMethod'));
    console.groupEnd();
    
    // Test 4: Path Preservation
    console.group('Test 4: Path Preservation');
    const basePath = SolidresPaymentAjax.getContextBasePath();
    console.log('Base path:', basePath);
    console.log('Path preserved in URL:', testUrl.includes(basePath + 'index.php'));
    console.groupEnd();
    
    console.groupEnd();
    
    return 'All tests completed. Check results above.';
}

// Run tests
runPaymentAjaxTests();
```

## Debug Commands

### Check Context Information
```javascript
// Run in browser console
SolidresPaymentAjax.debugContextInfo();
```

### Test URL Building
```javascript
// Test URL for different payment methods
console.log('Qvik URL:', SolidresPaymentAjax.buildContextAwareAjaxUrl('updatePaymentMethod', {
    payment_method_id: 'qvik'
}));

console.log('Revolut URL:', SolidresPaymentAjax.buildContextAwareAjaxUrl('updatePaymentMethod', {
    payment_method_id: 'revolut'
}));
```

### Simulate Payment Method Update
```javascript
// Test payment method update (doesn't actually update)
SolidresPaymentAjax.performPaymentMethodUpdate('qvik')
    .then(result => console.log('Success:', result))
    .catch(error => console.error('Error:', error));
```

## Success Criteria

### Overall Fix Validation
- ✅ All test cases pass
- ✅ No 404 errors in any context
- ✅ Parameters preserved correctly
- ✅ Error handling works properly
- ✅ Works in all major browsers
- ✅ No console errors or warnings
- ✅ Performance is acceptable (< 500ms response time)

### Regression Testing
- ✅ Root context still works (didn't break existing functionality)
- ✅ Submenu context now works (fixed the bug)
- ✅ Hub context now works (fixed the bug)
- ✅ Multi-level paths work

## Troubleshooting

### Issue: Still getting 404 errors

**Check:**
1. Verify JavaScript file is included
2. Check browser console for script errors
3. Verify `SolidresPaymentAjax` object is available
4. Run `debugContextInfo()` to check detection

### Issue: Parameters not preserved

**Check:**
1. Verify URL contains parameters before AJAX call
2. Check `getCurrentContextParameters()` output
3. Verify session is active
4. Check server-side parameter handling

### Issue: Error handling not working

**Check:**
1. Verify try-catch blocks are active
2. Check console for error messages
3. Verify alert() function is not blocked
4. Test with different error scenarios

## Performance Benchmarks

### Acceptable Response Times
- Root context: < 200ms
- Submenu context: < 300ms
- Hub context: < 400ms
- Multi-level: < 500ms

### Network Metrics
- Request size: < 2KB
- Response size: < 1KB
- Total time: < 500ms

## Reporting Results

### Test Report Template
```
Test Date: [DATE]
Tester: [NAME]
Environment: [PRODUCTION/STAGING/DEV]
Browser: [BROWSER + VERSION]

Root Context: [PASS/FAIL]
Submenu Context: [PASS/FAIL]
Hub Context: [PASS/FAIL]
Error Handling: [PASS/FAIL]

Notes:
[Any issues or observations]
```
