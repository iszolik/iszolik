# Testing Script for Solidres Payment Plugin 404 Fix

## Overview

This script provides comprehensive testing procedures to verify the fix works correctly in all scenarios.

## Prerequisites

- Joomla site with Solidres installed
- Qvik and/or Revolut payment plugins configured
- Test reservation created
- Browser with developer tools

## Test Environment Setup

### 1. Enable Debug Mode

```php
// Edit configuration.php
public $debug = '1';
public $log_path = '/path/to/logs';
```

### 2. Enable Logging

Create `administrator/components/com_solidres/config/logging.php`:

```php
<?php
defined('_JEXEC') or die;

JLog::addLogger(
    ['text_file' => 'com_solidres.routing.php'],
    JLog::ALL,
    ['com_solidres.routing']
);

JLog::addLogger(
    ['text_file' => 'com_solidres.payment.php'],
    JLog::ALL,
    ['com_solidres.payment']
);
```

### 3. Open Monitoring Tools

Terminal 1 - Routing logs:
```bash
tail -f logs/com_solidres.routing.php
```

Terminal 2 - Payment logs:
```bash
tail -f logs/com_solidres.payment.php
```

Terminal 3 - PHP error log:
```bash
tail -f /var/log/php-fpm/www-error.log
```

## Test Suite 1: Basic Functionality

### Test 1.1: Root Context

**Objective:** Verify payment method update works in root context

**Steps:**
1. Navigate to: `http://yoursite.com/index.php?option=com_solidres&view=reservationasset&...`
2. Open browser console (F12)
3. Select Qvik payment method
4. Observe:
   - Network tab shows request
   - Response is 200 OK
   - Payment method updates successfully

**Expected Results:**
```
✓ HTTP Status: 200 OK
✓ Response: {"success": true, "message": "..."}
✓ Console: No errors
✓ Visual: Payment method selected
```

**Verification:**
```javascript
// In browser console
fetch('/index.php?option=com_solidres&task=reservationasset.updatePaymentMethod&format=json&payment_method_id=5&Itemid=' + SolidresPayment.findCorrectItemId())
    .then(r => r.json())
    .then(d => console.log('Test 1.1 Result:', d.success ? 'PASS' : 'FAIL', d))
    .catch(e => console.error('Test 1.1 Result: FAIL', e));
```

### Test 1.2: Submenu Context (Primary Test)

**Objective:** Verify payment method update works in submenu context (this was failing before fix)

**Steps:**
1. Navigate to: `http://yoursite.com/property1/index.php?option=com_solidres&view=reservationasset&...`
2. Open browser console (F12)
3. Select Revolut payment method
4. Observe:
   - Network tab shows request to `/property1/index.php?...`
   - Response is 200 OK (NOT 404!)
   - Payment method updates successfully

**Expected Results:**
```
✓ HTTP Status: 200 OK (was 404 before fix)
✓ Response: {"success": true, "message": "..."}
✓ Console: No errors
✓ Visual: Payment method selected
✓ Logs: "Corrected Menu Context" message appears
```

**Verification:**
```javascript
// In browser console
fetch(window.location.pathname + '?option=com_solidres&task=reservationasset.updatePaymentMethod&format=json&payment_method_id=6&Itemid=' + SolidresPayment.findCorrectItemId())
    .then(r => {
        console.log('Test 1.2 Status:', r.status);
        return r.json();
    })
    .then(d => console.log('Test 1.2 Result:', d.success ? 'PASS ✓' : 'FAIL ✗', d))
    .catch(e => console.error('Test 1.2 Result: FAIL ✗', e));
```

### Test 1.3: Hub Context

**Objective:** Verify payment method update works in hub context

**Steps:**
1. Navigate to: `http://yoursite.com/hub/property2/index.php?option=com_solidres&view=reservationasset&...`
2. Select payment method
3. Verify success

**Expected Results:**
```
✓ HTTP Status: 200 OK
✓ Response: {"success": true}
✓ Context preserved across requests
```

## Test Suite 2: Context Preservation

### Test 2.1: Parameter Preservation

**Objective:** Verify all context parameters are preserved

**Test Script:**
```javascript
// In browser console
const before = SolidresPayment.getContextParameters();
console.log('Before update:', before);

SolidresPayment.updatePaymentMethod(5)
    .then(data => {
        console.log('Update successful:', data);
        const after = SolidresPayment.getContextParameters();
        console.log('After update:', after);
        
        // Verify parameters match
        const matches = 
            before.property_id === after.property_id &&
            before.hub_id === after.hub_id &&
            before.Itemid === after.Itemid;
        
        console.log('Test 2.1 Result:', matches ? 'PASS ✓' : 'FAIL ✗');
    })
    .catch(e => console.error('Test 2.1 Result: FAIL ✗', e));
```

**Expected:**
```
✓ property_id preserved
✓ hub_id preserved
✓ site_id preserved
✓ Itemid preserved
✓ reservation_id preserved
```

### Test 2.2: Session Persistence

**Objective:** Verify session data persists across contexts

**Test Script:**
```javascript
// Test in root context
console.log('Testing in context:', window.location.pathname);

fetch('/index.php?option=com_solidres&task=reservationasset.updatePaymentMethod&format=json&payment_method_id=5&Itemid=123')
    .then(r => r.json())
    .then(d => {
        console.log('Root context update:', d.success ? 'OK' : 'FAIL');
        
        // Now test in submenu context
        return fetch('/property1/index.php?option=com_solidres&task=reservationasset.updatePaymentMethod&format=json&payment_method_id=6&Itemid=456');
    })
    .then(r => r.json())
    .then(d => {
        console.log('Submenu context update:', d.success ? 'OK' : 'FAIL');
        console.log('Test 2.2 Result:', d.success ? 'PASS ✓' : 'FAIL ✗');
    })
    .catch(e => console.error('Test 2.2 Result: FAIL ✗', e));
```

## Test Suite 3: Edge Cases

### Test 3.1: Missing Itemid

**Objective:** Verify system handles missing Itemid gracefully

**Test Script:**
```javascript
// Request without Itemid - should fall back to session validation
fetch(window.location.pathname + '?option=com_solidres&task=reservationasset.updatePaymentMethod&format=json&payment_method_id=5')
    .then(r => {
        console.log('Status without Itemid:', r.status);
        return r.json();
    })
    .then(d => console.log('Test 3.1 Result:', d.success ? 'PASS ✓' : 'FAIL ✗'))
    .catch(e => console.error('Test 3.1:', e));
```

**Expected:** Success via session validation

### Test 3.2: Wrong Itemid

**Objective:** Verify router corrects wrong Itemid

**Test Script:**
```javascript
// Use root Itemid in submenu context - router should correct it
fetch('/property1/index.php?option=com_solidres&task=reservationasset.updatePaymentMethod&format=json&payment_method_id=5&Itemid=123')
    .then(r => r.json())
    .then(d => {
        console.log('Test 3.2 Result:', d.success ? 'PASS ✓' : 'FAIL ✗');
        console.log('Router should have logged Itemid correction');
    })
    .catch(e => console.error('Test 3.2:', e));
```

**Check logs for:**
```
Solidres Router - Corrected Menu Context: Original Itemid=123, Corrected Itemid=456
```

### Test 3.3: Cross-Context Navigation

**Objective:** Verify reservation survives context changes

**Steps:**
1. Start reservation in root context
2. Navigate to submenu context
3. Update payment method
4. Return to root context
5. Complete reservation

**Verification:**
```javascript
// Check session preserved
console.log('Session ID:', document.cookie.match(/([a-f0-9]{32})/)[1]);
```

### Test 3.4: SEF URLs

**Objective:** Verify fix works with SEF enabled

**Steps:**
1. Enable SEF URLs in Joomla Global Configuration
2. Clear cache
3. Repeat Test 1.2

**Expected:** Same results as with non-SEF URLs

### Test 3.5: Multiple Payment Methods

**Objective:** Verify switching between payment methods works

**Test Script:**
```javascript
// Test rapid payment method switching
async function testSwitching() {
    console.log('Testing payment method switching...');
    
    const methods = [5, 6, 5, 6, 5]; // Qvik, Revolut, Qvik, Revolut, Qvik
    
    for (const methodId of methods) {
        try {
            const result = await SolidresPayment.updatePaymentMethod(methodId);
            console.log(`Method ${methodId}:`, result.success ? '✓' : '✗');
            await new Promise(resolve => setTimeout(resolve, 500)); // 500ms delay
        } catch (e) {
            console.error(`Method ${methodId}: ✗`, e);
            return false;
        }
    }
    
    console.log('Test 3.5 Result: PASS ✓');
    return true;
}

testSwitching();
```

## Test Suite 4: Performance

### Test 4.1: Response Time

**Objective:** Measure AJAX response time

**Test Script:**
```javascript
async function measurePerformance(iterations = 10) {
    const times = [];
    
    for (let i = 0; i < iterations; i++) {
        const start = performance.now();
        
        await fetch(SolidresPayment.buildAjaxUrl('reservationasset.updatePaymentMethod', {
            payment_method_id: 5,
            Itemid: SolidresPayment.findCorrectItemId()
        }));
        
        const end = performance.now();
        times.push(end - start);
    }
    
    const avg = times.reduce((a, b) => a + b) / times.length;
    const min = Math.min(...times);
    const max = Math.max(...times);
    
    console.log('Performance Test Results:');
    console.log('  Iterations:', iterations);
    console.log('  Average:', avg.toFixed(2), 'ms');
    console.log('  Min:', min.toFixed(2), 'ms');
    console.log('  Max:', max.toFixed(2), 'ms');
    console.log('Test 4.1:', avg < 1000 ? 'PASS ✓' : 'FAIL ✗ (>1s)');
}

measurePerformance();
```

**Expected:** Average < 500ms

### Test 4.2: Memory Usage

**Objective:** Check for memory leaks

**Steps:**
1. Open browser Memory profiler
2. Take heap snapshot
3. Trigger 100 payment method updates
4. Take another heap snapshot
5. Compare

**Expected:** No significant memory increase

## Test Suite 5: Error Handling

### Test 5.1: Invalid Session

**Objective:** Verify proper error message for invalid session

**Test Script:**
```javascript
// Clear session cookie
document.cookie.split(";").forEach(c => {
    document.cookie = c.replace(/^ +/, "").replace(/=.*/, "=;expires=" + new Date().toUTCString() + ";path=/");
});

// Try to update
SolidresPayment.updatePaymentMethod(5)
    .then(d => console.log('Test 5.1: FAIL ✗ (should have failed)'))
    .catch(e => {
        console.log('Test 5.1:', e.message.includes('session') ? 'PASS ✓' : 'FAIL ✗');
    });
```

**Expected:** Error message about invalid session

### Test 5.2: Network Error

**Objective:** Verify error handling for network issues

**Test Script:**
```javascript
// Simulate network error by using invalid URL
fetch('http://invalid-domain-12345.com/test')
    .then(r => r.json())
    .catch(e => {
        console.log('Test 5.2:', e.message.includes('Network') ? 'PASS ✓' : 'FAIL ✗');
    });
```

### Test 5.3: Server Error (500)

**Objective:** Verify handling of server errors

**Test Script:**
```javascript
// This would need to be triggered by temporarily breaking the controller
// Check that user sees friendly error message
```

## Test Suite 6: Compatibility

### Test 6.1: Different Browsers

Test in:
- [ ] Chrome/Chromium
- [ ] Firefox
- [ ] Safari
- [ ] Edge

### Test 6.2: Different Devices

Test on:
- [ ] Desktop
- [ ] Tablet
- [ ] Mobile

### Test 6.3: Different Joomla Versions

Test with:
- [ ] Joomla 3.9.x
- [ ] Joomla 3.10.x
- [ ] Joomla 4.x

## Automated Test Runner

Run all tests automatically:

```javascript
async function runAllTests() {
    console.log('=== Solidres Payment Fix Test Suite ===\n');
    
    const tests = [
        { name: 'Test 1.1: Root Context', fn: test1_1 },
        { name: 'Test 1.2: Submenu Context', fn: test1_2 },
        { name: 'Test 1.3: Hub Context', fn: test1_3 },
        { name: 'Test 2.1: Parameter Preservation', fn: test2_1 },
        { name: 'Test 2.2: Session Persistence', fn: test2_2 },
        { name: 'Test 3.1: Missing Itemid', fn: test3_1 },
        { name: 'Test 3.2: Wrong Itemid', fn: test3_2 },
        { name: 'Test 3.5: Multiple Methods', fn: test3_5 },
        { name: 'Test 4.1: Performance', fn: test4_1 }
    ];
    
    let passed = 0;
    let failed = 0;
    
    for (const test of tests) {
        try {
            console.log(`Running ${test.name}...`);
            await test.fn();
            passed++;
            console.log(`✓ ${test.name} PASSED\n`);
        } catch (e) {
            failed++;
            console.error(`✗ ${test.name} FAILED:`, e, '\n');
        }
    }
    
    console.log('=== Test Results ===');
    console.log(`Passed: ${passed}/${tests.length}`);
    console.log(`Failed: ${failed}/${tests.length}`);
    console.log(`Success Rate: ${(passed/tests.length*100).toFixed(1)}%`);
    
    return failed === 0;
}

// Run tests
runAllTests().then(success => {
    console.log('\n' + (success ? '✓ ALL TESTS PASSED' : '✗ SOME TESTS FAILED'));
});
```

## Verification Checklist

After running all tests, verify:

- [ ] All tests pass
- [ ] No 404 errors in any context
- [ ] No JavaScript errors in console
- [ ] No PHP errors in logs
- [ ] Routing logs show correct Itemid resolution
- [ ] Payment logs show successful updates
- [ ] Session data persists
- [ ] Complete reservation works end-to-end
- [ ] Performance is acceptable (<500ms average)
- [ ] Error messages are user-friendly

## Regression Testing

After future updates, re-run:
- Test 1.2 (Submenu Context) - Primary fix
- Test 2.1 (Parameter Preservation)
- Test 3.2 (Wrong Itemid correction)

These three tests cover the core functionality of the fix.

## Troubleshooting Failed Tests

### If Test 1.2 fails (404 error):
1. Verify router.php is installed correctly
2. Check menu items exist
3. Review routing logs
4. Clear Joomla cache

### If Test 2.1 fails (parameters lost):
1. Check JavaScript enhancement is loaded
2. Verify getContextParameters() works
3. Check session cookies

### If Test 3.2 fails (Itemid not corrected):
1. Review router logs for "Corrected Menu Context"
2. Verify findItemIdForContext() method exists
3. Check menu items are published

## Reporting Issues

When reporting issues, include:
1. Test number that failed
2. Browser console screenshot
3. Network tab screenshot
4. Relevant log excerpts
5. Joomla version
6. Solidres version
7. Payment plugin version

## Success Criteria

**Minimum requirements:**
- ✓ Test 1.2 passes (submenu context works)
- ✓ Test 2.1 passes (context preserved)
- ✓ No 404 errors in any context

**Full success:**
- ✓ All tests pass
- ✓ Performance <500ms average
- ✓ No errors in logs
- ✓ End-to-end reservation completes
