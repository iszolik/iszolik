# Payment Method Context and Session Handling - Megoldás Dokumentáció

## Probléma leírása

A `confirmationform.php`-ban a `payment_method_id` nem mindig jelent meg helyesen különböző context környezetekben (menü/hub vagy gyökér context). AJAX endpoint hívások 404 hibát adtak context mismatch miatt.

## Megoldás áttekintése

### 1. Session Context Helper (sessioncontext.php)

Létrehoztunk egy központi helper osztályt, amely kezeli:
- Context paraméterek (Itemid, property_id, hub_id, site_id, reservation_id) tárolását és lekérését
- Reservation details session management-et
- Context type detektálást (submenu/hub/root)
- Context-aware URL építést

**Főbb funkciók:**

```php
// Context paraméterek lekérése (session cache-eli a performance-ért)
$contextParams = SolidresSessionContextHelper::getContextParams();

// Reservation details lekérése (session-ból vagy DB-ből)
$reservationDetails = SolidresSessionContextHelper::getReservationDetails($reservationId);

// Context type detektálás
$contextType = SolidresSessionContextHelper::detectContextType();
// Returns: ['is_submenu' => bool, 'is_hub' => bool, 'is_root' => bool, 'current_path' => string]

// Context-aware URL építés
$url = SolidresSessionContextHelper::buildContextUrl('reservationasset.save', ['format' => 'json']);

// Session validáció
$isValid = SolidresSessionContextHelper::validateContext();
```

### 2. Továbbfejlesztett confirmationform.php

A confirmation form mostantól:

#### A) Session context kezelés
- Automatikusan lekéri a reservation details-t session-ból
- Ha nincs session adat, fallback-el az adatbázisból
- Megőrzi az összes context paramétert (Itemid, property_id, hub_id, site_id)

#### B) Context detection
- Detektálja, hogy submenu, hub vagy root context-ben van
- Automatikusan meghatározza a helyes URL építési módot

#### C) JavaScript URL Builder
A template tartalmaz két JavaScript helper funkciót:

```javascript
// Context-aware URL építés
buildContextAwareUrl(baseUrl, additionalParams)

// AJAX URL builder
buildAjaxUrl(task, additionalParams)
```

**Példa használat:**

```javascript
// AJAX request context-aware módon
const ajaxUrl = buildAjaxUrl('reservationasset.save', {
    format: 'json',
    reservation_id: 123
});

fetch(ajaxUrl)
    .then(response => response.json())
    .then(data => {
        // Process response
    });
```

#### D) Payment Method ID megjelenítés
A payment_method_id kiírása mostantól biztonságos és context-aware:

```php
$paymentMethodId = isset($reservationDetails->guest['payment_method_id']) 
    ? $reservationDetails->guest['payment_method_id'] 
    : '';

// Language constant kulcs generálása
$paymentMethodKey = 'SR_PAYMENT_METHOD_' . strtoupper($paymentMethodId);
$paymentMethodName = Text::_($paymentMethodKey);

// Ha nincs fordítás, formázott név
if ($paymentMethodName === $paymentMethodKey) {
    $paymentMethodName = ucfirst(str_replace('_', ' ', $paymentMethodId));
}

echo Text::_('SR_CONFIRMATION_PAYMENT_METHOD') . ': ' . 
     htmlspecialchars($paymentMethodName, ENT_QUOTES, 'UTF-8');
```

### 3. Context-aware URL építés logika

#### Root context
```
https://example.com/index.php?option=com_solidres&task=...&Itemid=123&property_id=45
```

#### Submenu context
```
https://example.com/submenu/index.php?option=com_solidres&task=...&Itemid=123&property_id=45
```

#### Hub context
```
https://example.com/index.php?option=com_solidres&task=...&Itemid=123&hub_id=10&property_id=45
```

### 4. AJAX 404 hibák megelőzése

**Probléma:** Submenu context-ben az AJAX hívások `/index.php`-ra mentek root-ba, nem `/submenu/index.php`-ra.

**Megoldás:** A JavaScript URL builder automatikusan detektálja és megőrzi a teljes path-ot:

```javascript
function buildContextAwareUrl(baseUrl, additionalParams) {
    const pathname = window.location.pathname;
    const origin = window.location.origin;
    
    let fullBaseUrl = baseUrl;
    if (!baseUrl.startsWith('http')) {
        // Submenu context detektálás
        if (pathname.indexOf('index.php') > 0) {
            // Submenu context: /submenu/index.php
            fullBaseUrl = origin + pathname.substring(0, pathname.lastIndexOf('/') + 1) + baseUrl;
        } else {
            // Root context: /index.php
            fullBaseUrl = origin + '/' + baseUrl;
        }
    }
    
    // URL query string építése minden context paraméterrel...
    return fullBaseUrl + '?' + params.toString();
}
```

## Használati példák

### Példa 1: Confirmation form betöltése

```php
// components/com_solidres/views/reservationasset/tmpl/confirmationform.php
<?php
defined('_JEXEC') or die;

use Joomla\CMS\Factory;
use Joomla\CMS\Language\Text;

$session = Factory::getSession();
$app = Factory::getApplication();

// Reservation details lekérése session context-tel
$reservationDetails = $session->get('reservation_details', null, 'com_solidres');

// Context paraméterek
$contextParams = [
    'Itemid' => $app->input->getInt('Itemid', 0),
    'property_id' => $app->input->getInt('property_id', 0),
    'hub_id' => $app->input->getInt('hub_id', 0),
    'site_id' => $app->input->getInt('site_id', 0),
    'reservation_id' => !empty($reservationDetails->id) ? $reservationDetails->id : 0,
];

// Payment method megjelenítés
if (!empty($reservationDetails)) {
    $paymentMethodId = $reservationDetails->guest['payment_method_id'] ?? '';
    $paymentMethodKey = 'SR_PAYMENT_METHOD_' . strtoupper($paymentMethodId);
    $paymentMethodName = Text::_($paymentMethodKey);
    
    echo Text::_('SR_CONFIRMATION_PAYMENT_METHOD') . ': ' . 
         htmlspecialchars($paymentMethodName, ENT_QUOTES, 'UTF-8');
}
?>
```

### Példa 2: AJAX request context-aware módon

```javascript
// Frontend JavaScript
document.addEventListener('DOMContentLoaded', function() {
    // AJAX URL építés context-aware módon
    const ajaxUrl = buildAjaxUrl('reservationasset.validateContext');
    
    fetch(ajaxUrl, {
        method: 'GET',
        headers: {
            'Content-Type': 'application/json'
        },
        credentials: 'same-origin'
    })
    .then(response => {
        if (!response.ok) {
            throw new Error('HTTP error ' + response.status);
        }
        return response.json();
    })
    .then(data => {
        if (!data.success) {
            console.error('Session context validation failed:', data.message);
        }
        console.log('Context validated successfully');
    })
    .catch(error => {
        console.error('AJAX error:', error);
    });
});
```

### Példa 3: Session Context Helper használata

```php
// Controller vagy model-ben
require_once JPATH_COMPONENT . '/helpers/sessioncontext.php';

class SolidresControllerReservationasset extends JControllerLegacy
{
    public function save()
    {
        // Context paraméterek lekérése
        $contextParams = SolidresSessionContextHelper::getContextParams();
        
        // Reservation details lekérése
        $reservationDetails = SolidresSessionContextHelper::getReservationDetails();
        
        // Context validáció
        if (!SolidresSessionContextHelper::validateContext()) {
            throw new Exception('Invalid session context');
        }
        
        // Context-aware redirect URL
        $redirectUrl = SolidresSessionContextHelper::buildContextUrl(
            'reservationasset.confirmation',
            ['reservation_id' => $reservationDetails->id]
        );
        
        $this->setRedirect($redirectUrl);
    }
}
```

## Tesztelési forgatókönyvek

### 1. Root context
- URL: `https://example.com/index.php?option=com_solidres&view=reservationasset&layout=confirmation&reservation_id=123`
- Ellenőrizendő: payment_method_id helyesen megjelenik
- AJAX URL: `https://example.com/index.php?option=com_solidres&task=...&reservation_id=123`

### 2. Submenu context
- URL: `https://example.com/foglalas/index.php?option=com_solidres&view=reservationasset&layout=confirmation&reservation_id=123&Itemid=101`
- Ellenőrizendő: payment_method_id helyesen megjelenik, AJAX nem 404
- AJAX URL: `https://example.com/foglalas/index.php?option=com_solidres&task=...&reservation_id=123&Itemid=101`

### 3. Hub context
- URL: `https://example.com/index.php?option=com_solidres&view=reservationasset&layout=confirmation&reservation_id=123&hub_id=5&property_id=10`
- Ellenőrizendő: payment_method_id helyesen megjelenik, hub_id megmarad
- AJAX URL: `https://example.com/index.php?option=com_solidres&task=...&reservation_id=123&hub_id=5&property_id=10`

## Session context életciklus

1. **Booking indítása**: Context paraméterek mentése session-ba
2. **Payment selection**: payment_method_id mentése reservation_details-ba
3. **Confirmation page**: Reservation details és context paraméterek lekérése
4. **AJAX request**: Context paraméterek megőrzése URL-ben
5. **Post-booking**: Session tisztítás vagy megőrzés a receipt-hez

## Debug információk

A confirmation form tartalmaz egy debug section-t, amely megjeleníti:
- Context type (Root/Submenu/Hub)
- Összes context paramétert (Itemid, property_id, hub_id, site_id, reservation_id)
- Payment method ID-t
- Current path-ot

Ez segít a fejlesztésben és hibakeresésben.

## Kompatibilitás

- **Joomla 3.x+**: Factory, Session, Uri használat
- **Solidres komponens**: Reservation details struktúra
- **Modern browsers**: Fetch API, URLSearchParams támogatás
- **Backward compatible**: Fallback-ek IE11-hez (ha szükséges)

## Összefoglalás

Ez a megoldás biztosítja, hogy:
1. ✅ A payment_method_id mindig helyesen megjelenik minden context-ben
2. ✅ Nincs session context mismatch
3. ✅ AJAX endpoint hívások nem adnak 404 hibát
4. ✅ Context paraméterek megmaradnak az URL-ekben
5. ✅ Session adatok helyesen cache-elődnek és betöltődnek
6. ✅ Biztonságos kiírás (XSS védelem)
7. ✅ Debug információk fejlesztéshez
