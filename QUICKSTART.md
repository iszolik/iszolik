# Quick Start Guide - Payment Method Context Fix

## 🚀 5-Minute Integration Guide

### Prerequisites
- Joomla 3.x vagy újabb
- Solidres komponens
- PHP 7.4+

### Step 1: Copy Files (2 minutes)

```bash
# Copy helper class
cp components/com_solidres/helpers/sessioncontext.php \
   YOUR_JOOMLA_ROOT/components/com_solidres/helpers/

# Copy main confirmation form
cp components/com_solidres/views/reservationasset/tmpl/confirmationform.php \
   YOUR_JOOMLA_ROOT/components/com_solidres/views/reservationasset/tmpl/

# Copy plugin confirmation forms (if using)
cp plugins/solidrespayment/qvik/tmpl/confirmationform.php \
   YOUR_JOOMLA_ROOT/plugins/solidrespayment/qvik/tmpl/

cp plugins/solidrespayment/revolut/tmpl/confirmationform.php \
   YOUR_JOOMLA_ROOT/plugins/solidrespayment/revolut/tmpl/

# Copy language file
cp language/hu-HU/hu-HU.com_solidres.ini \
   YOUR_JOOMLA_ROOT/language/hu-HU/
```

### Step 2: Clear Cache (30 seconds)

```bash
# Via Joomla admin
System → Clear Cache → Clear All

# Or via command line
rm -rf YOUR_JOOMLA_ROOT/cache/*
```

### Step 3: Test (2 minutes)

#### Test Root Context
```
https://yoursite.com/index.php?option=com_solidres&view=reservationasset&layout=confirmation&reservation_id=123
```

✅ Check: Payment method megjelenik?
✅ Check: Nincs 404 hiba?

#### Test Submenu Context
```
https://yoursite.com/foglalas/index.php?option=com_solidres&view=reservationasset&layout=confirmation&reservation_id=123&Itemid=101
```

✅ Check: URL helyes (/foglalas/ benne van)?
✅ Check: Payment method megjelenik?
✅ Check: AJAX működik (F12 → Network → nincs 404)?

#### Test Hub Context
```
https://yoursite.com/index.php?option=com_solidres&view=reservationasset&layout=confirmation&reservation_id=123&hub_id=5&property_id=45
```

✅ Check: hub_id megmarad?
✅ Check: Payment method megjelenik?

### Step 4: Enable Debug (Optional)

Add `&debug=1` to URL:
```
https://yoursite.com/index.php?option=com_solidres&view=reservationasset&layout=confirmation&reservation_id=123&debug=1
```

You should see debug information:
- Context Type (Root/Submenu/Hub)
- All context parameters
- Payment method ID
- Session validation status

---

## 🔧 Integration Patterns

### Pattern 1: Use in PHP Template

```php
<?php
// Include helper
require_once JPATH_SITE . '/components/com_solidres/helpers/sessioncontext.php';

// Get reservation data
$reservationDetails = SolidresSessionContextHelper::getReservationDetails();

// Display payment method
$paymentMethodId = $reservationDetails->guest['payment_method_id'] ?? '';
$paymentMethodName = SolidresSessionContextHelper::getPaymentMethodName($paymentMethodId);
echo htmlspecialchars($paymentMethodName, ENT_QUOTES, 'UTF-8');
?>
```

### Pattern 2: Use in JavaScript

```javascript
// Build AJAX URL
const ajaxUrl = buildAjaxUrl('reservationasset.save', {
    reservation_id: 123,
    format: 'json'
});

// Make request
fetch(ajaxUrl)
    .then(response => response.json())
    .then(data => console.log(data));
```

### Pattern 3: Use in Controller

```php
class SolidresControllerReservationasset extends JControllerLegacy
{
    public function save()
    {
        require_once JPATH_COMPONENT . '/helpers/sessioncontext.php';
        
        // Get context
        $contextParams = SolidresSessionContextHelper::getContextParams();
        
        // Validate
        if (!SolidresSessionContextHelper::validateContext()) {
            throw new Exception('Invalid session context');
        }
        
        // Build redirect URL
        $redirectUrl = SolidresSessionContextHelper::buildContextUrl(
            'reservationasset.confirmation',
            ['reservation_id' => $reservationId]
        );
        
        $this->setRedirect($redirectUrl);
    }
}
```

---

## 🐛 Troubleshooting

### Issue: Payment method nem jelenik meg

**Cause**: Session elveszett vagy reservation_id hibás

**Fix**:
```php
// Check session
$session = Factory::getSession();
$data = $session->get('reservation_details', null, 'com_solidres');
var_dump($data); // Should show reservation object

// Check reservation_id
$reservationId = $app->input->getInt('reservation_id', 0);
echo "Reservation ID: " . $reservationId; // Should be > 0
```

### Issue: 404 hiba submenu context-ben

**Cause**: URL nem őrzi meg a submenu path-ot

**Fix**: Use `buildContextAwareUrl()` instead of hardcoded URLs
```javascript
// ❌ Wrong
const url = '/index.php?task=save';

// ✅ Correct
const url = buildContextAwareUrl('index.php', {task: 'save'});
```

### Issue: hub_id elvész

**Cause**: Context paraméterek nem kerülnek át

**Fix**: Always use context helper
```php
$contextParams = SolidresSessionContextHelper::getContextParams();
// This includes hub_id, property_id, site_id, etc.
```

### Issue: CSRF token error

**Cause**: Session token hiányzik

**Fix**: Add token to AJAX requests
```javascript
const ajaxUrl = buildAjaxUrl('task.name', {
    '<?php echo Session::getFormToken(); ?>': '1'
});
```

---

## 📊 Verification Checklist

After installation, verify:

- [ ] Root context: Payment method megjelenik
- [ ] Submenu context: URL helyes, nincs 404
- [ ] Hub context: hub_id megmarad
- [ ] AJAX requests: Nincs 404 hiba
- [ ] Language constants: Magyar fordítások működnek
- [ ] Debug mode: Információk megjelennek
- [ ] XSS protection: Minden output escaped
- [ ] Session: Fallback működik ha session elvész

---

## 📚 Further Reading

- **Full Documentation**: `PAYMENT_METHOD_CONTEXT_FIX.md`
- **Examples**: `EXAMPLES.md`
- **README**: `README.md`
- **Tests**: Run `php test_session_context.php`

---

## 💡 Best Practices

### DO ✅
- Use `SolidresSessionContextHelper` for session data
- Use `buildContextAwareUrl()` for all URLs
- Use `buildAjaxUrl()` for AJAX requests
- Always escape output with `htmlspecialchars()`
- Validate context with `validateContext()`
- Test in all contexts (root/submenu/hub)

### DON'T ❌
- Don't hardcode URLs (`/index.php?...`)
- Don't access session directly without helper
- Don't forget XSS protection
- Don't ignore context parameters
- Don't skip testing in submenu context
- Don't display raw payment_method_id

---

## 🆘 Support

If you encounter issues:

1. **Check debug mode**: Add `&debug=1` to URL
2. **Check console**: F12 → Console → Look for errors
3. **Check network**: F12 → Network → Look for 404s
4. **Run tests**: `php test_session_context.php`
5. **Review docs**: See `PAYMENT_METHOD_CONTEXT_FIX.md`

---

## ✅ Success Criteria

You know it's working when:

1. Payment method jelenik minden context-ben ✅
2. Nincs 404 hiba AJAX hívásokban ✅
3. Context paraméterek megmaradnak ✅
4. Session fallback működik ✅
5. Debug információk láthatók (ha engedélyezve) ✅

**You're all set! 🎉**
