# Debugging Guide: Solidres Payment Plugin 404 Errors in Submenu Context

## Quick Reference

| Issue | Location | Check |
|-------|----------|-------|
| URL Building | Browser Console | Verify URL structure and parameters |
| Menu Resolution | Joomla Debug | Check active menu item |
| Router Parsing | Component Log | Verify route parsing logic |
| Controller Access | PHP Error Log | Check controller execution |
| Session State | Session Data | Verify reservation context |

## Step-by-Step Debugging Process

### Step 1: Verify Frontend URL Construction

#### 1.1 Check Browser Developer Tools

Open the browser's Developer Tools (F12), go to Network tab, and trigger the payment method update.

**What to check:**
```
Request URL: /property1/index.php?option=com_solidres&task=reservationasset.updatePaymentMethod&Itemid=456
Status Code: 404
```

**Questions to ask:**
- ✓ Is the URL syntactically correct?
- ✓ Are all parameters present (option, task, Itemid)?
- ✓ Does the path match the current page context?

#### 1.2 Console Logging

Add this to your JavaScript (in the payment plugin's confirmationform.php):

```javascript
// Before fetch call
console.group('Payment AJAX Debug');
console.log('Current Location:', {
    href: window.location.href,
    pathname: window.location.pathname,
    origin: window.location.origin
});

const url = buildAjaxUrl('reservationasset.updatePaymentMethod', {
    payment_method_id: methodId,
    property_id: propertyId,
    hub_id: hubId,
    site_id: siteId,
    Itemid: itemId
});

console.log('Built URL:', url);
console.log('URL Components:', {
    base: url.split('?')[0],
    params: new URLSearchParams(url.split('?')[1])
});
console.groupEnd();
```

**Expected output:**
```
Payment AJAX Debug
  Current Location:
    href: "http://example.com/property1/index.php?option=com_solidres&view=reservationasset&..."
    pathname: "/property1/index.php"
    origin: "http://example.com"
  Built URL: "/property1/index.php?option=com_solidres&task=reservationasset.updatePaymentMethod&..."
  URL Components:
    base: "/property1/index.php"
    params: URLSearchParams { ... }
```

### Step 2: Enable Joomla Debug Mode

#### 2.1 Enable Debug in Configuration

Edit `configuration.php`:

```php
public $debug = '1';
public $debug_lang = '1';
public $log_path = '/path/to/logs';
```

Or use Joomla Admin:
1. System → Global Configuration
2. System tab
3. Debug System: Yes
4. Debug Language: Yes

#### 2.2 Check Debug Output

When debug is enabled, Joomla shows routing information at the bottom of the page. For AJAX requests, check:

```
Application: site
Component: com_solidres
Active Menu: [menu_item_id] or [not found]
```

**If "Active Menu" shows "not found":**
- This is the root cause of the 404
- The Itemid doesn't match the current context

### Step 3: Add Custom Logging

#### 3.1 Create Log File Configuration

Create/edit `administrator/components/com_solidres/config/logging.php`:

```php
<?php
// Configure Solidres logging
JLog::addLogger(
    array(
        'text_file' => 'com_solidres.routing.php',
        'text_entry_format' => '{DATETIME} {PRIORITY} {MESSAGE}'
    ),
    JLog::ALL,
    array('com_solidres.routing')
);

JLog::addLogger(
    array(
        'text_file' => 'com_solidres.payment.php',
        'text_entry_format' => '{DATETIME} {PRIORITY} {MESSAGE}'
    ),
    JLog::ALL,
    array('com_solidres.payment')
);
```

#### 3.2 Add Logging to Component Router

Edit `components/com_solidres/router.php`:

```php
class SolidresRouter extends JComponentRouterBase
{
    public function parse(&$segments)
    {
        $app = JFactory::getApplication();
        $input = $app->input;
        $menu = $app->getMenu();
        $active = $menu->getActive();
        
        // Log routing attempt
        JLog::add(
            sprintf(
                'Parse Route - Path: %s, Segments: %s, Active Menu: %s, Itemid: %s, Task: %s',
                JUri::getInstance()->getPath(),
                json_encode($segments),
                $active ? $active->id : 'none',
                $input->getInt('Itemid', 0),
                $input->get('task', '')
            ),
            JLog::DEBUG,
            'com_solidres.routing'
        );
        
        $vars = [];
        
        // ... your routing logic ...
        
        JLog::add(
            sprintf('Parse Route Result - Vars: %s', json_encode($vars)),
            JLog::DEBUG,
            'com_solidres.routing'
        );
        
        return $vars;
    }
}
```

#### 3.3 Add Logging to Controller

Edit `components/com_solidres/controllers/reservationasset.php`:

```php
public function updatePaymentMethod()
{
    $app = JFactory::getApplication();
    $input = $app->input;
    $session = JFactory::getSession();
    
    // Log controller entry
    JLog::add(
        sprintf(
            'Controller Entry - Method: %s, Itemid: %s, Session ID: %s',
            __METHOD__,
            $input->getInt('Itemid', 0),
            $session->getId()
        ),
        JLog::DEBUG,
        'com_solidres.payment'
    );
    
    // Get reservation details from session
    $reservationDetails = $session->get('reservationDetails', null, 'com_solidres');
    
    JLog::add(
        sprintf(
            'Session State - Has Reservation: %s, Context: %s',
            $reservationDetails ? 'yes' : 'no',
            $reservationDetails ? json_encode($reservationDetails->context ?? 'no context') : 'n/a'
        ),
        JLog::DEBUG,
        'com_solidres.payment'
    );
    
    // ... rest of your method ...
}
```

#### 3.4 Check Log Files

Location: `[joomla_root]/logs/`

```bash
# View routing log
tail -f logs/com_solidres.routing.php

# View payment log
tail -f logs/com_solidres.payment.php
```

**Expected output for successful request:**
```
2026-01-25 16:30:00 DEBUG Parse Route - Path: /property1/index.php, Segments: [], Active Menu: 456, Itemid: 456, Task: reservationasset.updatePaymentMethod
2026-01-25 16:30:00 DEBUG Parse Route Result - Vars: {"view":"reservationasset","task":"reservationasset.updatePaymentMethod"}
2026-01-25 16:30:00 DEBUG Controller Entry - Method: updatePaymentMethod, Itemid: 456, Session ID: abc123xyz
2026-01-25 16:30:00 DEBUG Session State - Has Reservation: yes, Context: {"property_id":10,"hub_id":5}
```

**Output for failing request:**
```
2026-01-25 16:30:00 DEBUG Parse Route - Path: /property1/index.php, Segments: [], Active Menu: none, Itemid: 123, Task: reservationasset.updatePaymentMethod
ERROR: Application Instantiation Error: Component not found
```

### Step 4: Trace Menu Item Resolution

#### 4.1 Check Menu Items in Database

```sql
-- Find all Solidres menu items
SELECT 
    m.id,
    m.title,
    m.alias,
    m.path,
    m.link,
    m.menutype,
    m.published
FROM 
    #__menu m
WHERE 
    m.link LIKE '%com_solidres%'
    AND m.published = 1
ORDER BY 
    m.menutype, m.lft;
```

**What to look for:**
- Multiple menu items with same component
- Different Itemid values for different menu types
- Menu paths that should match your submenu structure

#### 4.2 Check Active Menu Item

Add this diagnostic code to your component:

```php
// Add to components/com_solidres/solidres.php (entry point)
$app = JFactory::getApplication();
$menu = $app->getMenu();
$active = $menu->getActive();
$requested_itemid = $app->input->getInt('Itemid', 0);

if ($app->input->get('format') === 'json' || $app->input->get('tmpl') === 'component') {
    // For AJAX requests, log the menu resolution
    error_log(sprintf(
        'Solidres Entry: Path=%s, Active=%s, Requested Itemid=%s, Match=%s',
        JUri::getInstance()->getPath(),
        $active ? $active->id : 'none',
        $requested_itemid,
        ($active && $active->id == $requested_itemid) ? 'YES' : 'NO'
    ));
}
```

Check your PHP error log:
```bash
tail -f /var/log/apache2/error.log
# or
tail -f /var/log/php-fpm/error.log
```

### Step 5: Test Different Context Scenarios

#### 5.1 Root Context Test

```javascript
// In browser console on ROOT context page (e.g., /index.php?option=com_solidres...)
fetch('/index.php?option=com_solidres&task=reservationasset.updatePaymentMethod&format=json&Itemid=123')
    .then(r => r.json())
    .then(data => console.log('Root context:', data))
    .catch(err => console.error('Root context error:', err));
```

#### 5.2 Submenu Context Test

```javascript
// In browser console on SUBMENU context page (e.g., /property1/index.php?option=com_solidres...)
fetch('/property1/index.php?option=com_solidres&task=reservationasset.updatePaymentMethod&format=json&Itemid=456')
    .then(r => r.json())
    .then(data => console.log('Submenu context:', data))
    .catch(err => console.error('Submenu context error:', err));
```

#### 5.3 Cross-Context Test

```javascript
// Test using root Itemid in submenu context
fetch('/property1/index.php?option=com_solidres&task=reservationasset.updatePaymentMethod&format=json&Itemid=123')
    .then(r => r.json())
    .then(data => console.log('Cross context:', data))
    .catch(err => console.error('Cross context error:', err));
```

**Expected results:**
- Root context: ✓ Success
- Submenu context with submenu Itemid: ✓ Success (after fix)
- Cross context: ✗ 404 (expected, shows the issue)

### Step 6: Check .htaccess and URL Rewriting

#### 6.1 Review .htaccess

Check your Joomla `.htaccess` file:

```apache
RewriteEngine On
RewriteBase /

# Check if these rules affect submenu paths
RewriteCond %{REQUEST_FILENAME} !-f
RewriteCond %{REQUEST_FILENAME} !-d
RewriteRule ^(.*)$ index.php [L]
```

**For submenu contexts**, you might need:

```apache
# Allow submenu paths
RewriteCond %{REQUEST_URI} ^/[^/]+/index\.php
RewriteRule ^ - [L]

# Standard Joomla rewrite
RewriteCond %{REQUEST_FILENAME} !-f
RewriteCond %{REQUEST_FILENAME} !-d
RewriteRule ^(.*)$ index.php [L]
```

#### 6.2 Test Without SEF

Temporarily disable SEF URLs:
1. Go to System → Global Configuration
2. SEF URLs: No
3. Test again

If it works without SEF, the issue is in URL rewriting, not routing.

### Step 7: Session State Verification

#### 7.1 Check Session Data

Add this diagnostic endpoint:

```php
// components/com_solidres/controllers/reservationasset.php
public function debugSession()
{
    $session = JFactory::getSession();
    $reservationDetails = $session->get('reservationDetails', null, 'com_solidres');
    
    header('Content-Type: application/json');
    echo json_encode([
        'session_id' => $session->getId(),
        'has_reservation' => !is_null($reservationDetails),
        'reservation_data' => $reservationDetails,
        'session_namespace_keys' => $session->get('com_solidres', [], 'com_solidres')
    ]);
    
    JFactory::getApplication()->close();
}
```

Call it:
```javascript
fetch('/property1/index.php?option=com_solidres&task=reservationasset.debugSession&format=json')
    .then(r => r.json())
    .then(data => console.table(data));
```

#### 7.2 Verify Session Persistence

```javascript
// Test 1: Save something to session
fetch('/property1/index.php?option=com_solidres&task=reservationasset.testSave&format=json&test_data=hello')
    .then(r => r.json())
    .then(data => console.log('Saved:', data));

// Test 2: Retrieve it (in same context)
fetch('/property1/index.php?option=com_solidres&task=reservationasset.testLoad&format=json')
    .then(r => r.json())
    .then(data => console.log('Loaded:', data));

// Test 3: Retrieve from different context
fetch('/index.php?option=com_solidres&task=reservationasset.testLoad&format=json')
    .then(r => r.json())
    .then(data => console.log('Cross-context:', data));
```

## Common Issues and Solutions

### Issue 1: Itemid Not Found

**Symptom:** Active menu is "none" in debug output

**Solution:**
1. Create menu item for each context (root, submenu, hub)
2. Or use dynamic Itemid resolution (see workarounds)

### Issue 2: SEF URL Conflicts

**Symptom:** Works with SEF off, fails with SEF on

**Solution:**
1. Add component exception to SEF plugin
2. Use `format=json` to bypass SEF
3. Update .htaccess rules

### Issue 3: Session Lost in Submenu

**Symptom:** Session data is null in submenu context

**Solution:**
1. Check session cookie domain (must match submenu domain)
2. Verify session cookie path (should be `/`)
3. Use session ID parameter as fallback

### Issue 4: Router Not Called

**Symptom:** No routing log entries

**Solution:**
1. Check if task-based routes bypass router
2. Verify component is properly registered
3. Ensure router.php exists and is loaded

## Workarounds (Temporary Fixes)

### Workaround 1: Dynamic Itemid Finder

```javascript
// In payment plugin confirmationform.php
function findCorrectItemId() {
    // Get all links with com_solidres
    const links = Array.from(document.querySelectorAll('a[href*="com_solidres"]'));
    
    // Find one that matches current path
    const currentPath = window.location.pathname;
    
    for (const link of links) {
        const url = new URL(link.href, window.location.origin);
        if (url.pathname === currentPath) {
            const params = new URLSearchParams(url.search);
            return params.get('Itemid');
        }
    }
    
    // Fallback: use current page's Itemid
    const params = new URLSearchParams(window.location.search);
    return params.get('Itemid') || '0';
}

// Use it
const correctItemId = findCorrectItemId();
const url = buildAjaxUrl('reservationasset.updatePaymentMethod', {
    Itemid: correctItemId,
    // ... other params
});
```

### Workaround 2: Bypass Menu Routing

```php
// In component entry point (components/com_solidres/solidres.php)
$app = JFactory::getApplication();
$task = $app->input->get('task', '');

// For AJAX tasks, set a flag to bypass menu requirement
if (strpos($task, '.') !== false && $app->input->get('format') === 'json') {
    // Set a flag
    $app->input->set('_bypass_menu_check', true);
    
    // Force active menu to any valid Solidres menu item
    $menu = $app->getMenu();
    $items = $menu->getItems('component', 'com_solidres');
    if (!empty($items)) {
        $menu->setActive($items[0]->id);
    }
}
```

### Workaround 3: Session-Only Validation

```php
// In controller
public function updatePaymentMethod()
{
    // Don't require menu context for AJAX
    if ($this->app->input->get('format') === 'json') {
        // Validate by session only
        $session = JFactory::getSession();
        $reservationDetails = $session->get('reservationDetails', null, 'com_solidres');
        
        if (!$reservationDetails) {
            $this->sendJsonError('Invalid session');
            return;
        }
        
        // Process without menu validation
        $this->processPaymentMethod($reservationDetails);
        return;
    }
    
    // Standard validation for non-AJAX
    parent::updatePaymentMethod();
}
```

## Verification Checklist

After implementing fixes, verify:

- [ ] Root context: AJAX calls return 200 OK
- [ ] Submenu context: AJAX calls return 200 OK
- [ ] Hub context: AJAX calls return 200 OK
- [ ] Cross-context session preservation works
- [ ] Payment method updates successfully
- [ ] No PHP errors in log
- [ ] No JavaScript errors in console
- [ ] Reservation completes successfully
- [ ] All context parameters preserved
- [ ] SEF URLs work (if enabled)

## Performance Monitoring

### Add Timing Logs

```javascript
// In JavaScript
const perfStart = performance.now();

fetch(url)
    .then(response => {
        const perfEnd = performance.now();
        console.log(`AJAX took ${perfEnd - perfStart}ms`);
        return response;
    });
```

```php
// In PHP
$start = microtime(true);

// ... your code ...

$end = microtime(true);
JLog::add(
    sprintf('Execution time: %.3f seconds', $end - $start),
    JLog::INFO,
    'com_solidres.performance'
);
```

## Summary

The key to debugging this issue is understanding that **Joomla's routing heavily depends on menu context**, and task-based AJAX calls don't naturally fit into this model. The debug process should:

1. **Verify URL construction** is correct (it likely is)
2. **Check menu item resolution** (this is where it fails)
3. **Trace routing flow** to see where the disconnect occurs
4. **Validate session state** as an alternative to menu context
5. **Implement workarounds** or proper fixes based on findings

Remember: **The URL looks correct, but the routing context is wrong.**
